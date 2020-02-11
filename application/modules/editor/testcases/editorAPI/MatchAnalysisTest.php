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
        
        $params['name']='API Testing::'.__CLASS__.'_resource1';
        $this->api()->addFile('tmUpload', $this->api()->getFile('resource1.tmx'), "application/xml");
        $termCollection = $this->api()->requestJson('editor/languageresourceinstance', 'POST',$params);
        $this->assertTrue(is_object($termCollection), 'Unable to create the language resource:'.$params['name']);
        $this->assertEquals($params['name'], $termCollection->name);
        self::$collectionMap[$params['name']]=$termCollection->id;
        
        
        $params['name']='API Testing::'.__CLASS__.'_resource2';
        $this->api()->addFile('tmUpload', $this->api()->getFile('resource2.tmx'), "application/xml");
        $termCollection = $this->api()->requestJson('editor/languageresourceinstance', 'POST',$params);
        $this->assertTrue(is_object($termCollection), 'Unable to create the language resource:'.$params['name']);
        $this->assertEquals($params['name'], $termCollection->name);
        self::$collectionMap[$params['name']]=$termCollection->id;
    }
    
    public function testStartAnalysis() {
        $task=$this->api()->getTask();
        
        // associate languageresource to task
        $params = [];
        $params['languageResourceId'] = self::$collectionMap['API Testing::'.__CLASS__.'_resource1'];
        $params['taskGuid'] = $task->taskGuid;
        $params['segmentsUpdateable'] = 1;
        $this->api()->requestJson('editor/languageresourcetaskassoc', 'POST', $params);
        
        $params['languageResourceId'] = self::$collectionMap['API Testing::'.__CLASS__.'_resource2'];
        $this->api()->requestJson('editor/languageresourcetaskassoc', 'POST', $params);

        //run the analysis
        $params=[];
        $params['internalFuzzy']= 1;
        $params['pretranslateMatchrate']= 100;
        $params['pretranslateTmAndTerm']= 1;
        $params['pretranslateMt']= 0;
        $params['termtaggerSegment']= 0;
        $params['isTaskImport']= 0;
        
        $this->api()->requestJson('editor/task/'.$task->id.'/pretranslation/operation', 'PUT', $params);
        
    }
    
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
        
        self::$api->requestJson('editor/termcollection/'.self::$collectionMap['API Testing::'.__CLASS__.'_resource1'],'DELETE');
        self::$api->requestJson('editor/termcollection/'.self::$collectionMap['API Testing::'.__CLASS__.'_resource2'],'DELETE');
    }
}