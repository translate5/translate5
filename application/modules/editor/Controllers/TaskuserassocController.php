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
        $this->view->rows = $rows;
        $this->view->total = $this->entity->getTotalCount();
        $this->applyEditableAndDeletable();
        $this->addSegmentrangesToResult();
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
        $this->setDefaultAssignmentDate();
        settype($this->data->taskGuid, 'string');
        $this->task->loadByTaskGuid($this->data->taskGuid);
        
        $this->setLegacyDeadlineDate();
        
        $valid = parent::validate();
        //add the login hash AFTER validating, since we don't need any validation for it
        $this->entity->createstaticAuthHash();
        return $valid;
    }
    
    /**
     * @deprecated TODO: 11.02.2020 remove this function after all customers adopt there api calls, remove also the task meta targetDeliveryDate!
     */
    protected function setLegacyDeadlineDate() {
        $meta = $this->task->meta();
        if(!$meta->hasField('targetDeliveryDate')) {
            return;
        }
        $tdd = $meta->getTargetDeliveryDate();
        if(!empty($tdd) && empty($this->data->deadlineDate)) {
            $this->entity->setDeadlineDate($tdd);
        }
    }

    /**
     * {@inheritDoc}
     * @see ZfExtended_RestController::decodePutData()
     */
    protected function decodePutData() {
        parent::decodePutData();
        
        //lector deprecated message
        $lectorUsed = false;
        if(is_object($this->data) && property_exists($this->data, 'role') && $this->data->role == 'lector') {
            $this->data->role = editor_Workflow_Abstract::ROLE_REVIEWER;
            $lectorUsed = true;
        }
        elseif(is_array($this->data) && array_key_exists('role', $this->data) && $this->data['role'] == 'lector') {
            $this->data['role'] = editor_Workflow_Abstract::ROLE_REVIEWER;
            $lectorUsed = true;
        }
        if($lectorUsed) {
            Zend_Registry::get('logger')->warn('E1232', 'Job creation: role "lector" is deprecated, use "reviewer" instead!');
        }
        
        //may not be set from outside!
        if(is_object($this->data) && property_exists($this->data, 'staticAuthHash')) {
            unset($this->data->staticAuthHash);
            return;
        }
        if(is_array($this->data) && array_key_exists('staticAuthHash', $this->data)) {
            unset($this->data['staticAuthHash']);
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
        
        //here checks the isWritable if the tua is already in editing mode... Not as intended. 
        if(!empty($this->entity->getUsedState()) && $workflow->isWriteable($this->entity, true)) {
            // the following check on preventing changing Jobs which are used, prevents the following problems:
            // competitive tasks: 
            //   a task can not confirmed by user A if user A could not get a lock on the task, 
            //   because user B has opened the task for editing (and locked it), before User B was set to unconfirmed. 
            //   This is prevented now, since the PM gets an error when he wants to set User B to unconfirmed while B is editing already.
            //  another prevented problem: 
            //    User B have opened the task for editing, after that his job is set to unconfirmed
            //    User B does not notice this and edits more segments, although he should be unconfirmed or waiting.
            //  Throwing the following exception do not kick out the user, but the PM knows now that he fucked up the task.
            ZfExtended_Models_Entity_Conflict::addCodes([
                'E1161' => "The job can not be modified, since the user has already opened the task for editing. You are to late.",
            ]);
            throw ZfExtended_Models_Entity_Conflict::createResponse('E1161', [
                'id' => 'Sie können den Job zur Zeit nicht bearbeiten, der Benutzer hat die Aufgabe bereits zur Bearbeitung geöffnet.',
            ]);
        }
        $oldEntity = clone $this->entity;
        $this->decodePutData();
        $this->processClientReferenceVersion();
        $this->setDataInEntity();
        
        if (isset($this->data->segmentrange)) {
            $segmentrangeModel = ZfExtended_Factory::get('editor_Models_TaskUserAssoc_Segmentrange');
            /* @var $segmentrangeModel editor_Models_TaskUserAssoc_Segmentrange */
            if (!$segmentrangeModel->validateSyntax($this->data->segmentrange)) {
                ZfExtended_UnprocessableEntity::addCodes([
                    'E1280' => "The format of the segmentrange that is assigned to the user is not valid."
                ]);
                throw ZfExtended_UnprocessableEntity::createResponse('E1280', [
                    'id' => 'Das Format für die editierbaren Segmente ist nicht valide. Bsp: 1-3,5,8-9',
                ]);
            }
            
            $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
            /* @var $tua editor_Models_TaskUserAssoc */

            //get all usigned segments, but ignore the current assoc.
            $assignedSegments = $tua->getNotForUserAssignedSegments($this->entity->getTaskGuid(), $this->entity->getRole(),$this->entity->getUserGuid());
            
            if (!$segmentrangeModel->validateSemantics($this->data->segmentrange, $assignedSegments)) {
                ZfExtended_UnprocessableEntity::addCodes([
                    'E1281' => "The content of the segmentrange that is assigned to the user is not valid."
                ]);
                throw ZfExtended_UnprocessableEntity::createResponse('E1280', [
                    'id' => 'Der Inhalt für die editierbaren Segmente ist nicht valide. Die Zahlen müssen in der richtigen Reihenfolge angegeben sein und dürfen nicht überlappen, weder innerhalb der Eingabe noch mit anderen Usern von derselben Rolle.',
                ]);
            }
        }
        
        $this->entity->validate();
        $workflow->triggerBeforeEvents($oldEntity, $this->entity);
        $this->entity->save();

        $workflow->doWithUserAssoc($oldEntity, $this->entity);
        
        $this->view->rows = $this->entity->getDataObject();
        $this->addUserInfoToResult();
        if(isset($this->data->state) && $oldEntity->getState() != $this->data->state){
            $this->log->info('E1012', 'job status changed from {oldState} to {newState}', [
                'tua' => $this->getSanitizedEntityForLog(),
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
        $this->log->info('E1012', 'job created', ['tua' => $this->getSanitizedEntityForLog()]);
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
        $this->log->info('E1012', 'job deleted', ['tua' => $this->getSanitizedEntityForLog()]);
    }
    
    /**
     * returns the tua data with removed auth hash 
     * @return stdClass
     */
    protected function getSanitizedEntityForLog(): stdClass {
        $tua = $this->entity->getDataObject();
        unset($tua->staticAuthHash);
        unset($tua->usedInternalSessionUniqId);
        return $tua;
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
    
    /**
     * Add the number of segments that are not assigned to a user
     * although some other segments ARE assigned to users of this role.
     */
    protected function addSegmentrangesToResult() {
        $taskGuid = null;
        $filters = $this->entity->getFilter()->getFilters();
        array_walk(
            $filters,
            function ($item) use (&$taskGuid) {
                if ($item->field == 'taskGuid') {
                    $taskGuid = $item->value;
                }
            }
        );
        if (is_null($taskGuid)) {
            return;
        }
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        $this->view->segmentstoassign = $tua->getAllNotAssignedSegments($taskGuid);
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
    
    /***
     * Set the assignmentDate with the curent time stamp.
     * In different mysql versions the current_timestamp depends on mysql system variable (explicit_defaults_for_timestamp)
     * https://dev.mysql.com/doc/refman/5.6/en/server-system-variables.html#sysvar_explicit_defaults_for_timestamp
     */
    protected function setDefaultAssignmentDate(){
        if($this->getRequest()->isPost() && !isset($this->data->assignmentDate) || empty($this->data->assignmentDate)){
            $this->data->assignmentDate=NOW_ISO;
            $this->entity->setAssignmentDate(NOW_ISO);
        }
    }
}