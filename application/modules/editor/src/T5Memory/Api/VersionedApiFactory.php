<?php

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory\Api;

use Psr\Http\Client\ClientInterface;

/**
 * @template T
 */
class VersionedApiFactory
{
    public function __construct(
        private ClientInterface $client,
    ) {
    }

    /**
     * @param class-string<T> $apiClass
     * @return T
     */
    public function get(string $apiClass)
    {
        return match ($apiClass) {
            V5\VersionedApi::class => new V5\VersionedApi($this->client),
            V6\VersionedApi::class => new V6\VersionedApi($this->client),

            default => throw new \InvalidArgumentException("Unknown API class: $apiClass"),
        };
    }
}
