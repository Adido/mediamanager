<?php
namespace Sterc\MediaManager\Vendor\Aws\Api\Parser;

use Sterc\MediaManager\Vendor\Aws\Api\Service;
use Sterc\MediaManager\Vendor\Aws\Api\StructureShape;
use Sterc\MediaManager\Vendor\Aws\CommandInterface;
use Sterc\MediaManager\Vendor\Aws\ResultInterface;
use Sterc\MediaManager\Vendor\GuzzleHttp\Psr7\CachingStream;
use Sterc\MediaManager\Vendor\Psr\Http\Message\ResponseInterface;
use Sterc\MediaManager\Vendor\Psr\Http\Message\StreamInterface;

/**
 * @internal
 */
abstract class AbstractParser
{
    /** @var \Sterc\MediaManager\Vendor\Aws\Api\Service Representation of the service API*/
    protected $api;

    /** @var callable */
    protected $parser;

    /**
     * @param Service $api Service description.
     */
    public function __construct(Service $api)
    {
        $this->api = $api;
    }

    /**
     * @param CommandInterface  $command  Command that was executed.
     * @param ResponseInterface $response Response that was received.
     *
     * @return ResultInterface
     */
    abstract public function __invoke(
        CommandInterface $command,
        ResponseInterface $response
    );

    abstract public function parseMemberFromStream(
        StreamInterface $stream,
        StructureShape $member,
        $response
    );

    public static function getBodyContents(ResponseInterface $response): string
    {
        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        return $body->getContents();
    }

    public static function getResponseWithCachingStream(
        ResponseInterface $response
    ): ResponseInterface
    {
        if (!$response->getBody()->isSeekable()) {
            return $response->withBody(
                new CachingStream($response->getBody())
            );
        }

        return $response;
    }
}
