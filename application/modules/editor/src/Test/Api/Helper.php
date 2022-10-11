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

namespace MittagQI\Translate5\Test\Api;

/**
 * API Helper the provides general functions to test the translate5 API
 */
final class Helper extends \ZfExtended_Test_ApiHelper
{
    /**
     * How many time the task status will be check while the task is importing.
     * @var integer
     */
    const RELOAD_TASK_LIMIT = 100;

    /**
     * How many times the language reosurces status will be checked while the resource is importing
     * @var integer
     */
    const RELOAD_RESOURCE_LIMIT = 40;

    /**
     * Project taskType
     * @var string
     */
    const INITIAL_TASKTYPE_PROJECT = 'project';

    /**
     * Project task type
     * @var string
     */
    const INITIAL_TASKTYPE_PROJECT_TASK = 'projectTask';

    /**
     * Customer-number of the test-customer
     */
    const TEST_CUSTOMER_NUMBER = '123456789';

    /**
     *
     */
    const SEGMENT_DUPL_SAVE_CHECK = '<img src="data:image/gif;base64,R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" class="duplicatesavecheck" data-segmentid="%s" data-fieldname="%s">';

    /**
     * stdObject with the values of the last imported task
     * @var \stdClass
     */
    protected $task;

    /**
     * array of stdObject with the values of the last imported project tasks
     * @var array
     */
    protected $projectTasks;

    /**
     * stdObject with the values of the test customer
     * @var \stdClass
     */
    protected \stdClass $customer;

    /**
     * Collection of language resources created from addResources method
     * @var array
     */
    protected static array $resources = []; //TODO: remove from memory ?

    protected array $testusers = array(
        'testmanager' => '{00000000-0000-0000-C100-CCDDEE000001}',
        'testlector' => '{00000000-0000-0000-C100-CCDDEE000002}',
        'testtranslator' => '{00000000-0000-0000-C100-CCDDEE000003}',
    );

    //region Import API
    /******************************************************* IMPORT API *******************************************************/


    /**
     * @deprecated
     * Imports the task described in array $task, parameters are the API parameters, at least:
     * @Deprecated
     *
     * $task = array(
     * 'sourceLang' => 'en', // mandatory, source language in rfc5646
     * 'targetLang' => 'de', // mandatory, target language in rfc5646
     * 'relaisLang' => 'de', // optional, must be given on using relais column
     * 'taskName' => 'simple-en-de', //optional, defaults to __CLASS__::__TEST__
     * 'orderdate' => date('Y-m-d H:i:s'), //optional, defaults to now
     * 'wordCount' => 666, //optional, defaults to heavy metal
     * );
     *
     * @param array $task
     * @param bool $failOnError default true
     * @param bool $waitForImport default true : if this is set to false, the function will not check the task import state
     * @return boolean;
     */
    public function import(array $task, bool $failOnError = true, bool $waitForImport = true): bool
    {
        $this->initTaskPostData($task);
        $this->test::assertLogin('testmanager');

        $this->task = $this->postJson('editor/task', $task);
        if (isset($this->task->projectTasks)) {
            $this->projectTasks = is_array($this->task->projectTasks) ? $this->task->projectTasks : [$this->task->projectTasks];
        }
        $this->assertResponseStatus($this->getLastResponse(), 'Import');

        if (!$waitForImport) {
            return true;
        }
        if ($this->task->taskType == self::INITIAL_TASKTYPE_PROJECT) {
            return $this->checkProjectTasksStateLoop($failOnError);
        }
        return $this->checkTaskStateLoop($failOnError);
    }

    /**
     * Adds a single file for upload
     * @param string $path
     * @param string $mime
     */
    public function addImportFile(string $path, string $mime = 'application/zip')
    {
        $this->addFile('importUpload', $path, $mime);
    }

    /***
     * Add multiple work-files for upload.
     * @param string $path
     * @param string $mime
     * @return void
     */
    public function addImportFiles(string $path, string $mime = 'application/zip')
    {
        $this->addFile('importUpload[]', $path, $mime);
    }

    /**
     * @param string $path
     * @param string $mime
     */
    public function addImportTbx(string $path, string $mime = 'application/xml')
    {
        $this->addFile('importTbx', $path, $mime);
    }

