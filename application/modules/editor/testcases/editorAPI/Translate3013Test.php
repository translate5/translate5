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
class Translate3013Test extends \editor_Test_ApiTest {
    
    /***
     * First collection ID
     *
     * @var integer
     */
    protected static $collection1Id;

    /***
     * Second collection ID
     *
     * @var integer
     */
    protected static $collection2Id;

    /**
     * German language
     *
     * @var stdClass
     */
    protected static $german;

    /**
     * We need the termproposer to be logged in for the test
     *
     * @var string
     */
    protected static string $setupUserLogin = 'testtermproposer';

    /***
     * Test [collectionId <=> dataTypeId] mappings behaviour and influence on interaction with TermCollections and TermPortal
     *
     * @throws Zend_Http_Client_Exception
     * @throws \MittagQI\Translate5\Test\Api\Exception
     */
    public function testMapping(){

        // 1.TermCollection1: create
        $termCollection1 = static::api()->addResource([
            'name' => 'TermCollection1',
            'type' => 'termcollection',
            'serviceType' => 'editor_Services_TermCollection',
            'serviceName' => 'TermCollection',
            'resourceId' => 'editor_Services_TermCollection',
            'customerIds' => static::getTestCustomerId()
        ]);
        $this->assertTrue(is_object($termCollection1), 'Unable to create a TermCollection1');
        $this->assertEquals('TermCollection1', $termCollection1->name);
        self::$collection1Id = $termCollection1->id;

        // 2.Get number of datatype-records we have in database
        $dataTypeA = (array) static::api()->getJson('editor/attributedatatype');

        // 3.TermCollection1: importing tbx with 0 new datatype-record
        static::api()->reimportResource(self::$collection1Id, 'testfiles/TermCollection1.tbx', [
            'deleteTermsOlderThanCurrentImport' => 'on',
            'deleteProposalsLastTouchedOlderThan' => null,
        ]);

        // 4.TermCollection1: check number of mapping-records == number of datatype-records
        $mappingA = $this->getMapping(self::$collection1Id);
        $this->assertEquals(count($dataTypeA), count($mappingA), 'TermCollection1: collectionId<=>dataTypeId mappings qty is not equal to dataTypes qty');

        // 5.TermCollection2: create
        $termCollection2 = static::api()->addResource([
            'name' => 'TermCollection2',
            'type' => 'termcollection',
            'serviceType' => 'editor_Services_TermCollection',
            'serviceName' => 'TermCollection',
            'resourceId' => 'editor_Services_TermCollection',
            'customerIds' => static::getTestCustomerId()
        ]);
        $this->assertTrue(is_object($termCollection2), 'Unable to create a TermCollection2');
        $this->assertEquals('TermCollection2', $termCollection2->name);
        self::$collection2Id = $termCollection2->id;

        // 6.TermCollection2: import tbx-file having 2 attribute-records of a 1 NEW datatype-record (each is on term-level for 2 different terms)
        static::api()->reimportResource(self::$collection2Id, 'testfiles/TermCollection2.tbx', [
            'deleteTermsOlderThanCurrentImport' => 'on',
            'deleteProposalsLastTouchedOlderThan' => null,
        ]);

        // 7.TermCollection2: check number of mapping-records = number of datatype-records we had + 1
        $mappingA = $this->getMapping(self::$collection2Id);
        $this->assertEquals(count($dataTypeA) + 1, count($mappingA), 'TermCollection2: collectionid<=>dataTypeId mappings qty is not equal to dataTypes qty + 1');

        // 8.TermCollection2: get mapping-record for new datatype-record, check mapping-record's exists=1 and enabled=1
        $dataTypeId = current(array_diff(array_keys($mappingA), array_keys($dataTypeA)));
        $mapping = $mappingA[$dataTypeId];
        $this->assertEquals(1, $mapping->exists, 'TermCollection2: mapping-record\'s exists-prop is NOT 1');
        $this->assertEquals(1, $mapping->enabled, 'TermCollection2: mapping-record\'s enabled-prop is NOT 1');

        // 9.TermCollection1: check number of mapping-records = number of datatype-records we had + 1
        $mappingA = $this->getMapping(self::$collection1Id);
        $this->assertEquals(count($dataTypeA) + 1, count($mappingA), 'TermCollection1: collectionid<=>dataTypeId mappings qty is not equal to dataTypes qty + 1');

        // 10.TermCollection1: get mapping-record for new datatype-record, check mapping-record's exists=0 and enabled=0
        $mapping = $mappingA[$dataTypeId];
        $this->assertEquals(0, $mapping->exists, 'TermCollection2: mapping-record\'s exists-prop is NOT 0');
        $this->assertEquals(0, $mapping->enabled, 'TermCollection2: mapping-record\'s enabled-prop is NOT 0');

        // 11.TermCollection2: delete 1st attribute-record, check mapping-record's exists=1,
        //   yeah, still 1, as it was NOT the last attribute-record of it's dataTypeId

        //  - get german language
        $german = static::api()->getJson('editor/language', ['filter' => '[{"operator":"eq","value":"de-DE","property":"rfc5646"}]']);
        $this->assertNotEmpty($german, 'Unable to load the german-language needed for the term search.');
        self::$german = $german[0];

        // - find 2 imported terms by *-query and de-DE language id
        $termsearch = static::api()->getJson('editor/plugins_termportal_data/search', [
            'query' => '*',
            'collectionIds' => self::$collection2Id,
            'language' => self::$german->id,
            'start' => 0,
            'limit' => 2
        ]);
        $this->assertTrue(is_object($termsearch), 'No terms are found in the TermCollection2');
        $this->assertNotEmpty($termsearch->data, "No terms are found in the TermCollection2 for the search string '*'");

        // - get attrId of the 1st attribute-record to be used for further DELETE request
        $attr1Id = array_column(
            static::api()->postJson('editor/plugins_termportal_data/siblinginfo', [
                'termId' => $termsearch->data[0]->id
            ])->term->attributes,
            'id',
            'dataTypeId'
        )[$dataTypeId];

        // - delete that 1st attribute-record
        $delete = static::api()->delete('editor/attribute', ['attrId' => $attr1Id]);
        $this->assertObjectHasAttribute('updated', $delete, 'Something went wrong on attempt to DELETE 1st attribute-record having new dataTypeId');

        // - refresh mapping-record and check it's exists-prop is still 1
        $mapping = $this->getMapping(self::$collection2Id, $dataTypeId);
        $this->assertEquals(1, $mapping->exists, 'TermCollection2: mapping-record\'s exists-prop is NOT 1');

        // 12.TermCollection2: delete 2nd attribute-record, check mapping-record's exists=0,
        //    because that was the last one of it's dataTypeId in TermCollection2

        // - get attrId of a 2nd attribute-record to be used for further DELETE request
        $attr2Id = array_column(
            static::api()->postJson('editor/plugins_termportal_data/siblinginfo', [
                'termId' => $termsearch->data[1]->id
            ])->term->attributes,
            'id',
            'dataTypeId'
        )[$dataTypeId];

        // - delete that 2nd attribute-record
        $delete = static::api()->delete('editor/attribute', ['attrId' => $attr2Id]);
        $this->assertObjectHasAttribute('updated', $delete, 'Something went wrong on attempt to DELETE 2nd attribute-record having new dataTypeId');

        // - refresh mapping-record and check it's exists prop became 0
        $mapping = $this->getMapping(self::$collection2Id, $dataTypeId);
        $this->assertEquals(0, $mapping->exists, 'TermCollection2: mapping-record\'s exists-prop is NOT 0');

        // 13.TermCollection2: create attribute-record having new dataTypeId for 1st term,
        //    check mapping-record's exists=1 (back 1)

        // - create attribute-record
        $attrcreate = static::api()->postJson('editor/attribute', [
            'termId' => $termsearch->data[0]->id,
            'level' => 'term',
            'dataType' => $dataTypeId
        ]);
        $this->assertTrue(is_object($attrcreate)
            && is_numeric($attrcreate->inserted->id), 'Unable to create attribute having new dataTypeId');

        // - refresh mapping-record and check it's exists prop became back 1
        $mapping = $this->getMapping(self::$collection2Id, $dataTypeId);
        $this->assertEquals(1, $mapping->exists, 'TermCollection2: mapping-record\'s exists-prop is NOT 1');

        // 14.TermCollection2: disable new dataTypeId
        //  - refresh mapping, check mapping-record's enabled=0 and exists=0
        //  - check attribute-record not exist anymore

        // - disable new dataTypeId
        $response = static::api()->putJson('editor/collectionattributedatatype?answer=ok', [
            'mappingId' => $mapping->mappingId,
            'enabled' => 0
        ]);
        $this->assertEquals(1, $response->success, 'TermCollection2: new dataTypeId was not successfully disabled');

        // - refresh mapping-record and check mapping-record's enabled=0 and exists=0,
        $mapping = $this->getMapping(self::$collection2Id, $dataTypeId);
        $this->assertEquals(0, $mapping->exists, 'TermCollection2: mapping-record\'s exists-prop is NOT 0');
        $this->assertEquals(0, $mapping->enabled, 'TermCollection2: mapping-record\'s enabled-prop is NOT 0');

        // - check attribute-record having new dataTypeId not exists anymore
        $resp = static::api()->getJson('editor/plugins_termportal_data/search', [
            'query' => '*',
            'collectionIds' => self::$collection2Id,
            'language' => self::$german->id,
            'attr-' . $dataTypeId => '',
            'start' => 0,
            'limit' => 10
        ]);
        $this->assertIsObject($resp, 'Invalid response search response. ');
        $this->assertObjectHasAttribute('data', $resp, 'Response has no data-prop. ');
        $this->assertIsArray($resp->data, '$resp->data is not an array. ');
        $this->assertEquals(0, count($resp->data), 'Results qty should be ' . 0 . ', but ' . count($resp->data) . ' got instead. ');

        // 15.TermCollection1: attempt to create that attribute-record, get an error saying it's not enabled

        // - get info about the term, imported into TermCollection1
        $termsearch = static::api()->getJson('editor/plugins_termportal_data/search', $query = [
            'query' => '*',
            'collectionIds' => self::$collection1Id,
            'language' => self::$german->id,
            'start' => 0,
            'limit' => 1
        ]);
        $this->assertIsObject($termsearch, 'Invalid response search response. ');
        $this->assertObjectHasAttribute('data', $termsearch, 'Response has no data-prop. ');
        $this->assertIsArray($termsearch->data, '$resp->data is not an array. ');
        $this->assertEquals(1, count($termsearch->data), 'Results qty should be ' . 1 . ', but ' . count($termsearch->data) . ' got instead. ');

        // - create attribute-record having new dataTypeId for that term (1nd attempt)
        $attrcreate = static::api()->postJson('editor/attribute', [
            'termId' => $termsearch->data[0]->id,
            'level' => 'term',
            'dataType' => $dataTypeId
        ], null, true, true);

        // - get the error saying value of collectionId-param is not in the list of allowed values
        $this->assertEquals('E2004', $attrcreate->errorCode, 'Attribute was created despite having dataTypeId disabled for TermCollection1');

        // 16.TermCollection1: set enabled=1 for mapping-record for new dataTypeId
        $mapping = $this->getMapping(self::$collection1Id, $dataTypeId);
        $this->assertEquals(0, $mapping->exists, 'TermCollection1: mapping-record\'s exists-prop is NOT 0');
        $response = static::api()->putJson('editor/collectionattributedatatype?answer=ok', [
            'mappingId' => $mapping->mappingId,
            'enabled' => 1
        ]);
        $this->assertEquals(1, $response->success, 'TermCollection1: new dataTypeId was not successfully enabled');

        // 17.TermCollection1: try again to create attribute-record
        $attrcreate = static::api()->postJson('editor/attribute', [
            'termId' => $termsearch->data[0]->id,
            'level' => 'term',
            'dataType' => $dataTypeId
        ]);
        $this->assertTrue(is_object($attrcreate)
            && is_numeric($attrcreate->inserted->id), 'Unable to create attribute having new dataTypeId in TermCollection1');

        // 18.TermCollection1: check mapping-record's enabled=1 and exists=1
        $mapping = $this->getMapping(self::$collection1Id, $dataTypeId);
        $this->assertEquals(1, $mapping->exists, 'TermCollection1: mapping-record\'s exists-prop is NOT 0');
        $this->assertEquals(1, $mapping->enabled, 'TermCollection1: mapping-record\'s enabled-prop is NOT 0');
    }

    /**
     * Get mapping-record for a given $collectionId and $dataTypeId
     * If $dataTypeId arg is not given - array of mappings for given $collectionId is returned
     *
     * @param int $collectionId
     * @param int $dataTypeId
     * @return mixed
     * @throws Zend_Http_Client_Exception
     */
    public function getMapping(int $collectionId, int $dataTypeId = 0) {

        // Get response
        $response = static::api()->getJson('editor/collectionattributedatatype', ['collectionId' => $collectionId]);

        // Pick mapping info by $dataTypeId, if given, else return mappings array
        return $dataTypeId ? $response->mappingA->$dataTypeId : (array) $response->mappingA;
    }

    /**
     * Clean up
     *
     * @throws Zend_Http_Client_Exception
     */
    public static function afterTests(): void {
        static::api()->login('testtermproposer');
        static::api()->delete('editor/termcollection/'.self::$collection1Id);
        static::api()->delete('editor/termcollection/'.self::$collection2Id);
    }
}