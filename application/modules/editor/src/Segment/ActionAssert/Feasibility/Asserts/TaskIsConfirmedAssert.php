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

namespace MittagQI\Translate5\Segment\ActionAssert\Feasibility\Asserts;

use editor_Models_Segment;
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Feasibility\Asserts\FeasibilityAssertInterface;
use MittagQI\Translate5\Repository\TaskRepository;
use ZfExtended_Models_Entity_NoAccessException;

/**
 * @implements FeasibilityAssertInterface<editor_Models_Segment>
 */
class TaskIsConfirmedAssert implements FeasibilityAssertInterface
{
    private function __construct(
        private readonly TaskRepository $taskRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            TaskRepository::create(),
        );
    }

    public function supports(Action $action): bool
    {
        return $action->isMutable();
    }

    public function assertAllowed(object $object): void
    {
        $task = $this->taskRepository->getByGuid($object->getTaskGuid());

        if ($task->getState() === \editor_Models_Task::STATE_UNCONFIRMED) {
            throw new ZfExtended_Models_Entity_NoAccessException(
                'Task is not confirmed so no segment can be edited! Task: ' . $task->getTaskGuid()
            );
        }
    }
}
