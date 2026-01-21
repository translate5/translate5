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
declare(strict_types=1);

namespace MittagQI\Translate5\Task\Import;

use editor_Models_Import_Configuration;
use editor_Models_Import_DataProvider_Abstract;
use editor_Models_Import_DataProvider_Factory;
use editor_Models_Import_TaskConfig;
use editor_Models_Import_UploadProcessor;
use editor_Models_Languages;
use editor_Models_Task;
use editor_Models_Task_Remover;
use editor_Models_TaskConfig;
use editor_Models_TaskUsageLog;
use editor_Task_Type;
use editor_Task_Type_Default;
use Exception;
use MittagQI\Translate5\Task\Meta\TaskMetaImmutableDTO;
use MittagQI\ZfExtended\Sanitizer\HttpRequest;
use ReflectionException;
use Throwable;
use Zend_Db_Statement_Exception;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Authentication;
use ZfExtended_Debug;
use ZfExtended_ErrorCodeException;
use ZfExtended_Exception;
use ZfExtended_Factory;
use ZfExtended_Languages;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_User;

class ImportService
{
    /**
     * TODO: still neccessary ?
     * Cache for preventig tasks-meta events to be called twice for a task per request
     */
    private static array $metaTasks = [];

    private TaskUsageLogger $usageLogger;

    private TaskDefaults $defaults;

    private ProjectWorkersService $workersService;

    private ImportEventTrigger $eventTrigger;

    private bool $doDebug;

    private array $taskMetaData = [];

    private bool $importWizardUsed = false;

