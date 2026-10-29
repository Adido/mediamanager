<?php

namespace Sterc\MediaManager\Storage;

use DateTimeImmutable;
use modX;
use Sterc\MediaManager\Vendor\Aws\CommandInterface;
use Sterc\MediaManager\Vendor\Aws\Middleware;
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
 * several S3 sources can share one cache path. The source properties used are
 * documented in the README.
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
            'region'  => static::parseRegion($properties),
            'version' => !empty($properties['version']) ? (string) $properties['version'] : '2006-03-01'
        ];

        /* Without a key, the SDK's default credentials are used, such as environment variables or an IAM role. */
        if (!empty($properties['key'])) {
            $config['credentials'] = [
                'key'    => (string) $properties['key'],
                'secret' => isset($properties['secret_key']) ? (string) $properties['secret_key'] : ''
            ];
        }

        if (!empty($properties['endpoint'])) {
            $config['endpoint'] = (string) $properties['endpoint'];
        }

        $client = new S3Client($config);

        /* Buckets with ACLs disabled reject requests that set one, so leave them out when the source turns ACLs off. */
        if (!static::parseAclEnabled($properties)) {
            $client->getHandlerList()->appendInit(Middleware::mapCommand(function (CommandInterface $command) {
                unset($command['ACL']);

                return $command;
            }), 'mediamanager-no-acl');
        }

        $filesystem = new Filesystem(new AwsS3V3Adapter($client, $bucket, $prefix));

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
     * Whether to set ACLs on stored files, from the mediamanagerS3ObjectAclEnabled
     * property. ACLs are on when the property isn't set.
     *
     * @param array $properties Property values, keyed by name.
     * @return bool
     */
    protected static function parseAclEnabled($properties)
    {
        $value = isset($properties['mediamanagerS3ObjectAclEnabled']) ? $properties['mediamanagerS3ObjectAclEnabled'] : null;

        if ($value === null || $value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Region for the SDK, from MODX 2's region property.
     *
     * MODX 2's S3 source leaves the region empty for US East (N. Virginia),
     * and its SDK accepted "EU" for Ireland.
     *
     * @param array $properties Property values, keyed by name.
     * @return string
     */
    protected static function parseRegion($properties)
    {
        $region = isset($properties['region']) ? trim((string) $properties['region']) : '';

        if ($region === '') {
            return 'us-east-1';
        }

        // Backwards compatibility with MODX S3 sources
        if (strtoupper($region) === 'EU') {
            return 'eu-west-1';
        }

        return $region;
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
     * {@inheritDoc}
     */
    public function isRemote()
    {
        return true;
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

        $target = fopen($path, 'wb');

        if ($target === false) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[MediaManager] Could not open temporary file ' . $path . ' to download ' . $key);
            $this->releaseLocalCopy($path);

            return false;
        }

        try {
            $stream = $this->filesystem->readStream(ltrim($key, '/'));

            stream_copy_to_stream($stream, $target);
            fclose($stream);
        } catch (FilesystemException $exception) {
            $this->logError('Could not download ' . $key, $exception);
            fclose($target);
            $this->releaseLocalCopy($path);

            return false;
        }

        fclose($target);

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
