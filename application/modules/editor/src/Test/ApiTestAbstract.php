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

namespace MittagQI\Translate5\Test;

use MittagQI\Translate5\Test\Api\DbHelper;
use MittagQI\Translate5\Test\Api\Helper;
use MittagQI\Translate5\Test\Enums\TestUser;
use MittagQI\Translate5\Test\Import\Task;
use PHPUnit\Framework\TestCase;
use stdClass;
use Throwable;
use Zend_Http_Client_Exception;
use ZfExtended_Exception;

/**
 * Base Class for all API Tests
 * For tests importing tasks, use TaskImportTest
 */
abstract class ApiTestAbstract extends TestCase
{
    /**
     * To distinguish UNIT / API tests
     */
    public const TYPE = 'api';

    /**
     * Will be added to most generated resource-names in the DB
     */
    public const NAME_PREFIX = 'API Testing::';

    private static Helper $_api;

    private static ?stdClass $_appState = null;

    private static int $lastWorkerId = 0;

    /**
     * Holds the plugins temporarily activated for a test
     */
    private static array $_addedPlugins = [];

    /**
     * Holds the ids of the test-customers
     */
    private static array $_testCustomers = [];

    /**
     * Special Option to check for the Termtagger Plugin and that all configured termtaggers are running
     */
    protected static bool $termtaggerRequired = false;

    /**
     * Holds an array of active Plugin-classes that are required to run the test
     * These will be checked automatically in the test setup
     */
    protected static array $requiredPlugins = [];

    /**
     * Holds an array of Plugin-classes that must not be active to run the test
     * These will be checked automatically in the test setup
     */
    protected static array $forbiddenPlugins = [];

    /**
     * Holds an array of configs that must have the given value to run the test
     * Can be provided like [ 'autoQA.enableInternalTagCheck' => 1, ... ], "runtimeOptions." will be added
     * automatically if not present These will be checked automatically in the test setup BEFORE tasks are imported
     * (so don't check configs set by task-config files)
     */
    protected static array $requiredRuntimeOptions = [];

    /**
     * If the required runtimeOptions (defined with $requiredRuntimeOptions) are missing,
     * this decides if the test will be executed anyway (the default behaviour, leading to errors) or will be skipped
     */
    protected static bool $skipIfOptionsMissing = false;

    /**
     * The user that will be logged in in the base setup. This is the user logged in when ::beforeTests is called
     * When this is set to "testlector" imported tasks will be automatically assigned to the testlector
     */
    protected static TestUser $setupUserLogin = TestUser::TestManager;

    /**
     * If set, a test-specific customer will be created on test-setup and removed on teardown. The customer will be
     * accessible via ::$ownCustomer
     */
    protected static bool $setupOwnCustomer = false;

    /**
     * Holds the own customer if configured to be created
     */
    protected static ?stdClass $ownCustomer = null;

    /**
     * Retrieves the test API
     */
    public static function api(): Helper
    {
        return self::$_api;
    }

    /**
     * Retrieves the application-state object
     */
    final public static function getAppState(): stdClass
    {
        return self::$_appState;
    }

    final public static function getLastWorkerId(): int
    {
        return self::$lastWorkerId;
    }

    /**
     * Retrieves the id of the general test-customer, by default the first one but customer 2 and 3 can be retrieved by
     * providing "1" or "2" as indexes
     */
    final public static function getTestCustomerId(int $index = 0): int
    {
        if ($index > 2 || $index < 0) {
            throw new ZfExtended_Exception('getTestCustomerId supports only indexes 0,1,2');
        }

        return self::$_testCustomers[$index];
    }

    /**
     * retrieves the id of the test#s own customer (if setup) or nothing
     */
    final public static function getOwnCustomerId(): ?int
    {
        return static::$ownCustomer?->id;
    }

    /**
     * Returns the user-login that is set-up to be logged in after the test is setup
     */
    final public static function getTestLogin(): string
    {
        return static::$setupUserLogin->value;
    }

    /**
     * Use this method to add setting up additional stuff before the tests are performed
     */
    public static function beforeTests(): void
    {
    }

    /**
     * Use this method to clean up additional stuff after the tests have been performed
     */
    public static function afterTests(): void
    {
    }

    /**
     * Just an init function that runs as the first thing in the (unfortunately static) test setup
     * Here static props can be reset to avoid leftovers from previous tests
     */
    protected static function testSpecificInit()
    {
    }

    /**
     * internal setup for the inheriting testcase-classes
     * Do not override in concrete test-classes, use beforeTests there
     */
    protected static function testSpecificSetup()
    {
    }

    /**
     * internal teardown for the inheriting testcase-classes
     * Do not override in concrete test-classes, use afterTests there
     */
    protected static function testSpecificTeardown(bool $doCleanup)
    {
    }

