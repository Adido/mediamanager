# Upgrading

## From MODX 2 to MODX 3

MediaManager now requires **MODX Revolution 3.x** and **PHP 8.1+**. See the [README.md](README.md#requirements) for the version matrix.

### Requirements

- MODX Revolution 3.x
- PHP 8.1 or higher

### Installation

Upgrade the same way you'd install any MODX package, using the latest build from the `_packages` folder.

### Breaking Changes

If you have custom snippets or plugins that call MediaManager directly, update them as follows:

#### 1. `$modx->mediamanager` is no longer set

The old connector script set this as a public property on `$modx`. Use MODX3's service container instead:

```php
$mediamanager = $modx->services->get('mediamanager');
```

#### 2. `$modx->getService('mediamanager', 'MediaManager', ...)` no longer works

Replace it with the same `$modx->services->get('mediamanager')` call shown above.

### Other Changes

#### 1. Unqualified class names are deprecated

Classes were moved into the `Sterc\MediaManager\*` namespace. For backwards compatibility, `core/components/mediamanager/include/deprecated.php` currently registers class aliases so old unqualified class names still resolve.

These aliases are planned for removal in a future release.

If you reference these classes directly (e.g. `$modx->getObject('MediamanagerFiles', ...)` or `new MediaManagerFilesHelper()`), migrate to the namespaced equivalents:

| Old (deprecated) | New |
|---|---|
| `MediamanagerCategories` | `Sterc\MediaManager\Model\MediamanagerCategories` |
| `MediamanagerTags` | `Sterc\MediaManager\Model\MediamanagerTags` |
| `MediamanagerFiles` | `Sterc\MediaManager\Model\MediamanagerFiles` |
| `MediamanagerDownloads` | `Sterc\MediaManager\Model\MediamanagerDownloads` |
| `MediamanagerFilesCategories` | `Sterc\MediaManager\Model\MediamanagerFilesCategories` |
| `MediamanagerFilesTags` | `Sterc\MediaManager\Model\MediamanagerFilesTags` |
| `MediamanagerFilesContent` | `Sterc\MediaManager\Model\MediamanagerFilesContent` |
| `MediamanagerFilesRelations` | `Sterc\MediaManager\Model\MediamanagerFilesRelations` |
| `MediamanagerFilesVersions` | `Sterc\MediaManager\Model\MediamanagerFilesVersions` |
| `MediamanagerFilesMeta` | `Sterc\MediaManager\Model\MediamanagerFilesMeta` |
| `MediamanagerFilesLicense` | `Sterc\MediaManager\Model\MediamanagerFilesLicense` |
| `MediamanagerFilesLicenseFile` | `Sterc\MediaManager\Model\MediamanagerFilesLicenseFile` |
| `MediaManagerCategoriesHelper` | `Sterc\MediaManager\Helpers\MediaManagerCategoriesHelper` |
| `MediaManagerFilesHelper` | `Sterc\MediaManager\Helpers\MediaManagerFilesHelper` |
| `MediaManagerPermissionsHelper` | `Sterc\MediaManager\Helpers\MediaManagerPermissionsHelper` |
| `MediaManagerSourcesHelper` | `Sterc\MediaManager\Helpers\MediaManagerSourcesHelper` |
| `MediaManagerTagsHelper` | `Sterc\MediaManager\Helpers\MediaManagerTagsHelper` |
| `MediaManager` | `Sterc\MediaManager\MediaManager` |

Each model class also has a MySQL platform map class, aliased the same way with a `_mysql` suffix (e.g. `MediamanagerFiles_mysql` → `Sterc\MediaManager\Model\mysql\MediamanagerFiles`).
