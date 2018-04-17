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
class TbxImportApiTest extends \ZfExtended_Test_ApiTestcase {
    
    public static function setUpBeforeClass() {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        $appState = $api->requestJson('editor/index/applicationstate');
    }
    
    public function testTbxImport(){
        $appState = $this->api()->requestJson('editor/index/applicationstate');
        
        self::assertTrue(in_array('editor_Plugins_Customer_Init',$appState->pluginsLoaded),'Plugin Customer must be active for this test case!');
        
        self::assertCustomer();
        
        $termCollection = $this->api()->requestJson('editor/termcollection', 'POST', array('name' => 'Test api collection', 'customerId' => $this->api()->getCustomer()->id));
        self::assertTrue(is_object($termCollection), 'Unable to create a test collection');
        
        //save the termCollection id so later the collection can be deleted
        $this->api()->addScharedParameters('collectionId', $termCollection->id);
        
        /*
        $this->api()->addFile('Term.tbx', $this->api()->getFile('Term3.tbx'), "application/xml");
        $this->api()->requestJson('editor/termcollection/import', 'POST', array('collectionId' =>$termCollection->id, 'customerId' => $this->api()->getCustomer()->id));
        //$this->api()->requestJson('editor/termcollection/import', 'POST', array('collectionId' =>108, 'customerId' => $this->api()->getCustomer()->id));
        
        $attributes=$this->api()->requestJson('editor/termcollection/testgetattributes', 'GET', array('collectionId' =>$termCollection->id));
        
        self::assertTrue(7==$attributes->termsCount, 'Invalid number of terms created.Terms count:'.$attributes->termsCount.', expected:7');
        self::assertTrue(65==$attributes->termsAtributeCount, 'Invalid number of term attribute created.Terms attribute count:'.$attributes->termsAtributeCount.', expected:65');
        self::assertTrue(21==$attributes->termsEntryAtributeCount, 'Invalid number of entry attribute created.Terms entry attribute count:'.$attributes->termsEntryAtributeCount.', expected:21');
        
        //test with the another file, only the values in the terms, tersmAttributes, and termEntryAttributes are change
        $this->api()->addFile('Term1.tbx', $this->api()->getFile('Term1.tbx'), "application/xml");
        $this->api()->requestJson('editor/termcollection/import', 'POST', array('collectionId' =>$termCollection->id, 'customerId' => $this->api()->getCustomer()->id));
        
        $attributes=$this->api()->requestJson('editor/termcollection/testgetattributes', 'GET', array('collectionId' =>$termCollection->id));
        
        self::assertTrue(7==$attributes->termsCount, 'Second file test.Invalid number of terms created.Terms count:'.$attributes->termsCount.', expected:7');
        self::assertTrue(65==$attributes->termsAtributeCount, 'Second file test.Invalid number of term attribute created.Terms attribute count:'.$attributes->termsAtributeCount.', expected:65');
        self::assertTrue(21==$attributes->termsEntryAtributeCount, 'Second file test.Invalid number of entry attribute created.Terms entry attribute count:'.$attributes->termsEntryAtributeCount.', expected:21');
        
        $this->api()->addFile('Term1.tbx', $this->api()->getFile('Term1.tbx'), "application/xml");
        $this->api()->requestJson('editor/termcollection/import', 'POST', array('collectionId' =>$termCollection->id, 'customerId' => $this->api()->getCustomer()->id));
        
        $attributes=$this->api()->requestJson('editor/termcollection/testgetattributes', 'GET', array('collectionId' =>$termCollection->id));
        
        self::assertTrue(7==$attributes->termsCount, 'Second file test.Invalid number of terms created.Terms count:'.$attributes->termsCount.', expected:7');
        self::assertTrue(65==$attributes->termsAtributeCount, 'Second file test.Invalid number of term attribute created.Terms attribute count:'.$attributes->termsAtributeCount.', expected:65');
        self::assertTrue(21==$attributes->termsEntryAtributeCount, 'Second file test.Invalid number of entry attribute created.Terms entry attribute count:'.$attributes->termsEntryAtributeCount.', expected:21');
        */

        //import the first tbx file,
        $this->singleFileTest('Term.tbx', 7, 65, 21);
        
        //sleep(15);
        
        //the secound tbx file should update some of the terms and term attributes
        $this->singleFileTest('Term1.tbx', 7, 65, 21);

        //sleep(15);
        
        //termEntry in the tbx does not exist in the database, but the term exist
        //after Term2.tbx is imported, one term is merged, and the other 2 in the same term entry in the tbx
        //are added to the same termEntry in the database
        $this->singleFileTest('Term2.tbx', 9, 85, 21);
        
    }
    
    public function XXXtestTbxImportDifferentTerm(){
        self::assertCustomer();
        $termCollection = $this->api()->requestJson('editor/termcollection', 'POST', array('name' => 'Test api collection', 'customerId' => $this->api()->getCustomer()->id));

        self::assertTrue(is_object($termCollection), 'Unable to create a test collection');
        
        $this->api()->addFile('DifferentAttributes.tbx', $this->api()->getFile('DifferentAttributes.tbx'), "application/xml");
        $this->api()->requestJson('editor/termcollection/import', 'POST', array('collectionId' =>$termCollection->id, 'customerId' => $this->api()->getCustomer()->id));
    }
    
    private function singleFileTest($fileName,$termCount,$termsAtributeCount,$termsEntryAtributeCount){
        $collectionId=self::$api->getScharedParameterValue('collectionId');
        
        $this->api()->addFile($fileName, $this->api()->getFile($fileName), "application/xml");
        $this->api()->requestJson('editor/termcollection/import', 'POST', array('collectionId' =>$collectionId, 'customerId' => $this->api()->getCustomer()->id));
        
        $attributes=$this->api()->requestJson('editor/termcollection/testgetattributes', 'GET', array('collectionId' =>$collectionId));
        
        self::assertTrue($termCount==$attributes->termsCount, $fileName.' file test.Invalid number of terms created.Terms count:'.$attributes->termsCount.', expected:'.$termCount);
        self::assertTrue($termsAtributeCount==$attributes->termsAtributeCount, $fileName.' file test.Invalid number of term attribute created.Terms attribute count:'.$attributes->termsAtributeCount.', expected:'.$termsAtributeCount);
        self::assertTrue($termsEntryAtributeCount==$attributes->termsEntryAtributeCount, $fileName.' file test.Invalid number of entry attribute created.Terms entry attribute count:'.$attributes->termsEntryAtributeCount.', expected:'.$termsEntryAtributeCount);
    }
    
    public static function tearDownAfterClass() {
        self::$api->login('testmanager');
        $collectionId=self::$api->getScharedParameterValue('collectionId');
        //self::$api->requestJson('editor/termcollection/'.$collectionId,'DELETE');
    }
    
}