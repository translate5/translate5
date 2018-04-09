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
        //TODO: for the customer we should do same thing as for the user
        //add sql like test-users.sql -> test-customers.sql
        //create simmilar logic as assertLogin() -> assertCustomer
        //get the id from there and use it here
        $appState = $this->api()->requestJson('editor/index/applicationstate');
        
        self::assertTrue(in_array('editor_Plugins_Customer_Init',$appState->pluginsLoaded),'Plugin Customer must be active for this test case!');
        
        self::assertCustomer();
        
        $termCollection = $this->api()->requestJson('editor/termcollection', 'POST', array('name' => 'Test api collection', 'customerId' => $this->api()->getCustomer()->id));
        self::assertTrue(is_object($termCollection), 'Unable to create a test collection');
        
        $this->api()->addFile('Term.tbx', $this->api()->getFile('Term.tbx'), "application/xml");
        //$this->api()->requestJson('editor/termcollection/import', 'POST', array('collectionId' => 135));
        $this->api()->requestJson('editor/termcollection/import', 'POST', array('collectionId' =>$termCollection->id));
    }
    
}