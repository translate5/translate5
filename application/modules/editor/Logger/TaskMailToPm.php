<?php
/*
 START LICENSE AND COPYRIGHT
 
 This file is part of ZfExtended library
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
 
 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 https://www.gnu.org/licenses/lgpl-3.0.txt
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
 https://www.gnu.org/licenses/lgpl-3.0.txt
 
 END LICENSE AND COPYRIGHT
 */

/**
 * Additional Log Writer which just logs all events with an field "task" with a task Entity in its extra data to a separate task log table
 */
class editor_Logger_TaskMailToPm extends ZfExtended_Logger_Writer_Abstract {
    /**
     * {@inheritDoc}
     * @see ZfExtended_Logger_Writer_Abstract::write()
     */
    public function write(ZfExtended_Logger_Event $event) {
        //we do not send duplicates
        if($this->getDuplicateCount($event) > 0) {
            return;
        }
        $task = $event->extra['task'];
        /* @var $task editor_Models_Task */
        
        $pm = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $pm ZfExtended_Models_User */
        
        try {
            $pm->loadByGuid($task->getPmGuid());
        }
        catch (ZfExtended_Models_Entity_NotFoundException $e) {
            //if there is no PM user, we can not write an email to him
            return;
        }
        
        $event = clone $event; //clone it, so that extra data is not manipulated in object
        
        $mailer = ZfExtended_Factory::get('ZfExtended_TemplateBasedMail');
        /* @var $mailer ZfExtended_TemplateBasedMail */
        $mailer->setParameters([
            'task' => $task,
            'event' => $event,
        ]);
        $mailer->setTemplate('taskWarning.phtml');
        $mailer->sendToUser($pm);
    }
    
    /**
     * Only 'normal' tasks should send messages to the PM.
     * {@inheritDoc}
     * @see ZfExtended_Logger_Writer_Abstract::isAccepted()
     */
    public function isAccepted(ZfExtended_Logger_Event $event) {
        if(empty($event->extra) || empty($event->extra['task']) || !is_a($event->extra['task'], 'editor_Models_Task') || $event->extra['task']->isHiddenTask()) {
            return false;
        }
        return parent::isAccepted($event);
    }
}