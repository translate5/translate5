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
 * Tests the User Auth API
 */
class SessionApiTest extends editor_Test_ImportTest {

    protected static function setupImport(Config $config): void
    {
        $config->addTask('en', 'de', -1, 'justatask.xlf');
    }
    
    /**
     * Test correct behavior when all or one of the credential fields are empty
     */
    public function testEmptyCredentials() {
        $response = static::api()->post('editor/session');
        
        $this->assertEquals(422, $response->getStatus());
        $this->assertStringContainsString('"errors":[{"id":"login","msg":"No login given."},{"id":"passwd","msg":"No password given."}]', $response->getBody());
        
        $response = static::api()->post('editor/session', ['login' => 'givenLogin']);
        $this->assertEquals(422, $response->getStatus());
        $this->assertStringContainsString('"errors":[{"id":"passwd","msg":"No password given."}]', $response->getBody());
        
        $response = static::api()->post('editor/session', ['passwd' => 'givenPasswd']);
        $this->assertEquals(422, $response->getStatus());
        $this->assertStringContainsString('"errors":[{"id":"login","msg":"No login given."}]', $response->getBody());
    }
    
    /**
     * Test session API interface with wrong credentials
     */
    public function testWrongCredentials() {
        $response = static::api()->post('editor/session', ['login' => 'wrongUsername', 'passwd' => 'wrongPassword']);
        $msg403 = '{"errorCode":null,"httpStatus":403,"errorMessage":"Keine Zugriffsberechtigung!","message":"Forbidden","success":false}';
        
        $this->assertEquals(403, $response->getStatus());
        $this->assertEquals($msg403, $response->getBody());
        
        $response = static::api()->post('editor/session', ['login' => 'testlector', 'passwd' => 'wrongPassword']);
        $this->assertEquals(403, $response->getStatus());
        $this->assertEquals($msg403, $response->getBody());
        
        $response = static::api()->post('editor/session', ['login' => 'wrongUsername', 'passwd' => Helper::PASSWORD]);
        $this->assertEquals(403, $response->getStatus());
        $this->assertEquals($msg403, $response->getBody());
    }
    
    /**
     * Test login with correct credentials
     * Test afterwards if the logout call is working
     */
    public function testLogin() {
        static::api()->login('testlector');
        static::assertLogin('testlector');
        
        static::api()->login('testmanager');
        static::assertLogin('testmanager');

        $authCookie = Helper::getAuthCookie();
        static::api()->logout();
        
        $json = static::api()->getJson('editor/session/'.$authCookie);
        $this->assertEquals('not authenticated', $json->state);
        $this->assertEmpty($json->user);
    }

    public function testSessionTokenWithTask() {
        $this->testSessionToken(true);
    }

