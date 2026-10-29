<?php

namespace Sterc\MediaManager\Vendor\Aws\S3\S3Transfer\Progress;

interface ProgressBarFactoryInterface
{
    public function __invoke(): ProgressBarInterface;
}
