<?php

namespace Sterc\MediaManager\Processors\Mgr;

use MODX\Revolution\Processors\Processor;

class Tags extends Processor
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
            case 'create':
                $data = $this->create();

                break;
            case 'edit':
                $data = $this->edit();

                break;
            case 'delete':
                $data = $this->delete();

                break;
            case 'getTagsByName':
                $data = $this->getTagsByName();

                break;
        }

        return $this->outputArray($data);
    }

    private function create()
    {
        return $this->mediaManager->tags->createTag($this->getProperty('tag'));
    }

    private function edit()
    {

        return $this->mediaManager->tags->editTag($this->getProperty('tag_id'), $this->getProperty('tag'));
    }

    private function delete()
    {
        return $this->mediaManager->tags->deleteTag($this->getProperty('tag_id'));
    }

    private function getTagsByName()
    {
        return $this->mediaManager->tags->getTagsByName(
            $this->getProperty('search'),
            (bool) $this->getProperty('isSourceTag')
        );
    }
}
