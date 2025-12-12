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

use MittagQI\Translate5\Integration\SegmentUpdate\UpdateSegmentDTO;
use MittagQI\Translate5\T5Memory\Api\Contract\PoolAsyncClientInterface;
use MittagQI\Translate5\T5Memory\Api\Exception\SegmentErroneousException;
use MittagQI\Translate5\T5Memory\Api\Exception\SegmentTooLongException;
use MittagQI\Translate5\T5Memory\Api\SegmentLengthValidator;
use MittagQI\Translate5\T5Memory\Api\SegmentUpdateResultValidator;
use MittagQI\Translate5\T5Memory\Api\T5MemoryApi;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
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

        $api = new T5MemoryApi(
            $this->getClient($response),
            $this->createMock(SegmentLengthValidator::class),
            $this->createMock(SegmentUpdateResultValidator::class),
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

        $api = new T5MemoryApi(
            $this->getClient($response),
            $this->createMock(SegmentLengthValidator::class),
            $this->createMock(SegmentUpdateResultValidator::class),
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

        $api = new T5MemoryApi(
            $this->getClient($response),
            $this->createMock(SegmentLengthValidator::class),
            $this->createMock(SegmentUpdateResultValidator::class),
        );

        $stream = $api->downloadTm('http://example.com', 'tmName');

        self::assertInstanceOf(StreamInterface::class, $stream);
    }

    public function testUpdateSegmentSuccessful(): void
    {
        $api = new T5MemoryApi(
            $this->getClient($this->getUpdateResponse()),
            $this->createMock(SegmentLengthValidator::class),
            $this->createMock(SegmentUpdateResultValidator::class),
        );

        $dto = new UpdateSegmentDTO(
            source: 'Hello world',
            target: 'Hallo Welt',
            fileName: 'test.txt',
            timestamp: time(),
            userName: 'tester',
            context: 'test-context',
        );

        $updateResponse = $api->update(
            'http://example.com',
            'tmName',
            $dto,
            'en',
            'de',
            true,
        );

        self::assertTrue($updateResponse->successful());
    }

    public function testUpdateSegmentThrowsExceptionOnXmlError(): void
    {
        $segmentUpdateResultValidator = $this->createMock(SegmentUpdateResultValidator::class);
        $segmentUpdateResultValidator
            ->method('ensureSegmentValid')
            ->willThrowException(new SegmentErroneousException('Error in xml in source or target!'));

        $api = new T5MemoryApi(
            $this->getClient($this->getUpdateResponse('Error in xml in source or target!')),
            $this->createMock(SegmentLengthValidator::class),
            $segmentUpdateResultValidator,
        );

        $dto = new UpdateSegmentDTO(
            source: 'Hello world',
            target: 'Hallo <invalid>Welt',
            fileName: 'test.txt',
            timestamp: time(),
            userName: 'tester',
            context: 'test-context',
        );

        $this->expectException(SegmentErroneousException::class);
        $this->expectExceptionMessage('Error in xml in source or target!');

        $api->update(
            'http://example.com',
            'tmName',
            $dto,
            'en',
            'de',
            true,
        );
    }

    public function testUpdateSegmentThrowsExceptionOnLongSegment(): void
    {
        $segmentLengthValidator = $this->createMock(SegmentLengthValidator::class);
        $segmentLengthValidator
            ->method('validate')
            ->willThrowException(new SegmentTooLongException('Segment length exceeds limit.'));

        $api = new T5MemoryApi(
            $this->getClient($this->getUpdateResponse()),
            $segmentLengthValidator,
            $this->createMock(SegmentUpdateResultValidator::class),
        );

        $dto = new UpdateSegmentDTO(
            source: 'Hello world',
            target: 'Hallo Welt',
            fileName: 'test.txt',
            timestamp: time(),
            userName: 'tester',
            context: 'test-context',
        );

        $this->expectException(SegmentTooLongException::class);
        $this->expectExceptionMessage('Segment length exceeds limit.');

        $api->update(
            'http://example.com',
            'tmName',
            $dto,
            'en',
            'de',
            true,
        );
    }

    private function createStreamFromString(string $content): StreamInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn($content);
        $stream->method('getContents')->willReturn($content);

        return $stream;
    }

    private function getUpdateResponse(string $errorMsg = '', int $statusCode = 200): ResponseInterface
    {
        $responseBody = json_encode([
            'ErrorMsg' => $errorMsg,
        ]);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($this->createStreamFromString($responseBody));
        $response->method('getStatusCode')->willReturn($statusCode);

        return $response;
    }

    private function getClient(ResponseInterface $response): PoolAsyncClientInterface & ClientInterface
    {
        $client = new class($response) implements ClientInterface, PoolAsyncClientInterface {
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
