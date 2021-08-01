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
        self::$api->login('testtermproposer');//log in as proposer
        self::assertLogin('testtermproposer');
        self::assertCustomer();
    }
    
    /***
     * Test term and term attribute proposals.
     */
    public function testTermProposal(){
        
        //[1] create the term collection and import the test tbx in it
        $termCollection = $this->api()->requestJson('editor/termcollection', 'POST', ['name' => 'Test api collection', 'customerIds' => $this->api()->getCustomer()->id]);
        $this->assertTrue(is_object($termCollection), 'Unable to create a test collection');
        $this->assertEquals('Test api collection', $termCollection->name);
        self::$collectionId =$termCollection->id;
        $this->api()->addFile('Term.tbx', $this->api()->getFile('Term.tbx'), "application/xml");
        $this->api()->requestJson('editor/termcollection/import', 'POST', ['collectionId' =>self::$collectionId, 'customerIds' => $this->api()->getCustomer()->id,'mergeTerms'=>true]);
        
        
        //[2] find the term inside the term collection
        $response=$this->api()->requestJson('editor/language?page=1&start=0&limit=20&filter='.urlencode('[{"operator":"eq","value":"de-DE","property":"rfc5646"}]'), 'GET');
        $this->assertNotEmpty($response,"Unable to load the language needed for the term search.");
        //this is the term language in the test file. the id is needed for the search
        $response=$response[0];
        $searchParams=[
            'term'=>'*',
            'collectionId' =>self::$collectionId,
            'language'=>$response->id
        ];
        $response=$this->api()->requestJson('editor/termcollection/search', 'GET', $searchParams);
        $this->assertTrue(is_object($response),"No terms are found in the termcollection");
        $this->assertNotEmpty($response->term,"No terms are found in the term collection for the search string '*'");
        $term=$response->term[0];
        
        
        //[3] create term porposal for the Test term
        $proposeParams=[
            'term'=>'TestTermProposal'
        ];
        $proposal = $this->api()->requestJson('editor/term/'.$term->value.'/propose/operation', 'POST',$proposeParams);
        //check if the proposal is valid
        $this->assertTrue(is_object($proposal) && is_object($proposal->proposal),"Unable to propose the term");
        
        
        //[4] create new term entry and add new term in the test termcollection
        $newTermEntryParams=[
            'term'=>'NewTermEntryTerm',
            "collectionId"=>self::$collectionId,
            "language"=>"en",
            "termEntryId"=>null
        ];
        $newTerm = $this->api()->requestJson('editor/term', 'POST',$newTermEntryParams);
        //check if the proposal is valid
        $this->assertTrue(is_object($newTerm) && $newTerm->term=='NewTermEntryTerm',"Unable to propose new term entry with new term.");
        
        
        //[5] create new comment for the term
        $proposeParams=[
            'comment'=>'Alex test comment'
        ];
        $proposal = $this->api()->requestJson('editor/term/'.$term->value.'/comment/operation', 'POST',$proposeParams);
        //check if the proposal is valid
        $this->assertTrue(is_object($proposal) && $proposal->attrValue=='Alex test comment',"Unable to propose comment the term");
        
        
        //[6] search for the term attributes in the term termEntryId
        $attributes=$this->api()->requestJson('editor/termcollection/searchattribute', 'GET', ['termEntryId' =>$term->termEntryId]);
        //validate the term attributes
        $this->assertTrue(is_array($attributes->termAttributes),"No attributes where found for the test proposal term.");
        $attributes=$attributes->termAttributes;
        $this->assertTrue(is_array($attributes[0]->attributes),"No attributes where found for the test proposal term.");
        $attributes=$attributes[0]->attributes;
        //get one proposable attribute so proposal can be created
        $testAttribute=null;
        foreach ($attributes as $attribute){
            if($attribute->proposable){
                $testAttribute=$attribute;
                break;
            }
        }
        $this->assertTrue(!empty($testAttribute),"No attributes where found for the test proposal term.");
        
        
        //[7] create attribute proposal
        $proposeParams=[
            'value'=>'Alex test attribute proposal'
        ];
        $proposal = $this->api()->requestJson('editor/termattribute/'.$testAttribute->attributeId.'/propose/operation', 'POST',$proposeParams);
        //check if the proposal is valid
        $this->assertTrue(is_object($proposal) && is_object($proposal->proposal),"Unable to propose attribute");
        $this->assertTrue($proposal->proposal->attributeId==$testAttribute->attributeId,"The attribute proposal is not for the requested attribute");
        $this->assertTrue($proposal->proposal->value=='Alex test attribute proposal',"The attribute proposal value is not the same as the requested value");
        
        
        //[8] get the export data and compare the values with the expected export file data
        $response=$this->api()->requestJson('editor/languageresourceinstance/testexport','GET');
        $this->assertTrue(is_array($response),"Unable to export the term proposals");
        //file_put_contents($this->api()->getFile('/Export.json', null, false),json_encode($response));
        $expected=$this->api()->getFileContent('Export.json');
        //check for differences between the expected and the actual content
        $this->assertEquals(count($expected), count($response), "The proposal export result does not match the expected result");
        
        
        //[9] delete the attribute proposal
        $proposal = $this->api()->requestJson('editor/termattribute/'.$testAttribute->attributeId.'/removeproposal/operation', 'POST');
        //the response should return attribute object without proposal property
        $this->assertTrue(is_object($proposal) && empty($proposal->proposal),"Unable to remove the term attribute proposal!");
        
        
        //[10] delete the term proposal
        $proposal = $this->api()->requestJson('editor/term/'.$term->value.'/removeproposal/operation', 'POST');
        //the response should return attribute object without proposal property
        $this->assertTrue(is_object($proposal) && empty($proposal->proposal),"Unable to remove the term proposal");
    }
    
    
    public static function tearDownAfterClass(): void {
        self::$api->login('testtermproposer');
        self::$api->requestJson('editor/termcollection/'.self::$collectionId,'DELETE');
    }
    
}