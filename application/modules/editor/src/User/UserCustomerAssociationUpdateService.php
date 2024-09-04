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

namespace MittagQI\Translate5\User;

use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\ActionAssert\Action;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\FeasibilityExceptionInterface;
use MittagQI\Translate5\User\ActionAssert\Feasibility\UserActionFeasibilityAssert;
use MittagQI\Translate5\User\Exception\CustomerDoesNotBelongToUserException;
use MittagQI\Translate5\User\Validation\UserCustomerAssociationValidator;
use ZfExtended_Models_User as User;

final class UserCustomerAssociationUpdateService
{
    public function __construct(
        private readonly UserCustomerAssociationValidator $userCustomerAssociationValidator,
        private readonly UserRepository $userRepository,
        private readonly UserActionFeasibilityAssert $feasibilityAssert,
    ) {
    }

    public static function create(): self
    {
        return new self(
            UserCustomerAssociationValidator::create(),
            new UserRepository(),
            UserActionFeasibilityAssert::create(),
        );
    }

    /**
     * @param int[] $associatedCustomerIds
     * @throws FeasibilityExceptionInterface
     * @throws CustomerDoesNotBelongToUserException
     */
    public function updateAssociatedCustomersFor(
        User $user,
        array $associatedCustomerIds,
        ?User $authManager = null
    ): void {
        $this->feasibilityAssert->assertAllowed(Action::UPDATE, $user);

        $customerRepository = new CustomerRepository();

        $managerCustomers = $authManager?->getCustomersArray() ?: [];
        $currentUserCustomers = $user->getCustomersArray();

        $customers = $customerRepository->getList(...$associatedCustomerIds);

        if ($authManager?->isClientRestricted()) {
            $this->userCustomerAssociationValidator->assertCustomersAreSubsetForUser($authManager, $customers);
        }

        $this->userCustomerAssociationValidator->assertCustomersMayBeAssociatedWithUser($customers, $user);

        // Process deleted customers
        foreach ($currentUserCustomers as $customer) {
            if (in_array($customer, $associatedCustomerIds, true)) {
                continue;
            }

            if (! $authManager->isClientRestricted()) {
                continue;
            }

            if (in_array($customer, $managerCustomers, true)) {
                continue;
            }

            // customer is not really deleted but is out of manager visibility so we add it to associated customers
            $associatedCustomerIds[] = $customer;
        }

        $user->assignCustomers($associatedCustomerIds);

        $this->userRepository->save($user);
    }
}
