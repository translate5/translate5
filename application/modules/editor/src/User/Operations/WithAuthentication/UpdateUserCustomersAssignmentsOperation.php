<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\ActionAssert\Permission\UserActionPermissionAssert;
use MittagQI\Translate5\User\ActionAssert\UserAction;
use MittagQI\Translate5\User\Contract\UpdateUserCustomersAssignmentsOperationInterface;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\Validation\UserCustomerAssociationValidator;
use Zend_Registry;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;
use ZfExtended_Logger;

class UpdateUserCustomersAssignmentsOperation implements UpdateUserCustomersAssignmentsOperationInterface
{
    public function __construct(
        private readonly ActionPermissionAssertInterface $permissionAssert,
        private readonly UpdateUserCustomersAssignmentsOperationInterface $operation,
        private readonly ZfExtended_AuthenticationInterface $authentication,
        private readonly UserRepository $userRepository,
        private readonly UserCustomerAssociationValidator $userCustomerAssociationValidator,
        private readonly ZfExtended_Logger $logger,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            UserActionPermissionAssert::create(),
            \MittagQI\Translate5\User\Operations\UpdateUserCustomersAssignmentsOperation::create(),
            ZfExtended_Authentication::getInstance(),
            new UserRepository(),
            UserCustomerAssociationValidator::create(),
            Zend_Registry::get('logger')->cloneMe('user.update'),
        );
    }

    public function updateCustomers(User $user, array $customers, bool $forceUnassignment = false): void
    {
        $authUser = $this->userRepository->get($this->authentication->getUserId());

        $this->checkPermissions($user, $authUser);

        $this->userCustomerAssociationValidator->assertUserCanAssignCustomers($authUser, $customers);

        $authUserCustomers = $authUser->getCustomersArray();
        $currentUserCustomers = $user->getCustomersArray();

        foreach ($currentUserCustomers as $customer) {
            if (in_array($customer, $customers, true)) {
                continue;
            }

            if (! $authUser->isClientRestricted()) {
                continue;
            }

            if (in_array($customer, $authUserCustomers, true)) {
                continue;
            }

            // customer is not really deleted but is out of manager visibility so we add it to associated customers
            $customers[] = $customer;
        }

        $this->operation->updateCustomers($user, $customers, $forceUnassignment);
    }

    /**
     * @throws PermissionExceptionInterface
     */
    public function checkPermissions(User $user, User $authUser): void
    {
        try {
            $this->permissionAssert->assertGranted(
                UserAction::Update,
                $user,
                new PermissionAssertContext($authUser)
            );

            $this->logger->info(
                'E1637',
                'Audit: {message}',
                [
                    'message' => sprintf(
                        'Attempt to update customers for User (guid: "%s") by AuthUser (guid: %s) was granted',
                        $user->getUserGuid(),
                        $authUser->getUserGuid()
                    ),
                    'user' => $user->getUserGuid(),
                    'authUser' => $authUser->getLogin(),
                    'authUserGuid' => $authUser->getUserGuid(),
                ]
            );
        } catch (PermissionExceptionInterface $e) {
            $this->logger->warn(
                'E1637',
                'Audit: {message}',
                [
                    'message' => sprintf(
                        'Attempt to update customers for User (guid: "%s") by AuthUser (guid: %s) was not granted',
                        $user->getUserGuid(),
                        $authUser->getUserGuid()
                    ),
                    'user' => $user->getLogin(),
                    'authUser' => $authUser->getLogin(),
                    'authUserGuid' => $authUser->getUserGuid(),
                    'reason' => $e::class,
                ]
            );

            throw $e;
        }
    }
}
