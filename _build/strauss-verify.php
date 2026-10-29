<?php
/**
 * Verify the downloaded Strauss phar before it runs.
 *
 * The hash is the SHA-256 digest GitHub records for the strauss.phar asset of
 * the release in composer.json's strauss-download script. Update both together.
 * A phar that doesn't match is deleted, so the next run downloads it again.
 *
 * Run from core/components/mediamanager.
 */

$expected = '17dc2baf27b9a3f52834e3db729c8365d8915a80e37a087d253cf705387aa887';
$phar     = getcwd() . '/../../../_build/bin/strauss.phar';

if (!is_file($phar)) {
    fwrite(STDERR, "strauss-verify: {$phar} not found\n");
    exit(1);
}

$actual = hash_file('sha256', $phar);

if (!hash_equals($expected, $actual)) {
    unlink($phar);
    fwrite(STDERR, "strauss-verify: strauss.phar has SHA-256 {$actual}, expected {$expected}. The file has been deleted.\n");
    exit(1);
}
