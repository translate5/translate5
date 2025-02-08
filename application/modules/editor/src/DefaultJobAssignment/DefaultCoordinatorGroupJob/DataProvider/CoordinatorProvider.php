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

namespace MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\DataProvider;

use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\CoordinatorGroup\Exception\CustomerDoesNotBelongToJobCoordinatorException;
use MittagQI\Translate5\CoordinatorGroup\Model\Db\CoordinatorGroupTable;
use MittagQI\Translate5\CoordinatorGroup\Model\Db\CoordinatorGroupUserTable;
use MittagQI\Translate5\CoordinatorGroup\Validation\CoordinatorGroupCustomerAssociationValidator;
use MittagQI\Translate5\Customer\ActionAssert\CustomerAction;
use MittagQI\Translate5\Customer\ActionAssert\CustomerActionPermissionAssert;
use MittagQI\Translate5\Repository\CustomerRepository;
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
        private readonly ActionPermissionAssertInterface $customerPermissionAssert,
        private readonly CustomerRepository $customerRepository,
        private readonly PermissionAwareUserFetcher $permissionAwareUserFetcher,
        private readonly CoordinatorGroupCustomerAssociationValidator $coordinatorGroupCustomerAssociationValidator,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_Db_Table::getDefaultAdapter(),
            CustomerActionPermissionAssert::create(),
            CustomerRepository::create(),
            PermissionAwareUserFetcher::create(),
            CoordinatorGroupCustomerAssociationValidator::create(),
        );
    }

    /**
     * @return Coordinator[]
     */
    public function getCoordinatorsOfGroup(int $groupId, User $viewer): array
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
            ->where('user.roles LIKE ?', '%' . Roles::JOB_COORDINATOR . '%')
            ->where('groupUser.groupId = ?', $groupId)
        ;

        $coordinators = [];

        foreach ($this->permissionAwareUserFetcher->fetchVisible($select, $viewer) as $coordinator) {
            $coordinators[] = [
                'userGuid' => $coordinator['userGuid'],
                'longUserName' => $coordinator['longUserName'],
            ];
        }

        return $coordinators;
    }

    /**
     * @return Coordinator[]
     */
    public function getPossibleCoordinators(int $customerId, User $viewer): array
    {
        $context = new PermissionAssertContext($viewer);

        if ($viewer->isAdmin()) {
            return $this->filterCoordinatorsByCustomer(
                array_merge(
                    $this->getDirectCoordinators($viewer),
                    $this->getSubCoordinators($viewer)
                ),
                $customerId
            );
        }

        $customer = $this->customerRepository->get($customerId);

        if (! $this->customerPermissionAssert->isGranted(CustomerAction::DefaultJob, $customer, $context)) {
            return [];
        }

        if ($viewer->isPm() || $viewer->isClientPm()) {
            return $this->filterCoordinatorsByCustomer(
                $this->getDirectCoordinators($viewer),
                $customerId
            );
        }

        if (! $viewer->isCoordinator()) {
            return [];
        }

        return $this->filterCoordinatorsByCustomer(
            $this->getSubCoordinators($viewer),
            $customerId
        );
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
     * Fetch coordinators of sub Coordinator Groups if their parent Coordinator Group has job in a task
     * It is impossible to create Coordinator Group job for sub Group without parent Group job
     *
     * @return array{userId: int, userGuid: string, longUserName: string}[]
     */
    private function getSubCoordinators(User $viewer): array
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
                    'coordinatorGroup' => CoordinatorGroupTable::TABLE_NAME,
                ],
                'groupUser.groupId = coordinatorGroup.id',
                []
            )
            ->where('user.roles LIKE ?', '%' . Roles::JOB_COORDINATOR . '%')
            ->where('coordinatorGroup.parentId IS NOT NULL')
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
                    'coordinatorGroup' => CoordinatorGroupTable::TABLE_NAME,
                ],
                'groupUser.groupId = coordinatorGroup.id',
                []
            )
            ->where('user.roles LIKE ?', '%' . Roles::JOB_COORDINATOR . '%')
            ->where('coordinatorGroup.parentId IS NULL')
        ;

        return $this->permissionAwareUserFetcher->fetchVisible($select, $viewer);
    }
}
