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

namespace MittagQI\Translate5\LSP\ActionAssert\Permission\Asserts;

use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\ActionAssert\Permission\Asserts\PermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\NoAccessException;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;

/**
 * @implements PermissionAssertInterface<LanguageServiceProvider>
 */
abstract class RuleBasedPermissionAssert implements PermissionAssertInterface
{
    public function __construct(
        private readonly JobCoordinatorRepository $jobCoordinatorRepository,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    final public function assertGranted(object $object, PermissionAssertContext $context): void
    {
        if ($this->doesPermissionGranted($object, $context)) {
            return;
        }

        throw new NoAccessException();
    }

    private function doesPermissionGranted(LanguageServiceProvider $lsp, PermissionAssertContext $context): bool
    {
        $roles = $context->manager->getRoles();

        if (array_intersect([Roles::ADMIN, Roles::SYSTEMADMIN], $roles)) {
            return true;
        }

        if (in_array(Roles::PM, $roles)) {
            return $lsp->isDirectLsp();
        }

        $coordinator = $this->jobCoordinatorRepository->findByUser($context->manager);

        if (null === $coordinator) {
            return false;
        }

        if ($coordinator->lsp->same($lsp)) {
            return $this->coordinatorHasAccessToHisLsp();
        }

        return $lsp->isSubLspOf($coordinator->lsp);
    }

    abstract protected function coordinatorHasAccessToHisLsp(): bool;
}
