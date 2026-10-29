<?php
namespace Sterc\MediaManager\Vendor\Aws\S3\Exception;

use Sterc\MediaManager\Vendor\Aws\Exception\MultipartUploadException;
use Sterc\MediaManager\Vendor\Aws\Multipart\UploadState;

/**
 * Phase 3 PutObjectAnnotation partial failure. Carries per-name
 * succeeded/failed maps via {@see getSucceededAnnotations()} and
 * {@see getFailedAnnotations()}.
 */
class MultipartCopyAnnotationException extends MultipartUploadException
{
    /** @var string[] */
    private array $succeeded;
    /** @var array<string,S3Exception> */
    private array $failed;

    /**
     * @param UploadState               $state
     * @param array<string,S3Exception> $failed
     * @param string[]                  $succeeded
     */
    public function __construct(UploadState $state, array $failed, array $succeeded = [])
    {
        parent::__construct($state, $failed);

        $this->failed    = $failed;
        $this->succeeded = $succeeded;
    }

    /**
     * @return string[]
     */
    public function getSucceededAnnotations(): array
    {
        return $this->succeeded;
    }

    /**
     * @return array<string,S3Exception>
     */
    public function getFailedAnnotations(): array
    {
        return $this->failed;
    }
}
