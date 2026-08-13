<?php

namespace Sterc\MediaManager\Tv\Output;

use MODX\Revolution\modTemplateVarOutputRender;
use MODX\Revolution\Sources\modMediaSource;
use Sterc\MediaManager\Model\MediamanagerFiles;

class Image extends modTemplateVarOutputRender
{
    public function process($value, array $params = array())
    {
        if (!is_numeric($value)) {
            return $value;
        }

        $path = '';
        if ($image = $this->modx->getObject(MediamanagerFiles::class, $value)) {
            if ($source = $this->modx->getObject(modMediaSource::class, $image->get('media_sources_id'))) {
                $source->initialize();

                $path = $source->getBaseUrl();
            }

            $path .= $image->get('path');
        }

        return $path;
    }
}
