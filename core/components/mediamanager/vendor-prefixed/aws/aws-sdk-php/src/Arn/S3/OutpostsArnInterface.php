<?php
namespace Sterc\MediaManager\Vendor\Aws\Arn\S3;

use Sterc\MediaManager\Vendor\Aws\Arn\ArnInterface;

/**
 * @internal
 */
interface OutpostsArnInterface extends ArnInterface
{
    public function getOutpostId();
}
