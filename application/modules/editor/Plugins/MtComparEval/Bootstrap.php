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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Plugin Bootstrap for MT-ComparEval Plugin
 */
class editor_Plugins_MtComparEval_Bootstrap extends ZfExtended_Plugin_Abstract {
    const STATE_NOTSET = 'notset';
    const STATE_IMPORTED = 'imported';
    const STATE_IMPORTING = 'importing';
    
    /**
     * Contains the Plugin Path relativ to APPLICATION_PATH or absolut if not under APPLICATION_PATH
     * @var array
     */
    protected $frontendControllers = array('Editor.plugins.mtComparEval.controller.Controller');
    
    public function init() {
        $this->eventManager->attach('editor_TaskmetaController', 'beforeSetDataInEntity', array($this, 'startExportTo'));
        $this->eventManager->attach('editor_TaskmetaController', 'afterGetAction', array($this, 'injectUrl'));
    }
    
    public function injectUrl(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        $entity = $event->getParam('entity');
        $view->rows->mtCompareURL = $this->getMtUri('/tasks/?experimentId='.$entity->getMtCompareEvalId());
    }
    
    public function startExportTo(Zend_EventManager_Event $event) {
        $data = $event->getParam('data');
        
        if(empty($data->mtCompareEvalState) || $data->mtCompareEvalState !== self::STATE_IMPORTING) {
            return;
        }
        unset($data->mtCompareEvalState);
        
        $entity = $event->getParam('entity');
        /* @var $entity editor_Models_Task_Meta */
        $entity->initEmptyRowset();
        $taskGuid = $entity->getTaskGuid();
        if($entity->updateMutexed('mtCompareEvalState', self::STATE_IMPORTING, $taskGuid, 'taskGuid')) {
            $worker = ZfExtended_Factory::get('editor_Plugins_MtComparEval_Worker');
            /* @var $worker editor_Plugins_MtComparEval_Worker */
            $worker->init($taskGuid);
            $worker->queue();
        }
        $entity->loadByTaskGuid($taskGuid);
    }
    
    /**
     * Universal Method in this plugin to generate MT-ComparEvals URIs
     * @param string $path
     * @return string
     */
    public function getMtUri($path) {
        $config = $this->getConfig();
        /* @var $config Zend_Config */
        return rtrim($config->url,'/').$path;
    }
}