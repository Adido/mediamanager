<?php
use xPDO\Transport\xPDOTransport;

/**
 * Create tables
 *
 * THIS SCRIPT IS AUTOMATICALLY GENERATED, NO CHANGES WILL APPLY
 *
 * @package mediamanager
 * @subpackage build.scripts
 *
 * @var \xPDO\Transport\xPDOTransport $transport
 * @var array $object
 * @var array $options
 */

$modx =& $transport->xpdo;

if ($options[xPDOTransport::PACKAGE_ACTION] === xPDOTransport::ACTION_UNINSTALL) return true;

$manager = $modx->getManager();

$manager->createObjectContainer(\Sterc\MediaManager\Model\MediamanagerCategories::class);
$manager->createObjectContainer(\Sterc\MediaManager\Model\MediamanagerTags::class);
$manager->createObjectContainer(\Sterc\MediaManager\Model\MediamanagerFiles::class);
$manager->createObjectContainer(\Sterc\MediaManager\Model\MediamanagerDownloads::class);
$manager->createObjectContainer(\Sterc\MediaManager\Model\MediamanagerFilesCategories::class);
$manager->createObjectContainer(\Sterc\MediaManager\Model\MediamanagerFilesLicense::class);
$manager->createObjectContainer(\Sterc\MediaManager\Model\MediamanagerFilesLicenseFile::class);
$manager->createObjectContainer(\Sterc\MediaManager\Model\MediamanagerFilesTags::class);
$manager->createObjectContainer(\Sterc\MediaManager\Model\MediamanagerFilesContent::class);
$manager->createObjectContainer(\Sterc\MediaManager\Model\MediamanagerFilesRelations::class);
$manager->createObjectContainer(\Sterc\MediaManager\Model\MediamanagerFilesVersions::class);
$manager->createObjectContainer(\Sterc\MediaManager\Model\MediamanagerFilesMeta::class);

return true;
