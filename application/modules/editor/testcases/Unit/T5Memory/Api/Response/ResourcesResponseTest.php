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

namespace MittagQI\Translate5\Test\Unit\T5Memory\Api\Response;

use MittagQI\Translate5\T5Memory\Api\Contract\PoolAsyncClientInterface;
use MittagQI\Translate5\T5Memory\Api\Exception\CorruptResponseBodyException;
use MittagQI\Translate5\T5Memory\Api\Exception\InvalidJsonInResponseBodyException;
use MittagQI\Translate5\T5Memory\Api\Exception\InvalidResponseStructureException;
use MittagQI\Translate5\T5Memory\Api\Response\ResourcesResponse;
use MittagQI\Translate5\T5Memory\Api\SegmentLengthValidator;
use MittagQI\Translate5\T5Memory\Api\T5MemoryApi;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
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

        $api = new T5MemoryApi(
            $this->getClient($response),
            SegmentLengthValidator::create(),
        );

        self::expectException(InvalidResponseStructureException::class);
        $api->version('http://example.com');
    }

    public function testVersionThrowsCorruptResponseBodyException(): void
    {
        $bodyMock = $this->createMock(StreamInterface::class);
        $bodyMock->method('getContents')->willThrowException(new \RuntimeException());

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($bodyMock);

        $api = new T5MemoryApi(
            $this->getClient($response),
            SegmentLengthValidator::create(),
        );

        self::expectException(CorruptResponseBodyException::class);
        $api->version('http://example.com');
    }

    public function testVersionThrowsInvalidJsonInResponseBodyException(): void
    {
        $bodyMock = $this->createMock(StreamInterface::class);
        $bodyMock->method('getContents')->willReturn('oops');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($bodyMock);

        $api = new T5MemoryApi(
            $this->getClient($response),
            SegmentLengthValidator::create(),
        );

        self::expectException(InvalidJsonInResponseBodyException::class);
        $api->version('http://example.com');
    }

    private function getClient(ResponseInterface $response): PoolAsyncClientInterface & ClientInterface
    {
        $client = new class($response) implements ClientInterface, PoolAsyncClientInterface
        {
            public function __construct(
                private ResponseInterface $response
            ) {
            }

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                return $this->response;
            }

            public function poolAsync(array $requests, int $concurrency = 10, array $perRequestOptions = []): array
            {
                /** @phpstan-ignore-next-line */
                return [];
            }
        };

        return $client;
    }
}
