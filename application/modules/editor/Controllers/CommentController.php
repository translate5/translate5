<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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
        $id = (int)$this->entity->getSegmentId();
        $this->entity->delete();
        $this->entity->updateSegment($id);
    }

    public function postAction() {
        $session = new Zend_Session_Namespace();
        $sessionUser = new Zend_Session_Namespace('user');
        $now = date('Y-m-d H:i:s');
        $this->entity->init();
        $this->entity->setModified($now);
        $this->entity->setCreated($now);
        $this->entity->setTaskGuid($session->taskGuid);
        $this->entity->setUserGuid($sessionUser->data->userGuid);
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