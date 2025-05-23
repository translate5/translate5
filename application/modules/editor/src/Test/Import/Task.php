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

namespace MittagQI\Translate5\Test\Import;

use MittagQI\Translate5\Test\Api\Helper;
use MittagQI\Translate5\Test\ApiTestAbstract;
use MittagQI\Translate5\Test\Enums\TestUser;
use Zend_Http_Client_Exception;

/**
 * Represents the api-request configuration for a task
 * There are many quirks in the implementation of the underlying helper API that lead to the state here not neccesarily reflect the state of the task on the server,
 * e.g. when setting ::setNotToFailOnError in the helper Loop the data is not cached. why ?
 * Another problem is, that api-calls may manipulate the task without the changes reflected in this object.
 * To avoid this, simply use this class exclusively for task-api-calls ...
 */
final class Task extends AbstractResource
{
    public const TASK_CONFIG_INI = 'task-config.ini';

    /**
     * Mime-detection for import/upload files
     */
    public static function evaluateMime(string $file): string
    {
        switch (pathinfo($file, PATHINFO_EXTENSION)) {
            case 'zip':
                return 'application/zip';
            case 'csv':
                return 'application/csv';
            case 'tbx':
            case 'xliff':
            case 'xlf':
            case 'xml':
                return 'application/xml';
            default:
                return 'text/plain';
        }
    }

    public string $taskName;

    public string $sourceLang = 'en';

    public string|array $targetLang = 'de';

    public int $customerId;

    public bool $edit100PercentMatch = true;

    public bool $enableSourceEditing = false;

    public int $lockLocked = 1;

    public int $wordCount = 666;

    public int $autoStartImport = 1;

    public string $orderdate;

    public string $foreignId;

    public string $foreignName;

    public string $workflow = 'default';

    private ?string $_uploadFolder = null;

    private ?array $_uploadFiles = null;

    private ?array $_uploadData = null;

    private ?array $_additionalUploadFiles = null;

    private ?array $_importUsers = null;

    private ?string $_usageMode = null;

    private array $_userAssocs = [];

    private ?string $_cleanupZip = null;

    private bool $_setToEditAfterImport = false;

    private bool $_waitForImported = true;

    private bool $_failOnError = true;

    /**
     * Defines the default-configs that will be applied for all task-imports
     */
    private array $_importConfigs = [

    ];

    public function __construct(string $testClass, int $index)
    {
        parent::__construct($testClass, $index);
        $now = date('Y-m-d H:i:s');
        $this->orderdate = $now;
        // TODO FIXME: is the date-addition really neccessary ?
        $this->taskName = $this->_name . ' ' . $now;
    }

    protected function createName(string $testClass, int $resourceIndex): string
    {
        return ApiTestAbstract::NAME_PREFIX . parent::createName($testClass, $resourceIndex);
    }

    /**
     * Adds a folder in the test-dir that will be zipped for upload
     * The Upload can either be defined by file(s), by folder or by data
     * @return $this
     */
    public function addUploadFolder(string $folderInTestDir): Task
    {
        $this->_uploadFolder = trim($folderInTestDir, '/');

        return $this;
    }

    /**
     * Adds a direct path to be uploaded which is expected to reside in the test-dir
     * The Upload can either be defined by file(s), by folder or by data
     * @return $this
     */
    public function addUploadFile(string $filePath): Task
    {
        if ($this->_uploadFiles === null) {
            $this->_uploadFiles = [];
        }
        $this->_uploadFiles[] = $filePath;

        return $this;
    }

    /**
     * Adds direct pathes to be uploaded which are expected to reside in the test-dir
     * The Upload can either be defined by file(s), by folder or by data
     * @param string[] $filePathes
     * @return $this
     */
    public function addUploadFiles(array $filePathes): Task
    {
        foreach ($filePathes as $path) {
            $this->addUploadFile($path);
        }

        return $this;
    }

