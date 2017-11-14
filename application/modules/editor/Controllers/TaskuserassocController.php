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
 * Controller for the User Task Associations
 * Since PMs see all Task and Users, the indexAction has not to be constrained to show a subset of associations for security reasons
 */
class Editor_TaskuserassocController extends ZfExtended_RestController {

    protected $entityClass = 'editor_Models_TaskUserAssoc';

    /**
     * @var editor_Models_TaskUserAssoc
     */
    protected $entity;
    
    /**
     * ignoring ID field for POST Requests
     * @var array
     */
    protected $postBlacklist = array('id');
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction(){
        $this->view->rows = $this->entity->loadAllWithUserInfo();
        $this->view->total = $this->entity->getTotalCount();
    }
    
    public function postDispatch() {
        $user = new Zend_Session_Namespace('user');
        $acl = ZfExtended_Acl::getInstance();
        if($acl->isInAllowedRoles($user->data->roles, 'readAuthHash')) {
            parent::postDispatch();
            return;
        }
        if(is_array($this->view->rows)) {
            foreach($this->view->rows as &$row) {
                unset($row['staticAuthHash']);
            }
        }
        elseif(is_object($this->view->rows)) {
            unset($this->view->rows->staticAuthHash);
        }
        parent::postDispatch();
    }

    /**
     * for post requests we have to check the existence of the desired task first!
     * (non-PHPdoc)
     * @see ZfExtended_RestController::validate()
     */
    protected function validate() {
        if(!$this->_request->isPost()) {
            return parent::validate();
        }
        settype($this->data->taskGuid, 'string');
        $t = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $t editor_Models_Task */
        $t->loadByTaskGuid($this->data->taskGuid);
        
        $valid = parent::validate();
        //add the login hash AFTER validating, since we don't need any validation for it
        $this->entity->createstaticAuthHash();
        return $valid;
    }

    /**
     * {@inheritDoc}
     * @see ZfExtended_RestController::decodePutData()
     */
    protected function decodePutData() {
        parent::decodePutData();
        if(array_key_exists('staticAuthHash', $this->data)) {
            //may not be set from outside!
            unset($this->data['staticAuthHash']);
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::putAction()
     */
    public function putAction() {
        $workflow = ZfExtended_Factory::get('editor_Workflow_Manager')->getActive();
        /* @var $workflow editor_Workflow_Abstract */

        $this->entity->load($this->_getParam('id'));
        $oldEntity = clone $this->entity;
        $this->decodePutData();
        $this->processClientReferenceVersion();
        $this->setDataInEntity();
        //@todo in next release uncomment $workflow->isStateChangeable again and 
        //ensure in workflow, that the rights of a role decide, which states are changeable
        //therefore only the workflow knows everything about states
        //At the moment a check here is not necessary because only pm is allowed to use
        //taskuserassocController and he should be able to change everything right now.
        //makes sense to do a method isWorkflowStateChangeable und isTaskStateChangeable to decide between taskUserassoc-states and task-states
        //$this->setDataInEntity(array('state'), false); //rejecting value state
        //if($workflow->isStateChangeable($this->entity) && isset($this->data->state)) {
            //$this->entity->setState($this->data->state);
        //}
        $this->entity->validate();
        $workflow->triggerBeforeEvents($oldEntity, $this->entity);
        $this->entity->save();

        $workflow->doWithUserAssoc($oldEntity, $this->entity);
        
        $this->view->rows = $this->entity->getDataObject();
        $this->addUserInfoToResult();
        if(isset($this->data->state) && $oldEntity->getState() != $this->data->state){
            editor_Models_LogTask::createWithUserGuid($this->entity->getTaskGuid(), $this->data->state, $this->entity->getUserGuid());
        }
    }

    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::postAction()
     */
    public function postAction() {
        parent::postAction();
        $this->addUserInfoToResult();
    }
    
    /**
     * adds the extended userinfo to the resultset
     */
    protected function addUserInfoToResult() {
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->loadByGuid($this->entity->getUserGuid());
        $this->view->rows->login = $user->getLogin();
        $this->view->rows->firstName = $user->getFirstName();
        $this->view->rows->surName = $user->getSurName();
    }
}