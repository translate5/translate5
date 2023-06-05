<?php

/***
 * Here ale place all task import related functions just to split them from the main controller
 * TODO FIXME: This should NOT be a trait but parts of the import-process should better be encapsulated in classes ... a trait must not use props of the class using it
 */
trait editor_Controllers_Task_ImportTrait {
    
    /**
     * @var editor_Workflow_Default
     */
    protected $workflow;
    
    /**
     * @var editor_Workflow_Manager
     */
    protected $workflowManager;
    
    /***
     * Handles the import for non project data.
     * This will evaluate what kind of data provider should be used, and it will process the uploaded files
     * @return array
     * @throws Exception
     */
    protected function handleTaskImport(): array
    {
        //gets and validates the uploaded zip file
        $upload = ZfExtended_Factory::get('editor_Models_Import_UploadProcessor');
        /* @var $upload editor_Models_Import_UploadProcessor */
        $dpFactory = ZfExtended_Factory::get('editor_Models_Import_DataProvider_Factory');
        /* @var $dpFactory editor_Models_Import_DataProvider_Factory */
        $upload->initAndValidate();
        $dp = $dpFactory->createFromUpload($upload,$this->data);

        //was set as array in setDataInEntity
        $this->entity->setTargetLang(reset($this->data['targetLang']));
        //$this->entity->save(); => is done by the import call!
        //handling project tasks is also done in processUploadedFile
        $this->processUploadedFile($this->entity, $dp);

        // add task defaults (user associations and language resources)
        $this->setTaskDefaults($this->entity);

        //for internal tasks the usage log requires different handling, so log only non-internal tasks
        if(!$this->entity->getTaskType()->isInternalTask()){
            //update the task usage log for the current task
            $this->insertTaskUsageLog($this->entity);
        }

        return $this->entity->toArray();
    }

    /***
     * Handle project import.
     * This function will evaluate what kind of data provider should be used, and for each target langauge,
     * one project task will be created. For each project task, the data provider will decide which uploaded files
     * will be used.
     *
     * @return array
     * @throws Zend_Exception
     */
    protected function handleProjectUpload(): array
    {
        //gets and validates the uploaded zip file
        $upload = ZfExtended_Factory::get('editor_Models_Import_UploadProcessor');
        /* @var $upload editor_Models_Import_UploadProcessor */

        $upload->initAndValidate();

        $dpFactory = ZfExtended_Factory::get('editor_Models_Import_DataProvider_Factory');
        /* @var $dpFactory editor_Models_Import_DataProvider_Factory */
        $dp = $dpFactory->createFromUpload($upload,$this->data);

        $entityId=$this->entity->save();
        $this->entity->initTaskDataDirectory();

        // trigger an event that gives plugins a chance to hook into the import process after unpacking/checking the files and before archiving them
        $this->events->trigger('afterUploadPreparation', $this, array(
            'task' => $this->entity,
            'dataProvider' => $dp,
            'requestData' => $this->data
        ));

        $dp->checkAndPrepare($this->entity);

        //for projects this have to be done once before the single tasks are imported
        $dp->archiveImportedData();

        $this->entity->setProjectId($entityId);
        $this->entity->setTaskType(editor_Task_Type::getInstance()->getImportProjectType());

        $this->entity->save();// save the entity to keep the project id in case the task import fails.

        $languages=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $languages editor_Models_Languages */
        $languages=$languages->loadAllKeyValueCustom('id','rfc5646');

        $projectTasks = [];

        foreach($this->data['targetLang'] as $target) {
            $task = clone $this->entity;


            $task->setProjectId($entityId);
            $task->setTaskType(editor_Task_Type::getInstance()->getImportTaskType());
            $task->setTargetLang($target);
            $task->setTaskName($this->entity->getTaskName().' - '.$languages[$task->getSourceLang()].' / '.$languages[$task->getTargetLang()]);

            $this->processUploadedFile($task, $dp);

            // add task defaults (user associations and language resources)
            $this->setTaskDefaults($task);

            //update the task usage log for this project-task
            $this->insertTaskUsageLog($task);

            $projectTasks[] = $task->getDataObject();
        }

        $this->entity->setState($this->entity::STATE_PROJECT);
        $this->entity->save();

        return $projectTasks;
    }

