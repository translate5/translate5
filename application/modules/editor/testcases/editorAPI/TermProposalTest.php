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
class TermProposalTest extends \editor_Test_ApiTest {
    
    /***
     * The current active collection
     * @var integer
     */
    protected static $collectionId;

    /**
     * German language
     *
     * @var stdClass
     */
    protected static $german;

    /**
     * English language
     *
     * @var stdClass
     */
    protected static $english;

    /**
     * Italian language
     *
     * @var stdClass
     */
    protected static $italian;

    /**
     * Termportal setup data (dictionaries, etc)
     *
     * @var
     */
    protected static $setup;

    /**
     * We need the termproposer to be logged in for the test
     * @var string
     */
    protected static string $setupUserLogin = 'testtermproposer';

    /***
     * Test term and term attribute proposals.
     */
    public function testTermProposal(){

        /*class_exists('editor_Utils');
        $last = editor_Utils::db()->query('SELECT `id` FROM `LEK_languageresources` ORDER BY `id` DESC LIMIT 1')->fetchColumn();
        static::api()->delete('editor/termcollection/' . $last);*/

        // [1] create empty term collection
        $termCollection = static::api()->postJson('editor/termcollection', [
            'name' => 'Test api collection',
            'customerIds' => static::getTestCustomerId()
        ]);
        $this->assertTrue(is_object($termCollection), 'Unable to create a test collection');
        $this->assertEquals('Test api collection', $termCollection->name);

        // Remember collectionId
        self::$collectionId = $termCollection->id;

        // [2] import tbx with single termEntry
        static::api()->addFile('Term.tbx', static::api()->getFile('Term.tbx'), "application/xml");
        static::api()->postJson('editor/termcollection/import', [
            'collectionId' => self::$collectionId,
            'customerIds' => static::getTestCustomerId(),
            'mergeTerms' => true
        ]);

        // [3] get languages: german
        $german = static::api()->getJson('editor/language', ['filter' => '[{"operator":"eq","value":"de-DE","property":"rfc5646"}]']);
        $this->assertNotEmpty($german, 'Unable to load the german-language needed for the term search.');
        self::$german = $german[0];

        // english
        $english = static::api()->getJson('editor/language', ['filter' => '[{"operator":"eq","value":"en","property":"rfc5646"}]']);
        $this->assertNotEmpty($english, 'Unable to load english-language needed for use in noTermDefinedFor filter');
        self::$english = $english[0];

        // italian
        $italian = static::api()->getJson('editor/language', ['filter' => '[{"operator":"eq","value":"it","property":"rfc5646"}]']);
        $this->assertNotEmpty($italian, 'Unable to load italian-language needed for use in noTermDefinedFor-filter');
        self::$italian = $italian[0];

        // [4] find imported term by *-query and de-DE language id
        $termsearch = static::api()->getJson('editor/plugins_termportal_data/search', [
            'query' => '*',
            'collectionIds' => self::$collectionId,
            'language' => self::$german->id,
            'start' => 0,
            'limit' => 1
        ]);
        $this->assertTrue(is_object($termsearch), 'No terms are found in the termcollection ' . self::$collectionId);
        $this->assertNotEmpty($termsearch->data, "No terms are found in the term collection for the search string '*'");

        // Tbx-imported term shortcut
        $importedTerm = $termsearch->data[0];

        // [5] create proposal 'TestTermProposal' for imported term
        $data = [
            'termId' => $importedTerm->id,
            'proposal' => 'TestTermProposal'
        ];
        $importedTermProposal = static::api()->putJson('editor/term', $data);
        $this->assertTrue(is_object($importedTermProposal)
            && $importedTermProposal->proposal === $data['proposal'],
            'Unable to create proposal for the tbx-imported term');

        // Get imported term attributes
        $importedTermAttrA = static::api()->postJson('editor/plugins_termportal_data/siblinginfo', [
            'termId' => $importedTerm->id
        ])->term->attributes;

        // [6] reject that proposal, so we now have two separate terms, and last one is rejected
        $rejected = static::api()->putJson('editor/attribute', [
            'attrId' => array_column($importedTermAttrA, 'id', 'type')['processStatus'],
            'value' => 'rejected'
        ]);

        // [7] create new term entry with term = "Term1" (de-DE) and note-attr = "Note for Term1"
        $Term1_data = [
            'collectionId' => self::$collectionId,
            'language' => self::$german->rfc5646,
            'term' => 'Term1',
            'note' => 'Note for Term1'
        ];
        $Term1 = static::api()->postJson('editor/term', $Term1_data);

        // Check if the term entry proposal is valid
        $this->assertTrue(is_object($Term1)
            && is_numeric($Term1->termEntryId)
            && $Term1->query === $Term1_data['term'],
            'Unable to propose new term entry with new term.');

        // [8] create new term for same term entry with term=Term2 and 'en'-language
        $data = [
            'collectionId' => self::$collectionId,
            'termEntryId' => $Term1->termEntryId,
            'language' => 'en',
            'term' => 'Term2',
        ];
        $Term2 = static::api()->postJson('editor/term', $data);
        $this->assertTrue(is_object($Term2)
            && $Term2->query == $data['term']
            && $Term2->termEntryId == $data['termEntryId']
            && is_numeric($Term2->termId), 'Appending term under existing termEntry was unsuccessful');

        // [9] get the list of possible attributes (e.g. attribute datatypes)
        $dataTypeA = static::api()->getJson('editor/attributedatatype');
        $this->assertTrue(is_object($dataTypeA), 'Unable to get attribute datatypes');

        // [10] create image-attr for entry-level for created term entry
        $figurecreate = static::api()->postJson('editor/attribute', [
            'termId' => $Term1->termId,
            'level' => 'entry',
            'dataType' => 'figure'
        ]);
        $this->assertTrue(is_object($figurecreate)
            && is_numeric($figurecreate->inserted->id), 'Unable to create figure-attribute for the entry-level');

        // [11] update that image-attr, e.g upload the image file
        static::api()->addFile('figure', static::api()->getFile('Image.jpg'), "image/jpg");
        $figureupdate = static::api()->putJson('editor/attribute', [
            'attrId' => $figurecreate->inserted->id,
        ]);

        // [12] create ref-attr for termEntry-level for Term1
        $refcreate = static::api()->postJson('editor/attribute', [
            'termId' => $Term1->termId,
            'level' => 'entry',
            'dataType' => 'crossReference'
        ]);
        $this->assertTrue(is_object($refcreate)
            && is_numeric($refcreate->inserted->id), 'Unable to create ref-attribute for the entry-level');

        // [13] update that ref-attr with the termEntryTbxId of an imported term
        $refupdate = static::api()->putJson('editor/attribute', [
            'attrId' => $refcreate->inserted->id,
            'target' => $importedTerm->termEntryTbxId,
        ]);
        $this->assertTrue(is_object($refupdate)
            && $refupdate->target == $rejected->inserted->termEntryTbxId, 'Unable to update ref-attribute for the entry-level');

        // [14] create ref-attr for term-level for Term1
        $refcreate = static::api()->postJson('editor/attribute', [
            'termId' => $Term1->termId,
            'level' => 'term',
            'dataType' => 'crossReference'
        ]);
        $this->assertTrue(is_object($refcreate)
            && is_numeric($refcreate->inserted->id), 'Unable to create ref-attribute for the entry-level');

        // [15] update that ref-attr with the termTbxId of an appended term Term2
        $refupdate = static::api()->putJson('editor/attribute', [
            'attrId' => $refcreate->inserted->id,
            'target' => $Term2->termTbxId,
        ]);
        $this->assertTrue(is_object($refupdate)
            && $refupdate->value == $Term2->query, 'Unable to update ref-attribute for the term-level');

        // [16] create xref-attr for Term1
        $xrefcreate = static::api()->postJson('editor/attribute', [
            'termId' => $Term1->termId,
            'level' => 'term',
            'dataType' => 'externalCrossReference'
        ]);
        $this->assertTrue(is_object($xrefcreate)
            && is_numeric($xrefcreate->inserted->id), 'Unable to create xref-attribute for the term-level');

        // [17] update that xref-attr value
        $xrefupdate = static::api()->putJson('editor/attribute', [
            'attrId' => $xrefcreate->inserted->id,
            'dataIndex' => 'value',
            'value' => 'Wikipedia website'
        ]);
        $this->assertTrue(is_object($xrefupdate)
            && property_exists($xrefupdate, 'isValidUrl'), 'Unable to set value for the term-level xref-attribute');

        // [18] update that xref-attr target
        $xrefdata = [
            'attrId' => $xrefcreate->inserted->id,
            'dataIndex' => 'target',
            'value' => 'https://wikipedia.org'
        ];
        $xrefupdate = static::api()->putJson('editor/attribute', $xrefdata);
        $this->assertTrue(is_object($xrefupdate) && $xrefupdate->isValidUrl, 'Unable to set target for the term-level xref-attribute');

        // [10] search for the term attributes
        $terminfo = static::api()->postJson('editor/plugins_termportal_data/terminfo', ['termId' => $Term1->termId]);
        $this->assertTrue(is_object($terminfo), 'No data returned by terminfo-call');

        // Check image-attr is there
        $this->assertNotEmpty($images = $terminfo->entry->images, 'No image-attributes found for termEntry-level');
        $this->assertNotEmpty($src = $images[0]->src, 'First image-attr has empty src');

        // Check it's the same file that we uploaded
        $result = static::api()->getRaw($src);
        $this->assertFalse(static::api()->isJsonResultError($result), 'Image-file could not be requested');
        $this->assertEquals(
            $result->data,
            file_get_contents(static::api()->getFile('Image.jpg')),
            'Image-file not exists or not equal to uploaded'
        );

        // [20] get termportal setup data (dictionaries, etc)
        self::$setup = static::api()->getJson('editor/plugins_termportal_data');
        $this->assertIsObject(self::$setup, 'Termportal setup data is not an array');

        // Check props presence
        foreach ([
             'locale', 'right', 'permission', 'activeItem', 'l10n', 'filterWindow', 'filterPanel', 'lang',
             'langInclSubs', 'flag', 'langAll', 'language', 'cfg', 'itranslateQuery', 'time'] as $prop)
            $this->assertObjectHasAttribute($prop, self::$setup, 'Termportal setup data has no ' . $prop . '-property');

        // [21] call siblinginfo to get attributes for Term1
        $siblinginfo = static::api()->postJson('editor/plugins_termportal_data/siblinginfo', ['termId' => $Term1->termId]);
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

        // Assert that termportal filters are working
        $this->assertFilters($dataTypeA);

        // Batch create note-attr for termEntry-level
        $this->assertBatchEdit(2, 'batch-value for note-attr for termEntry-level', [
            'termId' => $termId_batch = join(',', [$importedTerm->id, $Term1->termId]),
            'level' => 'entry',
            'dataType' => $dataTypeId_note,
            'batch' => '1'
        ], 0, false);

        // Batch create image-attr for language-level
        $this->assertBatchEdit($planQtyImage = 1, static::api()->getFile('Image.jpg'), [
            'termId' => $Term1->termId,
            'level' => 'language',
            'dataType' => 'figure',
            'batch' => '1',
        ], 0, true);

        // Batch create note-attr for term-level
        $this->assertBatchEdit($planQtyNote = 2, 'batch-value for note-attr for term-level', [
            'termId' => $termId_batch,
            'level' => 'term',
            'dataType' => $dataTypeId_note,
            'batch' => '1',
        ], $existingPlanQtyNote = 1, true);

        // [22] Get the export data and compare the values with the expected export file data
        $exportFact = static::api()->getJson('editor/languageresourceinstance/testexport', [
            'collectionId' => self::$collectionId,
        ]);
        $this->assertIsArray($exportFact, 'Unable to export the term proposals');
        $exportPlan = static::api()->getFileContent('Export.json');
        $this->assertEquals(
            count($exportPlan), // + $planQtyImage + $planQtyNote - $existingPlanQtyNote,
            count((array) $exportFact),
            "The proposal export result does not match the expected result"
        );

        // [23] delete image-attr, check image full path not exists anymore
        $figuredelete = static::api()->delete('editor/attribute', ['attrId' => $figurecreate->inserted->id]);
        $this->assertIsObject($figuredelete, 'Unable to delete the image-attr');
        $this->assertObjectHasAttribute('updated', $figuredelete, 'Image-attr deletion response does not contain "updated" prop');
        $result = static::api()->getRaw($src);
        $this->assertTrue(static::api()->isJsonResultError($result), 'Image-attr deleted but image-file still exists at ' . $src);

        // [24] delete 'TestTermProposal' [isLast=false]
        $rejecteddelete = static::api()->delete('editor/term', ['termId' => $rejected->inserted->id]);
        $this->assertIsObject($rejecteddelete, 'Unable to delete rejected term');
        $this->assertObjectHasAttribute('isLast', $rejecteddelete, 'Rejected term deletion response does not have isLast prop');
        $this->assertFalse($rejecteddelete->isLast, 'Deleted term should have not been the last - neither among its language nor among its termEntry');

        // [25] delete note-attr for Term1
        $attrId_note = array_column($siblinginfo->term->attributes, 'id', 'dataTypeId')[$dataTypeId_note];
        $notedelete = static::api()->delete('editor/attribute', ['attrId' => $attrId_note]);
        $this->assertIsObject($notedelete, 'Unable to delete note-attr');
        $this->assertObjectHasAttribute('updated', $notedelete, 'Unable to delete note-attr');

        // [26] delete Term2 (having language=en)    [isLast=language]
        $Term2_delete = static::api()->delete('editor/term', ['termId' => $Term2->termId]);
        $this->assertIsObject($Term2_delete, 'Unable to delete Term2 (having language=en) [isLast=language]');
        $this->assertObjectHasAttribute('isLast', $Term2_delete, 'Term2 deletion response does not have isLast prop');
        $this->assertEquals($Term2_delete->isLast, 'language', 'Deleted term should be the last among its language');

        // [27] delete Term1 (having language=de-DE) [isLast=termEntry]
        $Term1_delete = static::api()->delete('editor/term', ['termId' => $Term1->termId]);
        $this->assertIsObject($Term1_delete, 'Unable to delete Term2 (having language=en) [isLast=language]');
        $this->assertObjectHasAttribute('isLast', $Term1_delete, 'Term2 deletion response does not have isLast prop');
        $this->assertEquals($Term1_delete->isLast, 'entry', 'Deleted term should be the last among its termEntry');

        // [28] Rename the note-attr label
        $currentLabel = $dataTypeA->$dataTypeId_note->title;
        $labeledit = static::api()->putJson('editor/attributedatatype', [
            'dataTypeId' => $dataTypeId_note,
            'locale' => 'en',
            'label' => $currentLabel . '-amended'
        ]);
        $this->assertIsObject($labeledit, 'Unable to edit note-attr label');
        $this->assertObjectHasAttribute('label', $labeledit, 'Note-attr editing response has no label-prop');

        // Restore old label back
        $labeledit = static::api()->putJson('editor/attributedatatype', [
            'dataTypeId' => $dataTypeId_note,
            'locale' => 'en',
            'label' => $currentLabel
        ]);
        $this->assertIsObject($labeledit, 'Unable to revert note-attr label back');
        $this->assertObjectHasAttribute('label', $labeledit, 'Note-attr reverting back response has no label-prop');
    }

