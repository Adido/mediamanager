<?php
/**
 * Media Manager Connector
 *
 * @package mediamanager
 */
require_once dirname(__DIR__, 3) . '/config.core.php';
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CONNECTORS_PATH . 'index.php';

if (!$modx->services->has('mediamanager')) {
    return;
}

$mediamanager = $modx->services->get('mediamanager');

/* handle request */
$modx->request->handleRequest(array(
    'processors_path' => $mediamanager->config['processors_path'],
    'location' => ''
));