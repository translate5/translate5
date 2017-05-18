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
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(get_class($this)));
        
        $this->restMessages = ZfExtended_Factory::get('ZfExtended_Models_Messages');
        Zend_Registry::set('rest_messages', $this->restMessages);
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction(){
        //$connector = ZfExtended_Factory::get('editor_Plugins_GlobalesePreTranslation_Connector');
        /* @var $connector editor_Plugins_GlobalesePreTranslation_Connector */
        //$connector->getGroups();
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
        $connector = ZfExtended_Factory::get('editor_Plugins_GlobalesePreTranslation_Connector');
        $connector->setAuth($data->username,$data->apiKey);
        
        /* @var $connector editor_Plugins_GlobalesePreTranslation_Connector */
        $engines = $connector->getEngines();
        
        
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
        $globaleseSession = new Zend_Session_Namespace('GlobalesePreTranslation');
        if($this->getParam('data') && $this->getParam('data')!=""){
            $data = json_decode($this->getParam('data'));
        }
        if(!$data){
            return;
        }
        $globaleseSession->engine =$data->engine;
        $globaleseSession->group =$data->group;
        //new session namespace
        //save the data from the gui to the session
        
        //here you save the posted data into the session
        //throw new ZfExtended_BadMethodCallException(__CLASS__.'->post');
    }
}