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
     *  @var editor_Logger_Workflow
     */
    protected $log = false;
    
    /**
     * contains if available the task to the current tua 
     * @var editor_Models_Task
     */
    protected $task;
    
    public function init() {
        parent::init();
        $this->task = ZfExtended_Factory::get('editor_Models_Task');
        $this->log = ZfExtended_Factory::get('editor_Logger_Workflow', [$this->task]);
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction(){
        $rows = $this->entity->loadAllWithUserInfo();
        // anonymize users for view?
        if ($this->task->anonymizeUsers()) {
            $anonymize = ['firstName','login','surName'];
            foreach ($rows as &$row) {
                array_walk($row, function( &$value, $key) use ($anonymize) { 
                    if (in_array($key, $anonymize)) {
                        // TODO: get data from tracking-table
                        $value = 'xxx'; 
                    }
                 });
            }
        }
        $this->view->rows = $rows;
        
        $this->view->total = $this->entity->getTotalCount();
        $this->applyEditableAndDeletable();
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
        $this->task->loadByTaskGuid($this->data->taskGuid);
        
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
            if(is_object($this->data)) {
                unset($this->data->staticAuthHash);
            }
            else {
                unset($this->data['staticAuthHash']);
            }
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::putAction()
     */
    public function putAction() {
        $this->entityLoad();
        $this->task->loadByTaskGuid($this->entity->getTaskGuid());
        $this->log->request();
        $workflow = ZfExtended_Factory::get('editor_Workflow_Manager')->getActive($this->task);
        /* @var $workflow editor_Workflow_Abstract */
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
            $this->log->info('E1012', 'job status changed from {oldState} to {newState}', [
                'tua' => $this->entity,
                'oldState' => $oldEntity->getState(),
                'newState' => $this->data->state,
            ]);
        }
        $this->applyEditableAndDeletable();
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::postAction()
     */
    public function postAction() {
        parent::postAction();
        $this->log->request();
        $this->addUserInfoToResult();
        $this->log->info('E1012', 'job created', ['tua' => $this->entity]);
        $this->applyEditableAndDeletable();
    }
    
    public function deleteAction(){
        $this->entityLoad();
        $this->task->loadByTaskGuid($this->entity->getTaskGuid());
        $this->log->request();
        $workflow = ZfExtended_Factory::get('editor_Workflow_Manager')->getActive($this->task);
        /* @var $workflow editor_Workflow_Abstract */
        $this->checkAuthenticatedIsParentOfEntity();
        $this->processClientReferenceVersion();
        $entity = clone $this->entity;
        $this->entity->setId(0);
        //we have to perform the delete call on cloned object, since the delete call resets the data in the entity, but we need it for post processing 
        $entity->delete();
        $this->log->info('E1012', 'job deleted', ['tua' => $this->entity]);
    }
    
    /**
     * checks user based access on POST/PUT
     * {@inheritDoc}
     * @see ZfExtended_RestController::additionalValidations()
     */
    protected function additionalValidations() {
        $this->checkAuthenticatedIsParentOfEntity();
    }
    
    /***
     * Check if the current logged in user is allowed to POST/PUT/DELETE the given TaskUser Assoc entry
     */
    protected function checkAuthenticatedIsParentOfEntity(){
        $userSession = new Zend_Session_Namespace('user');
        $authenticated = $userSession->data;
        
        //if i am allowed to see any user:
        if($this->isAllowed('backend', 'seeAllUsers')) {
            return;
        }
        
        //The authenticated user is allowed to see/edit himself
        if($this->entity->getUserGuid() === $authenticated->userGuid){
            return;
        }
        
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->loadByGuid($this->entity->getUserGuid());
        
        //if the authenticated user is no parent, then he is not allowed to proceed
        if(!$user->hasParent($authenticated->id)){
            throw new ZfExtended_NoAccessException();
        }
    }
    
    /**
     * adds the extended userinfo to the resultset
     */
    protected function addUserInfoToResult() {
        if($this->_request->isPost() && !$this->wasValid) {
            return;
        }
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->loadByGuid($this->entity->getUserGuid());
        $this->view->rows->login = $user->getLogin();
        $this->view->rows->firstName = $user->getFirstName();
        $this->view->rows->surName = $user->getSurName();
        $this->view->rows->parentIds = $user->getParentIds();
        $this->view->rows->longUserName=$user->getUsernameLong();
    }
    
    /***
     * Add editable/deletable variable calculated for each user in the response rows.
     */
    protected function applyEditableAndDeletable(){
        $userSession = new Zend_Session_Namespace('user');
        $userData=$userSession->data;
        $userModel=ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $userModel ZfExtended_Models_User */
        $seeAllUsersAllowed=$userModel->isAllowed("backend","seeAllUsers");
        
        if(is_array($this->view->rows)) {
            foreach ($this->view->rows as &$row){
                if($seeAllUsersAllowed || $row['login']==$userData->login){
                    $row['editable']=true;
                    $row['deletable']=true;
                    continue;
                }
                //check if the current loged user is a parent for the user in the row
                $hasParent=$userModel->hasParent($userData->id, $row['parentIds']);
                $row['editable']=$hasParent;
                $row['deletable']=$hasParent;
            }
        }
        elseif(is_object($this->view->rows)) {
            if($seeAllUsersAllowed || $this->view->rows->login==$userData->login){
                $this->view->rows->editable=true;
                $this->view->rows->deletable=true;
                return;
            }
            //check if the current loged user is a parent for the user in the row
            $hasParent=$userModel->hasParent($userData->id, $this->view->rows->parentIds);
            $this->view->rows->editable=$hasParent;
            $this->view->rows->deletable=$hasParent;
        }
    }
}