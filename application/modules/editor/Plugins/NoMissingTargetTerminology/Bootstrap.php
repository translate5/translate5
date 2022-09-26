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
 * Plugin Bootstrap for Missing Target Terminology Plugin
 * depends on editor_Plugins_SegmentStatistics_Bootstrap
 */
class editor_Plugins_NoMissingTargetTerminology_Bootstrap extends ZfExtended_Plugin_Abstract {
    protected static string $description = 'Sets the segment meta flag noMissingTargetTermOnImport to false if a term in source does not exist in target column';
    
    public function init() {
        $this->dependsOn('editor_Plugins_SegmentStatistics_Bootstrap');
        $this->eventManager->attach('editor_Models_Import', 'afterImport', array($this, 'handleAfterImport'),-100);
    }
    
    /**
     * handler for event: editor_Models_Import#afterImport
     * @param $event Zend_EventManager_Event
     */
    public function handleAfterImport(Zend_EventManager_Event $event) {
        $task = $event->getParam('task');
        /* @var $task editor_Models_Task */
        
        $worker = ZfExtended_Factory::get('editor_Plugins_NoMissingTargetTerminology_Worker');
        /* @var $worker editor_Plugins_NoMissingTargetTerminology_Worker */
        $worker->init($task->getTaskGuid());
        $worker->queue($event->getParam('parentWorkerId'));
    }
}