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
 * Initial Class of Plugin "GlobalesePreTranslation"
 */
class editor_Plugins_GlobalesePreTranslation_Init extends ZfExtended_Plugin_Abstract {
    public function init() {
        $this->log = ZfExtended_Factory::get('ZfExtended_Log', array(false));

        // event-listeners
        $this->eventManager->attach('editor_Models_Import', 'afterImport', array($this, 'handleAfterTaskImport'),10);
    }
    
    public function handleAfterTaskImport(Zend_EventManager_Event $event) {
        $worker = ZfExtended_Factory::get('editor_Plugins_GlobalesePreTranslation_Worker');
        /* @var $worker editor_Plugins_GlobalesePreTranslation_Worker */
        
        $task = $event->getParam('task');
        
        // init worker and queue it
        if (!$worker->init($task->getTaskGuid(), [])) {
            $this->log->logError('GlobalesePreTranslation-Error on worker init()', __CLASS__.' -> '.__FUNCTION__.'; Worker could not be initialized');
            return false;
        }
        $worker->queue($event->getParam('parentWorkerId'));
    }
}