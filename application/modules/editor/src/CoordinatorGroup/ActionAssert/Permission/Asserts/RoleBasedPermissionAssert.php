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

namespace MittagQI\Translate5\CoordinatorGroup\ActionAssert\Permission\Asserts;

use BackedEnum;
use MittagQI\Translate5\ActionAssert\Permission\Asserts\PermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\CoordinatorGroup\ActionAssert\Permission\CoordinatorGroupAction;
use MittagQI\Translate5\CoordinatorGroup\ActionAssert\Permission\Exception\NoAccessToCoordinatorGroupException;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinatorRepository;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;

/**
 * @implements PermissionAssertInterface<CoordinatorGroupAction, CoordinatorGroup>
 */
final class RoleBasedPermissionAssert implements PermissionAssertInterface
{
    public function __construct(
        private readonly JobCoordinatorRepository $jobCoordinatorRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            JobCoordinatorRepository::create(),
        );
    }

    public function supports(BackedEnum $action): bool
    {
        return true;
    }

    final public function assertGranted(BackedEnum $action, object $object, PermissionAssertContext $context): void
    {
        if ($this->doesPermissionGranted($action, $object, $context)) {
            return;
        }

        throw new NoAccessToCoordinatorGroupException((int) $object->getId());
    }

    private function doesPermissionGranted(
        BackedEnum $action,
        CoordinatorGroup $group,
        PermissionAssertContext $context
    ): bool {
        if ($context->actor->isAdmin()) {
            return true;
        }

        if ($context->actor->isPm()) {
            return $group->isTopRankGroup();
        }

        if (! $context->actor->isCoordinator()) {
            return false;
        }

        $coordinator = $this->jobCoordinatorRepository->findByUser($context->actor);

        if ($coordinator->group->same($group)) {
            return CoordinatorGroupAction::Read === $action;
        }

        return $group->isSubGroupOf($coordinator->group);
    }
}
