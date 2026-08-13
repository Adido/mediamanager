<?php

namespace Sterc\MediaManager\Tv\Input;

use MODX\Revolution\modTemplateVarInputRender;
use MODX\Revolution\Sources\modFileMediaSource;
use Sterc\MediaManager\Model\MediamanagerFiles;

class File extends modTemplateVarInputRender
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
        $file = $this->modx->getObject(MediamanagerFiles::class, $value);
        if ($file) {
            $mediaSourceId  = $file->get('media_sources_id');
            $mediaSource    = $this->modx->getObject(modFileMediaSource::class, ['id' => $mediaSourceId]);
            $basePath       = $mediaSource->getProperties()['basePath']['value'];
            $path           = $basePath . $file->get('path');
        }

        $this->setPlaceholder('path', $path);

        return $value;
    }
}
