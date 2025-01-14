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

namespace MittagQI\Translate5\CoordinatorGroup;

use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\CoordinatorGroup\Exception\CantCreateCoordinatorFromUserException;
use MittagQI\Translate5\CoordinatorGroup\Exception\CoordinatorGroupUserNotFoundException;
use MittagQI\Translate5\CoordinatorGroup\Exception\CoordinatorNotFoundException;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\CoordinatorGroup\Model\Db\CoordinatorGroupUserTable;
use MittagQI\Translate5\Repository\CoordinatorGroupRepository;
use MittagQI\Translate5\Repository\CoordinatorGroupUserRepository;
use MittagQI\Translate5\User\Model\User;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;
use Zend_Db_Table_Row;
use ZfExtended_Models_Db_User;

class JobCoordinatorRepository
{
    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
        private readonly CoordinatorGroupRepository $coordinatorGroupRepository,
        private readonly CoordinatorGroupUserRepository $coordinatorGroupUserRepository,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_Db_Table::getDefaultAdapter(),
            CoordinatorGroupRepository::create(),
            CoordinatorGroupUserRepository::create(),
        );
    }

    public function findByUser(User $user): ?JobCoordinator
    {
        try {
            return $this->getByUser($user);
        } catch (CoordinatorNotFoundException) {
            return null;
        }
    }

    /**
     * @throws CoordinatorNotFoundException
     */
    public function getByUser(User $user): JobCoordinator
    {
        try {
            $coordinatorGroupUser = $this->coordinatorGroupUserRepository->getByUser($user);

            return JobCoordinator::fromCoordinatorGroupUser($coordinatorGroupUser);
        } catch (CantCreateCoordinatorFromUserException|CoordinatorGroupUserNotFoundException) {
            throw new CoordinatorNotFoundException($user->getUserGuid());
        }
    }

    /**
     * @throws CoordinatorNotFoundException
     */
    public function getByUserGuid(string $userGuid): JobCoordinator
    {
        try {
            $coordinatorGroupUser = $this->coordinatorGroupUserRepository->getByUserGuid($userGuid);

            return JobCoordinator::fromCoordinatorGroupUser($coordinatorGroupUser);
        } catch (CantCreateCoordinatorFromUserException|CoordinatorGroupUserNotFoundException) {
            throw new CoordinatorNotFoundException($userGuid);
        }
    }

    public function findByUserGuid(string $userGuid): ?JobCoordinator
    {
        try {
            return $this->getByUserGuid($userGuid);
        } catch (CoordinatorNotFoundException) {
            return null;
        }
    }

    /**
     * @return iterable<JobCoordinator>
     */
    public function getByCoordinatorGroupId(int $id): iterable
    {
        $group = $this->coordinatorGroupRepository->get($id);

        yield from $this->getByCoordinatorGroup($group);
    }

    /**
     * @return iterable<JobCoordinator>
     */
    public function getByCoordinatorGroup(CoordinatorGroup $group): iterable
    {
        $user = new User();

        $select = $this->db
            ->select()
            ->from([
                'user' => ZfExtended_Models_Db_User::TABLE_NAME,
            ])
            ->join([
                'CoordinatorGroupToUser' => CoordinatorGroupUserTable::TABLE_NAME,
            ], 'user.id = CoordinatorGroupToUser.userId', ['CoordinatorGroupToUser.guid'])
            ->where('CoordinatorGroupToUser.groupId = ?', $group->getId())
            ->where('user.roles LIKE ?', '%' . Roles::JOB_COORDINATOR . '%');

        $rows = $this->db->fetchAll($select);

        if (empty($rows)) {
            return yield from [];
        }

        foreach ($rows as $row) {
            $guid = $row['guid'];

            unset($row['guid']);

            $user->init(new Zend_Db_Table_Row(
                [
                    'table' => $user->db,
                    'data' => $row,
                    'stored' => true,
                    'readOnly' => false,
                ]
            ));

            yield new JobCoordinator($guid, clone $user, clone $group);
        }
    }

    /**
     * @return iterable<JobCoordinator>
     */
    public function getSubCoordinatorGroupJobCoordinators(JobCoordinator $coordinator): iterable
    {
        foreach ($this->coordinatorGroupRepository->getSubCoordinatorGroupList($coordinator->group) as $subGroup) {
            yield from $this->getByCoordinatorGroup($subGroup);
        }
    }

    public function getCoordinatorsCount(CoordinatorGroup $group): int
    {
        $select = $this->db
            ->select()
            ->from([
                'user' => ZfExtended_Models_Db_User::TABLE_NAME,
            ], [
                'count' => 'COUNT(*)',
            ])
            ->join([
                'groupToUser' => CoordinatorGroupUserTable::TABLE_NAME,
            ], 'user.id = groupToUser.userId', [])
            ->where('groupToUser.groupId = ?', $group->getId())
            ->where('user.roles LIKE ?', '%' . Roles::JOB_COORDINATOR . '%');

        return (int) $this->db->fetchRow($select)['count'];
    }
}
