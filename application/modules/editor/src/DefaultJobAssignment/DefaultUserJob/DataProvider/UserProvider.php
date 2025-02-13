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

namespace MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\DataProvider;

use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinatorRepository;
use MittagQI\Translate5\CoordinatorGroup\Model\Db\CoordinatorGroupCustomerTable;
use MittagQI\Translate5\CoordinatorGroup\Model\Db\CoordinatorGroupUserTable;
use MittagQI\Translate5\Customer\ActionAssert\CustomerAction;
use MittagQI\Translate5\Customer\ActionAssert\CustomerActionPermissionAssert;
use MittagQI\Translate5\Repository\CustomerRepository;
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
        private readonly JobCoordinatorRepository $jobCoordinatorRepository,
        private readonly CustomerRepository $customerRepository,
        private readonly PermissionAwareUserFetcher $permissionAwareUserFetcher,
        private readonly ActionPermissionAssertInterface $customerPermissionAssert,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            Zend_Db_Table::getDefaultAdapter(),
            JobCoordinatorRepository::create(),
            CustomerRepository::create(),
            PermissionAwareUserFetcher::create(),
            CustomerActionPermissionAssert::create(),
        );
    }

    /**
     * @return UserData[]
     */
    public function getPossibleUsers(int $customerId, User $viewer): array
    {
        $context = new PermissionAssertContext($viewer);

        if ($viewer->isAdmin()) {
            return array_merge(
                $this->getSimpleUsers($viewer),
                $this->getLspUsers($customerId, $viewer)
            );
        }

        $customer = $this->customerRepository->get($customerId);

        if (! $this->customerPermissionAssert->isGranted(CustomerAction::DefaultJob, $customer, $context)) {
            return [];
        }

        if ($viewer->isPm() || $viewer->isClientPm() || $viewer->isPmLight()) {
            return $this->getSimpleUsers($viewer);
        }

        if (! $viewer->isCoordinator()) {
            return [];
        }

        return $this->getLspUsers($customerId, $viewer);
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

        return $this->permissionAwareUserFetcher->fetchVisible($select, $viewer);
    }

    /**
     * It is impossible to create CoordinatorGroup User job for CoordinatorGroup User without CoordinatorGroup job
     *
     * @return UserData[]
     */
    private function getLspUsers(int $customerId, User $viewer): array
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
                    'coordinatorGroupCustomer' => CoordinatorGroupCustomerTable::TABLE_NAME,
                ],
                'coordinatorGroupCustomer.groupId = groupUser.groupId',
                []
            )
            ->where('coordinatorGroupCustomer.customerId = ?', $customerId)
        ;

        if ($viewer->isCoordinator()) {
            $coordinator = $this->jobCoordinatorRepository->getByUser($viewer);

            $select->where('groupUser.groupId = ?', $coordinator->group->getId());
        }

        return $this->permissionAwareUserFetcher->fetchVisible($select, $viewer);
    }
}