    /**
     * imports the uploaded file into the given task and creates the associated Task_Meta entity
     * @param editor_Models_Task $task
     * @param editor_Models_Import_DataProvider_Abstract $dp
     * @throws Exception
     */
    protected function processUploadedFile(editor_Models_Task $task, editor_Models_Import_DataProvider_Abstract $dp) {

        /* @see editor_Models_Import::import Saves $meta after task */
        $meta = $task->meta();
        $this->events->trigger('beforeProcessUploadedFile', $this, [
            'task' => $task,
            'meta' => $meta,
            'data' => $this->data,
        ]);

        $import = ZfExtended_Factory::get('editor_Models_Import');
        /* @var $import editor_Models_Import */
        $import->setUserInfos($this->user->data->userGuid, $this->user->data->userName);

        $import->setTask($task);

        try {
            $import->import($dp, $this->data);
        } catch (ZfExtended_ErrorCodeException $e){

            // in case there is a task, remove it. To not blur the original Exception, we just try it
            try {
                $remover = ZfExtended_Factory::get(editor_Models_Task_Remover::class, [ $task ]);
                $remover->remove(true);
            } catch(Throwable){

            }

            if($e instanceof editor_Models_Import_ConfigurationException){
                $this->handleConfigurationException($e);
            }elseif ($e instanceof ZfExtended_Models_Entity_Exceptions_IntegrityConstraint){
                $this->handleIntegrityConstraint($e);
            }elseif ($e instanceof editor_Models_Import_DataProvider_Exception){
                $this->handleDataProviderException($e);
            }
            throw $e;
        }
    }
    
    /**
     * init the internal used workflow
     * @param string $wfId workflow ID. optional, if omitted use the workflow of $this->entity
     */
    protected function initWorkflow($wfId = null) {
        if(empty($wfId) && isset($this->entity)) {
            $wfId = $this->entity->getWorkflow();
        }
        try {
            $this->workflow = $this->workflowManager->getCached($wfId);
        }
        catch (Exception $e) {
            $this->workflow = $this->workflowManager->getCached('default');
        }
    }
    
    /**
     * starts the workers of the current or given task
     * @param string $taskGuid optional, if empty use current task
     */
    protected function startImportWorkers(editor_Models_Task $task = null) {
        if(empty($task)) {
            $task = $this->entity;
        }
        
        $tasks = [];
        //if it is a project, start the import workers for each sub task
        if($task->isProject()) {
            $tasks = $task->loadProjectTasks($task->getProjectId(),true);
            
            /** @var editor_Workflow_Manager $wfm */
            ZfExtended_Factory::get('editor_Workflow_Manager')
                ->getActiveByTask($task)
                ->hookin()
                ->doHandleProjectCreated($task);
            
        } else {
            $tasks[] = $task;
        }
        
        // we fix all task-specific configs of the task for it's remaining lifetime
        // this is crucial to ensure, that important configs are changed throughout the lifetime that are usually not designed to be dynamical (AutoQA, Visual, ...)
        $taskConfig = ZfExtended_Factory::get('editor_Models_TaskConfig');
        /* @var $taskConfig editor_Models_TaskConfig */
        $taskConfig->fixAfterImport($tasks);
    
        $model = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $model editor_Models_Task */
        foreach ($tasks as $t){
            
            if(is_array($t)){
                $model->load($t['id']);
            } else {
                $model = $t;
            }
            
            //import workers can only be started for tasks
            if($model->isProject()) {
                continue;
            }
            
            $workerModel = ZfExtended_Factory::get('ZfExtended_Models_Worker');
            /* @var $workerModel ZfExtended_Models_Worker */
            try {
                $workerModel->loadFirstOf('editor_Models_Import_Worker', $model->getTaskGuid());
                $worker = ZfExtended_Worker_Abstract::instanceByModel($workerModel);
                $worker && $worker->schedulePrepared();
            }
            catch (ZfExtended_Models_Entity_NotFoundException $e) {
                //if there is no worker, nothing can be done
            }
        }
    }
    
