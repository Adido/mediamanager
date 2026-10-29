<?php

declare(strict_types=1);

namespace Sterc\MediaManager\Vendor\League\Flysystem;

use InvalidArgumentException as BaseInvalidArgumentException;

class InvalidStreamProvided extends BaseInvalidArgumentException implements FilesystemException
{
}
