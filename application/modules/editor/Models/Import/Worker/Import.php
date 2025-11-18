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
use MittagQI\Translate5\Task\Import\FileParser\Factory;

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

    public function __construct()
    {
        $this->_localEncoded = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper('LocalEncoded');
        $this->segmentFieldManager = ZfExtended_Factory::get(editor_Models_SegmentFieldManager::class);
        //we should use __CLASS__ here, if not we loose bound handlers to base class in using subclasses
        $this->events = ZfExtended_Factory::get(ZfExtended_EventManager::class, [__CLASS__]);
    }

    /**
     * starts the main part of the file import which is intended to run in a worker
     */
    public function import(editor_Models_Task $task, editor_Models_Import_Configuration $importConfig)
    {
        $this->task = $task;
        $this->importConfig = $importConfig;

        $this->filelist = ZfExtended_Factory::get(editor_Models_Import_FileList::class, [$this->importConfig, $this->task]);

        $this->segmentFieldManager->initFields($this->task->getTaskGuid());

        //call import Methods:
        $this->importFiles();
        $this->syncFileOrderAndRepetitions();
        $this->importRelaisFiles();
        $this->task->createMaterializedView();
        $this->calculateMetrics();
        //saving task twice is the simplest way to do this. has meta data is only available after import.
        $this->task->save();

        //init default user prefs
        $workflowManager = ZfExtended_Factory::get(editor_Workflow_Manager::class);
        $workflowManager->getByTask($this->task)->hookin()->doImport($this->task, $importConfig);
        $workflowManager->initDefaultUserPrefs($this->task);
    }

    protected function importFiles(): void
    {
        $treeDb = ZfExtended_Factory::get(editor_Models_Foldertree::class);
        $treeDb->setPathPrefix($this->importConfig->getWorkfilesDirName());
        $filelist = $treeDb->getPaths($this->task->getTaskGuid(), 'file');

        $fileFilter = ZfExtended_Factory::get(Manager::class);
        $fileFilter->addByConfig($this->task->getTaskGuid(), $this->importConfig, $filelist);
        $fileFilter->initImport($this->task, $this->importConfig);

        $mqmProc = ZfExtended_Factory::get(editor_Models_Import_SegmentProcessor_MqmParser::class, [
            $this->task,
            $this->segmentFieldManager,
        ]);
        $repHash = ZfExtended_Factory::get(editor_Models_Import_SegmentProcessor_RepetitionHash::class, [
            $this->task,
            $this->segmentFieldManager,
        ]);
        $segProc = ZfExtended_Factory::get(editor_Models_Import_SegmentProcessor_Review::class, [
            $this->task,
            $this->importConfig,
        ]);
        $parserHelper = ZfExtended_Factory::get(Factory::class, [
            $this->task,
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
                'task' => $this->task,
            ]);
        }

        $mqmProc->handleErrors();

        $this->task->setReferenceFiles($this->filelist->hasReferenceFiles() ? 1 : 0);
        $this->task->setReimportable($isReImportable ? 1 : 0);
    }

    /**
     * Calculates and sets the task metrics emptyTargets (bool), wordCount (int) and segmentCount(int)
     * @throws Zend_Db_Select_Exception|ReflectionException
     */
    protected function calculateMetrics()
    {
        $taskGuid = $this->task->getTaskGuid();

        $segment = ZfExtended_Factory::get(editor_Models_Segment::class);
        $progress = ZfExtended_Factory::get(editor_Models_TaskProgress::class);
        $meta = ZfExtended_Factory::get(editor_Models_Segment_Meta::class);

        $this->task->setEmptyTargets($segment->hasEmptyTargetsOnly($taskGuid));

        //we may set the tasks wordcount only to our calculated values if there was no count given either by API or by import formats
        if ($this->task->getWordCount() == 0) {
            $this->task->setWordCount($meta->getWordCountSum($this->task));
        }

        $this->task->setSegmentCount($segment->getTotalSegmentsCount($taskGuid));
        $progress->refreshProgress($this->task);
    }

    /**
     * Importiert die Relais Dateien
     */
    protected function importRelaisFiles(): void
    {
        if (! $this->importConfig->hasRelaisLanguage()) {
            return;
        }

        $relayFiles = $this->filelist->processRelaisFiles();

        if (empty($relayFiles)) {
            $this->onPivotFilesNotFound();

            return;
        }

        // when there are files for pivot, we do not need the pivot language resources associations
        // remove all of them for the current project/task
        $this->removePivotAssoc();

        $mqmProc = ZfExtended_Factory::get(editor_Models_Import_SegmentProcessor_MqmParser::class, [
            $this->task,
            $this->segmentFieldManager,
        ]);
        $repHash = ZfExtended_Factory::get(editor_Models_Import_SegmentProcessor_RepetitionHash::class, [
            $this->task,
            $this->segmentFieldManager,
        ]);
        $segProc = ZfExtended_Factory::get(editor_Models_Import_SegmentProcessor_Relais::class, [
            $this->task,
            $this->segmentFieldManager,
        ]);
        $parserHelper = ZfExtended_Factory::get(Factory::class, [
            $this->task,
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

    protected function syncFileOrderAndRepetitions()
    {
        $segment = ZfExtended_Factory::get(editor_Models_Segment::class);
        //dont update view here, since it is not existing yet!
        $segment->syncFileOrderFromFiles($this->task->getTaskGuid(), true);
        $segment->syncRepetitions($this->task->getTaskGuid(), false);
    }

    /***
     * Check and create the pivot row if there are pivot translate assocs.
     */
    public function onPivotFilesNotFound(): void
    {
        $pivotAssoc = ZfExtended_Factory::get(TaskPivotAssociation::class);
        $associations = $pivotAssoc->loadTaskAssociated($this->task->getTaskGuid());

        // if no reference files where found, check for pivot pre-translation associations.
        if (! empty($associations)) {
            // add the relais field when there are no files but only resources for pre-translation
            $this->segmentFieldManager->addField($this->segmentFieldManager::LABEL_RELAIS, editor_Models_SegmentField::TYPE_RELAIS, false);

            // If the auto-queue config is set, queue the pivot worker
            if ($this->task->getConfig()->runtimeOptions->import->autoStartPivotTranslations) {
                $worker = ZfExtended_Factory::get(PivotQueuer::class);
                $worker->queuePivotWorker($this->task->getTaskGuid());
            }
        } else {
            // log the missing relais files if no pivot associations are found
            $this->filelist->getRelaisFolderTree()->logMissingFile();
            // remove the relais lang when no pivot assoc are found
            $this->task->setRelaisLang(0);
        }
    }

    /***
     * Remove all pivot assocs for the current project/task
     * @return void
     */
    protected function removePivotAssoc(): void
    {
        if ($this->task->isProject()) {
            $task = ZfExtended_Factory::get(editor_Models_Task::class);
            $projectTasks = $task->loadProjectTasks((int) $this->task->getProjectId(), true);
            $taskGuids = array_column($projectTasks, 'taskGuid');
        } else {
            $taskGuids = [$this->task->getTaskGuid()];
        }

        $logger = Zend_Registry::get('logger')->cloneMe('languageresources.pivotpretranslation');

        foreach ($taskGuids as $taskGuid) {
            $assoc = ZfExtended_Factory::get(\MittagQI\Translate5\LanguageResource\TaskPivotAssociation::class);
            if ($assoc->deleteAllForTask($taskGuid)) {
                $task = ZfExtended_Factory::get(editor_Models_Task::class);
                $task->loadByTaskGuid($taskGuid);
                $logger->info('E1011', 'Default user associations removed: Files will be used as pivot source.', [
                    'task' => $task,
                ]);
            }
        }
    }
}
