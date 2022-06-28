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
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::postAction()
     */
    public function postAction(){
        try {
            parent::postAction();
        }
        catch(Zend_Db_Statement_Exception $e){
            $m = $e->getMessage();
            //duplicate entries are OK, since the user tried to create it
            if(strpos($m,'SQLSTATE') !== 0 || stripos($m,'Duplicate entry') === false) {
                throw $e;
            }
            //but we have to load and return the already existing duplicate 
            $this->entity->loadByTaskGuidAndTm($this->data->taskGuid, $this->data->languageResourceId);
            $this->view->rows = $this->entity->getDataObject();
        }
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

        //TODO: call this only when the task is not in import ?
        $wq = ZfExtended_Factory::get('ZfExtended_Worker_Queue');
        /* @var $wq ZfExtended_Worker_Queue */
        $wq->trigger();
    }

    /***
     * Queue the match analysis worker
     *
     * @param string $taskGuid
     * @throws Zend_Exception
     */
    protected function queuePivotWorker(string $taskGuid) : void {

        /** @var TaskPivotAssociation $pivotAssoc */
        $pivotAssoc = ZfExtended_Factory::get('\MittagQI\Translate5\LanguageResource\TaskPivotAssociation');
        $assoc = $pivotAssoc->loadTaskAssociated($taskGuid);

        if(empty($assoc)){
            return;
        }


        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);

        if($task->isImporting()) {
            //on import, we use the import worker as parentId
            $parentWorkerId = $this->fetchImportWorkerId($task->getTaskGuid());
        } else {
            // crucial: add a different behaviour for the workers when Cperforming an operation
            $workerParameters['workerBehaviour'] = 'ZfExtended_Worker_Behaviour_Default';
            // this creates the operation start/finish workers
            $parentWorkerId = editor_Task_Operation::create(editor_Task_Operation::PIVOT_PRE_TRANSLATION, $task);
        }

        $workerParameters['userGuid'] = editor_User::instance()->getGuid();
        $workerParameters['userName'] = editor_User::instance()->getUserName();

        //enable batch query via config
        $workerParameters['batchQuery'] = (boolean) Zend_Registry::get('config')->runtimeOptions->LanguageResources->Pretranslation->enableBatchQuery;
        if($workerParameters['batchQuery']){

            // trigger an event that gives plugins a chance to hook into the import process after unpacking/checking the files and before archiving them
            $this->events->trigger("beforePivotPreTranslationQueue", $this, array(
                'task' => $task,
                'pivotAssociations' => $assoc,
                'parentWorkerId' => $parentWorkerId
            ));
        }

        /** @var MittagQI\Translate5\LanguageResource\Pretranslation\PivotWorker $pivotWorker */
        $pivotWorker = ZfExtended_Factory::get('MittagQI\Translate5\LanguageResource\Pretranslation\PivotWorker');

        if (!$pivotWorker->init($taskGuid, $workerParameters)) {
            $this->addWarn($task,'Pivot pre-translation Error on worker init(). Worker could not be initialized');
            return;
        }
        $pivotWorker->queue($parentWorkerId, null, false);
    }

    /**
     *
     * @param string $taskGuid
     * @return NULL|int
     */
    private function fetchImportWorkerId(string $taskGuid): ?int
    {
        $parent = ZfExtended_Factory::get('ZfExtended_Models_Worker');
        /* @var $parent ZfExtended_Models_Worker */
        $result = $parent->loadByState(ZfExtended_Models_Worker::STATE_PREPARE, 'editor_Models_Import_Worker', $taskGuid);
        if(count($result) > 0){
            return $result[0]['id'];
        }
        return 0;
    }

    /***
     * Log analysis warning
     * @param string $taskGuid
     * @param array $extra
     * @param string $message
     */
    protected function addWarn(editor_Models_Task $task,string $message,array $extra=[]) {
        $extra['task']=$task;
        $logger = Zend_Registry::get('logger')->cloneMe('plugin.matchanalysis');
        $logger->warn('E1100',$message,$extra);
    }
}
