<?php

declare(strict_types=1);

namespace Sterc\MediaManager\Vendor\League\Flysystem;

interface PathNormalizer
{
    public function normalizePath(string $path): string;
}
