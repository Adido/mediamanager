<?php
namespace Sterc\MediaManager\Vendor\Aws\Arn\S3;

use Sterc\MediaManager\Vendor\Aws\Arn\ArnInterface;

/**
 * @internal
 */
interface BucketArnInterface extends ArnInterface
{
    public function getBucketName();
}
