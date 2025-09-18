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

use MittagQI\Translate5\T5Memory\Api\SegmentLengthValidator;
use MittagQI\Translate5\T5Memory\Api\T5MemoryApi;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class T5MemoryApiTest extends TestCase
{
    public function testDownloadTmx(): void
    {
        $bodyMock = $this->createMock(StreamInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($bodyMock);
        $response->method('getStatusCode')->willReturn(200);

        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willReturn($response);

        $api = new T5MemoryApi(
            $client,
            SegmentLengthValidator::create(),
        );

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

        $api = new T5MemoryApi(
            $client,
            SegmentLengthValidator::create(),
        );

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

        $api = new T5MemoryApi(
            $client,
            SegmentLengthValidator::create(),
        );

        $stream = $api->downloadTm('http://example.com', 'tmName');

        self::assertInstanceOf(StreamInterface::class, $stream);
    }
}
