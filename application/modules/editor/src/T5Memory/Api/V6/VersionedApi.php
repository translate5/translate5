<?php

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory\Api\V6;

use Http\Client\Exception\HttpException;
use MittagQI\Translate5\T5Memory\Api\AbstractVersionedApi;
use MittagQI\Translate5\T5Memory\Api\Exception\InvalidResponseStructureException;
use MittagQI\Translate5\T5Memory\Api\V6\Request\DownloadTmRequest;
use MittagQI\Translate5\T5Memory\Api\V6\Request\DownloadTmxChunkRequest;
use MittagQI\Translate5\T5Memory\Api\V6\Response\DownloadTmResponse;
use MittagQI\Translate5\T5Memory\Api\V6\Response\DownloadTmxChunkResponse;
use PharIo\Version\VersionConstraint;
use PharIo\Version\VersionConstraintParser;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\StreamInterface;

class VersionedApi extends AbstractVersionedApi
{
    public const VERSION = '^0.6';

    public function __construct(
        private ClientInterface $client
    ) {
    }

    /**
     * Stream has whole TM.
     *
     * @throws ClientExceptionInterface
     * @throws HttpException
     */
    public function downloadTm(string $baseUrl, string $tmName): StreamInterface
    {
        $request = new DownloadTmRequest($baseUrl, $tmName);
        $response = $this->client->sendRequest($request);

        $this->throwExceptionOnNotSuccessfulResponse($response, $request);

        return DownloadTmResponse::fromResponse($response)->tm;
    }

    /**
     * Stream has the XML chunk of the TMX.
     *
     * @return iterable<StreamInterface>
     *
     * @throws ClientExceptionInterface
     * @throws InvalidResponseStructureException
     * @throws HttpException
     */
    public function downloadTmx(string $baseUrl, string $tmName, int $chunkSize): iterable
    {
        $chunk = $this->fetchChunk($baseUrl, $tmName, $chunkSize);

        yield $chunk->chunk;

        while ($chunk->nextInternalKey !== null) {
            $chunk = $this->fetchChunk($baseUrl, $tmName, $chunkSize, $chunk->nextInternalKey);

            yield $chunk->chunk;
        }
    }

    /**
     * @throws ClientExceptionInterface
     * @throws InvalidResponseStructureException
     * @throws HttpException
     */
    private function fetchChunk(
        string $baseUrl,
        string $tmName,
        int $chunkSize,
        ?string $startFromInternalKey = null,
    ): DownloadTmxChunkResponse {
        $request = new DownloadTmxChunkRequest($baseUrl, $tmName, $chunkSize, $startFromInternalKey);
        $response = $this->client->sendRequest($request);

        $this->throwExceptionOnNotSuccessfulResponse($response, $request);

        return DownloadTmxChunkResponse::fromResponse($response);
    }

    protected static function supportedVersion(): VersionConstraint
    {
        return (new VersionConstraintParser())->parse(self::VERSION);
    }
}
