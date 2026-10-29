<?php

namespace Sterc\MediaManager\Storage;

use modX;
use Sterc\MediaManager\Vendor\League\Flysystem\Filesystem;
use Sterc\MediaManager\Vendor\League\Flysystem\FilesystemException;
use Sterc\MediaManager\Vendor\League\Flysystem\Local\LocalFilesystemAdapter;
use Sterc\MediaManager\Vendor\League\Flysystem\Visibility;

/**
 * Storage for sources.modFileMediaSource.
 *
 * Files are on the local file system and stay public, as they always have.
 * Caches are kept inside the source, where they have always been, so existing
 * thumbnails and share links keep working.
 */
class LocalStorage extends Storage
{
    /**
     * Absolute path of the storage root, with a trailing slash.
     *
     * @var string
     */
    protected $localRoot;

    /**
     * @param modX $modx
     * @param Filesystem $filesystem
     * @param string $baseUrl
     * @param string $cachePath
     * @param string $cacheUrl
     * @param string $localRoot
     */
    public function __construct($modx, $filesystem, $baseUrl, $cachePath, $cacheUrl, $localRoot)
    {
        parent::__construct($modx, $filesystem, $baseUrl, $cachePath, $cacheUrl);

        $this->localRoot = $localRoot;
    }

    /**
     * {@inheritDoc}
     */
    public static function fromProperties($modx, $sourceId, $properties)
    {
        $basePath = isset($properties['basePath']) ? (string) $properties['basePath'] : '';
        if (!isset($properties['basePathRelative']) || $properties['basePathRelative'] !== false) {
            $basePath = rtrim(MODX_BASE_PATH, '/') . '/' . trim($basePath, '/') . '/';
        } else {
            $basePath = rtrim($basePath, '/') . '/';
        }

        /* Matches the file and upload URLs Media Manager has always used. */
        $baseUrl     = isset($properties['baseUrl']) ? (string) $properties['baseUrl'] : '';
        $relativeUrl = !isset($properties['baseUrlRelative']) || $properties['baseUrlRelative'] !== false;

        $fileUrl  = $relativeUrl ? '/' . trim($baseUrl, '/') . '/' : $baseUrl;
        $cacheUrl = $relativeUrl ? rtrim($modx->getOption('site_url'), '/') . '/' . trim($baseUrl, '/') . '/' : $baseUrl;

        $filesystem = new Filesystem(new LocalFilesystemAdapter($basePath));

        return new static($modx, $filesystem, $fileUrl, $basePath, $cacheUrl, $basePath);
    }

    /**
     * Local files are public, so their public URL is used.
     *
     * {@inheritDoc}
     */
    public function privateUrl($key, $lifetime = 3600)
    {
        return $this->url($key);
    }

    /**
     * {@inheritDoc}
     */
    public function localPath($key)
    {
        return $this->localRoot . ltrim($key, '/');
    }

    /**
     * The stored file itself is used, so nothing is copied.
     *
     * {@inheritDoc}
     */
    public function localCopy($key)
    {
        $path = $this->localPath($key);

        return is_file($path) ? $path : false;
    }

    /**
     * Nothing to release, as localCopy() returns the stored file itself.
     *
     * {@inheritDoc}
     */
    public function releaseLocalCopy($path)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function createDirectory($key)
    {
        try {
            $this->filesystem->createDirectory(ltrim($key, '/'));
        } catch (FilesystemException $exception) {
            $this->logError('Could not create directory ' . $key, $exception);

            return false;
        }

        return true;
    }

    /**
     * Local files are always public, as the manager links to them directly.
     *
     * {@inheritDoc}
     */
    protected function visibility($private)
    {
        return Visibility::PUBLIC;
    }
}
