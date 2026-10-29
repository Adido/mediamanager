<?php
/**
 * Generate a thumbnail
 *
 * @var modX $this->modx
 * @var array $scriptProperties
 *
 * @package modx
 * @subpackage processors.system
 */
class MediaManagerPhpThumbProcessor extends modProcessor
{
    /** @var modPhpThumb $phpThumb */
    public $phpThumb;
    /** @var modMediaSource|modFileMediaSource $source */
    public $source;

    public function initialize()
    {
        $this->setDefaultProperties([
            'wctx'   => $this->modx->context->get('key'),
            'src'    => '',
            'source' => 1
        ]);

        $this->modx->getService('fileHandler', 'modFileHandler', '', ['source' => $this->getProperty('wctx')]);

        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @return mixed
     */
    public function process()
    {
        $src = $this->getProperty('src');

        if (is_numeric($src)) {
            $file = $this->modx->getObject('MediamanagerFiles', $src);

            if ($file) {
                $mediaManager = $this->modx->getService('mediamanager', 'MediaManager', $this->modx->getOption('mediamanager.core_path', null, $this->modx->getOption('core_path') . 'components/mediamanager/') . 'model/mediamanager/');
                $storage      = $mediaManager->sources->getStorage($file->get('media_sources_id'));

                if (!$storage) {
                    return $this->failure($this->modx->lexicon('source_err_nf'));
                }

                if ((int) $this->getProperty('return_url') === 1) {
                    echo $mediaManager->files->absoluteUrl($storage->url($file->get('path'))); exit;
                }

                /* Media Manager files are served through their media source's storage. */
                return $this->outputFile($mediaManager, $storage, $file);
            }
        }

        if (empty($src)) {
            return $this->failure();
        }

        $this->unsetProperty('src');

        $this->getSource($this->getProperty('source'));
        if (empty($this->source)) {
            $this->failure($this->modx->lexicon('source_err_nf'));
        }

        $src = $this->source->prepareSrcForThumb($src);
        if (empty($src)) {
            return '';
        }

        $this->loadPhpThumb();
        /* set source and generate thumbnail */
        $this->phpThumb->set($src);

        /* check to see if there's a cached file of this already */
        if ($this->phpThumb->checkForCachedFile()) {
            $this->phpThumb->loadCache();

            return '';
        }

        /* generate thumbnail */
        $this->phpThumb->generate();

        /* cache the thumbnail and output */
        $this->phpThumb->cache();
        $this->phpThumb->output();

        return '';
    }

    /**
     * Output an SVG, or the Media Manager thumbnail of an image.
     *
     * @param MediaManager $mediaManager
     * @param \Sterc\MediaManager\Storage\Storage $storage
     * @param MediamanagerFiles $file
     * @return string
     */
    protected function outputFile($mediaManager, $storage, $file)
    {
        if ($file->get('file_type') === 'svg') {
            $stream = $storage->readStream($file->get('path'));

            if ($stream === false) {
                return $this->failure();
            }

            header('Content-type: image/svg+xml');

            $svgFile = new DOMDocument();
            $svgFile->loadXML(stream_get_contents($stream));
            fclose($stream);

            echo $svgFile->saveHTML($svgFile->getElementsByTagName('svg')[0]);
            exit;
        }

        $thumbPath = $mediaManager->files->ensureThumbnail($mediaManager->files->fileToArray($file));

        if ($thumbPath === false) {
            return $this->failure();
        }

        header('Content-Type: ' . mime_content_type($thumbPath));
        header('Content-Length: ' . filesize($thumbPath));
        readfile($thumbPath);
        exit;
    }

    /**
     * Get the source to load the paths from
     *
     * @param int $sourceId
     * @return modMediaSource|modFileMediaSource
     */
    public function getSource($sourceId)
    {
        /** @var modMediaSource|modFileMediaSource $source */
        $this->modx->loadClass('sources.modMediaSource');

        $this->source = modMediaSource::getDefaultSource($this->modx, $sourceId, false);
        if (empty($this->source)) {
            return false;
        }

        if (!$this->source->getWorkingContext()) {
            return false;
        }

        $this->source->setRequestProperties($this->getProperties());
        $this->source->initialize();
        return $this->source;
    }

    /**
     * Attempt to load modPhpThumb
     *
     * @return bool|modPhpThumb
     */
    public function loadPhpThumb()
    {
        if (!$this->modx->loadClass('modPhpThumb', $this->modx->getOption('core_path').'model/phpthumb/', true, true)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not load modPhpThumb class.');
            return false;
        }
        $this->phpThumb = new modPhpThumb($this->modx, $this->getProperties());
        /* do initial setup */
        $this->phpThumb->initialize();

        return $this->phpThumb;
    }
}

return 'MediaManagerPhpThumbProcessor';
