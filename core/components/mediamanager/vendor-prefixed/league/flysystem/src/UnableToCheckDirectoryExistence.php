<?php

declare(strict_types=1);

namespace Sterc\MediaManager\Vendor\League\Flysystem;

class UnableToCheckDirectoryExistence extends UnableToCheckExistence
{
    public function operation(): string
    {
        return FilesystemOperationFailed::OPERATION_DIRECTORY_EXISTS;
    }
}
