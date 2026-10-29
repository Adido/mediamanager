<?php

declare(strict_types=1);

namespace Sterc\MediaManager\Vendor\League\Flysystem;

class UnableToCheckFileExistence extends UnableToCheckExistence
{
    public function operation(): string
    {
        return FilesystemOperationFailed::OPERATION_FILE_EXISTS;
    }
}
