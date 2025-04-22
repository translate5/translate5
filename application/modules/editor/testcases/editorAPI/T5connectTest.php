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

use MittagQI\Translate5\Test\Api\Helper;
use MittagQI\Translate5\Test\Enums\TestUser;
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\Import\Task;
use MittagQI\Translate5\Test\JsonTestAbstract;

/**
 * This will import 2 tasks, and it will test the functionality if the user is able to open 2 different task at the
 * same time.
 */
class T5connectTest extends JsonTestAbstract
{
    private const T5CONNECT_URL = 'editor/t5connect/';

    private const FOREIGN_NAME = 't5Connect';

    private const FOREIGN_ID = 'f05249cc-07b3-47e6-bd22-ba3d66becf3a';

    private static ?ZfExtended_Auth_Token_Entity $authTokenEntity = null;

    private static string $appToken;

    protected static function setupImport(Config $config): void
    {
        // 3 tasks and users assigned
        $customerId = static::getTestCustomerId();
        $config->addTask('de', 'en', $customerId, 'task1-de-en.xlf')
            ->setProperty('foreignName', self::FOREIGN_NAME)
            ->setProperty('foreignId', self::FOREIGN_ID)
            ->addUser(TestUser::TestTranslator->value, editor_Workflow_Default::STATE_EDIT, 'reviewing')
            ->addUser(TestUser::TestLector->value, editor_Workflow_Default::STATE_OPEN, 'translatorCheck');
        $config->addTask('de', 'en', $customerId, 'task2-de-en.xlf')
            ->setProperty('foreignName', self::FOREIGN_NAME)
            ->addUser(TestUser::TestTranslator->value, editor_Workflow_Default::STATE_EDIT, 'reviewing')
            ->addUser(TestUser::TestLector->value, editor_Workflow_Default::STATE_UNCONFIRMED, 'translatorCheck');
        $config->addTask('de', 'en', $customerId, 'task3-de-en.xlf')
            ->setProperty('foreignName', self::FOREIGN_NAME)
            ->addUser(TestUser::TestTranslator->value, editor_Workflow_Default::STATE_WAITING, 'reviewing')
            ->addUser(TestUser::TestLector->value, editor_Workflow_Default::STATE_UNCONFIRMED, 'translatorCheck');
        // Create a temporary app-token for the test
        self::$authTokenEntity = ZfExtended_Factory::get('ZfExtended_Auth_Token_Entity');
        self::$appToken = self::$authTokenEntity->create(TestUser::TestApiUser->value);
    }

    /**
     * imports two tasks
     */
    public function testConnectController1(): void
    {
        // this will remove and reset the cookie
        // failed, imported, confirmed, finished
        self::api()->logout();
        Helper::setApplicationToken(self::$appToken);

        // test index
        $tasks = $this->assertEndpointBringsTasks('', 3, [0, 1, 2], 600);
        // test result format
        $this->assertTaskFormat($tasks[0]);
        // test /failed
        $this->assertEndpointBringsTasks('failed', 0, [], 600);
        // test /imported
        $this->assertEndpointBringsTasks('imported', 3, [0, 1, 2], 600);
        // test /confirmed
        $this->assertEndpointBringsTasks('confirmed', 1, [0], 600);
        // test /finished
        $this->assertEndpointBringsTasks('finished', 0, [], 600);
    }

    /**
     * @depends testConnectController1
     */
    public function testConnectController2(): void
    {
        $this->changeTUAState(
            $this->getTaskAt(1),
            TestUser::TestLector->value,
            editor_Workflow_Default::STATE_EDIT
        );
        $this->changeTUAState(
            $this->getTaskAt(2),
            TestUser::TestLector->value,
            editor_Workflow_Default::STATE_EDIT
        );
        // test /failed
        $this->assertEndpointBringsTasks('failed', 0, [], 5);
        // test /imported
        $this->assertEndpointBringsTasks('imported', 3, [0, 1, 2], 5);
        // test /confirmed
        $this->assertEndpointBringsTasks('confirmed', 2, [0, 1], 5);
        // test /finished
        $this->assertEndpointBringsTasks('finished', 0, [], 5);
    }

    /**
     * @depends testConnectController2
     */
    public function testConnectController3(): void
    {
        $this->changeTUAState(
            $this->getTaskAt(1),
            TestUser::TestTranslator->value,
            editor_Workflow_Default::STATE_FINISH
        );
        $this->changeTUAState(
            $this->getTaskAt(2),
            TestUser::TestTranslator->value,
            editor_Workflow_Default::STATE_FINISH
        );
        $this->changeTUAState(
            $this->getTaskAt(2),
            TestUser::TestLector->value,
            editor_Workflow_Default::STATE_FINISH
        );
        // test /failed
        $this->assertEndpointBringsTasks('failed', 0, [], 3);
        // test /imported
        $this->assertEndpointBringsTasks('imported', 3, [0, 1, 2], 3);
        // test /confirmed
        $this->assertEndpointBringsTasks('confirmed', 3, [0, 1, 2], 3);
        // test /finished
        $this->assertEndpointBringsTasks('finished', 1, [2], 3);
    }

