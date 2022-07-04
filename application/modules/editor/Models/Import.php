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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

/**
 * Starts an import by gathering all needed data, check and store it, and start an Import Worker
 */
class editor_Models_Import {
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * @var ZfExtended_EventManager
     */
    protected $events;
    
    /**
     * @var editor_Models_Import_Configuration
     */
    protected $importConfig;
    
    /**
     * Konstruktor
     */
    public function __construct(){
        //we should use __CLASS__ here, if not we loose bound handlers to base class in using subclasses
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
        $this->importConfig = ZfExtended_Factory::get('editor_Models_Import_Configuration');
    }

    /**
     * führt den Import aller Dateien eines Task durch
     * @param editor_Models_Import_DataProvider_Abstract $dataProvider
     * @param array $requestData: this should be the controller's request data
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function import(editor_Models_Import_DataProvider_Abstract $dataProvider, array $requestData=[]) {
        if(empty($this->task)){
            throw new Zend_Exception('taskGuid not set - please set using $this->setTask/$this->createTask');
        }
        Zend_Registry::set('affected_taskGuid', $this->task->getTaskGuid()); //for TRANSLATE-600 only
        
        //pre import methods:
        try {

            $dataProvider->checkAndPrepare($this->task);

            // After the files are moved, set the languages for the import configuration. For project uploads, relais language
            // is evaluated based on the file name match. If no relais file match for the current workfile is found, the same check will be
            // done for the following up project tasks
            $this->setLanguages(
                $this->task->getSourceLang(),
                $this->task->getTargetLang(),
                $this->task->getRelaisLang(),
                editor_Models_Languages::LANG_TYPE_ID);

            // trigger an event that gives plugins a chance to hook into the import process after unpacking/checking the files and before archiving them
            $this->events->trigger("afterUploadPreparation", $this, array(
                'task' => $this->task,
                'dataProvider' => $dataProvider,
                'requestData' => $requestData
            ));
            
            $dataProvider->archiveImportedData();
            $this->importConfig->importFolder = $dataProvider->getAbsImportPath();
            
            $this->importConfig->isValid($this->task->getTaskGuid());
            
            if(! $this->importConfig->hasRelaisLanguage()) {
                //reset given relais language value if no relais data is provided / feature is off
                $this->task->setRelaisLang(0); 
            }
            
            $this->task->save(); //Task erst Speichern wenn die obigen validates und checks durch sind.
            $this->task->meta()->save(); /** @see editor_Controllers_Task_ImportTrait::processUploadedFile Creates meta */

            $this->task->lock(NOW_ISO, $this->task::STATE_IMPORT); //locks the task
            
