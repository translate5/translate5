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

use MittagQI\Translate5\Test\Enums\TestUser;
use MittagQI\Translate5\Test\Import\Task;
use MittagQI\ZfExtended\Service\ConfigHelper;
use stdClass;
use Zend_Db_Statement_Exception;
use Zend_Http_Client_Exception;
use ZfExtended_Test_ApiHelper;

/**
 * API Helper the provides general functions to test the translate5 API
 */
final class Helper extends ZfExtended_Test_ApiHelper
{
    /**
     * Seconds we wait while a task is importing before we assume an error
     * @var int
     */
    public const RELOAD_TASK_LIMIT = 300;

    /**
     * How many times the language reosurces status will be checked while the resource is importing
     * With each check the sleep is 2 seconds
     * @var int
     */
    public const RELOAD_RESOURCE_LIMIT = 40;

    /**
     * Project taskType
     * @var string
     */
    public const INITIAL_TASKTYPE_PROJECT = 'project';

    /**
     * Project task type
     * @var string
     */
    public const INITIAL_TASKTYPE_PROJECT_TASK = 'projectTask';

    /**
     * Customer-number of the test-customer
     */
    public const TEST_CUSTOMER_NUMBER = '123456789';

    /**
     * Customer-number of the second test-customer
     */
    public const TEST_CUSTOMER_NUMBER_1 = '1234567891';

    /**
     * Customer-number of the third test-customer
     */
    public const TEST_CUSTOMER_NUMBER_2 = '1234567892';

    public const SEGMENT_DUPL_SAVE_CHECK = '<img src="data:image/gif;base64,R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" class="duplicatesavecheck" data-segmentid="%s" data-fieldname="%s">';

    /**
     * stdObject with the values of the last imported task
     * @var stdClass
     */
    protected $task;

    /**
     * array of stdObject with the values of the last imported project tasks
     * @var array
     */
    protected $projectTasks;

    /**
     * stdObject with the values of the test customer
     */
    protected stdClass $customer;

    //region Import API
    /******************************************************* IMPORT API *******************************************************/

    /**
     * Adds a single file for upload
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
     * Adds directly data to be imported instead of providing a filepath
     * useful for creating CSV testdata direct in testcase
     */
    public function addImportPlain(string $data, string $mime = 'application/csv', string $filename = 'apiTest.csv')
    {
        $this->addFilePlain('importUpload', $data, $mime, $filename);
    }

    /**
     * Waits for a Task to be imported
     * Expects a task-Object, either the regular API one (preferred) or also the api-internal stdClass object
     * returns the data of the completely loaded task
     * @throws Exception
     */
    public function waitForTaskImported(Task|stdClass $task, bool $failOnError = true): stdClass
    {
        if ($task->taskType === self::INITIAL_TASKTYPE_PROJECT) {
            return $this->waitForProjectImported($task, $failOnError);
        } else {
            DbHelper::waitForWorkers(
                /** @phpstan-ignore-next-line */
                $this->test,
                \editor_Models_Import_Worker_SetTaskToOpen::class,
                [$task->taskGuid],
                $failOnError,
                self::RELOAD_TASK_LIMIT
            );
            sleep(1);

            return $this->task = $this->loadTask((int) $task->id);
        }
    }

