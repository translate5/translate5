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

/**
 * Workflow Action Configuration Struct
 */
class editor_Workflow_Actions_Config
{
    public editor_Workflow_Default $workflow;

    /**
     * Task instance before task was changed
     */
    public ?editor_Models_Task $oldTask;

    /**
     * current task instance, with changes done by the current request
     */
    public ?editor_Models_Task $task;

    public ?editor_Models_TaskUserAssoc $newTua;

    public ?editor_Models_TaskUserAssoc $oldTua;

    /**
     * only available for import actions
     */
    public ?editor_Models_Import_Configuration $importConfig;

    public ZfExtended_Models_User $authenticatedUser;

    public bool $isCalledByCron = false;

    public ?string $trigger;

    public ZfExtended_EventManager $events;

    public ?stdClass $parameters;

    public ?string $createdByUser = null;

    public function isHandleDirect(): bool
    {
        return str_starts_with($this->trigger, editor_Workflow_Default_Hooks::DIRECT_TRIGGER);
    }
}
