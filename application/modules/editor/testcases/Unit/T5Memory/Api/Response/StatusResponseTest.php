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

use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;
use MittagQI\Translate5\T5Memory\Api\Response\StatusResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class StatusResponseTest extends TestCase
{
    public function provideProcessImportStatus(): array
    {
        return [
            'Not found' => [
                'status' => 'not found',
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => LanguageResourceStatus::ERROR,
            ],
            'Available' => [
                'status' => 'available',
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => LanguageResourceStatus::AVAILABLE,
            ],
            'Available just loaded' => [
                'status' => 'open',
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => LanguageResourceStatus::AVAILABLE,
            ],
            'Waiting to be loaded' => [
                'status' => 'waiting for loading',
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => LanguageResourceStatus::WAITING_FOR_LOADING,
            ],
            'Loading' => [
                'status' => 'loading',
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => LanguageResourceStatus::LOADING,
            ],
            'Failed to load' => [
                'status' => 'failed to open',
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => LanguageResourceStatus::FAILED_TO_OPEN,
            ],
            'Additional file import not finished' => [
                'status' => 'open',
                'tmxImportStatus' => 'available',
                'importTime' => 'not finished',
                'reorganizeStatus' => null,
                'expectedResult' => LanguageResourceStatus::IMPORT,
            ],
            'Additional file import not finished 0.6' => [
                'status' => 'import running',
                'tmxImportStatus' => 'available',
                'importTime' => 'not finished',
                'reorganizeStatus' => null,
                'expectedResult' => LanguageResourceStatus::IMPORT,
            ],
            'Primary file import not finished' => [
                'status' => 'open',
                'tmxImportStatus' => 'import',
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => LanguageResourceStatus::IMPORT,
            ],
            'Import finished successfully' => [
                'status' => 'open',
                'tmxImportStatus' => 'available',
                'importTime' => 'finished',
                'reorganizeStatus' => null,
                'expectedResult' => LanguageResourceStatus::AVAILABLE,
            ],
            'Import finished with error' => [
                'status' => 'open',
                'tmxImportStatus' => 'error',
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => LanguageResourceStatus::AVAILABLE,
            ],
            'Import failed' => [
                'status' => 'open',
                'tmxImportStatus' => 'failed',
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => LanguageResourceStatus::AVAILABLE,
            ],
            'Unknown status' => [
                'status' => bin2hex(random_bytes(10)),
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => LanguageResourceStatus::UNKNOWN,
            ],
            'Unknown tmxImportStatus' => [
                'status' => 'open',
                'tmxImportStatus' => bin2hex(random_bytes(10)),
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => LanguageResourceStatus::AVAILABLE,
            ],
            'Reorganize in progress' => [
                'status' => 'open',
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => 'reorganize',
                'expectedResult' => LanguageResourceStatus::REORGANIZE_IN_PROGRESS,
            ],
            'Reorganize in progress 0.6' => [
                'status' => 'reorganize running',
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => 'reorganize',
                'expectedResult' => LanguageResourceStatus::REORGANIZE_IN_PROGRESS,
            ],
            'Reorganize failed' => [
                'status' => 'open',
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => 'reorganize failed',
                'expectedResult' => LanguageResourceStatus::AVAILABLE,
            ],
            'Reorganize finished' => [
                'status' => 'open',
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => 'available',
                'expectedResult' => LanguageResourceStatus::AVAILABLE,
            ],
            'Empty tmxImportStatus' => [
                'status' => 'open',
                'tmxImportStatus' => null,
                'importTime' => null,
                'reorganizeStatus' => null,
                'expectedResult' => LanguageResourceStatus::AVAILABLE,
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
        string $expectedResult,
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

        $this->assertEquals($expectedResult, StatusResponse::fromResponse($apiResponse)->status);
    }
}