    /**
     * Adds directly data to be imported instead of providing a filepath
     * useful for creating CSV testdata direct in testcase
     *
     * @param string $data
     * @param string $mime
     * @param string $filename
     */
    public function addImportPlain(string $data, string $mime = 'application/csv', string $filename = 'apiTest.csv')
    {
        $this->addFilePlain('importUpload', $data, $mime, $filename);
    }

    /**
     * TODO FIXME: implement in the concrete test
     * Receives a two dimensional array and add it as a CSV file to the task
     * MID col and CSV head line is added automatically
     *
     * multiple targets currently not supported!
     *
     * @param array $data
     */
    public function addImportArray(array $data)
    {
        $i = 1;
        $data = array_map(function ($row) use (&$i) {
            $row = array_map(function ($cell) {
                //escape " chars
                return str_replace('"', '""', $cell);
            }, $row);
            array_unshift($row, $i++); //add mid
            return '"' . join('","', $row) . '"';
        }, $data);
        array_unshift($data, '"id", "source", "target"');
        $this->addImportPlain(join("\n", $data));
    }

    /**
     * DO NOT USE IN CONCRETE API TESTS
     * Check the task state. The test will fail when $failOnError = true and if the task is in state error or after RELOAD_TASK_LIMIT task state checks
     * @param bool $failOnError
     * @return boolean
     */
    public function checkTaskStateLoop(bool $failOnError = true): bool
    {
        $counter = 0;
        while (true) {
            error_log('Task state check ' . $counter . '/' . self::RELOAD_TASK_LIMIT . ' state: ' . $this->task->state . ' [' . $this->testClass . ']');
            $taskResult = $this->getJson('editor/task/' . $this->task->id);
            if ($taskResult->state == 'open') {
                $this->task = $taskResult;
                return true;
            }
            if ($taskResult->state == 'unconfirmed') {
                //with task templates we could implement separate tests for that feature:
                $this->test::fail('runtimeOptions.import.initialTaskState = unconfirmed is not supported at the moment!');
            }
            if ($taskResult->state == 'error') {
                if ($failOnError) {
                    $lastErrors = (property_exists($taskResult, 'lastErrors') && !empty($taskResult->lastErrors)) ? $taskResult->lastErrors : [];
                    $addon = (count($lastErrors) > 0) ? ' and last errors:' . "\n ".join("\n ", array_column($lastErrors, 'message')) : '.';
                    $this->test::fail('Task Import stopped. Task has state error'.$addon);
                }
                return false;
            }
            //break after RELOAD_TASK_LIMIT reloads
            if ($counter == self::RELOAD_TASK_LIMIT) {
                if ($failOnError) {
                    $this->test::fail('Task Import stopped. Task is not open after ' . self::RELOAD_TASK_LIMIT . ' task checks, but has state: ' . $taskResult->state);
                }
                return false;
            }
            $counter++;
            sleep(3);
        }
    }

    /**
     * DO NOT USE IN CONCRETE API TESTS
     * Check the state of all project tasks. The test will fail when $failOnError = true and if one of the project task is in state error or after RELOAD_TASK_LIMIT task state checks
     * @param bool $failOnError
     * @return bool
     * @throws Exception
     */
    public function checkProjectTasksStateLoop(bool $failOnError = true): bool
    {
        $counter = 0;
        while (true) {

            //reload the project
            $this->reloadProjectTasks();

            $toCheck = count($this->projectTasks);
            //foreach project task check the state
            foreach ($this->projectTasks as $task) {

                error_log('Project tasks state check ' . $counter . '/' . self::RELOAD_TASK_LIMIT . ', [ name:' . $task->taskName . '], [state: ' . $task->state . '] [' . $this->testClass . ']');

                if ($task->state == 'open') {
                    $toCheck--;
                    continue;
                }
                if ($task->state == 'unconfirmed') {
                    //with task templates we could implement separate tests for that feature:
                    throw new Exception("runtimeOptions.import.initialTaskState = unconfirmed is not supported at the moment!");
                }

                if ($task->state == 'error') {
                    if ($failOnError) {
                        $this->test::fail('Task Import stopped. Task has state error.');
                    }
                    return false;
                }
            }

            if ($toCheck == 0) {
                return true;
            }

            //break after RELOAD_TASK_LIMIT reloads
            if ($counter == self::RELOAD_TASK_LIMIT) {
                if ($failOnError) {
                    $this->test::fail('Project task import stopped. After ' . self::RELOAD_TASK_LIMIT . ' task state checks, all of the project task are not in state open.');
                }
                return false;
            }
            $counter++;
            sleep(10);
        }
    }

