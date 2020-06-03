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
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Info: the model front plugin depends on the matchanalysis plugin (matchanalys plugin must be active when model front plugin is active).
 */
class editor_Plugins_ModelFront_Init extends ZfExtended_Plugin_Abstract {
    
    /**
     * {@inheritDoc}
     * @see ZfExtended_Plugin_Abstract::init()
     */
    public function init() {
        if(ZfExtended_Debug::hasLevel('plugin', 'ModelFront')) {
            ZfExtended_Factory::addOverwrite('Zend_Http_Client', 'ZfExtended_Zendoverwrites_Http_DebugClient');
        }
        $this->initEvents();
    }
    
    /**
     */
    protected function initEvents() {
        $this->eventManager->attach('editor_TaskController', 'pretranslationOperation', array($this, 'handleOnPretranslationOperation'));
    }
    
    /***
     * Queue the ModelFront worker on pretranslation operation
     * 
     * @param Zend_EventManager_Event $event
     * @return boolean
     */
    public function handleOnPretranslationOperation(Zend_EventManager_Event $event){
        $task = $event->getParam('entity');
        /* @var $task editor_Models_Task*/
        
        $worker = ZfExtended_Factory::get('editor_Plugins_ModelFront_Worker');
        /* @var $worker editor_Plugins_ModelFront_Worker */
        // init worker and queue it
        if (!$worker->init($task->getTaskGuid(), [])) {
            error_log('ModelFront-Error on worker init()', __CLASS__.' -> '.__FUNCTION__.'; Worker could not be initialized');
            return false;
        }
        $parent=ZfExtended_Factory::get('ZfExtended_Models_Worker');
        /* @var $parent ZfExtended_Models_Worker */
        $result=$parent->loadByState("editor_Models_Import_Worker", ZfExtended_Models_Worker::STATE_PREPARE,$task->getTaskGuid());
        $parentWorkerId=null;
        if(!empty($result)){
            $parentWorkerId=$result[0]['id'];
        }
        
        $worker->queue($parentWorkerId);
    }
}
