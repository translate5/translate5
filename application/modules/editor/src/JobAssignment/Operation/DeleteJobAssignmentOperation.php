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

namespace MittagQI\Translate5\JobAssignment\Operation;

use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Contract\DeleteCoordinatorGroupJobOperationInterface;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Operation\DeleteCoordinatorGroupJobOperation;
use MittagQI\Translate5\JobAssignment\UserJob\Contract\DeleteUserJobOperationInterface;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\DeleteUserJobOperation;
use MittagQI\Translate5\Repository\CoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use ZfExtended_Models_Entity_NotFoundException;

class DeleteJobAssignmentOperation
{
    public function __construct(
        private readonly UserJobRepository $userJobRepository,
        private readonly CoordinatorGroupJobRepository $coordinatorGroupJobRepository,
        private readonly DeleteCoordinatorGroupJobOperationInterface $deleteCoordinatorGroupJob,
        private readonly DeleteUserJobOperationInterface $deleteUserJob,
    ) {
    }

    public static function create(): self
    {
        return new self(
            UserJobRepository::create(),
            CoordinatorGroupJobRepository::create(),
            DeleteCoordinatorGroupJobOperation::create(),
            DeleteUserJobOperation::create(),
        );
    }

    public function delete(int $jobId): void
    {
        try {
            $job = $this->userJobRepository->get($jobId);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            return;
        }

        if ($job->isCoordinatorGroupJob()) {
            $groupJob = $this->coordinatorGroupJobRepository->get((int) $job->getCoordinatorGroupJobId());

            $this->deleteCoordinatorGroupJob->delete($groupJob);

            return;
        }

        $this->deleteUserJob->delete($job);
    }

    public function forceDelete(int $jobId): void
    {
        try {
            $job = $this->userJobRepository->get($jobId);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            return;
        }

        if ($job->isCoordinatorGroupJob()) {
            $groupJob = $this->coordinatorGroupJobRepository->get((int) $job->getCoordinatorGroupJobId());

            $this->deleteCoordinatorGroupJob->forceDelete($groupJob);

            return;
        }

        $this->deleteUserJob->forceDelete($job);
    }
}