    /**
     * Adds raw data to be uploaded as file
     * The Upload can either be defined by file(s), by folder or by data
     * @return $this
     */
    public function addUploadData(string $data, string $mimeType = 'application/csv', string $fileName = 'apiTest.csv'): Task
    {
        $this->_uploadData = [
            'data' => $data,
            'mime' => $mimeType,
            'filename' => $fileName,
        ];

        return $this;
    }

    /**
     * Adds an additional file to the task-import that has a differen param name (for name 'importUpload' use ->addUploadFile)
     * IMPORTANT: Can not be used with ZIP or FOLDER uploads!
     * @return $this
     */
    public function addAdditionalUploadFile(string $uploadName, string $filePath): Task
    {
        if ($this->_additionalUploadFiles === null) {
            $this->_additionalUploadFiles = [];
        }
        $this->_additionalUploadFiles[] = [
            'name' => $uploadName,
            'path' => $filePath,
        ];

        return $this;
    }

    /**
     * Adds the given user to the actual task
     * To just add the testlector, use the $setupUserLogin option !!
     * @param string $userName : One of the predefined users (testmanager, testlector, testtranslator)
     * @param string $userState : open, waiting, finished, as available by the workflow
     * @param string $workflowStep : reviewing or translation, as available by the workflow
     * @param array $params : add additional taskuserassoc params to the add user call
     * @return $this
     */
    public function addUser(string $userName, string $userState = 'open', string $workflowStep = 'reviewing', array $params = []): Task
    {
        if ($this->_importUsers === null) {
            $this->_importUsers = [];
        }
        $this->_importUsers[] = [
            'name' => $userName,
            'state' => $userState,
            'step' => $workflowStep,
            'params' => $params,
        ];

        return $this;
    }

    /**
     * Sets the task-usage mode during import
     * @return $this
     */
    public function setUsageMode(string $usageMode): Task
    {
        $this->_usageMode = $usageMode;

        return $this;
    }

    /**
     * Adds a needed configuration for the imported task
     * the "runtimeOptions" scope is automatically added if not given
     * Make sure, that the passed config-value contains parenthesises for Strings, e.g. '"/§[^%]*%/"'
     * @param mixed $configValue
     * @return $this
     */
    public function addTaskConfig(string $configName, string $configValue): Task
    {
        if (! str_starts_with($configName, 'runtimeOptions.')) {
            $configName = 'runtimeOptions.' . ltrim($configName, '.');
        }
        $this->_importConfigs[$configName] = $configValue;

        return $this;
    }

    /**
     * Adds a bconf-id to the task to be used when importing
     * @return $this
     */
    public function setImportBconfId(int $bconfId): Task
    {
        $this->bconfId = $bconfId;

        return $this;
    }

