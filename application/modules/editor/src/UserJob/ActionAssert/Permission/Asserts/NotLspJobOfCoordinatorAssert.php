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

namespace MittagQI\Translate5\UserJob\ActionAssert\Permission\Asserts;

use editor_Models_TaskUserAssoc as UserJob;
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Permission\Asserts\PermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\Repository\LspJobRepository;
use MittagQI\Translate5\UserJob\ActionAssert\Permission\Exception\NoAccessToUserJobException;

/**
 * @implements PermissionAssertInterface<UserJob>
 */
class NotLspJobOfCoordinatorAssert implements PermissionAssertInterface
{
    public function __construct(
        private readonly JobCoordinatorRepository $jobCoordinatorRepository,
        private readonly LspJobRepository $lspJobRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            JobCoordinatorRepository::create(),
            LspJobRepository::create(),
        );
    }

    public function supports(Action $action): bool
    {
        return $action->isMutable();
    }

    public function assertGranted(object $object, PermissionAssertContext $context): void
    {
        $authCoordinator = $this->jobCoordinatorRepository->findByUser($context->authUser);

        if (null === $authCoordinator) {
            return;
        }

        if (! $object->isLspJob()) {
            return;
        }

        $lspJob = $this->lspJobRepository->get((int) $object->getLspJobId());

        if ((int) $lspJob->getLspId() === (int) $authCoordinator->lsp->getId()) {
            throw new NoAccessToUserJobException($object);
        }
    }
}
