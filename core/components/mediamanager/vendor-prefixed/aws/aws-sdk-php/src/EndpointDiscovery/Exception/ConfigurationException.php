<?php
namespace Sterc\MediaManager\Vendor\Aws\EndpointDiscovery\Exception;

use Sterc\MediaManager\Vendor\Aws\HasMonitoringEventsTrait;
use Sterc\MediaManager\Vendor\Aws\MonitoringEventsInterface;

/**
 * Represents an error interacting with configuration for endpoint discovery
 */
class ConfigurationException extends \RuntimeException implements
    MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
