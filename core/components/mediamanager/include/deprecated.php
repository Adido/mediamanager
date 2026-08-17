<?php
/**
 * This file contains deprecated class aliases for mediamanager classes that were
 * namespaced in 3.0.
 *
 * For now, the old class names are automatically available through the aliases in this file.
 *
 * In the future (likely 3.3 or 3.4), the deprecated class aliases will no longer be automatically included.
 * If you still need the deprecated aliases after that, you can manually include this file in legacy code.
 */

// Model classes
class_alias(\Sterc\MediaManager\Model\MediamanagerCategories::class, \MediamanagerCategories::class);
class_alias(\Sterc\MediaManager\Model\MediamanagerTags::class, \MediamanagerTags::class);
class_alias(\Sterc\MediaManager\Model\MediamanagerFiles::class, \MediamanagerFiles::class);
class_alias(\Sterc\MediaManager\Model\MediamanagerDownloads::class, \MediamanagerDownloads::class);
class_alias(\Sterc\MediaManager\Model\MediamanagerFilesCategories::class, \MediamanagerFilesCategories::class);
class_alias(\Sterc\MediaManager\Model\MediamanagerFilesTags::class, \MediamanagerFilesTags::class);
class_alias(\Sterc\MediaManager\Model\MediamanagerFilesContent::class, \MediamanagerFilesContent::class);
class_alias(\Sterc\MediaManager\Model\MediamanagerFilesRelations::class, \MediamanagerFilesRelations::class);
class_alias(\Sterc\MediaManager\Model\MediamanagerFilesVersions::class, \MediamanagerFilesVersions::class);
class_alias(\Sterc\MediaManager\Model\MediamanagerFilesMeta::class, \MediamanagerFilesMeta::class);
class_alias(\Sterc\MediaManager\Model\MediamanagerFilesLicense::class, \MediamanagerFilesLicense::class);
class_alias(\Sterc\MediaManager\Model\MediamanagerFilesLicenseFile::class, \MediamanagerFilesLicenseFile::class);

// Model classes (mysql)
class_alias(\Sterc\MediaManager\Model\mysql\MediamanagerCategories::class, \MediamanagerCategories_mysql::class);
class_alias(\Sterc\MediaManager\Model\mysql\MediamanagerTags::class, \MediamanagerTags_mysql::class);
class_alias(\Sterc\MediaManager\Model\mysql\MediamanagerFiles::class, \MediamanagerFiles_mysql::class);
class_alias(\Sterc\MediaManager\Model\mysql\MediamanagerDownloads::class, \MediamanagerDownloads_mysql::class);
class_alias(\Sterc\MediaManager\Model\mysql\MediamanagerFilesCategories::class, \MediamanagerFilesCategories_mysql::class);
class_alias(\Sterc\MediaManager\Model\mysql\MediamanagerFilesTags::class, \MediamanagerFilesTags_mysql::class);
class_alias(\Sterc\MediaManager\Model\mysql\MediamanagerFilesContent::class, \MediamanagerFilesContent_mysql::class);
class_alias(\Sterc\MediaManager\Model\mysql\MediamanagerFilesRelations::class, \MediamanagerFilesRelations_mysql::class);
class_alias(\Sterc\MediaManager\Model\mysql\MediamanagerFilesVersions::class, \MediamanagerFilesVersions_mysql::class);
class_alias(\Sterc\MediaManager\Model\mysql\MediamanagerFilesMeta::class, \MediamanagerFilesMeta_mysql::class);
class_alias(\Sterc\MediaManager\Model\mysql\MediamanagerFilesLicense::class, \MediamanagerFilesLicense_mysql::class);
class_alias(\Sterc\MediaManager\Model\mysql\MediamanagerFilesLicenseFile::class, \MediamanagerFilesLicenseFile_mysql::class);

// Helpers
class_alias(\Sterc\MediaManager\Helpers\MediaManagerCategoriesHelper::class, \MediaManagerCategoriesHelper::class);
class_alias(\Sterc\MediaManager\Helpers\MediaManagerFilesHelper::class, \MediaManagerFilesHelper::class);
class_alias(\Sterc\MediaManager\Helpers\MediaManagerPermissionsHelper::class, \MediaManagerPermissionsHelper::class);
class_alias(\Sterc\MediaManager\Helpers\MediaManagerSourcesHelper::class, \MediaManagerSourcesHelper::class);
class_alias(\Sterc\MediaManager\Helpers\MediaManagerTagsHelper::class, \MediaManagerTagsHelper::class);

// Services
class_alias(\Sterc\MediaManager\MediaManager::class, \MediaManager::class);
