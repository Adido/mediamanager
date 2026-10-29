<?php
if (!class_exists('MediaManagerOutputRender')) {
    class MediaManagerOutputRender extends modTemplateVarOutputRender
    {
        public function process($value, array $params = array())
        {
            if (!is_numeric($value)) {
                return $value;
            }

            $path = '';
            if ($image = $this->modx->getObject('MediamanagerFiles', $value)) {
                if ($source = $this->modx->getObject('sources.modMediaSource', $image->get('media_sources_id'))) {
                    $mediaManager = $this->modx->getService('mediamanager', 'MediaManager', $this->modx->getOption('mediamanager.core_path', null, $this->modx->getOption('core_path') . 'components/mediamanager/') . 'model/mediamanager/');
                    $storage      = $mediaManager->sources->getStorage($source->get('id'));

                    /* Files on remote storage, such as S3, use the storage URL. */
                    if ($storage && $storage->isRemote()) {
                        return $storage->url($image->get('path'));
                    }

                    $source->initialize();

                    $path = $source->getBaseUrl();
                }

                $path .= $image->get('path');
            }

            return $path;
        }
    }
}

return 'MediaManagerOutputRender';