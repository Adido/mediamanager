<?php
namespace Sterc\MediaManager\Model;

use xPDO\xPDO;

/**
 * Class MediamanagerFilesVersions
 *
 * @property integer $mediamanager_files_id
 * @property integer $version
 * @property string $path
 * @property string $file_name
 * @property integer $file_size
 * @property string $file_dimensions
 * @property string $file_hash
 * @property string $action
 * @property integer $replaced_file_id
 * @property string $created
 * @property integer $created_by
 *
 * @package Sterc\MediaManager\Model
 */
class MediamanagerFilesVersions extends \xPDO\Om\xPDOSimpleObject
{
}