    //endregion
    //region Task API
    /******************************************************* TASK API *******************************************************/


    /**
     * Imports a task and returns the requested data on success
     * @param array $task
     * @param bool $failOnError
     * @param bool $waitForImport
     * @return array|\stdClass
     * @throws Exception
     * @throws \Zend_Http_Client_Exception
     */
    public function importTask(array $task, bool $failOnError = true, bool $waitForImport = true)
    {
        $this->initTaskPostData($task);
        $this->test::assertLogin('testmanager'); // make sure testmanager is logged in
        $this->task = $this->postJson('editor/task', $task);
        $this->assertResponseStatus($this->getLastResponse(), 'Import');
        if (!$waitForImport) {
            return $this->task;
        }
        if ($this->task->taskType == self::INITIAL_TASKTYPE_PROJECT) {
            $this->checkProjectTasksStateLoop($failOnError);
        } else {
            $this->checkTaskStateLoop($failOnError);
        }
        return $this->task;
    }

    /**
     * Can be used to wait for an task import, that was initiated without autoStartImport
     * @param \stdClass $task
     * @throws Exception
     */
    public function waitForTaskImported(\stdClass $task)
    {
        $this->task = $task;
        if($task->taskType == self::INITIAL_TASKTYPE_PROJECT){
            $this->checkProjectTasksStateLoop();
        } else {
            $this->checkTaskStateLoop();
        }
    }

    /***
     * Add task specific config. The config must be added after the task is created and before the import is triggered.
     * @param string $taskGuid
     * @param string $configName
     * @param string $configValue
     * @return mixed|boolean
     */
    public function addTaskImportConfig(string $taskGuid, string $configName, string $configValue)
    {
        $this->putJson('editor/config', [
            'name' => $configName,
            'value' => $configValue,
            'taskGuid' => $taskGuid
        ]);
        $resp = $this->getLastResponse();
        $this->assertResponseStatus($resp, 'Config');
        return $this->decodeJsonResponse($resp);
    }

    /**
     * returns the current active task to test
     * @return \stdClass
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * returns the absolute data path to the task
     * @return string
     */
    public function getTaskDataDirectory()
    {
        return self::$CONFIG['DATA_DIR'] . trim($this->task->taskGuid, '{}') . '/';
    }

    /**
     * reloads the given or internally stored task
     * @param int|null $taskId
     * @return array|\stdClass
     * @throws \Zend_Http_Client_Exception
     */
    public function reloadTask(int $taskId = null)
    {
        return $this->task = $this->getJson('editor/task/' . ($taskId ?? $this->task->id));
    }

    /***
     * Reload the tasks of the current project
     * @return mixed|boolean
     */
    public function reloadProjectTasks()
    {
        return $this->projectTasks = $this->getJson('editor/task/', [
            'filter' => '[{"operator":"eq","value":"' . $this->task->projectId . '","property":"projectId"}]',
        ]);
    }

    /***
     *
     * @return array|mixed|boolean
     */
    public function getProjectTasks()
    {
        return $this->projectTasks;
    }

    /**
     * Setter for $this->task
     *
     * @param $task
     */
    public function setTask($task)
    {
        $this->task = $task;
    }

    /**
     * Sets the passed or current task to open
     * @param int $taskId : if given, this task is taken, otherwise the current task
     * @return array|\stdClass
     */
    public function setTaskToOpen(int $taskId = -1)
    {
        return $this->setTaskState($taskId, 'open');
    }

