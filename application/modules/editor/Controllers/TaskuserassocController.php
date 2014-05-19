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

    /**
     * for post requests we have to check the existance of the desired task first!
     * (non-PHPdoc)
     * @see ZfExtended_RestController::validate()
     */
    protected function validate() {
        if($this->_request->isPost()) {
            settype($this->data->taskGuid, 'string');
            $t = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $t editor_Models_Task */
            $t->loadByTaskGuid($this->data->taskGuid);
        }
        return parent::validate();
    }
    
    public function putAction() {
        $workflow = ZfExtended_Factory::get('editor_Workflow_Manager')->getActive();
        /* @var $workflow editor_Workflow_Abstract */

        $this->entity->load($this->_getParam('id'));
        $oldEntity = clone $this->entity;
        $this->decodePutData();
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
        $this->entity->save();

        $workflow->doWithUserAssoc($oldEntity, $this->entity);
        
        $this->view->rows = $this->entity->getDataObject();
        if(isset($this->data->state) && $oldEntity->getState() != $this->data->state){
            editor_Models_LogTask::createWithUserGuid($this->entity->getTaskGuid(), $this->data->state, $this->entity->getUserGuid());
        }
    }
}