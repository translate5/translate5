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

use MittagQI\Translate5\File\Filter\FilterException;
use MittagQI\Translate5\File\Filter\Manager;
use MittagQI\Translate5\LanguageResource\Pretranslation\PivotQueuer;
use MittagQI\Translate5\LanguageResource\TaskPivotAssociation;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Task\Import\FileParser\Factory;
use MittagQI\Translate5\Workflow\SetupInitialWorkflow;
use ZfExtended_Zendoverwrites_Controller_Action_HelperBroker as HelperBroker;

/**
 * Encapsulates the part of the import logic which is intended to be run in a worker
 */
class editor_Models_Import_Worker_Import
{
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

    /**
     * @throws ReflectionException
     * @throws Zend_Exception
     */
    private readonly editor_Workflow_Manager $workflowManager;

    private readonly SetupInitialWorkflow $setupInitialWorkflow;

    private readonly TaskRepository $taskRepository;

    public function __construct()
    {
        $this->_localEncoded = HelperBroker::getStaticHelper('LocalEncoded');
        $this->segmentFieldManager = ZfExtended_Factory::get(editor_Models_SegmentFieldManager::class);
        //we should use __CLASS__ here, if not we loose bound handlers to base class in using subclasses
        $this->events = ZfExtended_Factory::get(ZfExtended_EventManager::class, [__CLASS__]);
        $this->workflowManager = new editor_Workflow_Manager();
        $this->setupInitialWorkflow = SetupInitialWorkflow::create();
        $this->taskRepository = TaskRepository::create();
    }

    /**
     * starts the main part of the file import which is intended to run in a worker
     */
    public function import(editor_Models_Task $task, editor_Models_Import_Configuration $importConfig)
    {
        $this->importConfig = $importConfig;

        $this->filelist = ZfExtended_Factory::get(editor_Models_Import_FileList::class, [$this->importConfig, $task]);

        $this->segmentFieldManager->initFields($task->getTaskGuid());

        //call import Methods:
        $this->importFiles($task);
        $this->syncFileOrderAndRepetitions($task);

        $this->importRelaisFiles($task);
        $task->createMaterializedView();
        $this->calculateMetrics($task);
        //saving task twice is the simplest way to do this. has meta data is only available after import.
        $task->save();

        //init default user prefs
        $workflow = $this->workflowManager->getCached($task->getWorkflow());
        $this->setupInitialWorkflow->setup($workflow, $task);
        $workflow->hookin()->doImport($task, $importConfig);
        $this->workflowManager->initDefaultUserPrefs($task);
    }

    protected function importFiles(editor_Models_Task $task): void
    {
        $treeDb = ZfExtended_Factory::get(editor_Models_Foldertree::class);
        $treeDb->setPathPrefix($this->importConfig->getWorkfilesDirName());
        $filelist = $treeDb->getPaths($task->getTaskGuid(), 'file');

        $fileFilter = ZfExtended_Factory::get(Manager::class);
        $fileFilter->addByConfig($task->getTaskGuid(), $this->importConfig, $filelist);
        $fileFilter->initImport($task, $this->importConfig);

        $mqmProc = ZfExtended_Factory::get(editor_Models_Import_SegmentProcessor_MqmParser::class, [
            $task,
            $this->segmentFieldManager,
        ]);
        $repHash = ZfExtended_Factory::get(editor_Models_Import_SegmentProcessor_RepetitionHash::class, [
            $task,
            $this->segmentFieldManager,
        ]);
        $segProc = ZfExtended_Factory::get(editor_Models_Import_SegmentProcessor_Review::class, [
            $task,
            $this->importConfig,
        ]);
        $parserHelper = ZfExtended_Factory::get(Factory::class, [
            $task,
            $this->segmentFieldManager,
        ]);

        $filesProcessedAtAll = 0;
        $isReImportable = true;

        $fileModel = ZfExtended_Factory::get(editor_Models_File::class);

        foreach ($filelist as $fileId => $path) {
            try {
                $filelist[$fileId] = $path = $fileFilter->applyImportFilters($path, $fileId);
            } catch (FilterException $e) {
                // when a FileFilter throws an exception it means the filter failed
                // this creates the needed Task-event then to inform users
                Zend_Registry::get('logger')->exception($e, [
                    'level' => ZfExtended_Logger::LEVEL_ERROR,
                ]);

                continue;
            }

            $filePath = $this->importConfig->importFolder . '/' . $path;
            $parser = $parserHelper->getFileParserByExtension($fileId, $filePath);
            if (! $parser) {
                continue;
            }

            /* @var $parser editor_Models_Import_FileParser */
            $segProc->setSegmentFile($fileId, $parser->getFileName()); //$params[1] => filename
            $parser->addSegmentProcessor($mqmProc);
            $parser->addSegmentProcessor($repHash);
            $parser->addSegmentProcessor($segProc);
            $parser->parseFile();
            $filesProcessedAtAll++;

            // in case the file is re-importable, update the isReimportable flag for the current file
            if ($parser::IS_REIMPORTABLE) {
                $fileModel->load($fileId);
                $fileModel->setIsReimportable($parser::IS_REIMPORTABLE);
                $fileModel->save();
            }

            // task is not reimportable if one of the files is not supported by the reimport parser
            if ($parser::IS_REIMPORTABLE === false) {
                $isReImportable = false;
            }
        }

        if ($filesProcessedAtAll === 0) {
            //E1166: Although there were importable files in the task, no files were imported. Investigate the log for preceeding errors.
            throw new editor_Models_Import_FileParser_NoParserException('E1166', [
                'task' => $task,
            ]);
        }

        $mqmProc->handleErrors();

        $task->setReferenceFiles($this->filelist->hasReferenceFiles() ? 1 : 0);
        $task->setReimportable($isReImportable ? 1 : 0);
    }

