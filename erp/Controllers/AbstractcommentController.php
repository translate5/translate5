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

abstract class erp_Controllers_AbstractcommentController extends ZfExtended_RestController {
    /**
     * @var ZfExtended_EventManager
     */
    protected $events = false;
    
    
    /**
     * Initialize event-trigger.
     * 
     * For more Information see definition of parent-class
     * 
     * @param Zend_Controller_Request_Abstract $request
     * @param Zend_Controller_Response_Abstract $response
     * @param array $invokeArgs
     */
    public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
    {
        parent::__construct($request, $response);
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(get_class($this)));
    }
    
    public function putAction() {
        $commentId = (int) $this->_getParam('id');
        $this->entity->load($commentId);
        $this->checkAccess();
        $this->decodePutData();
        $allowedToChange = array('comment');
        $this->setDataInEntity($allowedToChange, self::SET_DATA_WHITELIST);

        $this->entity->validate();
        $this->entity->setModified(date('Y-m-d H:i:s'));
        
        $this->entity->save();
        $this->view->rows = $this->entity->getDataObject();
        $this->view->rows->isEditable = true; //a edited comment is editable again
    }
    
    /**
     * sets the given foreign id of the associated entity in the comment entity
     */
    abstract protected function setForeignId() ;
    
    public function postAction() {
        $sessionUser = new Zend_Session_Namespace('user');
        $this->entity->init();
        $this->entity->setUserId($sessionUser->data->id);
        $this->decodePutData();
        $this->setForeignId();
        $this->entity->setComment($this->data->comment);
        $this->entity->validate();
        //by pass validation for values setted by system
        $now = date('Y-m-d H:i:s');
        $this->entity->setModified($now);
        $this->entity->setCreated($now);
        $this->entity->setUserName($sessionUser->data->userName);
        $this->entity->save();
        $this->view->rows = $this->entity->getDataObject();
        $this->view->rows->isEditable = true; //a newly added comment is editable by the user
    }
    
    /**
     * returns a clone of the deleted comment
     * (non-PHPdoc)
     * @see ZfExtended_RestController::deleteAction()
     */
    public function deleteAction() {
        $commentId = (int) $this->_getParam('id');
        $this->entity->load($commentId);
        $this->checkAccess();
        $clone = clone $this->entity;
        $this->entity->delete();
        return $clone;
    }
    
    protected function decodePutData() {
        parent::decodePutData();
        $this->data->comment = strip_tags($this->data->comment);
    }
    
    /**
     * checks if current session taskguid matches to loaded comment taskguid
     * @throws ZfExtended_Models_Entity_NoAccessException
     */
    protected function checkAccess() {
        $editable = $this->entity->isEditable();
        $sessionUser = new Zend_Session_Namespace('user');
        if (empty($editable) || $sessionUser->data->id != $this->entity->getUserId()) {
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
    }
}