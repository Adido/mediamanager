<?php
namespace Sterc\MediaManager\Vendor\Aws\Exception;

use Sterc\MediaManager\Vendor\Aws\HasMonitoringEventsTrait;
use Sterc\MediaManager\Vendor\Aws\MonitoringEventsInterface;

class UnresolvedSignatureException extends \RuntimeException implements
    MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
