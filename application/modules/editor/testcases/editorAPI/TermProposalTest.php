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
        
        // [1] Create the empty term collection
        $termCollection = $this->api()->requestJson('editor/termcollection', 'POST', [
            'name' => 'Test api collection',
            'customerIds' => $this->api()->getCustomer()->id]
        );
        $this->assertTrue(is_object($termCollection), 'Unable to create a test collection');
        $this->assertEquals('Test api collection', $termCollection->name);

        // Remember collectionId
        self::$collectionId = $termCollection->id;

        // [2] Import the test tbx in that collection
        $this->api()->addFile('Term.tbx', $this->api()->getFile('Term.tbx'), "application/xml");
        $this->api()->requestJson('editor/termcollection/import', 'POST', [
            'collectionId' => self::$collectionId,
            'customerIds' => $this->api()->getCustomer()->id,
            'mergeTerms' => true
        ]);

        // [3] Load languages
        $language = $this->api()->requestJson('editor/language', 'GET', [
            'filter' => '[{"operator":"eq","value":"de-DE","property":"rfc5646"}]',
            'page' => 1,
            'start' => 0,
            'limit' => 20,
        ]);
        $this->assertNotEmpty($language, 'Unable to load the language needed for the term search.');

        // [4] Find the term inside the term collection
        $termsearch = $this->api()->requestJson('editor/plugins_termportal_data/search', 'GET', [
            'query' => '*',
            'collectionIds' => self::$collectionId,
            'language' => $language[0]->id,
            'start' => 0,
            'limit' => 1
        ]);
        $this->assertTrue(is_object($termsearch), 'No terms are found in the termcollection ' . self::$collectionId);
        $this->assertNotEmpty($termsearch->data, "No terms are found in the term collection for the search string '*'");

        // Tbx-imported term shortcut
        $importedTerm = $termsearch->data[0];

        // [5] Сreate proposal for the tbx-imported term
        $importedTermProposal = $this->api()->requestJson('editor/term', 'PUT', $data = [
            'termId' => $importedTerm->id,
            'proposal' => 'TestTermProposal'
        ]);
        $this->assertTrue(is_object($importedTermProposal) && $importedTermProposal->proposal === $data['proposal'], 'Unable to propose the term');
        
        // [6] Create new term entry and add new term in the test termcollection
        $appendedTerm = $this->api()->requestJson('editor/term', 'POST', $data = [
            'collectionId' => self::$collectionId,
            'language' => 'en',
            'term' => 'NewTermEntryTerm',
        ]);

        // Check if the term entry proposal is valid
        $this->assertTrue(is_object($appendedTerm)
            && is_numeric($appendedTerm->termEntryId)
            && $appendedTerm->query === $data['term'],
            'Unable to propose new term entry with new term.');

        // [7] Get the list of possible attributes (e.g. attribute datatypes)
        $dataTypeA = $this->api()->requestJson('editor/attributedatatype');
        $this->assertTrue(is_object($dataTypeA), 'Unable to get attribute datatypes');

        // [8] Create empty note-attribute for the appended term
        $attrcreate = $this->api()->requestJson('editor/attribute', 'POST', $data = [
            'level' => 'term',
            'termEntryId' => $appendedTerm->termEntryId,
            'language' => 'en',
            'termId' => $appendedTerm->termId,
            'dataTypeId' => $dataTypeId_note = array_column((array) $dataTypeA, 'id', 'dataType')['note'],
        ]);

        // Check if the note-attribute was created
        $this->assertTrue(is_object($attrcreate) && is_numeric($attrcreate->inserted->id), 'Unable to create note-attribute for the term');

        // [9] Setup a value for that note-attribute
        $attrupdate = $this->api()->requestJson('editor/attribute', 'PUT', $data = [
            'attrId' => $attrcreate->inserted->id,
            'value' => 'Alex test comment'
        ]);

        // Check if the proposal is valid
        $this->assertTrue(is_object($attrupdate) && $attrupdate->success, 'Unable to setup a value for note-attribute');

        // [10] Search for the term attributes in the term termEntryId
        $attributes = $this->api()->requestJson('editor/plugins_termportal_data/terminfo', 'POST', ['termId' => $appendedTerm->termId]);

        // Check term attributes are in place
        $this->assertTrue(is_object($attributes = $attributes->term), 'No attributes where found for the test proposal term.');
        $this->assertTrue(is_array($attributes = $attributes->attributes), 'No attributes where found for the test proposal term.');

        // Find note-attribute
        $foundAttribute = null;
        foreach ($attributes as $attribute) {
            if ($attribute->dataTypeId == $dataTypeId_note && $attribute->value == $data['value']) {
                $foundAttribute = $attribute;
                break;
            }
        }
        $this->assertTrue(!empty($foundAttribute), "Note-attribute that was set up for appended term - is not found");

        // [11] Get the export data and compare the values with the expected export file data
        $exportFact = $this->api()->requestJson('editor/languageresourceinstance/testexport', 'GET');
        $this->assertTrue(is_array($exportFact), 'Unable to export the term proposals');
        $exportPlan = $this->api()->getFileContent('Export.json');
        $this->assertEquals(count($exportFact), count($exportPlan), "The proposal export result does not match the expected result");

        // [12] Delete appended term
        $termdelete = $this->api()->requestJson('editor/term', 'DELETE', ['termId' => $appendedTerm->termId]);
        $this->assertTrue(is_object($termdelete), 'Appended term deletion was unsuccessful');
    }
    
    public static function tearDownAfterClass(): void {
        self::$api->login('testtermproposer');
        self::$api->cleanup && self::$api->requestJson('editor/termcollection/'.self::$collectionId,'DELETE');
    }

}