    /**
     * asserts that a certain user is loggedin
     * @return stdClass the login/status JSON for further processing
     */
    public static function assertLogin(string $login)
    {
        $json = static::api()->getJson('editor/session/' . Helper::getAuthCookie());
        static::assertTrue(is_object($json), 'User "' . $login . '" is not authenticated!');
        static::assertEquals('authenticated', $json->state, 'User "' . $login . '" is not authenticated!');
        static::assertEquals($login, $json->user->login);

        return $json;
    }

    /**
     * asserts that one of the passed users is logged in
     * @return stdClass
     * @throws Zend_Http_Client_Exception
     */
    public static function assertLogins(array $logins)
    {
        $json = static::api()->getJson('editor/session/' . Helper::getAuthCookie());
        static::assertTrue(is_object($json), 'User "' . Helper::getAuthLogin() . '" is not authenticated!');
        static::assertEquals(
            'authenticated',
            $json->state,
            'User "' . Helper::getAuthLogin() . '" is not authenticated!'
        );
        static::assertTrue(in_array($json->user->login, $logins), 'Logged in user is not ' . implode(' or ', $logins));

        return $json;
    }

    /**
     * checks for task-specific configs
     * can be provided like [ 'autoQA.enableInternalTagCheck' => 1, ... ],
     * "runtimeOptions." will be added automatically if not present
     */
    public static function assertTaskConfigs(string $taskGuid, array $configs): void
    {
        static::api()->testConfigs($configs, $taskGuid);
    }

    /**
     * Asserts, that the passed actual string matches the contents of the given file
     * TODO FIXME:
     * - the capture-param is a unneccessary dependency and can be evaluated directly in the method
     * - whitespace-normalization shoud be done on capturing, not on testing
     * @param bool $capture here can be passed the isCapturing parameter from outside if it is a test not extending
     *     JsonTest
     */
    public function assertFileContents(string $fileName, string $actual, string $message = null, bool $capture = false)
    {
        $filePath = static::api()->getFile($fileName, null, false);
        if ($capture) {
            file_put_contents($filePath, $actual);
        }

        $expected = file_get_contents($filePath);

        // If we're on Windows - replace CRLF with LF
        if (PHP_OS_FAMILY == 'Windows') {
            $expected = str_replace("\r\n", "\n", $expected);
        }

        static::assertEquals($expected, $actual, $message);
    }

    /***
     * Check if the current test request is from master tests.
     * It is used for skipping tests.
     * @return boolean
     */
    public static function isMasterTest(): bool
    {
        return ! ! getenv('MASTER_TEST');
    }

    final public static function setUpBeforeClass(): void
    {
        try {
            self::logTestStart();

            static::testSpecificInit();

            // each test gets an own api-object, the instance of the current test is for code-completion and does not hurt, since the constructor does nothing
            /** @phpstan-ignore-next-line */
            self::$_api = new Helper(static::class, new static());

            // this runs only once with the first API-Test
            if (self::$_appState === null) {
                self::testRunSetup(self::$_api);
            }

            if (self::$_api->isTestSkipped()) {
                // skip test when --skip option demanded to do so
                static::markTestSkipped('Skipped test "' . static::class . '" as requested.');
            } elseif (static::$skipIfOptionsMissing && ! static::api()->checkConfigs(static::$requiredRuntimeOptions)) {
                // skip test when configs/runtimeOptions are missing
                static::markTestSkipped(
                    'Skipped test "' . static::class . '" because neccessary configs are not set or missing.'
                );
            } else {
                // make sure the setup always happens as testmanager
                static::api()->login(TestUser::TestManager->value);

                // checks for the plugin & config dependencies that have been defined for this test
                self::assertAppState();

                // add a test-customer if setup-option set
                if (static::$setupOwnCustomer) {
                    static::$ownCustomer = static::api()->addCustomer('API Testing::' . static::class);
                }
                // internal method to setup more stuff in inheriting classes
                static::testSpecificSetup();

                // log the user in that is setup as the needed test-user. Asserts the success if pretests shall not be skipped
                if (static::api()->login(static::$setupUserLogin->value) && ! self::$_api->doSkipPretests()) {
                    static::assertLogin(static::$setupUserLogin->value);
                }
                // this can be used in concrete tests as replacement for setUpBeforeClass()
                static::beforeTests();
            }
        } catch (Throwable $e) {
            static::tearDownAfterClass();

            throw $e;
        }
    }

