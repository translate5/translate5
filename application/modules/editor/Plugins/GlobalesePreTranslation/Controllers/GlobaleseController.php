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
 * editor_Plugins_GlobalesePreTranslation_GlobaleseController
 */
class editor_Plugins_GlobalesePreTranslation_GlobaleseController extends ZfExtended_RestController {
    
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::init()
     *
     * copied the init method, parent can not be used, since no real entity is used here
     */
    public function init() {
        $this->initRestControllerSpecific();
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction(){
    }
    
    public function groupsAction(){
        if($this->getParam('data') && $this->getParam('data')!=""){
            $data = json_decode($this->getParam('data'));
        }
        if(!$data){
            $this->view->rows = "[]";
            return;
        }
        $connector = ZfExtended_Factory::get('editor_Plugins_GlobalesePreTranslation_Connector');
        $connector->setAuth($data->username,$data->apiKey);
        
        /* @var $connector editor_Plugins_GlobalesePreTranslation_Connector */
        $groups = $connector->getGroups();
        
        
        $this->view->rows = $groups;
    }
    
    public function enginesAction(){
        if($this->getParam('data') && $this->getParam('data')!=""){
            $data = json_decode($this->getParam('data'));
        }
        if(!$data){
            $this->view->rows = "[]";
            return;
        }
        
        //if the current request is for project, throw exception
        if(is_array($data->targetLang) && count($data->targetLang)>1){
            ZfExtended_UnprocessableEntity::addCodes([
                'E1025' => 'Globalese pretranslation is not supported for project imports.'
            ]);
            throw new ZfExtended_UnprocessableEntity('E1025');
        }
        
        $connector = ZfExtended_Factory::get('editor_Plugins_GlobalesePreTranslation_Connector');
        $connector->setAuth($data->username,$data->apiKey);

        /* @var $connector editor_Plugins_GlobalesePreTranslation_Connector */
        $engines = $connector->getEngines($data->sourceLang,$data->targetLang);
        
        $this->view->rows = $engines;
    }
    
    public function getAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->get');
    }
    
    public function putAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->put');
    }
    
    public function deleteAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->delete');
    }
    
    public function postAction() {
        if($this->getParam('data') && $this->getParam('data')!=""){
            $data = json_decode($this->getParam('data'));
        }
        if(!$data){
            return;
        }
        
        $worker = ZfExtended_Factory::get('editor_Plugins_GlobalesePreTranslation_Worker');
        /* @var $worker editor_Plugins_GlobalesePreTranslation_Worker */
        $task=ZfExtended_Factory::get('editor_Models_Task');
        
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($data->taskGuid);

        $params=[
            'group'=>$data->group,
            'engine'=>$data->engine,
            'apiUsername'=>$data->apiUsername,
            'apiKey'=>$data->apiKey
        ];
        
        //validate the params
        if(empty($params['group']) || empty($params['engine']) || empty($params['apiUsername']) || empty($params['apiKey'])) {
            return;
        }
        
        // init worker and queue it
        if (!$worker->init($task->getTaskGuid(), $params)) {
            $this->log->logError('GlobalesePreTranslation-Error on worker init()', __CLASS__.' -> '.__FUNCTION__.'; Worker could not be initialized');
            return false;
        }
        
        //find the parent worker
        $parent = ZfExtended_Factory::get('ZfExtended_Models_Worker');
        /* @var $parent ZfExtended_Models_Worker */
        $result = $parent->loadByState(ZfExtended_Models_Worker::STATE_PREPARE, 'editor_Models_Import_Worker', $task->getTaskGuid());
        $parentWorkerId=null;
        if(!empty($result)){
            $parentWorkerId=$result[0]['id'];
        }
        $worker->queue($parentWorkerId);
    }
}