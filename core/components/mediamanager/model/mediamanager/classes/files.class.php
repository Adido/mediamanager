<?php

require_once __DIR__ . '/../../../libs/tinify/lib/Tinify/Exception.php';
require_once __DIR__ . '/../../../libs/tinify/lib/Tinify/ResultMeta.php';
require_once __DIR__ . '/../../../libs/tinify/lib/Tinify/Result.php';
require_once __DIR__ . '/../../../libs/tinify/lib/Tinify/Source.php';
require_once __DIR__ . '/../../../libs/tinify/lib/Tinify/Client.php';
require_once __DIR__ . '/../../../libs/tinify/lib/Tinify.php';

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

use Sterc\MediaManager\Traits\ErrorTrait;

class MediaManagerFilesHelper
{
    use ErrorTrait;

    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR  = 'error';

    const ARCHIVE_DIRECTORY = 'archive';
    const DOWNLOAD_DIRECTORY = 'download';
    const VERSION_DIRECTORY = 'version';
    const LICENSE_DIRECTORY = 'license';

    const DOWNLOAD_EXPIRATION = 14;

    /**
     * The mediaManager object.
     */
    private $mediaManager = null;

    /**
     * Storage of the media source being worked on.
     *
     * @var \Sterc\MediaManager\Storage\Storage|null
     */
    private $storage = null;

    /**
     * Upload paths, relative to the storage root.
     */
    private $uploadDirectoryYear = null;
    private $uploadDirectoryMonth = null;

    /**
     * Archive path.
     */
    private $archiveDirectory = null;

    /**
     * Download path, relative to the local cache root.
     */
    private $downloadDirectory = null;

    /**
     * Version path.
     */
    private $versionDirectory = null;

    /**
     * License path.
     */
    private $licenseDirectory;

    /**
     * Image types.
     * @var array
     */
    private $imageTypes = [];

    /**
     * Video types.
     * @var array
     */
    private $videoTypes = [];

    /**
     * Sort options.
     * @var array
     */
    private $sortOptions = [];

    /**
     * Filter options.
     * @var array
     */
    private $filterOptions = [];

    /**
     * Tinify options.
     * @var bool
     */
    private $tinifyEnabled = false;
    private $tinifyApiKey = false;
    private $tinifyLimit = 0;

    /**
     * MediaManagerFilesHelper constructor.
     *
     * @param MediaManager $mediaManager
     */
    public function __construct(MediaManager $mediaManager)
    {
        $this->mediaManager  = $mediaManager;
        $this->tinifyEnabled = $this->mediaManager->modx->getOption('mediamanager.tinify.enabled', null, false);
        $this->tinifyApiKey  = $this->mediaManager->modx->getOption('mediamanager.tinify.api_key', null, false);
        $this->tinifyLimit   = (int) $this->mediaManager->modx->getOption('mediamanager.tinify.limit', null, 500);

        $this->setImageTypes();
        $this->setVideoTypes();
        $this->setSortOptions();
        $this->setFilterOptions();

        $this->cleanupArchive();
    }

    /**
     * Get single file.
     *
     * @param int $fileId
     * @return array
     */
    public function getFile($fileId)
    {
        // Get file
        $file = $this->mediaManager->modx->getObject('MediamanagerFiles', ['id' => $fileId]);

        // Get file categories
        $q = $this->mediaManager->modx->newQuery('MediamanagerCategories');
        $q->innerJoin('MediamanagerFilesCategories', 'Files');
        $q->where(['Files.mediamanager_files_id' => $fileId]);
        $q->groupby('Files.mediamanager_categories_id');

        $categories = $this->mediaManager->modx->getIterator('MediamanagerCategories', $q);

        // Get file tags
        $q = $this->mediaManager->modx->newQuery('MediamanagerTags');
        $q->innerJoin('MediamanagerFilesTags', 'Files');
        $q->where(['Files.mediamanager_files_id' => $fileId]);
        $q->groupby('Files.mediamanager_tags_id');

        $tags = $this->mediaManager->modx->getIterator('MediamanagerTags', $q);

        // Get file relations
        $q = $this->mediaManager->modx->newQuery('MediamanagerFiles');
        $q->innerJoin('MediamanagerFilesRelations', 'Relations');
        $q->where(['Relations.mediamanager_files_id_relation' => $fileId]);

        $relations = $this->mediaManager->modx->getIterator('MediamanagerFiles', $q);

        $q = $this->mediaManager->modx->newQuery('MediamanagerFiles');
        $q->innerJoin('MediamanagerFilesRelations', 'Relations2');
        $q->where(['Relations2.mediamanager_files_id' => $fileId]);

        $relations2 = $this->mediaManager->modx->getIterator('MediamanagerFiles', $q);

        // Get file content
        $q = $this->mediaManager->modx->newQuery('MediamanagerFilesContent');
        $q->where(['mediamanager_files_id' => $fileId]);

        $content = $this->mediaManager->modx->getIterator('MediamanagerFilesContent', $q);

        // Get user
        $profile = null;
        $user    = $this->mediaManager->modx->getObject('modUser', [
            'id' => $file->get('uploaded_by')
        ]);

        if ($user) {
            $profile = $user->getOne('Profile');
        }

        return [
            'file'       => $file,
            'tags'       => $tags,
            'categories' => $categories,
            'relations'  => $relations,
            'relations2' => $relations2,
            'content'    => $content,
            'user'       => $profile
        ];
    }

    /**
     * Get single file html.
     *
     * @param int $fileId
     * @param string $template
     *
     * @return string
     */
    public function getFileHtml($fileId, $template)
    {
        $bodyData   = [];
        $footerData = [];

        $footerData['button'] = [
            'edit'              => 1,
            'crop'              => 1,
            'share'             => 1,
            'download'          => 1,
            'archive'           => 1,
            'archive_replace'   => 1,
            'delete'            => 1,
            'history'           => 1,
            'copy'              => 0
        ];

        $data                     = $this->getFile($fileId);
        $file                     = $this->fileToArray($data['file']);
        $file['path']             = $this->absoluteUrl($file['path']);
        $file['uploaded_by_name'] = ($data['user'] !== null ? $data['user']->get('fullname') : $this->mediaManager->modx->lexicon('mediamanager.files.file_unknown_user'));
        $bodyData['file']         = $file;
        $footerData['file']       = $file;

        // The cropper reads the image back from a canvas, so remote images must come from this origin
        $bodyData['crop_path'] = $file['path'];
        $storage               = $this->mediaManager->sources->getStorage($file['media_sources_id']);
        if ($template === 'crop' && $storage && $storage->isRemote()) {
            $bodyData['crop_path'] = $this->mediaManager->config['connector_url'] . '?' . http_build_query([
                'action'       => 'mgr/image',
                'HTTP_MODAUTH' => $this->mediaManager->modx->user->getUserToken(
                    $this->mediaManager->modx->context->get('key')
                ),
                'id'           => $file['id']
            ]);
        }

        // Set file type
        if ($this->isImage($file['file_type'])) {
            $bodyData['preview']  = sprintf('<img src="%s" />', $this->getThumbnail($file, 'url'));
            $bodyData['is_image'] = 1;
        } elseif ($file['file_type'] === 'pdf' && extension_loaded('Imagick') &&
            ($thumb = $this->getPdfPreview($file, 'url'))
        ) {
            $bodyData['preview']          = '<img src="' . $thumb . '" />';
            $bodyData['is_image']         = 0;
            $footerData['button']['crop'] = 0;
        } elseif ($file['file_type'] === 'svg') {
            $file['preview_path']         = $file['path'];
            $bodyData['preview']          = $this->mediaManager->getChunk('files/file_preview_img', $file);
            $bodyData['is_image']         = 0;
            $footerData['button']['crop'] = 0;
        } else {
            $bodyData['preview']          = $this->mediaManager->getChunk('files/file_preview_file', $file);
            $bodyData['is_image']         = 0;
            $footerData['button']['crop'] = 0;
        }

        // File categories
        if (isset($data['categories'])) {
            $bodyData['categories'] = '';

            foreach ($data['categories'] as $category) {
                $bodyData['categories'] .= '<option value="' . $category->get('id') . '" selected="selected">' . $category->get('name') . '</option>';
            }
        }

        // File tags
        if (isset($data['tags'])) {
            $bodyData['tags']        = '';
            $bodyData['source_tags'] = '';

            foreach ($data['tags'] as $tag) {
                $tagSource = $tag->get('media_sources_id') === 0 ? 'tags' : 'source_tags';

                $bodyData[$tagSource] .= '<option value="' . $tag->get('id') . '" selected="selected">' . $tag->get('name') . '</option>';
            }
        }

        // File content
        $bodyData['content'] = [];

        if (isset($data['content'])) {
            $shown = [];
            foreach ($data['content'] as $content) {
                $resId = $content->get('site_content_id');
                if (!in_array($resId, $shown)) {
                    $resource = $this->mediaManager->modx->getObject('modResource', $resId);
                    if ($resource) {
                        $bodyData['content'][] = sprintf('<a href="?a=resource/update&id=%s">%s (%s)</a>', $resId, $resource->get('pagetitle'), $resId);
                        $shown[] = $resId;
                    }
                }
            }
        }

        $bodyData['content'] = implode(', ', $bodyData['content']);

        // check if file is linked to resources
        // if so, show archive & replace button, otherwise only Archive button
        if (!empty($bodyData['content'])) {
            $footerData['button']['archive'] = 0;
            $footerData['button']['archive_replace'] = 1;
        } else {
            $footerData['button']['archive'] = 1;
            $footerData['button']['archive_replace'] = 0;
        }

        // File relations
        $bodyData['relations'] = [];
        foreach ($data['relations'] as $relation) {
            $bodyData['relations'][] = '<a href="#" data-file-id="' . $relation->get('id') . '">' . $relation->get('file_dimensions') . '</a>';
        }
        foreach ($data['relations2'] as $relation) {
            $bodyData['relations'][] = '<a href="#" data-file-id="' . $relation->get('id') . '">' . $relation->get('file_dimensions') . '</a>';
        }
        $bodyData['relations'] = implode(', ', $bodyData['relations']);

        // File and user permissions
        $bodyData['can_edit'] = 1;
        if ($file['is_archived'] === 1) {
            $footerData['button']['edit']     = 0;
            $footerData['button']['crop']     = 0;
            $footerData['button']['share']    = 0;
            $footerData['button']['download'] = 0;
            $footerData['button']['archive']  = 0;
            $footerData['button']['archive_replace'] = 0;

            $bodyData['can_edit']             = 0;
        } else {
            $footerData['button']['delete']   = 0;
        }

        if (!$this->mediaManager->permissions->edit()) {
            $footerData['button']['edit']     = 0;
            $footerData['button']['crop']     = 0;
            $footerData['button']['share']    = 0;
            $footerData['button']['download'] = 0;
            $footerData['button']['archive']  = 0;
            $footerData['button']['archive_replace'] = 0;
            $footerData['button']['delete']   = 0;
            $footerData['button']['copy']     = 1;

            $bodyData['can_edit']             = 0;
        }

        if (!$this->mediaManager->permissions->delete()) {
            $footerData['button']['delete']   = 0;
        }

        // File history
        $v = $this->mediaManager->modx->newQuery('MediamanagerFilesVersions');
        $v->where([
            'mediamanager_files_id' => $fileId
        ]);
        $v->sortBy('version', 'desc');

        $versions = $this->mediaManager->modx->getIterator('MediamanagerFilesVersions', $v);

        $storage             = $this->mediaManager->sources->getStorage($file['media_sources_id']);
        $bodyData['history'] = '';
        foreach ($versions as $version) {
            $versionArr = $version->toArray();
            if (
                isset($versionArr['created_by']) &&
                $versionArr['created_by'] != 0
            ) {
                $user = $this->mediaManager->modx->getObject('modUser', ['id' => $versionArr['created_by']]);
                if ($user) {
                    $profile = $user->getOne('Profile');
                    $versionArr['created_by'] = $profile->get('fullname');
                } else {
                    $versionArr['created_by'] = $this->mediaManager->modx->lexicon('mediamanager.files.file_unknown_user');
                }
            }

            $fileInformation                = pathinfo($versionArr['path']);
            $versionArr['type']             = strtolower($fileInformation['extension']);
            $versionArr['file_size']        = $this->formatFileSize($versionArr['file_size']);
            $versionArr['active_version']   = $file['version'];
            $versionArr['path']             = $storage ? $this->absoluteUrl($storage->privateUrl($versionArr['path'])) : '';

            $versionArr['replaceHtml'] = '';
            if ($versionArr['action'] == 'replace') {
                if ($versionArr['replaced_file_id'] != 0) {
                    $oldFile = $this->mediaManager->modx->getObject('MediamanagerFiles', [
                        'id' => $versionArr['replaced_file_id']
                    ]);

                    if ($oldFile) {
                        $versionArr['replaceHtml'] = '<a href="' . $oldFile->get('path') . '" target="_blank">' . $oldFile->get('name') . '</a> was replaced by <a href="' . $versionArr['path'] . '">' . $versionArr['file_name'] . '</a>.';
                    }
                }
            }

            $bodyData['history'] .= $this->mediaManager->getChunk('files/history', $versionArr);
        }

        $i = 0;

        $metaChunk        = $template === 'edit' ? 'files/formgroup_filemeta' : 'files/filemeta_row';
        $bodyData['meta'] = [];

        $metaValues = $this->mediaManager->modx->getCollection('MediamanagerFilesMeta', ['mediamanager_files_id' => $fileId]);
        foreach ($metaValues as $metaKey => $metaValue) {
            $metaValues[$metaValue->get('meta_key')] = [
                'key'   => $metaValue->get('meta_key'),
                'value' => $metaValue->get('meta_value')
            ];

            unset($metaValues[$metaKey]);
        }

        $sourceArray = $this->mediaManager->sources->getSource($file['media_sources_id']);
        if (isset($sourceArray['meta']) && is_array($sourceArray['meta'])) {
            foreach ($sourceArray['meta'] as $meta) {
                if (isset($metaValues[$meta['key']])) {
                    $meta['value']  = $metaValues[$meta['key']]['value'];

                    unset($metaValues[$meta['key']]);
                }

                $bodyData['meta'][$meta['key']] = $this->mediaManager->getChunk($metaChunk, [
                    'meta_key'      => $meta['key'],
                    'meta_value'    => $meta['value'] ?? '',
                    'meta_label'    => $meta['label'] ?: $meta['key'],
                    'required'      => isset($meta['required']) && $meta['required'],
                    'disabled'      => true,
                    'prefix'        => $i
                ]);

                $i++;
            }
        }

        foreach ($metaValues as $meta) {
            $bodyData['meta'][$meta['key']] = $this->mediaManager->getChunk($metaChunk, [
                'meta_key'      => $meta['key'],
                'meta_value'    => $meta['value'] ?: '',
                'meta_label'    => $meta['key'],
                'required'      => false,
                'disabled'      => false,
                'prefix'        => $i
            ]);

            $i++;
        }

        $bodyData['licensing.license_file_extensions'] = implode(', ', $sourceArray['licensing_file_allowed_extensions']);
        if ($licenseInfo = $data['file']->getLicense()) {
            foreach ($licenseInfo->toArray() as $key => $value) {
                if ($key === 'license_path' && !empty($value)) {
                    $value = $storage ? $this->absoluteUrl($storage->privateUrl(self::LICENSE_DIRECTORY . '/' . $value)) : '';
                } elseif ($key === 'image_source') {
                    if (!empty($sourceArray['licensing_sources'])) {
                        foreach ($sourceArray['licensing_sources'] as $source) {
                            if ($source['key'] === $value) {
                                $value = $source['label'];

                                if (!empty($source['expireson'])) {
                                    $value .= ' - Expires on: ' . $source['expireson'];
                                }
                            }
                        }
                    }
                }

                $bodyData['licensing.' . $key] = $value;
            }
        } else {
            /* Set default empty placeholders. */
            foreach ($this->mediaManager->modx->newObject('MediamanagerFilesLicense')->toArray() as $key => $value) {
                $bodyData['licensing.' . $key] = $value;
            }

            $bodyData['licensing.id'] = '';
        }

        if ($template === 'edit') {
            $bodyData['licenseFields'] = '';

            if (isset($sourceArray['licensing']) && $sourceArray['licensing'] === true) {
                $bodyData['licenseFields'] = $this->mediaManager->getChunk('files/formgroup_filelicense', $bodyData);
            }
        }

        $bodyData['licensing.source_options'] = implode('', $this->mediaManager->sources->getLicenseOptions($sourceArray, $licenseInfo ? $licenseInfo->get('image_source') : ''));

        $bodyData['meta']      = implode(PHP_EOL, $bodyData['meta']);
        $bodyData['metaCount'] = $i;

        return [
            'body'   => $this->mediaManager->getChunk('files/popup/' . $template, $bodyData),
            'footer' => $this->mediaManager->getChunk('files/popup/buttons/' . $template, $footerData)
        ];
    }

