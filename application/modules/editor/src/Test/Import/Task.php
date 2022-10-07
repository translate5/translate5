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
    public int $wordCount = 0;
    public int $autoStartImport = 1;
    public bool $edit100PercentMatch = true;
    public bool $enableSourceEditing = false;
    public int $lockLocked = 1;
    private ?array $_uploadZip = null;
    private ?array $_uploadFolder = null;
    private ?array $_uploadFiles = null;
    private ?string $_cleanupZip = null;
    private bool $_setToEditAfterImport = false;

    /**
     * @param string $testClass
     * @param int $index
     */
    public function __construct(string $testClass, int $index)
    {
        parent::__construct($testClass, $index);
        $this->taskName = $this->_name;
    }

    /**
     * Adds a single zip that is expected to be in the test's dir. otherwise use second param to define the relative path in the test base-dir
     * @param string $zipFileName
     * @param string|null $folderInTestsBase
     * @return $this
     */
    public function addUploadZip(string $zipFileName, string $folderInTestsBase = null): Task
    {
        $this->_uploadZip = ['zip' => $zipFileName, 'folder' => trim($folderInTestsBase, '/')];
        return $this;
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
     * Adds direct pathes to be uploaded which are expected to reside in the test-dir
     * @param string[] $filePathes
     * @return $this
     */
    public function addUploadFiles(array $filePathes): Task
    {
        $this->_uploadFiles = $filePathes;
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
     * @param Helper $api
     * @param Config $config
     * @throws Exception
     * @throws \MittagQI\Translate5\Test\Api\Exception
     * @throws \Zend_Exception
     * @throws \Zend_Http_Client_Exception
     */
    public function request(Helper $api, Config $config)
    {
        // tasks will be uploaded as testmanager
        $api->login('testmanager');

        // prepare our resources
        $this->upload($api);

        if (!$config->hasLanguageResources() && !$config->hasPretranslation() && is_string($this->targetLang)) {

            // the simple case: task without resources & pretranslation
            if (!$this->import($api, true, true)) {
                return;
            }

        } else {

            if (!$this->import($api, false, false)) {
                return;
            }
            // associate resources
            if ($config->hasLanguageResources()) {
                foreach ($config->getLanguageResources() as $resource) {
                    if($resource->isTaskAssociated()){
                        $api->addResourceTaskAssoc($resource->getId(), $this->getTaskGuid());
                    }
                }
            }
            // queue pretranslation
            if ($config->hasPretranslation()) {
                $config->getPretranslation()->request($api, $this->getId());
            }
            // wait for the import to finish. TODO FIXME: is the waiting for project with multi-targetlang tasks actually correct ?
            if ($this->getProperty('taskType') == Helper::INITIAL_TASKTYPE_PROJECT || (is_array($this->originalTargetLang) && count($this->originalTargetLang) > 1)) {
                $api->checkProjectTasksStateLoop();
            } else {
                $api->checkTaskStateLoop();
            }
        }
        // if testlector shall be loged in after setup, we add him to the task automatically
        if ($config->getLogin() === 'testlector') {
            $api->addUserToTask($this->getTaskGuid(), $this->getProperty('entityVersion'), 'testlector');
        }
        // last step: open task for edit if configured
        if ($this->_setToEditAfterImport) {
            $api->setTaskToEdit($this->getId());
        }
    }

    public function cleanup(Helper $api, Config $config)
    {
        if ($this->_cleanupZip !== null) {
            @unlink($this->_cleanupZip);
            $this->_cleanupZip = null;
        }
        if ($this->_requested) {
            $testlector = ($config->getLogin() === 'testlector') ? 'testlector' : null;
            $api->deleteTask($this->getId(), 'testmanager', $testlector);
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
    private function import(Helper $api, bool $failOnError, bool $waitTorImport): bool
    {
        $this->originalSourceLang = $this->sourceLang;
        $this->originalTargetLang = $this->targetLang;
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
     * Adds all our files to the
     * @param Helper $api
     * @throws \Zend_Exception
     */
    private function upload(Helper $api)
    {
        if ($this->_uploadZip !== null) {
            $folder = ($this->_uploadZip['folder'] === null) ? $api->getTestClass() : $this->_uploadZip['folder'];
            $api->addImportFile($folder . '/' . $this->_uploadZip['zip']);
        } else if ($this->_uploadFolder !== null) {
            $this->_cleanupZip = $api->zipTestFiles($this->_uploadFolder['folder'] . '/', $this->_uploadFolder['zip']); // TODO FIXME: is the slash after the folder neccesary?
            $api->addImportFile($this->_cleanupZip);
        } else if ($this->_uploadFiles !== null) {
            foreach ($this->_uploadFiles as $relPath) {
                $api->addImportFiles($api->getFile($relPath));
            }
        }
    }
}
