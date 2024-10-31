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

namespace MittagQI\Translate5\Task\ActionAssert\Permission\Assert;

use editor_Models_Task as Task;
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Permission\Asserts\PermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\LSP\Exception\CantCreateCoordinatorFromUserException;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\Repository\Contract\LspUserRepositoryInterface;
use MittagQI\Translate5\Repository\LspJobRepository;
use MittagQI\Translate5\Repository\LspUserRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Task\ActionAssert\Permission\Exception\LspUserHasNoAccessToTaskException;

/**
 * @implements PermissionAssertInterface<Task>
 */
final class LspUserPermissionAssert implements PermissionAssertInterface
{
    public function __construct(
        private readonly LspUserRepositoryInterface $lspUserRepository,
        private readonly UserJobRepository $userJobRepository,
        private readonly LspJobRepository $lspJobRepository,
    ) {
    }

    public static function create(): self
    {
        return new self(
            LspUserRepository::create(),
            UserJobRepository::create(),
            LspJobRepository::create(),
        );
    }

    public function supports(Action $action): bool
    {
        return true;
    }

    public function assertGranted(object $object, PermissionAssertContext $context): void
    {
        $lspUser = $this->lspUserRepository->findByUser($context->authUser);

        if (null === $lspUser) {
            return;
        }

        if ($this->userJobRepository->userHasJobsInTask($context->authUser->getUserGuid(), $object->getTaskGuid())) {
            return;
        }

        try {
            JobCoordinator::fromLspUser($lspUser);
        } catch (CantCreateCoordinatorFromUserException) {
            throw new LspUserHasNoAccessToTaskException($object);
        }

        if (! $this->lspJobRepository->lspHasJobInTask((int) $lspUser->lsp->getId(), $object->getTaskGuid())) {
            throw new LspUserHasNoAccessToTaskException($object);
        }
    }
}
