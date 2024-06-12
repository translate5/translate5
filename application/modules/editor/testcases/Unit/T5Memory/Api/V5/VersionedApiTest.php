<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Unit\T5Memory\Api\V5;

use MittagQI\Translate5\T5Memory\Api\V5\VersionedApi;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class VersionedApiTest extends TestCase
{
    public function testGetTmx(): void
    {
        $bodyMock = $this->createMock(StreamInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($bodyMock);
        $response->method('getStatusCode')->willReturn(200);

        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willReturn($response);

        $api = new VersionedApi($client);

        $stream = $api->getTmx('http://example.com', 'tmName');

        self::assertInstanceOf(StreamInterface::class, $stream);
    }

    public function testGetTm(): void
    {
        $bodyMock = $this->createMock(StreamInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($bodyMock);
        $response->method('getStatusCode')->willReturn(200);

        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willReturn($response);

        $api = new VersionedApi($client);

        $stream = $api->getTm('http://example.com', 'tmName');

        self::assertInstanceOf(StreamInterface::class, $stream);
    }
}
