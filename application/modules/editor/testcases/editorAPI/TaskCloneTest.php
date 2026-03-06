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

use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\JsonTestAbstract;

/**
 * Tests that cloning a regular (default) task works correctly:
 * - The clone succeeds and produces a valid task
 * - The ImportArchiv.zip has workfiles/ at the root level
 * - The cloned task gets its own project context (projectId != original)
 */
class TaskCloneTest extends JsonTestAbstract
{
    protected static function setupImport(Config $config): void
    {
        $config
            ->addTask('de', 'en')
            ->addUploadFolder('testfiles');
    }

    public function testCloneDefaultTask(): void
    {
        $task = static::api()->getTask();

        // verify the structure before the clone
        $this->assertImportArchiveStructure($task, 'Original task');

        static::api()->post('editor/task/' . $task->id . '/clone');
        $response = json_decode(static::api()->getLastResponse()->getBody(), flags: JSON_THROW_ON_ERROR);

        self::assertEquals(
            '200',
            static::api()->getLastResponse()->getStatus(),
            'Clone request failed with: ' . print_r($response, true)
        );

        self::assertTrue($response->success, 'Clone response indicates failure');

        $clonedTask = $response->rows;

        static::api()->waitForTaskImported(static::api()->reloadTask((int) $clonedTask->id));
        $clonedTask = static::api()->reloadTask((int) $clonedTask->id);

        self::assertEquals('open', $clonedTask->state, 'Cloned task should be in open state after import');

        self::assertNotEquals(
            (int) $task->id,
            (int) $clonedTask->projectId,
            'Cloned task projectId must not point to the original task'
        );
        self::assertNotEquals(
            (int) $clonedTask->id,
            (int) $clonedTask->projectId,
            'Cloned default task should be wrapped in a separate project (projectId != id)'
        );

        $this->assertImportArchiveStructure($clonedTask, 'Cloned task');

        static::api()->login('testmanager');
    }

    private function assertImportArchiveStructure(stdClass $task, string $label): void
    {
        $archivePath = static::api()->getTaskDataBaseDirectory()
            . trim($task->taskGuid, '{}') . '/'
            . editor_Models_Import_DataProvider_Abstract::TASK_ARCHIV_ZIP_NAME;

        self::assertFileExists($archivePath, "$label: ImportArchiv.zip should exist");

        $zip = new ZipArchive();
        self::assertTrue(
            $zip->open($archivePath) === true,
            "$label: Failed to open ImportArchiv.zip"
        );

        $hasWorkfilesAtRoot = false;
        $hasNestedWorkfiles = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = ltrim($zip->getNameIndex($i), '/');
            if (str_starts_with($name, 'workfiles/')) {
                $hasWorkfilesAtRoot = true;
            }
            if (preg_match('#^[^/]+/workfiles/#', $name)) {
                $hasNestedWorkfiles = true;
            }
        }

        $zip->close();

        self::assertTrue(
            $hasWorkfilesAtRoot,
            "$label: ImportArchiv.zip must have workfiles/ at the root level"
        );
        self::assertFalse(
            $hasNestedWorkfiles,
            "$label: ImportArchiv.zip must NOT have workfiles/ nested under an extra directory"
        );
    }
}
