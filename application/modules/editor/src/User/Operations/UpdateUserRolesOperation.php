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
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Feasibility\ActionFeasibilityAssertInterface;
use MittagQI\Translate5\ActionAssert\Feasibility\Asserts\FeasibilityAssertInterface;
use MittagQI\Translate5\ActionAssert\Feasibility\Exception\FeasibilityExceptionInterface;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Asserts\CoordinatorCanBeDeletedAssert;
use MittagQI\Translate5\User\ActionAssert\Feasibility\UserActionFeasibilityAssert;
use MittagQI\Translate5\User\Contract\UpdateUserRolesOperationInterface;
use MittagQI\Translate5\User\Contract\UserRolesSetterInterface;
use MittagQI\Translate5\User\Exception\CantRemoveCoordinatorRoleFromUserException;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\Operations\Setters\UserRolesSetter;

final class UpdateUserRolesOperation implements UpdateUserRolesOperationInterface
{
    /**
     * @param ActionFeasibilityAssertInterface<User> $userActionFeasibilityChecker
     * @param FeasibilityAssertInterface<User> $coordinatorCanBeDeletedAssert
     */
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ActionFeasibilityAssertInterface $userActionFeasibilityChecker,
        private readonly FeasibilityAssertInterface $coordinatorCanBeDeletedAssert,
        private readonly UserRolesSetterInterface $setRoles,
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
            CoordinatorCanBeDeletedAssert::create(),
            UserRolesSetter::create(),
        );
    }

    public function updateRoles(User $user, array $roles): void
    {
        $this->userActionFeasibilityChecker->assertAllowed(Action::Update, $user);

        $oldRoles = $user->getRoles();
        $deletedRoles = array_diff($oldRoles, $roles);

        if (in_array(Roles::JOB_COORDINATOR, $deletedRoles, true)) {
            try {
                $this->coordinatorCanBeDeletedAssert->assertAllowed($user);
            } catch (FeasibilityExceptionInterface $e) {
                throw new CantRemoveCoordinatorRoleFromUserException(previous: $e);
            }
        }

        $this->setRoles->setExpendRolesService($user, $roles);

        $user->validate();

        $this->userRepository->save($user);
    }
}