    /**
     * Sets the passed or current task to edit
     * @param int $taskId : if given, this task is taken, otherwise the current task
     * @return array|\stdClass
     */
    public function setTaskToEdit(int $taskId = -1)
    {
        return $this->setTaskState($taskId, 'edit');
    }

    /**
     * Sets the passed or current task to finished
     * @param int $taskId : if given, this task is taken, otherwise the current task
     * @return array|\stdClass
     */
    public function setTaskToFinished(int $taskId = -1)
    {
        return $this->setTaskState($taskId, 'finished');
    }

    /**
     * @param int $taskId : if given, this task is taken, otherwise the current task
     * @param string $userState
     * @return array|\stdClass
     */
    private function setTaskState(int $taskId, string $userState)
    {
        if ($taskId < 1 && $this->task) {
            $taskId = $this->task->id;
        }
        if ($taskId > 0) {
            return $this->putJson('editor/task/' . $taskId, array('userState' => $userState, 'id' => $taskId));
        }
        return $this->createResponseResult(null, 'No Task to set state for');
    }

    /**
     * @param int $resourceId
     * @param string $taskGuid
     * @return array|\stdClass
     * @throws \Zend_Http_Client_Exception
     */
    public function addResourceTaskAssoc(int $resourceId, string $resourceName, string $taskGuid){
        error_log('Languageresources assoc to task. ' . $resourceName . ' -> ' . $taskGuid);
        return $this->postJson(
            'editor/languageresourcetaskassoc',
            [
                'languageResourceId' => $resourceId,
                'taskGuid' => $taskGuid,
                'segmentsUpdateable' => 0
            ]);

    }

    /**
     * @param array $task
     */
    protected function initTaskPostData(array &$task)
    {
        $now = date('Y-m-d H:i:s');
        if (empty($task['taskName'])) {
            $task['taskName'] = 'API Testing::' . $this->testClass . ' ' . $now;
        }
        if (empty($task['orderdate'])) {
            $task['orderdate'] = $now;
        }
        if (!isset($task['wordCount'])) {
            $task['wordCount'] = 666;
        }
        //currently all test tasks are started automatically, no test of the /editor/task/ID/import URL is implemented!
        if (!isset($task['autoStartImport'])) {
            $task['autoStartImport'] = 1;
        }
        $task['orderer'] = 'unittest'; // TODO FIXME: this should be solved with the available configs or defined props
    }

    /**
     * Removes the passed or current Task
     * @param int $taskId: if given, this task is taken, otherwise the current task
     * @param string|null $loginName: if given, a login with this user is done before opening/deleting the task
     * @param string|null $loginName: only in conjunction with $loginName. If given, a login with this user is done before to open the task, deletion is done with the latter
     * @param bool $isProjectTask
     */
    public function deleteTask(int $taskId = -1, string $loginName = null, string $loginNameToOpen = null, bool $isProjectTask = false)
    {
        if($taskId < 1 && $this->task){
            $taskId = $this->task->id;
            $isProjectTask = ($this->task->taskType == self::INITIAL_TASKTYPE_PROJECT);
        }
        if($taskId > 0){
            if($isProjectTask){
                $this->login($loginName);
            } else {
                if(!empty($loginName) && !empty($loginNameToOpen)){
                    $this->login($loginNameToOpen);
                } else if(!empty($loginName)){
                    $this->login($loginName);
                }
                $this->setTaskToOpen($taskId);
                if(!empty($loginName) && !empty($loginNameToOpen)){
                    $this->login($loginName);
                }
            }
            $this->delete('editor/task/'.$taskId);
        }
    }
    //endregion
    //region User API
    /******************************************************* USER API *******************************************************/

    /**
     * @deprecated
     * adds the given user to the actual task
     * @param string $username one of the predefined users (testmanager, testlector, testtranslator)
     * @param string $state open, waiting, finished, as available by the workflow
     * @param string $step reviewing or translation, as available by the workflow
     * @param array $params add additional taskuserassoc params to the add user call
     *
     * @return \stdClass taskuserassoc result
     */
    public function addUser(string $username, string $state = 'open', string $step = 'reviewing', array $params = [])
    {
        return $this->addUserToTask($this->task->taskGuid, $username, $state, $step, $params);
    }

