
<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**
 * Test for the CSRF Protection
 * Generally all Tests test this feature so here more the error-cases are tested
 */
class Translate3048Test extends editor_Test_ApiTest {

    private static string $apitestToken;

    private static ?ZfExtended_Auth_Token_Entity $authTokenEntity = null;

    public static function beforeTests(): void
    {
        // this must be reset to the initial value after the test
        static::$apitestToken = Helper::getCsrfToken();
    }

    /**
     * Checks if an invalid token is rejected
     * @throws Zend_Http_Client_Exception
     */
    public function testInvalidToken()
    {
        Helper::setCsrfToken('InvalidToken');
        $result = static::api()->getJson('editor/task/', [], null, true);
        $this->assertEquals(401, $result->status);
        $this->assertTrue((str_contains($result->data, 'Nicht authentifiziert') || str_contains($result->error, 'Unauthenticated')));
    }

    /**
     * Checks if an empty token is rejected
     * @throws Zend_Http_Client_Exception
     */
    public function testEmptyToken()
    {
        Helper::setCsrfToken(null); // null will lead to token not being sent
        $result = static::api()->getJson('editor/task/', [], null, true);
        $this->assertEquals(401, $result->status);
        $this->assertTrue((str_contains($result->data, 'Nicht authentifiziert') || str_contains($result->error, 'Unauthenticated')));
    }

    /**
     * This test checks the API with a real CSRF token extracted from the App's markup
     * @throws Zend_Http_Client_Exception
     */
    public function testRealToken()
    {
        // logout & destroy session & use normal app origin
        static::api()->logout();
        Helper::activateOriginHeader(false);
        Helper::setCsrfToken(null);

        // Login to generate a proper session token
        static::api()->login('testmanager');

        // retrieve App's index-page
        $authCookie = Helper::getAuthCookie();
        $response = static::api()->getHtmlPage('/editor/index', [], $authCookie);
        $appPage = $response->getBody();

        // extract the CSRF token
        $matches = [];
        $success = preg_match('~,\s*"csrfToken"\s*:\s*"([a-zA-Z0-9]+)"\s*,~', $appPage, $matches);
        static::assertEquals(1, $success, 'No CSRF Token could be extracted from the App-Markup');
        $csrfToken = $matches[1];

        // fetch the user endpoint with the extracted token, validate the result to contain the testmanager
        Helper::setCsrfToken($csrfToken);
        $this->fetchAndAssertUsers();
        static::api()->logout();

        // restore original state
        Helper::activateOriginHeader(true);
        Helper::setCsrfToken(static::$apitestToken);
        static::api()->login('testmanager');
    }



    /**
     * Tests if a API-call with an App-Token can be made successfully
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function testApiToken()
    {
        // generate an auth-token temporarily
        static::$authTokenEntity = ZfExtended_Factory::get(ZfExtended_Auth_Token_Entity::class);
        $appToken = static::$authTokenEntity->create('testmanager', static::class);

        // logout & destroy session & use app origin
        static::api()->logout();
        Helper::setCsrfToken(null);

        // requests the users-list with the app-token
        Helper::setApplicationToken($appToken);
        $this->fetchAndAssertUsers();

        // restore original state
        Helper::unsetApplicationToken();
        Helper::setCsrfToken(static::$apitestToken);
        static::api()->login('testmanager');
    }


    /**
     * Fetches the /user endpoint and validates the result as an example for a API-call
     * @throws Zend_Http_Client_Exception
     */
    private function fetchAndAssertUsers(){
        $users = static::api()->getJson('/editor/user');
        static::assertTrue(is_array($users), 'Fetching the Users-List failed');
        $testmanagerFound = false;
        foreach($users as $user){
            if($user->login === 'testmanager'){
                $testmanagerFound = true;
            }
        }
        static::assertTrue($testmanagerFound, 'The Users-List did not contain a user with login "testmanager"');
    }

    public static function afterTests(): void
    {
        // remove the temporary auth-token
        if(static::$authTokenEntity !== null){
            static::$authTokenEntity->delete();
        }
        // restore working state no matter if tests passed or failed
        Helper::setCsrfToken(static::$apitestToken);
        Helper::activateOriginHeader();
        Helper::unsetApplicationToken();
    }
}
