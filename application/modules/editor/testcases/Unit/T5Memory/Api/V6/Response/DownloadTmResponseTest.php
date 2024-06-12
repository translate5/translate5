<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Unit\T5Memory\Api\V6\Response;

use MittagQI\Translate5\T5Memory\Api\V6\Response\DownloadTmResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class DownloadTmResponseTest extends TestCase
{
    public function testFromResponse(): void
    {
        $bodyMock = $this->createMock(StreamInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($bodyMock);

        $resourcesResponse = DownloadTmResponse::fromResponse($response);

        self::assertInstanceOf(StreamInterface::class, $resourcesResponse->tm);
    }
}
