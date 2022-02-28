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

/**
 * Task Meta Controller supports so far only PUT and GET.
 * DELETE and POST makes no sense, since a task meta entry is defined per task. So creation and deletion is coupled to the task
 * index Action could make sense, but currently not.
 */
class editor_TaskmetaController extends ZfExtended_RestController {
    protected $entityClass = 'editor_Models_Task_Meta';
    
    /**
     * Instance of the Entity
     * @var editor_Models_Task_Meta
     */
    protected $entity;
    
    /**
     * encapsulating the entity load for simpler overwritting purposes
     */
    protected function entityLoad() {
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($this->_getParam('id'));
        //using the meta() method instead a direct meta::loadByTaskGuid ensures that a empty taskmeta instance is given, also if nothing exists in DB
        $this->entity = $task->meta(); 
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::decodePutData()
     * @param bool|null $associative When TRUE, returned objects will be converted into associative arrays.
     * @return void
     */
    protected function decodePutData(?bool $associative = false)
    {
        parent::decodePutData();
        //taskGuid may not be overwritten by frontend
        unset($this->data->taskGuid);
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::postAction()
     */
    public function postAction() {
        throw new BadMethodCallException('Task Meta data supports only PUT and GET Action');
    }
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::postAction()
     */
    public function indexAction() {
        $this->postAction();
    }
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::postAction()
     */
    public function deleteAction() {
        $this->postAction();
    }
}
