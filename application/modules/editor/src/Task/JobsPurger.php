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

namespace MittagQI\Translate5\Task;

use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Contract\DeleteCoordinatorGroupJobOperationInterface;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Operation\DeleteCoordinatorGroupJobOperation;
use MittagQI\Translate5\JobAssignment\UserJob\Contract\DeleteUserJobOperationInterface;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\DeleteUserJobOperation;
use MittagQI\Translate5\Repository\CoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\UserJobRepository;

class JobsPurger
{
    public function __construct(
        private readonly UserJobRepository $userJobRepository,
        private readonly CoordinatorGroupJobRepository $coordinatorGroupJobRepository,
        private readonly DeleteUserJobOperationInterface $deleteUserJobAssignmentOperation,
        private readonly DeleteCoordinatorGroupJobOperationInterface $deleteCoordinatorGroupJobOperation,
    ) {
    }

    public static function create(): self
    {
        return new self(
            UserJobRepository::create(),
            CoordinatorGroupJobRepository::create(),
            DeleteUserJobOperation::create(),
            DeleteCoordinatorGroupJobOperation::create(),
        );
    }

    public function purgeTaskJobs(string $taskGuid): void
    {
        // first delete all Coordinator group jobs, that will also delete the Coordinator group user jobs if there are any
        foreach ($this->coordinatorGroupJobRepository->getTaskJobsOfTopRankCoordinatorGroups($taskGuid) as $job) {
            $this->deleteCoordinatorGroupJobOperation->forceDelete($job);
        }

        // now delete simple user jobs
        foreach ($this->userJobRepository->getTaskJobs($taskGuid) as $job) {
            $this->deleteUserJobAssignmentOperation->forceDelete($job);
        }
    }
}
