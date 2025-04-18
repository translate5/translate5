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

namespace MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Validation;

use editor_Models_Task as Task;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Exception\CoordinatorOfParentGroupHasNotConfirmedCoordinatorGroupJobYetException;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Exception\NotFoundCoordinatorGroupJobException;
use MittagQI\Translate5\JobAssignment\Exception\ConfirmedCompetitiveJobAlreadyExistsException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\AttemptToAssignSubCoordinatorGroupJobBeforeParentJobCreatedException;
use MittagQI\Translate5\Repository\CoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\UserJobRepository;

class CompetitiveJobCreationValidator
{
    public function __construct(
        private readonly UserJobRepository $userJobRepository,
        private readonly CoordinatorGroupJobRepository $coordinatorGroupJobRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            UserJobRepository::create(),
            CoordinatorGroupJobRepository::create(),
        );
    }

    /**
     * @throws ConfirmedCompetitiveJobAlreadyExistsException
     */
    public function assertCanCreate(
        Task $task,
        CoordinatorGroup $group,
        string $workflow,
        string $workflowStepName
    ): void {
        if (! $task->isCompetitive()) {
            return;
        }

        if (! $group->isTopRankGroup()) {
            try {
                // check if parent Coordinator Group Job exists.
                // Sub Group can have only jobs related to its parent Coordinator Group
                $parentJob = $this->coordinatorGroupJobRepository->getByCoordinatorGroupIdTaskGuidAndWorkflow(
                    (int) $group->getParentId(),
                    $task->getTaskGuid(),
                    $workflow,
                    $workflowStepName,
                );
            } catch (NotFoundCoordinatorGroupJobException) {
                throw new AttemptToAssignSubCoordinatorGroupJobBeforeParentJobCreatedException();
            }

            $dataJob = $this->userJobRepository->getDataJobByCoordinatorGroupJob((int) $parentJob->getId());

            if (! $dataJob->isConfirmed()) {
                throw new CoordinatorOfParentGroupHasNotConfirmedCoordinatorGroupJobYetException();
            }

            return;
        }

        if ($this->userJobRepository->taskHasConfirmedJob($task->getTaskGuid(), $workflow, $workflowStepName)) {
            throw new ConfirmedCompetitiveJobAlreadyExistsException();
        }
    }
}
