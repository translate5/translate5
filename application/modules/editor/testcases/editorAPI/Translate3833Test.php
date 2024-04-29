<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**
 * This will test if the blocked segments which are containing repetitions are included in the analysis results.
 * The task contains 4 segments:
 *  segment 1 is blocked segment
 *  segment 2 is not blocked segment but it is repetition of segment 1
 *  segment 3 is blocked segmnet
 *  segment 4 is not blocked segment but it is repetition of segment 3
 *
 */
class Translate3833Test extends editor_Test_JsonTest
{
    use \MittagQI\Translate5\Test\Api\AnalysisTrait;

    protected static array $requiredPlugins = [
        'editor_Plugins_MatchAnalysis_Init',
        'editor_Plugins_ZDemoMT_Init'
    ];

    protected static bool $setupOwnCustomer = false;

    protected static string $setupUserLogin = 'testmanager';

    protected static function setupImport(Config $config): void
    {
        $sourceLangRfc = 'de';
        $targetLangRfc = 'en';
        $customerId = static::getTestCustomerId();
        $config->addLanguageResource(
            'zdemomt',
            null,
            $customerId,
            $sourceLangRfc,
            $targetLangRfc
        );
        $config->addPretranslation();
        $config->addTask($sourceLangRfc, $targetLangRfc, $customerId, 'TRANSLATE-3833-de-en.xlf');
    }

    public function testImport()
    {

        $jsonFileName = 'analysis-results.json';

        $analysis = static::api()->getJson('editor/plugins_matchanalysis_matchanalysis', [
            'taskGuid' => static::api()->getTask()->taskGuid,
            'notGrouped' => static::api()->getTask()->taskGuid,
        ], $jsonFileName);

        $this->assertNotEmpty($analysis,
            'No results found for the matchanalysis.'
        );

        $analysis = $this->filterUngroupedAnalysis($analysis);
        $analysis = json_encode($analysis, JSON_PRETTY_PRINT);

        $expectedAnalysis = static::api()->getFileContentRaw($jsonFileName);

        self::api()->isCapturing() &&
        file_put_contents(
            static::api()->getFile($jsonFileName, null, false),
            $analysis
        );

        //check for differences between the expected and the actual content
        $this->assertEquals(
            $expectedAnalysis,
            $analysis,
            'The expected file and the data does not match for the matchanalysis.'
        );
    }
}
