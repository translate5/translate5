<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Test word count of a task when edit100PercentMatch enabled/disabled.
 * This will also test the analysis results when the task edit100PercentMatch is enabled/disabled
 */
class Translate2428Test extends \ZfExtended_Test_ApiTestcase {
    
    protected static $customerTest;
    protected static $sourceLangRfc = 'de';
    protected static $targetLangRfc = 'en';
    
    public static function setUpBeforeClass(): void {
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $appState = self::assertAppState();
        self::assertContains('editor_Plugins_Okapi_Init', $appState->pluginsLoaded, 'Plugin Okapi must be activated for this test case!');
        self::assertContains('editor_Plugins_MatchAnalysis_Init', $appState->pluginsLoaded, 'Plugin MatchAnalysis must be activated for this test case!');
        self::assertContains('editor_Plugins_ZDemoMT_Init', $appState->pluginsLoaded, 'Plugin ZDemoMT must be activated for this test case!');
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
    }
    
    /***
     * Create test customer, task, moses language resource.
     * Associate the resources to task, queue and run matchanalysis and pretranslation.
     * Waith for the task import to finish.
     */
    public function testSetupCustomerAndResources() {
        self::$customerTest = self::$api->postJson('editor/customer/',[
            'name'=>'API Testing::ResourcesLogCustomer',
            'number'=>uniqid('API Testing::ResourcesLogCustomer'),
        ]);
        
        $this->createTask();
        $this->addZDemoMTMt("one");
        $this->addZDemoMTMt("two");
        self::$api->addTaskAssoc();
        $this->queueAnalysys();
        self::$api->getJson('editor/task/'.self::$api->getTask()->id.'/import');
        self::$api->checkTaskStateLoop();
    }
    
    /**
     * Test the word count and analysis with and without 100% match enabled/disabled
     */
    public function testTaskWorkCount() {
        $wordCount = self::$api->getTask()->wordCount;
        $this->assertEquals(66, $wordCount, 'Task word count is not as expected!');
        
        $this->checkAnalysis('edit100PercentMatch_false.txt');
        
        $task = self::$api->getTask();
        //enable 100% matches for edition. This should calculate also the word count
        self::$api->putJson('editor/task/'.$task->id, ['edit100PercentMatch' => 1]);
        
        self::$api->reloadTask();
        $wordCount = self::$api->getTask()->wordCount;
        
        $this->assertEquals(72, $wordCount, 'Task word count is not as expected!');
        
        $this->checkAnalysis('edit100PercentMatch_true.txt');
    }
    
    
    /***
     * Check and validate the analysis results. $validationFileName is file name constant (edit100PercentMatch_false and edit100PercentMatch_true)
     * which will switch the expected result to compare against.
     * @param string $validationFileName
     */
    protected function checkAnalysis(string $validationFileName){
        $analysis=self::$api->getJson('editor/plugins_matchanalysis_matchanalysis',[
            'taskGuid'=>self::$api->getTask()->taskGuid
        ]);
        
        $this->assertNotEmpty($analysis,'No results found for the matchanalysis.');
        //remove the created timestamp since is not relevant for the test
        foreach ($analysis as &$a){
            unset($a->created);
        }
        //this is to recreate the file from the api response
        //file_put_contents(self::$api->getFile($validationFileName, null, false), json_encode($analysis, JSON_PRETTY_PRINT));
        $expected=self::$api->getFileContent($validationFileName);
        $actual=json_encode($analysis, JSON_PRETTY_PRINT);
        //check for differences between the expected and the actual content
        $this->assertEquals($expected, $actual, "The expected analysis and the result file does not match.");
    }
    
    /***
     * Create the task. The task will not be imported directly autoStartImport is 0!
     */
    protected function createTask(){
        $task =[
            'taskName' => 'API Testing::'.__CLASS__, //no date in file name possible here!
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerId'=>self::$customerTest->id,
            'autoStartImport'=>0,
            'wordCount' => 0,//just to overwrite the default value set by the ApiHelper
            'edit100PercentMatch' => 0
        ];
        self::assertLogin('testmanager');
        
        $zipfile = self::$api->zipTestFiles('testfiles/','XLF-test.zip');
        self::$api->addImportFile($zipfile);
        
        self::$api->import($task,false,false);
        error_log('Task created. '.self::$api->getTask()->taskName);
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
        $params['pretranslateMt']= 1;
        $params['isTaskImport']= 0;
        self::$api->putJson('editor/task/'.self::$api->getTask()->id.'/pretranslation/operation', $params, null, false);
        error_log("Queue pretranslation and analysis.");
    }

    /***
     * Add task to languageresource assoc
     */
    protected function addTaskAssoc(){
        self::$api->addTaskAssoc();
    }
    
    /***
     * Create dummy mt resource.
     */
    protected function addZDemoMTMt(string $sufix){
        $params=[
            'resourceId'=>'ZDemoMT',
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerIds' => [self::$customerTest->id],
            'customerUseAsDefaultIds' => [],
            'customerWriteAsDefaultIds' => [],
            'serviceType' => 'editor_Plugins_ZDemoMT',
            'serviceName'=> 'ZDemoMT',
            'name' => 'API Testing::ZDemoMT_'.__CLASS__.'_'.$sufix
        ];
        
        self::$api->addResource($params);
    }
    
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        self::$api->deleteTask($task->id, 'testmanager');
        //remove the created resources
        self::$api->removeResources();
        //remove the temp customer
        self::$api->delete('editor/customer/'.self::$customerTest->id);
    }
}
