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
use MittagQI\Translate5\Test\Import\Config;

/**
 * Tests the Client PM role / multitenancy
 */
class ClientPmTest extends editor_Test_ImportTest
{
    private static int $newUserId = -1;

    protected static function setupImport(Config $config): void
    {
        // import one task with the testmanager as owner and the base customer
        $config->addTask('en', 'de', static::getTestCustomerId(), '3-segments-en-de.zip')
            ->setProperty('foreignId', '1');
        // import one task with the testclientpm as owner and customer 1
        $config->addTask('en', 'de', static::getTestCustomerId(1), '3-segments-en-de.zip')
            ->setProperty('foreignId', '2')
            ->setOwner('testclientpm');
    }

    /**
     * This tests if the project-view is restricted to the tasks bound to the clientpm and unrestricted for a pm
     */
    public function testProjects()
    {
        // the pm should see both tasks
        static::api()->login('testmanager');
        $tasks = static::api()->getJson('editor/task');
        static::assertCount(2, $tasks);

        // ... while the clientpm only can see the one bound to him
        static::api()->login('testclientpm');
        $tasks = static::api()->getJson('editor/task');
        static::assertCount(1, $tasks); // sees only one
        static::assertEquals('2', $tasks[0]->foreignId); // identify the one by foreign-id
    }

    /**
     * tests, if the user-management is following the client-accesibility-restrictions for the client-pm
     * and especially, if non-accessible clients (that are invisible in the frontend) cannot be removed
     */
    public function testUsers()
    {
        $mainClientId = static::getTestCustomerId();
        $firstClientPmClientId = static::getTestCustomerId(1);
        $secondClientPmClientId = static::getTestCustomerId(2);
        // add a User to work with, bound to the main customer
        static::api()->login('testmanager');
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
        static::api()->login('testclientpm');
        $result = static::api()->putJson('editor/user/' . static::$newUserId, [
            'customers' => $firstClientPmClientId . ',' . $secondClientPmClientId // mimicing the frontend, we just send the two customers the clientpm can access !
        ]);
        // this MUST lead to the "main customer" is still assigned
        static::assertEquals(
            $this->normalizeCustomers([$mainClientId, $firstClientPmClientId, $secondClientPmClientId]),
            $this->normalizeCustomers($result->customers)
        );
        // test again, now remove the first clientPm customer
        $result = static::api()->putJson('editor/user/' . static::$newUserId, [
            'customers' => $secondClientPmClientId // mimicing the frontend, we just send the customer accessible for the client-pm
        ]);
        // this MUST lead to the "main customer" is still assigned
        static::assertEquals(
            $this->normalizeCustomers([$mainClientId, $secondClientPmClientId]),
            $this->normalizeCustomers($result->customers)
        );

        // try to delete the added user as testclientpm, this must fail
        $result = static::api()->delete('editor/user/', [
            'id' => static::$newUserId
        ], true);
        static::assertEquals(403, $result->status);
        static::assertStringContainsString('not allowed due to client-restriction', $result->error);

        // login as pm
        static::api()->login('testmanager');
        // assign only the main customer the client-pm is not restricted for
        $result = static::api()->putJson('editor/user/' . static::$newUserId, [
            'customers' => $mainClientId
        ]);
        // this MUST lead to the "main customer" is still assigned
        static::assertEquals(
            $this->normalizeCustomers([$mainClientId]),
            $this->normalizeCustomers($result->customers)
        );

        // now the user must be inaccessible for the client-pm
        static::api()->login('testclientpm');
        $result = static::api()->getJson('editor/user/' . static::$newUserId, [], null, true);
        static::assertEquals(403, $result->status);
        static::assertStringContainsString('not accessible due to the users client-restriction', $result->error);

    }

    public function testLanguageResources()
    {
        static::assertTrue(true);

        // echo "\nRESULT:\n\n".json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n\n";
    }

    /**
     * Normalizes customers either from array or from the customers-prop of a user to be able to compare them
     * @param string|array $customers
     * @return array
     */
    private function normalizeCustomers(string|array $customers): array
    {
        if(is_string($customers)){
            $customers = explode(',', trim($customers, ','));
        }
        sort($customers, SORT_NUMERIC);
        return $customers;
    }

    // echo "\nRESULT:\n\n".json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n\n";

    /**
     * Cleans up the added entities during the tests
     */
    public static function afterTests(): void
    {
        if(static::$newUserId > 0){
            static::api()->login('testmanager');
            static::api()->delete('editor/user/', [
                'id' => static::$newUserId
            ]);
        }
    }
}
