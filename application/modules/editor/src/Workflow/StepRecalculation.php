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

namespace MittagQI\Translate5\Workflow;

use editor_Workflow_Default;
use MittagQI\Translate5\Repository\TaskRepository;

class StepRecalculation
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly WorkflowStepCalculator $nextStepCalculator,
    ) {
    }

    public static function create(): self
    {
        return new self(
            TaskRepository::create(),
            WorkflowStepCalculator::create(),
        );
    }

    /**
     * recalculates the workflow step by the given task user assoc combinations
     * If the combination of roles and states are pointing to a specific workflow step, this step is used
     * If the states and roles does not match any valid combination, no step is changed.
     */
    public function recalculateWorkflowStep(editor_Workflow_Default $workflow, string $taskGuid): void
    {
        $task = $this->taskRepository->getByGuid($taskGuid);
        $step = $this->nextStepCalculator->getValidTaskWorkflowStep($workflow, $taskGuid);

        if ($task->getWorkflowStepName() === $step) {
            return;
        }

        $workflow->getLogger($task)->info('E1013', 'recalculate workflow to step {step} ', [
            'step' => $step,
        ]);

        $task->updateWorkflowStep($step, false);

        //set $step as new workflow step if different to before!
        $this->sendFrontEndNotice($workflow, $step);
    }

    protected function sendFrontEndNotice(editor_Workflow_Default $workflow, string $step)
    {
        $msg = \ZfExtended_Factory::get('ZfExtended_Models_Messages');
        /* @var $msg \ZfExtended_Models_Messages */
        $labels = $workflow->getLabels();
        $steps = $workflow->getSteps();
        $step = $labels[array_search($step, $steps)];
        $msg->addNotice('Der Workflow Schritt der Aufgabe wurde zu "{0}" geändert!', 'core', null, $step);
    }
}