    public static function afterTests(): void {
        static::api()->login('testtermproposer');
        static::api()->delete('editor/termcollection/'.self::$collectionId);
    }

    /**
     * Run search and check that results quantity is as expected
     *
     * @param $qty Expected quantity of results
     * @param array $customParams
     */
    public function assertSearchResultQty($qty, $customParams = []) {

        // Common search params
        $commonParams = [
            'query' => '*',
            'collectionIds' => self::$collectionId,
            'language' => self::$german->id,
            'start' => 0,
            'limit' => 10
        ];

        // Append common data to custom data
        $finalParams = $commonParams + $customParams;

        // Get response
        $resp = static::api()->getJson('editor/plugins_termportal_data/search', $finalParams);

        // Print params and get output
        $query = var_export($finalParams, true);

        // Do checks
        $this->assertIsObject($resp, 'Invalid response. ' . $query);
        $this->assertObjectHasAttribute('data', $resp, 'Response has no data-prop. ' . $query);
        $this->assertIsArray($resp->data, '$resp->data is not an array. ' . $query);
        $this->assertEquals($qty, count($resp->data), 'Results qty should be ' . $qty . ', but ' . count($resp->data) . ' got instead. ' . $query);
    }

    /**
     * Assert that termportal filters are working
     *
     * @param $dataTypeA
     */
    public function assertFilters($dataTypeA) {

        // Currently there should be 3 terms total in term collection
        $this->assertSearchResultQty(3);

        // One of them is rejected
        $this->assertSearchResultQty(1, ['processStatus' => 'rejected']);

        // Two are rejected or unprocessed
        $this->assertSearchResultQty(2, ['processStatus' => 'rejected,unprocessed']);

        // Only one 'de-de'-term which is rejected or unprocessed and having no siblings for 'en'-language
        $this->assertSearchResultQty(1, [
            'processStatus' => 'rejected,unprocessed',
            'noTermDefinedFor' => self::$english->id
        ]);

        // Two 'de-de'-terms which are rejected or unprocessed and having no siblings for 'it'-language
        $this->assertSearchResultQty(2, [
            'processStatus' => 'rejected,unprocessed',
            'noTermDefinedFor' => self::$italian->id
        ]);

        // Get customerSubset attrbiute datatype id
        $dataTypeId_customerSubset = array_column((array) $dataTypeA, 'id', 'type')['customerSubset'];

        // No terms having this attribute defined with value 'Testkunde1' and having no siblings for 'it'-language
        $this->assertSearchResultQty(0, [
            'processStatus' => 'rejected,unprocessed',
            'noTermDefinedFor' => self::$italian->id,
            'attr-' . $dataTypeId_customerSubset => 'Testkunde1'
        ]);

        // One term having this attribute defined with value 'Testkunde' and having no siblings for 'it'-language
        $this->assertSearchResultQty(1, [
            'processStatus' => 'rejected,unprocessed',
            'noTermDefinedFor' => self::$italian->id,
            'attr-' . $dataTypeId_customerSubset => 'Testkunde'
        ]);

        // Two terms having termEntryTbxId LIKE '%1111-2222-3333%'
        $this->assertSearchResultQty(2, ['termEntryTbxId' => '1111-2222-3333']);

        // One term having termTbxId = 37705a81-e0df-4209-9a12-eaddc93674e0
        $this->assertSearchResultQty(1, ['termTbxId' => '37705a81-e0df-4209-9a12-eaddc93674e0']);

        // Get 'termproposer test'-person id
        $tbxPersonId = -1;
        foreach(self::$setup->filterWindow->tbxCreatedBy as $createdBy){ /* @var stdClass $createdBy */
            if($createdBy->name === 'termproposer test'){
                $tbxPersonId = $createdBy->ids;
            }
        }
        // Two terms created by that person
        $this->assertSearchResultQty(2, ['tbxCreatedBy' => $tbxPersonId]);

        // One term created at 2019-07-15
        $this->assertSearchResultQty(1, ['tbxCreatedAt' => '2019-07-15']);

        // Three terms created since 2019-07-15
        $this->assertSearchResultQty(3, ['tbxCreatedGt' => '2019-07-15']);

        // One term created until 2019-07-15
        $this->assertSearchResultQty(1, ['tbxCreatedLt' => '2019-07-15']);
    }

