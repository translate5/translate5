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
 * Additional Log Writer which just logs all events with a field "task"
 * with a task Entity in its extra data to a separate task log table
 */
class editor_Logger_TaskMailToPm extends ZfExtended_Logger_Writer_Abstract
{
    /**
     * {@inheritDoc}
     * @see ZfExtended_Logger_Writer_Abstract::write()
     */
    public function write(ZfExtended_Logger_Event $event): void
    {
        //we do not send duplicates
        if ($this->getDuplicateCount($event) > 0) {
            return;
        }
        $task = $event->extra['task'];
        /* @var $task editor_Models_Task */

        $pm = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $pm ZfExtended_Models_User */

        try {
            $pm->loadByGuid($task->getPmGuid());
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            //if there is no PM user, we can not write an email to him
            return;
        }

        $event = clone $event; //clone it, so that extra data is not manipulated in object

        $mailer = ZfExtended_Factory::get('ZfExtended_TemplateBasedMail');
        /* @var $mailer ZfExtended_TemplateBasedMail */
        $mailer->setParameters(['task' => $task, 'event' => $event,]);
        $mailer->setTemplate('taskWarning.phtml');
        $mailer->sendToUser($pm);
    }

    /**
     * Only 'normal' tasks should send messages to the PM.
     * {@inheritDoc}
     * @see ZfExtended_Logger_Writer_Abstract::isAccepted()
     */
    public function isAccepted(ZfExtended_Logger_Event $event): bool
    {
        $task = $event->extra['task'] ?? null;
        if ($task && is_a($task, 'editor_Models_Task') || $task->getTaskType()->isInternalTask()) {
            return parent::isAccepted($event);
        }
        return false;
    }
}