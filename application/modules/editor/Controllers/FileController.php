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

class Editor_FileController extends ZfExtended_RestController {
  use TaskContextTrait;

  protected $entityClass = 'editor_Models_Foldertree';
  
  /**
   * @var editor_Models_Foldertree
   */
  protected $entity;

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws NoAccessException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     */
    public function init()
    {
        parent::init();
        $this->initCurrentTask();
    }

    /**
     * @throws \MittagQI\Translate5\Task\Current\Exception
     */
    public function indexAction()
    {
        $this->entity->loadByTaskGuid($this->getCurrentTask()->getTaskGuid());
        //by passing output handling, output is already JSON
        $contextSwitch = $this->getHelper('ContextSwitch');
        $contextSwitch->setAutoSerialization(false);
        $this->getResponse()->setBody($this->entity->getTreeAsJson());
    }

    /**
     * @throws \MittagQI\Translate5\Task\Current\Exception
     * @throws ZfExtended_NoAccessException
     */
    public function putAction()
    {
        $taskGuid = $this->getCurrentTask()->getTaskGuid();
        $data = json_decode($this->_getParam('data'));

        $wfh = $this->_helper->workflow;
        /* @var $wfh Editor_Controller_Helper_Workflow */
        $wfh->checkWorkflowWriteable($taskGuid, editor_User::instance()->getGuid());

        $this->entity->loadByTaskGuid($taskGuid);
        $mover = ZfExtended_Factory::get('editor_Models_Foldertree_Mover', array($this->entity));
        $mover->moveNode((int)$data->id, (int)$data->parentId, (int)$data->index);
        $this->entity->syncTreeToFiles();
        $this->syncSegmentFileOrder($taskGuid);
        $this->view->data = $mover->getById((int)$data->id);
    }
  
  /**
   * syncronize the Segment FileOrder Values to the corresponding Values in LEK_Files
   * @param string $taskGuid
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