<?php
namespace Aws\S3\S3Transfer\Models;

use Aws\S3\S3Transfer\Progress\TransferListener;
use InvalidArgumentException;

final class CopyRequest extends TransferRequest
{
    public static array $configKeys = [
        'multipart_copy_threshold_bytes',
        'target_part_size_bytes',
        'track_progress',
        'concurrency',
    ];

    /** @var array */
    private array $source;

    /** @var array */
    private array $copyRequestArgs;

    /**
     * @param array $source
     * @param array $copyRequestArgs
     * @param array $config
     * @param TransferListener[] $listeners
     * @param TransferListener|null $progressTracker
     */
    public function __construct(
        array $source,
        array $copyRequestArgs,
        array $config = [],
        array $listeners = [],
        ?TransferListener $progressTracker = null
    ) {
        parent::__construct($listeners, $progressTracker, $config);
        $this->source = $source;
        $this->copyRequestArgs = $copyRequestArgs;
    }

    /**
     * @param array $source
     * @param array $copyRequestArgs
     * @param array $config
     * @param TransferListener[] $listeners
     * @param TransferListener|null $progressTracker
     * @return static
     */
    public static function fromLegacyArgs(
        array $source,
        array $copyRequestArgs,
        array $config = [],
        array $listeners = [],
        ?TransferListener $progressTracker = null
    ): self
    {
        return new self(
            $source,
            $copyRequestArgs,
            $config,
            $listeners,
            $progressTracker
        );
    }

    /**
     * @return array
     */
    public function getSource(): array
    {
        return $this->source;
    }

    /**
     * @return array
     */
    public function getCopyRequestArgs(): array
    {
        return $this->copyRequestArgs;
    }

    /**
     * Helper method for validating the given source.
     *
     * @throws InvalidArgumentException When either bucket/key or both are not provided
     *                                 in the source/destination or are the same object.
     */
    public function validateSourceAndDest(): void
    {
        $sourceBucket = $this->source['Bucket'] ?? null;
        $sourceKey = $this->source['Key'] ?? null;
        $destBucket = $this->copyRequestArgs['Bucket'] ?? null;
        $destKey = $this->copyRequestArgs['Key'] ?? null;

        if (empty($sourceBucket) || empty($sourceKey)) {
            throw new InvalidArgumentException(
                "Both `Bucket` and `Key` must be provided in the source array."
            );
        }

        if (empty($destBucket) || empty($destKey)) {
            throw new InvalidArgumentException(
                "Both `Bucket` and `Key` must be provided in the copy request arguments."
            );
        }

        if ($sourceBucket === $destBucket && $sourceKey === $destKey) {
            throw new InvalidArgumentException(
                "Source and destination cannot be the same object."
            );
        }
    }
}
