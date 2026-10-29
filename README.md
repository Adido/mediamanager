# Media Manager for MODX [BETA]
![Media Manager version](https://img.shields.io/badge/version-0.3.3-brightgreen.svg)
![MODX Extra by Sterc](https://img.shields.io/badge/extra%20by-sterc-ff69b4.svg)

The Media Manager is a MODX Extra replacing the default Media Browser with an enterprise-grade media management solution, an initiative by [SEDA](https://seda.digital/) and [Sterc](https://www.sterc.com).

## Features
- Fully responsive
- Built on jQuery and Bootstrap for easier future development
- Fully database driven
- Virtual categories, instead of folders to allow a file to be in multiple categories
- Tag support
- Version control for your media
- Image editing features, including resizing and cropping
- Ability to publish/unpublish media with reference-checking
- Support for TinyMCE, Redactor 3.0, TV's, ContentBlocks
- Meta data for images out of the box: id, version, filename, version, author, dimensions (original + cropped image size), category, tags)
- Unlimited additional meta data (like TV's for images!)
- MODX ACL for removing/archiving images
- Attaching licenses to images including expiration dates

## Requirements
- PHP 8.1 or later

## Migrating from the default media browser
A migration tool is not yet available. You will not loose your old images, but they also will not show up in the new media browser.

## Installation
1. Install the package through package management by uploading the latest version from the _packages folder.
2. You have to enable the Media Manager per MediaSource. Go to the top-menu "Media" -> "Media Sources" -> "Create property": Name=mediamanagerSource, Type=Textfield, Value=1
3. Now enable it per user by editing a user: "Manage" -> "Users" -> Edit a user -> "Settings" -> "Create new" and set the key and/or name to "media_sources_id" and set the value to the ID of the Media Source you just enabled.

That's it, have fun!

## Redactor implementation
If you like to implement the mediamanager for Redactor 3.0, please take the following steps:
* Add the following path to the system setting **redactor.js**: `/assets/components/mediamanager/js/inputs/redactor_mediamanager.js`
* Go to the configuration set of redactor and add `mediamanager` under Miscellaneous --> Additional plugins
* Add `mediamanager` in the Toolbar Buttons list in the Toolbar tab

## Media Source settings

The following settings can be defined on media source level.

| Key                                | Description                                                                                                    | Example value                                                                                                                                                 |
|------------------------------------|----------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------|
| mediamanagerSource                 | Determine if media source should use MediaManager.                                                             | 1                                                                                                                                                             |
| mediamanagerMeta                   | Set default meta fields and determine if fields should be marked as required or not.                           | [{"key":"author", "label":"Author", "required": true}, {"key":"photographer", "label":"Photographer", "required": true}, {"key": "editor", "label":"Editor"}] |
| mediamanagerLicenseEnabled         | Determine if license fields should be used.                                                                    | 1                                                                                                                                                             |
| mediamanagerLicenseSources         | Set available license sources with optional expiry date.                                                      | [{"key": "local", "label": "Local"},{"key": "getty_images", "label": "Getty Images", "expireson":  "24-03-2023"}]                                             |
| mediamanagerLicenseTestFrequencies | Frequencies (in days) to send out a notification email before image sources and/or images are about to expire. | ["14 days", "5 days", "1 days"]                                                                                                                               |
| mediamanagerLicenseTestRecipients  | Recipient emails that should receive image source/image license expiry notifications.                          | john@example.com,johndoe@example.com                                                                                                                          |


## S3 media sources
Media Manager can store files on Amazon S3, or an S3-compatible service, through a MODX S3 media source (`sources.modS3MediaSource`). Enable it with the `mediamanagerSource` property, as for a file system source.

### Media source properties
These are read from the S3 media source.

| Key        | Description                                                                                                                         |
|------------|-------------------------------------------------------------------------------------------------------------------------------------|
| url        | Public URL of the bucket, used for file URLs. For example `https://my-bucket.s3.eu-west-2.amazonaws.com/`.                          |
| bucket     | Bucket name.                                                                                                                        |
| key        | Access key ID. Leave empty to use the AWS SDK's default credentials, such as environment variables or an IAM role on the server.   |
| secret_key | Secret access key.                                                                                                                  |
| region     | Bucket region, such as `eu-west-2`. Empty means `us-east-1`, as in MODX 2. The legacy value `EU` means `eu-west-1`.                 |

MODX 2's S3 source doesn't define the following properties by default, but they are used when added to the source as custom properties.

| Key      | Description                                                                         |
|----------|-------------------------------------------------------------------------------------|
| prefix   | Folder in the bucket to store files in. It is also added to file URLs.             |
| endpoint | Endpoint of an S3-compatible service, such as MinIO or DigitalOcean Spaces.          |
| version  | S3 API version. Defaults to `2006-03-01`.                                           |
| mediamanagerS3ObjectAclEnabled | Enable (`1`) or disable (`0`) setting an ACL on each file written to S3: `public-read` for files, `private` for archived files, versions and licence files. Set to `0` for buckets with ACLs disabled (Object Ownership "Bucket owner enforced", the default for new AWS buckets) and S3-compatible services that don't support ACLs. Defaults to `1` when not set. |

### System settings

| Key                          | Description                                                                                                                                                                                                                                         | Default |
|------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------|
| mediamanager.cache_path      | Local path for thumbnails, PDF previews and download zips of S3 sources. Each source uses its own `sources/{id}/` folder. Empty uses `{assets_path}mediamanager/`.                                                                                  | Empty   |
| mediamanager.cache_url       | URL of the cache path. Empty uses `{assets_url}mediamanager/`.                                                                                                                                                                                      | Empty   |

Thumbnails and previews are cached on the web server's disk, not in the bucket. Servers that don't share a disk each build their own cache.

### Public and private files
With `mediamanagerS3ObjectAclEnabled` enabled, archived files, versions and licence files are stored with a private ACL and opened through signed URLs that expire after an hour. This only keeps them private if the bucket policy doesn't grant public read access to the whole bucket.

With it disabled, the bucket policy alone decides access. To serve files publicly while keeping the private folders private, grant public `s3:GetObject` on the bucket except for the `archive/`, `version/` and `license/` folders (under the `prefix`, if one is set).

### IAM permissions
The credentials need the following permissions. Replace `my-bucket` with the bucket name.

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": "s3:ListBucket",
            "Resource": "arn:aws:s3:::my-bucket"
        },
        {
            "Effect": "Allow",
            "Action": [
                "s3:GetObject",
                "s3:PutObject",
                "s3:DeleteObject",
                "s3:AbortMultipartUpload",
                "s3:PutObjectAcl",
                "s3:GetObjectAcl"
            ],
            "Resource": "arn:aws:s3:::my-bucket/*"
        }
    ]
}
```

- `s3:ListBucket` is needed to delete a file's versions folder. Without it, S3 also answers checks for missing files with "access denied" instead of "not found", which fills the MODX error log.
- `s3:PutObjectAcl` and `s3:GetObjectAcl` are only needed when `mediamanagerS3ObjectAclEnabled` is enabled.
- `s3:AbortMultipartUpload` cleans up large uploads, which are sent in parts, when they fail.

## Cron jobs
Run Cron jobs by calling the cron script and by specifying the jobs you'd like to run (comma delimited).

```
php /assets/components/mediamanager/cronjob/cron.php --jobs=TestImageSourceValidity,TestImageValidity
```

| Job                     | Description                                                                   |
|-------------------------|-------------------------------------------------------------------------------|
| TestImageValidity       | Job which checks image expiry and sends notification emails if needed.        |
| TestImageSourceValidity | Job which checks image source expiry and sends notification emails if needed. |


## Development
The bundled libraries (Flysystem and the AWS SDK) are committed, prefixed with the `Sterc\MediaManager\Vendor\` namespace, so a checkout runs without installing anything. Composer is only needed to add, remove or update a library.

### Requirements
- PHP 8.1 or later on the command line
- Composer 2
- `curl`, to download Strauss the first time

### Setup
1. Install [Git Package Management](https://github.com/theboxer/Git-Package-Management) (GPM) on a MODX instance. In its system settings, set `gitpackagemanagement.packages_dir` to the folder you keep packages in, and `gitpackagemanagement.packages_base_url` to the URL that folder is served from.
2. Clone Media Manager into the packages folder:
   ```
   cd /path/to/packages
   git clone git@github.com:Adido/mediamanager.git
   ```
3. Run composer install:
   ```
   cd mediamanager/core/components/mediamanager
   composer install --no-dev
   ```
4. In MODX, open Git Package Management, choose "Add package" and enter the folder name, `mediamanager`. GPM creates the namespace, system settings, elements, menus and database tables from `_build/config.json`.
5. Enable Media Manager on a media source and for a user, as described in steps 2 and 3 of [Installation](#installation).

Always run Composer with `--no-dev`. Without it, Strauss adds a line to `vendor/autoload.php` that loads its development aliases, which map the unprefixed `Aws\` and `GuzzleHttp\` names onto our prefixed copies. `_build/strauss-postprocess.php` deletes the aliases file, so the line does nothing, but don't commit it.

### Updating the libraries
```
cd core/components/mediamanager
composer update --no-dev
```

Commit the changes to `composer.json`, `composer.lock`, `vendor/` and `vendor-prefixed/`.


## BETA BETA
Please note that this manager is BETA. Please create your issues in Github or create Pull Requests. Any questions? Email us at modx@sterc.nl