            $this->events->trigger('beforeImport', $this, array(
                    'task' => $this->task,
                    'importFolder' => $this->importConfig->importFolder
            ));
        }
        catch (Exception $e) {
            //the DP exception handler is only needed before we have a valid task in the database, 
            // after that the cleanup is done implicitly by deleting the erroneous task, which is not possible before.
            $this->task->setErroneous();
            $dataProvider->handleImportException($e);
            throw $e;
        }

        /** @var editor_Models_Import_TaskConfig $taskConfig */
        $taskConfig = ZfExtended_factory::get('editor_Models_Import_TaskConfig');
        $taskConfig->loadConfigTemplate($this->task, $this->importConfig);

        $this->queueImportWorkers($dataProvider);
    }
    
    /**
     * Using this proxy method for triggering the event to keep the legacy code bound to this class instead to the new worker class
     * @param editor_Models_Task $task
     */
    public function triggerAfterImport(editor_Models_Task $task, int $parentWorkerId, editor_Models_Import_Configuration $importConfig) {
        $this->events->trigger('afterImport', $this, [
                'task' => $task, 
                'parentWorkerId' => $parentWorkerId,
                'importConfig' => $importConfig
        ]);
    }
    
    /**
     * Using this proxy method for triggering the event to keep the legacy code bound to this class instead to the new worker class
     * @param editor_Models_Task $task
     * @param int $parentWorkerId
     * @param editor_Models_Import_Configuration $importConfig
     */
    public function triggerAfterImportError(editor_Models_Task $task, int $parentWorkerId, editor_Models_Import_Configuration $importConfig) {
        $this->events->trigger('afterImportError', $this, [
            'task' => $task,
            'parentWorkerId' => $parentWorkerId,
            'importConfig' => $importConfig
        ]);
    }
    
    /**
     * sets the info/data to the user
     * @param string $userguid
     * @param string $username
     */
    public function setUserInfos(string $userguid, string $username) {
        $this->importConfig->userName = $username;
        $this->importConfig->userGuid = $userguid;
    }

    /**
     * sets a optional taskname and options of the imported task
     * returns the created task
     * Current Options: 
     *   enableSourceEditing => boolean
     * @param stdClass $params
     * @return editor_Models_Task
     */
    public function createTask(stdClass $params) {
        $config = Zend_Registry::get('config');
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->setTaskName($params->taskName);
        $task->setTaskGuid($params->taskGuid);
        $task->setPmGuid($params->pmGuid);
        $task->setLockLocked((int)$params->lockLocked);
        $task->setImportAppVersion(ZfExtended_Utils::getAppVersion());
        $task->setUsageMode($config->runtimeOptions->import->initialTaskUsageMode);
        
        $pm = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $pm ZfExtended_Models_User */
        try {
            $pm->loadByGuid($params->pmGuid);
            $task->setPmName($pm->getUsernameLong());
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e){
            $task->setPmName('- not found -');
        }
        
        $task->setTaskNr($params->taskNr);
        
        $sourceId = empty($this->importConfig->sourceLang) ? 0 : $this->importConfig->sourceLang->getId();
        $task->setSourceLang($sourceId);
        $targetId = empty($this->importConfig->targetLang) ? 0 : $this->importConfig->targetLang->getId();
        $task->setTargetLang($targetId);
        $relaisId = empty($this->importConfig->relaisLang) ? 0 : $this->importConfig->relaisLang->getId();
        $task->setRelaisLang($relaisId);
        
        $task->setWorkflow($params->workflow);
        $task->setWordCount($params->wordCount);
        $task->setOrderdate($params->orderdate);
        //Task based Source Editing can only be enabled if its allowed in the whole editor instance 
        $enableSourceEditing = (bool) $config->runtimeOptions->import->enableSourceEditing;
        $task->setEnableSourceEditing((int) (! empty($params->enableSourceEditing) && $enableSourceEditing));

        //100% matches editable can be only enabled if it is allowed/defined on system level
        $edit100PercentMatch = (bool) $config->runtimeOptions->frontend->importTask->edit100PercentMatch;
        $task->setEdit100PercentMatch((int) (! empty($params->editFullMatch) && $edit100PercentMatch));
        
        $task->setCustomerId($params->customerId ?? ZfExtended_Factory::get('editor_Models_Customer_Customer')->loadByDefaultCustomer()->getId());
        
        $task->validate();
        $this->setTask($task);
        return $task;
    }
    
    /**
     * sets the internal needed Task, inits the Task Directory
     * @param editor_Models_Task $task
     */
    public function setTask(editor_Models_Task $task) {
        $this->task = $task;
        $this->task->initTaskDataDirectory();
    }

    /**
     * Setzt die zu importierende Quell und Zielsprache, das Format der Sprach IDs wird über den Parameter $type festgelegt
     * @param mixed $source
     * @param mixed $target
     * @param mixed $relais Relaissprache, kann null/leer sein wenn es keine Relaissprache gibt
     * @param string $type
     */
    public function setLanguages($source, $target, $relais, $type = editor_Models_Languages::LANG_TYPE_RFC5646) {
        $this->importConfig->setLanguages($source, $target, $relais, $type);
    }
    
    /**
     * add and run all the needed import workers
     * @param editor_Models_Import_DataProvider_Abstract $dataProvider
     */
    protected function queueImportWorkers(editor_Models_Import_DataProvider_Abstract $dataProvider) {
        $taskGuid = $this->task->getTaskGuid();
        $params = ['config' => $this->importConfig];
        /**
         * Queue FileTree and RefFileTree Worker
         */
        $fileTreeWorker = ZfExtended_Factory::get('editor_Models_Import_Worker_FileTree');
        /* @var $fileTreeWorker editor_Models_Import_Worker_FileTree */
        $fileTreeWorker->init($taskGuid, $params);
        $fileTreeWorker->queue(0, ZfExtended_Models_Worker::STATE_PREPARE, false);
        
        $refTreeWorker = ZfExtended_Factory::get('editor_Models_Import_Worker_ReferenceFileTree');
        /* @var $refTreeWorker editor_Models_Import_Worker_ReferenceFileTree */
        $refTreeWorker->init($taskGuid, $params);
        $refTreeWorker->queue(0, ZfExtended_Models_Worker::STATE_PREPARE, false);
        
        /**
         * Queue Import Worker
         */
        $importWorker = ZfExtended_Factory::get('editor_Models_Import_Worker');
        /* @var $importWorker editor_Models_Import_Worker */
        $params['dataProvider'] = $dataProvider;
        $importWorker->init($taskGuid, $params);

        //prevent the importWorker to be started here. 
        $parentId = $importWorker->queue(0, ZfExtended_Models_Worker::STATE_PREPARE, false);

        //since none of the above workers are started yet, we can safely update the fileTreeWorkers parentId
        $fileTreeWorker->getModel()->setParentId($parentId);
        $fileTreeWorker->getModel()->save();
        $refTreeWorker->getModel()->setParentId($parentId);
        $refTreeWorker->getModel()->save();

        //sometimes it is not possbile for additional import workers to be invoked in afterImport, 
        // for that reason this event exists:
        $this->events->trigger('importWorkerQueued', $this, ['task' => $this->task, 'workerId' => $parentId]);
        
        $worker = ZfExtended_Factory::get('editor_Models_Import_Worker_SetTaskToOpen');
        /* @var $worker editor_Models_Import_Worker_SetTaskToOpen */
        // queuing this worker when task has errors make no sense, init checks this.
        if($worker->init($taskGuid, ['config' => $this->importConfig])) {
            $worker->queue($parentId, null, false); 
        }
        
        // queueing the quality workers, which have a kind of "sub-plugin" systematic to trigger the dependant workers like termtaggers etc.
        editor_Segment_Quality_Manager::instance()->queueImport($this->task, $parentId);
        
        // the worker finishing the import        
        $worker = ZfExtended_Factory::get('editor_Models_Import_Worker_FinalStep');
        /* @var $worker editor_Models_Import_Worker_FinalStep */
        if($worker->init($taskGuid, ['config' => $this->importConfig])) {
            $worker->queue($parentId); 
        }
    }
    
    /***
     * Get the import configuration
     * @return editor_Models_Import_Configuration
     */
    public function getImportConfig() {
        return $this->importConfig;
    }

    /**
     * Sets importing tasks to status error if the import was started more then 48 (default; configurable runtimeOptions.import.timeout) hours ago
     * @throws Zend_Exception
     */
    public function cleanupDanglingImports()
    {
        /** @var editor_Models_Task $task */
        $task = ZfExtended_Factory::get('editor_Models_Task');

        /** @var ZfExtended_Models_Worker $worker */
        $worker = ZfExtended_Factory::get('ZfExtended_Models_Worker');

        $config = Zend_Registry::get('config');
        $hours = (int) ($config->runtimeOptions->import->timeout ?? 48);
        $s = $task->db->select()
            ->where('state = ?', $task::STATE_IMPORT)
            ->where('created < DATE_SUB(NOW(), INTERVAL ? HOUR)', $hours);
        $danglingTasks = $task->db->fetchAll($s);

        $finalStepWorker = 'editor_Models_Import_Worker_FinalStep';

        foreach ($danglingTasks as $data) {
            $task->init($data->toArray());

            //log the dangling task
            $task->logger()->error('E1379', 'The task import was cancelled after {hours} hours.',[
                'task' => $task,
                'hours' => $hours,
            ]);

            //set the task to status error
            $task->setErroneous();

            //if there are still some workers,
            try {
                $worker->loadFirstOf($finalStepWorker, $task->getTaskGuid());
                $worker->defuncRemainingOfGroup([$finalStepWorker], true, true);
                $worker->wakeupScheduled();

                /** @var ZfExtended_Worker_TriggerByHttp $trigger */
                $trigger = ZfExtended_Factory::get('ZfExtended_Worker_TriggerByHttp');
                $trigger->triggerWorker($worker->getId(), $worker->getHash());
            }
            catch (ZfExtended_Models_Entity_NotFoundException) {
                //do nothing
            }
        }
    }
}
