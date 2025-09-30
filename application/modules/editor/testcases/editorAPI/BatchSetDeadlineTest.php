<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2025 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

class BatchSetDeadlineTest extends JsonTestAbstract
{
    protected static function setupImport(Config $config): void
    {
        $config->addTask('de', 'en', -1, '2_trans_unit_no_pretranslation.xlf');
    }

    public function testDeadlineUpdate(): void
    {
        $task = static::getTask();
        static::api()->setTaskToOpen($task->getId());

        $tuaJson = static::api()->addUserToTask(
            $task->getTaskGuid(),
            TestUser::TestManager->value,
            'open',
            'reviewing'
        );
        $this->assertEmpty($tuaJson->deadlineDate);

        $deadlineDate = '2025-03-05T17:35:00';

        static::api()->post(
            'editor/taskuserassoc/batchset',
            [
                'batchWorkflow' => 'default',
                'batchWorkflowStep' => 'reviewing',
                'deadlineDate' => $deadlineDate,
                'projectsAndTasks' => $task->getId(),
                'batchType' => 'deadlineDate',
            ]
        );

        $db = Zend_Db_Table::getDefaultAdapter();
        $deadlineDateDb = $db->fetchOne(
            'SELECT deadlineDate FROM ' . editor_Models_Db_TaskUserAssoc::TABLE_NAME . ' WHERE id = ?',
            [$tuaJson->id]
        );
        $this->assertEquals($deadlineDate, str_replace(' ', 'T', $deadlineDateDb));
    }
}
