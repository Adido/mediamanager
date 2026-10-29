<?php

class cbMediaManagerImageInput extends cbBaseInput
{
    public $defaultIcon = 'image';
    public $defaultTpl = '<img src="[[+url]]" width="[[+width]]" height="[[+height]]" alt="[[+title:htmlent]]">';

    public function getName()
    {
        return 'Media Manager - Image';
    }

    public function getDescription()
    {
        return 'Image input for the new Media Manager.';
    }

    /**
     * @return array
     */
    public function getJavaScripts()
    {
        $assetsUrl = $this->modx->getOption('mediamanager.assets_url', null, MODX_ASSETS_URL . 'components/mediamanager/');

        return [
            $assetsUrl . 'js/inputs/cb_mediamanager_image_input.js',
        ];
    }

    /*
     * @return array
     */
    public function getTemplates()
    {
        $tpls = array();

        // Grab the template from a .tpl file
        $corePath = $this->modx->getOption('mediamanager.core_path', null, MODX_CORE_PATH . 'components/mediamanager/');
        $template = file_get_contents($corePath . 'elements/templates/cb_mediamanager_image_input.tpl');

        // Wrap the template, giving the input a reference of "my_awesome_input", and
        // add it to the returned array.
        $tpls[] = $this->contentBlocks->wrapInputTpl('cb_mediamanager_image_input', $template);
        return $tpls;
    }

    public function process(cbField $field, array $data = array())
    {
        if (!isset($data['width']) || $data['width'] < 1) {
            $size = false;
            $file = null;

            // Use the stored dimensions, which also covers files on S3
            if (!empty($data['file_id']) && ($file = $this->modx->getObject('MediamanagerFiles', (int) $data['file_id']))) {
                $dimensions = explode('x', (string) $file->get('file_dimensions'));
                if (count($dimensions) === 2 && (int) $dimensions[0] > 0) {
                    $size = [(int) $dimensions[0], (int) $dimensions[1]];
                }
            }

            // Remote files, such as on S3, without stored dimensions get a fixed size, rather than a request each time
            if (!$size && $file) {
                $mediaManager = $this->modx->getService('mediamanager', 'MediaManager', $this->modx->getOption('mediamanager.core_path', null, $this->modx->getOption('core_path') . 'components/mediamanager/') . 'model/mediamanager/');
                $storage      = $mediaManager ? $mediaManager->sources->getStorage($file->get('media_sources_id')) : null;

                if ($storage && $storage->isRemote()) {
                    $size = [400, 400];
                }
            }

            if (!$size && file_exists($data['url']) && is_readable($data['url'])) {
                $size = getimagesize($data['url']);
            }

            if (!$size) {
                // Try it with a normalised path
                $normalisedPath = str_replace(MODX_BASE_URL.MODX_BASE_URL, MODX_BASE_URL, MODX_BASE_PATH . $data['url']);
                if (file_exists($normalisedPath) && is_readable($normalisedPath)) {
                    $size = getimagesize($normalisedPath);
                }
            }

            if (!empty($size)) {
                $data['width']  = $size[0];
                $data['height'] = $size[1];
            }
        }

        return parent::process($field, $data);
    }
}
