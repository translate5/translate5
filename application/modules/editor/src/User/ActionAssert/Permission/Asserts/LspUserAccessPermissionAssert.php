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

namespace MittagQI\Translate5\User\ActionAssert\Permission\Asserts;

use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Permission\Asserts\PermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\LSP\Exception\CantCreateCoordinatorFromUserException;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\LSP\LspUser;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\User\ActionAssert\Permission\Exception\NotAccessibleLspUserException;
use MittagQI\Translate5\User\Model\User;

/**
 * @implements PermissionAssertInterface<User>
 */
final class LspUserAccessPermissionAssert implements PermissionAssertInterface
{
    public function __construct(
        private readonly LspUserRepositoryInterface $lspUserRepository,
        private readonly JobCoordinatorRepository $coordinatorRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            new LspUserRepository(),
            JobCoordinatorRepository::create(),
        );
    }

    public function supports(Action $action): bool
    {
        return in_array($action, [Action::UPDATE, Action::DELETE, Action::READ], true);
    }

    /**
     * Restrict access to LSP users
     *
     * {@inheritDoc}
     */
    public function assertGranted(object $object, PermissionAssertContext $context): void
    {
        $manager = $context->manager;

        if ($manager->getId() === $object->getId()) {
            return;
        }

        $roles = $manager->getRoles();

        if (array_intersect([Roles::ADMIN, Roles::SYSTEMADMIN], $roles)) {
            return;
        }

        $lspUser = $this->lspUserRepository->findByUser($object);

        if (null === $lspUser) {
            return;
        }

        if (in_array(Roles::PM, $roles, true)) {
            if ($this->isGrantedForPm($lspUser)) {
                return;
            }

            throw new NotAccessibleLspUserException($lspUser);
        }

        $managerCoordinator = $this->coordinatorRepository->findByUser($manager);

        if (null === $managerCoordinator) {
            throw new NotAccessibleLspUserException($lspUser);
        }

        if (! $managerCoordinator->isSupervisorOf($lspUser)) {
            throw new NotAccessibleLspUserException($lspUser);
        }
    }

    private function isGrantedForPm(LspUser $lspUser): bool
    {
        if (! $lspUser->lsp->isDirectLsp()) {
            return false;
        }

        try {
            JobCoordinator::fromLspUser($lspUser);

            return true;
        } catch (CantCreateCoordinatorFromUserException) {
            return false;
        }
    }
}
