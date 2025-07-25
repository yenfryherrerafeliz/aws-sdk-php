<?php
namespace Aws\S3\S3Transfer;

use Aws\ResultInterface;
use Aws\S3\S3ClientInterface;
use Aws\S3\S3Transfer\Models\CopyResult;
use Aws\S3\S3Transfer\Progress\TransferListenerNotifier;
use Aws\S3\S3Transfer\Progress\TransferProgressSnapshot;
use Aws\Arn\ArnParser;
use Aws\Arn\AccessPointArn;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use Aws\CommandPool;
use Throwable;

/**
 * Multipart copier implementation.
 *
 * Handles copying large objects between S3 locations using multipart copy.
 *
 * @property array $source Location of the data in the format of an array with:
 *        - Bucket - Source bucket name
 *        - Key - Source object key
 *        - VersionId - Optional source version ID
 */
class MultipartCopier extends AbstractMultipartUploader
{
    /** @var array */
    private array $source;

    /**
     * @param S3ClientInterface $s3Client
     * @param array $requestArgs
     * @param array $config
     * @param array $source
     * @param string|null $uploadId
     * @param array $parts
     * @param TransferProgressSnapshot|null $currentSnapshot
     * @param TransferListenerNotifier|null $listenerNotifier
     */
    public function __construct(
        S3ClientInterface $s3Client,
        array $requestArgs,
        array $config,
        array $source,
        ?string $uploadId = null,
        array $parts = [],
        ?TransferProgressSnapshot $currentSnapshot = null,
        ?TransferListenerNotifier $listenerNotifier = null
    ) {

        $this->validateConfig($config);

        if (empty($source['Bucket']) || empty($source['Key'])) {
            throw new \InvalidArgumentException("The source array must contain 'Bucket' and 'Key'");
        }
        if ($source['Bucket'] === $requestArgs['Bucket']
            && $source['Key'] === $requestArgs['Key']
        ) {
            throw new \InvalidArgumentException("Source and destination cannot be the same object");
        }

        parent::__construct(
            $s3Client,
            $requestArgs,
            $config,
            $uploadId,
            $parts,
            $currentSnapshot,
            $listenerNotifier
        );

        $this->source = $source;
    }

    /**
     * @return CopyResult
     */
    public function copy(): CopyResult
    {
        return $this->promise()->wait();
    }

    /**
     * @return PromiseInterface
     */
    protected function processMultipartOperation(): PromiseInterface
    {
        return $this->copyParts();
    }

    /**
     * @return int
     */
    protected function getTotalSize(): int
    {
        return $this->calculatedObjectSize ??= isset($this->config['object_size'])
            ? (int) $this->config['object_size']
            : $this->getSourceSize();
    }

    /**
     * @param ResultInterface $result
     * @return CopyResult
     */
    protected function createResponse(ResultInterface $result): CopyResult
    {
        return new CopyResult($result->toArray());
    }

    /**
     * @return PromiseInterface
     */
    private function copyParts(): PromiseInterface
    {
        $partSize = $this->calculatePartSize();
        $objectSize = $this->getTotalSize();
        $totalParts = (int) ceil($objectSize / $partSize);

        $commands = [];

        for ($partNumber = 1; $partNumber <= $totalParts; $partNumber++) {
            $start = ($partNumber - 1) * $partSize;
            $end = min($start + $partSize - 1, $objectSize - 1);

            $length = $end - $start + 1;
            $copySource = $this->getSourcePath($this->source);
            $copyPartArgs = [
                ...$this->requestArgs,
                'UploadId' => $this->uploadId,
                'PartNumber' => $partNumber,
                'CopySource' => $copySource,
                'CopySourceRange' => "bytes={$start}-{$end}",
                'ContentLength' => $length,
            ];

            $command = $this->s3Client->getCommand('UploadPartCopy', $copyPartArgs);
            $commands[] = $command;
        }

        return (new CommandPool($this->s3Client, $commands, [
            'concurrency' => $this->config['concurrency'],
            'fulfilled' => function (ResultInterface $result, $index) use ($commands) {
                $command = $commands[$index];
                $this->collectPart($result, $command);
                $commandArgs = $command->toArray();
                $this->partCompleted(
                    $commandArgs['ContentLength'],
                    $commandArgs
                );
            },
            'rejected' => function (Throwable $e) {
                $this->partFailed($e);
                throw $e;
            },
        ]))->promise();
    }

    /**
     * @param array $source
     * @return string
     */
    protected function getSourcePath(array $source): string
    {
        $path = "/{$source['Bucket']}/";
        if (ArnParser::isArn($source['Bucket'])) {
            try {
                new AccessPointArn($source['Bucket']);
                $path = "{$source['Bucket']}/object/";
            } catch (\Exception $e) {
                throw new \InvalidArgumentException(
                    'Provided ARN was not a valid S3 access point ARN ('
                    . $e->getMessage() . ')',
                    0,
                    $e
                );
            }
        }

        $sourcePath = $path . rawurlencode($source['Key']);
        if (isset($source['VersionId'])) {
            $sourcePath .= "?versionId={$source['VersionId']}";
        }

        return $sourcePath;
    }

    /**
     * @return int
     */
    private function getSourceSize(): int
    {
        try {
            $result = $this->s3Client->headObject([
                'Bucket' => $this->source['Bucket'],
                'Key' => $this->source['Key'],
                'VersionId' => $this->source['VersionId'] ?? null,
            ]);

            return $result['ContentLength'];
        } catch (Throwable $e) {
            throw new \RuntimeException(
                "Failed to get source object size: " . $e->getMessage(),
                $e->getStatusCode() ?? 0
            );
        }
    }
}
