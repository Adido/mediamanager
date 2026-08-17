<?php
/**
 * Auto-assign policies to appropriate User Groups
 *
 * @package mediamanager
 * @subpackage build
 */

use xPDO\Transport\xPDOTransport;

return new class() {
    private $modx;
    private $action;

    public function __invoke(&$modx, $action, $options = [], $object = null)
    {
        $this->modx =& $modx;
        $this->action = $action;

        switch ($this->action) {
            case xPDOTransport::ACTION_INSTALL:
                break;
            case xPDOTransport::ACTION_UPGRADE:
                break;
        }

        return true;
    }
};
