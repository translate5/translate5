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
 * provides reusable workflow methods for controllers
 */
class Editor_Controller_Helper_Workflow extends Zend_Controller_Action_Helper_Abstract {
    /**
     * checks the user state of given taskGuid and userGuid,
     * throws a ZfExtended_NoAccessException if user is not allowed to write to the loaded task
     * @param string $taskGuid optional, if omitted we take the curently opened task from session
     * @param string $userGuid optional, if omitted we take the logged in user
     * @param editor_Workflow_Default $workflow optional, if omitted the configured workflow for task stored in the session is created
     * @throws ZfExtended_NoAccessException
     */
    public function checkWorkflowWriteable($taskGuid = null, $userGuid = null, editor_Workflow_Default $workflow = null) {
        if(empty($taskGuid)) {
            $s = new Zend_Session_Namespace();
            $taskGuid = $s->taskGuid;
        }
        if(empty($userGuid)) {
            $su = new Zend_Session_Namespace('user');
            $userGuid = $su->data->userGuid;
        }
        if(empty($workflow)) {
            $task = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            $task->loadByTaskGuid($taskGuid);
            
            $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');
            /* @var $wfm editor_Workflow_Manager */
            
            $workflow = $wfm->getByTask($task);
            /* @var $workflow editor_Workflow_Default */
        }
        $tua = null;
        try {
            $tua = editor_Models_Loaders_Taskuserassoc::loadByTaskGuid($userGuid,$taskGuid);
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e) {
        }
        if(empty($tua) || ! $workflow->isWritingAllowedForState($tua->getUsedState())) {
            $e = new ZfExtended_NoAccessException();
            $e->setLogging(false); //TODO info level logging
            if(empty($tua)) {
                $e->setMessage("Die Aufgabe wurde zwischenzeitlich in einem anderen Fenster durch ihren Benutzer verlassen.", true);
            }
            else {
                $e->setMessage("Die Aufgabe wurde zwischenzeitlich im nur Lesemodus geöffnet.", true);
            }
            throw $e;
        }
    }
}