    /**
     * @param $planQty
     * @param $params
     * @param int $existingPlanQty
     * @param bool $save
     */
    public function assertBatchEdit($planQty, $value, $postParams, $existingPlanQty = 0, $save = false) {

        // Get response
        $resp = static::api()->postJson('editor/attribute', $postParams);

        // Print params and get output
        $query = var_export($postParams, true);

        // Do checks
        $this->assertIsObject($resp, 'Invalid response. ' . $query);
        $this->assertObjectHasAttribute('inserted', $resp, 'Response has no inserted-prop. ' . $query);
        $this->assertIsObject($resp->inserted, '$resp->inserted is not an object. ' . $query);
        $this->assertObjectHasAttribute('id', $resp->inserted, '$resp->inserted has no id-prop. ' . $query);
        $factQty = $resp->inserted->id ? count(explode(',', $resp->inserted->id)) : 0;
        $this->assertEquals($planQty, $factQty, 'Inserted attrs qty should be ' . $planQty . ', but ' . $factQty . ' got instead. ' . $query);

        //
        $existingFact = [];

        // If $existingPlanQty arg is given and is > 0
        if ($existingPlanQty) {
            $this->assertObjectHasAttribute('existing', $resp, 'Response has no existing-prop. ' . $query);
            $this->assertIsObject($resp->existing, 'Response has no existing-prop. ' . $query);
            $existingFact = (array) $resp->existing;
            $existingFactQty = count($existingFact);
            $this->assertEquals($existingPlanQty, $existingFactQty, '$resp->existing contains '
                . $existingFactQty . ' instead of ' . $existingPlanQty . '.' . $query);

        // Else assert that there is no existing-prop in $resp
        } else $this->assertObjectNotHasAttribute('existing', $resp, 'Response should have no existing-prop. ' . $query);

        // Remember ids of inserted attrs
        $insertedIds = $resp->inserted->id;

        // Request params for PUT-request
        if ($postParams['dataType'] == 'figure') {
            static::api()->addFile('figure', $value, "image/jpg");
            $putParams = ['attrId' => $insertedIds];
        } else {
            $putParams = ['attrId' => $insertedIds, 'value' => $value];
        }

        // Batch update note-attr for termEntry-level
        $resp = static::api()->putJson('editor/attribute', $putParams);

        // Print params and get output
        $query = var_export($putParams, true);

        // Do checks
        $this->assertIsObject($resp, 'Invalid response. ' . $query);
        if ($postParams['dataType'] == 'figure') {
            $this->assertObjectHasAttribute('src', $resp, 'Response has no src-prop. ' . $query);
            $this->assertIsString($resp->src, '$resp->src is not string. ' . $query);
            $this->assertNotEmpty($resp->src, '$resp->src is empty. ' . $query);
        } else {
            $this->assertObjectHasAttribute('success', $resp, 'Response has no success-prop. ' . $query);
            $this->assertIsBool($resp->success, '$resp->success is not bool. ' . $query);
            $this->assertEquals(true, $resp->success, '$resp->success is not true. ' . $query);
        }

        // If $save arg is true
        if ($save) {

            // Pick values from inserted attrs and apply them to the existing attrs
            // Drop inserted attrs having existing attrs
            // Undraft inserted attrs having no existing attrs
            $resp = static::api()->putJson('editor/attribute', $params = [
                'attrId' => join(',', array_values($existingFact)),
                'dropId' => join(',', $dropIdA = array_keys($existingFact)),
                'draft0' => join(',', array_diff(explode(',', $insertedIds), $dropIdA))
            ]);

            // Check $resp
            $this->assertObjectHasAttribute('success', $resp, 'Attr batch-save: response has no success-prop. ' . $query);
            $this->assertIsBool($resp->success, 'Attr batch-save: $resp->success is not bool. ' . $query);
            $this->assertEquals(true, $resp->success, 'Attr batch-save: $resp->success is not true. ' . $query);

        // Else batch delete note-attr
        } else {
            $resp = static::api()->delete('editor/attribute', ['attrId' => $insertedIds]);
            $this->assertIsObject($resp, 'Attrs batch-deletion response is not an object');
            $this->assertObjectHasAttribute('updated', $resp, 'Note-attr deletion response does not contain "updated" prop');
        }
    }
}