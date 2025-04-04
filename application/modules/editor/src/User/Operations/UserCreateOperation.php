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

use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\CoordinatorGroup\Operations\CoordinatorGroupUserCreateOperation;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupRepositoryInterface;
use MittagQI\Translate5\Repository\Contract\CoordinatorGroupUserRepositoryInterface;
use MittagQI\Translate5\Repository\CoordinatorGroupRepository;
use MittagQI\Translate5\Repository\CoordinatorGroupUserRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Contract\UserAssignCustomersOperationInterface;
use MittagQI\Translate5\User\Contract\UserCreateOperationInterface;
use MittagQI\Translate5\User\Contract\UserRolesSetterInterface;
use MittagQI\Translate5\User\Exception\CoordinatorGroupMustBeProvidedInJobCoordinatorCreationProcessException;
use MittagQI\Translate5\User\Exception\CustomerNotProvidedOnClientRestrictedUserCreationException;
use MittagQI\Translate5\User\Exception\GuidAlreadyInUseException;
use MittagQI\Translate5\User\Exception\LoginAlreadyInUseException;
use MittagQI\Translate5\User\Exception\UserExceptionInterface;
use MittagQI\Translate5\User\Mail\ResetPasswordEmail;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\Operations\DTO\CreateUserDto;
use MittagQI\Translate5\User\Operations\Setters\UserPasswordSetter;
use MittagQI\Translate5\User\Operations\Setters\UserRolesSetter;
use Throwable;
use ZfExtended_ValidateException;

final class UserCreateOperation implements UserCreateOperationInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserRolesSetterInterface $setRoles,
        private readonly UserPasswordSetter $setPassword,
        private readonly UserAssignCustomersOperationInterface $assignCustomers,
        private readonly CoordinatorGroupUserCreateOperation $coordinatorGroupUserCreateOperation,
        private readonly ResetPasswordEmail $resetPasswordEmail,
        private readonly CoordinatorGroupRepositoryInterface $coordinatorGroupRepository,
        private readonly CoordinatorGroupUserRepositoryInterface $coordinatorGroupUserRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            new UserRepository(),
            UserRolesSetter::create(),
            UserPasswordSetter::create(),
            UserAssignCustomersOperation::create(),
            CoordinatorGroupUserCreateOperation::create(),
            ResetPasswordEmail::create(),
            CoordinatorGroupRepository::create(),
            CoordinatorGroupUserRepository::create(),
        );
    }

    /**
     * @throws GuidAlreadyInUseException
     * @throws LoginAlreadyInUseException
     * @throws UserExceptionInterface
     * @throws ZfExtended_ValidateException
     */
    public function createUser(CreateUserDto $dto): User
    {
        $user = new User();
        $user->setInitialFields($dto);

        if (! empty($dto->password)) {
            $this->setPassword->setPassword($user, $dto->password);
        }

        $user->validate();

        if ([] !== array_intersect($dto->roles, Roles::getRolesRequireClient()) && empty($dto->customers)) {
            throw new CustomerNotProvidedOnClientRestrictedUserCreationException();
        }

        if (in_array(Roles::JOB_COORDINATOR, $dto->roles) && null === $dto->coordinatorGroup) {
            throw new CoordinatorGroupMustBeProvidedInJobCoordinatorCreationProcessException();
        }

        $coordinatorGroup = null;

        if (null !== $dto->coordinatorGroup) {
            $coordinatorGroup = $this->coordinatorGroupRepository->get($dto->coordinatorGroup);
        }

        $this->userRepository->save($user);

        $groupUser = null;

        if (null !== $coordinatorGroup) {
            $groupUser = $this->coordinatorGroupUserCreateOperation
                ->createCoordinatorGroupUser($coordinatorGroup, $user);
        }

        try {
            $this->setRoles->setExpendRolesService($user, $dto->roles);
            $this->userRepository->save($user);
            $this->assignCustomers->assignCustomers($user, ...$dto->customers);
        } catch (Throwable $e) {
            if ($groupUser) {
                $this->coordinatorGroupUserRepository->delete($groupUser);
            }

            $this->userRepository->delete($user);

            throw $e;
        }

        if (empty($dto->password)) {
            $this->resetPasswordEmail->sendTo($user);
        }

        return $user;
    }
}
