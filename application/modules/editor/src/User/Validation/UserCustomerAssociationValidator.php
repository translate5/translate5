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

namespace MittagQI\Translate5\User\Validation;

use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\CoordinatorGroup\Exception\CustomerDoesNotBelongToCoordinatorGroupException;
use MittagQI\Translate5\CoordinatorGroup\Validation\CoordinatorGroupCustomerAssociationValidator;
use MittagQI\Translate5\Customer\ActionAssert\CustomerAction;
use MittagQI\Translate5\Customer\ActionAssert\CustomerActionPermissionAssert;
use MittagQI\Translate5\Repository\CoordinatorGroupUserRepository;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\User\Exception\CustomerDoesNotBelongToUserException;
use MittagQI\Translate5\User\Model\User;

class UserCustomerAssociationValidator
{
    public function __construct(
        private readonly CoordinatorGroupUserRepository $coordinatorGroupUserRepository,
        private readonly CoordinatorGroupCustomerAssociationValidator $coordinatorGroupCustomerAssociationValidator,
        private readonly CustomerRepository $customerRepository,
        private readonly ActionPermissionAssertInterface $customerPermissionAssert,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            CoordinatorGroupUserRepository::create(),
            CoordinatorGroupCustomerAssociationValidator::create(),
            new CustomerRepository(),
            CustomerActionPermissionAssert::create(),
        );
    }

    /**
     * @throws CustomerDoesNotBelongToCoordinatorGroupException
     */
    public function assertCustomersMayBeAssociatedWithUser(User $user, int ...$customerIds): void
    {
        if (empty($customerIds)) {
            return;
        }

        $groupUser = $this->coordinatorGroupUserRepository->findByUser($user);

        if (null !== $groupUser) {
            $this->coordinatorGroupCustomerAssociationValidator->assertCustomersAreSubsetForCoordinatorGroup(
                (int) $groupUser->group->getId(),
                ...$customerIds
            );
        }
    }

    /**
     * @param int[] $customerIds
     * @throws CustomerDoesNotBelongToUserException
     */
    public function assertUserCanAssignCustomers(User $authUser, array $customerIds): void
    {
        $context = new PermissionAssertContext($authUser);
        $customers = $this->customerRepository->getList(...$customerIds);

        foreach ($customers as $customer) {
            try {
                $this->customerPermissionAssert->assertGranted(CustomerAction::Read, $customer, $context);
            } catch (PermissionExceptionInterface) {
                throw new CustomerDoesNotBelongToUserException(
                    (int) $customer->getId(),
                    $context->actor->getUserGuid()
                );
            }
        }
    }
}
