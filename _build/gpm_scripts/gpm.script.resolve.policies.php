<?php
/**
 *
 * THIS SCRIPT IS AUTOMATICALLY GENERATED, NO CHANGES WILL APPLY
 *
 * @package mediamanager
 * @subpackage build
 *
 * @var \xPDO\Transport\xPDOTransport $transport
 * @var array $object
 * @var array $options
 */

use xPDO\Transport\xPDOTransport;

return (function () {
    /**
 * Auto-assign policies to appropriate User Groups
 *
 * @package mediamanager
 * @subpackage build
 */

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
})()($transport->xpdo, $options[xPDOTransport::PACKAGE_ACTION], $options, $object);
