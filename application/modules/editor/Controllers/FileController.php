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

class Editor_FileController extends editor_Controllers_EditorrestController {
  
  protected $entityClass = 'editor_Models_Foldertree';
  
  /**
   * @var editor_Models_Foldertree
   */
  protected $entity;
  
  public function indexAction()
  {
    $session = new Zend_Session_Namespace();
    $this->entity->loadByTaskGuid($session->taskGuid);
    //by passing output handling, output is already JSON
    $contextSwitch = $this->getHelper('ContextSwitch');
    $contextSwitch->setAutoSerialization(false);
    $this->getResponse()->setBody($this->entity->getTreeAsJson());
  }
  
  public function putAction()
  {
    $session = new Zend_Session_Namespace();
    $data = json_decode($this->_getParam('data'));
    
    $wfh = $this->_helper->workflow;
    /* @var $wfh ZfExtended_Controller_Helper_Workflow */
    $wfh->checkWorkflowWriteable($session->taskGuid);
    
    $this->entity->loadByTaskGuid($session->taskGuid);
    $mover = ZfExtended_Factory::get('editor_Models_Foldertree_Mover', array($this->entity));
    $mover->moveNode((int)$data->id, (int)$data->parentId, (int)$data->index);
    $this->entity->syncTreeToFiles();
    $this->syncSegmentFileOrder($session->taskGuid);
    $this->view->data = $mover->getById((int)$data->id);
  }
  
  /**
   * syncronize the Segment FileOrder Values to the corresponding Values in LEK_Files
   * @param guid $taskGuid
   */
  protected function syncSegmentFileOrder($taskGuid) {
    /* @var $segment editor_Models_Segment */
    $segment = ZfExtended_Factory::get('editor_Models_Segment');
    $segment->syncFileOrderFromFiles($taskGuid);
  }
  
  public function deleteAction()
  {
    throw new ZfExtended_BadMethodCallException(__CLASS__.'->delete');
  }
  
  public function postAction()
  {
    throw new ZfExtended_BadMethodCallException(__CLASS__.'->post');
  }
}