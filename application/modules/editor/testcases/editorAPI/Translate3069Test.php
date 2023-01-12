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

use MittagQI\Translate5\Test\Import\Config;

/***
 * Test if the analysis and pre-translation field pretranslateMatchrate will be automatically set from the
 * config runtimeOptions.plugins.MatchAnalysis.pretranslateMatchRate
 */
class Translate3069Test extends editor_Test_JsonTest {

    protected static array $forbiddenPlugins = [
    ];
    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init',
        'editor_Plugins_MatchAnalysis_Init'
    ];

    protected static array $requiredRuntimeOptions = [
        'import.xlf.preserveWhitespace' => 0,
        'runtimeOptions.import.xlf.ignoreFramingTags' => 'all'
    ];

    protected static bool $setupOwnCustomer = true;

    protected static string $setupUserLogin = 'testmanager';


    private static int $configValue = 44;

    protected static function setupImport(Config $config): void
    {
        $sourceLangRfc = 'de';
        $targetLangRfc = 'en';
        $customerId = static::$ownCustomer->id;
        $config
            ->addLanguageResource('zdemomt', null, $customerId, $sourceLangRfc, $targetLangRfc)
            ->addDefaultCustomerId($customerId);

        static::api()->putJson('editor/config/',[
            'value' => static::$configValue,
            'customerId' => $customerId,
            'name' => 'runtimeOptions.plugins.MatchAnalysis.pretranslateMatchRate'
        ]);

        $config
            ->addPretranslation()
            ->setProperty('pretranslateMatchrate', null);

        $config
            ->addTask($sourceLangRfc, $targetLangRfc, $customerId, 'TRANSLATE-3069-de-en.html')
            ->setProperty('wordCount', 1270);
    }

    public function testSegmentValuesAfterImport()
    {
        /** @var editor_Plugins_MatchAnalysis_Models_TaskAssoc $analysis */
        $analysis = ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_TaskAssoc');
        $result = $analysis->loadNewestByTaskGuid(static::api()->getTask()->taskGuid);

        self::assertNotEmpty($result,'Empty analysis object');

        self::assertEquals(static::$configValue,$result['pretranslateMatchrate'],'The analysis match-rate is not as expected');
    }
}
