<?php

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory\Api;

use MittagQI\Translate5\T5Memory\Api\Contract\HasVersion;
use MittagQI\Translate5\T5Memory\Api\Contract\ResponseException;
use MittagQI\Translate5\T5Memory\Api\Request\ResourcesRequest;
use MittagQI\Translate5\T5Memory\Api\Response\ResourcesResponse;
use Psr\Http\Client\ClientInterface;

class VersionFetchingApi implements HasVersion
{
    public function __construct(private ClientInterface $client)
    {
    }

    public function version(string $baseUrl, bool $suppressExceptions = true): string
    {
        $response = $this->client->sendRequest(new ResourcesRequest($baseUrl));

        try {
            return ResourcesResponse::fromResponse($response)->version;
        } catch (ResponseException $exception) {
            if ($suppressExceptions) {
                return self::FALLBACK_VERSION;
            }

            throw $exception;
        }
    }
}