    /**
     * adds the given user to the given task
     * UGLY: these are lots of params, better working with config-objects
     * @param string $taskGuid
     * @param string $username
     * @param string $state
     * @param string $step
     * @param array $params
     *
     * @return array|\stdClass
     * @throws \Zend_Http_Client_Exception
     */
    public function addUserToTask(string $taskGuid, string $username, string $state = 'open', string $step = 'reviewing', array $params = [])
    {
        $this->test::assertFalse(empty($this->testusers[$username]), 'Given testuser "' . $username . '" does not exist!');
        $p = array(
            "id" => 0,
            "taskGuid" => $taskGuid,
            "userGuid" => $this->testusers[$username],
            "state" => $state,
            "workflowStepName" => $step,
        );
        $p = array_merge($p, $params);
        $json = $this->postJson('editor/taskuserassoc', $p);
        $this->assertResponseStatus($this->getLastResponse(), 'User');
        return $json;
    }

    //endregion
    //region Customer API
    /******************************************************* CUSTOMER API *******************************************************/

    /**
     * Retrieves a ustomer by it's number
     * @param string $customerNumber
     * @return \stdClass|null
     */
    public function getCustomerByNumber(string $customerNumber): ?\stdClass
    {
        $filter = '[{"operator":"eq","value":"' . $customerNumber . '","property":"number"}]';
        $url = 'editor/customer?page=1&start=0&limit=20&filter=' . urlencode($filter);
        $customerData = $this->getJson($url);
        if ($customerData && is_array($customerData) && count($customerData) > 0) {
            return $customerData[0];
        }
        return null;
    }

    /**
     * Adds a test customer
     * @param string $customerName
     * @param string|null $customerNumber
     * @return bool|mixed|\stdClass|null
     */
    public function addCustomer(string $customerName, string $customerNumber = null)
    {
        // add customer
        if ($customerNumber === null) {
            $customerNumber = uniqid($customerName);
        }
        return $this->postJson('editor/customer/', [
            'name' => $customerName,
            'number' => $customerNumber,
        ]);
    }

    public function deleteCustomer(int $customerId)
    {
        $this->delete('editor/customer/' . $customerId);
    }

    //endregion
    //region Segment API
    /******************************************************* SEGMENT API *******************************************************/

    /**
     * Retrieves the segments as JSON
     * @param string|null $jsonFileName
     * @param int $limit
     * @param int $start
     * @param int $page
     * @return \stdClass|array
     */
    public function getSegments(string $jsonFileName = null, int $limit = 200, int $start = 0, int $page = 1)
    {
        $url = 'editor/segment?page=' . $page . '&start=' . $start . '&limit=' . $limit;
        return $this->fetchJson($url, 'GET', [], $jsonFileName, false);
    }

    /**
     * Saves a segment / sends segment put
     * @param int $segmentId
     * @param string $editedTarget
     * @param string|null $editedSource
     * @param string|null $jsonFileName
     * @param array $additionalPutData : may be used to send additional data. will overwrite programmatical values
     * @param int $duration
     * @return bool|\stdClass
     */
    public function saveSegment(int $segmentId, string $editedTarget = null, string $editedSource = null, string $jsonFileName = null, array $additionalPutData = [], int $duration = 666)
    {
        $data = [
            'id' => $segmentId,
            'autoStateId' => 999,
            'durations' => []
        ];
        if ($editedSource !== null) {
            $data['sourceEdit'] = $editedSource . sprintf(self::SEGMENT_DUPL_SAVE_CHECK, $segmentId, 'sourceEdit');
            $data['durations']['sourceEdit'] = $duration;
        }
        if ($editedTarget !== null) {
            $data['targetEdit'] = $editedTarget . sprintf(self::SEGMENT_DUPL_SAVE_CHECK, $segmentId, 'targetEdit');
            $data['durations']['targetEdit'] = $duration;
        }
        foreach ($additionalPutData as $key => $value) {
            $data[$key] = $value;
        }
        return $this->putJson('editor/segment/' . $segmentId, $data, $jsonFileName);
    }

    //endregion
    //region Resource API
    /******************************************************* RESOURCE API *******************************************************/

