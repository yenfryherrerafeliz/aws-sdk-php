<?php

namespace Aws\S3\S3Transfer\Models;

class CopyResponse
{
    private array $copyResponse;

    /**
     * @param array $copyResponse
     */
    public function __construct(array $copyResponse)
    {
        $this->copyResponse = $copyResponse;
    }

    public function getCopyResponse(): array
    {
        return $this->copyResponse;
    }
}