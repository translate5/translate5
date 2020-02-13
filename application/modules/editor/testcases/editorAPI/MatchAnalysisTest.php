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
 */
class MatchAnalysisTest extends \ZfExtended_Test_ApiTestcase {
    
    protected static $collectionMap=[];
    
    protected static $sourceLangRfc='de';
    protected static $targetLangRfc='en';
    
    /**
     */
    public static function setUpBeforeClass(): void {
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $appState = self::assertAppState();
        self::assertContains('editor_Plugins_Okapi_Init', $appState->pluginsLoaded, 'Plugin Okapi may not be activated for this test case!');
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertCustomer();//assert the test customer
        $task =[
            'taskName' => 'API Testing::'.__CLASS__, //no date in file name possible here!
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerId'=>self::$api->getCustomer()->id
        ];
        
        self::assertLogin('testmanager');
        self::$api->addImportFile(self::$api->getFile('test-analyse.html'));
        self::$api->import($task);
    }
    
    /***
     * Import the required language resources for the analysis
     */
    public function testImportLanguageResources(){
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
        $params['name']='API Testing::'.__CLASS__.'_resource1';
        $this->api()->addFile('tmUpload', $this->api()->getFile('resource1.tmx'), "application/xml");
        $resource = $this->api()->requestJson('editor/languageresourceinstance', 'POST',$params);
        $this->assertTrue(is_object($resource), 'Unable to create the language resource:'.$params['name']);
        $this->assertEquals($params['name'], $resource->name);
        self::$collectionMap[$params['name']]=$resource->id;
        
        //create the resource 2 and import the file
        $params['name']='API Testing::'.__CLASS__.'_resource2';
        $this->api()->addFile('tmUpload', $this->api()->getFile('resource2.tmx'), "application/xml");
        $resource = $this->api()->requestJson('editor/languageresourceinstance', 'POST',$params);
        $this->assertTrue(is_object($resource), 'Unable to create the language resource:'.$params['name']);
        $this->assertEquals($params['name'], $resource->name);
        self::$collectionMap[$params['name']]=$resource->id;
        
        //create the resource 3 and import the file
        $params['name']='API Testing::'.__CLASS__.'_resource3';
        $params['resourceId']='editor_Services_TermCollection';
        $params['serviceType']='editor_Services_TermCollection';
        $params['serviceName']='TermCollection';
        $params['mergeTerms']=false;
        unset($params['sourceLang']);
        unset($params['targetLang']);
        //create and import the term collection languageresource
        $this->api()->addFile('tmUpload', $this->api()->getFile('collection.tbx'), "application/xml");
        $resource = $this->api()->requestJson('editor/languageresourceinstance', 'POST',$params);
        $this->assertTrue(is_object($resource), 'Unable to create the language resource:'.$params['name']);
        $this->assertEquals($params['name'], $resource->name);
        
        //check the response
        $response=$this->api()->requestJson('editor/termcollection/export', 'POST',['collectionId' =>$resource->id]);
        $this->assertTrue(is_object($response),"Unable to export the terms by term collection");
        $this->assertNotEmpty($response->filedata,"The exported tbx file by collection is empty");
        
        self::$collectionMap[$params['name']]=$resource->id;
        
        $task=$this->api()->getTask();
        
        // associate languageresource to task
        $params = [];
        $params['languageResourceId'] = self::$collectionMap['API Testing::'.__CLASS__.'_resource1'];
        $params['taskGuid'] = $task->taskGuid;
        $params['segmentsUpdateable'] = 0;
        $this->api()->requestJson('editor/languageresourcetaskassoc', 'POST', $params);
        
        $params['languageResourceId'] = self::$collectionMap['API Testing::'.__CLASS__.'_resource2'];
        $this->api()->requestJson('editor/languageresourcetaskassoc', 'POST', $params);
        
        $params['languageResourceId'] = self::$collectionMap['API Testing::'.__CLASS__.'_resource3'];
        $this->api()->requestJson('editor/languageresourcetaskassoc', 'POST', $params);
    }
    
    public function testStartAnalysis() {
        $task=$this->api()->getTask();
        
        //run the analysis
        $params=[];
        $params['internalFuzzy']= 1;
        $params['pretranslateMatchrate']= 100;
        $params['pretranslateTmAndTerm']= 1;
        $params['pretranslateMt']= 0;
        $params['termtaggerSegment']= 0;
        $params['isTaskImport']= 0;
        
        $this->api()->requestJson('editor/task/'.$task->id.'/pretranslation/operation', 'PUT', $params,$params);
        
        $task=$this->api()->reloadTask();
        error_log('Task status check: '.$task->state);
        $counter=0;
        while ($task->state!='open'){
            if($task->state=='error'){
                break;
            }
            //break after 20 trys
            if($counter==20){
                break;
            }
            sleep(5);
            $task=$this->api()->reloadTask();
            error_log('Task status check: '.$task->state);
            $counter++;
        }
        $this->assertEquals('open',$task->state,'Pretranslation stopped. Task has state '.$task->state);
        
        $analysis=$this->api()->requestJson('editor/plugins_matchanalysis_matchanalysis', 'GET',[
            'taskGuid'=>$task->taskGuid
        ]);
        $this->assertNotEmpty($analysis,'No results found for the matchanalysis.');
        //remove the created timestamp since is not relevant for the test
        foreach ($analysis as &$a){
            unset($a->created);
        }
        
        //this is to recreate the file from the api response
        file_put_contents($this->api()->getFile('analysis.txt', null, false), json_encode($analysis));
        
        $expected=$this->api()->getFileContent('analysis.txt');
        $actual=json_encode($analysis);
        
        //check for differences between the expected and the actual content
        $this->assertEquals($expected, $actual, "The expected file an the result file does not match.");
    }
    
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
        
        self::$api->requestJson('editor/termcollection/'.self::$collectionMap['API Testing::'.__CLASS__.'_resource1'],'DELETE');
        self::$api->requestJson('editor/termcollection/'.self::$collectionMap['API Testing::'.__CLASS__.'_resource2'],'DELETE');
        self::$api->requestJson('editor/termcollection/'.self::$collectionMap['API Testing::'.__CLASS__.'_resource3'],'DELETE');
    }
}