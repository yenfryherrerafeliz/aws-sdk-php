<?php

namespace Aws\S3\S3Transfer;

use Aws\CommandInterface;
use Aws\CommandPool;
use Aws\ResultInterface;
use Aws\S3\S3ClientInterface;
use Aws\S3\S3Transfer\Progress\TransferListener;
use Aws\S3\S3Transfer\Progress\TransferListenerNotifier;
use GuzzleHttp\Promise\Coroutine;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\PromisorInterface;
use Throwable;

/**
 * Abstract base class for multipart operations (upload/copy).
 */
abstract class AbstractMultipartUploader implements PromisorInterface
{
    public const PART_MIN_SIZE = 5 * 1024 * 1024; // 5 MiB
    public const PART_MAX_SIZE = 5 * 1024 * 1024 * 1024; // 5 GiB
    public const PART_MAX_NUM = 10000;

    /** @var S3ClientInterface */
    protected readonly S3ClientInterface $s3Client;

    /** @var array @ */
    protected readonly array $createMultipartArgs;

    /** @var array @ */
    protected readonly array $config;

    /** @var string|null */
    protected string|null $uploadId;

    /** @var array @ */
    protected array $parts;
    /** @var array @ */
    protected array $partFns = [];

    /** @var int */
    protected int $calculatedObjectSize;

    /** @var array */
    private array $deferFns = [];

    /** @var TransferListenerNotifier|null */
    protected ?TransferListenerNotifier $listenerNotifier;

    /** Tracking Members */
    /** @var TransferProgressSnapshot|null */
    protected ?TransferProgressSnapshot $currentSnapshot;

    /**
     * @param S3ClientInterface $s3Client
     * @param array $createMultipartArgs
     * @param array $config
     * @param string|null $uploadId
     * @param array $parts
     * @param TransferProgressSnapshot|null $currentSnapshot
     * @param TransferListenerNotifier|null $listenerNotifier
     */
    public function __construct(
        S3ClientInterface         $s3Client,
        array                     $createMultipartArgs,
        array                     $config,
        ?string                   $uploadId = null,
        array                     $parts = [],
        ?TransferProgressSnapshot $currentSnapshot = null,
        ?TransferListenerNotifier $listenerNotifier = null,
    )
    {
        $this->s3Client = $s3Client;
        $this->createMultipartArgs = $createMultipartArgs;
        $this->validateConfig($config);
        $this->config = $config;
        $this->uploadId = $uploadId;
        $this->parts = $parts;
        $this->currentSnapshot = $currentSnapshot;
        $this->listenerNotifier = $listenerNotifier;
    }

    /**
     * @param array $config
     *
     * @return void
     */
    protected function validateConfig(array &$config): void
    {
        if (isset($config['part_size'])) {
            if ($config['part_size'] < self::PART_MIN_SIZE
                || $config['part_size'] > self::PART_MAX_SIZE) {
                throw new \InvalidArgumentException(
                    "The config `part_size` value must be between "
                    . self::PART_MIN_SIZE . " and " . self::PART_MAX_SIZE . "."
                );
            }
        } else {
            $config['part_size'] = self::PART_MIN_SIZE;
        }
    }

    /**
     * @return string|null
     */
    public function getUploadId(): ?string
    {
        return $this->uploadId;
    }

    /**
     * @return array
     */
    public function getParts(): array
    {
        return $this->parts;
    }


    /**
     * Get the current progress snapshot.
     * @return TransferProgressSnapshot|null
     */
    public function getCurrentSnapshot(): ?TransferProgressSnapshot
    {
        return $this->currentSnapshot;
    }

    /**
     * @return PromiseInterface
     */
    public function promise(): PromiseInterface
    {
        return Coroutine::of(function () {
            try {
                yield $this->createMultipartUpload();
                yield $this->processMultipartOperation();
                $result = yield $this->completeMultipartUpload();
                yield Create::promiseFor($this->createResponse($result));
            } catch (Throwable $e) {
                $this->operationFailed($e);
                yield Create::rejectionFor($e);
            } finally {
                $this->callDeferredFns();
            }
        });
    }