    /**
     * @throws ReflectionException
     */
    public function __construct()
    {
        $this->defaults = TaskDefaults::create();
        $this->usageLogger = ZfExtended_Factory::get(
            TaskUsageLogger::class,
            [ZfExtended_Factory::get(editor_Models_TaskUsageLog::class)]
        );
        $this->workersService = new ProjectWorkersService();
        $this->eventTrigger = new ImportEventTrigger();
        $this->doDebug = ZfExtended_Debug::hasLevel('core', 'ImportService');
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws ReflectionException
     * @throws Zend_Exception
     */
    public function startWorkers(editor_Models_Task $task): void
    {
        if ($this->doDebug) {
            error_log('ImportService: start import workers' . "\n");
        }
        $this->workersService->startImportWorkers($task);
    }

    /**
     * @throws Exception
     */
    public function importFromPost(
        editor_Models_Task $project,
        HttpRequest $request,
        array $data,
    ): array {
        $single = $this->prepareTaskType(
            $project,
            count($data['targetLang']) > 1,
            $request->getParam('taskType', editor_Task_Type_Default::ID)
        );

        if ($this->doDebug) {
            error_log(
                'ImportService::importFromPost: ' . $project->getTaskType() .
                ($single ? ' as Single Task' : '') . "\n"
            );
        }

        $user = ZfExtended_Authentication::getInstance()->getUser();

        //must be saved in order that initAndValidate works correct
        $project->save();

        $this->eventTrigger->triggerOnImportFromPost($project, $request);

        //gets and validates the uploaded zip file
        $upload = editor_Models_Import_UploadProcessor::taskInstance($project);
        $upload->initAndValidate();

        $dpFactory = ZfExtended_Factory::get(editor_Models_Import_DataProvider_Factory::class);

        $this->addTaskMetaData($data); //uggly: contains more parameters as only the ones for meta data!
        $this->importWizardUsed = (bool) $request->getParam('importWizardUsed', false);

        if ($single) {
            if ($this->doDebug) {
                error_log(
                    'ImportService::importSingleTask: ' . $project->getTaskType() .
                    ($request->getParam('importWizardUsed', false) ? ' from Import-Wizard' : '') .
                    ', data: ' . print_r($data, true) . "\n"
                );
            }

            return $this->importSingleTask(
                $project,
                $dpFactory->createFromUpload($upload, $data),
                (int) reset($data['targetLang']),
                $user,
            );
        }

        return $this->importProject(
            $project,
            $dpFactory->createFromUpload($upload, $data),
            $data['targetLang'],
            $user,
        );
    }

    /**
     * Assoc array with possible additional task meta data,
     * passed to plugins for further usage and setting finally then in task meta
     */
    public function addTaskMetaData(array $metaData): void
    {
        $this->taskMetaData = $metaData;
    }

    /**
     * Handles the import for non project data.
     * This will evaluate what kind of data provider should be used, and it will process the uploaded files
     * @throws Exception
     */
    public function importSingleTask(
        editor_Models_Task $task,
        editor_Models_Import_DataProvider_Abstract $dataProvider,
        int $targetLanguage,
        ZfExtended_Models_User $user,
    ): array {
        // was set as array in setDataInEntity
        $task->setTargetLang($targetLanguage);

        $task->save();

        // handling project tasks is also done in prepareConfigsDefaultsCheckUploadsQueueWorkers
        $this->prepareConfigsDefaultsCheckUploadsQueueWorkers($task, $dataProvider, $user);

        // for internal tasks the usage log requires different handling, so log only non-internal tasks
        if (! $task->getTaskType()->isInternalTask()) {
            //update the task usage log for the current task
            $this->usageLogger->log($task);
        }

        return $task->toArray();
    }

    /**
     * @return object[]
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function importProject(
        editor_Models_Task $project,
        editor_Models_Import_DataProvider_Abstract $dataProvider,
        array $targetLanguages,
        ZfExtended_Models_User $user,
    ): array {
        $entityId = $project->save();
        $project->initTaskDataDirectory();

        // trigger an event that gives plugins a chance to hook into the import process
        // after unpacking/checking the files and before archiving them
        $immutableProjectMetaDTO = $this->prepareMeta($project); //set meta explicit for project
        $this->eventTrigger->triggerAfterProjectUploadPreparation($project, $dataProvider, $immutableProjectMetaDTO);

        $dataProvider->checkAndPrepare($project);

        //for projects this have to be done once before the single tasks are imported
        $dataProvider->archiveImportedData();

        $taskType = editor_Task_Type::getInstance();

        $project->setProjectId($entityId);
        $project->setTaskType($taskType->getImportProjectType());

        $project->save(); // save the entity to keep the project id in case the task import fails.

        $languages = ZfExtended_Factory::get(editor_Models_Languages::class);
        $languages = $languages->loadAllKeyValueCustom('id', 'rfc5646');

        $projectTasks = [];

        foreach ($targetLanguages as $target) {
            $task = clone $project;

            $task->setProjectId($entityId);
            $task->setTaskType($taskType->getImportTaskType());
            $task->setTargetLang($target);
            $task->setTaskName(
                sprintf(
                    '%s - %s / %s',
                    $task->getTaskName(),
                    $languages[$task->getSourceLang()],
                    $languages[$task->getTargetLang()]
                )
            );

            try {
                $task->save();
                $this->prepareConfigsDefaultsCheckUploadsQueueWorkers($task, $dataProvider, $user);
                //update the task usage log for this project-task
                $this->usageLogger->log($task);
            } catch (Throwable $e) {
                $task->setErroneous();

                Zend_Registry::get('logger')->exception($e, [
                    'extra' => [
                        'task' => $task,
                    ],
                ]);
            }

            $projectTasks[] = $task->getDataObject();
        }

        $project->setState(editor_Models_Task::STATE_PROJECT);
        // finally save project and meta after all checks are passed
        $project->save();
        $project->meta()->save();

        return $projectTasks;
    }

    /**
     * Prepares the meta-data for a task-import
     * @throws ZfExtended_Exception
     * @throws Zend_Db_Statement_Exception
     */
    private function prepareMeta(editor_Models_Task $task): TaskMetaImmutableDTO
    {
        // TODO FIXME: is the catch to prevent duplication still neccessary ?
        if (! array_key_exists($task->getTaskGuid(), self::$metaTasks)) {
            $taskMeta = $task->meta();
            $taskMetaDTO = $taskMeta->toDTO();
            // send the DTO so plugins can add their data
            $this->eventTrigger->triggerTaskMetaEvent($task, $this->taskMetaData, $taskMetaDTO);
            // and save it back
            $taskMeta->setFromDTO($taskMetaDTO);
            self::$metaTasks[$task->getTaskGuid()] = $taskMetaDTO->getImmutable();

            if ($this->doDebug) {
                error_log(
                    'ImportService::prepareMeta: collected '
                    . $taskMeta->debug() . "\n"
                );
            }
        } else {
            error_log(
                'ERROR IN IMPORT LOGIC: Event "' . $this->eventTrigger::INIT_TASK_META .
                '" called multiple for Task ' . $task->getTaskGuid() . ' !'
            );
        }

        return self::$metaTasks[$task->getTaskGuid()];
    }

    /**
     * @throws Exception
     */
    public function prepareConfigsDefaultsCheckUploadsQueueWorkers(
        editor_Models_Task $task,
        editor_Models_Import_DataProvider_Abstract $dataProvider,
        ZfExtended_Models_User $user,
    ): void {
        try {
            $importConfig = $this->prepareImportConfig($task, $dataProvider, $user);
        } catch (ZfExtended_ErrorCodeException $e) {
            try {
                $remover = ZfExtended_Factory::get(editor_Models_Task_Remover::class, [$task]);
                $remover->remove(true);
            } catch (Throwable) {
                // in case there is a task, remove it. To not blur the original Exception, we just try it
            }

            throw $e;
        }

        $taskConfig = new editor_Models_Import_TaskConfig(new editor_Models_TaskConfig());
        $taskConfig->loadAndProcessConfigTemplate($task, $importConfig);

        // add task defaults (user associations and language resources)
        $this->defaults->setTaskDefaults($task, $this->importWizardUsed);

        $this->workersService->queueImportWorkers($task, $dataProvider, $importConfig, $this->importWizardUsed);
    }

    /**
     * führt den Import aller Dateien eines Task durch
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    private function prepareImportConfig(
        editor_Models_Task $task,
        editor_Models_Import_DataProvider_Abstract $dataProvider,
        ZfExtended_Models_User $user,
    ): editor_Models_Import_Configuration {
        $immutableTaskMetaDto = $this->prepareMeta($task); //this fixates the task meta-data for the current task!

        if ($this->doDebug) {
            error_log('ImportService::prepareImportConfig' . "\n");
        }
        $task->initTaskDataDirectory();

        $importConfig = ZfExtended_Factory::get(editor_Models_Import_Configuration::class);

        $importConfig->userName = $user->getUsername();
        $importConfig->userGuid = $user->getUserGuid();

        //pre import methods:
        try {
            $dataProvider->checkAndPrepare($task);

            // After the files are moved, set the languages for the import configuration.
            // For project uploads, relais language is evaluated based on the file name match.
            // If no relais file match for the current workfile is found,
            // the same check will be done for the following up project tasks
            $importConfig->setLanguages(
                $task->getSourceLang(),
                $task->getTargetLang(),
                $task->getRelaisLang(),
                ZfExtended_Languages::LANG_TYPE_ID
            );

            // trigger an event that gives plugins a chance to hook into the import process
            // after unpacking/checking the files and before archiving them
            $this->eventTrigger->triggerAfterUploadPreparation($task, $dataProvider, $immutableTaskMetaDto);

            $dataProvider->archiveImportedData();
            $importConfig->importFolder = $dataProvider->getAbsImportPath();

            $importConfig->isValid($task->getTaskGuid());

            if (! $importConfig->hasRelaisLanguage()) {
                //reset given relais language value if no relais data is provided / feature is off
                $task->setRelaisLang(0);
            }

            // finally save task and meta after all checks are passed
            $task->save();
            $task->meta()->save();
            $importConfig->warnImportDirDeprecated($task);

            $task->lock(NOW_ISO, $task::STATE_IMPORT); //locks the task

            $this->eventTrigger->triggerBeforeImport($task, $importConfig);
        } catch (Exception $e) {
            //the DP exception handler is only needed before we have a valid task in the database,
            // after that the cleanup is done implicitly by deleting the erroneous task, which is not possible before.
            $task->setErroneous();
            $dataProvider->handleImportException($e);

            throw $e;
        }

        return $importConfig;
    }

    /**
     * prepares the tasks type, by considering the language count and the initial given task type via API
     * returns if it should be a single task (project = task) or a project with sub-tasks
     */
    public function prepareTaskType(editor_Models_Task $project, bool $multiTarget, string $initialType): bool
    {
        $taskType = editor_Task_Type::getInstance();
        $taskType->calculateImportTypes($multiTarget, $initialType);
        $project->setTaskType($taskType->getImportProjectType());

        return $taskType->getImportTaskType() === $taskType->getImportProjectType();
    }
}
