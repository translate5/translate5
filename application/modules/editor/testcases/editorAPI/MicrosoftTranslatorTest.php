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
 * Test microsoft translator api for dictonary,normal and segmentation search
 */
class MicrosoftTranslatorTest extends \ZfExtended_Test_ApiTestcase {
    
    protected static $languageResourceId;
    
    protected static string $sourceLangRfc = 'de';
    protected static string $targetLangRfc = 'en';
    protected static string $serviceName = 'Microsoft';
    protected static string $languageResourceName = 'API Testing::'.__CLASS__;
    
    /**
     */
    public static function setUpBeforeClass(): void {
        //check if this test needs to be skipped.
        if (!self::isMasterTest()) {
            self::markTestSkipped('Test runs only in master test to reduce usage/costs.');
            return;
        }
        
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $appState = self::assertAppState();
        self::assertContains('editor_Plugins_InstantTranslate_Init', $appState->pluginsLoaded, 'Plugin InstantTranslate must be activated for this test case!');
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertCustomer();//assert the test customer
    }
    
    /***
     */
    public function testSetupData(){
        $this->createLanguageResource();
    }
    
    public function testSearch(){
        $this->translateText("wagen","dictonary.txt");
        $this->translateText("testwagen","regular.txt");
        $this->translateText("Vor dem Hintergrund des Konzepts freier Software. Leitet MittagQI die Entwicklung. Von translate5 als community-basiertem Open Source Übersetzungssystem.","segmentation.txt");
    }
    
    /***
     * Translate text and test the results
     * @param string $text
     * @param string $fileName
     */
    protected function translateText(string $text,string $fileName){
        $result=$this->api()->getJson('editor/instanttranslateapi/translate',[
            'text'=>$text,
            'source'=>self::$sourceLangRfc,
            'target'=>self::$targetLangRfc
        ]);
        $this->assertNotEmpty($result,'No results found for the search request. Search was:'.$text);
        
        //filter only the microsoft results
        $filtered = $this->filterResult($result);
        
        //this is to recreate the file from the api response
        //file_put_contents($this->api()->getFile($fileName, null, false), json_encode($filtered, JSON_PRETTY_PRINT));

        $expected=$this->api()->getFileContent($fileName);
        $actual=json_encode($filtered, JSON_PRETTY_PRINT);
        //check for differences between the expected and the actual content
        $this->assertEquals($expected, $actual, "The expected file an the result file does not match.");
    }
    
    /***
     * Create the language resource
     */
    protected function createLanguageResource(){
        $params=[
            'resourceId'=>'editor_Services_Microsoft',
            'name'=>self::$languageResourceName,
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerIds' => [$this->api()->getCustomer()->id],
            'customerUseAsDefaultIds' => [],
            'customerWriteAsDefaultIds' => [],
            'serviceType' => 'editor_Services_Microsoft',
            'serviceName'=> 'Microsoft'
        ];
        $resource = $this->api()->postJson('editor/languageresourceinstance', $params, null, false);
        $this->assertTrue(is_object($resource), 'Unable to create the language resource:'.$params['name']);
        $this->assertEquals($params['name'], $resource->name);
        self::$languageResourceId=$resource->id;
        error_log("Language resources created. ".$resource->name);
        $resp = $this->api()->getJson('editor/languageresourceinstance/'.$resource->id,[]);
        $this->assertEquals('available',$resp->status,'Tm import stoped. Tm state is:'.$resp->status);
    }
    
    /***
     * Filter only the required fields for the test
     * @param mixed $result
     * @return array
     */
    protected function filterResult($result){
        //filter only microsoft service results
        if(isset($result->{self::$serviceName})){
            $result = $result->{self::$serviceName};
        }
        //for segmentation request only the best matches will be returned
        if(isset($result->translationForSegmentedText)){
            return $result;
        }
        //filter out by language resource name
        if(isset($result->{self::$languageResourceName})){
            $result = $result->{self::$languageResourceName};
        }
        $filtered = [];
        //remove the result parametars which should not be tested
        foreach ($result as $r) {
            if(isset($r->languageResourceid)){
                unset($r->languageResourceid);
            }
            $filtered = $r;
        }
        return $filtered;
    }
    
    /***
     * Cleand up the resources and the task
     */
    public static function tearDownAfterClass(): void {
        self::$api->login('testmanager');
        self::$api->delete('editor/languageresourceinstance/'.self::$languageResourceId);
    }
}