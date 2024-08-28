<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

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
