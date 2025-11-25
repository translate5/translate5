<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\Translate5\Test\Enums\TestUser;
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\JsonTestAbstract;

class BatchExportTest extends JsonTestAbstract
{
    protected static function setupImport(Config $config): void
    {
        $config->addTask('de', 'en', -1, '2_trans_units_4_segments_match_rate.xlf');
        $config->addTask('de', 'en', -1, '3_trans_units_6_segments_match_rate.xlf');
    }

    public function testBulkExport(): void
    {
        self::api()->login(TestUser::TestManager->value);

        $taskIds = static::getTaskAt(0)->getId() . ',' . static::getTaskAt(1)->getId();

        $response = self::api()->post('editor/taskuserassoc/batchset', [
            'projectsAndTasks' => $taskIds,
            'previewTasks' => '1',
        ]);
        $this->assertEquals(200, $response->getStatus());
        $result = json_decode($response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, count($result['rows']));

        $response = self::api()->post('editor/taskuserassoc/batchset', [
            'projectsAndTasks' => $taskIds,
            'taskIds' => $taskIds,
            'batchType' => 'export',
        ]);
        $this->assertEquals(200, $response->getStatus());

        $result = json_decode($response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['nextUrl']); // "\/editor\/queuedexport\/ecf9e84f-9cc6-4caf-b351-231b2f42baff?title=Export"

        $response = self::api()->get($result['nextUrl']);
        $this->assertEquals(200, $response->getStatus());

        [$statusUrl] = explode('?', $result['nextUrl']);
        $statusUrl .= '/status';

        $this->waitForWorker(MittagQI\Translate5\Task\BatchOperations\BatchExportWorker::class);

        // calling the endpoint for the download in a loop (just like the frontend/popup does)
        $downloadGenerated = false;
        for ($i = 1; $i <= 10; $i++) {
            $response = self::api()->get($statusUrl);
            $this->assertEquals(200, $response->getStatus());
            $result = json_decode($response->getBody(), true, flags: JSON_THROW_ON_ERROR);
            if ($result['ready']) {
                $downloadGenerated = true;

                break;
            }
            sleep(1);
        }
        self::assertTrue($downloadGenerated);

    }
}
