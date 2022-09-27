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
class TbxSpecialcharsTest extends \ZfExtended_Test_ApiTestcase {
    
    /***
     * The current active collection
     * @var integer
     */
    protected static $collectionId;

    /**
     * English language
     *
     * @var stdClass
     */
    protected static $english;

    /**
     *
     */
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
    public function testTbxSpecialchars(){

        /*class_exists('editor_Utils');
        $last = editor_Utils::db()->query('SELECT `id` FROM `LEK_languageresources` ORDER BY `id` DESC LIMIT 1')->fetchColumn();
        self::$api->delete('editor/termcollection/' . $last);*/

        // [1] Create empty term collection
        $termCollection = $this->api()->postJson('editor/termcollection', [
            'name' => 'Test api collection',
            'customerIds' => $this->api()->getCustomer()->id
        ]);
        $this->assertTrue(is_object($termCollection), 'Unable to create a test collection');
        $this->assertEquals('Test api collection', $termCollection->name);

        // Remember collectionId
        self::$collectionId = $termCollection->id;

        // [2] Import tbx with single termEntry, having '&lt;' at multiple places
        $file = 'Specialchars.tbx';
        $this->api()->addFile($file, $this->api()->getFile($file), "application/xml");
        $this->api()->postJson('editor/termcollection/import', [
            'collectionId' => self::$collectionId,
            'customerIds' => $this->api()->getCustomer()->id,
            'mergeTerms' => true
        ]);

        // [3] Get language: english
        $english = $this->api()->getJson('editor/language', ['filter' => '[{"operator":"eq","value":"en","property":"rfc5646"}]']);
        $this->assertNotEmpty($english, 'Unable to load english-language');
        self::$english = $english[0];

        // [4] Find imported term by *-query and en-language id
        $termsearch = $this->api()->getJson('editor/plugins_termportal_data/search', [
            'query' => '*',
            'collectionIds' => self::$collectionId,
            'language' => self::$english->id,
            'start' => 0,
            'limit' => 1
        ]);
        $this->assertTrue(is_object($termsearch), 'No terms are found in the termcollection ' . self::$collectionId);
        $this->assertNotEmpty($termsearch->data, "No terms are found in the term collection for the search string '*'");

        // [5] Get imported term info and unset not needed props
        $json = $this->api()->postJson('editor/plugins_termportal_data/terminfo', [
            'termId' => $termsearch->data[0]->id
        ]);
        unset($json->siblings, $json->time);

        // [6] Assert qties of specialchars (original vs imported)
        $original = preg_match_all('~&lt;~', file_get_contents($this->api()->getFile($file)));
        $imported = preg_match_all('~<~', print_r($json, true));
        $original_descripGrp_transacGrp = 1;
        $original_respPerson = 2;
        $expected = $original - $original_descripGrp_transacGrp - $original_respPerson;
        $this->assertEquals($expected, $imported, 'Specialchars quantities in ' . $file .' and in /terminfo response are not equal');

        // [7] Assert qties of specialchars (original vs exported)
        $exportedTbx = $this->api()->getRaw('editor/languageresourceinstance/tbxexport', [
            'collectionId' => self::$collectionId,
            'tbxBasicOnly' => 0,
            'exportImages' => 1
        ]);
        $exported = preg_match_all('~&lt;~', $exportedTbx);
        $this->assertEquals($original, $exported, 'Specialchars quantities in original and exported files are not equal');
    }

    /**
     *
     */
    public static function tearDownAfterClass(): void {
        self::$api->login('testtermproposer');
        self::$api->delete('editor/termcollection/'.self::$collectionId);
    }
}