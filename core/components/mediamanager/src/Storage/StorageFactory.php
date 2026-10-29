<?php

namespace Sterc\MediaManager\Storage;

use Exception;
use modMediaSource;
use modX;

/**
 * Builds the Storage for a media source, using the Storage class for its type.
 */
class StorageFactory
{
    /**
     * Storage class for each supported media source class.
     *
     * @var array
     */
    protected $classes = [
        'sources.modFileMediaSource' => LocalStorage::class,
        'sources.modS3MediaSource'   => S3Storage::class
    ];

    /**
     * @var modX
     */
    protected $modx;

    /**
     * @param modX $modx
     */
    public function __construct($modx)
    {
        $this->modx = $modx;
    }

    /**
     * @param modMediaSource $source
     * @return Storage|null Null when the source type isn't supported.
     */
    public function create($source)
    {
        $classKey = $source->get('class_key');

        if (!isset($this->classes[$classKey])) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[MediaManager] Media source ' . $source->get('id') . ' uses unsupported class ' . $classKey);

            return null;
        }

        $properties = [];
        foreach ((array) $source->get('properties') as $key => $property) {
            $properties[$key] = isset($property['value']) ? $property['value'] : null;
        }

        try {
            return call_user_func([$this->classes[$classKey], 'fromProperties'], $this->modx, (int) $source->get('id'), $properties);
        } catch (Exception $exception) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[MediaManager] Could not create storage for media source ' . $source->get('id') . ': ' . $exception->getMessage());
        }

        return null;
    }
}
