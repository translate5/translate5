<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Unit\T5Memory\Api\V5\Request;

use MittagQI\Translate5\T5Memory\Api\V5\Request\DownloadTmRequest;
use PHPUnit\Framework\TestCase;

class DownloadTmRequestTest extends TestCase
{
    public function testCreation(): void
    {
        $request = new DownloadTmRequest('http://example.com', 'tmName');

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('http://example.com/tmName/', (string) $request->getUri());
        $this->assertSame('application/zip', $request->getHeaderLine('Accept'));
    }
}
