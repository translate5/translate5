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
class MatchAnalysisTest extends \editor_Test_ApiTest {
    
    protected static $sourceLangRfc = 'de';
    protected static $targetLangRfc = 'en';
    
    protected static $prefix = 'MATEST';

    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init',
        'editor_Plugins_MatchAnalysis_Init',
        'editor_Plugins_InstantTranslate_Init'
    ];
    
    /**
     */
    public static function beforeTests(): void {

        self::assertAppState();

        self::assertNeededUsers(); //last authed user is testmanager
        self::assertCustomer();//assert the test customer

        // Import all required resources and task before running the tests
        static::addTm('resource1.tmx', static::getLrRenderName('resource1'));
        static::addTm('resource2.tmx', static::getLrRenderName('resource2'));
        static::addTermCollection('collection.tbx', static::getLrRenderName('resource3'));
        static::createTask();
        static::api()->addTaskAssoc();
        static::queueAnalysys();
        static::api()->getJson('editor/task/'.static::api()->getTask()->id.'/import');
        static::api()->checkTaskStateLoop();
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
            'customerIds' => [ static::api()->getCustomer()->id ],
            'customerUseAsDefaultIds' => [],
            'customerWriteAsDefaultIds' => [],
            'serviceType' => 'editor_Services_OpenTM2',
            'serviceName'=> 'OpenTM2',
            'name' => $name
        ];
        //create the resource 1 and import the file
        static::api()->addResource($params, $fileName,true);
    }

    /**
     * Add the term collection resource
     * @param string $fileName
     * @param string $name
     */
    private static function addTermCollection(string $fileName, string $name) {
        $customer = static::api()->getCustomer();
        $params = [
            'name' => $name,
            'resourceId' =>'editor_Services_TermCollection',
            'serviceType' =>'editor_Services_TermCollection',
            'customerIds' => [ $customer->id ],
            'customerUseAsDefaultIds' => [],
            'customerWriteAsDefaultIds' => [],
            'serviceName' => 'TermCollection',
            'mergeTerms' => false
        ];
        static::api()->addResource($params, $fileName);
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
            'customerId' => static::api()->getCustomer()->id,
            'edit100PercentMatch' => true,
            'wordCount' => 1270,
            'autoStartImport' => 0
        ];
        self::assertLogin('testmanager');
        $zipfile = static::api()->zipTestFiles('testfiles/','XLF-test.zip');
        static::api()->addImportFile($zipfile);
        static::api()->import($task, false, false);
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
        static::api()->putJson('editor/task/'.static::api()->getTask()->id.'/pretranslation/operation', $params, null, false);
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

    /***
     *
     * @return void
     */
    public function testWordBasedResults(): void
    {
        $this->validateResults(false);
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

    /**
     * Clean up the resources and the task
     */
    public static function afterTests(): void {
        $task = static::api()->getTask();
        static::api()->deleteTask($task->id, 'testmanager');
        //remove the created resources
        static::api()->removeResources();
    }
}