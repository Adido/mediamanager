<?php
namespace Sterc\MediaManager\Model\mysql;

use xPDO\xPDO;

class MediamanagerFilesRelations extends \Sterc\MediaManager\Model\MediamanagerFilesRelations
{

    public static $metaMap = array (
        'package' => 'Sterc\\MediaManager\\Model',
        'version' => NULL,
        'table' => 'mediamanager_files_relations',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'tableMeta' => 
        array (
            'engine' => 'InnoDB',
        ),
        'fields' => 
        array (
            'mediamanager_files_id' => 0,
            'mediamanager_files_id_relation' => 0,
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
            'mediamanager_files_id_relation' => 
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
            'mediamanager_files_id_relation' => 
            array (
                'alias' => 'mediamanager_files_id_relation',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'mediamanager_files_id_relation' => 
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
            'Files2' => 
            array (
                'class' => 'Sterc\\MediaManager\\Model\\MediamanagerFiles',
                'local' => 'mediamanager_files_id_relation',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
        ),
    );

}