    /**
     * Removes an added configuration e.g. a default configuration (can not be used to remove configs from task-config.ini files!)
     * @return $this
     */
    public function removeTaskConfig(string $configName): Task
    {
        if (array_key_exists($configName, $this->_importConfigs)) {
            unset($this->_importConfigs[$configName]);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function setToEditAfterImport(): Task
    {
        $this->_setToEditAfterImport = true;

        return $this;
    }

    /**
     * Special API to not fail on error during import. Can be used e.g. to test expected failures
     * @return $this
     */
    public function setNotToFailOnError(): Task
    {
        $this->_failOnError = false;

        return $this;
    }

    /**
     * Special API to not wait for a task to import
     * Note that this is more of a Hack and prevents users to be assigned to the task automatically if setup or the login to the setup user-login
     * @return $this
     */
    public function setNotToWaitForImported(): Task
    {
        $this->_waitForImported = false;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function getTaskGuid(): string
    {
        return strval($this->getProperty('taskGuid'));
    }

    /**
     * @throws Exception
     */
    public function getTaskType(): string
    {
        return strval($this->getProperty('taskType'));
    }

    /**
     * @throws Exception
     */
    public function getTaskState(): string
    {
        return strval($this->getProperty('state'));
    }

    /**
     * @throws Exception
     */
    public function getProjectId(): int
    {
        return (int) $this->getProperty('projectId');
    }

    /**
     * @throws Exception
     */
    public function getProjectTasks(): ?array
    {
        if ($this->isProjectTask()) {
            return $this->getProperty('projectTasks');
        }

        return null;
    }

    /**
     * Retrieves the task's data-directory
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    public function getDataDirectory(): string
    {
        return Helper::getTaskDataBaseDirectory() . trim($this->getTaskGuid(), '{}') . '/';
    }

    /**
     * Retrieves, if a task is a project. This can only be called, after the task was imported
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    public function isProjectTask(): bool
    {
        return $this->getTaskType() === Helper::INITIAL_TASKTYPE_PROJECT;
    }

    /**
     * @throws Exception
     * @throws \MittagQI\Translate5\Test\Import\Exception
     * @throws \Zend_Exception
     * @throws \Zend_Http_Client_Exception
     */
    public function import(Helper $api, Config $config): void
    {
        if ($this->_requested) {
            throw new Exception('You cannot import a Task twice.');
        }
        // tasks will be uploaded as testmanager
        $api->login(TestUser::TestManager->value);

        // has to be evaluated before the data is re-set by request
        $isMultiLanguage = (is_array($this->targetLang) && count($this->targetLang) > 1);

        // prepare our resources
        $this->upload($api);

        if (! $config->hasLanguageResources() && ! $config->hasTaskOperation() && ! $isMultiLanguage) {
            // the simple case: task without resources & pretranslation
            if (! $this->doCreateTask($api, $this->_failOnError, $this->_waitForImported)) {
                return;
            }
            // if we shall not wait, we have to skip user-assignments & task-state-setting
            if (! $this->_waitForImported) {
                return;
            }
            $this->applyResult($api->getTask());
        } else {
            // TODO FIXME: check, if $this->_failOnError can not be used here, seems to be just a miscoding overtaken over the years ...
            if (! $this->doCreateTask($api, false, false)) {
                return;
            }
            // associate resources
            if ($config->hasLanguageResources()) {
                foreach ($config->getLanguageResources() as $resource) {
                    if ($resource->isTaskAssociated()) {
                        $api->addResourceTaskAssoc($resource->getId(), $resource->getName(), $this->getTaskGuid());
                    }
                }
            }
            // queue pretranslation / PivotBatchPetranslation / Analysis
            if ($config->hasTaskOperation()) {
                $config
                    ->getTaskOperation()
                    ->setTask($this)
                    ->import($api, $config);
            }
            // some tests want to hook in after the task-adding but before import starts
            if (! $this->_waitForImported) {
                return;
            }
            // start the import and await the result
            $this->startAndWaitForImport($api);
        }

        // set usage-mode if setup
        if ($this->_usageMode !== null) {
            $api->putJson('editor/task/' . $this->getId(), [
                'usageMode' => $this->_usageMode,
            ]);
        }
        // if testlector shall be loged in after setup, we add him to the task automatically
        if ($config->getLogin() === TestUser::TestLector->value) {
            $this->_userAssocs[TestUser::TestLector->value] = $api->addUserToTask($this->getTaskGuid(), TestUser::TestLector->value);
            $api->login(TestUser::TestLector->value);
        }
        // add additional defined users
        if ($this->_importUsers !== null) {
            foreach ($this->_importUsers as $data) {
                if ($data['name'] === TestUser::TestLector->value && $config->getLogin() === TestUser::TestLector->value) {
                    throw new Exception(
                        sprintf(
                            "You cannot setup the '%s' login and assign it as seperate user to the task at the same time.",
                            TestUser::TestLector->value
                        )
                    );
                }
                // $this->reload($api); // some tests added this always between user-adds but it seems to be unneccessary
                $this->_userAssocs[$data['name']] = $api->addUserToTask($this->getTaskGuid(), $data['name'], $data['state'], $data['step'], $data['params']);
            }
        }
        // last step: open task for edit if configured
        if ($this->_setToEditAfterImport) {
            $api->setTaskToEdit($this->getId());
        }
    }

    /**
     * Reloads a task and fetches fresh props
     * @return $this
     * @throws Exception
     * @throws \Zend_Http_Client_Exception
     */
    public function reload(Helper $api)
    {
        $this->checkImported(' therefore the task cannot be reloaded.');
        $result = $api->getJson('editor/task/' . $this->getId());
        $this->applyResult($result);

        return $this;
    }

    /**
     * If the import was configured not to be waiting with ::setNotToWaitForImported this api can be used to wait afterwards ...
     * @throws Exception
     */
    public function startAndWaitForImport(Helper $api)
    {
        // trigger import
        $api->getJson('editor/task/' . $this->getId() . '/import');
        // wait for setToOpen worker
        $this->applyResult($api->waitForTaskImported($this, $this->_failOnError));
    }

    /**
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    public function cleanup(Helper $api, Config $config): void
    {
        $api->login(TestUser::TestManager->value);
        // TODO FIXME: this is just neccessary because of quirks in the helper API ... see class comment
        if ($this->_failOnError) {
            $this->reload($api);
        }
        // remove on server
        if ($this->_requested) {
            $taskState = $this->getTaskState();
            $taskId = $this->getId();
            // project tasks or tasks in error/import state can not be opened
            // Note that it can not be guaranteed here, that the state of the task is persistent as there is a task-cache in the API still
            if (! $this->isProjectTask() && $taskState !== 'error' && $taskState !== 'import') {
                $login = ($config->hasTestlectorLogin()) ? TestUser::TestLector->value : TestUser::TestManager->value;
                $api->login($login);
                $api->setTaskToOpen($taskId);
            }
            $api->login(TestUser::TestManager->value);
            $api->delete('editor/task/' . $taskId);
        }
        // remnove zipped file when imported by folder
        if ($this->_cleanupZip !== null) {
            @unlink($this->_cleanupZip);
            $this->_cleanupZip = null;
        }
    }

    /**
     * Retrieves a user-assoc created on import
     * This can only be called successfully after loading a task & assigning the user ...
     * @throws Exception
     */
    public function getUserAssoc(string $login): ?\stdClass
    {
        $this->checkImported(' therefore user-assocs can not be retrieved.');
        if (array_key_exists($login, $this->_userAssocs)) {
            return $this->_userAssocs[$login];
        }

        return null;
    }

    /**
     * Sets the task to "finished" after import
     * @return \stdClass
     * @throws Exception
     */
    public function setTaskToFinished(Helper $api): mixed
    {
        $this->checkImported(' therefore the task cannot be finished.');

        return $api->setTaskToFinished($this->getId());
    }

    /**
     * Sets the task to "edit" after import
     * @return \stdClass
     * @throws Exception
     */
    public function setTaskToEdit(Helper $api): mixed
    {
        $this->checkImported(' therefore the task cannot be edited.');

        return $api->setTaskToEdit($this->getId());
    }

    /**
     * Imports and applies the result
     * @throws \MittagQI\Translate5\Test\Api\Exception
     * @throws \Zend_Http_Client_Exception
     */
    private function doCreateTask(Helper $api, bool $failOnError, bool $waitTorImport): bool
    {
        $this->autoStartImport = $waitTorImport ? 1 : 0;
        $result = $api->createTask($this->getRequestParams(), $failOnError, $waitTorImport);
        if ($this->validateResult($result, $api)) {
            return true;
        }

        return false;
    }

    /**
     * Adds all our files before importing
     * @throws \Zend_Exception
     */
    private function upload(Helper $api)
    {
        if ($this->_uploadFolder !== null) {
            $this->_cleanupZip = $api->zipTestFiles($this->_uploadFolder);
            // add/change a task-config.ini if we have configs
            if (count($this->_importConfigs) > 0) {
                $this->setTaskConfigsInZip($this->_cleanupZip);
            }
            $api->addImportFile($this->_cleanupZip);

            return; // zip is a highlander-format ...
        } elseif ($this->_uploadFiles !== null) {
            if (count($this->_uploadFiles) === 1) {
                $mime = self::evaluateMime($this->_uploadFiles[0]);
                $file = $api->getFile($this->_uploadFiles[0]);
                // add/change a task-config.ini if we have configs. We must use a temporary zip then to not overwrite the original ZIP
                if ($mime === 'application/zip' && count($this->_importConfigs) > 0) {
                    $this->_cleanupZip = APPLICATION_DATA . '/tmp-' . basename($file);
                    copy($file, $this->_cleanupZip);
                    $this->setTaskConfigsInZip($this->_cleanupZip);
                    $file = $this->_cleanupZip;
                }
                $api->addImportFile($file, $mime);
                if ($mime === 'application/zip') {
                    return; // zip is a highlander-format ...
                }
            } else {
                foreach ($this->_uploadFiles as $relPath) {
                    $api->addImportFiles($api->getFile($relPath), self::evaluateMime($relPath));
                }
            }
        } elseif ($this->_uploadData !== null) {
            $api->addImportPlain($this->_uploadData['data'], $this->_uploadData['mime'], $this->_uploadData['filename']);
        } else {
            throw new Exception('The task to import has no files assigned');
        }
        // add additional uploads if set (only for non ZIP or FOLDER uploads)
        if ($this->_additionalUploadFiles != null) {
            foreach ($this->_additionalUploadFiles as $data) {
                $api->addFile($data['name'], $api->getFile($data['path']), self::evaluateMime($data['path']));
            }
        }
        // add optional task-configs if set (only for non ZIP or FOLDER uploads)
        if (count($this->_importConfigs) > 0) {
            $taskConfigIni = new TaskConfigIni(null, $this->_importConfigs);
            $api->addFilePlain('taskConfig', $taskConfigIni->getContents(), 'text/plain', self::TASK_CONFIG_INI);
        }
    }

    /**
     * Adds or changes task-config.ini in a zip to enable adding configs via ::addTaskConfig for all upload formats
     * @throws Exception
     */
    private function setTaskConfigsInZip(string $zipPath)
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) === true) {
            // QUIRK: It seems normally a file in the top-level is found with /filename.extension but it seems it sometimes is found only without leading slash ?
            $taskConfigContent = $zip->getFromName('/' . self::TASK_CONFIG_INI);
            if ($taskConfigContent === false) {
                $taskConfigContent = $zip->getFromName(self::TASK_CONFIG_INI);
            }
            if ($taskConfigContent !== false) {
                // there is already a task-config we have to overwrite
                $taskConfig = new TaskConfigIni($taskConfigContent, $this->_importConfigs);
                $zip->addFromString(self::TASK_CONFIG_INI, $taskConfig->getContents(), \ZipArchive::FL_OVERWRITE);
            } else {
                $taskConfig = new TaskConfigIni(null, $this->_importConfigs);
                $zip->addFromString(self::TASK_CONFIG_INI, $taskConfig->getContents());
            }
            $zip->close();
        } else {
            throw new Exception('Could not open zip \'' . $zipPath . '\'');
        }
    }

    /**
     * @throws Exception
     * @throws Zend_Http_Client_Exception
     */
    public function getAvaliableResources(Helper $api): mixed
    {
        return $api->getJson('editor/languageresourcetaskassoc', [
            'filter' => '[{"operator":"eq","value":"' . $this->getTaskGuid() . '","property":"taskGuid"}]',
        ]);
    }
}