    /**
     * Get image thumbnail.
     *
     * @param array $file
     * @param string $type Can be path or url
     * @param string $hash - optional to find thumb for any version
     *
     * @return string
     */
    public function getThumbnail($file, $type = 'path', $hash = '')
    {
        if ($this->isImage($file['file_type'])) {
            $storage = $this->mediaManager->sources->getStorage($file['media_sources_id']);
            if (!$storage) {
                return '';
            }

            if (empty($hash)) {
                $hash = $file['file_hash'];
            }

            $thumbKey  = $this->thumbnailKey($file['file_path'], $hash, $file['file_type']);
            $thumbnail = [
                'path' => $storage->cachePath($thumbKey),
                'url'  => $storage->cacheUrl($thumbKey)
            ];

            if (!file_exists($thumbnail['path']) || !is_readable($thumbnail['path'])) {
                $params = [
                    'action' => 'mgr/thumbnail',
                    'HTTP_MODAUTH' => $this->mediaManager->modx->user->getUserToken(
                        $this->mediaManager->modx->context->get('key')
                    ),
                    'id' => $file['id']
                ];

                $thumbnail['url'] = $this->mediaManager->config['connector_url'] . '?' .
                    http_build_query($params);
            }

            return $thumbnail[$type];
        }

        return '';
    }

    /**
     * Get the local path or url of a PDF preview, if one was created.
     *
     * @param array $file
     * @param string $type Can be path or url
     *
     * @return string
     */
    public function getPdfPreview($file, $type = 'url')
    {
        $storage = $this->mediaManager->sources->getStorage($file['media_sources_id']);
        if (!$storage) {
            return '';
        }

        $previewKey = str_replace('.pdf', '_thumb.jpg', $file['file_path']);

        if (!is_readable($storage->cachePath($previewKey))) {
            return '';
        }

        return $type === 'path' ? $storage->cachePath($previewKey) : $storage->cacheUrl($previewKey);
    }

    /**
     * Get the local path of an image's thumbnail, creating it from the stored
     * file when it is missing.
     *
     * @param array $file
     *
     * @return string|false
     */
    public function ensureThumbnail($file)
    {
        $thumbPath = $this->getThumbnail($file, 'path');
        $storage   = $this->mediaManager->sources->getStorage($file['media_sources_id']);

        if (empty($thumbPath) || !$storage) {
            return false;
        }

        if (is_readable($thumbPath)) {
            return $thumbPath;
        }

        $localPath = $storage->localCopy($file['is_archived'] ? $file['archive_path'] : $file['file_path']);
        $created   = $localPath !== false && $this->createThumbnail($localPath, $thumbPath);

        $storage->releaseLocalCopy($localPath);

        return $created ? $thumbPath : false;
    }

    /**
     * Create a thumbnail with phpThumb.
     *
     * @param string $sourcePath Local path of the original image.
     * @param string $thumbPath Local path to write the thumbnail to.
     *
     * @return bool
     */
    private function createThumbnail($sourcePath, $thumbPath)
    {
        $modx = $this->mediaManager->modx;

        if (!$modx->loadClass('modPhpThumb', $modx->getOption('core_path') . 'model/phpthumb/', true, true)) {
            $modx->log(modX::LOG_LEVEL_ERROR, '[MediaManager] Could not load modPhpThumb class.');
            return false;
        }

        if (!$this->createLocalDirectory(dirname($thumbPath))) {
            return false;
        }

        $phpThumb = new modPhpThumb($modx);
        $phpThumb->initialize();
        $phpThumb->setParameter('h', 180);
        $phpThumb->setParameter('w', 230);
        $phpThumb->setParameter('q', 80);
        $phpThumb->setParameter('far', 1);
        $phpThumb->setParameter('config_allow_src_above_docroot', true);
        $phpThumb->set($sourcePath);

        if (!$phpThumb->GenerateThumbnail() || !$phpThumb->RenderToFile($thumbPath)) {
            $modx->log(modX::LOG_LEVEL_ERROR, '[MediaManager] Could not create thumbnail ' . $thumbPath);
            return false;
        }

        chmod($thumbPath, 0644);

        return true;
    }

    /**
     * Create the thumbnail or PDF preview for a stored file, from a local copy.
     *
     * @param string $localPath Local path of the file.
     * @param string $fileKey Storage key of the file.
     * @param string $fileType
     * @param string $hash
     */
    private function createPreviews($localPath, $fileKey, $fileType, $hash)
    {
        if ($this->isImage($fileType)) {
            $this->createThumbnail($localPath, $this->storage->cachePath($this->thumbnailKey($fileKey, $hash, $fileType)));
        } elseif ($fileType === 'pdf' && extension_loaded('Imagick')) {
            $previewPath = $this->storage->cachePath(str_replace('.pdf', '_thumb.jpg', $fileKey));

            if ($this->createLocalDirectory(dirname($previewPath))) {
                try {
                    $pdfPreview = new Imagick();
                    $pdfPreview->setResolution(230, 180);
                    $pdfPreview->readImage($localPath . '[0]');
                    $pdfPreview->setIteratorIndex(0);
                    $pdfPreview->setImageFormat('jpeg');
                    $pdfPreview->writeImage($previewPath);
                    $pdfPreview->clear();
                    $pdfPreview->destroy();
                } catch (ImagickException $exception) {
                    $this->mediaManager->modx->log(modX::LOG_LEVEL_ERROR, '[MediaManager] Could not create PDF preview ' . $previewPath . ': ' . $exception->getMessage());
                }
            }
        }
    }

    /**
     * Thumbnail key, relative to the cache root.
     *
     * @param string $fileKey Storage key of the file.
     * @param string $hash
     * @param string $fileType
     *
     * @return string
     */
    private function thumbnailKey($fileKey, $hash, $fileType)
    {
        $directory = dirname($fileKey);

        return ($directory === '.' ? '' : $directory . '/') . 'thumbs/' . $hash . '.' . $fileType;
    }

    /**
     * Get image dimensions from a local file.
     *
     * @param string $localPath
     * @param string $extension
     *
     * @return string Dimensions as WIDTHxHEIGHT, or empty when not an image.
     */
    private function getImageDimensions($localPath, $extension)
    {
        if ($this->isImage($extension) && ($image = getimagesize($localPath))) {
            return $image[0] . 'x' . $image[1];
        }

        return '';
    }

    /**
     * Get the absolute local path of a file.
     *
     * @param array $file
     * @param null|array $source Unused, the file's media source is always used. Kept for compatibility.
     * @param string $type Can be path or archive_path
     *
     * @return string Empty when the file has no local path, such as on S3.
     */
    public function filePath($file, $source = null, $type = 'path')
    {
        $storage = $this->mediaManager->sources->getStorage($file['media_sources_id']);
        if (!$storage) {
            return '';
        }

        return (string) $storage->localPath($file[$type]);
    }

    /**
     * Get file url.
     *
     * @param array $file
     * @param null|object $source Unused, the file's media source is always used. Kept for compatibility.
     * @param string $type Can be path or archive_path
     *
     * @return string
     */
    public function fileUrl($file, $source = null, $type = 'path')
    {
        $storage = $this->mediaManager->sources->getStorage($file['media_sources_id']);
        if (!$storage) {
            return '';
        }

        return $storage->url($file[$type]);
    }

    /**
     * Make a root-relative url absolute, using the site url's scheme and host.
     *
     * @param string $url
     *
     * @return string
     */
    public function absoluteUrl($url)
    {
        if ($url === '' || preg_match('#^([a-z0-9]+:)?//#i', $url)) {
            return $url;
        }

        $siteUrl = $this->mediaManager->modx->getOption('site_url');

        if (strpos($url, '/') === 0 && ($parts = parse_url($siteUrl)) && isset($parts['scheme'], $parts['host'])) {
            return $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '') . $url;
        }

