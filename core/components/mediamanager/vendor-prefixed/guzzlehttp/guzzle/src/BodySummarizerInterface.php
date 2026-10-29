<?php

declare(strict_types=1);

namespace Sterc\MediaManager\Vendor\GuzzleHttp;

use Sterc\MediaManager\Vendor\Psr\Http\Message\MessageInterface;

interface BodySummarizerInterface
{
    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message): ?string;
}
