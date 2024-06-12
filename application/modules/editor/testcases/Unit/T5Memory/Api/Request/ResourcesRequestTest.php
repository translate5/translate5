<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Unit\T5Memory\Api\Request;

use MittagQI\Translate5\T5Memory\Api\Request\ResourcesRequest;
use PHPUnit\Framework\TestCase;

class ResourcesRequestTest extends TestCase
{
    public function testCreation(): void
    {
        $baseUrl = 'http://example.com';
        $request = new ResourcesRequest($baseUrl);

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('http://example.com_service/resources', (string) $request->getUri());
        $this->assertSame('UTF-8', $request->getHeaderLine('Accept-charset'));
        $this->assertSame('application/json; charset=utf-8', $request->getHeaderLine('Accept'));
    }
}
