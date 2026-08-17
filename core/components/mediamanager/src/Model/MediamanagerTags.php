<?php
namespace Sterc\MediaManager\Model;

use xPDO\xPDO;

/**
 * Class MediamanagerTags
 *
 * @property integer $media_sources_id
 * @property string $name
 * @property boolean $is_deleted
 *
 * @property \MediamanagerFilesTags[] $Files
 *
 * @package Sterc\MediaManager\Model
 */
class MediamanagerTags extends \xPDO\Om\xPDOSimpleObject
{
}
