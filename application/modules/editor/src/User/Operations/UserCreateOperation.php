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

namespace MittagQI\Translate5\User\Operations;

use MittagQI\Translate5\LSP\Operations\LspUserCreateOperation;
use MittagQI\Translate5\Repository\Contract\LspRepositoryInterface;
use MittagQI\Translate5\Repository\LspRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Contract\UserAssignCustomersOperationInterface;
use MittagQI\Translate5\User\Contract\UserSetParentIdsOperationInterface;
use MittagQI\Translate5\User\Contract\UserSetRolesOperationInterface;
use MittagQI\Translate5\User\DTO\CreateUserDto;
use MittagQI\Translate5\User\Exception\GuidAlreadyInUseException;
use MittagQI\Translate5\User\Exception\LoginAlreadyInUseException;
use MittagQI\Translate5\User\Exception\LspMustBeProvidedInJobCoordinatorCreationProcessException;
use MittagQI\Translate5\User\Mail\ResetPasswordEmail;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_User as User;
use ZfExtended_ValidateException;

final class UserCreateOperation
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserSetParentIdsOperationInterface $setParentIds,
        private readonly UserSetRolesOperationInterface $setRoles,
        private readonly UserSetPasswordOperation $setPassword,
        private readonly UserAssignCustomersOperationInterface $assignCustomers,
        private readonly ResetPasswordEmail $resetPasswordEmail,
        private readonly LspUserCreateOperation $lspUserCreate,
        private readonly LspRepositoryInterface $lspRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            new UserRepository(),
            UserSetParentIdsOperation::create(),
            UserSetRolesOperation::create(),
            UserSetPasswordOperation::create(),
            UserAssignCustomersOperation::create(),
            ResetPasswordEmail::create(),
            LspUserCreateOperation::create(),
            LspRepository::create(),
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function createWithAuthentication(): self
    {
        return new self(
            new UserRepository(),
            WithAuthentication\UserSetParentIdsOperation::create(),
            WithAuthentication\UserSetRolesOperation::create(),
            UserSetPasswordOperation::create(),
            WithAuthentication\UserAssignCustomersOperation::create(),
            ResetPasswordEmail::create(),
            LspUserCreateOperation::create(),
            LspRepository::create(),
        );
    }

    /**
     * @throws GuidAlreadyInUseException
     * @throws LoginAlreadyInUseException
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_ValidateException
     */
    public function createUser(CreateUserDto $dto): User
    {
        $user = $this->userRepository->getEmptyModel();
        $user->setInitialFields($dto);

        if (! empty($dto->password)) {
            $this->setPassword->setPassword($user, $dto->password);
        }

        if (null !== $dto->parentId) {
            $this->setParentIds->setParentIds($user, $dto->parentId);
        }

        $this->setRoles->setRoles($user, $dto->roles);
        $this->assignCustomers->assignCustomers($user, $dto->customers);

        $user->validate();

        if ($user->isCoordinator() && null === $dto->lsp) {
            throw new LspMustBeProvidedInJobCoordinatorCreationProcessException();
        }

        $lsp = null;

        if (null !== $dto->lsp) {
            $lsp = $this->lspRepository->get($dto->lsp);
        }

        $this->userRepository->save($user);

        if (null !== $lsp) {
            $this->lspUserCreate->createLspUser($lsp, $user);
        }

        if (empty($dto->password)) {
            $this->resetPasswordEmail->sendTo($user);
        }

        return $user;
    }
}
