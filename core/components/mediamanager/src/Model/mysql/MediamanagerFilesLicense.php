<?php
namespace Sterc\MediaManager\Model\mysql;

use xPDO\xPDO;

class MediamanagerFilesLicense extends \Sterc\MediaManager\Model\MediamanagerFilesLicense
{

    public static $metaMap = array (
        'package' => 'Sterc\\MediaManager\\Model',
        'version' => NULL,
        'table' => 'mediamanager_files_license',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'tableMeta' => 
        array (
            'engine' => 'InnoDB',
        ),
        'fields' => 
        array (
            'image_createdon' => NULL,
            'image_source' => '',
            'image_valid_startdate' => NULL,
            'image_valid_enddate' => NULL,
            'license_exists' => 0,
            'license_depicted_consent' => 0,
            'license_path' => '',
        ),
        'fieldMeta' => 
        array (
            'image_createdon' => 
            array (
                'dbtype' => 'timestamp',
                'phptype' => 'timestamp',
                'null' => true,
                'default' => NULL,
            ),
            'image_source' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
            'image_valid_startdate' => 
            array (
                'dbtype' => 'timestamp',
                'phptype' => 'timestamp',
                'null' => true,
                'default' => NULL,
            ),
            'image_valid_enddate' => 
            array (
                'dbtype' => 'timestamp',
                'phptype' => 'timestamp',
                'null' => true,
                'default' => NULL,
            ),
            'license_exists' => 
            array (
                'dbtype' => 'tinyint',
                'precision' => '1',
                'attributes' => 'unsigned',
                'phptype' => 'boolean',
                'null' => false,
                'default' => 0,
            ),
            'license_depicted_consent' => 
            array (
                'dbtype' => 'tinyint',
                'precision' => '1',
                'attributes' => 'unsigned',
                'phptype' => 'boolean',
                'null' => false,
                'default' => 0,
            ),
            'license_path' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
        ),
        'aggregates' => 
        array (
            'LicenseFiles' => 
            array (
                'class' => 'Sterc\\MediaManager\\Model\\MediamanagerFilesLicenseFile',
                'local' => 'id',
                'foreign' => 'license_id',
                'cardinality' => 'many',
                'owner' => 'foreign',
            ),
        ),
    );

}
