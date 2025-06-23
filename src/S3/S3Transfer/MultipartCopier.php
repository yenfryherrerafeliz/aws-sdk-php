<?php

namespace Aws\S3\S3Transfer;

use Aws\ResultInterface;
use Aws\S3\S3ClientInterface;
use Aws\S3\S3Transfer\Models\CopyResponse;
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
    private array $source;
    public function __construct(
        S3ClientInterface $s3Client,
        array $createMultipartArgs,
        array $config,
        array $source,
        ?string $uploadId = null,
        array $parts = [],
        ?TransferProgressSnapshot $currentSnapshot = null,
        ?TransferListenerNotifier $listenerNotifier = null,
    ) {

        if (empty($source['Bucket']) || empty($source['Key'])) {
            throw new \InvalidArgumentException(
                "The source array must contain 'Bucket' and 'Key' parameters"
            );
        }

        // Validate same source/destination
        if ($source['Bucket'] === $createMultipartArgs['Bucket']
            && $source['Key'] === $createMultipartArgs['Key']) {
            throw new \InvalidArgumentException(
                "Source and destination cannot be the same object"
            );
        }
        parent::__construct(
            s3Client: $s3Client,
            createMultipartArgs: $createMultipartArgs,
            config: $config,
            uploadId: $uploadId,
            parts: $parts,
            currentSnapshot: $currentSnapshot,
            listenerNotifier: $listenerNotifier
        );

        $this->source = $source;
    }

    public function copy(): PromiseInterface
    {
        try {
            $result = $this->promise()->wait();
            return Create::promiseFor($result);
        } catch (Throwable $e) {
            return Create::rejectionFor($e);
        }
    }

    protected function processMultipartOperation(): PromiseInterface
    {
        return $this->copyParts();
    }

    protected function getTotalSize(): int
    {
        if (! isset($this->calculatedObjectSize)) {
            if (isset($this->config['object_size'])) {
                $this->calculatedObjectSize = (int) $this->config['object_size'];
            } else {
                $this->calculatedObjectSize = $this->getSourceSize();
            }
        }
        return $this->calculatedObjectSize;
    }

    protected function createResponse(ResultInterface $result): CopyResponse
    {
        return new CopyResponse($result->toArray());
    }

    private function copyParts(): PromiseInterface
    {
        $partSize = $this->config['part_size'];
        $objectSize = $this->getTotalSize();
        $totalParts = (int) ceil($objectSize / $partSize);

        if ($totalParts > AbstractMultipartUploader::PART_MAX_NUM) {
            throw new \InvalidArgumentException('Total parts cannot exceed 10000');
        }

        $commands = [];

        for ($partNumber = 1; $partNumber <= $totalParts; $partNumber++) {
            $start = ($partNumber - 1) * $partSize;
            $end = min($start + $partSize - 1, $objectSize - 1);

            $copySource = $this->getSourcePath($this->source);

            $copyPartArgs = [
                ...$this->createMultipartArgs,
                'UploadId' => $this->uploadId,
                'PartNumber' => $partNumber,
                'CopySource' => $copySource,
                'CopySourceRange' => "bytes=$start-$end",
            ];

            $copyPartArgs['requestArgs'] = [...$copyPartArgs];

            $command = $this->s3Client->getCommand('UploadPartCopy', $copyPartArgs);
            $commands[] = $command;
        }

        return (new CommandPool(
            $this->s3Client,
            $commands,
            [
                'concurrency' => $this->config['concurrency'],
                'fulfilled' => function (ResultInterface $result, $index) use ($commands) {
                    $command = $commands[$index];
                    $this->collectPart($result, $command);
                    $this->partCompleted(
                        $command['requestArgs']['ContentLength'] ?? $this->config['part_size'],
                        $command['requestArgs']
                    );
                },
                'rejected' => function (Throwable $e) {
                    $this->partFailed($e);
                    throw $e;
                },
            ]
        ))->promise();
    }



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
                0,
                $e
            );
        }
    }

}