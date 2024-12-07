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

namespace MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\Operation\WithAuthentication;

use editor_Models_UserAssocDefault as DefaultUserJob;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Customer\ActionAssert\CustomerAction;
use MittagQI\Translate5\Customer\ActionAssert\CustomerActionPermissionAssert;
use MittagQI\Translate5\DefaultJobAssignment\Contract\CreateDefaultUserJobOperationInterface;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\Operation\DTO\NewDefaultUserJobDto;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\ActionAssert\Permission\UserActionPermissionAssert;
use MittagQI\Translate5\User\ActionAssert\UserAction;
use MittagQI\Translate5\User\Exception\InexistentUserException;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;
use ZfExtended_NotFoundException;

class CreateDefaultUserJobOperation implements CreateDefaultUserJobOperationInterface
{
    public function __construct(
        private readonly CreateDefaultUserJobOperationInterface $operation,
        private readonly ActionPermissionAssertInterface $userPermissionAssert,
        private readonly ActionPermissionAssertInterface $customerPermissionAssert,
        private readonly ZfExtended_AuthenticationInterface $authentication,
        private readonly UserRepository $userRepository,
        private readonly CustomerRepository $customerRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            \MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\Operation\CreateDefaultUserJobOperation::create(),
            UserActionPermissionAssert::create(),
            CustomerActionPermissionAssert::create(),
            ZfExtended_Authentication::getInstance(),
            new UserRepository(),
            CustomerRepository::create(),
        );
    }

    public function assignJob(NewDefaultUserJobDto $dto): DefaultUserJob
    {
        try {
            $authUser = $this->userRepository->get($this->authentication->getUserId());
        } catch (InexistentUserException) {
            throw new ZfExtended_NotFoundException();
        }

        $context = new PermissionAssertContext($authUser);
        $customer = $this->customerRepository->get($dto->customerId);
        $user = $this->userRepository->getByGuid($dto->userGuid);

        $this->customerPermissionAssert->assertGranted(CustomerAction::DefaultJob, $customer, $context);
        $this->userPermissionAssert->assertGranted(UserAction::Read, $user, $context);

        return $this->operation->assignJob($dto);
    }
}
