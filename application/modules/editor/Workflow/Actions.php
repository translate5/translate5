<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Encapsulates the Default Actions triggered by the Workflow
 */
class editor_Workflow_Actions extends editor_Workflow_Actions_Abstract {
    /**
     * sets all segments to untouched state - if they are untouched by the user
     */
    public function segmentsSetUntouchedState() {
        //FIXME setze die Segmente ebenfalls auf $newTua->getUserGuid als letzten Editor!
        $this->updateAutoStates($this->config->task->getTaskGuid(),'setUntouchedState');
    }
    
    /**
     * sets all segments to initial state - if they were untouched by the user before
     */
    public function segmentsSetInitialState() {
        //FIXME Mit Marc klären, wenn wir oben die $newTua->getUserGuid als letzten Editor setzen, dann auch hier wieder zurücksetzen?
        $this->updateAutoStates($this->config->task->getTaskGuid(),'setInitialStates');
    }
    
    /**
     * Updates the tasks real delivery date to the current timestamp
     */
    public function taskSetRealDeliveryDate() {
        $task = $this->config->task;
        $task->setRealDeliveryDate(date('Y-m-d', $_SERVER['REQUEST_TIME']));
        $task->save();
    }
    
    /**
     * updates all Auto States of this task
     * currently this method supports only updating to REVIEWED_UNTOUCHED and to initial (which is NOT_TRANSLATED and TRANSLATED)
     * @param string $taskGuid
     * @param string $method method to call in editor_Models_Segment_AutoStates
     */
    protected function updateAutoStates(string $taskGuid, string $method) {
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        
        $states = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
        /* @var $states editor_Models_Segment_AutoStates */
        
        $states->{$method}($taskGuid, $segment);
    }
}