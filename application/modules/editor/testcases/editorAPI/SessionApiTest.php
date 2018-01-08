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
    
    
    public static function setUpBeforeClass() {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
    }
    
    /**
     * Test correct behavior when all or one of the credential fields are empty
     */
    public function testEmptyCredentials() {
        $response = $this->api()->request('editor/session', 'POST');
        
        $this->assertEquals(400, $response->getStatus());
        $this->assertEquals('{"errors":[{"id":"login","msg":"No login given."},{"id":"passwd","msg":"No password given."}],"message":"NOT OK","success":false}', $response->getBody());
        
        $response = $this->api()->request('editor/session', 'POST', ['login' => 'givenLogin']);
        $this->assertEquals(400, $response->getStatus());
        $this->assertEquals('{"errors":[{"id":"passwd","msg":"No password given."}],"message":"NOT OK","success":false}', $response->getBody());
        
        $response = $this->api()->request('editor/session', 'POST', ['passwd' => 'givenPasswd']);
        $this->assertEquals(400, $response->getStatus());
        $this->assertEquals('{"errors":[{"id":"login","msg":"No login given."}],"message":"NOT OK","success":false}', $response->getBody());
    }
    
    /**
     * Test session API interface with wrong credentials
     */
    public function testWrongCredentials() {
        $response = $this->api()->request('editor/session', 'POST', ['login' => 'wrongUsername', 'passwd' => 'wrongPassword']);
        $msg403 = '{"errors":[{"_errorMessage":"Keine Zugriffsberechtigung!","_errorCode":403}]}';
        
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
        
        global $T5_LOGOUT_PATH;
        $this->api()->request($T5_LOGOUT_PATH);
        
        $json = $this->api()->requestJson('editor/session/'.$this->api()->getAuthCookie());
        $this->assertEquals('not authenticated', $json->state);
        $this->assertEmpty($json->user);
    }
}