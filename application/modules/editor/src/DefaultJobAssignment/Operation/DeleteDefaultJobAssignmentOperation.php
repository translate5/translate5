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

namespace MittagQI\Translate5\DefaultJobAssignment\Operation;

use MittagQI\Translate5\DefaultJobAssignment\Contract\DeleteDefaultCoordinatorGroupJobOperationInterface;
use MittagQI\Translate5\DefaultJobAssignment\Contract\DeleteDefaultUserJobOperationInterface;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\Operation\DeleteDefaultCoordinatorGroupJobOperation;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\Operation\DeleteDefaultUserJobOperation;
use MittagQI\Translate5\Repository\DefaultCoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\DefaultUserJobRepository;
use ZfExtended_Models_Entity_NotFoundException;

class DeleteDefaultJobAssignmentOperation
{
    public function __construct(
        private readonly DefaultUserJobRepository $defaultUserJobRepository,
        private readonly DefaultCoordinatorGroupJobRepository $defaultCoordinatorGroupJobRepository,
        private readonly DeleteDefaultCoordinatorGroupJobOperationInterface $deleteDefaultCoordinatorGroupJobOperation,
        private readonly DeleteDefaultUserJobOperationInterface $deleteDefaultUserJobOperation,
    ) {
    }

    public static function create(): self
    {
        return new self(
            DefaultUserJobRepository::create(),
            DefaultCoordinatorGroupJobRepository::create(),
            DeleteDefaultCoordinatorGroupJobOperation::create(),
            DeleteDefaultUserJobOperation::create(),
        );
    }

    public function delete(int $jobId): void
    {
        try {
            $job = $this->defaultUserJobRepository->get($jobId);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            return;
        }

        $defaultGroupJobByDataJobId = $this->defaultCoordinatorGroupJobRepository
            ->findDefaultCoordinatorGroupJobByDataJobId($jobId);

        if (null !== $defaultGroupJobByDataJobId) {
            $this->deleteDefaultCoordinatorGroupJobOperation->delete($defaultGroupJobByDataJobId);

            return;
        }

        $this->deleteDefaultUserJobOperation->delete($job);
    }
}
