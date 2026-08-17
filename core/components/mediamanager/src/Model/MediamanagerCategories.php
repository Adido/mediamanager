<?php
namespace Sterc\MediaManager\Model;

use xPDO\xPDO;

/**
 * Class MediamanagerCategories
 *
 * @property integer $media_sources_id
 * @property integer $parent_id
 * @property string $name
 * @property integer $rank
 *
 * @property \MediamanagerFilesCategories[] $Files
 *
 * @package Sterc\MediaManager\Model
 */
class MediamanagerCategories extends \xPDO\Om\xPDOSimpleObject
{
}
