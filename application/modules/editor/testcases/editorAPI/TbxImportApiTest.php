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
 * Test the tbx import into the term collection with multiple tbx files with modified and new terms.
 * TODO: Since the tbx export does not provide the tbx file with term entry attributes and term attributes
 *       the test can not relay on tbx content test.
 *       When in the tbx export result the attributes are include this can be changed
 */
class TbxImportApiTest extends \ZfExtended_Test_ApiTestcase {

    /***
     * The current active collection
     * @var integer
     */
    protected static int $collId;

    public static function setUpBeforeClass(): void {
        self::$api= new ZfExtended_Test_ApiHelper(__CLASS__);
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        self::assertCustomer();
    }

    /***
     * Test the tbx import.
     * The test will import different tbx files, with different content in it.
     * The test will test against the generated tbx content after tbx import however the number
     * of the created attributes after the import
     */
    public function testTbxImport(){

        $termCollection = $this->api()->requestJson('editor/termcollection', 'POST', ['name' => 'Test api collection', 'customerIds' => $this->api()->getCustomer()->id]);
        $this->assertTrue(is_object($termCollection), 'Unable to create a test collection');
        $this->assertEquals('Test api collection', $termCollection->name);

        self::$collId = $termCollection->id;

        //import the first tbx file, OK
        $this->singleTest('Term.tbx', 8, 27, 18);

        //change existing term entry attribute
        //change existing term attribute
        //change existing term content
        //add new term to term collection
        $this->singleTest('Term1.tbx', 9, 29, 20);

        //different term entry id, different term id, same language and term content -> update the term and
        //check if the other terms in the tbx term entry can be merged
//        $this->singleTest('Term2.tbx', 11, 101, 21);

        //add new terms to the term collection
        //handle the unknown tags
//        $this->singleTest('Export.tbx', 13, 128, 37);

        //one term attribute is removed and the term text is changed
        //add two new term attributes
//        $this->singleTest('ExportTermChange.tbx', 13, 131, 37);
    }

    /***
     * Run single test for each file. Test against the content and attributes count
     *
     * @param string $fileName
     * @param int $termCount : the count of the terms after the import
     * @param int $termsAtributeCount : the count afo the term attributes after the import
     * @param int $termsEntryAtributeCount : the count of the term entry attributes after the import
     */
    private function singleTest($fileName,$termCount,$termsAtributeCount,$termsEntryAtributeCount)
    {
        $this->api()->addFile($fileName, $this->api()->getFile($fileName), "application/xml");
        $this->api()->requestJson('editor/termcollection/import', 'POST', ['collectionId' => self::$collId, 'customerIds' => [$this->api()->getCustomer()->id],'mergeTerms' => true]);

        //export the generated file
        $response = $this->api()->requestJson('editor/termcollection/export', 'POST', ['collectionId' => self::$collId]);

        $this->assertTrue(is_object($response),"Unable to export the terms by term collection");
        $this->assertNotEmpty($response->filedata,"The exported tbx file by collection is empty");

        //file_put_contents($this->api()->getFile('/E_'.$fileName, null, false), $response->filedata);
        $expected = $this->api()->getFileContent('E_'.$fileName);
        $actual = $response->filedata;

        //check for differences between the expected and the actual content
        $this->assertEquals($expected, $actual, "The expected file an the result file does not match.Test file name: ".$fileName);

//        $attributes = $this->api()->requestJson('editor/termcollection/testgetattributes', 'GET', ['collectionId' => self::$collId]);
//
//        //check if the generated attributes are matching
//        $this->assertTrue($termCount == $attributes->termsCount, $fileName.' file test.Invalid number of terms created.Terms count:'.$attributes->termsCount.', expected:'.$termCount);
//        $this->assertTrue($termsAtributeCount == $attributes->termsAtributeCount, $fileName.' file test.Invalid number of term attribute created.Terms attribute count:'.$attributes->termsAtributeCount.', expected:'.$termsAtributeCount);
//        $this->assertTrue($termsEntryAtributeCount == $attributes->termsEntryAtributeCount, $fileName.' file test.Invalid and number of entry attribute created.Terms entry attribute count:'.$attributes->termsEntryAtributeCount.', expected:'.$termsEntryAtributeCount);
    }

    public static function tearDownAfterClass(): void {
        self::$api->login('testmanager');
        self::$api->requestJson('editor/termcollection/'.self::$collId,'DELETE');
    }

}
