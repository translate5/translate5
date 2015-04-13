<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

class Editor_CommentController extends editor_Controllers_EditorrestController {

    protected $entityClass = 'editor_Models_Comment';

    /**
     * @var editor_Models_Comment
     */
    protected $entity;

    public function indexAction() {
        $segmentId = (int)$this->_getParam('segmentId');
        $this->view->rows = $this->entity->loadBySegmentId($segmentId);
        $this->view->total = count($this->view->rows);
    }

    public function putAction() {
        $session = new Zend_Session_Namespace();
        $commentId = (int) $this->_getParam('id');
        $this->entity->load($commentId);

        $this->checkUserGuid();
        $this->checkEditable();
        $wfh = $this->_helper->workflow;
        /* @var $wfh ZfExtended_Controller_Helper_Workflow */
        $wfh->checkWorkflowWriteable($this->entity->getTaskGuid(), $this->entity->getUserGuid());

        $this->decodePutData();

        $allowedToChange = array('comment');
        $this->setDataInEntity($allowedToChange, self::SET_DATA_WHITELIST);

        $this->entity->setModified(date('Y-m-d H:i:s'));
        $this->entity->validate();
        
        $this->entity->save();
        $this->view->rows = $this->entity->getDataObject();
        $this->view->rows->isEditable = true; //a edited comment is editable again
        $this->entity->updateSegment((int)$this->entity->getSegmentId());
    }

    public function deleteAction() {
        $commentId = (int) $this->_getParam('id');
        $this->entity->load($commentId);
        $this->checkUserGuid();
        $this->checkEditable();
        $wfh = $this->_helper->workflow;
        /* @var $wfh ZfExtended_Controller_Helper_Workflow */
        $wfh->checkWorkflowWriteable($this->entity->getTaskGuid(), $this->entity->getUserGuid());
        $id = (int)$this->entity->getSegmentId();
        $this->entity->delete();
        $this->entity->updateSegment($id);
    }

    public function postAction() {
        $session = new Zend_Session_Namespace();
        $sessionUser = new Zend_Session_Namespace('user');
        $userGuid = $sessionUser->data->userGuid;
        $wfh = $this->_helper->workflow;
        /* @var $wfh ZfExtended_Controller_Helper_Workflow */
        $wfh->checkWorkflowWriteable($session->taskGuid, $userGuid);
        $now = date('Y-m-d H:i:s');
        $this->entity->init();
        $this->entity->setModified($now);
        $this->entity->setCreated($now);
        $this->entity->setTaskGuid($session->taskGuid);
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
        $this->entity->updateSegment((int)$this->entity->getSegmentId());
    }
    
    /**
     * removes HTML from comment
     * (non-PHPdoc)
     * @see ZfExtended_RestController::decodePutData()
     */
    protected function decodePutData() {
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
        $editable = $this->entity->isEditable();
        if (empty($editable) || $this->session->taskGuid !== $this->entity->getTaskGuid()) {
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
    }
    /**
     * compares the taskGuid of the desired segment and the actually loaded taskGuid
     * @param integer $segmentId
     * @throws ZfExtended_Models_Entity_NoAccessException
     */
    protected function checkSegmentTaskGuid(integer $segmentId) {
        $session = new Zend_Session_Namespace();
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $segment->load($segmentId);
        if ($session->taskGuid !== $segment->getTaskGuid()) {
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
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