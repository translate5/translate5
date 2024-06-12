<?php

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory\Api\V5;

use Http\Client\Exception\HttpException;
use MittagQI\Translate5\T5Memory\Api\AbstractVersionedApi;
use MittagQI\Translate5\T5Memory\Api\Exception\InvalidResponseStructureException;
use MittagQI\Translate5\T5Memory\Api\V5\Request\DownloadTmRequest;
use MittagQI\Translate5\T5Memory\Api\V5\Request\DownloadTmxRequest;
use MittagQI\Translate5\T5Memory\Api\V5\Response\DownloadTmResponse;
use MittagQI\Translate5\T5Memory\Api\V5\Response\DownloadTmxResponse;
use PharIo\Version\VersionConstraint;
use PharIo\Version\VersionConstraintParser;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\StreamInterface;

class VersionedApi extends AbstractVersionedApi
{
    public const VERSION = '^0.4 || ^0.5';

    public function __construct(
        private ClientInterface $client
    ) {
    }

    /**
     * Stream has whole TMX.
     *
     * @throws ClientExceptionInterface
     * @throws InvalidResponseStructureException
     */
    public function getTmx(string $baseUrl, string $tmName): StreamInterface
    {
        $request = new DownloadTmxRequest($baseUrl, $tmName);
        $response = $this->client->sendRequest($request);

        $this->throwExceptionOnNotSuccessfulResponse($response, $request);

        return DownloadTmxResponse::fromResponse($response)->tmx;
    }

    /**
     * Stream has whole TM.
     *
     * @throws ClientExceptionInterface
     * @throws HttpException
     */
    public function getTm(string $baseUrl, string $tmName): StreamInterface
    {
        $request = new DownloadTmRequest($baseUrl, $tmName);
        $response = $this->client->sendRequest($request);

        $this->throwExceptionOnNotSuccessfulResponse($response, $request);

        return DownloadTmResponse::fromResponse($response)->tm;
    }

    protected static function supportedVersion(): VersionConstraint
    {
        return (new VersionConstraintParser())->parse(self::VERSION);
    }
}
