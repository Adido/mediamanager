<?php
if (!class_exists('MediaManagerInputFileRender')) {
    class MediaManagerInputFileRender extends modTemplateVarInputRender
    {
        public function getTemplate()
        {
            return $this->modx->getOption('mediamanager.core_path', null, $this->modx->getOption('core_path') . 'components/mediamanager/') . 'elements/tv/input/tpl/mm_input_file.tpl';
        }

        public function process($value, array $params = array())
        {
            if (!is_numeric($value)) {
                return $value;
            }

            $path = '';
            $file = $this->modx->getObject('MediamanagerFiles', $value);
            if ($file) {
                $mediaManager = $this->modx->getService('mediamanager', 'MediaManager', $this->modx->getOption('mediamanager.core_path', null, $this->modx->getOption('core_path') . 'components/mediamanager/') . 'model/mediamanager/');
                $path         = $mediaManager->files->fileUrl($file->toArray());
            }

            $this->setPlaceholder('path', $path);

            return $value;
        }
    }
}

return 'MediaManagerInputFileRender';