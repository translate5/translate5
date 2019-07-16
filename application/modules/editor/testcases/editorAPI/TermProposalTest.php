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
class TermProposalTest extends \ZfExtended_Test_ApiTestcase {
    
    /***
     * The current active collection
     * @var integer
     */
    protected static $collectionId;
    
    public static function setUpBeforeClass(): void {
        self::$api=new ZfExtended_Test_ApiHelper(__CLASS__);
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testtermproposer');
        self::assertCustomer('testtermproposer');
    }
    
    /***
     */
    public function testTermProposal(){
        $termCollection = $this->api()->requestJson('editor/termcollection', 'POST', ['name' => 'Test api collection', 'customerIds' => $this->api()->getCustomer()->id]);
        $this->assertTrue(is_object($termCollection), 'Unable to create a test collection');
        $this->assertEquals('Test api collection', $termCollection->name);
        self::$collectionId =$termCollection->id;
        
        $this->api()->addFile('Term.tbx', $this->api()->getFile('Term.tbx'), "application/xml");
        $this->api()->requestJson('editor/termcollection/import', 'POST', ['collectionId' =>self::$collectionId, 'customerIds' => $this->api()->getCustomer()->id,'mergeTerms'=>true]);
        
        //TODO: the problem here with the test is that the test customer is not assignet to the new user
        //so the whole user test customer test collection assigment should be done here so the search function is used
        //if not, find other way to load the term and get the id so the proposal can be done
        $response=$this->api()->requestJson('editor/termcollection/search', 'GET', ['term'=>'*','collectionId' =>self::$collectionId]);
        
        $this->assertTrue(is_object($response),"Unable to export the terms by term collection");
        $this->assertNotEmpty($response->filedata,"The exported tbx file by collection is empty");
        $actual=$response->filedata;
        error_log($actual);
    }
    
    public static function tearDownAfterClass(): void {
        self::$api->login('testtermproposer');
        self::$api->requestJson('editor/termcollection/'.self::$collectionId,'DELETE');
    }
    
}