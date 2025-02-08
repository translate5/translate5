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

namespace MittagQI\Translate5\Workflow;

use editor_Workflow_Default;
use MittagQI\Translate5\Repository\UserJobRepository;

class NextStepCalculator
{
    public function __construct(
        private readonly UserJobRepository $userJobRepository,
    ) {
    }

    public static function create(): self
    {
        return new self(
            UserJobRepository::create(),
        );
    }

    /**
     * Returns next step in stepChain, or STEP_WORKFLOW_ENDED if for nextStep no users are associated
     */
    public function getNextStep(editor_Workflow_Default $workflow, string $taskGuid, string $step): ?string
    {
        //get used roles in task:
        $associatedSteps = $this->userJobRepository->getWorkflowStepNamesOfJobsInTask($taskGuid);

        if (empty($associatedSteps)) {
            return editor_Workflow_Default::STEP_WORKFLOW_ENDED;
        }

        $stepChain = array_values($workflow->getStepChain());
        $stepCount = count($stepChain);

        $position = array_search($step, $stepChain);

        // if the current step is not found in the chain or
        // if there are no jobs the workflow should be ended then
        // (normally we never reach here since to change the workflow at least one job is needed)
        if ($position === false) {
            return editor_Workflow_Default::STEP_WORKFLOW_ENDED;
        }

        //we want the position of the next step, not the current one:
        $position++;

        //loop over all steps after the current one
        for (; $position < $stepCount; $position++) {
            if (in_array($stepChain[$position], $associatedSteps)) {
                //the first one with associated users is returned
                return $stepChain[$position];
            }
        }

        //if no next step is found, it is ended by definition
        return editor_Workflow_Default::STEP_WORKFLOW_ENDED;
    }
}
