<?php
namespace Sterc\MediaManager\Model\mysql;

use xPDO\xPDO;

class MediamanagerFilesMeta extends \Sterc\MediaManager\Model\MediamanagerFilesMeta
{

    public static $metaMap = array (
        'package' => 'Sterc\\MediaManager\\Model',
        'version' => NULL,
        'table' => 'mediamanager_files_meta',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'tableMeta' => 
        array (
            'engine' => 'InnoDB',
        ),
        'fields' => 
        array (
            'mediamanager_files_id' => 0,
            'meta_key' => '',
            'meta_value' => '',
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
            'meta_key' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
            'meta_value' => 
            array (
                'dbtype' => 'longtext',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
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
        ),
    );

}
