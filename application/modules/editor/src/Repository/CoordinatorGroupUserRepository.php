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

namespace MittagQI\Translate5\Repository;

use MittagQI\Translate5\CoordinatorGroup\CoordinatorGroupUser;
use MittagQI\Translate5\CoordinatorGroup\Exception\CoordinatorGroupUserNotFoundException;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroupUser as CoordinatorGroupUserModel;
use MittagQI\Translate5\CoordinatorGroup\Model\Db\CoordinatorGroupTable;
use MittagQI\Translate5\CoordinatorGroup\Model\Db\CoordinatorGroupUserTable;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupUserRepositoryInterface;
use MittagQI\Translate5\User\Model\User;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use ZfExtended_Models_Db_User as UserTable;
use ZfExtended_Models_Entity_NotFoundException;

class CoordinatorGroupUserRepository implements CoordinatorGroupUserRepositoryInterface
{
    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
        private readonly UserRepository $userRepository,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_Db_Table::getDefaultAdapter(),
            new UserRepository(),
        );
    }

    public function save(CoordinatorGroupUser $groupUser): void
    {
        $assoc = new CoordinatorGroupUserModel();
        $assoc->setGuid($groupUser->guid);
        $assoc->setGroupId((int) $groupUser->group->getId());
        $assoc->setUserId((int) $groupUser->user->getId());

        $assoc->save();
    }

    public function delete(CoordinatorGroupUser $groupUser): void
    {
        $this->db->delete(CoordinatorGroupUserTable::TABLE_NAME, [
            'guid = ?' => $groupUser->guid,
        ]);
    }

    public function findByUser(User $user): ?CoordinatorGroupUser
    {
        try {
            return $this->getByUser($user);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            return null;
        }
    }

    /**
     * @throws CoordinatorGroupUserNotFoundException
     */
    public function getByUser(User $user): CoordinatorGroupUser
    {
        $group = new CoordinatorGroup();

        $select = $this->db->select()
            ->from([
                'group' => CoordinatorGroupTable::TABLE_NAME,
            ])
            ->join([
                'groupToUser' => CoordinatorGroupUserTable::TABLE_NAME,
            ], 'group.id = groupToUser.groupId', ['groupToUser.guid'])
            ->where('groupToUser.userId = ?', $user->getId());

        $row = $this->db->fetchRow($select);

        if (! $row) {
            throw new CoordinatorGroupUserNotFoundException((int) $user->getId());
        }

        $guid = $row['guid'];

        unset($row['guid']);

        $group->init(
            new \Zend_Db_Table_Row(
                [
                    'table' => $group->db,
                    'data' => $row,
                    'stored' => true,
                    'readOnly' => false,
                ]
            )
        );

        return new CoordinatorGroupUser($guid, $user, $group);
    }

    /**
     * @throws CoordinatorGroupUserNotFoundException
     */
    public function getByUserGuid(string $userGuid): CoordinatorGroupUser
    {
        try {
            $user = $this->userRepository->getByGuid($userGuid);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            throw new CoordinatorGroupUserNotFoundException($userGuid);
        }

        return $this->getByUser($user);
    }

    public function findByUserGuid(string $userGuid): ?CoordinatorGroupUser
    {
        try {
            return $this->getByUserGuid($userGuid);
        } catch (CoordinatorGroupUserNotFoundException) {
            return null;
        }
    }

    /**
     * @throws CoordinatorGroupUserNotFoundException
     */
    public function getByUserId(int $userId): CoordinatorGroupUser
    {
        try {
            $user = $this->userRepository->get($userId);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            throw new CoordinatorGroupUserNotFoundException($userId);
        }

        return $this->getByUser($user);
    }

    /**
     * @return array<int, int>
     */
    public function getUserIdToCoordinatorGroupIdMap(): array
    {
        $groupToUser = new CoordinatorGroupUserModel();
        $assocs = $groupToUser->loadAll();

        return array_column($assocs, 'groupId', 'userId');
    }

    public function getUsers(int $groupId): iterable
    {
        $user = new User();

        $select = $this->db->select()
            ->from([
                'user' => UserTable::TABLE_NAME,
            ])
            ->join([
                'groupToUser' => CoordinatorGroupUserTable::TABLE_NAME,
            ], 'user.id = groupToUser.userId', [])
            ->where('groupToUser.groupId = ?', $groupId);

        $rows = $this->db->fetchAll($select);

        if (empty($rows)) {
            return yield from [];
        }

        foreach ($rows as $row) {
            $user->init(
                new \Zend_Db_Table_Row(
                    [
                        'table' => $user->db,
                        'data' => $row,
                        'stored' => true,
                        'readOnly' => false,
                    ]
                )
            );

            yield clone $user;
        }
    }

    public function getUserGuids(int $groupId): array
    {
        $select = $this->db
            ->select()
            ->from(
                [
                    'user' => UserTable::TABLE_NAME,
                ],
                'userGuid',
            )
            ->join(
                [
                    'groupToUser' => CoordinatorGroupUserTable::TABLE_NAME,
                ],
                'user.id = groupToUser.userId',
                []
            )
            ->where('groupToUser.groupId = ?', $groupId);

        return $this->db->fetchCol($select);
    }

    public function getCoordinatorGroupUsers(CoordinatorGroup $group): iterable
    {
        foreach ($this->getUsers((int) $group->getId()) as $user) {
            yield new CoordinatorGroupUser($user->getUserGuid(), $user, $group);
        }
    }
}
