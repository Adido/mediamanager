<?php
/**
 * Thumbnail processor
 *
 * Creates a missing thumbnail in the local cache from the stored file, which
 * may be on S3, then outputs it.
 */
class MediaManagerThumbnailProcessor extends modProcessor
{

    private $mediaManager = null;

    public function checkPermissions()
    {
        return $this->modx->hasPermission('file_manager');
    }

    public function initialize()
    {
        $this->mediaManager = $this->modx->getService('mediamanager', 'MediaManager', $this->modx->getOption('mediamanager.core_path', null, $this->modx->getOption('core_path') . 'components/mediamanager/') . 'model/mediamanager/');

        return parent::initialize();
    }

    public function process()
    {
        $file = $this->modx->getObject('MediamanagerFiles', (int) $this->getProperty('id'));

        if (!$file || !($this->mediaManager instanceof MediaManager)) {
            return '';
        }

        $thumbPath = $this->mediaManager->files->ensureThumbnail($this->mediaManager->files->fileToArray($file));

        if ($thumbPath === false) {
            return '';
        }

        header('Content-Type: ' . mime_content_type($thumbPath));
        header('Content-Length: ' . filesize($thumbPath));
        readfile($thumbPath);
        exit;
    }
}

return 'MediaManagerThumbnailProcessor';
