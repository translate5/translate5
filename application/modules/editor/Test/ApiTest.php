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

/**
 * Base Class for all API Tests
 */
abstract class editor_Test_ApiTest extends \PHPUnit\Framework\TestCase {

    const TYPE = 'api';

    /**
     * @var editor_Test_ApiHelper|null
     */
    private static editor_Test_ApiHelper $_api;

    /**
     * @var
     * Special Option to check for the Termtagger Plugin and that all configured termtaggers are running
     */
    protected static bool $termtaggerRequired = false;
    /**
     * Holds an array of active Plugin-classes that are required to run the test
     * These will be checked with ::assertAppState()
     * @var array
     */
    protected static array $requiredPlugins = [];
    /**
     * Holds an array of Plugin-classes that must not be active to run the test
     * These will be checked with ::assertAppState()
     * @var array
     */
    protected static array $forbiddenPlugins = [];
    /**
     * Hods an array of configs that must have the given value to run the test
     * Provide like [ 'autoQA.enableInternalTagCheck' => 1, ... ], "runtimeOptions." will be added automatically
     * These will be checked with ::assertAppState()
     * @var array
     */
    protected static array $requiredRuntimeOptions = [];
    /**
     * The user that will be logged in in the base setup
     * @var string
     */
    protected static string $testUserToLogin = 'testapiuser';

    /**
     * Retrieves the test API
     * @return editor_Test_ApiHelper
     */
    public static function api() : editor_Test_ApiHelper {
        return static::$_api;
    }

    /**
     * Use this method to add setting up additional stuff before the tests are performed
     */
    public static function beforeTests() : void {
    }

    /**
     * Use this method to clean up additional stuff after the tests have been performed
     */
    public static function afterTests(): void {

    }

    /**
     * Asserts that the application state could be loaded
     * @return stdClass
     */
    public static function assertAppState(){
        static::api()->login(static::$testUserToLogin, 'asdfasdf');
        static::assertLogin(static::$testUserToLogin);
        $state = static::api()->getJson('editor/index/applicationstate');
        static::assertTrue(is_object($state), 'Application state data is no object!');
        //other system checks
        static::assertEquals(0, $state->worker->scheduled, 'For API testing no scheduled workers are allowed in DB!');
        static::assertEquals(0, $state->worker->waiting, 'For API testing no waiting workers are allowed in DB!');
        static::assertEquals(0, $state->worker->running, 'For API testing no running workers are allowed in DB!');
        if(!$state->database->isUptodate) {
            die('Database is not up to date! '.$state->database->newCount.' new / '.$state->database->modCount.' modified.'."\n\n");
        }
        // check for termtagger
        if(static::$termtaggerRequired){
            static::assertFalse(empty($state->termtagger), 'Termtagger Plugin not active!');
            static::assertTrue($state->termtagger->runningAll, 'Some configured termtaggers are not running: '.print_r($state->termtagger->running,1));
        }
        // test the runtimeOptions whitelist
        foreach(static::$requiredPlugins as $plugin){
            self::assertContains($plugin, $state->pluginsLoaded, 'Plugin '.$plugin.' must be activated for this test case!');
        }
        // test the runtimeOptions blacklist
        foreach(static::$forbiddenPlugins as $plugin){
            self::assertNotContains($plugin, $state->pluginsLoaded, 'Plugin '.$plugin.' must not be activated for this test case!');
        }
        return $state;
    }

    /**
     * asserts that a certain user is loggedin
     * @param string $user
     * @return stdClass the login/status JSON for further processing
     */
    public static function assertLogin($user) {
        $json = static::api()->getJson('editor/session/'.static::api()->getAuthCookie());
        static::assertTrue(is_object($json), 'User "'.$user.'" is not authenticated!');
        static::assertEquals('authenticated', $json->state, 'User "'.$user.'" is not authenticated!');
        static::assertEquals($user, $json->user->login);
        return $json;
    }

    /**
     * Asserts that a default set of test users is available (provided by testdata.sql not imported by install-and-update kit!)
     */
    public static function assertNeededUsers() {
        static::api()->login('testlector', 'asdfasdf');
        $json = static::assertLogin('testlector');
        static::assertContains('editor', $json->user->roles, 'Checking users roles:');
        static::assertNotContains('pm', $json->user->roles, 'Checking users roles:');
        static::assertContains('basic', $json->user->roles, 'Checking users roles:');
        static::assertContains('noRights', $json->user->roles, 'Checking users roles:');

        static::api()->login('testtranslator', 'asdfasdf');
        $json = static::assertLogin('testtranslator');
        static::assertContains('editor', $json->user->roles, 'Checking users roles:');
        static::assertNotContains('pm', $json->user->roles, 'Checking users roles:');
        static::assertContains('basic', $json->user->roles, 'Checking users roles:');
        static::assertContains('noRights', $json->user->roles, 'Checking users roles:');


        static::api()->login('testtermproposer', 'asdfasdf');
        $json = static::assertLogin('testtermproposer');
        static::assertContains('termProposer', $json->user->roles, 'Checking users roles:');
        static::assertContains('editor', $json->user->roles, 'Checking users roles:');
        static::assertContains('pm', $json->user->roles, 'Checking users roles:');
        static::assertContains('basic', $json->user->roles, 'Checking users roles:');
        static::assertContains('noRights', $json->user->roles, 'Checking users roles:');

        static::api()->login('testmanager', 'asdfasdf');
        $json = static::assertLogin('testmanager');
        static::assertContains('editor', $json->user->roles, 'Checking users roles:');
        static::assertContains('pm', $json->user->roles, 'Checking users roles:');
        static::assertContains('basic', $json->user->roles, 'Checking users roles:');
        static::assertContains('noRights', $json->user->roles, 'Checking users roles:');
    }

    /**
     * Asserts that a default customer is loaded
     */
    public static function assertCustomer(){
        static::api()->loadCustomer();
    }

    /**
     * Asserts the required configs/runtimeOptions
     * @param string|null $taskGuid
     */
    public static function assertConfigs(string $taskGuid = null){
        if(count(static::$requiredRuntimeOptions) > 0){
            $plainFilter = ($taskGuid === null) ? [] : ['taskGuid' => $taskGuid];
            static::api()->testConfig(static::$requiredRuntimeOptions, $plainFilter);
        }
    }

    /**
     * Asserts, that the passed actual string matches the contents of the given file
     * @param string $fileName
     * @param string $actual
     * @param string|null $message
     * @param bool $capture here can be passed the isCapturing parameter from outside if it is a test not extending JsonTest
     */
    public function assertFileContents(string $fileName, string $actual, string $message=NULL, bool $capture = false) {
        $filePath = static::api()->getFile($fileName, null, false);
        if($capture) {
            file_put_contents($filePath, $actual);
        }
        static::assertEquals(file_get_contents($filePath), $actual, $message);
    }

    /***
     * Check if the current test request is from master tests.
     * It is used for skipping tests.
     * @return boolean
     */
    public static function isMasterTest() : bool {
        return !!getenv('MASTER_TEST');
    }

    final public static function setUpBeforeClass(): void {
        // each test gets an own api-object
        static::$_api = new editor_Test_ApiHelper(static::class);
        // this can be used in extending classes as replacement for setUpBeforeClass()
        static::beforeTests();
    }

    final public static function tearDownAfterClass(): void {
        // this can be used in extending classes as replacement for tearDownAfterClass()
        static::afterTests();
    }
}
