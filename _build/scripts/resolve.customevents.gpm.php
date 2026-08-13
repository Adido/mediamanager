<?php
/**
 * Register / remove custom system events used by Media Manager.
 *
 * @package mediamanager
 * @subpackage build
 */

use MODX\Revolution\modEvent;
use xPDO\Transport\xPDOTransport;

return new class() {
    private $modx;
    private $action;

    public function __invoke(&$modx, $action, $options = [], $object = null)
    {
        $this->modx =& $modx;
        $this->action = $action;

        $events = [
            'MediaManagerFileArchived',
            'MediaManagerFileDeleted',
            'MediaManagerFileSaved',

            'MediaManagerFileVersionChanged',

            'MediaManagerFilesArchived',

            'MediaManagerVersionChanged',
        ];

        switch ($this->action) {
            case xPDOTransport::ACTION_INSTALL:
            case xPDOTransport::ACTION_UPGRADE:
                foreach ($events as $eventName) {
                    $event = $modx->getObject(modEvent::class, ['name' => $eventName]);
                    if (!$event) {
                        $event = $modx->newObject(modEvent::class);
                        $event->set('name', $eventName);
                        $event->set('service', 6);
                        $event->set('groupname', 'MediaManager');
                        $event->save();
                    }
                }

                break;
            case xPDOTransport::ACTION_UNINSTALL:
                foreach ($events as $eventName) {
                    $event = $modx->getObject(modEvent::class, ['name' => $eventName]);
                    if ($event) {
                        $event->remove();
                    }
                }

                break;
        }

        return true;
    }
};
