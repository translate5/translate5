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