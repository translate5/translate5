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
use MittagQI\Translate5\Test\ImportTestAbstract;

/**
 * Match analysis tests.
 * The test will check if the current codebase provides a valid matchanalysis test results.
 * The valid test results are counted by hand.
 * For more info about the segment results check the Analysis test result Info.ods document in the test folder
 */
class MatchAnalysisTest extends ImportTestAbstract
{
    use \MittagQI\Translate5\Test\Api\AnalysisTrait;

    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init',
        'editor_Plugins_MatchAnalysis_Init',
        'editor_Plugins_InstantTranslate_Init',
    ];

    /***
     * Configs which are changed for test tests and needs to be revoked after the test is done
     * @var array
     */
    protected static $changedConfigs = [];

    protected static function setupImport(Config $config): void
    {
        $sourceLangRfc = 'en';
        $targetLangRfc = 'de';
        $customerId = static::getTestCustomerId();

        // collect the original config value so it will be set again after the test is finished
        self::$changedConfigs[] = static::api()->getConfig('runtimeOptions.import.edit100PercentMatch');

        static::api()->putJson('editor/config/', [
            'value' => 0,
            'name' => 'runtimeOptions.import.edit100PercentMatch',
        ]);

        $config
            ->addLanguageResource('opentm2', 'MatchAnalysisTest_TM.tmx', $customerId, $sourceLangRfc, $targetLangRfc)
            ->setProperty('name', 'MatchAnalysisTest_TM');
        $config
            ->addLanguageResource('termcollection', 'MatchAnalysisTest_Collection.tbx', $customerId)
            ->setProperty('name', 'MatchAnalysisTest_Collection');
        $config
            ->addPretranslation();

        $config
            ->addTask($sourceLangRfc, $targetLangRfc, $customerId, 'Test-task-en-de_v2.html')
            ->setProperty('wordCount', 114)
            ->setProperty('taskName', 'API Testing::MatchAnalysisTest')
            ->setProperty('edit100PercentMatch', true);
    }

    /***
     * @return void
     */
    public function testExportXmlResultsWord(): void
    {
        $this->exportXmlResults(false);
    }

    public function testWordBasedResults(): void
    {
        $this->validateGroupedResults(false);
    }

    public function testCharacterBasedResults(): void
    {
        $this->validateGroupedResults(true);
    }

    /***
     * Validate all analysis results.
     */
    public function testValidateAllResults(): void
    {
        $unitType = 'word';
        $jsonFileName = 'allanalysis-' . $unitType . '.json';
        $analysis = static::api()->getJson('editor/plugins_matchanalysis_matchanalysis', [
            'taskGuid' => static::api()->getTask()->taskGuid,
            'notGrouped' => static::api()->getTask()->taskGuid,
        ], $jsonFileName);
        $this->assertNotEmpty($analysis, 'No results found for the ' . $unitType . '-based not-grouped matchanalysis.');

        static::api()->isCapturing() && file_put_contents(static::api()->getFile($jsonFileName, null, false), json_encode($this->filterUngroupedAnalysis($analysis), JSON_PRETTY_PRINT));

        $expectedAnalysis = static::api()->getFileContent($jsonFileName);

        $this->assertEquals(
            $this->filterUngroupedAnalysis($expectedAnalysis),
            $this->filterUngroupedAnalysis($analysis),
            'The expected file and the data does not match for the ' . $unitType . '-based not-grouped matchanalysis..'
        );
    }

    /***
     * Validate the grouped analysis results.
     */
    private function validateGroupedResults(bool $characterBased): void
    {
        $unitType = $characterBased ? 'character' : 'word';

        $jsonFileName = 'analysis-' . $unitType . '.json';
        // fetch task data
        $analysis = static::api()->getJson('editor/plugins_matchanalysis_matchanalysis', [
            'taskGuid' => static::api()->getTask()->taskGuid,
            'unitType' => $unitType,
        ], $jsonFileName);
        $this->assertNotEmpty($analysis, 'No results found for the ' . $unitType . '-based task-specific matchanalysis.');
        //check for differences between the expected and the actual content
        $expectedAnalysis = static::api()->getFileContent($jsonFileName);

        $this->assertEquals(
            $this->filterTaskAnalysis($expectedAnalysis),
            $this->filterTaskAnalysis($analysis),
            'The expected file and the data does not match for the ' . $unitType . '-based task-specific matchanalysis.'
        );
    }

    /***
     * Test the xml analysis summary
     */
    private function exportXmlResults(bool $characterBased): void
    {
        $unitType = $characterBased ? 'character' : 'word';

        $taskGuid = static::api()->getTask()->taskGuid;
        $response = static::api()->get('editor/plugins_matchanalysis_matchanalysis/export', [
            'taskGuid' => $taskGuid,
            'type' => 'exportXml',
        ]);

        self::assertTrue($response->getStatus() === 200, 'export XML HTTP Status is not 200');
        $actual = static::api()->formatXml($response->getBody());

        //sanitize task information
        $actual = str_replace('number="' . $taskGuid . '"/>', 'number="UNTESTABLECONTENT"/>', $actual);

        //sanitize analysis information
        $actual = preg_replace(
            '/<taskInfo taskId="([^"]*)" runAt="([^"]*)" runTime="([^"]*)">/',
            '<taskInfo taskId="UNTESTABLECONTENT" runAt="UNTESTABLECONTENT" runTime="UNTESTABLECONTENT">',
            $actual
        );

        static::api()->isCapturing() && file_put_contents(static::api()->getFile('exportResults-' . $unitType . '.xml', null, false), $actual);
        $expected = static::api()->getFileContent('exportResults-' . $unitType . '.xml');

        //check for differences between the expected and the actual content
        self::assertEquals($expected, $actual, "The expected file(exportResults) an the result file does not match.");
    }

    public static function afterTests(): void
    {
        foreach (self::$changedConfigs as $c) {
            static::api()->putJson('editor/config/', [
                'value' => $c->value,
                'name' => $c->name,
            ]);
        }
    }
}
