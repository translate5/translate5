<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
    
    protected static $collectionMap=[];
    
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
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertCustomer();//assert the test customer
    }
    
    /***
     * Import all required resources and task before the validation
     */
    public function testSetupData(){
        $this->addTm('resource1.tmx',$this->getLrRenderName('resource1'));
        $this->addTm('resource2.tmx',$this->getLrRenderName('resource2'));
        $this->addTermCollection('collection.tbx', $this->getLrRenderName('resource3'));
        $this->createTask();
        $this->addTaskAssoc($this->getLrRenderName('resource1'));
        $this->addTaskAssoc($this->getLrRenderName('resource2'));
        $this->addTaskAssoc($this->getLrRenderName('resource3'));
        $this->queueAnalysys();
        $this->startImport();
        $this->checkTaskState();
    }
    
    /***
     * Validate the analysis results.
     * 1. the first validation will validate the grouped results for the analysis
     * 2. the second validation will validate the all existing results for the analyis
     */
    public function testValidateResults(){
        $analysis=$this->api()->requestJson('editor/plugins_matchanalysis_matchanalysis', 'GET',[
            'taskGuid'=>$this->api()->getTask()->taskGuid
        ]);
        
        $this->assertNotEmpty($analysis,'No results found for the matchanalysis.');
        //remove the created timestamp since is not relevant for the test
        foreach ($analysis as &$a){
            unset($a->created);
        }
        
        //this is to recreate the file from the api response
        //file_put_contents($this->api()->getFile('analysis.txt', null, false), json_encode($analysis));
        $expected=$this->api()->getFileContent('analysis.txt');
        $actual=json_encode($analysis);
        //check for differences between the expected and the actual content
        $this->assertEquals($expected, $actual, "The expected file an the result file does not match.");
        
        
        //not test all results and matches
        $analysis=$this->api()->requestJson('editor/plugins_matchanalysis_matchanalysis', 'GET',[
            'taskGuid'=>$this->api()->getTask()->taskGuid,
            'notGrouped'=>$this->api()->getTask()->taskGuid
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
        
        //file_put_contents($this->api()->getFile('allanalysis.txt', null, false), json_encode($analysis));
        $expected=$this->api()->getFileContent('allanalysis.txt');
        $actual=json_encode($analysis);
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
            'autoStartImport'=>0
        ];
        self::assertLogin('testmanager');
        self::$api->addImportFile(self::$api->getFile('test-analyse.html'));
        self::$api->import($task,false,false);
        error_log('Task created. '.$this->api()->getTask()->taskName);
    }
    
    /***
     * Add the translation memory resource. OpenTM2 in our case
     * @param string $fileName
     * @param string $name
     */
    protected function addTm(string $fileName,string $name){
        $customer = self::api()->getCustomer();
        $customerParamObj = new stdClass();
        $customerParamObj->customerId = $customer->id;
        $customerParamObj->useAsDefault = 0;
        $customerParamArray = [];
        $customerParamArray[] = $customerParamObj;
        
        $params=[
            'resourceId'=>'editor_Services_OpenTM2_1',
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'resourcesCustomers' => $this->api()->getCustomer()->id,
            'resourcesCustomersHidden' =>json_encode($customerParamArray),
            'serviceType' => 'editor_Services_OpenTM2',
            'serviceName'=> 'OpenTM2'
        ];
        
        //create the resource 1 and import the file
        $params['name']=$name;
        $this->api()->addFile('tmUpload', $this->api()->getFile($fileName), "application/xml");
        $resource = $this->api()->requestJson('editor/languageresourceinstance', 'POST',$params);
        $this->assertTrue(is_object($resource), 'Unable to create the language resource:'.$params['name']);
        $this->assertEquals($params['name'], $resource->name);
        self::$collectionMap[$params['name']]=$resource->id;
        error_log("Language resources created. ".$resource->name);
        
        $resp = $this->api()->requestJson('editor/languageresourceinstance/'.$resource->id, 'GET',[]);
        
        error_log('Languageresources status check:'.$resp->status);
        $counter=0;
        $limitCheck=20;
        while ($resp->status!='available'){
            if($resp->status=='error'){
                break;
            }
            //break after 20 trys
            if($counter==$limitCheck){
                break;
            }
            sleep(5);
            $resp = $this->api()->requestJson('editor/languageresourceinstance/'.$resp->id, 'GET',[]);
            error_log('Languageresources status check '.$counter.'/'.$limitCheck.' state: '.$resp->status);
            $counter++;
        }
        
        $this->assertEquals('available',$resp->status,'Tm import stoped. Tm state is:'.$resp->status);
    }
    
    /***
     * Add the term collection resource
     * @param string $fileName
     * @param string $name
     */
    protected function addTermCollection(string $fileName,string $name) {
        $customer = self::api()->getCustomer();
        $customerParamObj = new stdClass();
        $customerParamObj->customerId = $customer->id;
        $customerParamObj->useAsDefault = 0;
        $customerParamArray = [];
        $customerParamArray[] = $customerParamObj;
        
        $params=[];
        //create the resource 3 and import the file
        $params['name']=$name;
        $params['resourceId']='editor_Services_TermCollection';
        $params['serviceType']='editor_Services_TermCollection';
        $params['resourcesCustomers']=$customer->id;
        $params['resourcesCustomersHidden']=json_encode($customerParamArray);
        $params['serviceName']='TermCollection';
        $params['mergeTerms']=false;
        //create and import the term collection languageresource
        $this->api()->addFile('tmUpload', $this->api()->getFile($fileName), "application/xml");
        $resource = $this->api()->requestJson('editor/languageresourceinstance', 'POST',$params);
        $this->assertTrue(is_object($resource), 'Unable to create the language resource:'.$name);
        $this->assertEquals($name, $resource->name);
        
        //check the response
        $response=$this->api()->requestJson('editor/termcollection/export', 'POST',['collectionId' =>$resource->id]);
        $this->assertTrue(is_object($response),"Unable to export the terms by term collection");
        $this->assertNotEmpty($response->filedata,"The exported tbx file by collection is empty");
        
        self::$collectionMap[$name]=$resource->id;
        error_log("Termcollection created. ".$resource->name);
    }
    
    /***
     * Add task to languageresource assoc
     * @param string $name
     */
    protected function addTaskAssoc(string $name){
        // associate languageresource to task
        $this->api()->requestJson('editor/languageresourcetaskassoc', 'POST',[
            'languageResourceId'=>self::$collectionMap[$name],
            'taskGuid'=>$this->api()->getTask()->taskGuid,
            'segmentsUpdateable'=>0
        ]);
        error_log('Languageresources assoc to task. '.$name.' -> '.$this->api()->getTask()->taskGuid);
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
        $params['termtaggerSegment']= 0;
        $params['isTaskImport']= 0;
        $this->api()->requestJson('editor/task/'.$this->api()->getTask()->id.'/pretranslation/operation', 'PUT', $params,$params);
        error_log("Queue pretranslation and analysis.");
    }
    
    /***
     * Start the import process
     */
    protected function startImport(){
        $this->api()->requestJson('editor/task/'.$this->api()->getTask()->id.'/import', 'GET');
        error_log('Import workers started.');
    }
    
    /***
     * Check the task state
     */
    protected function checkTaskState(){
        $this->api()->reloadTask();
        error_log('Task status:'.$this->api()->getTask()->state);
        $counter=0;
        $limitCheck=20;
        while ($this->api()->getTask()->state!='open'){
            if($this->api()->getTask()->state=='error'){
                break;
            }
            //break after 20 trys
            if($counter==$limitCheck){
                break;
            }
            sleep(5);
            $this->api()->reloadTask();
            error_log('Task state check '.$counter.'/'.$limitCheck.' state: '.$this->api()->getTask()->state);
            $counter++;
        }
        
        $this->assertEquals('open',$this->api()->getTask()->state,'Pretranslation stopped. Task has state '.$this->api()->getTask()->state);
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
        
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
        self::$api->requestJson('editor/languageresourceinstance/'.self::$collectionMap[self::getLrRenderName('resource1')],'DELETE');
        self::$api->requestJson('editor/languageresourceinstance/'.self::$collectionMap[self::getLrRenderName('resource2')],'DELETE');
        self::$api->requestJson('editor/termcollection/'.self::$collectionMap[self::getLrRenderName('resource3')],'DELETE');
    }
}