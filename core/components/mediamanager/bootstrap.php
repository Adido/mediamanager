<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/include/deprecated.php';

$modx->addPackage('Sterc\MediaManager\Model', $namespace['path'] . 'src/', null, 'Sterc\\MediaManager\\');

$modx->services->add('mediamanager', function () use ($modx) {
    return new \Sterc\MediaManager\MediaManager($modx);
});
