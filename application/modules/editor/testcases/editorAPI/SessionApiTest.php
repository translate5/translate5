<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Tests the User Auth API
 */
class SessionApiTest extends \ZfExtended_Test_ApiTestcase {
    
    
    public static function setUpBeforeClass(): void {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $task = array(
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
            'lockLocked' => 1,
        );
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        
        $api->addImportFile($api->getFile('justatask.xlf'));
        $api->import($task);
    }
    
    /**
     * Test correct behavior when all or one of the credential fields are empty
     */
    public function testEmptyCredentials() {
        $response = $this->api()->request('editor/session', 'POST');
        
        $this->assertEquals(422, $response->getStatus());
        $this->assertEquals('{"errors":[{"id":"login","msg":"No login given."},{"id":"passwd","msg":"No password given."}],"message":"NOT OK","success":false}', $response->getBody());
        
        $response = $this->api()->request('editor/session', 'POST', ['login' => 'givenLogin']);
        $this->assertEquals(422, $response->getStatus());
        $this->assertEquals('{"errors":[{"id":"passwd","msg":"No password given."}],"message":"NOT OK","success":false}', $response->getBody());
        
        $response = $this->api()->request('editor/session', 'POST', ['passwd' => 'givenPasswd']);
        $this->assertEquals(422, $response->getStatus());
        $this->assertEquals('{"errors":[{"id":"login","msg":"No login given."}],"message":"NOT OK","success":false}', $response->getBody());
    }
    
    /**
     * Test session API interface with wrong credentials
     */
    public function testWrongCredentials() {
        $response = $this->api()->request('editor/session', 'POST', ['login' => 'wrongUsername', 'passwd' => 'wrongPassword']);
        $msg403 = '{"httpStatus":403,"errorMessage":"Keine Zugriffsberechtigung!","message":"Forbidden","success":false,"messages":[]}';
        
        $this->assertEquals(403, $response->getStatus());
        $this->assertEquals($msg403, $response->getBody());
        
        $response = $this->api()->request('editor/session', 'POST', ['login' => 'testlector', 'passwd' => 'wrongPassword']);
        $this->assertEquals(403, $response->getStatus());
        $this->assertEquals($msg403, $response->getBody());
        
        $response = $this->api()->request('editor/session', 'POST', ['login' => 'wrongUsername', 'passwd' => 'asdfasdf']);
        $this->assertEquals(403, $response->getStatus());
        $this->assertEquals($msg403, $response->getBody());
    }
    
    /**
     * Test login with correct credentials
     * Test afterwards if the logout call is working 
     */
    public function testLogin() {
        $this->api()->login('testlector');
        self::assertLogin('testlector');
        
        $this->api()->login('testmanager');
        self::assertLogin('testmanager');
        
        $this->api()->logout();
        
        $json = $this->api()->requestJson('editor/session/'.$this->api()->getAuthCookie());
        $this->assertEquals('not authenticated', $json->state);
        $this->assertEmpty($json->user);
    }
    
    public function testSessionTokenWithTask() {
        $this->testSessionToken(true);
    }
    
    public function testSessionToken($withTask = false) {
        $this->api()->logout();
        
        $loginData = [
            'login' => 'testmanager',
            'passwd' => 'asdfasdf',
        ];
        
        $taskGuid = $this->api()->getTask()->taskGuid;
        if($withTask) {
            $loginData['taskGuid'] = $taskGuid;
        }
        
        $response = $this->api()->requestJson('editor/session', 'POST', $loginData);
        $sessionId = $response->sessionId;
        $sessionToken = $response->sessionToken;
        $plainResponse = $this->api()->getLastResponse();
        $this->assertEquals(200, $plainResponse->getStatus(), 'Server did not respond HTTP 200');
        $this->assertNotFalse($response, 'JSON Login request was not successfull!');
        $this->assertRegExp('/[a-zA-Z0-9]{26}/', $sessionId, 'Login call does not return a valid sessionId!');
        $this->assertRegExp('/[0-9a-fA-F]{32}/', $sessionToken, 'Login call does not return a valid sessionToken!');
        
        $response = $this->api()->request('editor/?sessionToken='.$sessionToken);
        $this->assertNotFalse(strpos($response->getBody(), '<div id="loading-indicator-text"></div>'), 'The editor page does not contain the expected content.');
        if($withTask) {
            $this->assertNotFalse(strpos($response->getBody(), '"taskGuid":"'.$taskGuid.'"'), 'The editor page does not contain the expected taskGuid for the opened task.');
        }
        else {
            $this->assertFalse(strpos($response->getBody(), '"taskGuid":"'.$taskGuid.'"'), 'The editor page does contain a taskGuid, which should not be.');
        }
        $sessionData = $this->api()->requestJson('editor/session/'.$sessionId);
        $this->assertEquals(200, $this->api()->getLastResponse()->getStatus(), 'Server did not respond HTTP 200');
        unset($sessionData->user->id);
        unset($sessionData->user->loginTimeStamp);
        
        //since default customer id changes on testsystems we have to test it via regex and remove it for next test
        if(!empty($sessionData->user->customers)) {
            //if a customer is set, it must like the following regex 
            //but customers are not mandatory, so empty check before
            $this->assertRegExp('/^,[0-9]+,$/', $sessionData->user->customers);
        }
        $sessionData->user->customers = null;
        
        $expected = '{"state":"authenticated","user":{"userGuid":"{00000000-0000-0000-C100-CCDDEE000001}","firstName":"manager","surName":"test","gender":"m","login":"testmanager","email":"support@translate5.net","roles":["pm","editor","admin","instantTranslate","basic","noRights"],"passwd":"********","editable":0,"locale":"en","sourceLanguage":null,"targetLanguage":null,"parentIds":null,"customers":null,"userName":"manager test"}}';
        $this->assertEquals(json_decode($expected), $sessionData, 'User was not properly authenticated via ');
        
        $this->api()->logout();
    }
    
    public function testSingleClickAuthentication() {
        $this->api()->logout();
        $this->api()->login('testmanager');
        $this->assertLogin('testmanager');
        $this->api()->reloadTask();
        $assoc = $this->api()->addUser('testlector');
        $this->assertFalse(isset($assoc->staticAuthHash), 'staticAuthHash for non API user must be empty!');
        
        $this->api()->login('testapiuser');
        $this->assertLogin('testapiuser');
        $assoc = $this->api()->requestJson('editor/taskuserassoc/'.$assoc->id);
        $hash = $assoc->staticAuthHash;
        $this->assertRegExp('/^(\{){0,1}[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}(\}){0,1}$/',$hash, 'Single click auth hash is no valid guid');
        $this->api()->logout();
        $response = $this->api()->request('editor/session?authhash='.$hash);
        $taskGuid = $this->api()->getTask()->taskGuid;
        $this->assertNotFalse(strpos($response->getBody(), '"taskGuid":"'.$taskGuid.'"'), 'The editor page does not contain the expected taskGuid for the opened task.');
        $this->assertLogin('testlector'); //must be testlector after single click auth
        $this->api()->logout();
    }
    
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testlector');
        self::$api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'open', 'id' => $task->id));
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}