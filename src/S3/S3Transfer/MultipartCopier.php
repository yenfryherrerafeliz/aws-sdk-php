<?php

namespace Aws\S3\S3Transfer;

use Aws\CommandInterface;
use Aws\ResultInterface;
use Aws\S3\S3ClientInterface;
use Aws\S3\S3Transfer\Models\CopyResponse;
use Aws\S3\S3Transfer\Progress\TransferListenerNotifier;
use Aws\Arn\ArnParser;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use Throwable;

/**
 * Multipart copier implementation.
 *
 * @param array $source Location of the data in the format of an array with:
 *        - source_bucket
 *        - source_key
 *        - source_version_id (optional)
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
        $this->calculatedObjectSize = $this->getSourceSize($this->source);
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
        return $this->calculatedObjectSize;
    }

    protected function extractPartData(ResultInterface $result, CommandInterface $command): array
    {
        $copyResult = $result['CopyPartResult'];
        $partData = [
            'PartNumber' => $command['PartNumber'],
            'ETag' => $copyResult['ETag']
        ];

        // Add checksum from the CopyPartResult
        if (isset($result['CopyPartResult']['ChecksumCRC32'])) {
            $partData['ChecksumCRC32'] = $result['CopyPartResult']['ChecksumCRC32'];
        }

        return $partData;
    }

    protected function createResponse(ResultInterface $result): CopyResponse
    {
        return new CopyResponse($result->toArray());
    }

    protected function getCompletionArgs(): array
    {
        return []; // No additional args needed for copy completion
    }

    private function copyParts(): PromiseInterface
    {
        $partSize = $this->config['part_size'];

        // Validate part size
        if ($partSize < AbstractMultipartUploader::PART_MIN_SIZE || $partSize > AbstractMultipartUploader::PART_MAX_SIZE) {
            throw new \InvalidArgumentException('Part size must be between 5MiB and 5GiB');
        }

        $totalParts = ceil($this->calculatedObjectSize / $partSize);

        // Check if total parts doesn't exceed S3's limit
        if ($totalParts > AbstractMultipartUploader::PART_MAX_NUM) {
            throw new \InvalidArgumentException('Total parts cannot exceed 10000');
        }

        $commands = [];

        for ($partNumber = 1; $partNumber <= $totalParts; $partNumber++) {
            $start = ($partNumber - 1) * $partSize;
            $end = min($start + $partSize - 1, $this->calculatedObjectSize - 1);

            $copySource = $this->getSourcePath($this->source);

            $copyPartArgs = [
                ...$this->createMultipartArgs,
                'UploadId' => $this->uploadId,
                'PartNumber' => $partNumber,
                'CopySource' => $copySource,
                'CopySourceRange' => "bytes=$start-$end",
            ];

            // Include checksum settings
            if (isset($this->createMultipartArgs['ChecksumAlgorithm'])) {
                $copyPartArgs['ChecksumAlgorithm'] = $this->createMultipartArgs['ChecksumAlgorithm'];
                $copyPartArgs['RequestChecksumAlgorithm'] = $this->createMultipartArgs['ChecksumAlgorithm'];
            }

            $requestArgs = [...$copyPartArgs];
            $command = $this->s3Client->getCommand('UploadPartCopy', $copyPartArgs);
            $command['requestArgs'] = $requestArgs;
            $commands[] = $command;
        }

        return $this->createCommandPool(
            $commands,
            function (ResultInterface $result, $index) use ($commands) {
                $command = $commands[$index];
                $this->collectPart($result, $command);
                $this->partCompleted(
                    $this->config['part_size'],
                    $command['requestArgs']
                );
            },
            function (Throwable $e) {
                $this->partFailed($e);
                throw $e;
            }
        );
    }

    private function getSourcePath(array $source): string
    {
        $path = "/{$source['Bucket']}/";
        if (ArnParser::isArn($source['Bucket'])) {
            try {
                new AccessPointArn($source['Bucket']);
                $path = "{$source['Bucket']}/object/";
            } catch (\Exception $e) {
                throw new \InvalidArgumentException(
                    'Provided ARN was a not a valid S3 access point ARN ('
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