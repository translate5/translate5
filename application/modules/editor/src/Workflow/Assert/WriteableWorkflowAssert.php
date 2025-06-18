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

namespace MittagQI\Translate5\Workflow\Assert;

use editor_Models_Loaders_Taskuserassoc;
use editor_Models_Task;
use editor_Workflow_Default;
use editor_Workflow_Manager;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_NoAccessException;

class WriteableWorkflowAssert
{
    public static function create(): self
    {
        return new self();
    }

    /**
     * checks the user state of given taskGuid and userGuid,
     * throws a ZfExtended_NoAccessException if user is not allowed to write to the loaded task
     * @param editor_Workflow_Default|null $workflow optional, if omitted the configured workflow for task stored in the session is created
     * @throws ZfExtended_NoAccessException
     */
    public function assert(
        string $taskGuid,
        string $userGuid,
        editor_Workflow_Default $workflow = null
    ): void {
        if (empty($workflow)) {
            $task = ZfExtended_Factory::get(editor_Models_Task::class);
            $task->loadByTaskGuid($taskGuid);

            $wfm = ZfExtended_Factory::get(editor_Workflow_Manager::class);

            $workflow = $wfm->getByTask($task);
        }

        $tua = null;

        try {
            $tua = editor_Models_Loaders_Taskuserassoc::loadByTaskGuid($userGuid, $taskGuid);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
        }
        if (empty($tua) || ! $workflow->isWritingAllowedForState($tua->getUsedState())) {
            $e = new ZfExtended_NoAccessException();
            $e->setLogging(false); //TODO info level logging
            if (empty($tua)) {
                $e->setMessage("Die Aufgabe wurde zwischenzeitlich in einem anderen Fenster durch ihren Benutzer verlassen.", true);
            } else {
                $e->setMessage("Die Aufgabe wurde zwischenzeitlich im nur Lesemodus geöffnet.", true);
            }

            throw $e;
        }
    }
}
