<?php
namespace Sterc\MediaManager\Model\mysql;

use xPDO\xPDO;

class MediamanagerFilesTags extends \Sterc\MediaManager\Model\MediamanagerFilesTags
{

    public static $metaMap = array (
        'package' => 'Sterc\\MediaManager\\Model',
        'version' => NULL,
        'table' => 'mediamanager_files_tags',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'tableMeta' => 
        array (
            'engine' => 'InnoDB',
        ),
        'fields' => 
        array (
            'mediamanager_files_id' => 0,
            'mediamanager_tags_id' => 0,
        ),
        'fieldMeta' => 
        array (
            'mediamanager_files_id' => 
            array (
                'dbtype' => 'int',
                'precision' => '10',
                'attributes' => 'unsigned',
                'phptype' => 'integer',
                'null' => false,
                'default' => 0,
                'index' => 'index',
            ),
            'mediamanager_tags_id' => 
            array (
                'dbtype' => 'int',
                'precision' => '10',
                'attributes' => 'unsigned',
                'phptype' => 'integer',
                'null' => false,
                'default' => 0,
                'index' => 'index',
            ),
        ),
        'indexes' => 
        array (
            'mediamanager_files_id' => 
            array (
                'alias' => 'mediamanager_files_id',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'mediamanager_files_id' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'mediamanager_tags_id' => 
            array (
                'alias' => 'mediamanager_tags_id',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'mediamanager_tags_id' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
        ),
        'aggregates' => 
        array (
            'Files' => 
            array (
                'class' => 'Sterc\\MediaManager\\Model\\MediamanagerFiles',
                'local' => 'mediamanager_files_id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'Tags' => 
            array (
                'class' => 'Sterc\\MediaManager\\Model\\MediamanagerTags',
                'local' => 'mediamanager_tags_id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
        ),
    );

}
