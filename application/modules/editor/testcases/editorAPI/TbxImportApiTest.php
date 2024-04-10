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
 * Test the tbx import into the term collection with multiple tbx files with modified and new terms.
 * TODO: Since the tbx export does not provide the tbx file with term entry attributes and term attributes
 *       the test can not relay on tbx content test.
 *       When in the tbx export result the attributes are include this can be changed
 */
class TbxImportApiTest extends \editor_Test_ApiTest
{
    /***
     * Test the tbx import.
     * The test will import different tbx files, with different content in it.
     * The test will test against the generated tbx content after tbx import however the number
     * of the created attributes after the import
     */
    public function testTbxImport()
    {
        $termCollection = static::api()->postJson('editor/termcollection', [
            'name' => 'Test api collection',
            'customerIds' => static::getTestCustomerId(),
        ]);
        $this->assertTrue(is_object($termCollection), 'Unable to create a test collection');
        $this->assertEquals('Test api collection', $termCollection->name);

        $collectionId = $termCollection->id;

        //import the first tbx file,
        $this->singleTest($collectionId, 'Term.tbx', 13, 63, 6, 5);

        //change existing term entry attribute
        //change existing term attribute
        //change existing term content
        //add new term to term collection
        $this->singleTest($collectionId, 'Term1.tbx', 14, 68, 6, 5);

        // different term entry id, different term id, same language and term content -> update the term and
        // check if the other terms in the tbx term entry can be merged
        // Merge: the term <term id="462bed50-6779-4cb1-be6b-223e78b54f26">Desk</term> will be merged to: <term id="462bed50-6779-4cb1-be6b-223e78b54f26">Table</term>
        // reason is because other terms in the importing term entry are merged and the found term entry is used to merge "Desk" to "Table"
        $this->singleTest($collectionId, 'Term2.tbx', 14, 68, 6, 5);

        //add new terms to the term collection
        //handle the unknown tags
        $this->singleTest($collectionId, 'Export.tbx', 16, 88, 10, 9);

        // Change term content and add 2 new attributes (admin and note attributes)
        // On the second term, add 2 new attributes (termNote with custom type and note)
        $this->singleTest($collectionId, 'ExportTermChange.tbx', 16, 91, 10, 9);

        // clean up
        static::api()->login('testmanager');
        static::api()->delete('editor/termcollection/' . $collectionId);
    }

    /***
     *
     * Run single test for each file. Test against the content and attributes count
     *
     * @param int $collectionId
     * @param string $fileName
     * @param int $termCount : the count of the terms after the import
     * @param int $termsAtributeCount : the count afo the term attributes after the import
     * @param int $termsEntryAtributeCount : the count of the term entry attributes after the import
     * @param int $languageAtributeCount: the count of the language level attributes after the import
     */
    private function singleTest(int $collectionId, string $fileName, int $termCount, int $termsAtributeCount, int $termsEntryAtributeCount, int $languageAtributeCount)
    {
        static::api()->addFile($fileName, static::api()->getFile($fileName), "application/xml");
        static::api()->postJson('editor/termcollection/import', [
            'collectionId' => $collectionId,
            'customerIds' => static::getTestCustomerId(),
            'mergeTerms' => true,
        ]);

        //export the generated file
        $response = static::api()->postJson('editor/termcollection/export?format=1', [
            'collectionId' => $collectionId,
        ]);

        $this->assertTrue(is_object($response), "Unable to export the terms by term collection");
        $this->assertNotEmpty($response->filedata, "The exported tbx file by collection is empty");

        if (static::api()->isCapturing()) {
            file_put_contents(static::api()->getFile('/E_' . $fileName, null, false), $response->filedata);
        }
        $expected = static::api()->getFileContent('E_' . $fileName);
        $actual = $response->filedata;

        //check for differences between the expected and the actual content
        $this->assertEquals($expected, $actual, "The expected file an the result file does not match.Test file name: " . $fileName);

        $attributes = static::api()->getJson('editor/termcollection/testgetattributes', [
            'collectionId' => $collectionId,
        ]);

        //check if the generated attributes are matching
        $this->assertTrue($termCount == $attributes->termsCount, $fileName . ' file test.Invalid number of terms created.Terms count:' . $attributes->termsCount . ', expected:' . $termCount);
        $this->assertTrue($termsAtributeCount == $attributes->termsAtributeCount, $fileName . ' file test.Invalid number of term attribute created.Terms attribute count:' . $attributes->termsAtributeCount . ', expected:' . $termsAtributeCount);
        $this->assertTrue($termsEntryAtributeCount == $attributes->termsEntryAtributeCount, $fileName . ' file test.Invalid and number of entry attribute created.Terms entry attribute count:' . $attributes->termsEntryAtributeCount . ', expected:' . $termsEntryAtributeCount);
        $this->assertTrue($languageAtributeCount == $attributes->languageAtributeCount, $fileName . ' file test.Invalid and number of language level attribute created.Language level attribute count:' . $attributes->languageAtributeCount . ', expected:' . $languageAtributeCount);
    }
}