    /***
     * Handle the task usage log for given entity. This will update the sum counter or insert new record
     * based on the unique key of `taskType`,`customerId`,`yearAndMonth`
     *
     * @param editor_Models_task $task
     */
    protected function insertTaskUsageLog(editor_Models_task $task) {
        $log = ZfExtended_Factory::get('editor_Models_TaskUsageLog');
        /* @var $log editor_Models_TaskUsageLog */
        $log->setTaskType($task->getTaskType()->id());
        $log->setSourceLang($task->getSourceLang());
        $log->setTargetLang($task->getTargetLang());
        $log->setCustomerId($task->getCustomerId());
        $log->setYearAndMonth(date('Y-m'));
        $log->updateInsertTaskCount();
    }

    /**
     * Converts the ConfigurationException caused by wrong user input to ZfExtended_UnprocessableEntity exceptions
     * @param editor_Models_Import_ConfigurationException $e
     * @throws editor_Models_Import_ConfigurationException
     * @throws ZfExtended_UnprocessableEntity
     */
    protected function handleConfigurationException(editor_Models_Import_ConfigurationException $e) {
        $codeToFieldAndMessage = [
            'E1032' => ['sourceLang', 'Die übergebene Quellsprache "{language}" ist ungültig!'],
            'E1033' => ['targetLang', 'Die übergebene Zielsprache "{language}" ist ungültig!'],
            'E1034' => ['relaisLang', 'Es wurde eine Relaissprache gesetzt, aber im Importpaket befinden sich keine Relaisdaten.'],
            'E1039' => ['importUpload', 'Das importierte Paket beinhaltet kein gültiges "{review}" Verzeichnis.'],
            'E1040' => ['importUpload', 'Das importierte Paket beinhaltet keine Dateien im "{review}" Verzeichnis.'],
        ];
        $code = $e->getErrorCode();
        if(empty($codeToFieldAndMessage[$code])) {
            throw $e;
        }
        // the config exceptions causing unprossable entity exceptions are logged on level info
        $this->log->exception($e, [
            'level' => ZfExtended_Logger::LEVEL_INFO
        ]);

        throw ZfExtended_UnprocessableEntity::createResponseFromOtherException($e, [
            //fieldName => error message to field
            $codeToFieldAndMessage[$code][0] => $codeToFieldAndMessage[$code][1]
        ]);
    }

    /**
     * Converts the IntegrityConstraint Exceptions caused by wrong user input to ZfExtended_UnprocessableEntity exceptions
     * @param ZfExtended_Models_Entity_Exceptions_IntegrityConstraint $e
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_UnprocessableEntity
     * @throws ZfExtended_ErrorCodeException
     */
    protected function handleIntegrityConstraint(ZfExtended_Models_Entity_Exceptions_IntegrityConstraint $e) {
        //check if the error comes from the customer assoc or not
        if(! $e->isInMessage('REFERENCES `LEK_customer`')) {
            throw $e;
        }
        throw ZfExtended_UnprocessableEntity::createResponse('E1064', [
            'customerId' => 'Der referenzierte Kunde existiert nicht (mehr)'
        ], [], $e);
    }

    /***
     * @param editor_Models_Import_DataProvider_Exception $e
     * @return mixed
     * @throws ZfExtended_ErrorCodeException
     */
    protected function handleDataProviderException(editor_Models_Import_DataProvider_Exception $e){
        //FIXME ZfExtended_Models_Entity_Conflict::addCodes(); is missing / ecode is duplicated!
        throw ZfExtended_Models_Entity_Conflict::createResponse('E1369',[
            'targetLang[]' => 'No work files found for one of the target languages. This happens when the user selects multiple target languages in the dropdown and then imports a bilingual file via drag and drop.',
        ],[],$e);
    }

}
