<?php

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory\Api;

use MittagQI\Translate5\T5Memory\Api\Contract\HasVersionInterface;
use MittagQI\Translate5\T5Memory\Api\Contract\ResponseExceptionInterface;
use MittagQI\Translate5\T5Memory\Api\Request\ResourcesRequest;
use MittagQI\Translate5\T5Memory\Api\Response\ResourcesResponse;
use Psr\Http\Client\ClientInterface;

class VersionFetchingApi implements HasVersionInterface
{
    /**
     * @var string[]
     */
    private array $versions = [];

    public function __construct(
        private ClientInterface $client,
    ) {
    }

    public function version(string $baseUrl, bool $suppressExceptions = true): string
    {
        if (isset($this->versions[$baseUrl])) {
            return $this->versions[$baseUrl];
        }

        $response = $this->client->sendRequest(new ResourcesRequest($baseUrl));

        try {
            $this->versions[$baseUrl] = ResourcesResponse::fromResponse($response)->version;

            return $this->versions[$baseUrl];
        } catch (ResponseExceptionInterface $exception) {
            if ($suppressExceptions) {
                return self::FALLBACK_VERSION;
            }

            throw $exception;
        }
    }
}
