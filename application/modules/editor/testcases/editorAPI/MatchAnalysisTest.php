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
    
    protected static $sourceLangRfc='de';
    protected static $targetLangRfc='en';
    
    protected static $prefix='MATEST';
    
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
    }
    
    /***
     * Import all required resources and task before the validation
     */
    public function testSetupData(){
        //use the following lines to rerun the validation tests on a specific task
        //$this->api()->reloadTask(9066);
        //return;
        $this->addTm('resource1.tmx',$this->getLrRenderName('resource1'));
        $this->addTm('resource2.tmx',$this->getLrRenderName('resource2'));
        $this->addTermCollection('collection.tbx', $this->getLrRenderName('resource3'));
        $this->createTask();
        $this->addTaskAssoc();
        $this->queueAnalysys();
        $this->startImport();
        $this->checkTaskState();
    }

    /***
     * @depends testSetupData
     * @return void
     */
    public function testExportXmlResultsWord(): void
    {
        $this->exportXmlResults();
    }

    /***
     * @depends testSetupData
     * @return void
     */
    public function testExportXmlResultsCharacter(): void
    {
        $this->exportXmlResults(true);
    }

    /***
     * Test the xml analysis summary
     */
    public function exportXmlResults(bool $characterBased = false): void
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
        
        self::$api->isCapturing() && file_put_contents($this->api()->getFile('exportResults-'.$unitType.'.xml', null, false), $actual);
        $expected = self::$api->getFileContent('exportResults-'.$unitType.'.xml');
        
        //check for differences between the expected and the actual content
        self::assertEquals($expected, $actual, "The expected file(exportResults) an the result file does not match.");
    }

    /***
     *
     * @depends testSetupData
     * @return void
     */
    public function testWordBasedResults(): void
    {
        $this->validateResults();
    }

    /***
     *
     * @depends testSetupData
     * @return void
     */
    public function testCharacterBasedResults(): void
    {
        $this->validateResults(true);
    }

    /***
     * Validate the analysis results.
     * 1. the first validation will validate the grouped results for the analysis
     * 2. the second validation will validate the all existing results for the analysis
     *
     * @param bool $characterBased
     * @return void
     */
    protected function validateResults(bool $characterBased = false): void
    {

        $unitType = $characterBased ? 'character' : 'word';

        $analysis=$this->api()->getJson('editor/plugins_matchanalysis_matchanalysis',[
            'taskGuid'=> $this->api()->getTask()->taskGuid,
            'unitType' => $unitType
        ]);
        
        $this->assertNotEmpty($analysis,'No results found for the matchanalysis.');
        //remove the created timestamp since is not relevant for the test
        foreach ($analysis as &$a){
            unset($a->created);
        }
        
        //this is to recreate the file from the api response
        $this->api()->isCapturing() && file_put_contents($this->api()->getFile('analysis-'.$unitType.'.txt', null, false), json_encode($analysis, JSON_PRETTY_PRINT));
        $expected=$this->api()->getFileContent('analysis-'.$unitType.'.txt');
        $actual=json_encode($analysis, JSON_PRETTY_PRINT);
        //check for differences between the expected and the actual content
        $this->assertEquals($expected, $actual, "The expected file an the result file does not match.");
        
        
        //not test all results and matches
        $analysis = $this->api()->getJson('editor/plugins_matchanalysis_matchanalysis',[
            'taskGuid' => $this->api()->getTask()->taskGuid,
            'notGrouped' => $this->api()->getTask()->taskGuid
        ]);
        $this->assertNotEmpty($analysis,'No results found for the matchanalysis.');
        //remove some of the unneeded columns
        foreach ($analysis as &$a){
            unset($a->id);
            unset($a->taskGuid);
            unset($a->analysisId);
            unset($a->segmentId);
            unset($a->languageResourceid);
        }

        $this->api()->isCapturing() && file_put_contents($this->api()->getFile('allanalysis-'.$unitType.'.txt', null, false), json_encode($analysis, JSON_PRETTY_PRINT));
        $expected=$this->api()->getFileContent('allanalysis-'.$unitType.'.txt');
        $actual=json_encode($analysis, JSON_PRETTY_PRINT);
        //check for differences between the expected and the actual content
        $this->assertEquals($expected, $actual, "The expected file(allanalysis) an the result file does not match.");
        
    }
    
    /***
     * Create the task. The task will not be imported directly autoStartImport is 0!
     */
    protected function createTask(){
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
        error_log('Task created. '.$this->api()->getTask()->taskName);
    }
    
    /***
     * Add the translation memory resource. OpenTM2 in our case
     * @param string $fileName
     * @param string $name
     */
    protected function addTm(string $fileName,string $name){
        $params=[
            'resourceId'=>'editor_Services_OpenTM2_1',
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerIds' => [$this->api()->getCustomer()->id],
            'customerUseAsDefaultIds' => [],
            'customerWriteAsDefaultIds' => [],
            'serviceType' => 'editor_Services_OpenTM2',
            'serviceName'=> 'OpenTM2',
            'name' => $name
        ];
        //create the resource 1 and import the file
        self::$api->addResource($params,$fileName,true);
    }
    
    /***
     * Add the term collection resource
     * @param string $fileName
     * @param string $name
     */
    protected function addTermCollection(string $fileName,string $name) {
        $customer = self::api()->getCustomer();
        
        $params=[];
        //create the resource 3 and import the file
        $params['name']=$name;
        $params['resourceId']='editor_Services_TermCollection';
        $params['serviceType']='editor_Services_TermCollection';
        $params['customerIds'] = [$customer->id];
        $params['customerUseAsDefaultIds'] = [];
        $params['customerWriteAsDefaultIds'] = [];
        $params['serviceName']='TermCollection';
        $params['mergeTerms']=false;
        
        self::$api->addResource($params,$fileName);
    }
    
    /***
     * Associate all resources to the task
     */
    protected function addTaskAssoc(){
        self::$api->addTaskAssoc();
    }
    
    /***
     * Queue the match anlysis worker
     */
    protected function queueAnalysys(){
        //run the analysis
        $params=[];
        $params['internalFuzzy']= 1;
        $params['pretranslateMatchrate']= 100;
        $params['pretranslateTmAndTerm']= 1;
        $params['pretranslateMt']= 0;
        $params['isTaskImport']= 0;
        $this->api()->putJson('editor/task/'.$this->api()->getTask()->id.'/pretranslation/operation', $params, null, false);
        error_log("Queue pretranslation and analysis.");
    }
    
    /***
     * Start the import process
     */
    protected function startImport(){
        $this->api()->getJson('editor/task/'.$this->api()->getTask()->id.'/import');
        error_log('Import workers started.');
    }
    
    /***
     * Check the task state
     */
    protected function checkTaskState(){
        self::$api->checkTaskStateLoop();
    }
    
    /***
     * Return the languageresource render name with the prefix
     * @param string $name
     * @return string
     */
    protected static function getLrRenderName(string $name){
        return self::$prefix.$name;
    }
    
    /***
     * Cleand up the resources and the task
     */
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testmanager');

        self::$api->delete('editor/task/'.$task->id);
        //remove the created resources
        self::$api->removeResources();
    }
}