<?php
/**
 * @var \MODX\Revolution\modX $modx
 * @var ContentBlocks $contentBlocks
 * @var array $scriptProperties
 */

use Sterc\MediaManager\ContentBlocks\Input\Input;
use Sterc\MediaManager\ContentBlocks\Input\ImageInput;
use Sterc\MediaManager\ContentBlocks\Input\GalleryInput;

if ($modx->event->name == 'ContentBlocks_RegisterInputs') {
    // Create an instance of your input type, passing the $contentBlocks var
    $cbMMInput          = new Input($contentBlocks);
    $cbMMImageInput     = new ImageInput($contentBlocks);
    $cbMMGalleryInput   = new GalleryInput($contentBlocks);

    // Pass back your input reference as key, and the instance as value
    $modx->event->output([
        'cb_mediamanager_input'         => $cbMMInput,
        'cb_mediamanager_image_input'   => $cbMMImageInput,
        'cb_mediamanager_gallery_input' => $cbMMGalleryInput
    ]);
}