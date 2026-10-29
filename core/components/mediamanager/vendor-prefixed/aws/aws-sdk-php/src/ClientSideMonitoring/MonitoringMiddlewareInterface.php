<?php

namespace Sterc\MediaManager\Vendor\Aws\ClientSideMonitoring;

use Sterc\MediaManager\Vendor\Aws\CommandInterface;
use Sterc\MediaManager\Vendor\Aws\Exception\AwsException;
use Sterc\MediaManager\Vendor\Aws\ResultInterface;
use Sterc\MediaManager\Vendor\GuzzleHttp\Psr7\Request;
use Sterc\MediaManager\Vendor\Psr\Http\Message\RequestInterface;

/**
 * @internal
 */
interface MonitoringMiddlewareInterface
{

    /**
     * Data for event properties to be sent to the monitoring agent.
     *
     * @param RequestInterface $request
     * @return array
     */
    public static function getRequestData(RequestInterface $request);


    /**
     * Data for event properties to be sent to the monitoring agent.
     *
     * @param ResultInterface|AwsException|\Exception $klass
     * @return array
     */
    public static function getResponseData($klass);

    public function __invoke(CommandInterface $cmd, RequestInterface $request);
}