<?php

declare(strict_types=1);

namespace Sterc\MediaManager\Vendor\League\MimeTypeDetection;

interface ExtensionToMimeTypeMap
{
    public function lookupMimeType(string $extension): ?string;
}
