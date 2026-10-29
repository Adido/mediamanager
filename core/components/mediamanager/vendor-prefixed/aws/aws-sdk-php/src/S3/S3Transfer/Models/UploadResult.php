<?php

namespace Sterc\MediaManager\Vendor\Aws\S3\S3Transfer\Models;

use Sterc\MediaManager\Vendor\Aws\Result;

final class UploadResult extends Result
{
    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        parent::__construct($data);
    }
}
