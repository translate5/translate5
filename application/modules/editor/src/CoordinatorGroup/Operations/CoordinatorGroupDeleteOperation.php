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

namespace MittagQI\Translate5\CoordinatorGroup\Operations;

use MittagQI\Translate5\CoordinatorGroup\Contract\CoordinatorGroupDeleteOperationInterface;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\Operation\DeleteDefaultCoordinatorGroupJobOperation;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Contract\DeleteCoordinatorGroupJobOperationInterface;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Operation\DeleteCoordinatorGroupJobOperation;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupRepositoryInterface;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupUserRepositoryInterface;
use MittagQI\Translate5\Repository\CoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\CoordinatorGroupRepository;
use MittagQI\Translate5\Repository\CoordinatorGroupUserRepository;
use MittagQI\Translate5\Repository\DefaultCoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\LastCoordinatorException;
use MittagQI\Translate5\User\Contract\UserDeleteOperationInterface;
use MittagQI\Translate5\User\Operations\UserDeleteOperation;
use Zend_Registry;
use ZfExtended_Logger;

final class CoordinatorGroupDeleteOperation implements CoordinatorGroupDeleteOperationInterface
{
    public function __construct(
        private readonly CoordinatorGroupRepositoryInterface $coordinatorGroupRepository,
        private readonly CoordinatorGroupUserRepositoryInterface $coordinatorGroupUserRepository,
        private readonly CoordinatorGroupJobRepository $coordinatorGroupJobRepository,
        private readonly UserRepository $userRepository,
        private readonly DefaultCoordinatorGroupJobRepository $defaultCoordinatorGroupJobRepository,
        private readonly UserDeleteOperationInterface $deleteUserOperation,
        private readonly DeleteCoordinatorGroupJobOperationInterface $deleteCoordinatorGroupJobOperation,
        private readonly DeleteDefaultCoordinatorGroupJobOperation $deleteDefaultCoordinatorGroupJobOperation,
        private readonly ZfExtended_Logger $logger,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            CoordinatorGroupRepository::create(),
            CoordinatorGroupUserRepository::create(),
            CoordinatorGroupJobRepository::create(),
            new UserRepository(),
            DefaultCoordinatorGroupJobRepository::create(),
            UserDeleteOperation::create(),
            DeleteCoordinatorGroupJobOperation::create(),
            DeleteDefaultCoordinatorGroupJobOperation::create(),
            Zend_Registry::get('logger')->cloneMe('coordinatorGroup.delete')
        );
    }

    public function deleteCoordinatorGroup(CoordinatorGroup $group): void
    {
        $this->deleteCoordinatorGroupJobs($group);
        $this->deleteDefaultCoordinatorGroupJobs($group);
        $this->deleteCoordinatorGroupUsers($group);

        foreach ($this->coordinatorGroupRepository->getSubCoordinatorGroupList($group) as $subGroup) {
            $this->deleteCoordinatorGroup($subGroup);
        }

        $this->coordinatorGroupRepository->delete($group);

        $this->logger->info(
            'E1637',
            'Audit: {message}',
            [
                'message' => sprintf('Coordinator Group "%s" was deleted', $group->getName()),
                'coordinatorGroup' => $group->getName(),
            ]
        );
    }

    public function deleteCoordinatorGroupJobs(CoordinatorGroup $group): void
    {
        $coordinatorGroupJobs = $this->coordinatorGroupJobRepository->getCoordinatorGroupJobs((int) $group->getId());

        foreach ($coordinatorGroupJobs as $groupJob) {
            $this->deleteCoordinatorGroupJobOperation->forceDelete($groupJob);
        }
    }

    public function deleteDefaultCoordinatorGroupJobs(CoordinatorGroup $group): void
    {
        $groupJobs = $this->defaultCoordinatorGroupJobRepository->getDefaultCoordinatorGroupJobs((int) $group->getId());

        foreach ($groupJobs as $groupJob) {
            $this->deleteDefaultCoordinatorGroupJobOperation->delete($groupJob);
        }
    }

    public function deleteCoordinatorGroupUsers(CoordinatorGroup $group): void
    {
        $usersOfCoordinatorGroup = $this->coordinatorGroupUserRepository->getUsers((int) $group->getId());

        foreach ($usersOfCoordinatorGroup as $user) {
            try {
                $this->deleteUserOperation->forceDelete($user);
            } catch (LastCoordinatorException) {
                $this->userRepository->delete($user);

                $this->logger->info(
                    'E1637',
                    'Audit: {message}',
                    [
                        'message' => sprintf('User (login: "%s") was deleted', $user->getLogin()),
                        'user' => $user->getLogin(),
                    ]
                );
            }
        }
    }
}
