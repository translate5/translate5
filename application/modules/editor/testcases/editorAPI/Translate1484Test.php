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
 * 
 */
class Translate1484Test extends \ZfExtended_Test_ApiTestcase {
    
    protected static $resources=[];
    protected static $customerTest;
    protected static $sourceLangRfc = 'de';
    protected static $targetLangRfc = 'en';
    
    
    public static function setUpBeforeClass(): void {
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $appState = self::assertAppState();
        self::assertContains('editor_Plugins_Okapi_Init', $appState->pluginsLoaded, 'Plugin Okapi must be activated for this test case!');
        self::assertContains('editor_Plugins_MatchAnalysis_Init', $appState->pluginsLoaded, 'Plugin MatchAnalysis must be activated for this test case!');
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
    }
    
    public function testSetupCustomerAndResources() {
        // 1. Create new customer and use it. With the new customer, the sum stuff will be removed 
        //    after the customer is removed.
        // 2. The log stuff can be removed with cron periodical (set the config to 0 days)
        self::$customerTest = self::$api->requestJson('editor/customer/', 'POST',[
            'name'=>'API Testing::ResourcesLogCustomer',
            'number'=>uniqid('API Testing::ResourcesLogCustomer'),
        ]);
        
        $this->createTask();
        $this->addMt('API Testing::'.__CLASS__);
        $this->addTaskAssoc();
        $this->queueAnalysys();
        $this->startImport();
        $this->checkTaskState();
    }

    
    public static function testExportResourcesLog() {
        $result = self::$api->requestJson('editor/customer/exportresource','GET',[
            'customerId' =>self::$customerTest->id,
            'dataOnly' => 1
        ]);
        
        $result = $this->filterLogResults($result);
        file_put_contents(self::$api->getFile('exportResults.txt', null, false), json_encode($result, JSON_PRETTY_PRINT));
        $expected=self::$api->getFileContent('exportResults.txt');
        $actual=json_encode($result, JSON_PRETTY_PRINT);
        //check for differences between the expected and the actual content
        self::assertEquals($expected, $actual, "The expected file(exportResults) an the result file does not match.");
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
            'autoStartImport'=>0
        ];
        self::assertLogin('testmanager');
        self::$api->addImportFile(self::$api->getFile('import-test-file.html'));
        self::$api->import($task,false,false);
        error_log('Task created. '.self::$api->getTask()->taskName);
    }
    
    /***
     */
    protected function addMt(string $name){
        $params=[
            'resourceId'=>'editor_Services_Moses_1',
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerIds' => [self::$customerTest->id],
            'customerUseAsDefaultIds' => [],
            'serviceType' => 'editor_Services_Moses',
            'serviceName'=> 'Moses',
            'name' => $name
        ];
        
        $response = self::$api->request('editor/languageresourceinstance', 'POST',$params);
        $resource = self::$api->decodeJsonResponse($response);
        $this->assertTrue(is_object($resource), 'Unable to create the language resource:'.$params['name']);
        $this->assertEquals($params['name'], $resource->name);
        self::$resources[]=$resource;
        error_log("Language resources created. ".$resource->name);
        
        $resp = self::$api->requestJson('editor/languageresourceinstance/'.$resource->id, 'GET',[]);
        $this->assertEquals('available',$resp->status,'Tm import stoped. Tm state is:'.$resp->status);
    }
    
    /***
     * Add task to languageresource assoc
     */
    protected function addTaskAssoc(){
        $taskGuid = self::$api->getTask()->taskGuid;
        foreach (self::$resources as $resource){
            // associate languageresource to task
            self::$api->requestJson('editor/languageresourcetaskassoc', 'POST',[
                'languageResourceId'=>$resource->id,
                'taskGuid'=>$taskGuid,
                'segmentsUpdateable'=>0
            ]);
            error_log('Languageresources assoc to task. '.$resource->name.' -> '.$taskGuid);
        }
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
        $params['termtaggerSegment']= 0;
        $params['isTaskImport']= 0;
        self::$api->requestJson('editor/task/'.self::$api->getTask()->id.'/pretranslation/operation', 'PUT', $params,$params);
        error_log("Queue pretranslation and analysis.");
    }
    
    /***
     * Check the task state
     */
    protected function checkTaskState(){
        self::$api->reloadTask();
        error_log('Task status:'.self::$api->getTask()->state);
        $counter=0;
        $limitCheck = 25;
        while (self::$api->getTask()->state!='open'){
            if(self::$api->getTask()->state=='error'){
                break;
            }
            //break after 20 trys
            if($counter==$limitCheck){
                break;
            }
            sleep(5);
            self::$api->reloadTask();
            error_log('Task state check '.$counter.'/'.$limitCheck.' state: '.self::$api->getTask()->state);
            $counter++;
        }
        
        $this->assertEquals('open',self::$api->getTask()->state,'Pretranslation stopped. Task has state '.self::$api->getTask()->state.' instead of open.');
    }
    
    /***
     * Start the import process
     */
    protected function startImport(){
        self::$api->requestJson('editor/task/'.self::$api->getTask()->id.'/import', 'GET');
        error_log('Import workers started.');
    }
    
    /***
     * TODO: 
     * Filter the not important results from the log result groups
     * @param array $result
     */
    protected function filterLogResults(array $result){
        throw new Exception();  
        $clean = array_map(
            function (array $group) {
                return array_map(
                    function($res){
                        //TODO: filter out those parametars, we do not need them in the final result
                        //customerId
                        //"sourceLang": "4",
                        //"targetLang": "5",
                        //yearAndMonth
                        //"timestamp": "2021-02-09 12:13:30",
                        //"customers": ",31,",
                    }, $group);
            }
        ,$result);
    }
    
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testmanager');
        
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
        foreach (self::$resources as $resource){
            self::$api->requestJson('editor/languageresourceinstance/'.$resource->id,'DELETE');
        }
        
        self::$api->requestJson('editor/customer/'.self::$customerTest->id, 'DELETE');
    }
}
