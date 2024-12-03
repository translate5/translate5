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

use MittagQI\Translate5\JobAssignment\LspJob\Contract\DeleteLspJobAssignmentOperationInterface;
use MittagQI\Translate5\JobAssignment\LspJob\Operation\DeleteLspJobAssignmentOperation;
use MittagQI\Translate5\JobAssignment\UserJob\Contract\DeleteUserJobOperationInterface;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\DeleteUserJobOperation;
use MittagQI\Translate5\Repository\LspJobRepository;
use MittagQI\Translate5\Repository\UserJobRepository;

class JobsPurger
{
    public function __construct(
        private readonly UserJobRepository $userJobRepository,
        private readonly LspJobRepository $lspJobRepository,
        private readonly DeleteUserJobOperationInterface $deleteUserJobAssignmentOperation,
        private readonly DeleteLspJobAssignmentOperationInterface $deleteLspJobAssignmentOperation,
    ) {
    }

    public static function create(): self
    {
        return new self(
            UserJobRepository::create(),
            LspJobRepository::create(),
            DeleteUserJobOperation::create(),
            DeleteLspJobAssignmentOperation::create(),
        );
    }

    public function purgeTaskJobs(string $taskGuid): void
    {
        foreach ($this->userJobRepository->getTaskJobs($taskGuid) as $job) {
            if ($job->isLspJob()) {
                $lspJob = $this->lspJobRepository->get((int) $job->getLspJobId());

                $this->deleteLspJobAssignmentOperation->forceDelete($lspJob);

                continue;
            }

            $this->deleteUserJobAssignmentOperation->forceDelete($job);
        }
    }
}