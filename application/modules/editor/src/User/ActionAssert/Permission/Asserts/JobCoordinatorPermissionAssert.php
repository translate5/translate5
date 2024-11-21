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

use BackedEnum;
use MittagQI\Translate5\ActionAssert\Permission\Asserts\PermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\User\ActionAssert\Permission\Exception\NoAccessToUserException;
use MittagQI\Translate5\User\Model\User;

/**
 * @implements PermissionAssertInterface<User>
 */
final class JobCoordinatorPermissionAssert implements PermissionAssertInterface
{
    public function __construct(
        private readonly JobCoordinatorRepository $coordinatorRepository,
        private readonly LspUserRepositoryInterface $lspUserRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            JobCoordinatorRepository::create(),
            LspUserRepository::create(),
        );
    }

    public function supports(BackedEnum $action): bool
    {
        return true;
    }

    /**
     * Coordinator allowed to manage only his LSP users and Coordinators of his sub LSPs
     *
     * {@inheritDoc}
     */
    public function assertGranted(BackedEnum $action, object $object, PermissionAssertContext $context): void
    {
        if ($object->getId() === $context->authUser->getId()) {
            return;
        }

        $authCoordinator = $this->coordinatorRepository->findByUser($context->authUser);

        if (null === $authCoordinator) {
            return;
        }

        $lspUser = $this->lspUserRepository->findByUser($object);

        if (null === $lspUser) {
            throw new NoAccessToUserException($object);
        }

        if ($authCoordinator->isSupervisorOf($lspUser)) {
            return;
        }

        if (! $lspUser->isCoordinator()) {
            throw new NoAccessToUserException($object);
        }

        if (! $lspUser->lsp->isSubLspOf($authCoordinator->lsp)) {
            throw new NoAccessToUserException($object);
        }
    }
}
