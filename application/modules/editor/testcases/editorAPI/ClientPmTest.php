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

use MittagQI\Translate5\Test\Enums\TestUser;
use MittagQI\Translate5\Test\Import\LanguageResource;
use MittagQI\Translate5\Test\ImportTestAbstract;

/**
 * Tests the Client PM role / multitenancy
 */
class ClientPmTest extends ImportTestAbstract
{
    private static int $newUserId = -1;

    private static int $numTasksBefore = 0;

    public static function beforeTests(): void
    {
        // the test often fails because tests before left tasks in the DB
        // too overcome this, we evaluate the number of tasks before testing.
        // this is just to avoid false positive errors in the suite ...
        self::$numTasksBefore = count(static::api()->getJson('editor/task'));
    }

    /**
     * This tests if the project-view is restricted to the tasks bound to the clientpm and unrestricted for a pm
     */
    public function testProjects()
    {
        // import the needed tasks
        $config = static::getConfig();
        // import one task with the testmanager as owner and the base customer
        $task0 = $config->addTask('en', 'de', static::getTestCustomerId(0), '3-segments-en-de.zip')
            ->setProperty('foreignId', '1');
        $config->import($task0);
        // import one task with the testclientpm as owner and customer 1
        $task1 = $config->addTask('en', 'de', static::getTestCustomerId(1), '3-segments-en-de.zip')
            ->setProperty('foreignId', '2');
        $config->import($task1);

        // the pm should see both tasks
        static::api()->login(TestUser::TestManager->value);
        $tasks = static::api()->getJson('editor/task');

        // take number of tasks at the beginning into account
        static::assertCount(2 + self::$numTasksBefore, $tasks);

        // ... while the clientpm only can see the one bound to him
        static::api()->login(TestUser::TestClientPm->value);
        $tasks = static::api()->getJson('editor/task');
        static::assertCount(1, $tasks); // sees only one
        static::assertEquals('2', $tasks[0]->foreignId); // identify the one by foreign-id

        // the clientpm must not see tasks he is not entitled for (being the first imported task)
        $result = static::api()->getJson('editor/task/' . $task0->getId(), [], null, true);
        static::assertEquals(403, $result->status);
        static::assertStringContainsString('not accessible due to the users client-restriction', $result->error);
    }

    /**
     * tests, if the user-management is following the client-accesibility-restrictions for the client-pm
     * and especially, if non-accessible clients (that are invisible in the frontend) cannot be removed
     */
    public function testUsers()
    {
        // the customer-ids to test with
        $mainClientId = static::getTestCustomerId();
        $firstClientPmClientId = static::getTestCustomerId(1);
        $secondClientPmClientId = static::getTestCustomerId(2);

        // add a User to work with, bound to the main customer
        static::api()->login(TestUser::TestManager->value);
        $newUser = static::api()->postJson('editor/user/', [
            "firstName" => "Just",
            "surName" => "A Test",
            "email" => "test@apitest.com",
            "login" => "just-a-test",
            "gender" => "m",
            "roles" => "editor",
            "customers" => $mainClientId . ',' . $firstClientPmClientId,
            "locale" => "en",
        ]);
        static::assertIsObject($newUser); // user exists
        static::$newUserId = $newUser->id; // deletes the user in the test-teardown
        // check if customers were really assigned
        static::assertEquals(
            $this->normalizeCustomers([$mainClientId, $firstClientPmClientId]),
            $this->normalizeCustomers($newUser->customers)
        );

        // login as testclientpm and add the second client-pm customer to the user
        static::api()->login(TestUser::TestClientPm->value);
        $result = static::api()->putJson('editor/user/' . static::$newUserId, [
            'customers' => $firstClientPmClientId . ',' . $secondClientPmClientId, // mimicing the frontend, we just send the two customers the clientpm can access !
        ]);
        static::assertEquals(
            $this->normalizeCustomers([$mainClientId, $firstClientPmClientId, $secondClientPmClientId]),  // this MUST lead to the "main customer" is still assigned
            $this->normalizeCustomers($result->customers)
        );

        // test again, now remove the first clientPm customer
        $result = static::api()->putJson('editor/user/' . static::$newUserId, [
            'customers' => $secondClientPmClientId, // mimicing the frontend, we just send the customer accessible for the client-pm
        ]);
        static::assertEquals(
            $this->normalizeCustomers([$mainClientId, $secondClientPmClientId]), // this MUST lead to the "main customer" is still assigned
            $this->normalizeCustomers($result->customers)
        );

        // try to delete the added user as testclientpm, this must fail
        $result = static::api()->delete('editor/user/', [
            'id' => static::$newUserId,
        ], true);
        static::assertEquals(403, $result->status);
        static::assertStringContainsString('not allowed due to client-restriction', $result->error);

        // login as pm
        static::api()->login(TestUser::TestManager->value);
        // assign only the main customer the client-pm is not restricted for
        $result = static::api()->putJson('editor/user/' . static::$newUserId, [
            'customers' => $mainClientId,
        ]);
        static::assertEquals(
            $this->normalizeCustomers([$mainClientId]),
            $this->normalizeCustomers($result->customers)
        );

        // now the user must be inaccessible for the client-pm
        static::api()->login(TestUser::TestClientPm->value);
        $result = static::api()->getJson('editor/user/' . static::$newUserId, [], null, true);
        static::assertEquals(403, $result->status);
        static::assertStringContainsString('not accessible due to the users client-restriction', $result->error);
    }

