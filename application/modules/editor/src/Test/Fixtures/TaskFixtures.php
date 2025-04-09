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

namespace MittagQI\Translate5\Test\Fixtures;

use editor_Models_Task;
use editor_Task_Type_Project;
use editor_Task_Type_ProjectTask;
use Faker\Factory;
use Faker\Generator;
use ZfExtended_Utils;

/**
 * @codeCoverageIgnore
 */
class TaskFixtures
{
    public function __construct(
        private readonly Generator $faker,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Factory::create(),
        );
    }

    public function createTask(
        int $customerId,
        string $workflow,
        string $workflowStepName,
        int $sourceLang,
        int $targetLang,
        ?string $state = null,
        ?int $projectId = null,
    ): editor_Models_Task {
        $task = new editor_Models_Task();
        $task->setTaskGuid(ZfExtended_Utils::guid());
        $task->setTaskNr('1');
        $task->setCustomerId($customerId);
        $task->setState($state ?: editor_Models_Task::STATE_IMPORT);
        $task->setTaskName($this->faker->sentence());
        $task->setTaskType(editor_Task_Type_ProjectTask::ID);
        $task->setWorkflow($workflow);
        $task->setSourceLang($sourceLang);
        $task->setTargetLang($targetLang);
        if ($projectId !== null) {
            $task->setProjectId($projectId);
        }
        $task->save();
        if (! empty($workflowStepName)) {
            $task->updateWorkflowStep($workflowStepName);
        }

        return $task;
    }

    public function createProject(
        int $customerId,
        string $pmGuid
    ): editor_Models_Task {
        $task = new editor_Models_Task();
        $task->setTaskGuid(ZfExtended_Utils::guid());
        //$task->setTaskNr('1');
        $task->setCustomerId($customerId);
        $task->setPmGuid($pmGuid);
        $task->setState(editor_Models_Task::STATE_PROJECT);
        $task->setTaskName($this->faker->sentence());
        $task->setTaskType(editor_Task_Type_Project::ID);
        $task->save();

        return $task;
    }
}
