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
use MittagQI\Translate5\Test\Api\Exception;

/**
 * Represents the api-request configuration for a task
 */
final class Task extends Resource
{
    public string $taskName;
    public string $sourceLang = 'en';
    /**
     * @var string|array
     */
    public $targetLang = 'de';
    public int $customerId;
    public bool $edit100PercentMatch = true;
    public bool $enableSourceEditing = false;
    public int $lockLocked = 1;
    public int $wordCount = 666;
    public int $autoStartImport = 1;
    public string $orderdate;
    private ?array $_uploadFolder = null;
    private ?array $_uploadFiles = null;
    private ?array $_uploadData = null;
    private ?string $_cleanupZip = null;
    private string $_originalSourceLang;
    /**
     * @var string|array
     */
    private $_originalTargetLang;
    private bool $_setToEditAfterImport = false;

    /**
     * @param string $testClass
     * @param int $index
     */
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
        return \editor_Test_ApiTest::NAME_PREFIX . parent::createName($testClass, $resourceIndex);
    }

    /**
     * Adds a folder in the test-dir that will be zipped for upload
     * @param string $folderInTestDir
     * @param string $zipFileName
     * @return $this
     */
    public function addUploadFolder(string $folderInTestDir, string $zipFileName = 'testTask.zip'): Task
    {
        $this->_uploadFolder = ['zip' => $zipFileName, 'folder' => trim($folderInTestDir, '/')];
        return $this;
    }

    /**
     * Adds a direct path to be uploaded which is expected to reside in the test-dir
     * @param string $filePath
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
     * @param string[] $filePathes
     * @return $this
     */
    public function addUploadFiles(array $filePathes): Task
    {
        $this->_uploadFiles = ($this->_uploadFiles === null) ? $filePathes : array_merge($this->_uploadFiles, $filePathes);
        return $this;
    }

    /**
     * Adds raw data to be uploaded as file
     * @param string $data
     * @param string $mime
     * @param string $filename
     * @return $this
     */
    public function addUploadData(string $data, string $mime = 'application/csv', string $filename = 'apiTest.csv'): Task
    {
        $this->_uploadData = ['data' => $data, 'mime' => $mime, 'filename' => $filename];
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
     * @return string
     * @throws Exception
     */
    public function getTaskGuid(): string
    {
        return strval($this->getProperty('taskGuid'));
    }

    /**
     * Retrieves, if a task is a project. This can only be called, after the task was imported
     * @return bool
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    public function isProjectTask(): bool
    {
        return $this->getProperty('taskType') == Helper::INITIAL_TASKTYPE_PROJECT;
    }

    /**
     * @param Helper $api
     * @param Config $config
     * @throws Exception
     * @throws \MittagQI\Translate5\Test\Import\Exception
     * @throws \Zend_Exception
     * @throws \Zend_Http_Client_Exception
     */
    public function import(Helper $api, Config $config): void
    {
        // tasks will be uploaded as testmanager
        $api->login('testmanager');

        // prepare our resources
        $this->upload($api);

        if (!$config->hasLanguageResources() && !$config->hasPretranslation() && is_string($this->targetLang)) {

            // the simple case: task without resources & pretranslation
            if (!$this->doImport($api, true, true)) {
                return;
            }

        } else {

            if (!$this->doImport($api, false, false)) {
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
            // queue pretranslation
            if ($config->hasPretranslation()) {
                $config
                    ->getPretranslation()
                    ->setTaskId($this->getId())
                    ->import($api, $config);
            }

            // start the import
            $api->getJson('editor/task/'.$this->getId().'/import');

            // wait for the import to finish. TODO FIXME: is the waiting for project with multi-targetlang tasks actually correct ?
            if ($this->isProjectTask() || (is_array($this->_originalTargetLang) && count($this->_originalTargetLang) > 1)) {

                error_log("\n\nTASK PROJEKT TASK STATE LOOP\n\n"); // TODO REMOVE
                $api->checkProjectTasksStateLoop();

            } else {
                error_log("\n\nTASK TASK STATE LOOP\n\n"); // TODO REMOVE
                $api->checkTaskStateLoop();
            }
        }
        // if testlector shall be loged in after setup, we add him to the task automatically
        if ($config->getLogin() === 'testlector') {
            $api->addUserToTask($this->getTaskGuid(), 'testlector');
            $api->login('testlector');
        }
        // last step: open task for edit if configured
        if ($this->_setToEditAfterImport) {
            $api->setTaskToEdit($this->getId());
        }
    }

    /**
     * @param Helper $api
     * @param Config $config
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    public function cleanup(Helper $api, Config $config): void
    {
        // remove on server
        if ($this->_requested) {
            $taskId = $this->getId();
            if($this->isProjectTask()){
                $api->login('testmanager');
            } else {
                if ($config->hasTestlectorLogin()) {
                    $api->login('testlector');
                    $api->setTaskToOpen($taskId);
                    $api->login('testmanager');
                } else {
                    $api->login('testmanager');
                    $api->setTaskToOpen($taskId);
                }
            }
            $api->delete('editor/task/' . $taskId);
        }
        // remnove zipped file when imported by folder
        if ($this->_cleanupZip !== null) {
            @unlink($this->_cleanupZip);
            $this->_cleanupZip = null;
        }
    }

    /**
     * Imports and applies the result
     * @param Helper $api
     * @param bool $failOnError
     * @param bool $waitTorImport
     * @return bool
     * @throws \MittagQI\Translate5\Test\Api\Exception
     * @throws \Zend_Http_Client_Exception
     */
    private function doImport(Helper $api, bool $failOnError, bool $waitTorImport): bool
    {
        $this->_originalSourceLang = $this->sourceLang;
        $this->_originalTargetLang = $this->targetLang;
        $this->autoStartImport = $waitTorImport ? 1 : 0;
        $result = $api->importTask($this->getRequestParams(), $failOnError, $waitTorImport);
        if ($this->validateResult($result, $api)) {
            // normalize projectTasks
            if (property_exists($result, 'projectTasks')) {
                $this->projectTasks = is_array($result->projectTasks) ? $result->projectTasks : [$result->projectTasks];
            }
            return true;
        }
        return false;
    }

    /**
     * Adds all our files before importing
     * @param Helper $api
     * @throws \Zend_Exception
     */
    private function upload(Helper $api)
    {
        if ($this->_uploadFolder !== null) {
            $this->_cleanupZip = $api->zipTestFiles($this->_uploadFolder['folder'] . '/', $this->_uploadFolder['zip']); // TODO FIXME: is the slash after the folder neccesary?
            $api->addImportFile($this->_cleanupZip);
        } else if ($this->_uploadFiles !== null) {
            if (count($this->_uploadFiles) == 1) {
                $api->addImportFile($api->getFile($this->_uploadFiles[0]));
            } else {
                foreach ($this->_uploadFiles as $relPath) {
                    $api->addImportFiles($api->getFile($relPath));
                }
            }
        } else if($this->_uploadData !== null) {
            $api->addImportPlain($this->_uploadData['data'], $this->_uploadData['mime'], $this->_uploadData['filename']);
        } else {
            throw new Exception('The task to import has no files assigned');
        }
    }
}
