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

/**
 * Match analysis tests.
 * The test will check if the current codebase provides a valid matchanalysis test results.
 * The valid test results are counted by hand and they are correct.
 *
 */
class MatchAnalysisTest extends editor_Test_ImportTest {
    
    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init',
        'editor_Plugins_MatchAnalysis_Init',
        'editor_Plugins_InstantTranslate_Init'
    ];

    protected static function setupImport(Config $config): void
    {
        $sourceLangRfc = 'de';
        $targetLangRfc = 'en';
        $config
            ->addLanguageResource('opentm2', 'resource1.tmx', static::getTestCustomerId(), $sourceLangRfc, $targetLangRfc)
            ->setProperty('name', 'MATESTresource1'); // TODO FIXME: we better generate data independent from resource-names ...
        $config
            ->addLanguageResource('opentm2', 'resource2.tmx', static::getTestCustomerId(), $sourceLangRfc, $targetLangRfc)
            ->setProperty('name', 'MATESTresource2'); // TODO FIXME: we better generate data independent from resource-names ...
        $config
            ->addLanguageResource('termcollection', 'collection.tbx', static::getTestCustomerId())
            ->setProperty('name', 'MATESTresource3'); // TODO FIXME: we better generate data independent from resource-names ...
        $config->addPretranslation();
        $config
            ->addTask($sourceLangRfc, $targetLangRfc, static::getTestCustomerId())
            ->addUploadFolder('testfiles', 'XLF-test.zip')
            ->setProperty('wordCount', 1270)
            ->setProperty('taskName', 'API Testing::MatchAnalysisTest'); // TODO FIXME: we better generate data independent from resource-names ...
    }

    /***
     * @return void
     */
    public function testExportXmlResultsWord(): void
    {
        $this->exportXmlResults(false);
    }

    /***
     * @return void
     */
    public function testExportXmlResultsCharacter(): void
    {
        $this->exportXmlResults(true);
    }

    /**
     *
     * @return void
     */
    public function testWordBasedResults(): void
    {
        $this->validateResults(false);
    }

    /**
     *
     * @return void
     */
    public function testCharacterBasedResults(): void
    {
        $this->validateResults(true);
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
            'type' => 'exportXml'
        ]);

        self::assertTrue($response->getStatus() === 200, 'export XML HTTP Status is not 200');
        $actual = static::api()->formatXml($response->getBody());

        //sanitize task information
        $actual = str_replace('number="'.$taskGuid.'"/>', 'number="UNTESTABLECONTENT"/>', $actual);

        //sanitize analysis information
        $actual = preg_replace(
            '/<taskInfo taskId="([^"]*)" runAt="([^"]*)" runTime="([^"]*)">/',
            '<taskInfo taskId="UNTESTABLECONTENT" runAt="UNTESTABLECONTENT" runTime="UNTESTABLECONTENT">',
            $actual);

        static::api()->isCapturing() && file_put_contents(static::api()->getFile('exportResults-'.$unitType.'.xml', null, false), $actual);
        $expected = static::api()->getFileContent('exportResults-'.$unitType.'.xml');

        //check for differences between the expected and the actual content
        self::assertEquals($expected, $actual, "The expected file(exportResults) an the result file does not match.");
    }

    /***
     * Validate the analysis results.
     * 1. the first validation will validate the grouped results for the analysis
     * 2. the second validation will validate the all existing results for the analysis
     *
     * @param bool $characterBased
     * @return void
     */
    private function validateResults(bool $characterBased): void
    {
        $unitType = $characterBased ? 'character' : 'word';

        $jsonFileName = 'analysis-'.$unitType.'.json';
        // fetch task data
        $analysis = static::api()->getJson('editor/plugins_matchanalysis_matchanalysis', [ 'taskGuid'=> static::api()->getTask()->taskGuid, 'unitType' => $unitType ], $jsonFileName);
        $this->assertNotEmpty($analysis,'No results found for the '.$unitType.'-based task-specific matchanalysis.');
        //check for differences between the expected and the actual content
        $expectedAnalysis = static::api()->getFileContent($jsonFileName);
        $this->assertEquals($this->filterTaskAnalysis($expectedAnalysis), $this->filterTaskAnalysis($analysis), 'The expected file and the data does not match for the '.$unitType.'-based task-specific matchanalysis.');

        //now test all results and matches
        $jsonFileName = 'allanalysis-'.$unitType.'.json';
        $analysis = static::api()->getJson('editor/plugins_matchanalysis_matchanalysis', [ 'taskGuid' => static::api()->getTask()->taskGuid, 'notGrouped' => static::api()->getTask()->taskGuid ], $jsonFileName);
        $this->assertNotEmpty($analysis,'No results found for the '.$unitType.'-based not-grouped matchanalysis.');
        //check for differences between the expected and the actual content
        $expectedAnalysis = static::api()->getFileContent($jsonFileName);
        //check for differences between the expected and the actual content
        $this->assertEquals($this->filterUngroupedAnalysis($expectedAnalysis), $this->filterUngroupedAnalysis($analysis), "The expected file and the data does not match for the '.$unitType.'-based not-grouped matchanalysis..");
    }

    /**
     * @param array $data
     * @return array
     */
    private function filterTaskAnalysis(array &$data) : array {
        // remove the created timestamp since is not relevant for the test
        foreach ($data as &$a){
            unset($a->created);
        }
        usort($data, function($a, $b){ return strcmp($a->resourceName, $b->resourceName); });
        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    private function filterUngroupedAnalysis(array &$data){
        // remove some of the unneeded columns
        foreach ($data as &$a){
            unset($a->id);
            unset($a->taskGuid);
            unset($a->analysisId);
            unset($a->segmentId);
            unset($a->languageResourceid);
        }
        return $data;
    }
}