    /**
     * @depends testConnectController3
     */
    public function testConnectController4(): void
    {
        $this->changeTUAState(
            $this->getTaskAt(1),
            TestUser::TestLector->value,
            editor_Workflow_Default::STATE_FINISH
        );
        self::api()->setTaskToError($this->getTaskAt(0)->getId());
        // test /failed
        $this->assertEndpointBringsTasks('failed', 1, [0], 1000);
        // test /imported
        $this->assertEndpointBringsTasks('imported', 2, [1, 2], 1000);
        // test /confirmed
        $this->assertEndpointBringsTasks('confirmed', 3, [0, 1, 2], 1000);
        // test /finished
        $this->assertEndpointBringsTasks('finished', 2, [1, 2], 1000);
    }

    /**
     * @depends testConnectController4
     */
    public function testConnectController5(): void
    {
        // first set task 0 & 1 to have foreignState "test"
        $url = self::T5CONNECT_URL . 'setforeignstate?taskId=';
        $stateParam = '&foreignState=test';
        $result = self::api()->getJson($url . $this->getTaskAt(0)->getId() . $stateParam);
        self::assertObjectHasProperty('success', $result);
        self::assertEquals(1, $result->success);
        $result = self::api()->getJson($url . $this->getTaskAt(1)->getId() . $stateParam);
        self::assertObjectHasProperty('success', $result);
        self::assertEquals(1, $result->success);

        // now expect the filters to just bring tasks with that foreignState
        // test /index
        $this->assertEndpointBringsTasks('', 2, [0, 1], 50, $stateParam);
        // test /failed
        $this->assertEndpointBringsTasks('failed', 1, [0], 50, $stateParam);
        // test /imported
        $this->assertEndpointBringsTasks('imported', 1, [1], 50, $stateParam);
        // test /confirmed
        $this->assertEndpointBringsTasks('confirmed', 2, [0, 1], 50, $stateParam);
        // test /finished
        $this->assertEndpointBringsTasks('finished', 1, [1], 50, $stateParam);
    }

    /**
     * tests the /byforeignid endpoint
     */
    public function testConnectControllerByforeignid(): void
    {
        $endpoint = 'byforeignid';
        $tasks = static::api()->getJson(self::T5CONNECT_URL . $endpoint . '?foreignId=' . self::FOREIGN_ID);
        $response = static::api()->getLastResponse();
        self::assertContains(
            $response->getStatus(),
            [200],
            'T5Connect controller did not respond on ' . self::T5CONNECT_URL . $endpoint
        );
        self::assertCount(
            1,
            $tasks,
            'T5Connect request on ' . self::T5CONNECT_URL . $endpoint . ' seem to not work properly'
        );
        self::assertEquals(
            $this->getTaskAt(0)->getTaskGuid(),
            $tasks[0]->taskGuid,
            'T5Connect request on ' . self::T5CONNECT_URL . $endpoint . ' fetched the wrong task'
        );
    }

    private function changeTUAState(Task $task, string $userName, string $state): void
    {
        $tua = self::api()->getTaskUserAssoc($task->getId(), $userName);
        $result = self::api()->saveTaskUserAssoc($tua->id, $state);
        self::assertEquals($tua->id, $result->id);
        self::assertEquals($state, $result->state);
        self::assertEquals($task->getTaskGuid(), $result->taskGuid);
    }

    private function assertTaskFormat(stdClass $task): void
    {
        $taskFields = ['id', 'taskGuid', 'foreignId', 'foreignName', 'foreignState', 'taskName', 'sourceLang',
            'targetLang', 'relaisLang', 'state', 'locked', 'users'];
        $userFields = ['id', 'login', 'state', 'workflowStepName'];

        foreach ($taskFields as $field) {
            self::assertObjectHasProperty($field, $task);
        }
        foreach ($userFields as $field) {
            self::assertObjectHasProperty($field, $task->users[0]);
        }
    }

    private function assertEndpointBringsTasks(
        string $endpoint,
        int $expectedCount,
        array $expectedTaskIndices,
        int $lastDays = 365,
        string $params = ''
    ): array {
        $tasks = static::api()->getJson(self::T5CONNECT_URL . $endpoint . '?lastDays=' . $lastDays . $params);
        $response = static::api()->getLastResponse();
        self::assertContains(
            $response->getStatus(),
            [200],
            'T5Connect controller did not respond on ' . self::T5CONNECT_URL . $endpoint
        );
        self::assertCount(
            $expectedCount,
            $tasks,
            'T5Connect request on ' . self::T5CONNECT_URL . $endpoint . ' seem to not work properly'
        );
        $taskGuids = [];
        foreach ($expectedTaskIndices as $taskIndex) {
            $taskGuids[] = $this->getTaskAt($taskIndex)->getTaskGuid();
        }
        foreach ($tasks as $task) {
            self::assertContains($task->taskGuid, $taskGuids);
        }

        return $tasks;
    }

    public static function afterTests(): void
    {
        // remove the temporary auth-token
        if (self::$authTokenEntity !== null) {
            self::$authTokenEntity->delete();
        }
        Helper::activateOriginHeader();
        Helper::unsetApplicationToken();
    }
}
