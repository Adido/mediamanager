<?php
/**
 * Image processor
 *
 * Outputs a stored image, which may be on S3, from the manager's own origin so
 * it can be read back from a canvas, such as by the cropper, without CORS.
 */
class MediaManagerImageProcessor extends modProcessor
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

        $file    = $this->mediaManager->files->fileToArray($file);
        $storage = $this->mediaManager->sources->getStorage($file['media_sources_id']);

        if (!$storage || !$this->mediaManager->files->isImage($file['file_type'])) {
            return '';
        }

        $stream = $storage->readStream($file['is_archived'] ? $file['archive_path'] : $file['file_path']);

        if ($stream === false) {
            return '';
        }

        header('Content-Type: image/' . ($file['file_type'] === 'jpg' ? 'jpeg' : $file['file_type']));
        header('Cache-Control: private, no-cache');
        fpassthru($stream);
        fclose($stream);
        exit;
    }
}

return 'MediaManagerImageProcessor';
