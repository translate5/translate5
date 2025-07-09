<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2023 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Plugins\IpAuthentication\tests\Functional;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use MittagQI\Translate5\Customer\CustomerConfigService;
use MittagQI\Translate5\DbConfig\ConfigService;
use MittagQI\Translate5\Test\Api\Helper;
use MittagQI\Translate5\Test\ApiTestAbstract;
use MittagQI\Translate5\Test\Enums\TestUser;
use Psr\Http\Message\RequestInterface;

class IpAuthenticationTest extends ApiTestAbstract
{
    private static CustomerConfigService $customerConfigService;

    private static int $customerId;

    private static CookieJar $cookies;

    public static function beforeTests(): void
    {
        $customer = new \editor_Models_Customer_Customer();
        $customer->loadByNumber(Helper::TEST_CUSTOMER_NUMBER);

        $configService = ConfigService::create();
        $configService->updateConfig('runtimeOptions.authentication.ipbased.applets', '[]');

        //same test but not with customer relation!
        $configService->updateConfig(
            'runtimeOptions.authentication.ipbased.IpCustomerMap',
            '{"' . gethostbyname(gethostname()) . '":"' . Helper::TEST_CUSTOMER_NUMBER . '"}',
        );

        self::$customerId = (int) $customer->getId();

        self::$customerConfigService = CustomerConfigService::create();

        /*
        testcases
         IpBased matched:
           logout
           /instanttranslate → InstantTranslate as IpBased
           /instanttranslate →

        //    /editor/instanttranslate#itranslate
//    /editor/termportal#itranslate
//    /editor/taskid/8171/#project/8171/8171/focus

        */
    }

    public function testAlwaysRedirectToInstanttranslate(): void
    {
        self::$customerConfigService->upsertConfig(
            self::$customerId,
            'runtimeOptions.authentication.ipbased.applets',
            '[]'
        );

        $this->assertRedirect(
            '/editor/',
            '/editor/termportal#itranslate',
            true
        );

        $this->assertRedirect(
            '/login/',
            '/editor/termportal#itranslate',
            true
        );
    }

    public function testIpBasedAllowLoginOnEditor()
    {
        self::$customerConfigService->upsertConfig(
            self::$customerId,
            'runtimeOptions.authentication.ipbased.applets',
            '["itranslate","instanttranslate","termportal"]'
        );

        $this->assertRedirect(
            '/editor/',
            '/login',
            false
        );

        $this->assertRedirect(
            '/',
            '/login',
            false
        );

        // remain on termportal / instanttranslate
        $this->assertRedirect(
            '/editor/termportal#itranslate',
            '/editor/termportal',
            true
        );

        // we are still an ip based user, /editor/ is allowed in general,
        // IP Based user may not access it, so its getting logged out and redirected to /login for proper login
        $this->assertRedirect(
            '/editor/',
            '/login',
            false,
            logout: false
        );
    }

    public function testEditorUserWithNoInstantAccess()
    {
        self::$customerConfigService->upsertConfig(
            self::$customerId,
            'runtimeOptions.authentication.ipbased.applets',
            '["itranslate","instanttranslate","termportal"]'
        );

        //as normal user test /editor/ access
        $this->assertRedirect(
            '/editor/',
            '/editor/',
            false,
            logout: false,
            login: TestUser::TestLector->value
        );

        //still the same testlector user:
        $this->assertRedirect(
            '/login',
            '/editor/',
            false,
            logout: false
        );

        //as testlector user we try to access instanttranslate#itranslate, testlector is not allowed to to do so,
        // therefore we end up as IP based user again
        $this->assertRedirect(
            '/editor/instanttranslate#itranslate',
            '/editor/instanttranslate',
            true,
            logout: false
        );
    }

