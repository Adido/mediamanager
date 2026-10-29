<?php
/**
 * Prefix namespaced class names that Strauss cannot rewrite.
 *
 * Strauss prefixes literal class names, but not ones built by string
 * interpolation, such as "Aws\\{$service}\\Exception\\{$service}Exception"
 * in the AWS SDK. Left unprefixed, these resolve to a class that doesn't
 * exist, or to another extra's unprefixed copy of the SDK.
 *
 * Run from core/components/mediamanager after Strauss.
 */

$prefix    = 'Sterc\\\\MediaManager\\\\Vendor\\\\';
$directory = getcwd() . '/vendor-prefixed/aws/aws-sdk-php/src';
$pattern   = '/"Aws\\\\\\\\\{/';
$changed   = 0;

if (!is_dir($directory)) {
    fwrite(STDERR, "strauss-postprocess: {$directory} not found\n");
    exit(1);
}

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));

foreach ($files as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $contents = file_get_contents($file->getPathname());
    $updated  = preg_replace_callback($pattern, function () use ($prefix) {
        return '"' . $prefix . 'Aws\\\\{';
    }, $contents, -1, $count);

    if ($count > 0) {
        file_put_contents($file->getPathname(), $updated);
        $changed += $count;
    }
}

echo "strauss-postprocess: prefixed {$changed} interpolated AWS class names\n";
