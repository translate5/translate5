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

namespace MittagQI\Translate5\JobAssignment\UserJob\DataProvider;

use editor_Models_Task as Task;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinatorRepository;
use MittagQI\Translate5\CoordinatorGroup\Model\Db\CoordinatorGroupTable;
use MittagQI\Translate5\CoordinatorGroup\Model\Db\CoordinatorGroupUserTable;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Model\Db\CoordinatorGroupJobTable;
use MittagQI\Translate5\Task\ActionAssert\Permission\TaskActionPermissionAssert;
use MittagQI\Translate5\Task\ActionAssert\TaskAction;
use MittagQI\Translate5\User\DataProvider\PermissionAwareUserFetcher;
use MittagQI\Translate5\User\Model\User;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use ZfExtended_Models_Db_User;
use ZfExtended_Models_User;

/**
 * @template UserData as array{userGuid: string, longUserName: string}
 */
class UserProvider
{
    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
        private readonly ActionPermissionAssertInterface $taskActionPermissionAssert,
        private readonly JobCoordinatorRepository $jobCoordinatorRepository,
        private readonly PermissionAwareUserFetcher $permissionAwareUserFetcher,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_Db_Table::getDefaultAdapter(),
            TaskActionPermissionAssert::create(),
            JobCoordinatorRepository::create(),
            PermissionAwareUserFetcher::create(),
        );
    }

    /**
     * @return UserData[]
     */
    public function getPossibleUsersForNewJobInTask(Task $task, User $viewer): array
    {
        $context = new PermissionAssertContext($viewer);

        if ($viewer->isAdmin()) {
            return array_merge(
                $this->getSimpleUsers($viewer),
                $this->getCoordinatorGroupUsers($task->getTaskGuid(), $viewer)
            );
        }

        if (! $this->taskActionPermissionAssert->isGranted(TaskAction::Read, $task, $context)) {
            return [];
        }

        if ($viewer->isPm() || $viewer->isClientPm()) {
            return $this->getSimpleUsers($viewer);
        }

        if (! $viewer->isCoordinator()) {
            return [];
        }

        return $this->getCoordinatorGroupUsers($task->getTaskGuid(), $viewer);
    }

    /**
     * @return UserData[]
     */
    private function getSimpleUsers(User $viewer): array
    {
        $select = $this->db
            ->select()
            ->from(
                [
                    'user' => ZfExtended_Models_Db_User::TABLE_NAME,
                ]
            )
            ->joinLeft(
                [
                    'groupUser' => CoordinatorGroupUserTable::TABLE_NAME,
                ],
                'groupUser.userId = user.id',
                ['groupUser.groupId']
            )
            ->where('groupUser.groupId IS NULL')
            ->where('user.login != ?', ZfExtended_Models_User::SYSTEM_LOGIN)
        ;

        return array_map(
            fn ($user) => [
                'userGuid' => $user['userGuid'],
                'longUserName' => $user['longUserName'],
            ],
            $this->permissionAwareUserFetcher->fetchVisible($select, $viewer)
        );
    }

    /**
     * It is impossible to create Coordinator group User job for Coordinator group User without Coordinator group job
     *
     * @return UserData[]
     */
    private function getCoordinatorGroupUsers(string $taskGuid, User $viewer): array
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
                    'groupJob' => CoordinatorGroupJobTable::TABLE_NAME,
                ],
                'groupJob.groupId = group.id',
                []
            )
            ->where('groupJob.taskGuid = ?', $taskGuid)
        ;

        if ($viewer->isCoordinator()) {
            $coordinator = $this->jobCoordinatorRepository->getByUser($viewer);

            $select->where('group.id = ?', $coordinator->group->getId());
        }

        return array_map(
            fn ($user) => [
                'userGuid' => $user['userGuid'],
                'longUserName' => $user['longUserName'],
            ],
            $this->permissionAwareUserFetcher->fetchVisible($select, $viewer)
        );
    }
}
