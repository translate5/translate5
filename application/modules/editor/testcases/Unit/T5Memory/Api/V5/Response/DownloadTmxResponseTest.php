<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Unit\T5Memory\Api\V5\Response;

use MittagQI\Translate5\T5Memory\Api\V5\Response\DownloadTmxResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class DownloadTmxResponseTest extends TestCase
{
    public function testFromResponse(): void
    {
        $bodyMock = $this->createMock(StreamInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($bodyMock);

        $resourcesResponse = DownloadTmxResponse::fromResponse($response);

        self::assertInstanceOf(StreamInterface::class, $resourcesResponse->tmx);
    }
}