    public function testManagerUserWithInstantAccess()
    {
        self::$customerConfigService->upsertConfig(
            self::$customerId,
            'runtimeOptions.authentication.ipbased.applets',
            '["itranslate","instanttranslate","termportal"]'
        );

        //as normal user test /editor/ access
        $this->assertRedirect(
            '/editor/',
            '/editor/',
            false,
            logout: false,
            login: TestUser::TestManager->value
        );

        //still the same testmanager user:
        $this->assertRedirect(
            '/login',
            '/editor/',
            false,
            logout: false
        );

        //as testmanager user we may access instanttranslate#itranslate
        $this->assertRedirect(
            '/editor/instanttranslate#itranslate',
            '/editor/instanttranslate#itranslate',
            false,
            logout: false
        );
    }

    protected function assertRedirect(
        string $desiredUrl,
        string $expectedUrl,
        bool $isIpBasedUser,
        bool $logout = true,
        string $login = '',
    ): void {
        $apiUrl = rtrim($this->api()->getTestConfig()['API_URL'], '/');
        $desiredUrl = $apiUrl . $desiredUrl;
        $expectedUrl = $apiUrl . $expectedUrl;
        $sessionUrl = $apiUrl . '/editor/session';

        $redirects = [];

        $history = Middleware::tap(
            function (RequestInterface $request, array $options) use (&$redirects) {
                $redirects[] = (string) $request->getUri();
            },
            function (RequestInterface $request) {
                //print_r($request->getHeaders());
            }
        );

        $stack = HandlerStack::create();
        $stack->push($history);

        if (empty(self::$cookies) || $logout) {
            self::$cookies = CookieJar::fromArray([
                'XDEBUG_SESSION' => $this->api()->xdebug ? 'PHPSTORM' : '',
            ], 'php');
        }

        $client = new Client([
            'handler' => $stack,
            'allow_redirects' => [
                'max' => 10,
                'track_redirects' => true,
            ],
            'cookies' => self::$cookies,
        ]);

        if ($logout) {
            $client->request('GET', $apiUrl . $this->api()->getTestConfig()['LOGOUT_PATH']);
        }

        if (strlen($login) > 0) {
            $client->request('POST', $sessionUrl, [
                'json' => [
                    'login' => $login,
                    'passwd' => self::api()::PASSWORD,
                ],
            ]);
        }

        $client->request('GET', $desiredUrl);

        //        // Print the chain of redirects
        //        echo "Redirect history:\n";
        //        foreach ($redirects as $i => $url) {
        //            echo ($i + 1) . ": $url\n";
        //        }

        $actualUrl = end($redirects);
        //remove ports - in tests sometimes given on one side of the comparison
        $actualUrl = preg_replace('#:\d+/#', '/', $actualUrl);
        $expectedUrl = preg_replace('#:\d+/#', '/', $expectedUrl);
        $this->assertEquals($expectedUrl, $actualUrl, 'Request did not finally land on ' . $expectedUrl);

        $response = $client->request('GET', $sessionUrl);

        if (strlen($login) > 0) {
            $this->assertStringContainsString(
                '<span id="login">' . $login . '</span>',
                $response->getBody(),
                'User is not the expected ' . $login
            );
        }

        if ($isIpBasedUser) {
            $this->assertStringContainsString(
                '<span id="login">tmp-ip-based-user',
                $response->getBody(),
                'Not asserted as tmp-ip-based-user'
            );
        } else {
            $this->assertStringNotContainsString(
                '<span id="login">tmp-ip-based-user',
                $response->getBody(),
                'User is ip based, but should not be'
            );
        }

        //        // Print the chain of redirects
        //        echo "Redirect history:\n";
        //        foreach ($redirects as $i => $url) {
        //            echo ($i + 1) . ": $url\n";
        //        }
    }

    public static function afterTests(): void
    {
        $configService = ConfigService::create();
        $configService->updateConfig(
            'runtimeOptions.authentication.ipbased.IpCustomerMap',
            '{}',
        );
    }
}
