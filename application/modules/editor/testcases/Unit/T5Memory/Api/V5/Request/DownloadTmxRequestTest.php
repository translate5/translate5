<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Unit\T5Memory\Api\V5\Request;

use MittagQI\Translate5\T5Memory\Api\V5\Request\DownloadTmxRequest;
use PHPUnit\Framework\TestCase;

class DownloadTmxRequestTest extends TestCase
{
    public function testCreation(): void
    {
        $request = new DownloadTmxRequest('http://example.com', 'tmName');

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('http://example.com/tmName/', (string) $request->getUri());
        $this->assertSame('UTF-8', $request->getHeaderLine('Accept-charset'));
        $this->assertSame('application/xml', $request->getHeaderLine('Accept'));
    }
}