    /**
     * Waits for a complete project (with several languages) to be imported
     * Expects a task-Object, either the regular API one (preferred) or also the api-internal stdClass object
     * returns the data of the completely loaded project
     * @throws Zend_Http_Client_Exception
     */
    public function waitForProjectImported(Task|stdClass $task, bool $failOnError = true): stdClass
    {
        // usually the project-tasks are already there ... otherwise, load them
        $hasProjectTasks = property_exists($task, 'projectTasks')
            && is_array($task->projectTasks) && count($task->projectTasks) > 0;
        $projectTasks = ($hasProjectTasks) ? $task->projectTasks : $this->loadProjectTasks((int) $task->projectId);
        // evaluate taskGuids & wait for workers
        $taskGuids = [];
        foreach ($projectTasks as $task) {
            if ($task->state !== 'open') {
                $taskGuids[] = $task->taskGuid;
            }
        }
        if (count($taskGuids) > 0) {
            DbHelper::waitForWorkers(
                /** @phpstan-ignore-next-line */
                $this->test,
                \editor_Models_Import_Worker_SetTaskToOpen::class,
                $taskGuids,
                $failOnError,
                self::RELOAD_TASK_LIMIT
            );
        }
        sleep(1);
        // reload project & reattach project-tasks
        $this->task = $this->loadTask((int) $task->projectId); // crucial: we need to return the project!
        $this->task->projectTasks = $projectTasks;

        return $this->task;
    }

    /**
     * Check the task state. The test will fail when $failOnError = true and if the task is in state error or after
     * RELOAD_TASK_LIMIT task state checks
     *
     * @throws Zend_Http_Client_Exception
     */
    public function waitForTaskState(
        int $taskId,
        string $stateToWaitFor,
        bool $failOnError = true,
        int $maxSeconds = self::RELOAD_TASK_LIMIT,
    ): bool {
        $counter = 0;
        while (true) {
            $taskResult = $this->getJson('editor/task/' . $taskId);
            $currentState = strval($taskResult->state ?? 'NONE');
            error_log(
                'Task state check ' . $counter . '/' . $maxSeconds
                . ' state: ' . $currentState . ' [' . $this->testClass . ']'
            );

            if ($currentState === $stateToWaitFor) {
                if (isset($this->task) && is_object($this->task) && (int) $this->task->id === $taskId) {
                    $this->task = $taskResult;
                }

                return true;
            }
            if ($currentState === \editor_Models_Task::STATE_UNCONFIRMED) {
                //with task templates we could implement separate tests for that feature:
                $this->test::fail(
                    'runtimeOptions.import.initialTaskState = unconfirmed is not supported at the moment!'
                );
            }
            if ($currentState === \editor_Models_Task::STATE_ERROR) {
                if ($failOnError) {
                    $lastErrors = (property_exists($taskResult, 'lastErrors') && is_array($taskResult->lastErrors)) ?
                        $taskResult->lastErrors : [];
                    $addon = (count($lastErrors) > 0) ?
                        " and last errors:\n " . join("\n ", array_column($lastErrors, 'message')) : '.';
                    $this->test::fail('Task Import stopped. Task has state error' . $addon);
                }

                return false;
            }
            //break after RELOAD_TASK_LIMIT reloads
            if ($counter === $maxSeconds) {
                if ($failOnError) {
                    $this->test::fail(
                        'Task Import stopped. Task is not open after ' . $maxSeconds
                        . ' task checks, but has state: ' . $currentState
                    );
                }

                return false;
            }
            $counter++;
            sleep(1);
        }
    }

    //endregion
    //region Task API
    /******************************************************* TASK API *******************************************************/

    /**
     * Imports a task and returns the requested data on success
     * @return array|stdClass
     * @throws Exception
     * @throws Zend_Http_Client_Exception
     */
    public function createTask(array $task, bool $failOnError = true, bool $waitForImport = true)
    {
        $this->initTaskPostData($task);

        // prevent the allowed states to be reset with session requests
        $allowedStatuses = $this->getAllowHttpStatusOnce();

        // make sure testmanager or testclientpm is logged in
        $this->test::assertLogins([TestUser::TestManager->value, TestUser::TestClientPm->value]);

        $response = $this->postJson('editor/task', $task, expectedToFail: ! $failOnError);

        if (is_object($response) && isset($response->error) && $failOnError === false) {
            return $response;
        }

        $this->task = $response;

        // the project tasks will only be part of the first request
        $projectTasks = (property_exists($this->task, 'projectTasks')) ? $this->task->projectTasks : null;

        // re add the collected allowed states after the task request is done
        foreach ($allowedStatuses as $status) {
            $this->allowHttpStatusOnce($status);
        }

        $this->assertResponseStatus($this->getLastResponse(), 'Import');

        if (! $waitForImport) {
            return $this->task;
        }
        $this->waitForTaskImported($this->task, $failOnError);

        if ($projectTasks !== null) {
            $this->task->projectTasks = is_array($projectTasks) ? $projectTasks : [$projectTasks];
        }

        return $this->task;
    }

