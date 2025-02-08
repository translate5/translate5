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

use MittagQI\Translate5\Acl\Validation\RolesValidator;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Contract\UserCreateOperationInterface;
use MittagQI\Translate5\User\Exception\CustomerDoesNotBelongToUserException;
use MittagQI\Translate5\User\Exception\GuidAlreadyInUseException;
use MittagQI\Translate5\User\Exception\LoginAlreadyInUseException;
use MittagQI\Translate5\User\Exception\UserExceptionInterface;
use MittagQI\Translate5\User\Exception\UserIsNotAuthorisedToAssignRoleException;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\Operations\DTO\CreateUserDto;
use MittagQI\Translate5\User\Validation\UserCustomerAssociationValidator;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;
use ZfExtended_ValidateException;

class UserCreateOperation implements UserCreateOperationInterface
{
    public function __construct(
        private readonly UserCreateOperationInterface $operation,
        private readonly ZfExtended_AuthenticationInterface $authentication,
        private readonly UserRepository $userRepository,
        private readonly UserCustomerAssociationValidator $userCustomerAssociationValidator,
        private readonly RolesValidator $rolesValidator,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            \MittagQI\Translate5\User\Operations\UserCreateOperation::create(),
            ZfExtended_Authentication::getInstance(),
            new UserRepository(),
            UserCustomerAssociationValidator::create(),
            RolesValidator::create(),
        );
    }

    /**
     * @throws CustomerDoesNotBelongToUserException
     * @throws GuidAlreadyInUseException
     * @throws LoginAlreadyInUseException
     * @throws UserIsNotAuthorisedToAssignRoleException
     * @throws UserExceptionInterface
     * @throws ZfExtended_ValidateException
     */
    public function createUser(CreateUserDto $dto): User
    {
        $authUser = $this->userRepository->get($this->authentication->getUserId());

        $this->userCustomerAssociationValidator->assertUserCanAssignCustomers($authUser, $dto->customers);

        $this->rolesValidator->assertUserCanSetRoles($authUser, $dto->roles);

        return $this->operation->createUser($dto);
    }
}
