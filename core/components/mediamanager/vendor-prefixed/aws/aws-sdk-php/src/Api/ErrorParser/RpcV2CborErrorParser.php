<?php
namespace Sterc\MediaManager\Vendor\Aws\Api\ErrorParser;

use Sterc\MediaManager\Vendor\Aws\Api\Cbor\CborDecoder;
use Sterc\MediaManager\Vendor\Aws\Api\Parser\RpcV2ParserTrait;
use Sterc\MediaManager\Vendor\Aws\Api\Service;
use Sterc\MediaManager\Vendor\Aws\Api\StructureShape;
use Sterc\MediaManager\Vendor\Psr\Http\Message\ResponseInterface;
use Sterc\MediaManager\Vendor\Psr\Http\Message\StreamInterface;

/**
 * Parses errors according to Smithy RPC V2 CBOR protocol standards.
 *
 * https://smithy.io/2.0/additional-specs/protocols/smithy-rpc-v2.html
 *
 * @internal
 */
final class RpcV2CborErrorParser extends AbstractRpcV2ErrorParser
{
    /** @var CborDecoder */
    private CborDecoder $decoder;

    use RpcV2ParserTrait;

    /**
     * @param Service|null $api
     */
    public function __construct(?Service $api = null)
    {
        $this->decoder = new CborDecoder();
        parent::__construct($api);
    }

    /**
     * @param ResponseInterface $response
     * @param StructureShape $member
     *
     * @return array
     * @throws \Exception
     */
    protected function payload(
        ResponseInterface $response,
        StructureShape $member
    ): array
    {
        $body = $response->getBody();
        $cborBody = $this->parseCbor($body, $response);

        return $this->resolveOutputShape($member, $cborBody);
    }

    /**
     * @param StreamInterface $body
     * @param ResponseInterface $response
     *
     * @return mixed
     */
    protected function parseBody(
        StreamInterface $body,
        ResponseInterface $response
    ): mixed
    {
        return $this->parseCbor($body, $response);
    }
}