    /**
     * Calculates and sets the task metrics emptyTargets (bool), wordCount (int) and segmentCount(int)
     * @throws Zend_Db_Select_Exception|ReflectionException
     */
    protected function calculateMetrics(editor_Models_Task $task)
    {
        $taskGuid = $task->getTaskGuid();

        $segment = ZfExtended_Factory::get(editor_Models_Segment::class);
        $progress = ZfExtended_Factory::get(editor_Models_TaskProgress::class);
        $meta = ZfExtended_Factory::get(editor_Models_Segment_Meta::class);

        $task->setEmptyTargets($segment->hasEmptyTargetsOnly($taskGuid));

        //we may set the tasks wordcount only to our calculated values if there was no count given either by API or by import formats
        if ($task->getWordCount() == 0) {
            $task->setWordCount($meta->getWordCountSum($task));
        }

        $task->setSegmentCount($segment->getTotalSegmentsCount($taskGuid));
        $progress->refreshProgress($task);
    }

    /**
     * Importiert die Relais Dateien
     */
    protected function importRelaisFiles(editor_Models_Task $task): void
    {
        if (! $this->importConfig->hasRelaisLanguage()) {
            return;
        }

        $relayFiles = $this->filelist->processRelaisFiles();

        if (empty($relayFiles)) {
            $this->onPivotFilesNotFound($task);

            return;
        }

        // when there are files for pivot, we do not need the pivot language resources associations
        // remove all of them for the current project/task
        $this->removePivotAssoc($task);

        $mqmProc = ZfExtended_Factory::get(editor_Models_Import_SegmentProcessor_MqmParser::class, [
            $task,
            $this->segmentFieldManager,
        ]);
        $repHash = ZfExtended_Factory::get(editor_Models_Import_SegmentProcessor_RepetitionHash::class, [
            $task,
            $this->segmentFieldManager,
        ]);
        $segProc = ZfExtended_Factory::get(editor_Models_Import_SegmentProcessor_Relais::class, [
            $task,
            $this->segmentFieldManager,
        ]);
        $parserHelper = ZfExtended_Factory::get(Factory::class, [
            $task,
            $this->segmentFieldManager,
        ]);

        foreach ($relayFiles as $fileId => $path) {
            $filePath = $this->importConfig->importFolder . '/' . $path;
            $parser = $parserHelper->getFileParserByExtension($fileId, $filePath);
            if (! $parser) {
                continue;
            }
            /** @var editor_Models_Import_FileParser $parser */
            $segProc->setSegmentFile($fileId, $parser->getFileName());
            $parser->addSegmentProcessor($mqmProc);
            $parser->addSegmentProcessor($repHash);
            $parser->addSegmentProcessor($segProc);
            $parser->parseFile();
        }
        $mqmProc->handleErrors();
    }

    protected function syncFileOrderAndRepetitions(editor_Models_Task $task)
    {
        $segment = ZfExtended_Factory::get(editor_Models_Segment::class);
        //dont update view here, since it is not existing yet!
        $segment->syncFileOrderFromFiles($task->getTaskGuid(), true);
        $segment->syncRepetitions($task->getTaskGuid(), false);
    }

    /***
     * Check and create the pivot row if there are pivot translate assocs.
     */
    public function onPivotFilesNotFound(editor_Models_Task $task): void
    {
        $pivotAssoc = ZfExtended_Factory::get(TaskPivotAssociation::class);
        $associations = $pivotAssoc->loadTaskAssociated($task->getTaskGuid());

        // if no reference files where found, check for pivot pre-translation associations.
        if (! empty($associations)) {
            // add the relais field when there are no files but only resources for pre-translation
            $this->segmentFieldManager->addField($this->segmentFieldManager::LABEL_RELAIS, editor_Models_SegmentField::TYPE_RELAIS, false);

            // If the auto-queue config is set, queue the pivot worker
            if ($task->getConfig()->runtimeOptions->import->autoStartPivotTranslations) {
                $worker = ZfExtended_Factory::get(PivotQueuer::class);
                $worker->queuePivotWorker($task->getTaskGuid());
            }
        } else {
            // log the missing relais files if no pivot associations are found
            $this->filelist->getRelaisFolderTree()->logMissingFile();
            // remove the relais lang when no pivot assoc are found
            $task->setRelaisLang(0);
        }
    }

    /***
     * Remove all pivot assocs for the current project/task
     * @return void
     */
    protected function removePivotAssoc(editor_Models_Task $task): void
    {
        $logger = Zend_Registry::get('logger')->cloneMe('languageresources.pivotpretranslation');
        $assoc = ZfExtended_Factory::get(TaskPivotAssociation::class);

        if ($task->isProject()) {
            $projectTasks = $this->taskRepository->getProjectTaskList((int) $task->getProjectId());

            foreach ($projectTasks as $projectTask) {
                if ($assoc->deleteAllForTask($projectTask->getTaskGuid())) {
                    $logger->info('E1011', 'Default user associations removed: Files will be used as pivot source.', [
                        'task' => $projectTask,
                    ]);
                }
            }

            return;
        }

        if ($assoc->deleteAllForTask($task->getTaskGuid())) {
            $logger->info('E1011', 'Default user associations removed: Files will be used as pivot source.', [
                'task' => $task,
            ]);
        }
    }
}
