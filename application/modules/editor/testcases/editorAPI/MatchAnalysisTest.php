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

/**
 * Match analysis tests.
 * The test will check if the current codebase provides a valid matchanalysis test results.
 * The valid test results are counted by hand and thay are correct.
 *
 */
class MatchAnalysisTest extends \ZfExtended_Test_ApiTestcase {
    
    protected static $sourceLangRfc = 'de';
    protected static $targetLangRfc = 'en';
    
    protected static $prefix = 'MATEST';
    
    /**
     */
    public static function setUpBeforeClass(): void {
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $appState = self::assertAppState();
        self::assertContains('editor_Plugins_Okapi_Init', $appState->pluginsLoaded, 'Plugin Okapi must be activated for this test case!');
        self::assertContains('editor_Plugins_InstantTranslate_Init', $appState->pluginsLoaded, 'Plugin InstantTranslate must be activated for this test case!');
        self::assertContains('editor_Plugins_MatchAnalysis_Init', $appState->pluginsLoaded, 'Plugin MatchAnalysis must be activated for this test case!');
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertCustomer();//assert the test customer

        // Import all required resources and task before running the tests
        static::addTm('resource1.tmx', static::getLrRenderName('resource1'));
        static::addTm('resource2.tmx', static::getLrRenderName('resource2'));
        static::addTermCollection('collection.tbx', static::getLrRenderName('resource3'));
        static::createTask();
        self::$api->addTaskAssoc();
        static::queueAnalysys();
        self::$api->getJson('editor/task/'.self::$api->getTask()->id.'/import');
        self::$api->checkTaskStateLoop();
    }

    /**
     * Add the translation memory resource. OpenTM2 in our case
     * @param string $fileName
     * @param string $name
     */
    private static function addTm(string $fileName, string $name){
        $params = [
            'resourceId' => 'editor_Services_OpenTM2_1',
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerIds' => [ self::$api->getCustomer()->id ],
            'customerUseAsDefaultIds' => [],
            'customerWriteAsDefaultIds' => [],
            'serviceType' => 'editor_Services_OpenTM2',
            'serviceName'=> 'OpenTM2',
            'name' => $name
        ];
        //create the resource 1 and import the file
        self::$api->addResource($params, $fileName,true);
    }

    /**
     * Add the term collection resource
     * @param string $fileName
     * @param string $name
     */
    private static function addTermCollection(string $fileName, string $name) {
        $customer = self::$api->getCustomer();
        $params = [
            'name' => $name,
            'resourceId' =>'editor_Services_TermCollection',
            'serviceType' =>'editor_Services_TermCollection',
            'customerIds' => [$customer->id],
            'customerUseAsDefaultIds' => [],
            'customerWriteAsDefaultIds' => [],
            'serviceName' =>'TermCollection',
            'mergeTerms' => false
        ];
        self::$api->addResource($params, $fileName);
    }

    /**
     * Return the languageresource render name with the prefix
     * @param string $name
     * @return string
     */
    private static function getLrRenderName(string $name){
        return self::$prefix.$name;
    }

    /**
     * Create the task. The task will not be imported directly autoStartImport is 0!
     */
    private static function createTask(){
        $task =[
            'taskName' => 'API Testing::'.__CLASS__, //no date in file name possible here!
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerId'=>self::$api->getCustomer()->id,
            'edit100PercentMatch' => true,
            'wordCount' => 1270,
            'autoStartImport'=>0
        ];
        self::assertLogin('testmanager');
        $zipfile = self::$api->zipTestFiles('testfiles/','XLF-test.zip');
        self::$api->addImportFile($zipfile);
        self::$api->import($task,false,false);
    }

    /**
     * Queue the match anlysis worker
     */
    private static function queueAnalysys(){
         $params = [
            'internalFuzzy' => 1,
            'pretranslateMatchrate' => 100,
            'pretranslateTmAndTerm' => 1,
            'pretranslateMt' => 0,
            'isTaskImport' => 0
        ];
        self::$api->putJson('editor/task/'.self::$api->getTask()->id.'/pretranslation/operation', $params, null, false);
    }

