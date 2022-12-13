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

/***
 * Application token authentication
 */
class Translate3108Test extends editor_Test_JsonTest {

    private static string $userLogin = 'testmanager';
    
    public function testAuthentication()
    {
        // this will remove and reset the cookie
        self::api()->logout();

        $auth = ZfExtended_Factory::get('ZfExtended_Auth_Token_Entity');
        /** @var ZfExtended_Auth_Token_Entity $auth */

        $auth->create(self::$userLogin).'ace';

        Helper::setApplicationToken($auth->create(self::$userLogin));

        static::api()->getJson('editor/task/');
        $response = static::api()->getLastResponse();
        self::assertContains($response->getStatus(),[200],'Error on authentication with app token');

        // set invalid token and test it again. Now the authentication should fail and not be possible
        Helper::setApplicationToken('Invalid_token');

        static::api()->getJson('editor/task/',expectedToFail: true);
        $response = static::api()->getLastResponse();
        self::assertNotContains($response->getStatus(),[200],'Something is wrong, authenticated with invalid token is possible!!!');
    }
}
