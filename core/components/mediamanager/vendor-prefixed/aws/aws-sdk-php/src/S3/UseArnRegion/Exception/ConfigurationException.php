<?php
namespace Sterc\MediaManager\Vendor\Aws\S3\UseArnRegion\Exception;

use Sterc\MediaManager\Vendor\Aws\HasMonitoringEventsTrait;
use Sterc\MediaManager\Vendor\Aws\MonitoringEventsInterface;

/**
 * Represents an error interacting with configuration for S3's UseArnRegion
 */
class ConfigurationException extends \RuntimeException implements
    MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
