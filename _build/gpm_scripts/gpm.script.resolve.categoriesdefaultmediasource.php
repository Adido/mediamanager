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

use MODX\Revolution\modX;
use Sterc\MediaManager\Model\MediamanagerCategories;
use xPDO\Transport\xPDOTransport;

return (function () {
    /**
 * If package is being updated, sets the category media source ids to the default media source ID if not set already.
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

        if ($this->action !== xPDOTransport::ACTION_UPGRADE) {
            return true;
        }

        $modx->log(modX::LOG_LEVEL_INFO, '- Resolving default media source ID\'s for categories.');

        $defaultMediaSource = $modx->getOption('default_media_source', null, 0);
        $categories = $modx->getIterator(MediamanagerCategories::class);
        if ($categories) {
            foreach ($categories as $category) {
                if ($category->get('media_sources_id') > 0) {
                    continue;
                }

                $category->set('media_sources_id', $defaultMediaSource);

                if ($category->save()) {
                    $modx->log(
                        modX::LOG_LEVEL_INFO,
                        '-- Setting Media Source ID for category: ' . $category->get('name')
                    );
                }
            }
        }

        return true;
    }
};
})()($transport->xpdo, $options[xPDOTransport::PACKAGE_ACTION], $options, $object);
