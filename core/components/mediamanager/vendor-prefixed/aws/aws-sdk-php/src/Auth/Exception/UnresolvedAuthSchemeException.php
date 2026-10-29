<?php

namespace Sterc\MediaManager\Vendor\Aws\Auth\Exception;

use Sterc\MediaManager\Vendor\Aws\HasMonitoringEventsTrait;
use Sterc\MediaManager\Vendor\Aws\MonitoringEventsInterface;

/**
 * Represents an error when attempting to resolve authentication.
 */
class UnresolvedAuthSchemeException extends \RuntimeException implements
    MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