    /**
     * @return PromiseInterface
     */
    protected function createMultipartUpload(): PromiseInterface
    {
        $requestArgs = [...$this->createMultipartArgs];
        $this->operationInitiated($requestArgs);
        $command = $this->s3Client->getCommand(
            'CreateMultipartUpload',
            $requestArgs
        );

        return $this->s3Client->executeAsync($command)
            ->then(function (ResultInterface $result) {
                $this->uploadId = $result['UploadId'];
                return $result;
            });
    }

    /**
     * @return PromiseInterface
     */
    protected function completeMultipartUpload(): PromiseInterface
    {
        $this->sortParts();

        $parts = array_map(function ($part) {
            $partData = [
                'PartNumber' => $part['PartNumber'],
                'ETag' => $part['ETag']
            ];

            $checksumTypes = [
                'ChecksumCRC32',
                'ChecksumCRC32C',
                'ChecksumSHA1',
                'ChecksumSHA256'
            ];

            foreach ($checksumTypes as $checksumType) {
                if (!empty($part[$checksumType])) {
                    $partData[$checksumType] = $part[$checksumType];
                }
            }

            return $partData;
        }, $this->parts);

        $completeMultipartUploadArgs = [
            ...$this->createMultipartArgs,
            'UploadId' => $this->uploadId,
            'MultipartUpload' => [
                'Parts' => $parts
            ]
        ];

        if (isset($this->createMultipartArgs['ChecksumAlgorithm'])) {
            $completeMultipartUploadArgs['ChecksumAlgorithm'] =
                $this->createMultipartArgs['ChecksumAlgorithm'];
        }

        if (isset($this->calculatedObjectSize)) {
            $completeMultipartUploadArgs['MpuObjectSize'] = $this->calculatedObjectSize;
        }

        $command = $this->s3Client->getCommand(
            'CompleteMultipartUpload',
            $completeMultipartUploadArgs
        );

        return $this->s3Client->executeAsync($command)
            ->then(function (ResultInterface $result) {
                $this->operationCompleted($result);
                return $result;
            });
    }


    /**
     * @return PromiseInterface
     */
    protected function abortMultipartUpload(): PromiseInterface
    {
        $command = $this->s3Client->getCommand('AbortMultipartUpload', [
            ...$this->createMultipartArgs,
            'UploadId' => $this->uploadId,
        ]);

        return $this->s3Client->executeAsync($command);
    }

    /**
     * @return void
     */
    protected function sortParts(): void
    {
        usort($this->parts, function ($partOne, $partTwo) {
            return $partOne['PartNumber'] <=> $partTwo['PartNumber'];
        });
    }

    /**
     * @param ResultInterface $result
     * @param CommandInterface $command
     * @return void
     */
    protected function collectPart(ResultInterface $result, CommandInterface $command): void
    {
        $copyResult = $result['CopyPartResult'];

        $partData = [
            'PartNumber' => $command['PartNumber'],
            'ETag' => $copyResult['ETag']
        ];

        if (isset($command['ChecksumAlgorithm'])) {
            $checksumMemberName = 'Checksum' . strtoupper($command['ChecksumAlgorithm']);
            $partData[$checksumMemberName] = $copyResult[$checksumMemberName] ?? null;
        }
        $this->parts[] = $partData;
    }

    /**
     * @param array $commands
     * @param callable $fulfilledCallback
     * @param callable $rejectedCallback
     * @return PromiseInterface
     */
    protected function createCommandPool(array $commands, callable $fulfilledCallback, callable $rejectedCallback): PromiseInterface
    {
        return (new CommandPool(
            $this->s3Client,
            $commands,
            [
                'concurrency' => $this->config['concurrency'],
                'fulfilled' => $fulfilledCallback,
                'rejected' => $rejectedCallback
            ]
        ))->promise();
    }

