<?php
declare(strict_types=1);

namespace Sterc\MediaManager\Vendor\League\Flysystem\UrlGeneration;

use DateTimeInterface;
use Sterc\MediaManager\Vendor\League\Flysystem\Config;
use Sterc\MediaManager\Vendor\League\Flysystem\UnableToGenerateTemporaryUrl;

interface TemporaryUrlGenerator
{
    /**
     * @throws UnableToGenerateTemporaryUrl
     */
    public function temporaryUrl(string $path, DateTimeInterface $expiresAt, Config $config): string;
}
