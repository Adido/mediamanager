<?php

namespace Sterc\MediaManager\Vendor\Aws;

use Sterc\MediaManager\Vendor\Psr\Http\Message\ResponseInterface;

interface ResponseContainerInterface
{
    /**
     * Get the received HTTP response if any.
     *
     * @return ResponseInterface|null
     */
    public function getResponse();
}