    public function testSessionToken($withTask = false) {

        $task = static::api()->getTask();
        $taskGuid = $task->taskGuid;
        static::api()->logout();
        $loginData = [
            'login' => 'testmanager',
            'passwd' => Helper::PASSWORD,
        ];
        if($withTask) {
            $loginData['taskGuid'] = $taskGuid;
        }
        else {
            //remove internal task to prevent adding to the URL
            static::api()->setTask(null);
        }
        
        $response = static::api()->postJson('editor/session', $loginData);
        $plainResponse = static::api()->getLastResponse();
        $sessionId = $response->sessionId;
        $sessionToken = $response->sessionToken;

        // restore authentication-data in API
        Helper::setAuthentication($sessionId, 'testmanager');

        $this->assertEquals(200, $plainResponse->getStatus(), 'Server did not respond HTTP 200');
        $this->assertNotFalse($response, 'JSON Login request was not successful!');
        $this->assertMatchesRegularExpression('/[a-zA-Z0-9]{26}/', $sessionId, 'Login call does not return a valid sessionId!');
        $this->assertMatchesRegularExpression('/[0-9a-fA-F]{32}/', $sessionToken, 'Login call does not return a valid sessionToken!');

        if($withTask) {
            $this->assertEquals('/editor/taskid/'.$task->id.'/', $response->taskUrlPath, 'Login call does return a valid task URL!');
        }

        $response = static::api()->get('editor/?sessionToken='.$sessionToken);
        $this->assertNotFalse(strpos($response->getBody(), '<div id="loading-indicator-text"></div>'), 'The editor page does not contain the expected content.');
        if($withTask) {
            $this->assertNotFalse(strpos($response->getBody(), '"taskGuid":"'.$taskGuid.'"'), 'The editor page does not contain the expected taskGuid for the opened task.');
        }
        else {
            $this->assertFalse(strpos($response->getBody(), '"taskGuid":"'.$taskGuid.'"'), 'The editor page does contain a taskGuid, which should not be.');
        }
        $sessionData = static::api()->getJson('editor/session/'.$sessionId);
        $this->assertEquals(200, static::api()->getLastResponse()->getStatus(), 'Server did not respond HTTP 200');
        unset($sessionData->user->id);
        unset($sessionData->user->loginTimeStamp);
        
        //since default customer id changes on testsystems we have to test it via regex and remove it for next test
        if(!empty($sessionData->user->customers)) {
            //if a customer is set, it must like the following regex
            //but customers are not mandatory, so empty check before
            $this->assertMatchesRegularExpression('/^,[0-9]+,$/', $sessionData->user->customers);
        }
        $sessionData->user->customers = null;
        // TODO FIXME: it seems for these rights there is additional SQL needed in the test-creation SQL ?
        // $expected = '{"state":"authenticated","user":{"userGuid":"{00000000-0000-0000-C100-CCDDEE000001}","firstName":"manager","surName":"test","gender":"m","login":"testmanager","email":"noreply@translate5.net","roles":["pm","editor","admin","instantTranslate","api","termCustomerSearch","termProposer","termFinalizer","termPM","termPM_allClients","termReviewer","instantTranslateWriteTm","basic","noRights"],"passwd":"********","editable":0,"locale":"en","parentIds":null,"customers":null,"userName":"manager test"}}';
        $expected = '{"state":"authenticated","user":{"userGuid":"{00000000-0000-0000-C100-CCDDEE000001}","firstName":"manager","surName":"test","gender":"m","login":"testmanager","email":"noreply@translate5.net","roles":["pm","editor","admin","instantTranslate","api","instantTranslateWriteTm","basic","noRights"],"passwd":"********","editable":0,"locale":"en","parentIds":null,"customers":null,"userName":"manager test","isTokenAuth":false}}';
        $this->assertEquals(json_decode($expected), $sessionData, 'User was not properly authenticated via ');

        static::api()->logout();
        static::api()->setTask($task);
    }

    /**
     * @depends testSessionToken
     * @depends testSessionTokenWithTask
     * @return void
     * @throws Zend_Http_Client_Exception
     */
    public function __testSingleClickAuthentication() {
        static::api()->logout();
        static::api()->login('testmanager2');
        static::assertLogin('testmanager2');
        static::api()->reloadTask();
        $assoc = static::api()->addUser('testlector');
        $this->assertFalse(isset($assoc->staticAuthHash), 'staticAuthHash for non API user must be empty!');
        
        static::api()->login('testapiuser');
        static::assertLogin('testapiuser');
        $assoc = static::api()->getJson('editor/taskuserassoc/'.$assoc->id);
        $hash = $assoc->staticAuthHash;
        $this->assertMatchesRegularExpression('/^(\{){0,1}[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}(\}){0,1}$/',$hash, 'Single click auth hash is no valid guid');
        static::api()->logout();
        $response = static::api()->get('editor/session?authhash='.$hash);
        $taskGuid = static::api()->getTask()->taskGuid;
        $this->assertNotFalse(strpos($response->getBody(), '"taskGuid":"'.$taskGuid.'"'), 'The editor page does not contain the expected taskGuid for the opened task.');
        static::assertLogin('testlector'); //must be testlector after single click auth
        static::api()->logout();
    }
    
    /***
     * Test session impersonate feature.
     * 1. Login as manager
     * 2. Impersonate testlector
     * 3. Check if the current user is testlector
     */
    public function testSessionImpersonate() {
        static::api()->logout();
        static::api()->login('testmanager');
        static::assertLogin('testmanager');
        // This will replace the testmanager session with testlector
        static::api()->get('editor/session/impersonate', [
            'login' => 'testlector'
        ]);
        static::assertLogin('testlector');
        static::api()->logout();
    }
}
