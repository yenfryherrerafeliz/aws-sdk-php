<?php

namespace Aws\S3\S3Transfer\Models;

use Aws\Result;

class CopyResult extends Result
{
    /**
     * @param array $copyResult
     */
    public function __construct(array $copyResult)
    {
        parent::__construct($copyResult);
    }
}
