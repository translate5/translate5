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

        /*class_exists('editor_Utils');
        $last = editor_Utils::db()->query('SELECT `id` FROM `LEK_languageresources` ORDER BY `id` DESC LIMIT 1')->fetchColumn();
        self::$api->requestJson('editor/termcollection/' . $last,'DELETE');*/

        // [1] create empty term collection
        $termCollection = $this->api()->requestJson('editor/termcollection', 'POST', [
            'name' => 'Test api collection',
            'customerIds' => $this->api()->getCustomer()->id]
        );
        $this->assertTrue(is_object($termCollection), 'Unable to create a test collection');
        $this->assertEquals('Test api collection', $termCollection->name);

        // Remember collectionId
        self::$collectionId = $termCollection->id;

        // [2] import tbx with single termEntry
        $this->api()->addFile('Term.tbx', $this->api()->getFile('Term.tbx'), "application/xml");
        $this->api()->requestJson('editor/termcollection/import', 'POST', [
            'collectionId' => self::$collectionId,
            'customerIds' => $this->api()->getCustomer()->id,
            'mergeTerms' => true
        ]);

        // [3] get languages list, limited to de-DE language
        $language = $this->api()->requestJson('editor/language', 'GET', [
            'filter' => '[{"operator":"eq","value":"de-DE","property":"rfc5646"}]',
            'page' => 1,
            'start' => 0,
            'limit' => 20,
        ]);
        $this->assertNotEmpty($language, 'Unable to load the language needed for the term search.');

        // [4] find imported term by *-query and de-DE language id
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

        // [5] create proposal 'TestTermProposal' for imported term
        $importedTermProposal = $this->api()->requestJson('editor/term', 'PUT', $data = [
            'termId' => $importedTerm->id,
            'proposal' => 'TestTermProposal'
        ]);
        $this->assertTrue(is_object($importedTermProposal)
            && $importedTermProposal->proposal === $data['proposal'],
            'Unable to create proposal for the tbx-imported term');

        // Get imported term attributes
        $importedTermAttrA = $this->api()->requestJson('editor/plugins_termportal_data/siblinginfo', 'POST', [
            'termId' => $importedTerm->id
        ])->term->attributes;

        // [6] reject that proposal, so we now have two separate terms, and last one is rejected
        $rejected = $this->api()->requestJson('editor/attribute', 'PUT', [
            'attrId' => array_column($importedTermAttrA, 'id', 'type')['processStatus'],
            'value' => 'rejected'
        ]);

        // [7] create new term entry with term = "Term1" (de-DE) and note-attr = "Note for Term1"
        $Term1 = $this->api()->requestJson('editor/term', 'POST', $Term1_data = [
            'collectionId' => self::$collectionId,
            'language' => $language[0]->rfc5646,
            'term' => 'Term1',
            'note' => 'Note for Term1'
        ]);

        // Check if the term entry proposal is valid
        $this->assertTrue(is_object($Term1)
            && is_numeric($Term1->termEntryId)
            && $Term1->query === $Term1_data['term'],
            'Unable to propose new term entry with new term.');

        // [8] create new term for same term entry with term=Term2 and 'en'-language
        $Term2 = $this->api()->requestJson('editor/term', 'POST', $data = [
            'collectionId' => self::$collectionId,
            'termEntryId' => $Term1->termEntryId,
            'language' => 'en',
            'term' => 'Term2',
        ]);
        $this->assertTrue(is_object($Term2)
            && $Term2->query == $data['term']
            && $Term2->termEntryId == $data['termEntryId']
            && is_numeric($Term2->termId), 'Appending term under existing termEntry was unsuccessful');

        // [9] get the list of possible attributes (e.g. attribute datatypes)
        $dataTypeA = $this->api()->requestJson('editor/attributedatatype');
        $this->assertTrue(is_object($dataTypeA), 'Unable to get attribute datatypes');

        // [10] create image-attr for entry-level for created term entry
        $figurecreate = $this->api()->requestJson('editor/attribute', 'POST', $data = [
            'termEntryId' => $Term1->termEntryId,
            'dataType' => 'figure'
        ]);
        $this->assertTrue(is_object($figurecreate)
            && is_numeric($figurecreate->inserted->id), 'Unable to create figure-attribute for the entry-level');

        // [11] update that image-attr, e.g upload the image file
        $this->api()->addFile('figure', $this->api()->getFile('Image.jpg'), "image/jpg");
        $figureupdate = $this->api()->requestJson('editor/attribute', 'PUT', $data = [
            'attrId' => $figurecreate->inserted->id,
        ]);

        // [12] create ref-attr for termEntry-level for Term1
        $refcreate = $this->api()->requestJson('editor/attribute', 'POST', $data = [
            'termEntryId' => $Term1->termEntryId,
            'dataType' => 'crossReference'
        ]);
        $this->assertTrue(is_object($refcreate)
            && is_numeric($refcreate->inserted->id), 'Unable to create ref-attribute for the entry-level');

        // [13] update that ref-attr with the termEntryTbxId of an imported term
        $refupdate = $this->api()->requestJson('editor/attribute', 'PUT', $data = [
            'attrId' => $refcreate->inserted->id,
            'target' => $importedTerm->termEntryTbxId,
        ]);
        $this->assertTrue(is_object($refupdate)
            && $refupdate->value == $importedTerm->term, 'Unable to update ref-attribute for the entry-level');

        // [14] create ref-attr for term-level for Term1
        $refcreate = $this->api()->requestJson('editor/attribute', 'POST', $data = [
            'termId' => $Term1->termId,
            'dataType' => 'crossReference'
        ]);
        $this->assertTrue(is_object($refcreate)
            && is_numeric($refcreate->inserted->id), 'Unable to create ref-attribute for the entry-level');

        // [15] update that ref-attr with the termTbxId of an appended term Term2
        $refupdate = $this->api()->requestJson('editor/attribute', 'PUT', $data = [
            'attrId' => $refcreate->inserted->id,
            'target' => $Term2->termTbxId,
        ]);
        $this->assertTrue(is_object($refupdate)
            && $refupdate->value == $Term2->query, 'Unable to update ref-attribute for the term-level');

        // [16] create xref-attr for Term1
        $xrefcreate = $this->api()->requestJson('editor/attribute', 'POST', $data = [
            'termId' => $Term1->termId,
            'dataType' => 'externalCrossReference'
        ]);
        $this->assertTrue(is_object($xrefcreate)
            && is_numeric($xrefcreate->inserted->id), 'Unable to create xref-attribute for the term-level');

        // [17] update that xref-attr value
        $xrefupdate = $this->api()->requestJson('editor/attribute', 'PUT', [
            'attrId' => $xrefcreate->inserted->id,
            'dataIndex' => 'value',
            'value' => 'Wikipedia website'
        ]);
        $this->assertTrue(is_object($xrefupdate)
            && property_exists($xrefupdate, 'isValidUrl'), 'Unable to set value for the term-level xref-attribute');

        // [18] update that xref-attr target
        $xrefupdate = $this->api()->requestJson('editor/attribute', 'PUT', $xrefdata = [
            'attrId' => $xrefcreate->inserted->id,
            'dataIndex' => 'target',
            'value' => 'https://wikipedia.org'
        ]);
        $this->assertTrue(is_object($xrefupdate) && $xrefupdate->isValidUrl, 'Unable to set target for the term-level xref-attribute');

        // [10] search for the term attributes
        $terminfo = $this->api()->requestJson('editor/plugins_termportal_data/terminfo', 'POST', ['termId' => $Term1->termId]);
        $this->assertTrue(is_object($terminfo), 'No data returned by terminfo-call');

        // Check image-attr is there
        $this->assertNotEmpty($images = $terminfo->entry->images, 'No image-attributes found for termEntry-level');
        $this->assertNotEmpty($src = $images[0]->src, 'First image-attr has empty src');

        // Check it's the same file that we uploaded
        $this->assertEquals(
            $this->api()->getRaw($src),
            file_get_contents($this->api()->getFile('Image.jpg')),
            'Image-file not exists or not equal to uploaded'
        );

        // [20] get termportal setup data (dictionaries, etc)
        $setup = $this->api()->requestJson('editor/plugins_termportal_data');
        $this->assertIsObject($setup, 'Termportal setup data is not an array');

        // Check props presence
        foreach ([
             'locale', 'role', 'permission', 'activeItem', 'l10n', 'filterWindow', 'filterPanel', 'lang',
             'langInclSubs', 'flag', 'newTermLang', 'language', 'cfg', 'itranslateQuery', 'time'] as $prop)
            $this->assertObjectHasAttribute($prop, $setup, 'Termportal setup data has no ' . $prop . '-property');

        // [21] call siblinginfo to get attributes for Term1
        $siblinginfo = $this->api()->requestJson('editor/plugins_termportal_data/siblinginfo', 'POST', ['termId' => $Term1->termId]);
        $this->assertIsObject($siblinginfo, 'Siblinginfo data is not an array');

        // Check props presence
        foreach (['entry', 'language', 'term'] as $prop)
            $this->assertObjectHasAttribute($prop, $siblinginfo, 'Sibling info data has no ' . $prop . '-property');

        // Check ref-attr is there
        $this->assertNotEmpty($refs = $siblinginfo->term->refs, 'Unable to find any ref-attribute for Term1');
        $this->assertEquals($refs[0]->target, $Term2->termTbxId, 'Found ref-attr is not pointing to Term2');

        // Check xref-attr is there
        $this->assertNotEmpty($xrefs = $siblinginfo->term->xrefs->externalCrossReference, 'Unable to find any ref-attribute for Term1');
        $this->assertEquals($xrefs[0]->target, $xrefdata['value'], 'Found xref-attr has value not equal to "' . $xrefdata['value'] . '"');

        // Check note-attr is there
        $dataTypeId_note = array_column((array) $dataTypeA, 'id', 'dataType')['note'];
        $attrA = array_column($siblinginfo->term->attributes, 'value', 'dataTypeId');
        $this->assertArrayHasKey($dataTypeId_note, $attrA, 'Note-attr not found among Term1 attributes');
        $this->assertEquals($attrA[$dataTypeId_note], $Term1_data['note'], 'Note-attr found but not equals to the one used in api call');

        // [22] Get the export data and compare the values with the expected export file data
        $exportFact = $this->api()->requestJson('editor/languageresourceinstance/testexport', 'GET');
        $this->assertIsArray($exportFact, 'Unable to export the term proposals');
        $exportPlan = $this->api()->getFileContent('Export.json');
        $this->assertEquals(count((array) $exportFact), count($exportPlan), "The proposal export result does not match the expected result");

        // [23] delete image-attr, check image full path not exists anymore
        $figuredelete = $this->api()->requestJson('editor/attribute', 'DELETE', ['attrId' => $figurecreate->inserted->id]);
        $this->assertIsObject($figuredelete, 'Unable to delete the image-attr');
        $this->assertObjectHasAttribute('updated', $figuredelete, 'Image-attr deletion response does not contain "updated" prop');
        $this->assertFalse($this->api()->getRaw($src), 'Image-attr deleted but image-file still exists at ' . $src);

        // [24] delete 'TestTermProposal' [isLast=false]
        $rejecteddelete = $this->api()->requestJson('editor/term', 'DELETE', ['termId' => $rejected->inserted->id]);
        $this->assertIsObject($rejecteddelete, 'Unable to delete rejected term');
        $this->assertObjectHasAttribute('isLast', $rejecteddelete, 'Rejected term deletion response does not have isLast prop');
        $this->assertFalse($rejecteddelete->isLast, 'Deleted term should have not been the last - neither among its language nor among its termEntry');

        // [25] delete note-attr for Term1
        $attrId_note = array_column($siblinginfo->term->attributes, 'id', 'dataTypeId')[$dataTypeId_note];
        $notedelete = $this->api()->requestJson('editor/attribute', 'DELETE', ['attrId' => $attrId_note]);
        $this->assertIsObject($notedelete, 'Unable to delete note-attr');
        $this->assertObjectHasAttribute('updated', $notedelete, 'Unable to delete note-attr');

        // [26] delete Term2 (having language=en)    [isLast=language]
        $Term2_delete = $this->api()->requestJson('editor/term', 'DELETE', ['termId' => $Term2->termId]);
        $this->assertIsObject($Term2_delete, 'Unable to delete Term2 (having language=en) [isLast=language]');
        $this->assertObjectHasAttribute('isLast', $Term2_delete, 'Term2 deletion response does not have isLast prop');
        $this->assertEquals($Term2_delete->isLast, 'language', 'Deleted term should be the last among its language');

        // [27] delete Term1 (having language=de-DE) [isLast=termEntry]
        $Term1_delete = $this->api()->requestJson('editor/term', 'DELETE', ['termId' => $Term1->termId]);
        $this->assertIsObject($Term1_delete, 'Unable to delete Term2 (having language=en) [isLast=language]');
        $this->assertObjectHasAttribute('isLast', $Term1_delete, 'Term2 deletion response does not have isLast prop');
        $this->assertEquals($Term1_delete->isLast, 'entry', 'Deleted term should be the last among its termEntry');
    }

    public static function tearDownAfterClass(): void {
        self::$api->login('testtermproposer');
        self::$api->cleanup && self::$api->requestJson('editor/termcollection/'.self::$collectionId,'DELETE');
    }

}