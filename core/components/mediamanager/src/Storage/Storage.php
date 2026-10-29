<?php

namespace Sterc\MediaManager\Storage;

use modX;
use Sterc\MediaManager\Vendor\League\Flysystem\Filesystem;
use Sterc\MediaManager\Vendor\League\Flysystem\FilesystemException;

/**
 * Storage for one media source.
 *
 * Original files (uploads, archive, versions, licences) are stored through
 * Flysystem. Caches (thumbnails, PDF previews, download zips) are always kept
 * on local disk. Each type of media source has its own subclass, which holds
 * everything that differs between them.
 */
abstract class Storage
{
    /**
     * @var modX
     */
    protected $modx;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Public URL of the storage root, with a trailing slash.
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Absolute path and URL of the local cache root, with trailing slashes.
     *
     * @var string
     */
    protected $cachePath;
    protected $cacheUrl;

    /**
     * @param modX $modx
     * @param Filesystem $filesystem
     * @param string $baseUrl
     * @param string $cachePath
     * @param string $cacheUrl
     */
    public function __construct($modx, $filesystem, $baseUrl, $cachePath, $cacheUrl)
    {
        $this->modx       = $modx;
        $this->filesystem = $filesystem;
        $this->baseUrl    = $baseUrl;
        $this->cachePath  = $cachePath;
        $this->cacheUrl   = $cacheUrl;
    }

    /**
     * Create the storage from a media source's properties.
     *
     * @param modX $modx
     * @param int $sourceId
     * @param array $properties Property values, keyed by name.
     * @return static
     */
    abstract public static function fromProperties($modx, $sourceId, $properties);

    /**
     * URL for a privately stored file.
     *
     * @param string $key
     * @param int $lifetime Seconds the URL stays valid, where it expires.
     * @return string
     */
    abstract public function privateUrl($key, $lifetime = 3600);

    /**
     * Absolute path of a stored file, or null when the storage has no local
     * path for it.
     *
     * @param string $key
     * @return string|null
     */
    abstract public function localPath($key);

    /**
     * Whether files are stored remotely, such as on S3, so reading one means
     * a request to another service, and there is no local path for it.
     *
     * @return bool
     */
    abstract public function isRemote();

    /**
     * Absolute path of a local file holding the stored file's contents.
     * Release it with releaseLocalCopy() when done.
     *
     * @param string $key
     * @return string|false
     */
    abstract public function localCopy($key);

    /**
     * Release a file returned by localCopy().
     *
     * @param string|false $path
     */
    abstract public function releaseLocalCopy($path);

    /**
     * Create a directory.
     *
     * @param string $key
     * @return bool
     */
    abstract public function createDirectory($key);

    /**
     * Flysystem visibility for a stored file.
     *
     * @param bool $private Whether the file should be stored privately.
     * @return string
     */
    abstract protected function visibility($private);

    /**
     * @return Filesystem
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * Public URL of a stored file.
     *
     * @param string $key
     * @return string
     */
    public function url($key)
    {
        return $this->baseUrl . ltrim($key, '/');
    }

    /**
     * Absolute path in the local cache.
     *
     * @param string $key
     * @return string
     */
    public function cachePath($key = '')
    {
        return $this->cachePath . ltrim($key, '/');
    }

    /**
     * URL in the local cache.
     *
     * @param string $key
     * @return string
     */
    public function cacheUrl($key = '')
    {
        return $this->cacheUrl . ltrim($key, '/');
    }

    /**
     * @param string $key
     * @return bool
     */
    public function fileExists($key)
    {
        try {
            return $this->filesystem->fileExists(ltrim($key, '/'));
        } catch (FilesystemException $exception) {
            $this->logError('Could not check ' . $key, $exception);
        }

        return false;
    }

    /**
     * Store a local file.
     *
     * @param string $key
     * @param string $localFile
     * @param bool $private
     * @return bool
     */
    public function writeFile($key, $localFile, $private = false)
    {
        $stream = fopen($localFile, 'rb');

        if ($stream === false) {
            return false;
        }

        try {
            $this->filesystem->writeStream(ltrim($key, '/'), $stream, ['visibility' => $this->visibility($private)]);
        } catch (FilesystemException $exception) {
            $this->logError('Could not write ' . $key, $exception);

            return false;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return true;
    }

    /**
     * Store a string.
     *
     * @param string $key
     * @param string $contents
     * @param bool $private
     * @return bool
     */
    public function write($key, $contents, $private = false)
    {
        try {
            $this->filesystem->write(ltrim($key, '/'), $contents, ['visibility' => $this->visibility($private)]);
        } catch (FilesystemException $exception) {
            $this->logError('Could not write ' . $key, $exception);

            return false;
        }

        return true;
    }

    /**
     * @param string $key
     * @return resource|false
     */
    public function readStream($key)
    {
        try {
            return $this->filesystem->readStream(ltrim($key, '/'));
        } catch (FilesystemException $exception) {
            $this->logError('Could not read ' . $key, $exception);
        }

        return false;
    }

    /**
     * Copy a stored file, setting the visibility of the copy.
     *
     * @param string $from
     * @param string $to
     * @param bool $private
     * @return bool
     */
    public function copy($from, $to, $private = false)
    {
        try {
            $this->filesystem->copy(ltrim($from, '/'), ltrim($to, '/'), ['visibility' => $this->visibility($private)]);
        } catch (FilesystemException $exception) {
            $this->logError('Could not copy ' . $from . ' to ' . $to, $exception);

            return false;
        }

        return true;
    }

    /**
     * Move a stored file, setting the visibility of the moved file.
     *
     * @param string $from
     * @param string $to
     * @param bool $private
     * @return bool
     */
    public function move($from, $to, $private = false)
    {
        try {
            $this->filesystem->move(ltrim($from, '/'), ltrim($to, '/'), ['visibility' => $this->visibility($private)]);
        } catch (FilesystemException $exception) {
            $this->logError('Could not move ' . $from . ' to ' . $to, $exception);

            return false;
        }

        return true;
    }

    /**
     * Delete a stored file. A file that doesn't exist counts as deleted.
     *
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        try {
            $this->filesystem->delete(ltrim($key, '/'));
        } catch (FilesystemException $exception) {
            $this->logError('Could not delete ' . $key, $exception);

            return false;
        }

        return true;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function deleteDirectory($key)
    {
        try {
            $this->filesystem->deleteDirectory(ltrim($key, '/'));
        } catch (FilesystemException $exception) {
            $this->logError('Could not delete directory ' . $key, $exception);

            return false;
        }

        return true;
    }

    /**
     * @param string $message
     * @param FilesystemException $exception
     */
    protected function logError($message, $exception)
    {
        $previous = $exception->getPrevious();

        $this->modx->log(modX::LOG_LEVEL_ERROR, '[MediaManager] ' . $message . ': ' . $exception->getMessage() . ($previous ? ' (' . $previous->getMessage() . ')' : ''));
    }
}
