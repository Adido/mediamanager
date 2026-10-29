<?php

namespace Sterc\MediaManager\Storage;

use DateTimeImmutable;
use Sterc\MediaManager\Vendor\Aws\S3\S3Client;
use Sterc\MediaManager\Vendor\League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use Sterc\MediaManager\Vendor\League\Flysystem\Filesystem;
use Sterc\MediaManager\Vendor\League\Flysystem\FilesystemException;
use Sterc\MediaManager\Vendor\League\Flysystem\Visibility;

/**
 * Storage for sources.modS3MediaSource.
 *
 * Files are public unless stored privately, in which case they are served by
 * signed URLs. Caches go in a sources/{id}/ subdirectory of the cache path, so
 * several S3 sources can share one cache path. Properties are read with the
 * same names MODX 3's modS3MediaSource uses.
 */
class S3Storage extends Storage
{
    /**
     * Temporary files created by localCopy().
     *
     * @var array
     */
    protected $temporaryFiles = [];

    public function __destruct()
    {
        foreach (array_keys($this->temporaryFiles) as $path) {
            $this->releaseLocalCopy($path);
        }
    }

    /**
     * {@inheritDoc}
     */
    public static function fromProperties($modx, $sourceId, $properties)
    {
        $bucket = isset($properties['bucket']) ? (string) $properties['bucket'] : '';
        $prefix = isset($properties['prefix']) ? trim((string) $properties['prefix'], '/') : '';

        $config = [
            'credentials' => [
                'key'    => isset($properties['key']) ? (string) $properties['key'] : '',
                'secret' => isset($properties['secret_key']) ? (string) $properties['secret_key'] : ''
            ],
            'region'  => !empty($properties['region']) ? (string) $properties['region'] : 'us-east-2',
            'version' => !empty($properties['version']) ? (string) $properties['version'] : '2006-03-01'
        ];

        if (!empty($properties['endpoint'])) {
            $config['endpoint'] = (string) $properties['endpoint'];
        }

        $filesystem = new Filesystem(new AwsS3V3Adapter(new S3Client($config), $bucket, $prefix));

        $baseUrl = rtrim(isset($properties['url']) ? (string) $properties['url'] : '', '/') . '/';
        if ($prefix !== '') {
            $baseUrl .= $prefix . '/';
        }

        /* Caches for each source go in their own subdirectory, so one setting serves every source. */
        $cachePath = rtrim($modx->getOption('mediamanager.cache_path', null, $modx->getOption('assets_path') . 'mediamanager/', true), '/') . '/sources/' . $sourceId . '/';
        $cacheUrl  = rtrim($modx->getOption('mediamanager.cache_url', null, $modx->getOption('assets_url') . 'mediamanager/', true), '/') . '/sources/' . $sourceId . '/';

        return new static($modx, $filesystem, $baseUrl, $cachePath, $cacheUrl);
    }

    /**
     * A signed URL that expires.
     *
     * {@inheritDoc}
     */
    public function privateUrl($key, $lifetime = 3600)
    {
        try {
            return $this->filesystem->temporaryUrl(ltrim($key, '/'), new DateTimeImmutable('+' . (int) $lifetime . ' seconds'));
        } catch (FilesystemException $exception) {
            $this->logError('Could not create a temporary URL for ' . $key, $exception);
        }

        return '';
    }

    /**
     * S3 files have no local path.
     *
     * {@inheritDoc}
     */
    public function localPath($key)
    {
        return null;
    }

    /**
     * Downloads the file to a temporary file.
     *
     * {@inheritDoc}
     */
    public function localCopy($key)
    {
        $extension = pathinfo($key, PATHINFO_EXTENSION);
        $path      = tempnam(sys_get_temp_dir(), 'mediamanager-');

        if ($path === false) {
            return false;
        }

        /* Keep the extension, as some image tools rely on it. */
        if (!empty($extension) && rename($path, $path . '.' . $extension)) {
            $path .= '.' . $extension;
        }

        $this->temporaryFiles[$path] = true;

        try {
            $stream = $this->filesystem->readStream(ltrim($key, '/'));
            $target = fopen($path, 'wb');

            stream_copy_to_stream($stream, $target);

            fclose($target);
            fclose($stream);
        } catch (FilesystemException $exception) {
            $this->logError('Could not download ' . $key, $exception);
            $this->releaseLocalCopy($path);

            return false;
        }

        return $path;
    }

    /**
     * Removes the temporary file.
     *
     * {@inheritDoc}
     */
    public function releaseLocalCopy($path)
    {
        if ($path && isset($this->temporaryFiles[$path])) {
            if (is_file($path)) {
                unlink($path);
            }

            unset($this->temporaryFiles[$path]);
        }
    }

    /**
     * S3 has no real directories, so there is nothing to create.
     *
     * {@inheritDoc}
     */
    public function createDirectory($key)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function visibility($private)
    {
        return $private ? Visibility::PRIVATE : Visibility::PUBLIC;
    }
}
