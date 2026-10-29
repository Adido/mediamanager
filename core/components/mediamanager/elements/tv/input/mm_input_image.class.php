<?php
if (!class_exists('MediaManagerInputRender')) {
    class MediaManagerInputRender extends modTemplateVarInputRender
    {
        public function getTemplate()
        {
            return $this->modx->getOption(
                    'mediamanager.core_path',
                    null,
                    $this->modx->getOption('core_path') . 'components/mediamanager/'
                ) . 'elements/tv/input/tpl/mm_input_image.tpl';
        }

        public function process($value, array $params = [])
        {
            if (!is_numeric($value)) {
                return $value;
            }

            $path    = '';
            $url     = '';
            $preview = '';
            $file    = $this->modx->getObject('MediamanagerFiles', $value);
            if ($file) {
                $mediaManager = $this->modx->getService('mediamanager', 'MediaManager', $this->modx->getOption('mediamanager.core_path', null, $this->modx->getOption('core_path') . 'components/mediamanager/') . 'model/mediamanager/');
                $storage      = $mediaManager->sources->getStorage($file->get('media_sources_id'));

                if ($storage) {
                    $url  = $storage->url($file->get('path'));
                    $path = $url;

                    /* Files that aren't on remote storage keep the path placeholder they have always had: the source's base path and the file path. */
                    if (!$storage->isRemote() && ($mediaSource = $this->modx->getObject('sources.modMediaSource', $file->get('media_sources_id')))) {
                        $properties = $mediaSource->getProperties();
                        $path       = (isset($properties['basePath']['value']) ? $properties['basePath']['value'] : '') . $file->get('path');
                    }

                    if ($file->get('file_type') === 'svg') {
                        $preview = $url;
                    } else {
                        $preview = $mediaManager->files->getThumbnail($mediaManager->files->fileToArray($file), 'url');
                    }
                }
            }

            $this->setPlaceholder('path', $path);
            $this->setPlaceholder('url', $url);
            $this->setPlaceholder('preview', $preview);

            return $value;
        }
    }
}
return 'MediaManagerInputRender';