<?php
namespace Aws\S3\S3Transfer;

use Aws\CommandInterface;
use Aws\HashingStream;
use Aws\PhpHash;
use Aws\ResultInterface;
use Aws\S3\S3ClientInterface;
use Aws\S3\S3Transfer\Models\UploadResponse;
use Aws\S3\S3Transfer\Models\CopyResponse;
use Aws\S3\S3Transfer\Progress\TransferProgressSnapshot;
use Aws\S3\S3Transfer\Progress\TransferListenerNotifier;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\StreamInterface as Stream;

/**
 * Multipart uploader implementation.
 */
class MultipartUploaderAbstract extends AbstractMultipartUploader
{
    private StreamInterface $body;
    protected int $calculatedObjectSize;
    private array $deferFns = [];

    public function __construct(
        S3ClientInterface $s3Client,
        array $createMultipartArgs,
        array $config,
        string | StreamInterface $source,
        ?string $uploadId = null,
        array $parts = [],
        ?TransferProgressSnapshot $currentSnapshot = null,
        ?TransferListenerNotifier $listenerNotifier = null,
    ) {
        parent::__construct(
            $s3Client,
            $createMultipartArgs,
            $config,
            $uploadId,
            $parts,
            $currentSnapshot,
            $listenerNotifier
        );

        $this->body = $this->parseBody($source);
    }

    public function getCalculatedObjectSize(): int
    {
        return $this->calculatedObjectSize;
    }

    protected function processMultipartOperation(): PromiseInterface
    {
        return $this->uploadParts();
    }

    protected function getTotalSize(): int
    {
        return $this->body->getSize();
    }

    protected function extractPartData(ResultInterface $result, CommandInterface $command): array
    {
        $checksumResult = $command->getName() === 'UploadPart'
            ? $result
            : $result[$command->getName() . 'Result'];

        $partData = [
            'PartNumber' => $command['PartNumber'],
            'ETag' => $result['ETag'],
        ];

        if (isset($command['ChecksumAlgorithm'])) {
            $checksumMemberName = 'Checksum' . strtoupper($command['ChecksumAlgorithm']);
            $partData[$checksumMemberName] = $checksumResult[$checksumMemberName] ?? null;
        }

        return $partData;
    }

    protected function createResponse(ResultInterface $result): UploadResponse
    {
        return new UploadResponse($result->toArray());
    }

    protected function getCompletionArgs(): array
    {
        $args = ['MpuObjectSize' => $this->calculatedObjectSize];

        if ($this->containsChecksum($this->createMultipartArgs)) {
            $args['ChecksumType'] = 'FULL_OBJECT';
        }

        return $args;
    }

    protected function cleanup(): void
    {
        $this->callDeferredFns();
    }

    private function uploadParts(): PromiseInterface
    {
        $this->calculatedObjectSize = 0;
        $isSeekable = $this->body->isSeekable();
        $partSize = $this->config['part_size'];
        $commands = [];

        for ($partNo = count($this->parts) + 1;
             $isSeekable
                 ? $this->body->tell() < $this->body->getSize()
                 : !$this->body->eof();
             $partNo++
        ) {
            if ($isSeekable) {
                $readSize = min($partSize, $this->body->getSize() - $this->body->tell());
            } else {
                $readSize = $partSize;
            }

            $partBody = Utils::streamFor($this->body->read($readSize));

            // To make sure we do not create an empty part when
            // we already reached the end of file.
            if (!$isSeekable && $this->body->eof() && $partBody->getSize() === 0) {
                break;
            }

            $uploadPartCommandArgs = [
                ...$this->createMultipartArgs,
                'UploadId' => $this->uploadId,
                'PartNumber' => $partNo,
                'ContentLength' => $partBody->getSize(),
            ];

            // To get `requestArgs` when notifying the bytesTransfer listeners.
            $uploadPartCommandArgs['requestArgs'] = [...$uploadPartCommandArgs];

            // Attach body
            $uploadPartCommandArgs['Body'] = $this->decorateWithHashes(
                $partBody,
                $uploadPartCommandArgs
            );

            $command = $this->s3Client->getCommand('UploadPart', $uploadPartCommandArgs);
            $commands[] = $command;
            $this->calculatedObjectSize += $partBody->getSize();

            if ($partNo > self::PART_MAX_NUM) {
                return Create::rejectionFor(
                    "The max number of parts has been exceeded. " .
                    "Max = " . self::PART_MAX_NUM
                );
            }
        }

        return $this->createCommandPool(
            $commands,
            function (ResultInterface $result, $index) use ($commands) {
                $command = $commands[$index];
                $this->collectPart($result, $command);
                // Part Upload Completed Event
                $this->partCompleted(
                    $command['ContentLength'],
                    $command['requestArgs']
                );
            },
            function (Throwable $e) {
                $this->partFailed($e);
            }
        );
    }

    private function parseBody(string | StreamInterface $source): StreamInterface
    {
        if (is_string($source)) {
            // Make sure the files exists
            if (!is_readable($source)) {
                throw new \InvalidArgumentException(
                    "The source for this upload must be either a readable file or a valid stream."
                );
            }
            $body = new LazyOpenStream($source, 'r');
            // To make sure the resource is closed.
            $this->deferFns[] = function () use ($body) {
                $body->close();
            };
        } elseif ($source instanceof StreamInterface) {
            $body = $source;
        } else {
            throw new \InvalidArgumentException(
                "The source must be a string or a StreamInterface."
            );
        }

        return $body;
    }

    protected function callDeferredFns(): void
    {
        foreach ($this->deferFns as $fn) {
            $fn();
        }
        $this->deferFns = [];
    }



    private function decorateWithHashes(Stream $stream, array &$data): StreamInterface
    {
        // Decorate source with a hashing stream
        $hash = new PhpHash('sha256');
        return new HashingStream($stream, $hash, function ($result) use (&$data) {
            $data['ContentSHA256'] = bin2hex($result);
        });
    }
}