    final public static function tearDownAfterClass(): void
    {
        self::logTestEnd();

        // ensure the teardown happens as testmanager
        static::api()->login(TestUser::TestManager->value);
        // everything is wrapped in try-catch to make sure, all cleanups are executed. Anyone knows a better way to collect exceptions ?
        $errors = [];
        // for single tests, the cleanup can be prevented via KEEP_DATA
        $doCleanup = static::api()->doCleanup();

        try {
            // this can be used in concrete tests as replacement for tearDownAfterClass()
            static::afterTests();
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }

        try {
            // internal method to clean up stuff in inheriting classes
            static::testSpecificTeardown($doCleanup);
        } catch (\Throwable $e) {
            $errors[] = $e->getMessage();
        }
        if (static::$setupOwnCustomer && $doCleanup) {
            try {
                if ($customerId = static::getOwnCustomerId()) {
                    static::api()->deleteCustomer($customerId);
                }
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }
        if (count(self::$_addedPlugins) > 0) {
            if (! DbHelper::deactivatePlugins(self::$_addedPlugins)) {
                $errors[] = 'One or more of the following neccessary Plugins could not be deactivated: \''
                    . implode("', '", self::$_addedPlugins) . "'";
            }
            self::$_addedPlugins = [];
        }
        // as a final step., we check if the test left workers in the DB
        // for single running tests or if no cleanup is wanted, we do not remove the workers after test has run
        $preventRemoval = ! static::api()->isSuite() || ! $doCleanup;
        $state = DbHelper::cleanupWorkers(false, $preventRemoval, true);
        if ($state->cleanupNeccessary) {
            $task = static::api()->getTask();
            $errors[] = 'The test left running, waiting, scheduled or crashed worker\'s in the DB:' . PHP_EOL
                . implode(PHP_EOL, $state->remainingWorkers) . PHP_EOL;
            $errors[] = 'The current task is:' . ($task?->taskGuid ?? 'none');
        }
        if (! empty($errors)) {
            static::fail(implode(PHP_EOL, $errors));
        }
    }

    /**
     * Is called once in the first API-Test of a test-run
     * Creates the appState for the run
     */
    private static function testRunSetup(Helper $api)
    {
        // for dev-purposes it may be unwanted to have all the environment-tests before running the test
        // this reduces the requests to a single request on the app-state and an initial login
        if ($api->doSkipPretests()) {
            self::$_appState = $api->getJson('editor/index/applicationstate');
            unset(self::$_appState->worker);
        } else {
            // cleanup before running the suite/test: removes any existing workers from the db
            DbHelper::removeWorkers();
            // evaluates the application state and checks basic prequesites
            self::evaluateAppState($api);
            // makes sure all test users are present in the DB & correctly configured
            self::assertNeededUsers();
            // makes sure the test customer is present in the DB and exposes it's id
            self::assertTestCustomers();
        }

        self::$lastWorkerId = DbHelper::getLastWorkerId();
    }

    /**
     * Fetches the app-state for the current test-run and checks basic requirements
     */
    /**
     * @throws Zend_Http_Client_Exception
     */
    private static function evaluateAppState(Helper $api)
    {
        // the initial login of the current Test suite
        $api->login(TestUser::TestApiUser->value);
        static::assertLogin(TestUser::TestApiUser->value);
        $state = $api->getJson('editor/index/applicationstate');
        if (! is_object($state)) {
            // UGLY: no Exception can terminate the suite with a single "Explanation", so to avoid all tests failing we simply die ...
            die('Application state could not be fetched, terminating API-tests.' . "\n\n");
        }
        // system checks
        $errors = [];
        if ($state->worker->scheduled > 0) {
            $errors[] = 'For API testing no scheduled workers are allowed in the DB!';
        }
        if ($state->worker->waiting > 0) {
            $errors[] = 'For API testing no waiting workers are allowed in the DB!';
        }
        if ($state->worker->running > 0) {
            $errors[] = 'For API testing no running workers are allowed in the DB!';
        }
        if (! $state->database->isUptodate) {
            $errors[] = 'Database is not up to date! ' . $state->database->newCount . ' new / ' . $state->database->modCount . ' modified.';
            if ($state->database->newCount > 0) {
                $errors[] = "New files: \n" . print_r($state->database->newFiles, true);
            }
        }
        if (count($errors) > 0) {
            // UGLY: no Exception can terminate the suite with a single "Explanation", so to avoid all tests failing we simply die ...
            die(implode("\n", $errors) . "\nTerminating API-tests\n\n");
        }
        unset($state->worker); // worker state is not persistent ...
        self::$_appState = $state;
    }

    /**
     * Asserts that the application state is sufficient for the current test
     * This is done before each test
     */
    private static function assertAppState()
    {
        $state = static::getAppState();
        // check for termtagger
        if (static::$termtaggerRequired) {
            static::assertFalse(
                empty($state->termtagger),
                'Termtagger Plugin not active!'
            );
            static::assertTrue(
                $state->termtagger->runningAll,
                'Some configured termtaggers are not running: ' . print_r($state->termtagger->running, true)
            );
        }

        if (count(static::$forbiddenPlugins) > 0 && ! DbHelper::deactivatePlugins(static::$forbiddenPlugins)) {
            static::fail(
                sprintf(
                    "One or more of the following forbidden Plugins could not be deactivated: '%s'",
                    implode("', '", static::$forbiddenPlugins)
                )
            );
        }

        // evaluate the plugins whitelist
        foreach (static::$requiredPlugins as $plugin) {
            if (! in_array($plugin, $state->pluginsLoaded)) {
                self::$_addedPlugins[] = $plugin;
            }
        }
        // try to activate plugins that are needed but not loaded
        if (count(self::$_addedPlugins) > 0) {
            if (! DbHelper::activatePlugins(self::$_addedPlugins)) {
                static::fail(
                    'One or more of the following neccessary Plugins could not be activated: \'' . implode(
                        "', '",
                        self::$_addedPlugins
                    ) . "'"
                );
            }
        }
        // test the required runtimeOptions (these must already be checked if static::$skipIfOptionsMissing is set ...)
        if (! static::$skipIfOptionsMissing && count(static::$requiredRuntimeOptions) > 0) {
            static::api()->testConfigs(static::$requiredRuntimeOptions);
        }
    }

    /**
     * Asserts that a default set of test users is available (provided by testdata.sql not imported by
     * install-and-update kit!) This is done only once per test-run
     */
    private static function assertNeededUsers()
    {
        static::api()->login(TestUser::TestLector->value);
        $json = static::assertLogin(TestUser::TestLector->value);
        static::assertContains('editor', $json->user->roles, 'Checking users roles:');
        static::assertNotContains('pm', $json->user->roles, 'Checking users roles:');
        static::assertContains('basic', $json->user->roles, 'Checking users roles:');
        static::assertContains('noRights', $json->user->roles, 'Checking users roles:');

        static::api()->login(TestUser::TestTranslator->value);
        $json = static::assertLogin(TestUser::TestTranslator->value);
        static::assertContains('editor', $json->user->roles, 'Checking users roles:');
        static::assertNotContains('pm', $json->user->roles, 'Checking users roles:');
        static::assertContains('basic', $json->user->roles, 'Checking users roles:');
        static::assertContains('noRights', $json->user->roles, 'Checking users roles:');

        static::api()->login(TestUser::TestTermProposer->value);
        $json = static::assertLogin(TestUser::TestTermProposer->value);
        static::assertContains('termProposer', $json->user->roles, 'Checking users roles:');
        static::assertContains('editor', $json->user->roles, 'Checking users roles:');
        static::assertContains('pm', $json->user->roles, 'Checking users roles:');
        static::assertContains('basic', $json->user->roles, 'Checking users roles:');
        static::assertContains('noRights', $json->user->roles, 'Checking users roles:');

        static::api()->login(TestUser::TestManager->value);
        $json = static::assertLogin(TestUser::TestManager->value);
        static::assertContains('editor', $json->user->roles, 'Checking users roles:');
        static::assertContains('pm', $json->user->roles, 'Checking users roles:');
        static::assertContains('basic', $json->user->roles, 'Checking users roles:');
        static::assertContains('noRights', $json->user->roles, 'Checking users roles:');
    }

    /**
     * Asserts that the default test customer is loaded and makes it's id accessible for the test run
     */
    private static function assertTestCustomers()
    {
        $customerNumbers = [
            Helper::TEST_CUSTOMER_NUMBER,
            Helper::TEST_CUSTOMER_NUMBER_1,
            Helper::TEST_CUSTOMER_NUMBER_2,
        ];
        foreach ($customerNumbers as $index => $customerNumber) {
            $customer = static::api()->getCustomerByNumber($customerNumber);
            static::assertIsObject(
                $customer,
                'Unable to load test customer. No test customer was found for customer-number: ' . $customerNumber
            );
            $response = static::api()->getLastResponse();
            static::assertEquals(
                200,
                $response->getStatus(),
                'Load test customer request does not respond HTTP 200! Body was: ' . $response->getBody()
            );
            self::$_testCustomers[$index] = $customer->id;
        }
    }

    /**
     * Logs the start of a test
     */
    private static function logTestStart(): void
    {
        error_log('Starting test: ' . static::class . ' | ' . date("Y-m-d H:i:s"));
    }

    /**
     * Logs the end of a test
     */
    private static function logTestEnd(): void
    {
        error_log('Finished test: ' . static::class . ' | ' . date("Y-m-d H:i:s"));
    }

    /**
     * Waits for the given worker optionally identified by task to be finished
     * @param int $timeout in seconds for all other states (how long might the worker be scheduled/waiting/running)
     */
    final public function waitForWorker(
        string $class,
        Task|stdClass $task = null,
        int $timeout = 100,
    ): void {
        $taskGuids = ($task === null || empty($task?->taskGuid)) ? [] : [$task->taskGuid];
        DbHelper::waitForWorkers($this, $class, $taskGuids, true, $timeout);
    }
}
