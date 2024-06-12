<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Unit\T5Memory\Api\Response;

use MittagQI\Translate5\T5Memory\Api\Exception\CorruptResponseBodyException;
use MittagQI\Translate5\T5Memory\Api\Exception\InvalidJsonInResponseBodyException;
use MittagQI\Translate5\T5Memory\Api\Exception\InvalidResponseStructureException;
use MittagQI\Translate5\T5Memory\Api\Response\ResourcesResponse;
use MittagQI\Translate5\T5Memory\Api\VersionFetchingApi;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ResourcesResponseTest extends TestCase
{
    public function testFromResponse(): void
    {
        $bodyMock = $this->createMock(StreamInterface::class);
        $bodyMock->method('getContents')->willReturn('{"Version":"1.0.0"}');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($bodyMock);

        $resourcesResponse = ResourcesResponse::fromResponse($response);

        $this->assertSame('1.0.0', $resourcesResponse->version);
    }

    public function testVersionThrowsInvalidResponseStructureException(): void
    {
        $bodyMock = $this->createMock(StreamInterface::class);
        $bodyMock->method('getContents')->willReturn('{"trash":"prop"}');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($bodyMock);

        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willReturn($response);

        $api = new VersionFetchingApi($client);

        self::expectException(InvalidResponseStructureException::class);
        $api->version('http://example.com', false);
    }

    public function testVersionThrowsCorruptResponseBodyException(): void
    {
        $bodyMock = $this->createMock(StreamInterface::class);
        $bodyMock->method('getContents')->willThrowException(new \RuntimeException());

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($bodyMock);

        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willReturn($response);

        $api = new VersionFetchingApi($client);

        self::expectException(CorruptResponseBodyException::class);
        $api->version('http://example.com', false);
    }

    public function testVersionThrowsInvalidJsonInResponseBodyException(): void
    {
        $bodyMock = $this->createMock(StreamInterface::class);
        $bodyMock->method('getContents')->willReturn('oops');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($bodyMock);

        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willReturn($response);

        $api = new VersionFetchingApi($client);

        self::expectException(InvalidJsonInResponseBodyException::class);
        $api->version('http://example.com', false);
    }
}
