<?php
namespace Sterc\MediaManager\Vendor\Aws\Api\Exception;

use Sterc\MediaManager\Vendor\Aws\HasMonitoringEventsTrait;
use Sterc\MediaManager\Vendor\Aws\MonitoringEventsInterface;

class RpcV2CborException extends \RuntimeException implements
    MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