    /**
     * returns the current active task to test
     * @return stdClass
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * @return array|stdClass
     */
    public function getProjectTasks()
    {
        return $this->projectTasks;
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
     * @throws Zend_Http_Client_Exception
     */
    public function reloadTask(int $taskId = null): stdClass
    {
        return $this->task = $this->loadTask($taskId ?? (int) $this->task->id);
    }

    /**
     * Reload the tasks of the current project
     * @throws Zend_Http_Client_Exception
     */
    public function reloadProjectTasks(): array
    {
        return $this->projectTasks = $this->loadProjectTasks((int) $this->task->projectId);
    }

    /**
     * loads a task via API
     * @throws Zend_Http_Client_Exception
     */
    public function loadTask(int $taskId): stdClass
    {
        return $this->getJson('editor/task/' . $taskId);
    }

    /**
     * loads the project-tasks for the given project-id via API
     * @throws Zend_Http_Client_Exception
     */
    public function loadProjectTasks(int $projectId): array
    {
        return $this->getJson('editor/task/', [
            'filter' => '[{"operator":"eq","value":"' . $projectId . '","property":"projectId"}]',
        ]);
    }

    /**
     * Setter for $this->task
     */
    public function setTask($task)
    {
        $this->task = $task;
    }

    /**
     * Sets the passed or current task to open
     * @param int $taskId : if given, this task is taken, otherwise the current task
     * @return array|stdClass
     */
    public function setTaskToOpen(int $taskId = -1)
    {
        return $this->setTaskState($taskId, 'open');
    }

    /**
     * Sets the passed or current task to edit
     * @param int $taskId : if given, this task is taken, otherwise the current task
     * @return array|stdClass
     */
    public function setTaskToEdit(int $taskId = -1)
    {
        return $this->setTaskState($taskId, 'edit');
    }

    /**
     * Sets the passed or current task to finished
     * @param int $taskId : if given, this task is taken, otherwise the current task
     * @return array|stdClass
     */
    public function setTaskToFinished(int $taskId = -1)
    {
        return $this->setTaskState($taskId, 'finished');
    }

    /**
     * @param int $taskId : if given, this task is taken, otherwise the current task
     * @return array|stdClass
     */
    private function setTaskState(int $taskId, string $userState)
    {
        if ($taskId < 1 && $this->task) {
            $taskId = $this->task->id;
        }
        if ($taskId > 0) {
            return $this->putJson('editor/task/' . $taskId, [
                'userState' => $userState,
                'id' => $taskId,
            ]);
        }

        return $this->createResponseResult(null, 'No Task to set state for');
    }

    /**
     * @return array|stdClass
     * @throws Zend_Http_Client_Exception
     */
    public function addResourceTaskAssoc(int $resourceId, string $resourceName, string $taskGuid)
    {
        error_log('Languageresources assoc to task. ' . $resourceName . ' -> ' . $taskGuid);

        return $this->postJson(
            'editor/languageresourcetaskassoc',
            [
                'languageResourceId' => $resourceId,
                'taskGuid' => $taskGuid,
                'segmentsUpdateable' => 0,
            ]
        );
    }

    protected function initTaskPostData(array &$task)
    {
        $now = date('Y-m-d H:i:s');
        if (empty($task['taskName'])) {
            $task['taskName'] = 'API Testing::' . $this->testClass . ' ' . $now;
        }
        if (empty($task['orderdate'])) {
            $task['orderdate'] = $now;
        }
        if (! isset($task['wordCount'])) {
            $task['wordCount'] = 666;
        }
        //currently all test tasks are started automatically, no test of the /editor/task/ID/import URL is implemented!
        if (! isset($task['autoStartImport'])) {
            $task['autoStartImport'] = 1;
        }
        $task['orderer'] = 'unittest'; // TODO FIXME: this should be solved with the available configs or defined props
    }

    /**
     * Removes the passed or current Task
     * @param int $taskId : if given, this task is taken, otherwise the current task
     * @param string|null $loginName : if given, a login with this user is done before opening/deleting the task
     * @param string|null $loginName : only in conjunction with $loginName. If given, a login with this user is done
     *     before to open the task, deletion is done with the latter
     */
    public function deleteTask(
        int $taskId = -1,
        string $loginName = null,
        string $loginNameToOpen = null,
        bool $isProjectTask = false,
    ) {
        if ($taskId < 1 && $this->task) {
            $taskId = $this->task->id;
            $isProjectTask = ($this->task->taskType == self::INITIAL_TASKTYPE_PROJECT);
        }
        if ($taskId > 0) {
            if ($isProjectTask) {
                $this->login($loginName);
            } else {
                if (! empty($loginName) && ! empty($loginNameToOpen)) {
                    $this->login($loginNameToOpen);
                } elseif (! empty($loginName)) {
                    $this->login($loginName);
                }
                $this->setTaskToOpen($taskId);
                if (! empty($loginName) && ! empty($loginNameToOpen)) {
                    $this->login($loginName);
                }
            }
            $this->delete('editor/task/' . $taskId);
        }
    }
    //endregion
    //region User API
    /******************************************************* USER API *******************************************************/

    /**
     * @param string $username one of the predefined users (testmanager, testlector, testtranslator)
     * @param string $state open, waiting, finished, as available by the workflow
     * @param string $step reviewing or translation, as available by the workflow
     * @param array $params add additional taskuserassoc params to the add user call
     *
     * @return stdClass taskuserassoc result
     * @deprecated
     * adds the given user to the actual task
     */
    public function addUser(string $username, string $state = 'open', string $step = 'reviewing', array $params = [])
    {
        return $this->addUserToTask($this->task->taskGuid, $username, $state, $step, $params);
    }

    /**
     * adds the given user to the given task
     * UGLY: these are lots of params, better working with config-objects
     *
     * @return array|stdClass
     * @throws Zend_Http_Client_Exception
     */
    public function addUserToTask(
        string $taskGuid,
        string $username,
        string $state = 'open',
        string $step = 'reviewing',
        array $params = [],
    ) {
        $this->test::assertFalse(
            empty(self::getUsersGuid()[$username]),
            'Given testuser "' . $username . '" does not exist!'
        );
        $p = [
            "id" => 0,
            "userGuid" => self::getUsersGuid()[$username],
            "state" => $state,
            "workflowStepName" => $step,
        ];
        $p = array_merge($p, $params);
        $json = $this->postJson(sprintf('editor/task/%s/job', urlencode($taskGuid)), $p);
        $this->assertResponseStatus($this->getLastResponse(), 'User');

        return $json;
    }

    /**
     * Retrieves a userGuid from the setup test-users
     * @throws Exception
     */
    public function getUserGuid(string $login): string
    {
        if (array_key_exists($login, static::getUsersGuid())) {
            return static::getUsersGuid()[$login];
        }

        throw new Exception('User \'' . $login . '\' is no valid test user.');
    }

    //endregion
    //region Customer API
    /******************************************************* CUSTOMER API *******************************************************/

    /**
     * Retrieves a ustomer by it's number
     */
    public function getCustomerByNumber(string $customerNumber): ?stdClass
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
     * @return bool|mixed|stdClass|null
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

    /**
     * deletes a customer
     */
    public function deleteCustomer(int $customerId)
    {
        $this->delete('editor/customer/' . $customerId);
    }

    //endregion
    //region Segment API
    /******************************************************* SEGMENT API *******************************************************/

    /**
     * Retrieves the segments as JSON
     * @return stdClass|array
     */
    public function getSegmentsRequest(string $jsonFileName = null, int $limit = 200, int $start = 0, int $page = 1)
    {
        $url = 'editor/segment?page=' . $page . '&start=' . $start . '&limit=' . $limit;

        return $this->fetchJson($url, 'GET', [], $jsonFileName, false);
    }

    public function getSegmentsWithBasicData(
        string $jsonFileName = null,
        int $limit = 200,
        int $start = 0,
        int $page = 1,
    ): array {
        $segments = $this->getSegments($jsonFileName, $limit, $start, $page);

        $fields = [
            'segmentNrInTask',
            'mid',
            'userGuid',
            'editable',
            'pretrans',
            'matchRate',
            'isRepeated',
            'source',
            'sourceMd5',
            'sourceToSort',
            'target',
            'targetMd5',
            'targetToSort',
            'targetEdit',
            'targetEditToSort',
            'relais',
            'relaisMd5',
            'relaisToSort',
        ];

        $result = [];
        foreach ($segments as $segment) {
            $seg = (array) $segment;
            $tmp = [];
            foreach ($fields as $field) {
                if (isset($seg[$field])) {
                    $tmp[$field] = $seg[$field];
                }
            }
            $result[] = $tmp;
        }

        return $result;
    }

    /***
     * Get all segments from jsonFile or from remote api with the option to provide which fields should be removed from
     * the result list. By default, mid will be removed from the segments array because this field is
     * not always the same.
     *
     * @param string|null $jsonFileName
     * @param int $limit
     * @param int $start
     * @param int $page
     * @param array $fieldsToExclude
     * @return array
     */
    public function getSegments(
        string $jsonFileName = null,
        int $limit = 200,
        int $start = 0,
        int $page = 1,
        array $fieldsToExclude = ['mid'],
    ): array {
        $segments = $this->getSegmentsRequest($jsonFileName, $limit, $start, $page);

        foreach ($segments as $segment) {
            foreach ($fieldsToExclude as $field) {
                if (is_array($segment) && array_key_exists($field, $segment)) {
                    unset($segment[$field]);
                }
                if (is_object($segment) && property_exists($segment, $field)) {
                    unset($segment->$field);
                }
            }
        }

        return $segments;
    }

    /**
     * Saves a segment / sends segment put
     * @param array $additionalPutData may be used to send additional data. will overwrite programmatically values
     * @param array $fieldsToExclude segment field to be excluded in the results array. By default, the segment mid
     *                                 is removed from the array because it is expected to be unique by a lot of tests.
     *                                 With the new mid-implementation, the mid is also generated out of segment fileId,
     *                                 and it is always different for each test run.
     * @throws Zend_Http_Client_Exception
     */
    public function saveSegment(
        int $segmentId,
        string $editedTarget = null,
        string $editedSource = null,
        string $jsonFileName = null,
        array $additionalPutData = [],
        int $duration = 666,
        array $fieldsToExclude = ['mid'],
    ): array|stdClass {
        $data = [
            'id' => $segmentId,
            'autoStateId' => 999,
            'durations' => [],
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
        $result = $this->putJson('editor/segment/' . $segmentId, $data, $jsonFileName);

        foreach ($fieldsToExclude as $field) {
            if (is_array($result) && array_key_exists($field, $result)) {
                unset($result[$field]);
            }
            if (is_object($result) && property_exists($result, $field)) {
                unset($result->$field);
            }
        }

        return $result;
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
     * @return array|stdClass
     * @throws Zend_Http_Client_Exception
     */
    public function addResource(
        array $params,
        string $fileName = null,
        bool $waitForImport = false,
        string $testDir = '',
    ) {
        if (! empty($this->filesToAdd)) {
            throw new Exception(
                'There are already some files added as pending request and not sent yet! Send them first to the server before calling addResource!'
            );
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

        error_log("Language resources created. " . $resource->name);

        $result = $this->getJson('editor/languageresourceinstance/' . $resource->id);

        if (! $waitForImport) {
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
            error_log(
                'Languageresources status check ' . $counter . '/' . self::RELOAD_RESOURCE_LIMIT . ' state: ' . $result->status
            );
            $counter++;
        }

        $this->test::assertEquals(
            'available',
            $result->status,
            'Resource import of ' . $resource->name . ' stopped. Resource state is:' . $result->status
        );

        return $result;
    }

    /***
     * Reimport tbx into existing language resource
     *
     * @param int $resourceId
     * @param string|null $fileName
     * @param array $params
     * @param bool $waitForImport
     * @param string $testDir
     * @return array|stdClass
     * @throws Zend_Http_Client_Exception
     * @throws Exception
     */
    public function reimportResource(
        int $resourceId,
        string $fileName,
        array $params,
        bool $waitForImport = true,
        string $testDir = '',
    ) {
        if (! empty($this->filesToAdd)) {
            throw new Exception(
                'There are already some files added as pending request and not sent yet! Send them first to the server before calling addResource!'
            );
        }

        // Add file for upload
        $this->addFile('tmUpload', $this->getFile($fileName, $testDir), "application/xml");

        // Submit that
        $resource = $this->postJson('editor/languageresourceinstance/' . $resourceId . '/import/', $params);

        // Make sure it's the same resource we've submitted file for
        $this->test::assertEquals($resourceId, $resource->id);

        // Log that we submitted tbx-file
        error_log("Language resource reimport tbx-file submitted. " . $resource->name);

        // Check whether reimport tbx started
        $result = $this->getJson('editor/languageresourceinstance/' . $resource->id);

        // If we won't wait for import completed - just return result
        if (! $waitForImport) {
            return $result;
        }

        // Log current status
        error_log('Languageresource reimport status check:' . $result->status);

        // Counter initial value
        $counter = 0;

        // Enter status-checking loop
        while ($result->status != 'available') {
            // If reimport status is error - break
            if ($result->status == 'error') {
                break;
            }

            // If RELOAD_RESOURCE_LIMIT reached - break
            if ($counter == self::RELOAD_RESOURCE_LIMIT) {
                break;
            }

            // Wait a bit
            sleep(2);

            // Get fresh status
            $result = $this->getJson('editor/languageresourceinstance/' . $result->id);

            // Log status
            error_log(
                'Languageresource reimport status check ' . $counter . '/' . self::RELOAD_RESOURCE_LIMIT . ' state: ' . $result->status
            );

            // Increment counter
            $counter++;
        }

        // Make sure reimport completed
        $this->test::assertEquals(
            'available',
            $result->status,
            'Resource import of ' . $resource->name . ' stopped. Resource state is:' . $result->status
        );

        // Return
        return $result;
    }

    //endregion
    //region Config API
    /******************************************* CONFIG/RUNTIMEOPTIONS API *******************************************/

    /**
     * tests the config names and values in the given associated array against the REST accessible application config
     * If the given value to the config is null, the config value is just checked for existence,
     * otherwise for equality
     */
    public function testConfigs(array $configsToTest, string $taskGuid = null): void
    {
        $origin = empty($taskGuid) ? 'instance config' : 'task config';
        $foundConfigs = $this->getTestConfigs(array_keys($configsToTest), $taskGuid);

        foreach ($configsToTest as $name => $value) {
            $configValue = array_key_exists($name, $foundConfigs) ? $foundConfigs[$name] : null;

            if (is_null($value)) {
                $this->test::assertNotEmpty(
                    $configValue,
                    "Config $name in $origin is empty but should be set with a value!"
                );
            } else {
                $this->test::assertTrue(
                    ConfigHelper::isValueEqual($configValue, $value),
                    "Config $name in $origin is not as expected!"
                );
            }
        }
    }

    /**
     * Checks, if the passed configs are set / set to the wanted value
     * Hint: if the passed configs have null as value, they are only checked for existance, otherwise for equality
     * @throws Zend_Db_Statement_Exception
     */
    public function checkConfigs(array $configsToTest): bool
    {
        $foundConfigs = $this->getTestConfigs(array_keys($configsToTest));
        foreach ($configsToTest as $name => $value) {
            if (! array_key_exists($name, $foundConfigs)) {
                return false;
            }
            if ($value === null && ConfigHelper::isValueEmpty($foundConfigs[$name])) {
                error_log('Found config ' . $name . ' is empty but should have a non-empty value');

                return false;
            }
            if ($value !== null && ! ConfigHelper::isValueEqual($foundConfigs[$name], $value)) {
                error_log('Found config ' . $name . ' does not match the expected value!');

                return false;
            }
        }

        return true;
    }

    /**
     * Retrieves the configs from the special apitest-endpoint of the config-controller
     * @throws Zend_Http_Client_Exception
     */
    private function getTestConfigs(array $configNames, string $taskGuid = null): array
    {
        $params = [
            'configs' => $configNames,
        ];
        if (! empty($taskGuid)) {
            $params['taskGuid'] = $taskGuid;
        }
        $foundConfigs = $this->getJson('editor/config/apitest', $params);

        return json_decode(json_encode($foundConfigs), true);
    }

    /**
     * Get configs like the frontend normally does
     * Get instance level config fro given config name. This function will not perform any asserts
     * @return mixed|null
     * @throws Zend_Http_Client_Exception
     */
    public function getConfig(string $configName)
    {
        $config = $this->getJson('editor/config');
        if (empty($config)) {
            return null;
        }
        foreach ($config as $c) {
            if ($c->name === $configName) {
                return $c;
            }
        }

        return null;
    }

    //endregion

    /**
     * @return mixed|stdClass
     * @throws Zend_Http_Client_Exception
     */
    public function getLanguages()
    {
        $resp = $this->get('editor/language');
        $this->assertResponseStatus($resp, 'Languages');

        return $this->decodeJsonResponse($resp);
    }

    /**
     * removes random revIds from the given XML string of changes.xml files
     */
    public function replaceChangesXmlContent(string $changesXml): string
    {
        $guid = htmlspecialchars($this->task->taskGuid);
        $changesXml = str_replace(
            ' translate5:taskguid="' . $guid . '"',
            ' translate5:taskguid="TASKGUID"',
            $changesXml
        );

        return preg_replace('/sdl:revid="[^"]{36}"/', 'sdl:revid="replaced-for-testing"', $changesXml);
    }

    /**
     * Get coincidence between test user and guid.
     *
     * @return array<string, string>
     */
    private static function getUsersGuid(): array
    {
        return [
            TestUser::TestManager->value => '{00000000-0000-0000-C100-CCDDEE000001}',
            TestUser::TestLector->value => '{00000000-0000-0000-C100-CCDDEE000002}',
            TestUser::TestTranslator->value => '{00000000-0000-0000-C100-CCDDEE000003}',
            TestUser::TestApiUser->value => '{00000000-0000-0000-C100-CCDDEE000004}',
            TestUser::TestTermProposer->value => '{00000000-0000-0000-C100-CCDDEE000005}',
            TestUser::TestManager2->value => '{00000000-0000-0000-C100-CCDDEE000006}',
            TestUser::TestClientPm->value => '{00000000-0000-0000-C100-CCDDEE000007}',
        ];
    }
}
