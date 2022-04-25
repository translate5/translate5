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

use MittagQI\Translate5\Task\Current\NoAccessException;
use MittagQI\Translate5\Task\TaskContextTrait;

class Editor_CommentController extends ZfExtended_RestController {
    use TaskContextTrait;

    protected $entityClass = 'editor_Models_Comment';

    /**
     * @var editor_Models_Comment
     */
    protected $entity;

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws NoAccessException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     */
    public function init() {
        parent::init();
        $this->initCurrentTask();
        $events = Zend_EventManager_StaticEventManager::getInstance();
        
        //if comments are changed via REST the workflow stuff must be triggered
        $events->attach('editor_Models_Segment', 'beforeSave', function(Zend_EventManager_Event $event) {
            $segment = $event->getParam('entity');
            /* @var $segment editor_Models_Segment */
            $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');
            /* @var $wfm editor_Workflow_Manager */
            $workflow = $wfm->getActive($segment->getTaskGuid());
            $task = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            $task->loadByTaskGuid($segment->getTaskGuid());
            //@todo do this with events
            $workflow->getSegmentHandler()->beforeCommentedSegmentSave($segment, $task);
        });
    }

    /**
     * @throws \MittagQI\Translate5\Task\Current\Exception
     */
    public function indexAction() {
        $taskGuid = $this->getCurrentTask()->getTaskGuid();
        $segmentId = (int)$this->_getParam('segmentId');
        $this->view->rows = $this->entity->loadBySegmentId($segmentId, $taskGuid);
        foreach($this->view->rows as &$row) {
            $row['comment'] = htmlspecialchars($row['comment']);
        }
        $this->view->total = count($this->view->rows);
        
        // anonymize users for view?
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        if ($task->anonymizeUsers()) {
            $workflowAnonymize = ZfExtended_Factory::get('editor_Workflow_Anonymize');
            /* @var $workflowAnonymize editor_Workflow_Anonymize */
            foreach ($this->view->rows as &$row) {
                $row = $workflowAnonymize->anonymizeUserdata($taskGuid, $row['userGuid'], $row);
            }
        }
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NoAccessException
     * @throws Zend_Db_Statement_Exception
     * @throws \MittagQI\Translate5\Task\Current\Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_ValidateException
     * @throws NoAccessException
     * @throws ZfExtended_NoAccessException
     */
    public function putAction() {
        $commentId = (int) $this->_getParam('id');
        $this->entity->load($commentId);

        $this->checkUserGuid();
        $this->checkEditable();
        $wfh = $this->_helper->workflow;
        /* @var $wfh Editor_Controller_Helper_Workflow */
        $wfh->checkWorkflowWriteable($this->entity->getTaskGuid(), $this->entity->getUserGuid());

        $this->decodePutData();

        $allowedToChange = array('comment');
        $this->setDataInEntity($allowedToChange, self::SET_DATA_WHITELIST);

        $this->entity->setModified(date('Y-m-d H:i:s'));
        $this->entity->validate();
        
        $this->entity->save();
        $this->view->rows = $this->entity->getDataObject();
        $this->view->rows->isEditable = true; //a edited comment is editable again
        $this->updateSegment((int)$this->entity->getSegmentId(), $this->getCurrentTask()->getTaskGuid());
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     * @throws ZfExtended_Models_Entity_NoAccessException
     * @throws ZfExtended_NoAccessException
     */
    public function deleteAction() {
        $commentId = (int) $this->_getParam('id');
        $this->entity->load($commentId);
        $this->checkUserGuid();
        $this->checkEditable();
        $wfh = $this->_helper->workflow;
        /* @var $wfh Editor_Controller_Helper_Workflow */
        $wfh->checkWorkflowWriteable($this->entity->getTaskGuid(), $this->entity->getUserGuid());
        $id = (int)$this->entity->getSegmentId();
        $this->entity->delete();
        $this->updateSegment($id, $this->getCurrentTask()->getTaskGuid());
    }

    /**
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NoAccessException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_ValidateException
     * @throws ZfExtended_NoAccessException
     */
    public function postAction() {
        $taskGuid = $this->getCurrentTask()->getTaskGuid();
        $sessionUser = new Zend_Session_Namespace('user');
        $userGuid = $sessionUser->data->userGuid;
        $wfh = $this->_helper->workflow;
        /* @var $wfh Editor_Controller_Helper_Workflow */
        $wfh->checkWorkflowWriteable($taskGuid, $userGuid);
        $now = date('Y-m-d H:i:s');
        $this->entity->init();
        $this->entity->setModified($now);
        $this->entity->setCreated($now);
        $this->entity->setTaskGuid($taskGuid);
        $this->entity->setUserGuid($userGuid);
        $this->entity->setUserName($sessionUser->data->userName);
        $this->decodePutData();
        $this->checkSegmentTaskGuid($this->data->segmentId);
        $this->entity->setSegmentId($this->data->segmentId);
        $this->entity->setComment($this->data->comment);
        $this->entity->validate();
        $this->entity->save();
        $this->view->rows = $this->entity->getDataObject();
        $this->view->rows->isEditable = true; //a newly added comment is editable by the user
        $this->updateSegment((int)$this->entity->getSegmentId(), $taskGuid);
    }
    
    /**
     * Wrapper function to load segment before updating the comments in it
     * @param int $segmentId
     * @param string $taskGuid
     */
    protected function updateSegment(int $segmentId, string $taskGuid): void {
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $segment->load($segmentId);
        $this->entity->updateSegment($segment, $taskGuid);
    }
    
    /**
     * removes HTML from comment
     * @see ZfExtended_RestController::decodePutData()
     * @return void
     */
    protected function decodePutData()
    {
        parent::decodePutData();
        $this->data->comment = strip_tags($this->data->comment);
    }
    
    //getting a single comment is actually unnecessary
    public function getAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->put');
    }
    
   
    /**
     * checks if current session taskguid matches to loaded comment taskguid
     * @throws ZfExtended_Models_Entity_NoAccessException
     */
    protected function checkEditable() {
        $this->validateTaskAccess($this->entity->getTaskGuid());
        $editable = $this->entity->isEditable();
        if (empty($editable)) {
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
    }
    /**
     * compares the taskGuid of the desired segment and the actually loaded taskGuid
     * @param int $segmentId
     * @throws ZfExtended_Models_Entity_NoAccessException
     */
    protected function checkSegmentTaskGuid(int $segmentId) {
        /** @var editor_Models_Segment $segment */
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        $segment->load($segmentId);
        $this->validateTaskAccess($segment->getTaskGuid());
    }
    
    /**
     * compares the userGuid of the actual comment entity with the userGuid of the user.
     * @throws ZfExtended_Models_Entity_NoAccessException
     */
    protected function checkUserGuid() {
        $sessionUser = new Zend_Session_Namespace('user');
        if ($sessionUser->data->userGuid !== $this->entity->getUserGuid()) {
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
    }
}