<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\Translate5\T5Memory\Api\Response\ImportStatusResponse;
use MittagQI\Translate5\T5Memory\Enum\ImportStatusEnum;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ImportStatusResponseTest extends TestCase
{
    public function provideProcessImportStatus(): array
    {
        return [
            'Not found' => [
                'status' => 'not found',
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => ImportStatusEnum::Error,
            ],
            'Available' => [
                'status' => 'available',
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => ImportStatusEnum::Terminated,
            ],
            'Available just loaded' => [
                'status' => 'open',
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => ImportStatusEnum::Terminated,
            ],
            'Waiting to be loaded' => [
                'status' => 'waiting for loading',
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => ImportStatusEnum::Terminated,
            ],
            'Loading' => [
                'status' => 'loading',
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => ImportStatusEnum::Terminated,
            ],
            'Failed to load' => [
                'status' => 'failed to open',
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => ImportStatusEnum::Error,
            ],
            'Additional file import not finished' => [
                'status' => 'open',
                'tmxImportStatus' => 'available',
                'importTime' => 'not finished',
                'reorganizeStatus' => null,
                'expectedResult' => ImportStatusEnum::Importing,
            ],
            'Additional file import not finished 0.6' => [
                'status' => 'import running',
                'tmxImportStatus' => 'available',
                'importTime' => 'not finished',
                'reorganizeStatus' => null,
                'expectedResult' => ImportStatusEnum::Importing,
            ],
            'Primary file import not finished' => [
                'status' => 'open',
                'tmxImportStatus' => 'import',
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => ImportStatusEnum::Importing,
            ],
            'Import finished successfully' => [
                'status' => 'open',
                'tmxImportStatus' => 'available',
                'importTime' => 'finished',
                'reorganizeStatus' => null,
                'expectedResult' => ImportStatusEnum::Done,
            ],
            'Import finished with error' => [
                'status' => 'open',
                'tmxImportStatus' => 'error',
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => ImportStatusEnum::Error,
            ],
            'Import failed' => [
                'status' => 'open',
                'tmxImportStatus' => 'failed',
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => ImportStatusEnum::Error,
            ],
            'Unknown status' => [
                'status' => bin2hex(random_bytes(10)),
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => ImportStatusEnum::Error,
            ],
            'Unknown tmxImportStatus' => [
                'status' => 'open',
                'tmxImportStatus' => bin2hex(random_bytes(10)),
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => ImportStatusEnum::Error,
            ],
            'Reorganize in progress' => [
                'status' => 'open',
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => 'reorganize',
                'expectedResult' => ImportStatusEnum::Terminated,
            ],
            'Reorganize in progress 0.6' => [
                'status' => 'reorganize running',
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => 'reorganize',
                'expectedResult' => ImportStatusEnum::Terminated,
            ],
            'Reorganize failed' => [
                'status' => 'open',
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => 'reorganize failed',
                'expectedResult' => ImportStatusEnum::Terminated,
            ],
            'Reorganize finished' => [
                'status' => 'open',
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => 'available',
                'expectedResult' => ImportStatusEnum::Terminated,
            ],
            'Empty tmxImportStatus' => [
                'status' => 'open',
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => ImportStatusEnum::Terminated,
            ],
        ];
    }

    /**
     * @dataProvider provideProcessImportStatus
     */
    public function testProcessImportStatus(
        string $status,
        ?string $tmxImportStatus,
        ?string $importTime,
        ?string $reorganizeStatus,
        ImportStatusEnum $expectedResult,
    ): void {
        $apiResponse = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $body = new \stdClass();

        $body->status = $status;

        if (null !== $tmxImportStatus) {
            $body->tmxImportStatus = $tmxImportStatus;
        }

        if (null !== $importTime) {
            $body->importTime = $importTime;
        }

        if (null !== $reorganizeStatus) {
            $body->reorganizeStatus = $reorganizeStatus;
        }

        $stream->method('getContents')->willReturn(json_encode($body, JSON_THROW_ON_ERROR));
        $apiResponse->method('getBody')->willReturn($stream);
        $apiResponse->method('getStatusCode')->willReturn(200);

        $this->assertEquals($expectedResult, ImportStatusResponse::fromResponse($apiResponse)->status);
    }
}
