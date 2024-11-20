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

use MittagQI\Translate5\Test\Enums\TestUser;
use MittagQI\Translate5\Test\JsonTestAbstract;

/***
 * This test will create and write content into instant translate memory. The content which is saved to the tm, will be
 * additionally validate with instant-translate search functionality
 *
 */
class Translate2483Test extends JsonTestAbstract
{
    /***
     * String which will be saved as source in the instant-translate memory
     * @var string
     */
    private static string $sourceText = '';

    /***
     * String which will be saved as target in the instant-translate memory
     * @var string
     */
    private static string $targetText = '';

    /***
     * Memory source langauge
     * @var string
     */
    private static string $sourceLang = 'en';

    /***
     * Memory target language
     * @var string
     */
    private static string $targetLang = 'de';

    /***
     * All created resources by this test. Those resources will be removed at the end of the test.
     * @var array
     */
    private static array $createdResources = [];

    protected static array $requiredPlugins = [
        'editor_Plugins_InstantTranslate_Init',
    ];

    /**
     * @throws ZfExtended_Exception
     * @throws Exception
     */
    public static function beforeTests(): void
    {
        $json = self::assertLogin(TestUser::TestManager->value);
        self::assertContains('instantTranslate', $json->user->roles, 'Missing role for user.');
        self::assertContains('instantTranslateWriteTm', $json->user->roles, 'Missing role for user.');
        $userIds = [];
        foreach (explode(',', $json->user->customers) as $userId) {
            if (! empty($userId)) {
                $userIds[] = intval($userId);
            }
        }
        self::assertContains(
            static::getTestCustomerId(),
            $userIds,
            'The test customer is not assigned to the testmanager'
        );
        self::$sourceText = bin2hex(random_bytes(10));
        self::$targetText = bin2hex(random_bytes(10));
    }

    /**
     * @throws Zend_Http_Client_Exception
     */
    public function testCreateAndWrite()
    {
        $this->createAndWrite();
        $this->translate();
    }

    /**
     *  Create and write into instant-translate memory
     * @throws Zend_Http_Client_Exception
     */
    private function createAndWrite(): void
    {
        self::assertLogin(TestUser::TestManager->value);
        $response = static::api()->postJson(
            'editor/instanttranslateapi/writetm',
            [
                'source' => self::$sourceText,
                'target' => self::$targetText,
                'sourceLanguage' => self::$sourceLang,
                'targetLanguage' => self::$targetLang,
            ]
        );

        self::$createdResources = $response->created ?? [];
        self::assertNotEmpty(self::$createdResources, "No Instant-Translate memories where created by this test");
    }

    /**
     * InstantTranslate:
     * Run a translation with our OpenTM2-LanguageResource
     * and check if the result is as expected.
     * IMPORTANT:The "translate" API call, will return only the results for the customers of the current user.
     * Memories will be created for all user customers where the user has instantTranslate role
     */
    private function translate(): void
    {
        $params = [];
        $params['source'] = self::$sourceLang;
        $params['target'] = self::$targetLang;
        $params['text'] = self::$sourceText;
        $params['langresId'] = self::$createdResources[0];

        // (according to Confluence: GET / according to InstantTranslate in Browser: POST)
        static::api()->getJson('editor/instanttranslateapi/translate', $params);
        $responseBody = json_decode(static::api()->getLastResponse()->getBody());
        // Is anything returned for Deep at all?
        $this->assertIsObject($responseBody, 'InstantTranslate: Response for translation does not return an object, check error log.');
        $this->assertIsObject($responseBody->rows, 'InstantTranslate: Response for translation does not return any rows.');
        $allTranslations = (array) $responseBody->rows;
        $this->assertIsArray($allTranslations);
        $this->assertArrayHasKey('OpenTM2', $allTranslations, 'InstantTranslate: Translations do not include a response for OpenTM2.');

        self::assertNotEmpty($allTranslations['OpenTM2'], "InstantTranslate: no translation where saved to the TM");

        // Does the result match our expectations?
        foreach ($allTranslations['OpenTM2'] as $translation) {
            self::assertNotEmpty($translation, "InstantTranslate: no translation where saved to the TM");

            $translation = htmlspecialchars_decode($translation[0]->target);
            $this->assertEquals(self::$targetText, $translation, 'Result of translation is not as expected! Text was:' . "\n" . self::$sourceText);
        }
    }

    public static function afterTests(): void
    {
        foreach (self::$createdResources as $createdResource) {
            static::api()->delete('editor/languageresourceinstance/' . $createdResource);
        }
    }
}
