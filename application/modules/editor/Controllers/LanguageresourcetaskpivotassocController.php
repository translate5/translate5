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

use MittagQI\Translate5\LanguageResource\TaskPivotAssociation;

/**
 */
class editor_LanguageresourcetaskpivotassocController extends ZfExtended_RestController {

    protected $entityClass = 'MittagQI\Translate5\LanguageResource\TaskPivotAssociation';

    /**
     * @var MittagQI\Translate5\LanguageResource\TaskPivotAssociation;
     */
    protected $entity;
    
    /**
     * ignoring ID field for POST Requests
     * @var array
     */
    protected $postBlacklist = ['id'];

    public function init()
    {
        ZfExtended_UnprocessableEntity::addCodes([
            'E1403' => 'The taskGuid is required as parameter',
        ], 'languageresources.pivotpretranslation');

        parent::init();
    }

    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction(){
        $taskGuid = $this->getParam('taskGuid');
        if(empty($taskGuid)){
            throw ZfExtended_UnprocessableEntity::createResponse('E1403',[
                'taskGuid' => 'The taskGuid field is empty'
            ]);
        }
        $this->view->rows =  $this->entity->loadAllAvailableForTask($taskGuid);
    }

    public function putAction()
    {
        throw new BadMethodCallException('HTTP method PUT not allowed!');
    }
    
    public function pretranslationBatch(){

        $taskGuid = $this->getParam('taskGuid');
        if(empty($taskGuid)){
            return;
        }

        /** @var editor_Models_Task $task */
        $task = ZfExtended_Factory::get('editor_Models_Task');
        $task->loadByTaskGuid($taskGuid);

        $taskGuids = [$task->getTaskGuid()];
        //if the requested operation is from project, queue analysis for each project task
        if($task->isProject()){
            $projects = ZfExtended_Factory::get('editor_Models_Task');
            /* @var editor_Models_Task $projects */
            $projects = $projects->loadProjectTasks($task->getProjectId(), true);
            $taskGuids = array_column($projects, 'taskGuid');
        }

        foreach ($taskGuids as $taskGuid){
            $this->queuePivotWorker($taskGuid);
        }

        if($task->isImporting() === false){
            $wq = ZfExtended_Factory::get('ZfExtended_Worker_Queue');
            /* @var $wq ZfExtended_Worker_Queue */
            $wq->trigger();
        }
    }

    /***
     * Queue pivot pretranslation and batch worker
     * @param string $taskGuid
     * @throws Zend_Exception
     */
    protected function queuePivotWorker(string $taskGuid) : void {
        /** @var \MittagQI\Translate5\LanguageResource\Pretranslation\PivotQueuer $queuer */
        $queuer = ZfExtended_Factory::get('\MittagQI\Translate5\LanguageResource\Pretranslation\PivotQueuer');
        $queuer->queuePivotWorker($taskGuid);
    }

    /***
     * Log analysis warning
     * @param editor_Models_Task $task
     * @param string $message
     * @param array $extra
     * @throws Zend_Exception
     */
    protected function addWarn(editor_Models_Task $task,string $message,array $extra=[]) {
        $extra['task']=$task;
        $logger = Zend_Registry::get('logger')->cloneMe('plugin.matchanalysis');
        $logger->warn('E1100',$message,$extra);
    }
}
