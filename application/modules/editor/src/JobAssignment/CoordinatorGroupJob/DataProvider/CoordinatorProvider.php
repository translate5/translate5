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

namespace MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\DataProvider;

use editor_Models_Task as Task;
use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\CoordinatorGroup\Exception\CustomerDoesNotBelongToJobCoordinatorException;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinatorRepository;
use MittagQI\Translate5\CoordinatorGroup\Model\Db\CoordinatorGroupTable;
use MittagQI\Translate5\CoordinatorGroup\Model\Db\CoordinatorGroupUserTable;
use MittagQI\Translate5\CoordinatorGroup\Validation\CoordinatorGroupCustomerAssociationValidator;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Model\CoordinatorGroupJob;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Model\Db\CoordinatorGroupJobTable;
use MittagQI\Translate5\Task\ActionAssert\Permission\TaskActionPermissionAssert;
use MittagQI\Translate5\Task\ActionAssert\TaskAction;
use MittagQI\Translate5\User\DataProvider\PermissionAwareUserFetcher;
use MittagQI\Translate5\User\Model\User;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use ZfExtended_Models_Db_User;

/**
 * @template Coordinator as array{userGuid: string, longUserName: string}
 */
class CoordinatorProvider
{
    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
        private readonly ActionPermissionAssertInterface $taskActionPermissionAssert,
        private readonly JobCoordinatorRepository $jobCoordinatorRepository,
        private readonly PermissionAwareUserFetcher $permissionAwareUserFetcher,
        private readonly CoordinatorGroupCustomerAssociationValidator $coordinatorGroupCustomerAssociationValidator,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_Db_Table::getDefaultAdapter(),
            TaskActionPermissionAssert::create(),
            JobCoordinatorRepository::create(),
            PermissionAwareUserFetcher::create(),
            CoordinatorGroupCustomerAssociationValidator::create(),
        );
    }

    /**
     * @return Coordinator[]
     */
    public function getPossibleCoordinatorsForNewJobInTask(Task $task, User $viewer): array
    {
        $context = new PermissionAssertContext($viewer);

        if ($viewer->isAdmin()) {
            return $this->filterCoordinatorsByCustomer(
                array_merge(
                    $this->getDirectCoordinators($viewer),
                    $this->getSubCoordinatorsForTask($task->getTaskGuid(), $viewer)
                ),
                (int) $task->getCustomerId()
            );
        }

        if (! $this->taskActionPermissionAssert->isGranted(TaskAction::Read, $task, $context)) {
            return [];
        }

        if ($viewer->isPm()) {
            return $this->filterCoordinatorsByCustomer(
                $this->getDirectCoordinators($viewer),
                (int) $task->getCustomerId()
            );
        }

        if (! $viewer->isCoordinator()) {
            return [];
        }

        $coordinators = [];
        $viewerCoordinator = $this->jobCoordinatorRepository->getByUser($viewer);

        foreach ($this->jobCoordinatorRepository->getSubCoordinatorGroupJobCoordinators($viewerCoordinator) as $coordinator) {
            $coordinators[] = [
                'userId' => (int) $coordinator->user->getId(),
                'userGuid' => $coordinator->user->getUserGuid(),
                'longUserName' => $coordinator->user->getUsernameLong(),
            ];
        }

        return $this->filterCoordinatorsByCustomer($coordinators, (int) $task->getCustomerId());
    }

    /**
     * @param array{userId: int, userGuid: string, longUserName: string}[] $coordinators
     * @return Coordinator[]
     */
    private function filterCoordinatorsByCustomer(array $coordinators, int $customerId): array
    {
        $filteredCoordinators = [];

        foreach ($coordinators as $coordinator) {
            try {
                $this->coordinatorGroupCustomerAssociationValidator->assertCustomersAreSubsetForGroupOfCoordinator(
                    $coordinator['userId'],
                    $customerId
                );

                $filteredCoordinators[] = [
                    'userGuid' => $coordinator['userGuid'],
                    'longUserName' => $coordinator['longUserName'],
                ];
            } catch (CustomerDoesNotBelongToJobCoordinatorException) {
                // do nothing
            }
        }

        return $filteredCoordinators;
    }

    /**
     * @return array{userId: int, userGuid: string, longUserName: string}[]
     */
    public function getPossibleCoordinatorsForCoordinatorGroupJobUpdate(CoordinatorGroupJob $groupJob): array
    {
        $coordinators = [];
        foreach ($this->jobCoordinatorRepository->getByCoordinatorGroupId((int) $groupJob->getGroupId()) as $coordinator) {
            $coordinators[] = [
                'userId' => (int) $coordinator->user->getId(),
                'userGuid' => $coordinator->user->getUserGuid(),
                'longUserName' => $coordinator->user->getUsernameLong(),
            ];
        }

        return $coordinators;
    }

    /**
     * Fetch coordinators of sub Coordinator Groups if their parent Group has job in a task
     * It is impossible to create Coordinator Group job for sub Group without parent Group job
     *
     * @return array{userId: int, userGuid: string, longUserName: string}[]
     */
    private function getSubCoordinatorsForTask(string $taskGuid, User $viewer): array
    {
        $select = $this->db
            ->select()
            ->distinct()
            ->from(
                [
                    'user' => ZfExtended_Models_Db_User::TABLE_NAME,
                ]
            )
            ->join(
                [
                    'groupUser' => CoordinatorGroupUserTable::TABLE_NAME,
                ],
                'groupUser.userId = user.id',
                []
            )
            ->join(
                [
                    'group' => CoordinatorGroupTable::TABLE_NAME,
                ],
                'groupUser.groupId = group.id',
                []
            )
            ->join(
                [
                    'parentgroupJob' => CoordinatorGroupJobTable::TABLE_NAME,
                ],
                'parentgroupJob.groupId = group.parentId',
                []
            )
            ->where('parentgroupJob.taskGuid = ?', $taskGuid)
            ->where('user.roles LIKE ?', '%' . Roles::JOB_COORDINATOR . '%')
            ->where('group.parentId IS NOT NULL')
        ;

        return $this->permissionAwareUserFetcher->fetchVisible($select, $viewer);
    }

    /**
     * @return array{userId: int, userGuid: string, longUserName: string}[]
     */
    private function getDirectCoordinators(User $viewer): array
    {
        $select = $this->db
            ->select()
            ->distinct()
            ->from(
                [
                    'user' => ZfExtended_Models_Db_User::TABLE_NAME,
                ]
            )
            ->join(
                [
                    'groupUser' => CoordinatorGroupUserTable::TABLE_NAME,
                ],
                'groupUser.userId = user.id',
                []
            )
            ->join(
                [
                    'group' => CoordinatorGroupTable::TABLE_NAME,
                ],
                'groupUser.groupId = group.id',
                []
            )
            ->where('user.roles LIKE ?', '%' . Roles::JOB_COORDINATOR . '%')
            ->where('group.parentId IS NULL')
        ;

        return $this->permissionAwareUserFetcher->fetchVisible($select, $viewer);
    }
}
