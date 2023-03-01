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

use MittagQI\Translate5\LanguageResource\Pretranslation\PivotQueuer;
use MittagQI\Translate5\LanguageResource\TaskPivotAssociation;
use MittagQI\Translate5\Task\Import\FileParser\Factory;

/**
 * Encapsulates the part of the import logic which is intended to be run in a worker
 */
class editor_Models_Import_Worker_Import {
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * @var ZfExtended_Controller_Helper_LocalEncoded
     */
    protected $_localEncoded;

    /**
     * shared instance over all parse objects of the segment field manager
     * @var editor_Models_SegmentFieldManager
     */
    protected $segmentFieldManager;
    
    /**
     * @var editor_Models_Import_FileList
     */
    protected $filelist;
    
    /**
     * @var ZfExtended_EventManager
     */
    protected $events;

    private editor_Models_Import_Configuration $importConfig;

    public function __construct() {
        $this->_localEncoded = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper('LocalEncoded');
        $this->segmentFieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        //we should use __CLASS__ here, if not we loose bound handlers to base class in using subclasses
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
    }
    
    /**
     * starts the main part of the file import which is intended to run in a worker
     * @param string $taskGuid
     * @param editor_Models_Import_Configuration $importConfig
     */
    public function import(editor_Models_Task $task, editor_Models_Import_Configuration $importConfig) {
        $this->task = $task;
        $this->importConfig = $importConfig;
        
        $importConfig->isValid($task->getTaskGuid());
        $this->filelist = ZfExtended_Factory::get('editor_Models_Import_FileList', array($this->importConfig, $this->task));
        
        //down from here should start the import worker
        //in the worker again:
        Zend_Registry::set('affected_taskGuid', $this->task->getTaskGuid()); //for TRANSLATE-600 only
        
        $this->segmentFieldManager->initFields($this->task->getTaskGuid());

        $this->events->trigger('beforeImportFiles', $this, ['task' => $task, 'importConfig' => $importConfig]);
        
        //call import Methods:
        $this->importFiles();
        $this->syncFileOrderAndRepetitions();
        $this->importRelaisFiles();
        $this->task->createMaterializedView();
        $this->calculateMetrics();
        //saving task twice is the simplest way to do this. has meta data is only available after import.
        $this->task->save();
        
        //init default user prefs
        $workflowManager = ZfExtended_Factory::get('editor_Workflow_Manager');
        /* @var $workflowManager editor_Workflow_Manager */
        $workflowManager->getByTask($this->task)->hookin()->doImport($this->task, $importConfig);
        $workflowManager->initDefaultUserPrefs($this->task);
        
        $this->events->trigger('importCleanup', $this, ['task' => $task, 'importConfig' => $importConfig]);
    }
    
    /**
     * Importiert die Dateien und erzeugt die Taggrafiken
     */
    protected function importFiles(){

        $treeDb = ZfExtended_Factory::get('editor_Models_Foldertree');
        /* @var $treeDb editor_Models_Foldertree */
        $treeDb->setPathPrefix($this->importConfig->getFilesDirectory());
        $filelist = $treeDb->getPaths($this->task->getTaskGuid(),'file');
        
        $fileFilter = ZfExtended_Factory::get(editor_Models_File_FilterManager::class);
        $fileFilter->initImport($this->task, $this->importConfig);
            
        $mqmProc = ZfExtended_Factory::get('editor_Models_Import_SegmentProcessor_MqmParser', array($this->task, $this->segmentFieldManager));
        $repHash = ZfExtended_Factory::get('editor_Models_Import_SegmentProcessor_RepetitionHash', array($this->task, $this->segmentFieldManager));
        $segProc = ZfExtended_Factory::get('editor_Models_Import_SegmentProcessor_Review', array($this->task, $this->importConfig));
        /* @var $segProc editor_Models_Import_SegmentProcessor_Review */

        /** @var Factory $parserHelper */
        $parserHelper = ZfExtended_Factory::get(Factory::class,[
            $this->task,
            $this->segmentFieldManager
        ]);

        $filesProcessedAtAll = 0;
        foreach ($filelist as $fileId => $path) {
            $filelist[$fileId] = $path = $fileFilter->applyImportFilters($path, $fileId);
            $filePath = $this->importConfig->importFolder.'/'.$path;
            $parser = $parserHelper->getFileParserByExtension($fileId, $filePath);
            if(!$parser) {
                continue;
            }
            
            /* @var $parser editor_Models_Import_FileParser */
            $segProc->setSegmentFile($fileId, $parser->getFileName()); //$params[1] => filename
            $parser->addSegmentProcessor($mqmProc);
            $parser->addSegmentProcessor($repHash);
            $parser->addSegmentProcessor($segProc);
            $parser->parseFile();
            $filesProcessedAtAll++;
        }
        
        if($filesProcessedAtAll === 0) {
            //E1166: Although there were importable files in the task, no files were imported. Investigate the log for preceeding errors.
            throw new editor_Models_Import_FileParser_NoParserException('E1166', [
                'task' => $this->task
            ]);
        }
        
        $mqmProc->handleErrors();
        
        $this->task->setReferenceFiles($this->filelist->hasReferenceFiles());
    }
    