    /***
     * Create new language resource
     * @param array $params
     * @param string|null $fileName
     * @param bool $waitForImport
     * @param string $testDir
     * @return array|\stdClass
     * @throws \Zend_Http_Client_Exception
     */
    public function addResource(array $params, string $fileName = null, bool $waitForImport = false, string $testDir = '')
    {
        if (!empty($this->filesToAdd)) {
            throw new Exception('There are already some files added as pending request and not sent yet! Send them first to the server before calling addResource!');
        }
        //if filename is provided, set the file upload field
        if ($fileName) {
            $this->addFile('tmUpload', $this->getFile($fileName, $testDir), "application/xml");
            $resource = $this->postJson('editor/languageresourceinstance', $params);
        } else {
            //request because the requestJson will encode the params with "data" as parent
            $response = $this->request('editor/languageresourceinstance', 'POST', $params);
            $this->assertResponseStatus($response, 'Language resource');
            $resource = $this->decodeJsonResponse($response);
        }
        $this->test::assertEquals($params['name'], $resource->name);

        //collect the created resource
        self::$resources[] = $resource;

        error_log("Language resources created. " . $resource->name);

        $result = $this->getJson('editor/languageresourceinstance/' . $resource->id);

        if (!$waitForImport) {
            return $result;
        }
        error_log('Languageresources status check:' . $result->status);
        $counter = 0;
        while ($result->status != 'available') {
            if ($result->status == 'error') {
                break;
            }
            //break after RELOAD_RESOURCE_LIMIT trys
            if ($counter == self::RELOAD_RESOURCE_LIMIT) {
                break;
            }
            sleep(2);
            $result = $this->getJson('editor/languageresourceinstance/' . $result->id);
            error_log('Languageresources status check ' . $counter . '/' . self::RELOAD_RESOURCE_LIMIT . ' state: ' . $result->status);
            $counter++;
        }

        $this->test::assertEquals('available', $result->status, 'Resource import of '.$resource->name.' stopped. Resource state is:' . $result->status);
        return $result;
    }

    //endregion
    //region Config API
    /******************************************************* CONFIG/RUNTIMEOPTIONS API *******************************************************/

    /**
     * tests the config names and values in the given associated array against the REST accessible application config
     * If the given value to the config is null, the config value is just checked for existence and if the configured value is not empty
     * @param array $configsToTest
     * @param array $filter provide an array with several filtering guids. Key taskGuid or userGuid or customerId, value the according value
     */
    public function testConfig(array $configsToTest, array $plainFilter = [])
    {
        foreach ($configsToTest as $name => $value) {
            if (!str_starts_with($name, 'runtimeOptions.')) {
                $name = 'runtimeOptions.' . $name;
            }
            $filter = array_merge([
                'filter' => '[{"type":"string","value":"' . $name . '","property":"name","operator":"like"}]',
            ], $plainFilter);
            $config = $this->getJson('editor/config', $filter);
            $this->test::assertCount(1, $config, 'No Config entry for config "' . $name . '" found in instance config!');
            if (is_null($value)) {
                $this->test::assertNotEmpty($config[0]->value, 'Config ' . $name . ' in instance is empty but should be set with a value!');
            } else {
                $this->test::assertEquals($value, $config[0]->value, 'Config ' . $name . ' in instance config is not as expected: ');
            }
        }
    }

    //endregion

    /**
     * @return mixed|\stdClass
     * @throws \Zend_Http_Client_Exception
     */
    public function getLanguages()
    {
        $resp = $this->get('editor/language');
        $this->assertResponseStatus($resp, 'Languages');
        return $this->decodeJsonResponse($resp);
    }

    /**
     * removes random revIds from the given XML string of changes.xml files
     * @param string $changesXml
     * @return string
     */
    public function replaceChangesXmlContent(string $changesXml): string
    {
        $guid = htmlspecialchars($this->task->taskGuid);
        $changesXml = str_replace(' translate5:taskguid="' . $guid . '"', ' translate5:taskguid="TASKGUID"', $changesXml);
        return preg_replace('/sdl:revid="[^"]{36}"/', 'sdl:revid="replaced-for-testing"', $changesXml);
    }
}