        return $this->addTrailingSlash($siteUrl) . ltrim($url, '/');
    }

    /**
     * Return array from file row
     *
     * @param object $file
     * @param null|object $source Unused, the file's media source is always used. Kept for compatibility.
     *
     * @return array
     */
    public function fileToArray(?MediamanagerFiles $file = null, $source = null)
    {
        if ($file === null) {
            return [];
        }

        $file = $file->toArray();

        $file['categories']  = [];
        $file['file_size']   = $this->formatFileSize($file['file_size']);
        $file['file_path']   = $file['path'];
        $file['is_archived'] = (int) $file['is_archived'];
        $file['path']        = $this->fileUrl($file);

        // Absolute local path of the source, empty for sources without one, such as S3
        $storage           = $this->mediaManager->sources->getStorage($file['media_sources_id']);
        $file['base_path'] = $storage ? (string) $storage->localPath('') : '';

        return $file;
    }

    /**
     * Get files.
     *
     * @param string $search
     * @param array $filters
     * @param array $sorting
     * @param int $isArchive
     * @param int $limit
     * @param int $offset
     *
     * @return object xPDOIterator
     */
    public function getList($search = '', $filters = [], $sorting = [], $isArchive = 0, $limit = 50, $offset = 0)
    {
        $sourceId      = $this->mediaManager->sources->getCurrentSource();
        $sortColumn    = 'MediamanagerFiles.upload_date';
        $sortDirection = 'DESC';
        $where         = [];

        $q      = $this->mediaManager->modx->newQuery('MediamanagerFiles');
        $select = $this->mediaManager->modx->getSelectColumns('MediamanagerFiles', 'MediamanagerFiles');

        $where[]['MediamanagerFiles.is_archived'] = $isArchive;
        $where[]['MediamanagerFiles.media_sources_id'] = $sourceId;

        $searchArray = [];
        if (!empty($search)) {
            $searchArray = ['MediamanagerFiles.id' => $search];
        }

        if (!empty($search) && strlen($search) > 2) {
            $searchArray[]['OR:name:LIKE'] = '%' . $search . '%';
        }

        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                if (empty($value)) {
                    continue;
                }

                switch ($key) {
                    case 'type' :
                        if ((int) $value !== 0) {
                            $where[]['MediamanagerFiles.file_type:IN'] = $this->filterOptions[$key][$value]['types'];
                        }
                        break;

                    case 'user' :
                        $where[]['MediamanagerFiles.uploaded_by'] = (int) $value;
                        break;

                    case 'categories' :
                        if (is_array($value) && count($value) === 1) {
                            $categories = $this->mediaManager->categories->getCategories();
                            $categories = $this->mediaManager->getCategoryChildIds($categories, $value[0]);
                            $categories[] = $value[0];
                            $value = $categories;
                        }

                        $q->innerJoin('MediamanagerFilesCategories', 'Categories');
                        $where[]['Categories.mediamanager_categories_id:IN'] = $value;
                        break;

                    case 'tags' :
                        $q->innerJoin('MediamanagerFilesTags', 'Tags');
                        $where[]['Tags.mediamanager_tags_id:IN'] = $value;
                        break;
                    case 'date' :
                        if (!empty($filters['date']['from'])) {
                            if (is_numeric($filters['date']['from'])) {
                                $filters['date']['from'] = substr($filters['date']['from'], 0, 10);
                                $where[]['MediamanagerFiles.upload_date:>='] = date('Y-m-d 00:00:00', $filters['date']['from']);
                            } else {
                                $where[]['MediamanagerFiles.upload_date:>='] = date('Y-m-d 00:00:00', strtotime($filters['date']['from']));
                            }
                        }

                        if (!empty($filters['date']['to'])) {
                            $where[]['MediamanagerFiles.upload_date:<='] = date('Y-m-d 23:59:59', strtotime($filters['date']['to']));
                        }

                        break;
                }
            }
        }


        $where[] = $searchArray;

        if (!empty($sorting)) {
            $sortColumn    = 'MediamanagerFiles.' . $sorting[0];
            $sortDirection = $sorting[1];
        }

        $q->select($select);
        $q->where($where);
        $q->limit($limit, $offset);
        $q->sortby($sortColumn, $sortDirection);
        $q->groupby('MediamanagerFiles.id');

        return $this->mediaManager->modx->getIterator('MediamanagerFiles', $q);
    }

    /**
     * Get files html.
     *
     * @param int $category
     * @param string $search
     * @param array $filters
     * @param array $sorting
     * @param string $viewMode
     * @param array $selectedFiles
     * @param int $limit
     * @param int $offset
     *
     * @return string
     */
    public function getListHtml($category = 0, $search = '', $filters = [], $sorting = [], $viewMode = 'grid', $selectedFiles = [], $limit = 50, $offset = 0, $message = '')
    {
        $html = '';
        $viewMode = ($viewMode === 'grid' ? 'grid' : 'list');

        if ($category > 0 && ! isset($filters['categories'])) {
            $filters['categories'][] = $category;
        }

        $isArchive = 0;
        if ($category === -1) {
            $isArchive = 1;
        }

        $files = $this->getList($search, $filters, $sorting, $isArchive, $limit, $offset);

        $selectedFilesIds = [];
        foreach ($selectedFiles as $selectedFile) {
            $selectedFilesIds[] = $selectedFile['id'];
        }

        $breadcrumbs = '';

        if ($offset === 0) {
            switch ($category) {
                case 0 :
                    $breadcrumbs = $this->mediaManager->getChunk('files/breadcrumb', [
                        'id' => 0,
                        'name' => $this->mediaManager->modx->lexicon('mediamanager.global.root')
                    ]);
                    break;
                case -1 :
                    $breadcrumbs = $this->mediaManager->getChunk('files/breadcrumb', [
                        'id' => -1,
                        'name' => $this->mediaManager->modx->lexicon('mediamanager.global.archive')
                    ]);
                    break;
                default :
                    $breadcrumbs = $this->buildBreadcrumbs($this->mediaManager->categories->getCategories(), $category);
            }

            $breadcrumbs = ['breadcrumbs' => $breadcrumbs];
            $breadcrumbs = $this->mediaManager->getChunk('files/breadcrumbs', $breadcrumbs);
        }

        $imagickLoaded = extension_loaded('Imagick');
        $source = null;

        foreach ($files as $file) {
            $file = $this->fileToArray($file);

            $file['selected'] = 0;
            if (in_array($file['id'], $selectedFilesIds)) {
                $file['selected'] = 1;
            }

            if ($viewMode === 'grid') {
                if ($this->isImage($file['file_type'])) {
                    $file['preview_path'] = $this->getThumbnail($file, 'url');
                    $file['preview'] = $this->mediaManager->getChunk('files/file_preview_img', $file);
                } elseif ($imagickLoaded && $file['file_type'] === 'pdf' &&
                    ($thumb = $this->getPdfPreview($file, 'url'))
                ) {
                    $file['preview_path'] = $thumb;
                    $file['preview']      = $this->mediaManager->getChunk('files/file_preview_img', $file);
                } elseif ($file['file_type'] === 'svg') {
                    $file['preview_path']     = $file['path'];
                    $file['preview']          = $this->mediaManager->getChunk('files/file_preview_img', $file);
                } else {
                    $file['preview'] = $this->mediaManager->getChunk('files/file_preview_file', $file);
                }
            } else {
                $user = $this->mediaManager->modx->getObject('modUser', array('id' => $file['uploaded_by']));
                $profile = $user->getOne('Profile');
                $file['uploaded_by'] = $profile->get('fullname');

                $q = $this->mediaManager->modx->newQuery('MediamanagerCategories');
                $q->innerJoin('MediamanagerFilesCategories', 'Files');
                $q->where(array('Files.mediamanager_files_id' => $file['id']));

                $categories = $this->mediaManager->modx->getIterator('MediamanagerCategories', $q);

                foreach ($categories as $category) {
                    $file['categories'][] = $category->get('name');
                }
                $file['categories'] = implode(', ', $file['categories']);
            }

            $html .= $this->mediaManager->getChunk('files/' . $viewMode . '/file', $file);
        }

        if (empty($html) && $offset === 0) {
            $html = $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.error.no_files_found'), 'info');
        }

        $pagination = '';

        if (!empty($html)) {
            $paginationParameters = $_REQUEST;
            $paginationParameters['offset'] += $paginationParameters['limit'];
            $paginationParameters = http_build_query($paginationParameters);
            $pagination = '<a data-pagination class="clearfix media-pagination" href="' . $this->mediaManager->config['connector_url'] . '?' . $paginationParameters . '">Loading</a>';
        }

        $data = [
            'breadcrumbs' => $breadcrumbs,
            'items'       => $html,
            'pagination'  => $pagination
        ];

        $response = [
            'html' => $this->mediaManager->getChunk('files/' . $viewMode . '/list', $data),
            'type' => 'array'
        ];

        if ($offset !== 0) {
            $response['type'] = 'html';
        }

        return $response;
    }

    /**
     * Build breadcrumbs.
     *
     * @param array $categories
     * @param int $category
     * @param int $parent
     * @param string $children
     *
     * @return string
     */
    public function buildBreadcrumbs(array $categories, $category, $parent = -1, $children = '')
    {
        if ($parent === 0) {
            return $children;
        }

        foreach ($categories as $item) {
            if ($parent === -1) {
                if ($item->get('id') === $category) {
                    $parent = $item->get('parent_id');
                    return $this->buildBreadcrumbs($categories, $category, $parent) . $this->mediaManager->getChunk('files/breadcrumb', $item->toArray());
                }
            }

            if ($parent !== -1 && $item->get('id') === $parent) {
                return $this->buildBreadcrumbs($categories, $category, $item->get('parent_id')) . $this->mediaManager->getChunk('files/breadcrumb', $item->toArray());
            }
        }

        return $children;
    }

    /**
     * Get sort options html.
     *
     * @return string
     */
    public function getSortOptionsHtml()
    {
        $html = '';
        foreach ($this->sortOptions as $option) {
            $html .= $this->mediaManager->getChunk('files/sort_option', $option);
        }

        return $html;
    }

    /**
     * Set sort options.
     */
    private function setSortOptions()
    {
        $this->sortOptions = array(
            array(
                'name'      => $this->mediaManager->modx->lexicon('mediamanager.files.sorting.date'),
                'field'     => 'upload_date',
                'direction' => 'DESC'
            ),
            array(
                'name'      => $this->mediaManager->modx->lexicon('mediamanager.files.sorting.date.asc'),
                'field'     => 'upload_date',
                'direction' => 'ASC'
            ),
            array(
                'name'      => $this->mediaManager->modx->lexicon('mediamanager.files.sorting.name.asc'),
                'field'     => 'name',
                'direction' => 'ASC'
            ),
            array(
                'name'      => $this->mediaManager->modx->lexicon('mediamanager.files.sorting.name'),
                'field'     => 'name',
                'direction' => 'DESC'
            )
        );
    }

    /**
     * Get filter options.
     *
     * @return array
     */
    public function getFilterOptionsHtml()
    {
        $html = [];
        foreach ($this->filterOptions as $key => $options) {
            $html[$key] = '';
            foreach ($options as $option) {
                $html[$key] .= $this->mediaManager->getChunk('files/filter_option', $option);
            }
        }

        return $html;
    }

    /**
     * Set filter options.
     */
    private function setFilterOptions()
    {
        $options = array(
            xPDO::OPT_CACHE_KEY => 'mediamanager',
        );

        $filters = $this->mediaManager->modx->cacheManager->get('filters', $options);
        if ($filters) {
            return $this->filterOptions = $filters;
        }

        $filters = array(
            'users' => array(
                array(
                    'value' => '',
                    'name'  => $this->mediaManager->modx->lexicon('mediamanager.files.filter.all_users')
                )
            ),
            'dates' => array(
                array(
                    'value' => 'all',
                    'name'  => $this->mediaManager->modx->lexicon('mediamanager.files.filter.all_dates')
                ),
                array(
                    'value' => 'recent',
                    'name'  => $this->mediaManager->modx->lexicon('mediamanager.files.filter.date_recent')
                ),
                array(
                    'value' => 'custom',
                    'name'  => $this->mediaManager->modx->lexicon('mediamanager.files.filter.date_custom')
                )
            )
        );

        $users = $this->mediaManager->modx->getIterator('modUser');
        foreach ($users as $user) {
            if (!$this->mediaManager->permissions->isMediaManagerUser($user)) {
                continue;
            }
            $filters['users'][] = array(
                'value' => $user->get('id'),
                'name'  => $user->get('username')
            );
        }

        $filters['type'] = array(
            array(
                'value' => 0,
                'name'  => $this->mediaManager->modx->lexicon('mediamanager.files.filter.all_types'),
                'types' => []
            ),
            array(
                'value' => 1,
                'name'  => $this->mediaManager->modx->lexicon('mediamanager.files.filter.type_documents'),
                'types' => array(
                    'pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx', 'odt', 'ods', 'odp', 'odb', 'odg', 'odf', 'csv', 'pptx'
                )
            ),
            array(
                'value' => 2,
                'name'  => $this->mediaManager->modx->lexicon('mediamanager.files.filter.type_images'),
                'types' => $this->imageTypes
            ),
            array(
                'value' => 3,
                'name'  => $this->mediaManager->modx->lexicon('mediamanager.files.filter.type_other'),
                'types' => array(
                    'zip', 'tar', 'mp4', 'wmv', 'avi'
                )
            )
        );

        $this->mediaManager->modx->cacheManager->set('filters', $filters, 3600, $options);
        return $this->filterOptions = $filters;
    }

    /**
     * Set image types.
     */
    private function setImageTypes()
    {
        $this->imageTypes = array('jpg', 'png', 'gif', 'tiff', 'bmp', 'jpeg', 'webp', 'ico');
    }

    /**
     * Get image types.
     */
    public function getImageTypes()
    {
        return $this->imageTypes;
    }

    /**
     * Set video types.
     */
    private function setVideoTypes()
    {
        $this->videoTypes = array('webm', 'mkv', 'flv', 'ogg', 'avi', 'mov', 'wmv', 'mp4', 'mpg', 'm4v', '3gp');
    }

    /**
     * Get video types.
     */
    public function getVideoTypes()
    {
        return $this->videoTypes;
    }

    /**
     * Check if file type is image.
     *
     * @param string $type
     * @return bool
     */
    public function isImage($type)
    {
        return in_array($type, $this->imageTypes);
    }

    /**
     * Add file.
     *
     * @param array $data
     * @param array $options Available options: skip_file_hash_check, skip_tinify
     *
     * @return array
     */
    public function addFile($data = [], $options = [])
    {
        // Get file and data
        $file = $_FILES['file'];

        if (empty($data)) {
            $data = $_REQUEST;
        }

        if (!isset($data['source'])) {
            $data['source'] = $this->mediaManager->sources->getCurrentSource();
        }

        if (!isset($data['user'])) {
            $data['user'] = $this->mediaManager->modx->getUser()->get('id');
        }

        $mediaSource = $this->mediaManager->sources->getSource($data['source']);
        if ($mediaSource) {
            /* Handle licensing validation. */
            if (isset($mediaSource['licensing']) && $mediaSource['licensing'] === true) {
                /* Check required fields. */
                foreach (['image_valid_startdate', 'image_valid_enddate', 'license_exists', 'license_depicted_consent', 'image_source'] as $requiredField) {
                    if (!isset($data['license'][$requiredField]) || (empty($data['license'][$requiredField]) && $data['license'][$requiredField] !== '0')) {
                        $this->addError('l[' . $requiredField . ']', $this->mediaManager->modx->lexicon('mediamanager.error.required_field', [
                            'field' => $this->mediaManager->modx->lexicon('mediamanager.files.' . $requiredField)
                        ]));
                    }
                }

                /* Check conditional required field. */
                if (!empty($data['license']['license_exists']) && (int) $data['license']['license_exists'] === 1 && empty($_FILES['license_file'])) {
                    $this->addError('l[license_file]', $this->mediaManager->modx->lexicon('mediamanager.error.required_field', ['field' => $this->mediaManager->modx->lexicon('mediamanager.files.license_file')]));
                }

                if (!empty($_FILES['license_file'])) {
                    if (!in_array(pathinfo($_FILES['license_file']['name'], PATHINFO_EXTENSION), array_map(function ($value) {
                        return ltrim($value, '.');
                    }, $mediaSource['licensing_file_allowed_extensions']), true)) {
                        $this->addError('l[license_file]', $this->mediaManager->modx->lexicon('mediamanager.error.extension_not_allowed_for_field', [
                            'field'         => $this->mediaManager->modx->lexicon('mediamanager.files.license_file'),
                            'extensions'    => implode(', ', $mediaSource['licensing_file_allowed_extensions'])
                        ]));
                    }
                }

                /* Check if enddate is not before startdate. */
                if (
                    isset($data['license']['image_valid_startdate'], $data['license']['image_valid_enddate']) &&
                    strtotime($data['license']['image_valid_startdate']) > strtotime($data['license']['image_valid_enddate'])
                ) {
                    $this->addError('l[image_valid_enddate]', $this->mediaManager->modx->lexicon('mediamanager.error.date_comparison', [
                        'date1' => $this->mediaManager->modx->lexicon('mediamanager.files.image_valid_enddate'),
                        'date2' => $this->mediaManager->modx->lexicon('mediamanager.files.image_valid_startdate')
                    ]));
                }
            }

            if (isset($mediaSource['meta']) && is_array($mediaSource['meta'])) {
                foreach ($mediaSource['meta'] as $meta) {
                    if (isset($meta['required']) && $meta['required']) {
                        if (empty($data['meta'][$meta['key']])) {
                            $this->addError('m[' . $meta['key'] . ']',  $this->mediaManager->modx->lexicon('mediamanager.error.required_field', [
                                'field'  => $meta['label']
                            ]));
                        }
                    }
                }
            }

            if ($this->hasErrors()) {
                return [
                    'status'  => self::STATUS_ERROR,
                    'message' => $this->alertMessageHtml(implode('<br/>', $this->getErrors())),
                    'errors'  => $this->getErrors()
                ];
            }
        }

        // Create upload directory
        if (!$this->createUploadDirectory($data['source'])) {
            return [
                'status'  => self::STATUS_ERROR,
                'message' => $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.error.create_directory'), 'danger')
            ];
        }

        // Check if file hash exists
        $file['hash'] = $this->getFileHashByPath($file['tmp_name']);

        if (!isset($options['skip_file_hash_check'])) {
            $hashExists = $this->fileHashExists($file['hash']);
            if ($hashExists) {
                return [
                    'status'  => self::STATUS_ERROR,
                    'message' => $this->alertMessageHtml(
                        $this->mediaManager->modx->lexicon('mediamanager.files.error.file_exists', array(
                            'file' => $file['name'],
                            'link' => '<a href="#" data-file-id="' . $hashExists . '" data-preview-link>Link</a>'
                        )), 'danger')
                ];
            }
        }

        // Add unique id to file name if needed
        $fileInformation = pathinfo($file['name']);
        $fileName = $this->createUniqueFile($this->uploadDirectoryMonth, $this->sanitizeFileName($fileInformation['filename']), $fileInformation['extension']);

        $file['extension']   = strtolower($fileInformation['extension']);
        $file['unique_name'] = $fileName;

        // Tinify image, before it is stored
        if (!isset($options['skip_tinify'])) {
            if ($file['extension'] === 'jpg' || $file['extension'] === 'png') {
                if ($this->tinify($file['tmp_name'])) {
                    clearstatcache(true, $file['tmp_name']);
                    $file['size'] = filesize($file['tmp_name']);
                }
            }
        }

        $file['file_dimensions'] = $this->getImageDimensions($file['tmp_name'], $file['extension']);

        // Upload file
        if (!$this->uploadFile($file)) {
            return [
                'status'  => self::STATUS_ERROR,
                'message' => $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.error.file_upload', array('file' => $file['name'])), 'danger')
            ];
        }

        $this->createPreviews($file['tmp_name'], $this->uploadDirectoryMonth . $file['unique_name'], $file['extension'], $file['hash']);

        $file['version']    = $this->createVersionNumber();
        $file['media_sources_id'] = $data['source'];

        // Add file to database
        $fileId         = $this->insertFile($file, $data);
        $versionCreated = $this->saveFileVersion($fileId, $file, 'create');
        if (!$fileId || !$versionCreated) {
            // Remove file from server if saving failed
            $this->removeFile($file);

            return [
                'status'  => self::STATUS_ERROR,
                'message' => $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.error.file_save', array('file' => $file['name'])), 'danger')
            ];
        }

        return [
            'status'  => self::STATUS_SUCCESS,
            'message' => $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.success.file_upload', array('file' => $file['unique_name'])), 'success'),
            'fileId'  => $fileId
        ];
    }

    /**
     * Save file.
     *
     * @param array $fileData
     * @param array $data
     *
     * @return bool
     */
    private function insertFile($fileData, $data)
    {
        $file = $this->mediaManager->modx->newObject('MediamanagerFiles');

        $file->set('name',             $fileData['unique_name']);
        $file->set('version',          $fileData['version']);
        $file->set('path',             $this->uploadDirectoryMonth . $fileData['unique_name']);
        $file->set('file_type',        $fileData['extension']);
        $file->set('file_size',        $fileData['size']);
        $file->set('file_hash',        $fileData['hash']);
        $file->set('upload_date',      time());
        $file->set('uploaded_by',      isset($data['user']) ? $data['user'] : $this->mediaManager->modx->getUser()->get('id'));
        $file->set('edited_by',        isset($data['user']) ? $data['user'] : $this->mediaManager->modx->getUser()->get('id'));
        $file->set('media_sources_id', $data['source']);

        // If file type is image set dimensions
        if ($this->isImage($fileData['extension']) && !empty($fileData['file_dimensions'])) {
            $file->set('file_dimensions', $fileData['file_dimensions']);
        }

        if ($file->save()) {
            // Save categories
            if (isset($data['categories'])) {
                if (!is_array($data['categories'])) {
                    $data['categories'] = explode(',', $data['categories']);
                }

                foreach ($data['categories'] as $category) {
                    if (is_numeric($category)) {
                        $object = $this->mediaManager->modx->newObject('MediamanagerFilesCategories');

                        if ($object) {
                            $object->fromArray([
                                'mediamanager_files_id'         => $file->get('id'),
                                'mediamanager_categories_id'    => $category
                            ]);

                            $object->save();
                        }
                    }
                }
            }

            // Save tags
            if (isset($data['tags'])) {
                if (!is_array($data['tags'])) {
                    $data['tags'] = explode(',', $data['tags']);
                }

                foreach ($data['tags'] as $tag) {
                    if (is_numeric($tag)) {
                        $object = $this->mediaManager->modx->newObject('MediamanagerFilesTags');

                        if ($object) {
                            $object->fromArray([
                                'mediamanager_files_id' => $file->get('id'),
                                'mediamanager_tags_id'  => $tag
                            ]);

                            $object->save();
                        }
                    }
                }
            }

            // Save meta fields
            if (isset($data['meta']) && is_array($data['meta'])) {
                foreach ($data['meta'] as $key => $value) {
                    $object = $this->mediaManager->modx->newObject('MediamanagerFilesMeta');

                    if ($object) {
                        $object->fromArray([
                            'mediamanager_files_id' => $file->get('id'),
                            'meta_key'              => $key,
                            'meta_value'            => $value
                        ]);

                        $object->save();
                    }
                }
            }

            // Save license fields
            if (isset($data['license']) && is_array($data['license'])) {
                $object             = $this->mediaManager->modx->newObject('MediamanagerFilesLicense', $data['license']);
                $licenseFile        = $_FILES['license_file'] ?? null;

                if ($licenseFile) {
                    $fileInformation    = pathinfo($licenseFile['name']);
                    $fileName           = $this->createUniqueFile($this->licenseDirectory . $this->uploadDirectoryMonth, $this->sanitizeFileName($fileInformation['filename']), $fileInformation['extension']);

                    $licenseFile['extension']   = strtolower($fileInformation['extension']);
                    $licenseFile['unique_name'] = $fileName;

                    // Upload license file
                    if (!$this->uploadLicenseFile($licenseFile)) {
                        return [
                            'status'  => self::STATUS_ERROR,
                            'message' => $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.error.file_upload', array('file' => $licenseFile['name'])), 'danger')
                        ];
                    }

                    $object->set('license_path', $this->uploadDirectoryMonth . $licenseFile['unique_name']);
                }

                if ($object->save()) {
                    $objectRelation = $this->mediaManager->modx->newObject('MediamanagerFilesLicenseFile', [
                        'mediamanager_files_id' => $file->get('id'),
                        'license_id'            => $object->get('id')
                    ]);

                    $objectRelation->save();
                }
            }
        }

        return $file->get('id');
    }

    /**
     * Save file.
     *
     * @param int $fileId
     * @param array $rawData
     *
     * @return array
     */
    public function saveFile($fileId, $rawData)
    {
        $data       = [
            'name'      => '',
            'meta'      => []
        ];

        $meta    = [];
        $license = [];

        foreach ($rawData as $key => $value) {
            $key = preg_replace('/\s+/', '', $key);

            if (preg_match('/^meta\[([\d]+)\]\[(key|value)\]$/', $key, $matches)) {
                $meta[$matches[1]][$matches[2]] = $value;
            } elseif (preg_match('/^license\[(.*)\]$/', $key, $matches)) {
                $license[$matches[1]] = $value;
            } else if ($key === 'filename') {
                $data['name'] = $value;
            }
        }

        foreach ($meta as $index => $row) {
            $data['meta'][$row['key']] = array_merge($row, ['index' => $index]);
        }

        foreach ($license as $key => $row) {
            $data['license'][$key] = $row;
        }

        $file = $this->mediaManager->modx->getObject('MediamanagerFiles', [
            'id' => $fileId
        ]);

        $mediaSource = $this->mediaManager->sources->getSource($file->get('media_sources_id'));
        if ($mediaSource) {
            if (isset($mediaSource['meta']) && is_array($mediaSource['meta'])) {
                foreach ($mediaSource['meta'] as $meta) {
                    if (isset($meta['required']) && $meta['required']) {
                        if (empty($data['meta'][$meta['key']]['value'])) {
                            $this->addError(
                                'meta[ ' . $data['meta'][$meta['key']]['index'] . ' ][value]',
                                $this->mediaManager->modx->lexicon('mediamanager.error.required_field', [
                                    'field' => $meta['label']
                                ])
                            );
                        }
                    }
                }
            }
        }

        /* Handle licensing validation. */
        if (isset($mediaSource['licensing']) && $mediaSource['licensing'] === true) {
            $license = $file->getLicense();

            if (!$license) {
                $license = $this->mediaManager->modx->newObject('MediamanagerFilesLicense');
            }

            /* Check required fields. */
            foreach (['image_valid_startdate', 'image_valid_enddate', 'license_exists', 'license_depicted_consent', 'image_source'] as $requiredField) {
                if (empty($data['license'][$requiredField]) && $data['license'][$requiredField] !== '0') {
                    $this->addError('license[' . $requiredField . ']', $this->mediaManager->modx->lexicon('mediamanager.error.required_field', [
                        'field' => $this->mediaManager->modx->lexicon('mediamanager.files.' . $requiredField)
                    ]));
                }
            }

            /* Check conditional required field. */
            if (!empty($data['license']['license_exists']) && (int) $data['license']['license_exists'] === 1 && (empty($_FILES['license_file']) && empty($license->get('license_path')))) {
                $this->addError('license_file', $this->mediaManager->modx->lexicon('mediamanager.error.required_field', ['field' => $this->mediaManager->modx->lexicon('mediamanager.files.license_file')]));
            }

            if (!empty($_FILES['license_file'])) {
                if (!in_array(pathinfo($_FILES['license_file']['name'], PATHINFO_EXTENSION), array_map(function ($value) {
                    return ltrim($value, '.');
                }, $mediaSource['licensing_file_allowed_extensions']), true)
                ) {
                    $this->addError('license_file', $this->mediaManager->modx->lexicon('mediamanager.error.extension_not_allowed_for_field', [
                        'field'         => $this->mediaManager->modx->lexicon('mediamanager.files.license_file'),
                        'extensions'    => implode(', ', $mediaSource['licensing_file_allowed_extensions'])
                    ]));
                }
            }

            /* Check if enddate is not before startdate. */
            if (
                isset($data['license']['image_valid_startdate'], $data['license']['image_valid_enddate']) &&
                strtotime($data['license']['image_valid_startdate']) > strtotime($data['license']['image_valid_enddate'])
            ) {
                $this->addError('license[image_valid_enddate]', $this->mediaManager->modx->lexicon('mediamanager.error.date_comparison', [
                    'date1' => $this->mediaManager->modx->lexicon('mediamanager.files.image_valid_enddate'),
                    'date2' => $this->mediaManager->modx->lexicon('mediamanager.files.image_valid_startdate')
                ]));
            }
        }

        if ($this->hasErrors()) {
            return [
                'status'  => self::STATUS_ERROR,
                'message' => $this->alertMessageHtml(implode('<br/>', $this->getErrors()), 'danger'),
                'errors'  => $this->getErrors()
            ];
        }

        // Check whether or not a file version should be created.
        $createFileVersion = false;
        $actionName        = '';

        // sanitize early before change-check
        $data['name'] = $this->sanitizeFileName($data['name']);
        if ($file->get('name') !== $data['name']) {
            $createFileVersion = true;
            $actionName        = 'rename';
        }

        // Create upload directory
        if (!$this->createUploadDirectory($file->get('media_sources_id'))) {
            return [
                'status'  => self::STATUS_ERROR,
                'message' => $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.error.create_directory'), 'danger')
            ];
        }

        $file->set('name',      $this->sanitizeFileName($data['name']));
        $file->set('edited_on', time());
        $file->set('edited_by', $this->mediaManager->modx->getUser()->get('id'));

        $pathInfo               = pathinfo($file->get('path'));
        $data                   = array_merge($file->toArray(), $data);
        $fileInformation        = pathinfo($data['path']);
        $filename               = $this->sanitizeFileName($fileInformation['filename']) . '.' . $fileInformation['extension'];

        $data['unique_name']    = $filename;
        $data['extension']      = $fileInformation['extension'];

        // Save meta fields
        if (isset($data['meta']) && is_array($data['meta'])) {
            $this->mediaManager->modx->removeCollection('MediamanagerFilesMeta', [
                'mediamanager_files_id' => $file->get('id')
            ]);

            foreach ($data['meta'] as $meta) {
                if (!empty($meta['key'])) {
                    $object = $this->mediaManager->modx->newObject('MediamanagerFilesMeta');

                    if ($object) {
                        $object->fromArray([
                            'mediamanager_files_id' => $file->get('id'),
                            'meta_key'              => $meta['key'],
                            'meta_value'            => $meta['value']
                        ]);

                        $object->save();
                    }
                }
            }
        }

        /* Handle license file. */
        if (isset($mediaSource['licensing']) && $mediaSource['licensing'] === true) {
            if (!empty($_FILES['license_file'])) {
                /* Remove current file. */
                if (!empty($license->get('license_path'))) {
                    $this->storage->delete($this->licenseDirectory . $license->get('license_path'));
                }

                /* Upload new file. */
                $licenseFile                = $_FILES['license_file'];
                $fileInformation            = pathinfo($licenseFile['name']);
                $fileName                   = $this->createUniqueFile($this->licenseDirectory . $this->uploadDirectoryMonth, $this->sanitizeFileName($fileInformation['filename']), $fileInformation['extension']);
                $licenseFile['unique_name'] = $fileName;

                /* Upload license file. */
                if (!$this->uploadLicenseFile($licenseFile)) {
                    return [
                        'status'  => self::STATUS_ERROR,
                        'message' => $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.error.file_upload', array('file' => $licenseFile['name'])), 'danger')
                    ];
                } else {
                    $license->set('license_path', $this->uploadDirectoryMonth . $licenseFile['unique_name']);
                    $license->save();
                }
            }

            if (isset($data['license']) && is_array($data['license'])) {
                $license->fromArray($data['license']);
                $license->save();

                /* Store connection between file and license. */
                if (!$this->mediaManager->modx->getObject('MediamanagerFilesLicenseFile', [
                    'mediamanager_files_id' => $file->get('id'),
                    'license_id'            => $license->get('id')
                ])) {
                    $licenseRelation = $this->mediaManager->modx->newObject('MediamanagerFilesLicenseFile', [
                        'mediamanager_files_id' => $file->get('id'),
                        'license_id'            => $license->get('id')
                    ]);

                    $licenseRelation->save();
                }
            }
        }

        if ($createFileVersion) {
            $data['version'] = $this->createVersionNumber($file->get('id'));
            $file->set('version', $data['version']);
            $this->saveFileVersion($file->get('id'), $data, $actionName);
        }
        $file->save();

        $this->mediaManager->modx->invokeEvent(
            'MediaManagerFileSaved',
            [
                'file_id' => $fileId,
                'file' => $file,
                'created_new_version' => $createFileVersion,
                'version_number' => $file->get('version'),
            ]
        );

        return [];
    }

    /**
     * Save file version.
     *
     * @param int $fileId
     * @param array $file
     * @param string $action
     * @param int $replacedId
     *
     * @return bool
     */
    public function saveFileVersion($fileId, $file, $action = '', $replacedId = 0) {
        if (!isset($fileId) || empty($fileId) || $fileId === 0) {
            return false;
        }

        if (!$this->createUploadDirectory($file['media_sources_id'])) {
            return false;
        }

        $version = $this->mediaManager->modx->newObject('MediamanagerFilesVersions');

        $file['file_id']      = $fileId;
        $file['version_path'] = $this->versionDirectory . $file['file_id'];

        // Add unique id to file name if needed
        $fileInformation = pathinfo($file['unique_name']);
        $versionFileName = $this->sanitizeFileName($fileInformation['filename']) . '-v' . $file['version'] . '.' . $fileInformation['extension'];

        if (empty($file['extension'])) {
            $file['extension'] = $fileInformation['extension'];
        }

        $path = $file['version_path'] . '/' . $versionFileName;

        // The stored file to copy: the file's own path, or the file just uploaded
        $sourceKey = !empty($file['path']) ? $file['path'] : $this->uploadDirectoryMonth . $file['unique_name'];

        if ($this->uploadVersionFile($sourceKey, $path) === false) {
            return false;
        }

        $version->set('mediamanager_files_id', $fileId);
        $version->set('version',               $file['version']);
        $version->set('path',                  $path);
        $version->set('file_name',             $file['name']);
        $version->set('file_size',             !empty($file['size']) ? $file['size'] : $file['file_size']);
        $version->set('created_by',            $this->mediaManager->modx->getUser()->get('id'));
        $version->set('action',                $action);

        if($replacedId != 0){
            $version->set('replaced_file_id', $replacedId);
        }

        // If file type is image set dimensions
        if ($this->isImage($file['extension']) && !empty($file['file_dimensions'])) {
            $version->set('file_dimensions', $file['file_dimensions']);
        }

        $version->set('file_hash', !empty($file['hash']) ? $file['hash'] : $file['file_hash']);

        if (!$version->save()) {
            return false;
        }

        $previous = $this->mediaManager->modx->getObject(
            'MediamanagerFiles',
            [
                'id' => $fileId,
            ]
        );

        $this->mediaManager->modx->invokeEvent(
            'MediaManagerFileVersionChanged',
            [
                'action' => $action,
                'replaced_id' => $replacedId,
                'file_id' => $fileId,
                'previous_version_number' => $previous instanceof MediamanagerFiles ? $previous->get('version') : 0,
            ]
        );

        return true;
    }

    /**
     * Revert to a specified version of a file.
     *
     * @param $versionId
     *
     * @return array $response
     */
    public function revertFile($versionId)
    {
        $version = $this->mediaManager->modx->getObject('MediamanagerFilesVersions', $versionId);
        if (!$version) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => $this->alertMessageHtml(
                    $this->mediaManager->modx->lexicon(
                        'mediamanager.files.error.version_not_found',
                        array(
                            'version' => $versionId,
                        )
                    ),
                    'danger'
                ),
            ];
        }

        $file = $this->mediaManager->modx->getObject('MediamanagerFiles', $version->get('mediamanager_files_id'));
        if (!$file) {
            return [
                'status'  => self::STATUS_ERROR,
                'message' => $this->alertMessageHtml(
                    $this->mediaManager->modx->lexicon('mediamanager.files.error.file_not_found'),
                    'danger'
                ),
            ];
        }

        $previousVersion = $file->get('version');

        $file->set('name',            $version->get('file_name'));
        $file->set('file_size',       $version->get('file_size'));
        $file->set('file_dimensions', $version->get('file_dimensions'));
        $file->set('file_hash',       $version->get('file_hash'));
        $file->set('version',         $version->get('version'));
        $file->set('edited_on',       time());
        $file->set('edited_by',       $this->mediaManager->modx->getUser()->get('id'));

        if (!$this->setUploadPaths($file->get('media_sources_id'))) {
            $this->mediaManager->modx->log(modX::LOG_LEVEL_ERROR, '[MediaManager] Could not restore version ' . $versionId . ' of file ' . $file->get('id') . ': media source ' . $file->get('media_sources_id') . ' is not available to Media Manager.');
        }

        //Get old file and replace current file
        $versionFile = $this->removeSlashes($version->get('path'));
        $currentFile = $this->removeSlashes($file->get('path'));

        $replacedFile = $this->storage && $this->storage->copy($versionFile, $currentFile);

        if ($replacedFile) {
            // Listeners have always received the absolute path of the version file, where there is one
            $versionFile = $this->storage->localPath($versionFile) ?: $versionFile;
        }

        if (!$replacedFile) {
            return [
                'status'  => self::STATUS_ERROR,
                'message' => $this->alertMessageHtml(
                    $this->mediaManager->modx->lexicon(
                        'mediamanager.files.error.revertfile_failed',
                        array(
                            'file' => $file->get('name'),
                        )
                    ),
                    'danger'
                ),
            ];
        }

        if (!$file->save()) {
            return [
                'status'  => self::STATUS_ERROR,
                'message' => $this->alertMessageHtml(
                    $this->mediaManager->modx->lexicon(
                        'mediamanager.files.error.revert_failed',
                        array(
                            'file' => $file->get('name'),
                        )
                    ),
                    'danger'
                ),
            ];
        }

        $this->mediaManager->modx->invokeEvent(
            'MediaManagerVersionChanged',
            [
                'file' => $file,
                'file_id' => $file->get('id'),
                'version' => $versionFile,
                'version_id' => $versionId,
                'previous_version_number' => $previousVersion,
            ]
        );

        return [
            'status'  => self::STATUS_SUCCESS,
            'message' => ''
        ];
    }

    /*
     * Retrieve versionnumber for creating a new version of a file.
     *
     * @param int $fileId
     *
     * @return int $versionNumber
     */
    private function createVersionNumber($fileId = 0)
    {
        $versionNumber = 1;
        if ($fileId != 0) {
            $sql = "SELECT MAX(version) as highestVersionNumber FROM " . $this->mediaManager->modx->getTableName('MediamanagerFilesVersions') . " WHERE mediamanager_files_id = '$fileId'";
            $query = $this->mediaManager->modx->query($sql);
            if ($query) {
                while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                    if ($row['highestVersionNumber']) {
                        $versionNumber = ++$row['highestVersionNumber'];
                    }
                }
            }
        }

        return $versionNumber;
    }

    /**
     * Delete file.
     *
     * @param $selectedFiles
     * @return array
     */
    public function deleteFiles($selectedFiles)
    {
        $response = [
            'status'    => self::STATUS_SUCCESS,
            'message'   => ''
        ];

        if (!$this->mediaManager->permissions->delete()) {
            return [
                'status'    => self::STATUS_ERROR,
                'message'   => ''
            ];
        }

        $fileIds = [];
        if (!is_array($selectedFiles)) {
            $fileIds[] = $selectedFiles;
        } else {
            foreach ($selectedFiles as $file) {
                $fileIds[] = $file['id'];
            }
        }

        $files = $this->mediaManager->modx->getIterator('MediamanagerFiles', ['id:IN' => $fileIds]);
        if (!$files) {
            return false;
        }

        foreach ($files as $file) {
            $fileRow = $file;
            $file    = $this->fileToArray($fileRow);
            $path    = $file['file_path'];
            if ($file['is_archived']) {
                $path = $file['archive_path'];
            }

            $storage = $this->mediaManager->sources->getStorage($file['media_sources_id']);
            if (!$storage) {
                $response['status']   = self::STATUS_ERROR;
                $response['message'] .= $this->mediaManager->modx->lexicon('mediamanager.files.error.file_not_found') . '<br />';
                continue;
            }

            // Delete all versions from server
            $versions = $this->mediaManager->modx->getIterator('MediamanagerFilesVersions', ['mediamanager_files_id' => $file['id']]);
            foreach ($versions as $version) {
                // Delete thumbnails for each version
                $thumbPath = $this->getThumbnail($file, 'path', $version->get('file_hash'));
                if ($thumbPath && file_exists($thumbPath)) {
                    unlink($thumbPath);
                }

                $storage->delete($version->get('path'));
                $version->remove();
            }

            // Delete versions directory
            $dir = self::VERSION_DIRECTORY . '/' . $file['id'];
            if (!$storage->deleteDirectory($dir)) {
                $response['message'] .= $this->mediaManager->modx->lexicon('mediamanager.files.error.delete_dir', ['dir' => $dir]) . '<hr />';
            }

            // Delete thumbnail and PDF preview from server
            $thumbPath = $this->getThumbnail($file, 'path');
            if ($thumbPath && file_exists($thumbPath)) {
                unlink($thumbPath);
            }

            if ($file['file_type'] === 'pdf' && ($previewPath = $this->getPdfPreview($file, 'path'))) {
                unlink($previewPath);
            }

            // Delete file from server
            $storage->delete($path);

            // Delete file
            $fileRow->remove();

            // Delete file categories
            $this->mediaManager->modx->removeCollection('MediamanagerFilesCategories', ['mediamanager_files_id' => $file['id']]);

            // Delete file tags
            $this->mediaManager->modx->removeCollection('MediamanagerFilesTags', ['mediamanager_files_id' => $file['id']]);

            // Delete file relations
            $this->mediaManager->modx->removeCollection('MediamanagerFilesRelations', ['mediamanager_files_id' => $file['id']]);
            $this->mediaManager->modx->removeCollection('MediamanagerFilesRelations', ['mediamanager_files_id_relation' => $file['id']]);

            $this->mediaManager->modx->invokeEvent(
                'MediaManagerFileDeleted',
                [
                    'file' => $file,
                ]
            );
        }

        return $response;
    }

    /**
     * Move files.
     *
     * @param array $selectedFiles
     * @param int $category
     *
     * @return array
     */
    public function moveFiles($selectedFiles, $category)
    {
        foreach ($selectedFiles as $file) {
            $fileCategory = $this->mediaManager->modx->getObject('MediamanagerFilesCategories', array(
                'mediamanager_files_id' => $file['id'],
                'mediamanager_categories_id' => $file['category']
            ));

            if (!$fileCategory) {
                $fileCategory = $this->mediaManager->modx->newObject('MediamanagerFilesCategories');
                $fileCategory->set('mediamanager_files_id', $file['id']);
            }

            $fileCategory->set('mediamanager_categories_id', $category);
            $fileCategory->save();
        }

        return [
            'status'  => self::STATUS_SUCCESS,
            'message' => $this->mediaManager->modx->lexicon('mediamanager.files.success.files_moved')
        ];
    }

    /**
     * Archive files.
     *
     * @param array|int $fileIds
     * @return array
     */
    public function archiveFiles($selectedFiles)
    {
        $response = [
            'status'        => self::STATUS_SUCCESS,
            'message'       => '',
            'archivedFiles' => []
        ];

        $fileIds = [];
        if (!is_array($selectedFiles)) {
            $fileIds[] = $selectedFiles;
        } else {
            foreach ($selectedFiles as $file) {
                $fileIds[] = $file['id'];
            }
        }

        // Check if files are linked to a resource
        $q = $this->mediaManager->modx->newQuery('MediamanagerFilesContent');
        $q->select('
            MediamanagerFilesContent.mediamanager_files_id AS id,
            Files.name AS name
        ');
        $q->innerJoin('MediamanagerFiles', 'Files');
        $q->where([
            'MediamanagerFilesContent.mediamanager_files_id:IN' => $fileIds
        ]);
        $q->groupby('MediamanagerFilesContent.mediamanager_files_id');
        $q->prepare();

        $query   = $this->mediaManager->modx->query($q->toSQL());
        $results = $query->fetchAll(PDO::FETCH_OBJ);

        // Send error message
        if (!empty($results)) {
            $message = '';

            foreach ($results as $result) {
                $message .= $this->mediaManager->modx->lexicon('mediamanager.files.error.file_linked', ['file' => $result->name]) . '<br />';
            }

            $response['status']  = self::STATUS_ERROR;
            $response['message'] = $this->alertMessageHtml($message, 'danger');
            return $response;
        }

        // Archive files
        foreach ($fileIds as $key => $id) {
            $file = $this->mediaManager->modx->getObject('MediamanagerFiles', $id);

            // Create archive directories
            if (!$this->createUploadDirectory($file->get('media_sources_id'))) {
                $response['status']  = self::STATUS_ERROR;
                $response['message'] = $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.error.create_directory'), 'danger');
                return $response;
            }

            $old = $file->get('path');
            $new = $this->createUniqueFile($this->archiveDirectory, time(), $file->get('file_type'), uniqid('-'));

            // Archived files are stored privately
            if ($this->storage->fileExists($old) && !$this->storage->move($old, $this->archiveDirectory . $new, true)) {
                $response['status'] = self::STATUS_ERROR;
                $response['message'] .= $this->mediaManager->modx->lexicon('mediamanager.files.error.file_archive', array('id' => $id)) . '<br />';
                continue;
            }

            $file->set('is_archived', 1);
            $file->set('archive_date', time());
            $file->set('archive_path', $this->archiveDirectory . $new);
            $file->save();

            $response['archivedFiles'][] = $key;
            $this->mediaManager->modx->invokeEvent(
                'MediaManagerFileArchived',
                [
                    'file' => $file,
                    'file_id' => $id,
                ]
            );
        }

        $archived = [];
        foreach ($response['archivedFiles'] as $index) {
            $archived[$index] = $fileIds[$index];
        }
        $this->mediaManager->modx->invokeEvent(
            'MediaManagerFilesArchived',
            [
                'files' => $archived,
            ]
        );

        if (!empty($response['message'])) {
            $response['message'] = $this->alertMessageHtml($response['message'], 'danger');
        }

        return $response;
    }

    /**
     * Archive and replace file.
     *
     * @param int $fileId
     * @param int $newFileId
     *
     * @return array
     */
    public function archiveReplaceFile($fileId, $newFileId)
    {
        if ($fileId === $newFileId) {
            return [
                'message' => $this->mediaManager->modx->lexicon('mediamanager.files.archive_and_replace.success')
            ];
        }

        // New file
        $newFile = $this->mediaManager->modx->getObject('MediamanagerFiles', array('id' => $newFileId));

        // Get linked content
        $q = $this->mediaManager->modx->newQuery('MediamanagerFilesContent');
        $q->select('
            MediamanagerFilesContent.*,
            Files.path
        ');
        $q->innerJoin('MediamanagerFiles', 'Files');
        $q->where(array('MediamanagerFilesContent.mediamanager_files_id' => $fileId));

        $linkedContent = $this->mediaManager->modx->getCollection('MediamanagerFilesContent', $q);

        // Replace all content and template variables with new file
        foreach ($linkedContent as $fileContent) {
            if ($fileContent->get('is_tmplvar')) {
                // Replace template variable
                $tvData = [
                    'tmplvarid' => $fileContent->get('site_tmplvars_id'),
                    'contentid' => $fileContent->get('site_content_id')
                ];
                if (!$templateVariable = $this->mediaManager->modx->getObject('modTemplateVarResource', $tvData)) {
                    $templateVariable = $this->mediaManager->modx->newObject('modTemplateVarResource');
                    $templateVariable->fromArray($tvData);
                }
                $templateVariable->set('value', $newFile->get('id'));
                $templateVariable->save();
            } else {
                if ($oldFile  = $this->mediaManager->modx->getObject('MediamanagerFiles', $fileId)) {
                    $oldUrl   = $this->fileUrl($oldFile->toArray());
                    $newUrl   = $this->fileUrl($newFile->toArray());

                    // Replace resource CB properties
                    $resource      = $this->mediaManager->modx->getObject('modResource', $fileContent->get('site_content_id'));
                    $properties    = $resource->getProperties('contentblocks');
                    $parser        = $this->mediaManager->modx->getService('modParser');
                    $corePath      = $this->mediaManager->modx->getOption('contentblocks.core_path', null, MODX_CORE_PATH . 'components/contentblocks/');
                    $contentBlocks = $this->mediaManager->modx->getService('contentblocks', 'ContentBlocks', $corePath . 'model/contentblocks/');
                    $content       = json_decode(isset($properties['content']) ? (string) $properties['content'] : '', true);

                    if (!is_array($content)) {
                        continue;
                    }

                    foreach ($content as &$layout) {
                        foreach ($layout['content'] as &$value) {
                            foreach ($value as &$field) {
                                if (!empty($field['file_id']) && $field['file_id'] == $oldFile->get('id') &&
                                    !empty($field['url']) && $field['url'] == $oldUrl) {
                                    $field['file_id'] = $newFile->get('id');
                                    $field['url']     = $newUrl;
                                }
                            }
                        }
                    }

                    $summary       = $contentBlocks->summarizeContent($content);
                    $parsedContent = $contentBlocks->generateHtml($content);

                    $resource->setProperties(array(
                        'content'     => json_encode($content),
                        'linear'      => $summary['linear'],
                        'fieldcounts' => $summary['fieldcounts']
                    ), 'contentblocks', true);
                    $resource->setContent($parsedContent);
                    $resource->save();
                }
            }
        }

        //save File version
        $data                = $newFile->toArray();
        $version             = $this->createVersionNumber($newFileId);
        $data['version']     = $version;
        $fileInformation     = pathinfo($data['path']);
        $data['unique_name'] = $fileInformation['filename'] . '.' . $fileInformation['extension'];
        $data['size']        = $data['file_size'];

        $this->saveFileVersion($newFileId, $data, 'replace', $fileId);

        // Replace old file id with new file id
        $this->mediaManager->modx->updateCollection('MediamanagerFilesContent',
            array('mediamanager_files_id' => $newFileId),
            array('mediamanager_files_id' => $fileId)
        );

        // Archive old file
        $this->archiveFiles($fileId);

        return [
            'message' => $this->mediaManager->modx->lexicon('mediamanager.files.archive_and_replace.success')
        ];
    }

    /**
     * Unarchive files.
     *
     * @param array|int $selectedFiles
     * @return array
     */
    public function unArchiveFiles($selectedFiles)
    {
        $response = [
            'status'        => self::STATUS_SUCCESS,
            'message'       => '',
            'archivedFiles' => []
        ];

        $fileIds = [];
        if (!is_array($selectedFiles)) {
            $fileIds[] = $selectedFiles;
        } else {
            foreach ($selectedFiles as $file) {
                $fileIds[] = $file['id'];
            }
        }

        // Check if files are linked to a resource
        $q = $this->mediaManager->modx->newQuery('MediamanagerFilesContent');
        $q->select('
            MediamanagerFilesContent.mediamanager_files_id AS id,
            Files.name AS name
        ');
        $q->innerJoin('MediamanagerFiles', 'Files');
        $q->where([
            'MediamanagerFilesContent.mediamanager_files_id:IN' => $fileIds
        ]);
        $q->groupby('MediamanagerFilesContent.mediamanager_files_id');
        $q->prepare();

        $query   = $this->mediaManager->modx->query($q->toSQL());
        $results = $query->fetchAll(PDO::FETCH_OBJ);

        // Send error message
        if (!empty($results)) {
            $message = '';

            foreach ($results as $result) {
                $message .= $this->mediaManager->modx->lexicon('mediamanager.files.error.file_linked', ['file' => $result->name]) . '<br />';
            }

            $response['status']  = self::STATUS_ERROR;
            $response['message'] = $this->alertMessageHtml($message, 'danger');
            return $response;
        }

        // Unarchive files
        foreach ($fileIds as $key => $id) {
            $file = $this->mediaManager->modx->getObject('MediamanagerFiles', $id);

            if (!$this->createUploadDirectory($file->get('media_sources_id'))) {
                $response['status']  = self::STATUS_ERROR;
                $response['message'] = $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.error.create_directory'), 'danger');
                return $response;
            }

            $old = $file->get('archive_path');
            $new = $file->get('path');

            if (!$this->storage->fileExists($old)) {
                $response['status'] = self::STATUS_ERROR;
                $response['message'] .= $this->mediaManager->modx->lexicon('mediamanager.files.error.file_unarchive', array('id' => $id)) . '<br />';
                continue;
            }

            // Restored files are public again
            if (!$this->storage->move($old, $new, false)) {
                $response['status'] = self::STATUS_ERROR;
                $response['message'] .= $this->mediaManager->modx->lexicon('mediamanager.files.error.file_unarchive', array('id' => $id)) . '<br />';
                continue;
            }

            $file->set('is_archived', false);
            $file->set('archive_date', '');
            $file->set('archive_path', '');
            $file->save();

            $response['archivedFiles'][] = $key;
        }

        if (!empty($response['message'])) {
            $response['message'] = $this->alertMessageHtml($response['message'], 'danger');
        }

        return $response;
    }

    /**
     * Share files.
     *
     * @param array|int $selectedFiles
     * @param bool $isDownload
     *
     * @return array
     */
    public function shareFiles($selectedFiles, $isDownload = false)
    {
        $response = [
            'status'  => self::STATUS_SUCCESS,
            'message' => ''
        ];

        $fileIds = [];

        if (!is_array($selectedFiles)) {
            $fileIds[] = $selectedFiles;
        } else {
            foreach ($selectedFiles as $file) {
                $fileIds[] = $file['id'];
            }
        }

        // Create download directory, in the local cache
        if (!$this->setUploadPaths() || !$this->createLocalDirectory($this->storage->cachePath($this->downloadDirectory))) {
            $response['status']  = self::STATUS_ERROR;
            $response['message'] = $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.error.create_directory'), 'danger');
            return $response;
        }

        // Check if files are linked to a resource
        $files = $this->mediaManager->modx->getIterator('MediamanagerFiles', array(
            'id:IN' => $fileIds
        ));

        // Create zip
        do {
            $zipName = sha1(time()) . uniqid('-') . '.zip';
        } while (file_exists($this->storage->cachePath($this->downloadDirectory . $zipName)));

        $zip         = new ZipArchive();
        $zipLocation = $this->storage->cachePath($this->downloadDirectory . $zipName);
        $zipUrl      = $this->absoluteUrl($this->storage->cacheUrl($this->downloadDirectory . $zipName));

        $zipFile = $zip->open($zipLocation, ZipArchive::CREATE);
        if ($zipFile !== true) {
            $response['status']  = self::STATUS_ERROR;
            $response['message'] = $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.error.create_zip'), 'danger');
            return $response;
        }

        // Files are read when the zip is closed, so keep local copies until then
        $localCopies = [];
        foreach ($files as $file) {
            $storage = $this->mediaManager->sources->getStorage($file->get('media_sources_id'));
            $local   = $storage ? $storage->localCopy($file->get('path')) : false;

            if ($local) {
                $zip->addFile($local, $file->get('path'));
                $localCopies[] = [$storage, $local];
            }
        }

        $zip->close();

        foreach ($localCopies as $localCopy) {
            $localCopy[0]->releaseLocalCopy($localCopy[1]);
        }

        // Check if zip is created
        if (!file_exists($zipLocation)) {
            $response['status']  = self::STATUS_ERROR;
            $response['message'] = $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.error.create_zip'), 'danger');
            return $response;
        }

        if ($isDownload) {
            // Return download link
            $response['message'] = $zipUrl;
            return $response;
        }

        // Add download to database
        $expirationDate = time() + ((3600 * 24) * self::DOWNLOAD_EXPIRATION);

        $download = $this->mediaManager->modx->newObject('MediamanagerDownloads');
        $download->set('expires_on', $expirationDate);
        $download->set('path', $zipUrl);
        $download->set('hash', md5($zipUrl));
        $download->save();

        // Return download link
        $response['message'] = $this->mediaManager->modx->lexicon('mediamanager.files.share_download', [
            'link'       => '<input class="form-control" value="' . $zipUrl . '">',
            'expiration' => self::DOWNLOAD_EXPIRATION
        ]);

        return $response;
    }

    /**
     * Download files.
     *
     * @param array $files
     * @return array
     */
    public function downloadFiles($files)
    {
        return $this->shareFiles($files, true);
    }

    /**
     * Crop file.
     *
     * @param int $fileId
     * @param string $cropData
     * @param bool $isNewImage
     *
     * @return array
     */
    public function cropFile($fileId, $cropData, $isNewImage = false)
    {
        $response = [
            'status'  => self::STATUS_SUCCESS,
            'message' => ''
        ];

        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $cropData));

        $file = $this->mediaManager->modx->getObject('MediamanagerFiles', array('id' => $fileId));

        if (!$file) {
            $response['status'] = self::STATUS_ERROR;
            $response['message'] = $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.error.file_not_found'), 'danger');
            return $response;
        }

        if ($isNewImage) {
            // Save as new file
            return $this->duplicateFile($file, $imageData);
        }
        if (!$this->createUploadDirectory($file->get('media_sources_id'))) {
            $response['status']  = self::STATUS_ERROR;
            $response['message'] = $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.error.create_directory'), 'danger');
            return $response;
        }

        $fileKey = $this->removeSlashes($file->get('path'));

        // Write the cropped image locally first, to read its details
        $localPath   = tempnam(sys_get_temp_dir(), 'mediamanager-');
        $fileCreated = $localPath !== false ? file_put_contents($localPath, $imageData) : false;

        // Replace current file
        if ($fileCreated === false || !$this->storage->write($fileKey, $imageData)) {
            if ($localPath !== false) {
                unlink($localPath);
            }

            $response['status'] = self::STATUS_ERROR;
            $response['message'] = $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.error.image_not_saved'), 'danger');
            return $response;
        }

        $hash          = $this->getFileHashByPath($localPath);
        $image         = getimagesize($localPath);
        $size          = filesize($localPath);
        $versionNumber = $this->createVersionNumber($file->get('id'));

        $this->createPreviews($localPath, $fileKey, $file->get('file_type'), $hash);
        unlink($localPath);

        $file->set('file_dimensions', $image[0] . 'x' . $image[1]);
        $file->set('file_hash', $hash);
        $file->set('file_size', $size);
        $file->set('version', $versionNumber);

        $data                = $file->toArray();
        $fileInformation     = pathinfo($data['path']);
        $data['version']     = $versionNumber;
        $data['unique_name'] = $fileInformation['filename'] . '.' . $fileInformation['extension'];

        // Save file version.
        if ($this->saveFileVersion($file->get('id'), $data, 'crop') && $file->save()) {
            $response['message'] = $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.success.image_saved'), 'success');

            return $response;
        }

        $response['status'] = self::STATUS_ERROR;
        $response['message'] = $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.error.image_not_saved'), 'danger');

        return $response;
    }

    /**
     * Duplicate file.
     *
     * @param object $file
     * @param string $imageData
     *
     * @return array
     */
    public function duplicateFile($file, $imageData)
    {
        $originalFile = $file;
        $file         = $file->toArray();
        $data         = [];

        // Create upload directory
        if (!$this->createUploadDirectory($file['media_sources_id'])) {
            return [
                'status'  => self::STATUS_ERROR,
                'message' => $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.error.create_directory'), 'danger')
            ];
        }

        // Add unique id to file name if needed
        $fileName = explode('.', $file['name']); // @TODO: Need better solution (what if a filename contains multiple dots?)
        $fileName = $this->createUniqueFile($this->uploadDirectoryMonth, $fileName[0], $file['file_type']);

        $destinationPath = $this->uploadDirectoryMonth . $fileName;

        // Write the image locally first, to read its details
        $localPath   = tempnam(sys_get_temp_dir(), 'mediamanager-');
        $fileCreated = $localPath !== false ? file_put_contents($localPath, $imageData) : false;

        // Create new file
        if ($fileCreated === false || !$this->storage->write($destinationPath, $imageData)) {
            if ($localPath !== false) {
                unlink($localPath);
            }

            return [
                'status'  => self::STATUS_ERROR,
                'message' => $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.error.file_upload', array('file' => $file['name'])), 'danger')
            ];
        }

        $file['size']            = filesize($localPath);
        $file['hash']            = $this->getFileHashByPath($localPath);
        $file['extension']       = $file['file_type'];
        $file['unique_name']     = $fileName;
        $file['file_dimensions'] = $this->getImageDimensions($localPath, $file['file_type']);

        $this->createPreviews($localPath, $destinationPath, $file['file_type'], $file['hash']);
        unlink($localPath);

        // Get file categories
        $categories = $this->mediaManager->modx->getIterator('MediamanagerFilesCategories', array('mediamanager_files_id' => $file['id']));
        foreach ($categories as $category) {
            $data['categories'][] = $category->get('mediamanager_categories_id');
        }

        // Get file tags
        $tags = $this->mediaManager->modx->getIterator('MediamanagerFilesTags', array('mediamanager_files_id' => $file['id']));
        foreach ($tags as $tag) {
            $data['tags'][] = $tag->get('mediamanager_tags_id');
        }

        // Get file meta
        if ($meta = $originalFile->getMany('Meta')) {
            foreach ($meta as $item) {
                $data['meta'][$item->get('meta_key')] = $item->get('meta_value');
            }
        }

        // Set source
        $data['source']     = $file['media_sources_id'];
        $file['version']    = $this->createVersionNumber();

        // Add file to database
        $fileId         = $this->insertFile($file, $data);
        // we update the path here so that "version" file uses the newly created image and not the source duplicated
        $file['path'] = $destinationPath;
        $versionCreated = $this->saveFileVersion($fileId, $file, 'create');
        if (!$fileId || !$versionCreated) {
            // Remove file from server if saving failed
            $this->removeFile($file);

            return [
                'status'  => self::STATUS_ERROR,
                'message' => $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.error.file_save', array('file' => $fileName)), 'danger')
            ];
        }

        $this->addFileRelation($file['id'], $fileId);

        if ($license = $originalFile->getLicense()) {
            $licenseRelation = $this->mediaManager->modx->newObject('MediamanagerFilesLicenseFile', [
                'mediamanager_files_id' => $fileId,
                'license_id'            => $license->get('id')
            ]);

            $licenseRelation->save();
        }

        return [
            'status'  => self::STATUS_SUCCESS,
            'message' => $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.success.file_upload', array('file' => $fileName)), 'success'),
            'id'      => $fileId
        ];
    }

    /**
     * Copy file to source.
     *
     * @param int $fileId
     * @param int $sourceId
     *
     * @return array
     */
    public function copyToSource($fileId, $sourceId = 0)
    {
        $file = $this->mediaManager->modx->getObject('MediamanagerFiles', array('id' => $fileId));
        $file = $file->toArray();
        $data = [];

        if ($sourceId === 0) {
            $sourceId = $this->mediaManager->sources->getUserSource();
        }

        $sourceStorage = $this->mediaManager->sources->getStorage($file['media_sources_id']);

        // Create upload directory
        if (!$sourceStorage || !$this->createUploadDirectory($sourceId)) {
            return [
                'status'  => self::STATUS_ERROR,
                'message' => $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.error.create_directory'), 'danger')
            ];
        }

        // Add unique id to file name if needed
        $fileName = explode('.', $file['name']);
        $fileName = $this->createUniqueFile($this->uploadDirectoryMonth, $fileName[0], $file['file_type']);

        // Copy file, which may be between different storages
        $localPath   = $sourceStorage->localCopy($file['path']);
        $fileCreated = $localPath !== false && $this->storage->writeFile($this->uploadDirectoryMonth . $fileName, $localPath);

        if ($fileCreated) {
            $this->createPreviews($localPath, $this->uploadDirectoryMonth . $fileName, $file['file_type'], $file['file_hash']);
        }

        $sourceStorage->releaseLocalCopy($localPath);

        if ($fileCreated === false) {
            return [
                'status'  => self::STATUS_ERROR,
                'message' => $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.error.file_copy', array('file' => $file['name'])), 'danger')
            ];
        }

        $file['source']      = $sourceId;
        $file['extension']   = $file['file_type'];
        $file['size']        = $file['file_size'];
        $file['hash']        = $file['file_hash'];
        $file['unique_name'] = $fileName;

        // Get file categories
        $categories = $this->mediaManager->modx->getIterator('MediamanagerFilesCategories', array('mediamanager_files_id' => $file['id']));

        foreach ($categories as $category) {
            $data['categories'][] = $category->get('mediamanager_categories_id');
        }

        // Get file tags
        $tags = $this->mediaManager->modx->getIterator('MediamanagerFilesTags', array('mediamanager_files_id' => $file['id']));

        foreach ($tags as $tag) {
            $data['tags'][] = $tag->get('mediamanager_tags_id');
        }

        // Set source, to the source the file was copied to
        $data['source'] = $sourceId;

        // Add file to database
        if (!$this->insertFile($file, $data)) {
            // Remove file from server if saving failed
            $this->removeFile($file);

            return [
                'status'  => self::STATUS_ERROR,
                'message' => $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.error.file_save', array('file' => $fileName)), 'danger')
            ];
        }

        return [
            'status'  => self::STATUS_SUCCESS,
            'message' => $this->alertMessageHtml($this->mediaManager->modx->lexicon('mediamanager.files.success.file_copy', array('file' => $fileName)), 'success')
        ];
    }

    /**
     * Create file relation.
     *
     * @param int $id
     * @param int $relationId
     */
    private function addFileRelation($id, $relationId)
    {
        $file = $this->mediaManager->modx->newObject('MediamanagerFilesRelations');
        $file->set('mediamanager_files_id', $id);
        $file->set('mediamanager_files_id_relation', $relationId);
        $file->save();
    }

    /**
     * Add category to file.
     *
     * @param int $fileId
     * @param int $categoryId
     *
     * @return array
     */
    public function addCategory($fileId, $categoryId)
    {
        $category = $this->mediaManager->modx->newObject('MediamanagerFilesCategories');
        $category->set('mediamanager_files_id', $fileId);
        $category->set('mediamanager_categories_id', $categoryId);
        $category->save();

        return [];
    }

    /**
     * Remove category from file.
     *
     * @param int $fileId
     * @param int $categoryId
     *
     * @return array
     */
    public function removeCategory($fileId, $categoryId)
    {
        $this->mediaManager->modx->removeObject('MediamanagerFilesCategories', array(
            'mediamanager_files_id' => $fileId,
            'mediamanager_categories_id' => $categoryId
        ));

        return [];
    }

    /**
     * Add tag to file.
     *
     * @param int $fileId
     * @param int $tagId
     * @param mixed $name
     *
     * @return array
     */
    public function addTag($fileId, $tagId, $name = false)
    {
        // Create new tag
        if ($tagId === 0 && $name !== false) {
            $tag = $this->mediaManager->modx->getObject('MediamanagerTags', [
                'name:='           => $name,
                'media_sources_id' => $this->mediaManager->sources->getCurrentSource()
            ]);
            if (!$tag) {
                $newTag = $this->mediaManager->modx->newObject('MediamanagerTags');
                $newTag->set('media_sources_id', $this->mediaManager->sources->getCurrentSource());
                $newTag->set('name', $name);
                $newTag->save();

                $tagId = $newTag->get('id');
            }
        }

        if ($tagId === 0) {
            return [
                'status' => self::STATUS_ERROR
            ];
        }

        // Add tag to file
        $tag = $this->mediaManager->modx->newObject('MediamanagerFilesTags');
        $tag->set('mediamanager_files_id', $fileId);
        $tag->set('mediamanager_tags_id', $tagId);
        $tag->save();

        return [
            'status' => self::STATUS_SUCCESS,
            'html'   => '<option value="' . $tagId . '">' . $name . '</option>',
            'tagId'  => $tagId
        ];
    }

    /**
     * Remove tag from file.
     *
     * @param int $fileId
     * @param int $tagId
     *
     * @return array
     */
    public function removeTag($fileId, $tagId)
    {
        $this->mediaManager->modx->removeCollection('MediamanagerFilesTags', array(
            'mediamanager_files_id' => $fileId,
            'mediamanager_tags_id' => $tagId
        ));

        return [];
    }

    /**
     * Cleanup archive.
     * Automatically remove archived files.
     */
    private function cleanupArchive()
    {
        $time        = time();
        $cleanupTime = (int) $this->mediaManager->modx->getOption('mediamanager.cleanup_time');
        $cleanupMax  = (int) $this->mediaManager->modx->getOption('mediamanager.cleanup_max_age');

        $this->mediaManager->modx->setOption('mediamanager.cleanup_time', $time);

        // Stop if cleanup was less than 24 hours ago
        if ($time < ($cleanupTime + 86400)) {
            return false;
        }

        // Get files that need to be removed
        $files = $this->mediaManager->modx->getIterator('MediamanagerFiles', array(
            'is_archived'    => 1,
            'archive_date:<' => date('Y-m-d H:i:s', $time - ($cleanupMax * 86400))
        ));

        $fileIds = [];
        foreach ($files as $file) {
            $fileIds[] = $file->get('id');
        }

        if (empty($fileIds)) {
            return false;
        }

        // Remove files
        $this->mediaManager->modx->removeCollection('MediamanagerFiles', array(
            'is_archived'    => 1,
            'archive_date:<' => date('Y-m-d H:i:s', $time - ($cleanupMax * 86400))
        ));

        // Remove file categories
        $this->mediaManager->modx->removeCollection('MediamanagerFilesCategories', array(
            'mediamanager_files_id:IN' => $fileIds
        ));

        // Remove file tags
        $this->mediaManager->modx->removeCollection('MediamanagerFilesTags', array(
            'mediamanager_files_id:IN' => $fileIds
        ));

        // Remove file relations
        $this->mediaManager->modx->removeCollection('MediamanagerFilesRelations', array(
            'mediamanager_files_id:IN' => $fileIds,
            'OR:mediamanager_files_id_relation:IN' => $fileIds
        ));
    }

    /**
     * Create unique file name.
     *
     * @param string $directory Directory, relative to the storage root.
     * @param string $name
     * @param string $extension
     * @param string $uniqueId
     *
     * @return string
     */
    private function createUniqueFile($directory, $name, $extension, $uniqueId = '')
    {
        $file = $directory . $name . $uniqueId . '.' . $extension;
        if ($this->storage->fileExists($file)) {
            $uniqueId = uniqid('-');
            return $this->createUniqueFile($directory, $name, $extension, $uniqueId);
        }

        return $name . $uniqueId . '.' . $extension;
    }

    /**
     * Set upload paths and the storage of a media source.
     *
     * @param int $sourceId
     * @return bool
     */
    private function setUploadPaths($sourceId = 0)
    {
        // Set year and month
        $year  = date('Y');
        $month = date('m');

        if (!$sourceId) {
            $sourceId = $this->mediaManager->sources->getCurrentSource();
        }

        // Clear the previous source's storage, so a failure here can't leave it in use
        $this->storage = null;

        // Get media source
        $mediaSource = $this->mediaManager->sources->getSource($sourceId);

        if (!$mediaSource) {
            return false;
        }

        $this->storage = $this->mediaManager->sources->getStorage($sourceId);

        if (!$this->storage) {
            return false;
        }

        // Upload directories, relative to the storage root
        $this->uploadDirectoryYear   = $year . '/';
        $this->uploadDirectoryMonth  = $this->uploadDirectoryYear . $month . '/';

        // Archive path
        $this->archiveDirectory      = self::ARCHIVE_DIRECTORY . '/';

        // Download path, relative to the local cache root
        $this->downloadDirectory     = self::DOWNLOAD_DIRECTORY . '/';

        // Version path
        $this->versionDirectory      = self::VERSION_DIRECTORY . '/';

        $this->licenseDirectory      = self::LICENSE_DIRECTORY . '/';

        return true;
    }

    /**
     * Create upload directory if not exists.
     *
     * @param int $sourceId
     * @return bool
     */
    private function createUploadDirectory($sourceId = 0)
    {
        if (!$this->setUploadPaths($sourceId)) {
            return false;
        }

        foreach ([$this->uploadDirectoryMonth, $this->archiveDirectory, $this->versionDirectory, $this->licenseDirectory] as $directory) {
            if (!$this->storage->createDirectory($directory)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a local directory, such as a cache directory.
     *
     * @param string $directoryPath
     *
     * @return bool
     */
    private function createLocalDirectory($directoryPath)
    {
        return is_dir($directoryPath) || mkdir($directoryPath, 0777, true);
    }

    /**
     * Upload file.
     *
     * @param array $file
     *
     * @return bool
     */
    private function uploadFile($file)
    {
        return $this->storage->writeFile($this->uploadDirectoryMonth . $file['unique_name'], $file['tmp_name']);
    }

    /**
     * Upload license. License files are stored privately.
     *
     * @param array $file
     *
     * @return bool
     */
    private function uploadLicenseFile($file)
    {
        return $this->storage->writeFile($this->licenseDirectory . $this->uploadDirectoryMonth . $file['unique_name'], $file['tmp_name'], true);
    }

    /**
     * Remove license file.
     *
     * @param MediamanagerFilesLicenseFile $license
     * @param int $sourceId Media source of the file the license belonged to.
     * @return bool
     */
    public function removeLicenseFile($license, $sourceId = 0)
    {
        if (!$this->setUploadPaths($sourceId)) {
            return false;
        }

        return $this->storage->delete($this->licenseDirectory . $license->get('license_path'));
    }

    /**
     * Copy a stored file to a version file. Version files are stored privately.
     *
     * @param string $sourceKey
     * @param string $versionKey
     *
     * @return bool
     */
    private function uploadVersionFile($sourceKey, $versionKey)
    {
        return $this->storage->fileExists($sourceKey) && $this->storage->copy($sourceKey, $versionKey, true);
    }

    /**
     * Compress image.
     *
     * @param string $file
     * @return bool
     */
    private function tinify($file)
    {
        if (!$this->tinifyEnabled) return false;

        try {
            // Set and validate tinify api key
            \Tinify\setKey($this->tinifyApiKey);
            \Tinify\validate();

            // Check if tinify limit is reached
            if (\Tinify\compressionCount() === $this->tinifyLimit) return false;

            // Compress file
            $sourceData = file_get_contents($file);
            $resultData = \Tinify\fromBuffer($sourceData)->toBuffer();
            file_put_contents($file, $resultData);
        } catch(\Tinify\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Remove file.
     *
     * @param string $file
     * @return bool
     */
    private function removeFile($file)
    {
        return $this->storage->delete($this->uploadDirectoryMonth . $file['unique_name']);
    }

    /**
     * Sanitize file name.
     * Only allow a-z, 0-9, _ and -
     *
     * @param string $fileName
     * @return string
     */
    public function sanitizeFileName($fileName)
    {
        $find = array(
            ' '
        );

        $replace = array(
            '-'
        );

        $fileName = strtolower($fileName);
        $fileName = str_replace($find, $replace, $fileName);
        $fileName = preg_replace('/[^a-z0-9_.-]+/', '', $fileName);

        return $fileName;
    }

    /**
     * Get file hash by path.
     *
     * @param string $filePath
     * @return string
     */
    public function getFileHashByPath($filePath)
    {
        return md5_file($filePath);
    }

    /**
     * Check if file hash exists.
     *
     * @param string $fileHash
     * @return mixed Return file id or false if no file found.
     */
    public function fileHashExists($fileHash)
    {
        $file = $this->mediaManager->modx->getObject('MediamanagerFiles',
            array(
                'file_hash' => $fileHash,
                'media_sources_id' => $this->mediaManager->sources->getCurrentSource()
            )
        );

        if ($file === null) {
            return false;
        }

        return $file->get('id');
    }

    /**
     * Add slashes to string.
     *
     * @param string $string
     * @return string
     */
    public function addSlashes($string)
    {
        return DIRECTORY_SEPARATOR . trim($string, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Add trailing slash to string.
     *
     * @param string $string
     * @return string
     */
    public function addTrailingSlash($string)
    {
        return rtrim($string, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Remove slashes from string.
     *
     * @param string $string
     * @return string
     */
    public function removeSlashes($string)
    {
        return trim($string, DIRECTORY_SEPARATOR);
    }

    /**
     * Alert message.
     *
     * @param string $message
     * @param string $type Use the bootstrap class success, info, warning or danger
     *
     * @return string
     */
    public function alertMessageHtml($message, $type = 'danger')
    {
        return '<div class="alert alert-' . $type . '" role="alert">' . $message . '</div>';
    }

    /**
     * Format file size.
     *
     * @param int $bytes
     * @param int $precision
     *
     * @return string
     */
    public function formatFileSize($bytes, $precision = 0) {
        if ($bytes === 0) {
            return '0 B';
        }

        $unit = ["B", "KB", "MB", "GB"];
        $exp  = floor(log($bytes, 1024)) | 0;
        $size = @round((float) $bytes / (pow(1024, $exp)), $precision);

        return ($size ? $size : 0) . ' ' . $unit[$exp];
    }
}