    /**
     * Calculates and sets the task metrics emptyTargets (bool), wordCount (int) and segmentCount(int)
     */
    protected function calculateMetrics() {
        $taskGuid = $this->task->getTaskGuid();
        
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        
        $meta = ZfExtended_Factory::get('editor_Models_Segment_Meta');
        /* @var $meta editor_Models_Segment_Meta */
        
        /* @var $segment editor_Models_Segment */
        $this->task->setEmptyTargets($segment->hasEmptyTargetsOnly($taskGuid));

        //we may set the tasks wordcount only to our calculated values if there was no count given either by API or by import formats
        if ($this->task->getWordCount() == 0) {
            $this->task->setWordCount($meta->getWordCountSum($this->task));
        }
        
        $this->task->setSegmentCount($segment->getTotalSegmentsCount($taskGuid));
    }
    
    /**
     * Importiert die Relais Dateien
     */
    protected function importRelaisFiles(): void
    {
        if(! $this->importConfig->hasRelaisLanguage()){
            return;
        }

        $relayFiles = $this->filelist->processRelaisFiles();

        if(empty($relayFiles)){
            $this->onPivotFilesNotFound();
            return;
        }

        // when there are files for pivot, we do not need the pivot language resources associations
        // remove all of them for the current project/task
        $this->removePivotAssoc();
        
        $mqmProc = ZfExtended_Factory::get('editor_Models_Import_SegmentProcessor_MqmParser', array($this->task, $this->segmentFieldManager));
        $repHash = ZfExtended_Factory::get('editor_Models_Import_SegmentProcessor_RepetitionHash', array($this->task, $this->segmentFieldManager));
        $segProc = ZfExtended_Factory::get('editor_Models_Import_SegmentProcessor_Relais', array($this->task, $this->segmentFieldManager));
        /* @var $segProc editor_Models_Import_SegmentProcessor_Relais */

        /** @var Factory $parserHelper */
        $parserHelper = ZfExtended_Factory::get(Factory::class,[
            $this->task,
            $this->segmentFieldManager
        ]);

        foreach ($relayFiles as $fileId => $path) {
            $filePath = $this->importConfig->importFolder.'/'.$path;
            $parser = $parserHelper->getFileParserByExtension($fileId, $filePath);
            if(!$parser) {
                continue;
            }
            /* @var $parser editor_Models_Import_FileParser */
            $segProc->setSegmentFile($fileId, $parser->getFileName());
            $parser->addSegmentProcessor($mqmProc);
            $parser->addSegmentProcessor($repHash);
            $parser->addSegmentProcessor($segProc);
            $parser->parseFile();
    	}
        $mqmProc->handleErrors();
    }
    
    protected function syncFileOrderAndRepetitions() {
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        //dont update view here, since it is not existing yet!
        $segment->syncFileOrderFromFiles($this->task->getTaskGuid(), true);
        $segment->syncRepetitions($this->task->getTaskGuid(), false);
    }

    /***
     * Check and create the pivot row if there are pivot translate assocs.
     */
    public function onPivotFilesNotFound(): void
    {
        /** @var TaskPivotAssociation $pivotAssoc */
        $pivotAssoc = ZfExtended_Factory::get(TaskPivotAssociation::class);
        $associations = $pivotAssoc->loadTaskAssociated($this->task->getTaskGuid());

        // if no reference files where found, check for pivot pre-translation associations.
        if(!empty($associations)){
            // add the relais field when there are no files but only resources for pre-translation
            $this->segmentFieldManager->addField($this->segmentFieldManager::LABEL_RELAIS, editor_Models_SegmentField::TYPE_RELAIS, false);

            // If the auto-queue config is set, queue the pivot worker
            if($this->task->getConfig()->runtimeOptions->import->autoStartPivotTranslations){
                /** @var PivotQueuer $worker */
                $worker = ZfExtended_Factory::get(PivotQueuer::class);
                $worker->queuePivotWorker($this->task->getTaskGuid());
            }
        }else{
            // log the missing relais files if no pivot associations are found
            $this->filelist->getRelaisFolderTree()->logMissingFile();
            // remove the relais lang when no pivot assoc are found
            $this->task->setRelaisLang(null);
        }

    }

    /***
     * Remove all pivot assocs for the current project/task
     * @return void
     */
    protected function removePivotAssoc(): void
    {
        if($this->task->isProject()){
            /** @var editor_Models_Task $task */
            $task = ZfExtended_Factory::get('editor_Models_Task');
            $projectTasks = $task->loadProjectTasks($this->task->getProjectId(),true);
            $taskGuids = array_column($projectTasks,'taskGuid');
        }else{
            $taskGuids = [$this->task->getTaskGuid()];
        }

        $logger = Zend_Registry::get('logger')->cloneMe('languageresources.pivotpretranslation');

        foreach ($taskGuids as $taskGuid){
            /** @var TaskPivotAssociation $assoc */
            $assoc = ZfExtended_Factory::get('MittagQI\Translate5\LanguageResource\TaskPivotAssociation');
            if($assoc->deleteAllForTask($taskGuid)){
                /** @var editor_Models_Task $task */
                $task = ZfExtended_Factory::get('editor_Models_Task');
                $task->loadByTaskGuid($taskGuid);
                $logger->info('E1011','Default user associations removed: Files will be used as pivot source.',[
                    'task' => $task
                ]);
            }
        }
    }
}
