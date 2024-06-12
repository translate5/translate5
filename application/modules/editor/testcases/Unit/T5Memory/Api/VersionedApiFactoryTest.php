<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Unit\T5Memory\Api;

use MittagQI\Translate5\T5Memory\Api;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

class VersionedApiFactoryTest extends TestCase
{
    private ClientInterface $client;

    protected function setUp(): void
    {
        // Create a mock for the ClientInterface
        $this->client = $this->createMock(ClientInterface::class);
    }

    public function testGetV5Api()
    {
        $factory = new Api\VersionedApiFactory($this->client);
        $api = $factory->get(Api\V5\VersionedApi::class);
        $this->assertInstanceOf(Api\V5\VersionedApi::class, $api);
    }

    public function testGetV6Api()
    {
        $factory = new Api\VersionedApiFactory($this->client);
        $api = $factory->get(Api\V6\VersionedApi::class);
        $this->assertInstanceOf(Api\V6\VersionedApi::class, $api);
    }

    public function testGetUnknownApi()
    {
        $this->expectException(\InvalidArgumentException::class);

        $factory = new Api\VersionedApiFactory($this->client);
        $factory->get(self::class);
    }
}