    /***
     * @return void
     */
    public function testExportXmlResultsWord(): void
    {
        $this->exportXmlResults();
    }

    /***
     * @return void
     */
    public function testExportXmlResultsCharacter(): void
    {
        $this->exportXmlResults(true);
    }

    /***
     *
     * @return void
     */
    public function testWordBasedResults(): void
    {
        $this->validateResults();
    }

    /***
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
    private function exportXmlResults(bool $characterBased = false): void
    {

        $unitType = $characterBased ? 'character' : 'word';

        $taskGuid = self::$api->getTask()->taskGuid;
        $response = self::$api->get('editor/plugins_matchanalysis_matchanalysis/export', [
            'taskGuid' => $taskGuid,
            'type' => 'exportXml'
        ]);

        self::assertTrue($response->getStatus() === 200, 'export XML HTTP Status is not 200');
        $actual = self::$api->formatXml($response->getBody());

        //sanitize task information
        $actual = str_replace('number="'.$taskGuid.'"/>', 'number="UNTESTABLECONTENT"/>', $actual);

        //sanitize analysis information
        $actual = preg_replace(
            '/<taskInfo taskId="([^"]*)" runAt="([^"]*)" runTime="([^"]*)">/',
            '<taskInfo taskId="UNTESTABLECONTENT" runAt="UNTESTABLECONTENT" runTime="UNTESTABLECONTENT">',
            $actual);

        self::$api->isCapturing() && file_put_contents(self::$api->getFile('exportResults-'.$unitType.'.xml', null, false), $actual);
        $expected = self::$api->getFileContent('exportResults-'.$unitType.'.xml');

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
    private function validateResults(bool $characterBased = false): void
    {
        $unitType = $characterBased ? 'character' : 'word';

        $jsonFileName = 'analysis-'.$unitType.'.json';
        // fetch task data
        $analysis = self::$api->getJson('editor/plugins_matchanalysis_matchanalysis', [ 'taskGuid'=> self::$api->getTask()->taskGuid, 'unitType' => $unitType ], $jsonFileName);
        $this->assertNotEmpty($analysis,'No results found for the '.$unitType.'-based task-specific matchanalysis.');
        //check for differences between the expected and the actual content
        $expectedAnalysis = self::$api->getFileContent($jsonFileName);
        $this->assertEquals($this->filterTaskAnalysis($expectedAnalysis), $this->filterTaskAnalysis($analysis), 'The expected file and the data does not match for the '.$unitType.'-based task-specific matchanalysis.');

        //now test all results and matches
        $jsonFileName = 'allanalysis-'.$unitType.'.json';
        $analysis = self::$api->getJson('editor/plugins_matchanalysis_matchanalysis', [ 'taskGuid' => self::$api->getTask()->taskGuid, 'notGrouped' => self::$api->getTask()->taskGuid ], $jsonFileName);
        $this->assertNotEmpty($analysis,'No results found for the '.$unitType.'-based not-grouped matchanalysis.');
        //check for differences between the expected and the actual content
        $expectedAnalysis = self::$api->getFileContent($jsonFileName);
        //check for differences between the expected and the actual content
        $this->assertEquals($this->filterUngroupedAnalysis($expectedAnalysis), $this->filterUngroupedAnalysis($analysis), "The expected file and the data does not match for the '.$unitType.'-based not-grouped matchanalysis..");
    }

    private function filterTaskAnalysis(array $data) : array {
        // remove the created timestamp since is not relevant for the test
        foreach ($data as &$a){
            unset($a->created);
        }
        usort($data, function($a, $b) { return strcmp($a->resourceName, $b->resourceName); });
        return $data;
    }

    private function filterUngroupedAnalysis(array $data){
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

    /**
     * Clean up the resources and the task
     */
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        self::$api->deleteTask($task->id, 'testmanager');
        //remove the created resources
        self::$api->removeResources();
    }
}