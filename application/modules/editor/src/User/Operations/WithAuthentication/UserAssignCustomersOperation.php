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

namespace MittagQI\Translate5\User\Operations\WithAuthentication;

use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Customer\ActionAssert\CustomerActionPermissionAssert;
use MittagQI\Translate5\LSP\Exception\CustomerDoesNotBelongToLspException;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\ActionAssert\Permission\UserActionPermissionAssert;
use MittagQI\Translate5\User\Contract\UserAssignCustomersOperationInterface;
use MittagQI\Translate5\User\Exception\CustomerDoesNotBelongToUserException;
use MittagQI\Translate5\User\Model\User;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;

/**
 * Should not be used directly, use:
 * @see UserCreateOperation
 */
final class UserAssignCustomersOperation implements UserAssignCustomersOperationInterface
{
    public function __construct(
        private readonly ActionPermissionAssertInterface $userPermissionAssert,
        private readonly UserAssignCustomersOperationInterface $operation,
        private readonly ZfExtended_AuthenticationInterface $authentication,
        private readonly UserRepository $userRepository,
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
            UserActionPermissionAssert::create(),
            \MittagQI\Translate5\User\Operations\UserAssignCustomersOperation::create(),
            ZfExtended_Authentication::getInstance(),
            new UserRepository(),
            new CustomerRepository(),
            CustomerActionPermissionAssert::create(),
        );
    }

    /**
     * @param int[] $associatedCustomerIds
     * @throws CustomerDoesNotBelongToUserException
     * @throws CustomerDoesNotBelongToLspException
     */
    public function assignCustomers(User $user, array $associatedCustomerIds): void
    {
        $authUser = $this->userRepository->get($this->authentication->getUserId());

        $this->userPermissionAssert->assertGranted(
            Action::DELETE,
            $user,
            new PermissionAssertContext($authUser)
        );

        $customers = $this->customerRepository->getList(...$associatedCustomerIds);

        foreach ($customers as $customer) {
            try {
                $this->customerPermissionAssert->assertGranted(
                    Action::READ,
                    $customer,
                    new PermissionAssertContext($authUser)
                );
            } catch (PermissionExceptionInterface) {
                throw new CustomerDoesNotBelongToUserException((int) $customer->getId(), $user->getUserGuid());
            }
        }

        $this->operation->assignCustomers($user, $associatedCustomerIds);
    }
}
