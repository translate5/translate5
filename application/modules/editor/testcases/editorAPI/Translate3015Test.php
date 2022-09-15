<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/***
 * Test the merging of the same attributes at 1.
 * On entry level, there are 2 attributes which will be merged as 1:
 *    	<descrip type="subjectField">manufacturing</descrip>
 *      <descrip type="subjectField">production</descrip>
 *
 * On term level, there are also 2 attributes which will be merged as 1:
 *      <termNote type="geographicalUsage">Canada</termNote>
 *      <termNote type="geographicalUsage">USA</termNote>
 *
 */
class Translate3015Test extends editor_Test_JsonTest {

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
     */
    public function testTbxImport(){

        $fileName = 'TBX-basic-sample.tbx';

        $termCollection = $this->api()->requestJson('editor/termcollection', 'POST', [
            'name' => __CLASS__,
            'customerIds' => $this->api()->getCustomer()->id
        ]);
        $this->assertTrue(is_object($termCollection), 'Unable to create a test collection');
        $this->assertEquals(__CLASS__, $termCollection->name);

        self::$collId = $termCollection->id;


        $this->api()->addFile($fileName, $this->api()->getFile($fileName), "application/xml");
        $this->api()->requestJson('editor/termcollection/import', 'POST', [
            'collectionId' =>self::$collId,
            'customerIds' => $this->api()->getCustomer()->id,
            'mergeTerms'=>true
        ]);

        //export the generated file
        $response = $this->api()->requestJson('editor/termcollection/export?format=1', 'POST', array('collectionId' =>self::$collId));

        $this->assertTrue(is_object($response),"Unable to export the terms by term collection");
        $this->assertNotEmpty($response->filedata,"The exported tbx file by collection is empty");

        if($this->api()->isCapturing()) {
            file_put_contents($this->api()->getFile('/E_'.$fileName, null, false), $response->filedata);
        }

        $expected = $this->api()->getFileContent('E_'.$fileName);
        $actual = $response->filedata;

        //check for differences between the expected and the actual content
        $this->assertEquals($expected, $actual, "The expected file an the result file does not match.Test file name: ".$fileName);

        $attributes=$this->api()->requestJson('editor/termcollection/testgetattributes', 'GET', array('collectionId' =>self::$collId));

        $termCount = 1;
        $termsAtributeCount = 10;
        $termsEntryAtributeCount = 10;
        $languageAtributeCount = 1;

        //check if the generated attributes are matching
        $this->assertTrue($termCount === (int)$attributes->termsCount, $fileName.' file test.Invalid number of terms created.Terms count:'.$attributes->termsCount.', expected:'.$termCount);
        $this->assertTrue($termsAtributeCount ===  (int)$attributes->termsAtributeCount, $fileName.' file test.Invalid number of term attribute created.Terms attribute count:'.$attributes->termsAtributeCount.', expected:'.$termsAtributeCount);
        $this->assertTrue($termsEntryAtributeCount === (int)$attributes->termsEntryAtributeCount, $fileName.' file test.Invalid and number of entry attribute created.Terms entry attribute count:'.$attributes->termsEntryAtributeCount.', expected:'.$termsEntryAtributeCount);
        $this->assertTrue($languageAtributeCount === (int)$attributes->languageAtributeCount, $fileName.' file test.Invalid and number of language level attribute created.Language level attribute count:'.$attributes->languageAtributeCount.', expected:'.$languageAtributeCount);
    }

    public static function tearDownAfterClass(): void {
        self::$api->login('testmanager');
        self::$api->cleanup && self::$api->requestJson('editor/termcollection/'.self::$collId,'DELETE');
    }

}
