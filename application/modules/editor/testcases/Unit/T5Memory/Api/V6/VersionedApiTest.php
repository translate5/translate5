<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Unit\T5Memory\Api\V6;

use MittagQI\Translate5\T5Memory\Api\V6\VersionedApi;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class VersionedApiTest extends TestCase
{
    public function testDownloadTmx(): void
    {
        $bodyMock = $this->createMock(StreamInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($bodyMock);
        $response->method('getStatusCode')->willReturn(200);

        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willReturn($response);

        $api = new VersionedApi($client);

        $iterator = $api->downloadTmx('http://example.com', 'tmName', 20);

        $chunks = iterator_to_array($iterator);

        self::assertCount(1, $chunks);
        self::assertInstanceOf(StreamInterface::class, current($chunks));
    }

    public function testDownloadTmxChunks(): void
    {
        $bodyMock = $this->createMock(StreamInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($bodyMock);
        $response->method('getStatusCode')->willReturn(200);

        $response->method('getHeader')->willReturnOnConsecutiveCalls(['11:1'], []);

        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willReturn($response);

        $api = new VersionedApi($client);

        $iterator = $api->downloadTmx('http://example.com', 'tmName', 20);

        $chunks = iterator_to_array($iterator);

        self::assertCount(2, $chunks);
        self::assertInstanceOf(StreamInterface::class, current($chunks));
    }

    public function testDownloadTm(): void
    {
        $bodyMock = $this->createMock(StreamInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($bodyMock);
        $response->method('getStatusCode')->willReturn(200);

        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willReturn($response);

        $api = new VersionedApi($client);

        $stream = $api->downloadTm('http://example.com', 'tmName');

        self::assertInstanceOf(StreamInterface::class, $stream);
    }
}
