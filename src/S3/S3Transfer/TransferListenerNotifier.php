<?php

namespace Aws\S3\S3Transfer;

use function Aws\is_associative;

class TransferListenerNotifier extends TransferListener
{
    /** @var TransferListener[] */
    private array $listeners;

    /**
     * @param array $listeners
     */
    public function __construct(array $listeners = [])
    {
        foreach ($listeners as $listener) {
            if (!$listener instanceof TransferListener) {
                throw new \InvalidArgumentException(
                    "Listener must implement " . TransferListener::class . "."
                );
            }
        }
        $this->listeners = $listeners;
    }

    /**
     * @inheritDoc
     *
     * @return void
     */
    public function transferInitiated(array $context): void
    {
        foreach ($this->listeners as $name => $listener) {
            $listener->transferInitiated($context);
        }
    }

    /**
     * @inheritDoc
     *
     * @return void
     */
    public function bytesTransferred(array $context): void
    {
        foreach ($this->listeners as $name => $listener) {
            $listener->bytesTransferred($context);
        }
    }

    /**
     * @inheritDoc
     *
     * @return void
     */
    public function transferComplete(array $context): void
    {
        foreach ($this->listeners as $name => $listener) {
            $listener->transferComplete($context);
        }
    }

    /**
     * @inheritDoc
     *
     * @return void
     */
    public function transferFail(array $context): void
    {
        foreach ($this->listeners as $name => $listener) {
            $listener->transferFail($context);
        }
    }
}