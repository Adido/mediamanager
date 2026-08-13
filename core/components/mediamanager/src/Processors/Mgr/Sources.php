<?php

namespace Sterc\MediaManager\Processors\Mgr;

use MODX\Revolution\Processors\Processor;

/**
 * Sources processor
 */
class Sources extends Processor
{
    private $mediaManager = null;

    public function checkPermissions()
    {
        return $this->modx->hasPermission('file_manager');
    }

    public function process()
    {
        $this->mediaManager = $this->modx->services->get('mediamanager');

        $method = $this->getProperty('method');
        $data   = array();

        switch ($method) {
            case 'list':
                $data = $this->getList();

                break;
        }

        return $this->outputArray($data);
    }

    private function getList()
    {
        return $this->mediaManager->sources->getListHtml();
    }
}
