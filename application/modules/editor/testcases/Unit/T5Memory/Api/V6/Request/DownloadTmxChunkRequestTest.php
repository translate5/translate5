<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Unit\T5Memory\Api\V6\Request;

use MittagQI\Translate5\T5Memory\Api\V6\Request\DownloadTmxChunkRequest;
use PHPUnit\Framework\TestCase;

class DownloadTmxChunkRequestTest extends TestCase
{
    public function testCreation(): void
    {
        $request = new DownloadTmxChunkRequest('http://example.com', 'tmName');

        self::assertSame('GET', $request->getMethod());
        self::assertSame('http://example.com/tmName/download.tmx', (string) $request->getUri());
        self::assertSame('UTF-8', $request->getHeaderLine('Accept-charset'));
        self::assertSame('application/xml', $request->getHeaderLine('Accept'));
    }

    public function testCreationWithLimit(): void
    {
        $request = new DownloadTmxChunkRequest('http://example.com', 'tmName', 10);

        self::assertSame('GET', $request->getMethod());
        self::assertSame('http://example.com/tmName/download.tmx', (string) $request->getUri());
        self::assertSame('UTF-8', $request->getHeaderLine('Accept-charset'));
        self::assertSame('application/xml', $request->getHeaderLine('Accept'));
        self::assertSame('{"limit":10}', $request->getBody()->getContents());
    }

    public function testCreationWithOffset(): void
    {
        $request = new DownloadTmxChunkRequest('http://example.com', 'tmName', startFromInternalKey: '10:1');

        self::assertSame('GET', $request->getMethod());
        self::assertSame('http://example.com/tmName/download.tmx', (string) $request->getUri());
        self::assertSame('UTF-8', $request->getHeaderLine('Accept-charset'));
        self::assertSame('application/xml', $request->getHeaderLine('Accept'));
        self::assertSame('{"startFromInternalKey":"10:1"}', $request->getBody()->getContents());
    }

    public function testThrowInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new DownloadTmxChunkRequest('http://example.com', 'tmName', startFromInternalKey: '10-1');
    }
}
