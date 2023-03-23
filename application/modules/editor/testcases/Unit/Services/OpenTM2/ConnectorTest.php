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

namespace MittagQI\Translate5\Test\Unit\Services\Connector\TagHandler;

use PHPUnit\Framework\TestCase;
use editor_Services_OpenTM2_Connector as Connector;
use stdClass;

class ConnectorTest extends TestCase
{
    public function testProcessImportStatusNullApiResponse(): void
    {
        $apiResponse = null;
        $expectedResult = Connector::STATUS_UNKNOWN;

        $myClass = new Connector();
        $result = $myClass->processImportStatus($apiResponse);

        $this->assertEquals($expectedResult, $result);
    }

    public function provideProcessImportStatus(): array
    {
        return [
            'Not found' => [
                'status' => 'not found',
                'tmxImportStatus' => null,
                'importTime' => null,
                'expectedResult' => Connector::STATUS_ERROR
            ],
            'Available' => [
                'status' => 'available',
                'tmxImportStatus' => null,
                'importTime' => null,
                'expectedResult' => Connector::STATUS_AVAILABLE
            ],
            'Additional file import not finished' => [
                'status' => "open",
                'tmxImportStatus' => "available",
                'importTime' => "not finished",
                'expectedResult' => Connector::STATUS_IMPORT
            ],
            'Primary file import not finished' => [
                'status' => "open",
                'tmxImportStatus' => "import",
                'importTime' => null,
                'expectedResult' => Connector::STATUS_IMPORT
            ],
            'Import finished successfully' => [
                'status' => "open",
                'tmxImportStatus' => "available",
                'importTime' => 'finished',
                'expectedResult' => Connector::STATUS_AVAILABLE
            ],
            'Import finished with error' => [
                'status' => "open",
                'tmxImportStatus' => "error",
                'importTime' => null,
                'expectedResult' => Connector::STATUS_ERROR
            ],
            'Import failed' => [
                'status' => "open",
                'tmxImportStatus' => "failed",
                'importTime' => null,
                'expectedResult' => Connector::STATUS_ERROR
            ],
            'Unknown status' => [
                'status' => bin2hex(random_bytes(10)),
                'tmxImportStatus' => null,
                'importTime' => null,
                'expectedResult' => Connector::STATUS_UNKNOWN
            ],
            'Unknown tmxImportStatus' => [
                'status' => 'open',
                'tmxImportStatus' => bin2hex(random_bytes(10)),
                'importTime' => null,
                'expectedResult' => Connector::STATUS_UNKNOWN
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
        string $expectedResult,
    ): void {
        $apiResponse = new stdClass();
        $apiResponse->status = $status;

        // We don't care about error message in this test
        $apiResponse->ErrorMsg = null;

        if (null !== $tmxImportStatus) {
            $apiResponse->tmxImportStatus = $tmxImportStatus;
        }

        if (null !== $tmxImportStatus) {
            $apiResponse->importTime = $importTime;
        }

        $myClass = new Connector();
        $result = $myClass->processImportStatus($apiResponse);

        $this->assertEquals($expectedResult, $result);
    }
}