    // Progress tracking methods

    /**
     * @param array $requestArgs
     * @return void
     */
    protected function operationInitiated(array $requestArgs): void
    {
        if ($this->currentSnapshot === null) {
            $this->currentSnapshot = new TransferProgressSnapshot(
                $requestArgs['Key'],
                0,
                $this->getTotalSize()
            );
        }

        $this->listenerNotifier?->transferInitiated([
            TransferListener::REQUEST_ARGS_KEY => $requestArgs,
            TransferListener::PROGRESS_SNAPSHOT_KEY => $this->currentSnapshot
        ]);
    }


    /**
     * @param ResultInterface $result
     * @return void
     */
    protected function operationCompleted(ResultInterface $result): void
    {
        $newSnapshot = new TransferProgressSnapshot(
            $this->currentSnapshot->getIdentifier(),
            $this->getTotalSize(),
            $this->getTotalSize(),
            $result->toArray()
        );
        $this->currentSnapshot = $newSnapshot;
        $this->listenerNotifier?->transferComplete([
            TransferListener::REQUEST_ARGS_KEY => $this->createMultipartArgs,
            TransferListener::PROGRESS_SNAPSHOT_KEY => $this->currentSnapshot
        ]);
    }

    /**
     * @param Throwable $reason
     * @return void
     */
    protected function operationFailed(Throwable $reason): void
    {
        if (!empty($this->uploadId)) {
            $this->abortMultipartUpload()->wait();
        }
        $this->listenerNotifier?->transferFail([
            TransferListener::REQUEST_ARGS_KEY => $this->createMultipartArgs,
            TransferListener::PROGRESS_SNAPSHOT_KEY => $this->currentSnapshot,
            'reason' => $reason
        ]);
    }

    /**
     * @param int $partSize
     * @param array $requestArgs
     * @return void
     */
    protected function partCompleted(int $partSize, array $requestArgs): void
    {
        $newSnapshot = new TransferProgressSnapshot(
            $this->currentSnapshot->getIdentifier(),
            $this->currentSnapshot->getTransferredBytes() + $partSize,
            $this->getTotalSize()
        );
        $this->currentSnapshot = $newSnapshot;
        $this->listenerNotifier?->bytesTransferred([
            TransferListener::REQUEST_ARGS_KEY => $requestArgs,
            TransferListener::PROGRESS_SNAPSHOT_KEY => $this->currentSnapshot
        ]);
    }

    /**
     * @return void
     */
    protected function callDeferredFns(): void
    {
        foreach ($this->deferFns as $fn) {
            $fn();
        }

        $this->deferFns = [];
    }

    /**
     * @param Throwable $reason
     * @return void
     */
    protected function partFailed(Throwable $reason): void
    {
        $this->operationFailed($reason);
    }

    /**
     * @param array $requestArgs
     * @return bool
     */
    protected function containsChecksum(array $requestArgs): bool
    {
        static $algorithms = [
            'ChecksumCRC32',
            'ChecksumCRC32C',
            'ChecksumCRC64NVME',
            'ChecksumSHA1',
            'ChecksumSHA256',
        ];

        foreach ($algorithms as $algorithm) {
            if (isset($requestArgs[$algorithm])) {
                return true;
            }
        }

        return false;
    }
    // Abstract methods that must be implemented by concrete classes

    /**
     * @return PromiseInterface
     */
    abstract protected function processMultipartOperation(): PromiseInterface;

    /**
     * @return int
     */
    abstract protected function getTotalSize(): int;

    /**
     * @param ResultInterface $result
     * @param CommandInterface $command
     * @return array
     */
    abstract protected function extractPartData(ResultInterface $result, CommandInterface $command): array;

    /**
     * @param ResultInterface $result
     * @return mixed
     */
    abstract protected function createResponse(ResultInterface $result): mixed;

    /**
     * @return array
     */
    abstract protected function getCompletionArgs(): array;

}