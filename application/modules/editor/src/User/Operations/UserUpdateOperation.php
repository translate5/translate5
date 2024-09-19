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

use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\FeasibilityExceptionInterface;
use MittagQI\Translate5\User\ActionAssert\Feasibility\UserActionFeasibilityAssert;
use MittagQI\Translate5\User\ActionAssert\Feasibility\UserActionFeasibilityAssertInterface;
use MittagQI\Translate5\User\Contract\UserAssignCustomersOperationInterface;
use MittagQI\Translate5\User\Contract\UserSetParentIdsOperationInterface;
use MittagQI\Translate5\User\Contract\UserSetRolesOperationInterface;
use MittagQI\Translate5\User\Contract\UserUpdateOperationInterface;
use MittagQI\Translate5\User\DTO\UpdateUserDto;
use MittagQI\Translate5\User\Exception\GuidAlreadyInUseException;
use MittagQI\Translate5\User\Exception\LoginAlreadyInUseException;
use MittagQI\Translate5\User\Exception\UserExceptionInterface;
use MittagQI\Translate5\User\Mail\ResetPasswordEmail;
use MittagQI\Translate5\User\Model\User;
use ZfExtended_ValidateException;

final class UserUpdateOperation implements UserUpdateOperationInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserActionFeasibilityAssertInterface $userActionFeasibilityChecker,
        private readonly UserSetParentIdsOperationInterface $setParentIds,
        private readonly UserSetRolesOperationInterface $setRoles,
        private readonly UserSetPasswordOperation $setPassword,
        private readonly UserAssignCustomersOperationInterface $assignCustomers,
        private readonly ResetPasswordEmail $resetPasswordEmail,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            new UserRepository(),
            UserActionFeasibilityAssert::create(),
            UserSetParentIdsOperation::create(),
            UserSetRolesOperation::create(),
            UserSetPasswordOperation::create(),
            UserAssignCustomersOperation::create(),
            ResetPasswordEmail::create(),
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public static function createWithAuthentication(): self
    {
        return new self(
            new UserRepository(),
            UserActionFeasibilityAssert::create(),
            WithAuthentication\UserSetParentIdsOperation::create(),
            WithAuthentication\UserSetRolesOperation::create(),
            UserSetPasswordOperation::create(),
            WithAuthentication\UserAssignCustomersOperation::create(),
            ResetPasswordEmail::create(),
        );
    }

    /**
     * @throws FeasibilityExceptionInterface
     * @throws GuidAlreadyInUseException
     * @throws LoginAlreadyInUseException
     * @throws UserExceptionInterface
     * @throws ZfExtended_ValidateException
     */
    public function updateUser(User $user, UpdateUserDto $dto): void
    {
        $this->userActionFeasibilityChecker->assertAllowed(Action::UPDATE, $user);

        if (null !== $dto->email) {
            $user->setEmail($dto->email);
        }

        if (null !== $dto->login) {
            $user->setLogin($dto->login);
        }

        if (null !== $dto->firstName) {
            $user->setFirstName($dto->firstName);
        }

        if (null !== $dto->surName) {
            $user->setSurName($dto->surName);
        }

        if (null !== $dto->gender) {
            $user->setGender($dto->gender);
        }

        if (null !== $dto->locale) {
            $user->setLocale($dto->locale);
        }

        if (null !== $dto->password) {
            $this->setPassword->setPassword($user, $dto->password->password);
        }

        if (null !== $dto->roles) {
            $this->setRoles->setRoles($user, $dto->roles);
        }

        if (null !== $dto->parentId) {
            $this->setParentIds->setParentIds($user, $dto->parentId);
        }

        if (null !== $dto->customers) {
            $this->assignCustomers->assignCustomers($user, $dto->customers);
        }

        $user->validate();

        $this->userRepository->save($user);

        if (null === $dto->password) {
            $this->resetPasswordEmail->sendTo($user);
        }
    }
}
