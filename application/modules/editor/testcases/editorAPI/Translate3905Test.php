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
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\JsonTestAbstract;

/**
 * This test is using IntantTranslate POSTed data for filepretranslation input
 */
class Translate3905Test extends JsonTestAbstract
{
    public const SOURCE_LANG = 'de';

    public const TARGET_LANG = 'en';

    public const TESTDATA_EXPORT_XLF = 'testdata-export.xlf';

    protected static array $requiredPlugins = [
        'editor_Plugins_InstantTranslate_Init',
        'editor_Plugins_Okapi_Init',
        'editor_Plugins_MatchAnalysis_Init',
        'editor_Plugins_ZDemoMT_Init',
    ];

    /**
     * @throws \MittagQI\Translate5\Test\Import\Exception
     * @throws ZfExtended_Exception
     */
    protected static function setupImport(Config $config): void
    {
        $config
            ->addLanguageResource(
                'zdemomt',
                null,
                static::getTestCustomerId(),
                self::SOURCE_LANG,
                self::TARGET_LANG
            )
            ->addDefaultCustomerId(static::getTestCustomerId())
            ->setProperty('name', 'API Testing::ZDemoMT_Translate3905Test_one');
    }

    /**
     * @throws ZfExtended_Exception
     * @throws Exception
     */
    public static function beforeTests(): void
    {
        $json = self::assertLogin(TestUser::TestManager->value);
        self::assertContains('instantTranslate', $json->user->roles, 'Missing role for user.');
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
    }

    /**
     * @throws Zend_Http_Client_Exception
     * @throws \MittagQI\Translate5\Test\Api\Exception
     */
    public function testTranslateFileByPost()
    {
        $params = [
            'source' => self::SOURCE_LANG,
            'target' => self::TARGET_LANG,
            'fileName' => 'test.xlf',
            'fileData' => static::api()->getFileContentRaw('testdata.xlf'),
        ];

        static::api()->post('editor/instanttranslateapi/filepretranslate', $params);
        $responseBody = json_decode(static::api()->getLastResponse()->getBody());
        $this->assertEquals(
            '200',
            static::api()->getLastResponse()->getStatus(),
            'wrong HTTP status returned on POSTing filepretranslate, answer: ' . print_r($responseBody, true)
        );
        $this->assertObjectHasProperty(
            'taskId',
            $responseBody,
            'filepretranslate response does not contain a taskId'
        );
        $this->assertIsNumeric($responseBody->taskId, 'returned taskId is non numeric');

        static::api()->waitForTaskImported(static::api()->reloadTask($responseBody->taskId));
        $res = static::api()->get('/editor/task/export/id/' . $responseBody->taskId . '?format=filetranslation');

        if (static::api()->isCapturing()) {
            file_put_contents(static::api()->getFile(self::TESTDATA_EXPORT_XLF, null, false), rtrim($res->getBody()));
        }

        $this->assertEquals(static::api()->getFileContent(self::TESTDATA_EXPORT_XLF), $res->getBody());
    }
}
