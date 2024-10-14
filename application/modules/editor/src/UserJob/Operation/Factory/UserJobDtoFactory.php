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

namespace MittagQI\Translate5\UserJob\Operation\Factory;

use editor_Models_Task as Task;
use editor_Models_TaskUserAssoc_Segmentrange as SegmentRange;
use editor_Workflow_Default as Workflow;
use editor_Workflow_Manager;
use MittagQI\Translate5\UserJob\Exception\InvalidSegmentRangeFormatException;
use MittagQI\Translate5\UserJob\Exception\InvalidSegmentRangeSemanticException;
use MittagQI\Translate5\UserJob\Exception\WorkflowStepNotProvidedException;
use MittagQI\Translate5\UserJob\Operation\DTO\TrackChangesRightsDto;
use MittagQI\Translate5\UserJob\Operation\DTO\WorkflowDto;
use MittagQI\Translate5\UserJob\Validation\SegmentRangeValidator;
use REST_Controller_Request_Http as Request;
use ZfExtended_Logger;

/**
 * @template Dto
 */
abstract class UserJobDtoFactory
{
    public function __construct(
        private readonly ZfExtended_Logger $logger,
        private readonly editor_Workflow_Manager $workflowManager,
        private readonly SegmentRangeValidator $segmentRangeValidator,
    ) {
    }

    /**
     * @param Request $request
     * @return Dto
     */
    abstract public function fromRequest(Request $request);

    protected function getWorkflow(Task $task, ?string $workflowName = null): Workflow
    {
        if (! empty($workflowName)) {
            return $this->workflowManager->getCached($workflowName);
        }

        return $this->workflowManager->getActiveByTask($task);
    }

    /**
     * @return array{string, string}
     * @throws WorkflowStepNotProvidedException
     */
    private function getWorkflowStepNameAndRole(array $data, Workflow $workflow): array
    {
        $role = $data['role'] ?? null;
        $workflowStepName = $data['workflowStepName'] ?? null;

        if (null === $workflowStepName && null === $role) {
            throw new WorkflowStepNotProvidedException();
        }

        if (null !== $workflowStepName) {
            return [$workflowStepName, $workflow->getRoleOfStep($workflowStepName)];
        }

        if ($role === 'lector') {
            $role = Workflow::ROLE_REVIEWER;

            $this->logger->warn('E1232', 'Job creation: role "lector" is deprecated, use "reviewer" instead!');
        }

        //we have to get the step from the role (the first found step to the role)
        $steps = $workflow->getSteps2Roles();
        $roles = array_flip(array_reverse($steps));
        $workflowStepName = $roles[$data['role']] ?? null;

        $this->logger->warn(
            'E1232',
            'Job creation: using role as parameter on job creation is deprecated, use workflowStepName instead'
        );

        return [$workflowStepName, $role];
    }

    /**
     * @throws InvalidSegmentRangeFormatException
     * @throws InvalidSegmentRangeSemanticException
     */
    protected function getSegmentRanges(
        ?string $segmentRanges,
        string $taskGuid,
        string $userGuid,
        mixed $workflowStepName,
    ): ?string {
        if (empty($segmentRanges)) {
            return null;
        }

        $segmentRanges = SegmentRange::prepare($segmentRanges);

        $this->segmentRangeValidator->validate(
            $segmentRanges,
            $taskGuid,
            $userGuid,
            $workflowStepName,
        );

        return $segmentRanges;
    }

    protected function getWorkflowDto(mixed $data, Task $task): WorkflowDto
    {
        $workflow = $this->getWorkflow($task, $data['workflow']);

        [$workflowStepName, $role] = $this->getWorkflowStepNameAndRole($data, $workflow);

        return new WorkflowDto(
            $role,
            $workflow->getName(),
            $workflowStepName,
        );
    }
}