    /**
     * tests, if the language-resource-management is following the client-accesibility-restrictions for the client-pm
     */
    public function testLanguageResources()
    {
        // import the two language-resources to test (assigned to different customers)
        static::api()->login(TestUser::TestManager->value);
        $config = static::getConfig();
        $dummyMt0 = $config->addLanguageResource(
            LanguageResource::DUMMY_TM,
            null,
            static::getTestCustomerId(),
            'en',
            'de'
        );
        $config->import($dummyMt0);
        $dummyMt1 = $config->addLanguageResource(
            LanguageResource::DUMMY_TM,
            null,
            static::getTestCustomerId(1),
            'en',
            'de'
        );
        $config->import($dummyMt1);

        // make sure, the clientpm can only see the single resource he is entitled for
        static::api()->login(TestUser::TestClientPm->value);
        $resources = static::api()->getJson('editor/languageresourceinstance');
        static::assertCount(1, $resources);
        static::assertEquals($dummyMt1->getId(), $resources[0]->id);

        // check, the clientpm can see the "his" resource via getAction
        $result = static::api()->getJson('editor/languageresourceinstance/' . $dummyMt1->getId());
        static::assertIsObject($result);
        static::assertEquals($dummyMt1->getId(), $result->id);

        // check, the clientpm not see the other one via getAction
        $result = static::api()->getJson('editor/languageresourceinstance/' . $dummyMt0->getId(), [], null, true);
        static::assertEquals(403, $result->status);
        static::assertStringContainsString('not accessible due to the users client-restriction', $result->error);

        $mainClientId = static::getTestCustomerId();
        $firstClientPmClientId = static::getTestCustomerId(1);
        $secondClientPmClientId = static::getTestCustomerId(2);

        // add customer to TM 2
        static::api()->login(TestUser::TestManager->value);
        $result = static::api()->putJson('editor/languageresourceinstance/' . $dummyMt0->getId(), [
            'customerIds' => [$mainClientId, $firstClientPmClientId],
        ]);
        static::assertEquals(
            $this->normalizeCustomers([$mainClientId, $firstClientPmClientId]),
            $this->normalizeCustomers($result->customerIds)
        );

        // the clientpm now should have access
        static::api()->login(TestUser::TestClientPm->value);
        $result = static::api()->getJson('editor/languageresourceinstance/' . $dummyMt0->getId());
        static::assertEquals(
            $this->normalizeCustomers([$mainClientId, $firstClientPmClientId]),
            $this->normalizeCustomers($result->customerIds)
        );

        // now add other client-pm customer as clientpm ... and ensure, the base-cusomer is still present
        $result = static::api()->putJson('editor/languageresourceinstance/' . $dummyMt0->getId(), [
            'customerIds' => [$firstClientPmClientId, $secondClientPmClientId],
        ]);
        static::assertEquals(
            $this->normalizeCustomers([$mainClientId, $firstClientPmClientId, $secondClientPmClientId]), // $mainClientId must be present !
            $this->normalizeCustomers($result->customerIds)
        );
    }

    /**
     * Normalizes customers either from array or from the customers-prop of a user to be able to compare them
     */
    private function normalizeCustomers(string|array $customers): array
    {
        if (is_string($customers)) {
            $customers = explode(',', trim($customers, ','));
        }
        sort($customers, SORT_NUMERIC);

        return $customers;
    }

    /**
     * Cleans up the added entities during the tests
     */
    public static function afterTests(): void
    {
        if (static::$newUserId > 0) {
            static::api()->delete('editor/user/', [
                'id' => static::$newUserId,
            ]);
        }
    }
}
