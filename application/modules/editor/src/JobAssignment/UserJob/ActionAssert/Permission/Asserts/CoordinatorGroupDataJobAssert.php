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

namespace MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\Permission\Asserts;

use editor_Models_TaskUserAssoc as UserJob;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Asserts\PermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\CoordinatorGroup\JobCoordinatorRepository;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\ActionAssert\Permission\CoordinatorGroupJobActionPermissionAssert;
use MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\Permission\Exception\ActionNotAllowedException;
use MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\UserJobAction;
use MittagQI\Translate5\Repository\CoordinatorGroupJobRepository;

/**
 * @implements PermissionAssertInterface<UserJobAction, UserJob>
 */
class CoordinatorGroupDataJobAssert implements PermissionAssertInterface
{
    public function __construct(
        private readonly CoordinatorGroupJobRepository $coordinatorGroupJobRepository,
        private readonly JobCoordinatorRepository $coordinatorRepository,
        private readonly ActionPermissionAssertInterface $coordinatorGroupJobActionPermissionAssert,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            CoordinatorGroupJobRepository::create(),
            JobCoordinatorRepository::create(),
            CoordinatorGroupJobActionPermissionAssert::create(),
        );
    }

    public function supports(\BackedEnum $action): bool
    {
        return true;
    }

    public function assertGranted(\BackedEnum $action, object $object, PermissionAssertContext $context): void
    {
        if (! $object->isCoordinatorGroupJob()) {
            return;
        }

        $groupJob = $this->coordinatorGroupJobRepository->get((int) $object->getCoordinatorGroupJobId());

        $this->coordinatorGroupJobActionPermissionAssert->assertGranted($action, $groupJob, $context);

        if (! in_array($action, [UserJobAction::UpdateDeadline, UserJobAction::Delete], true)) {
            return;
        }

        if (! $context->actor->isCoordinator()) {
            return;
        }

        $coordinator = $this->coordinatorRepository->getByUser($context->actor);

        if ((int) $coordinator->group->getId() === (int) $groupJob->getGroupId()) {
            throw new ActionNotAllowedException($action, $object);
        }
    }
}
