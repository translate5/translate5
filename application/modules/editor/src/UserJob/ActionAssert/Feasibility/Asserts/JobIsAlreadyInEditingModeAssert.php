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

namespace MittagQI\Translate5\UserJob\ActionAssert\Feasibility\Asserts;

use editor_Models_TaskUserAssoc as UserJob;
use editor_Workflow_Manager;
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Feasibility\Asserts\FeasibilityAssertInterface;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\UserJob\ActionAssert\Feasibility\Exception\UserHasAlreadyOpenedTheTaskForEditingException;

/**
 * The following check on preventing changing Jobs which are used, prevents the following problems:
 * Competitive tasks:
 *    a task can not be confirmed by user A if user A could not get a lock on the task,
 *    because user B has opened the task for editing (and locked it), before User B was set to unconfirmed.
 *    This is prevented now, since the PM gets an error when he wants
 *    to set User B to unconfirmed while B is editing already.
 * Another prevented problem:
 *     User B have opened the task for editing, after that his job is set to unconfirmed
 *     User B does not notice this and edits more segments, although he should be unconfirmed or waiting.
 *
 * PM knows now that he fucked up the task.
 *
 * @implements FeasibilityAssertInterface<UserJob>
 */
class JobIsAlreadyInEditingModeAssert implements FeasibilityAssertInterface
{
    public function __construct(
        private readonly editor_Workflow_Manager $workflowManager,
        private readonly TaskRepository $taskRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            new editor_Workflow_Manager(),
            new TaskRepository(),
        );
    }

    public function supports(Action $action): bool
    {
        return $action === Action::Update;
    }

    /**
     * {@inheritDoc}
     */
    public function assertAllowed(object $object): void
    {
        if (empty($object->getUsedState())) {
            return;
        }

        $task = $this->taskRepository->getByGuid($object->getTaskGuid());
        $workflow = $this->workflowManager->getActiveByTask($task);

        if ($workflow->isWriteable($object, true)) {
            throw new UserHasAlreadyOpenedTheTaskForEditingException();
        }
    }
}