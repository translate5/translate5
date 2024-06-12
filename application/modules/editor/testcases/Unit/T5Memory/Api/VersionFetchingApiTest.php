<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Unit\T5Memory\Api;

use MittagQI\Translate5\T5Memory\Api\VersionFetchingApi;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class VersionFetchingApiTest extends TestCase
{
    public function testVersion(): void
    {
        $bodyMock = $this->createMock(StreamInterface::class);
        $bodyMock->method('getContents')->willReturn('{"Version":"1.0.0"}');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($bodyMock);

        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willReturn($response);

        $api = new VersionFetchingApi($client);
        $version = $api->version('http://example.com');

        $this->assertSame('1.0.0', $version);
    }

    public function testReturnsFallbackVersion(): void
    {
        $bodyMock = $this->createMock(StreamInterface::class);
        $bodyMock->method('getContents')->willReturn('');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($bodyMock);

        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willReturn($response);

        $api = new VersionFetchingApi($client);
        $version = $api->version('http://example.com');

        $this->assertSame('0.4', $version);
    }

    public function testThrowsException(): void
    {
        $bodyMock = $this->createMock(StreamInterface::class);
        $bodyMock
            ->method('getContents')
            ->willThrowException($this->createMock(ClientExceptionInterface::class));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($bodyMock);

        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willReturn($response);

        $api = new VersionFetchingApi($client);

        self::expectException(\Throwable::class);
        $api->version('http://example.com', false);
    }
}
