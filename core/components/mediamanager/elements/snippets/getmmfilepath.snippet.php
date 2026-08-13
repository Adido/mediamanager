<?php
use Sterc\MediaManager\MediaManager;
use Sterc\MediaManager\Model\MediamanagerFiles;
use MODX\Revolution\Sources\modFileMediaSource;

$mediaManager = $modx->services->get('mediamanager');
if (!($mediaManager instanceof MediaManager)) {
    return false;
}

if (!is_numeric($input)) {
    return $input;
}

$path = '';
if ($file = $modx->getObject(MediamanagerFiles::class, $input)) {
    $mediaSourceId  = $file->get('media_sources_id');
    $mediaSource    = $modx->getObject(modFileMediaSource::class, ['id' => $mediaSourceId]);
    $basePath       = $mediaSource->getProperties()['basePath']['value'];
    $isRelative     = $mediaSource->getProperties()['baseUrlRelative']['value'];

    if ($isRelative) {
        $path = MODX_BASE_PATH . $basePath . $file->get('path');
    } else {
        $path = $basePath . $file->get('path');
    }
}

return $path;