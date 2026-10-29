<?php

declare(strict_types=1);

namespace Sterc\MediaManager\Vendor\League\Flysystem\UrlGeneration;

use Sterc\MediaManager\Vendor\League\Flysystem\Config;
use Sterc\MediaManager\Vendor\League\Flysystem\UnableToGeneratePublicUrl;

interface PublicUrlGenerator
{
    /**
     * @throws UnableToGeneratePublicUrl
     */
    public function publicUrl(string $path, Config $config): string;
}
