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

namespace MittagQI\Translate5\Segment\ActionAssert\Permission\Asserts;

use BackedEnum;
use MittagQI\Translate5\ActionAssert\Permission\Asserts\PermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Segment\ActionAssert\SegmentAction;

/**
 * @implements PermissionAssertInterface<SegmentAction, \editor_Models_Segment>
 */
class SegmentIsEditableAssert implements PermissionAssertInterface
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly UserJobRepository $userJobRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            TaskRepository::create(),
            UserJobRepository::create()
        );
    }

    public function supports(BackedEnum $action): bool
    {
        return true;
    }

    public function assertGranted(BackedEnum $action, object $object, PermissionAssertContext $context): void
    {
        if (! $object->isEditable()) {
            throw new \ZfExtended_Models_Entity_NoAccessException();
        }

        $task = $this->taskRepository->getByGuid($object->getTaskGuid());

        // if the user can edit only segmentranges, we must also check if s/he is allowed to edit and save this segment
        $tua = $this->userJobRepository->findUserJobInTask(
            $context->actor->getUserGuid(),
            $task->getTaskGuid(),
            $task->getWorkflowStepName()
        );

        if ($tua && $tua->isSegmentrangedTaskForStep($task, $task->getWorkflowStepName())) {
            $assignedSegments = $tua->getAllAssignedSegmentsByUserAndStep(
                $task->getTaskGuid(),
                $context->actor->getUserGuid(),
                $task->getWorkflowStepName()
            );

            if (! in_array($object->getSegmentNrInTask(), $assignedSegments)) {
                throw new \ZfExtended_Models_Entity_NoAccessException();
            }
        }
    }
}
