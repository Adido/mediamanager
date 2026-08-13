<?php
namespace Sterc\MediaManager\Model;

use xPDO\xPDO;

/**
 * Class MediamanagerFiles
 *
 * @property integer $media_sources_id
 * @property string $name
 * @property string $path
 * @property integer $version
 * @property string $file_type
 * @property integer $file_size
 * @property string $file_dimensions
 * @property string $file_hash
 * @property string $upload_date
 * @property integer $uploaded_by
 * @property string $edited_on
 * @property integer $edited_by
 * @property boolean $is_archived
 * @property string $archive_date
 * @property string $archive_path
 *
 * @property \MediamanagerFilesLicenseFile $FileLicense
 * @property \MediamanagerFilesCategories[] $Categories
 * @property \MediamanagerFilesTags[] $Tags
 * @property \MediamanagerFilesContent[] $Content
 * @property \MediamanagerFilesRelations[] $Relations
 * @property \MediamanagerFilesRelations[] $Relations2
 *
 * @package Sterc\MediaManager\Model
 */
class MediamanagerFiles extends \xPDO\Om\xPDOSimpleObject
{
    /**
     * Remove file object.
     *
     * @param array $ancestors
     * @return void
     */
    public function remove(array $ancestors = array ())
    {
        $license = $this->getLicense();
        $result  = parent::remove($ancestors);

        /* Remove related license if it has no connections with other files. */
        if ($result && $license && count($license->getMany('LicenseFiles')) === 0) {
            $mediamanager = $this->xpdo->services->get('mediamanager');
            $mediamanager->files->removeLicenseFile($license);

            $license->remove();
        }

        return $result;
    }

    /**
     * Retrieve license attached to this file.
     *
     * @return void
     */
    public function getLicense()
    {
        if (($licenseRelation = $this->getOne('FileLicense')) && $license = $licenseRelation->getOne('License')) {
            return $license;
        }

        return null;
    }
}
