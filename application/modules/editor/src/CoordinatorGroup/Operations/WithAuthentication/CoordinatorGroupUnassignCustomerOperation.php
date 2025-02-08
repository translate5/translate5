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

namespace MittagQI\Translate5\CoordinatorGroup\Operations\WithAuthentication;

use editor_Models_Customer_Customer as Customer;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\CoordinatorGroup\ActionAssert\Permission\CoordinatorGroupAction;
use MittagQI\Translate5\CoordinatorGroup\ActionAssert\Permission\CoordinatorGroupActionPermissionAssert;
use MittagQI\Translate5\CoordinatorGroup\ActionAssert\Permission\Exception\NoAccessToCoordinatorGroupException;
use MittagQI\Translate5\CoordinatorGroup\Contract\CoordinatorGroupUnassignCustomerOperationInterface;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\Customer\ActionAssert\CustomerAction;
use MittagQI\Translate5\Customer\ActionAssert\CustomerActionPermissionAssert;
use MittagQI\Translate5\Customer\Exception\NoAccessToCustomerException;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Exception\InexistentUserException;
use Zend_Registry;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;
use ZfExtended_Logger;
use ZfExtended_NotAuthenticatedException;

final class CoordinatorGroupUnassignCustomerOperation implements CoordinatorGroupUnassignCustomerOperationInterface
{
    /**
     * @param ActionPermissionAssertInterface<CoordinatorGroupAction, CoordinatorGroup> $coordinatorGroupActionPermissionAssert
     * @param ActionPermissionAssertInterface<CustomerAction, Customer> $customerActionPermissionAssert
     */
    public function __construct(
        private readonly CoordinatorGroupUnassignCustomerOperationInterface $generalOperation,
        private readonly ActionPermissionAssertInterface $coordinatorGroupActionPermissionAssert,
        private readonly ActionPermissionAssertInterface $customerActionPermissionAssert,
        private readonly ZfExtended_AuthenticationInterface $authentication,
        private readonly UserRepository $userRepository,
        private readonly ZfExtended_Logger $logger,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            \MittagQI\Translate5\CoordinatorGroup\Operations\CoordinatorGroupUnassignCustomerOperation::create(),
            CoordinatorGroupActionPermissionAssert::create(),
            CustomerActionPermissionAssert::create(),
            ZfExtended_Authentication::getInstance(),
            new UserRepository(),
            Zend_Registry::get('logger')->cloneMe('coordinatorGroup.customer.unassign'),
        );
    }

    /**
     * @throws NoAccessToCoordinatorGroupException
     * @throws NoAccessToCustomerException
     * @throws PermissionExceptionInterface
     * @throws ZfExtended_NotAuthenticatedException
     */
    public function unassignCustomer(CoordinatorGroup $group, Customer $customer): void
    {
        $this->assertPermissionsGranted($group, $customer);

        $this->generalOperation->unassignCustomer($group, $customer);
    }

    /**
     * @throws NoAccessToCoordinatorGroupException
     * @throws NoAccessToCustomerException
     * @throws PermissionExceptionInterface
     * @throws ZfExtended_NotAuthenticatedException
     */
    public function forceUnassignCustomer(CoordinatorGroup $group, Customer $customer): void
    {
        $this->assertPermissionsGranted($group, $customer);

        $this->generalOperation->forceUnassignCustomer($group, $customer);
    }

    private function assertPermissionsGranted(CoordinatorGroup $group, Customer $customer): void
    {
        try {
            $authUser = $this->userRepository->get($this->authentication->getUserId());
        } catch (InexistentUserException) {
            throw new ZfExtended_NotAuthenticatedException();
        }

        try {
            $context = new PermissionAssertContext($authUser);

            $this->coordinatorGroupActionPermissionAssert->assertGranted(CoordinatorGroupAction::Update, $group, $context);
            $this->customerActionPermissionAssert->assertGranted(CustomerAction::Read, $customer, $context);

            $this->logger->info(
                'E1637',
                'Audit: {message}',
                [
                    'message' => sprintf(
                        'Attempt to unassign Customer (number: "%s") from Coordinator Group (name: %s) by AuthUser (guid: %s) was granted',
                        $customer->getNumber(),
                        $group->getName(),
                        $authUser->getUserGuid()
                    ),
                    'customerNumber' => $customer->getNumber(),
                    'customerId' => $customer->getId(),
                    'coordinatorGroup' => $group->getName(),
                    'coordinatorGroupId' => $group->getId(),
                    'authUserGuid' => $authUser->getUserGuid(),
                ]
            );
        } catch (PermissionExceptionInterface $e) {
            $this->logger->warn(
                'E1637',
                'Audit: {message}',
                [
                    'message' => sprintf(
                        'Attempt to unassign Customer (number: "%s") from Coordinator Group (name: %s) by AuthUser (guid: %s) was not granted',
                        $customer->getNumber(),
                        $group->getName(),
                        $authUser->getUserGuid()
                    ),
                    'customerNumber' => $customer->getNumber(),
                    'customerId' => $customer->getId(),
                    'coordinatorGroup' => $group->getName(),
                    'coordinatorGroupId' => $group->getId(),
                    'authUserGuid' => $authUser->getUserGuid(),
                    'reason' => $e::class,
                ]
            );

            throw $e;
        }
    }
}
