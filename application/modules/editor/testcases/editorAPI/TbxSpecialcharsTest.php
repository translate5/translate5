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
class TbxSpecialcharsTest extends \editor_Test_ApiTest {
    
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
     * We need the termproposer to be logged in for the test
     * @var string
     */
    protected static string $setupUserLogin = 'testtermproposer';

    /***
     * Test term and term attribute proposals.
     */
    public function testTbxSpecialchars(){

        /*class_exists('editor_Utils');
        $last = editor_Utils::db()->query('SELECT `id` FROM `LEK_languageresources` ORDER BY `id` DESC LIMIT 1')->fetchColumn();
        static::api()->delete('editor/termcollection/' . $last);*/

        // [1] Create empty term collection
        $termCollection = static::api()->postJson('editor/termcollection', [
            'name' => 'Test api collection',
            'customerIds' => static::getTestCustomerId()
        ]);
        $this->assertTrue(is_object($termCollection), 'Unable to create a test collection');
        $this->assertEquals('Test api collection', $termCollection->name);

        // Remember collectionId
        $collectionId = $termCollection->id;

        // [2] Import tbx with single termEntry, having '&lt;' at multiple places
        $file = 'Specialchars.tbx';
        static::api()->addFile($file, static::api()->getFile($file), "application/xml");
        static::api()->postJson('editor/termcollection/import', [
            'collectionId' => $collectionId,
            'customerIds' => static::getTestCustomerId(),
            'mergeTerms' => true
        ]);

        // [3] Get language: english
        $english = static::api()->getJson('editor/language', ['filter' => '[{"operator":"eq","value":"en","property":"rfc5646"}]']);
        $this->assertNotEmpty($english, 'Unable to load english-language');
        self::$english = $english[0];

        // [4] Find imported term by *-query and en-language id
        $termsearch = static::api()->getJson('editor/plugins_termportal_data/search', [
            'query' => '*',
            'collectionIds' => $collectionId,
            'language' => self::$english->id,
            'start' => 0,
            'limit' => 1
        ]);
        $this->assertTrue(is_object($termsearch), 'No terms are found in the termcollection ' . $collectionId);
        $this->assertNotEmpty($termsearch->data, "No terms are found in the term collection for the search string '*'");

        // [5] Get imported term info and unset not needed props
        $json = static::api()->postJson('editor/plugins_termportal_data/terminfo', [
            'termId' => $termsearch->data[0]->id
        ]);
        unset($json->siblings, $json->time);

        // [6] Assert qties of specialchars (original vs imported)
        $original = preg_match_all('~&lt;~', file_get_contents(static::api()->getFile($file)));
        $imported = preg_match_all('~<~', print_r($json, true));
        $original_descripGrp_transacGrp = 1;
        $original_respPerson = 2;
        $expected = $original - $original_descripGrp_transacGrp - $original_respPerson;
        $this->assertEquals($expected, $imported, 'Specialchars quantities in ' . $file .' and in /terminfo response are not equal');

        // [7] Assert qties of specialchars (original vs exported)
        $result = static::api()->getRaw('editor/languageresourceinstance/tbxexport', [
            'collectionId' => $collectionId,
            'tbxBasicOnly' => 0,
            'exportImages' => 1
        ]);
        $this->assertFalse($this->api()->isJsonResultError($result), 'TBX export could not be requested');
        $exported = preg_match_all('~&lt;~', $result->data);
        $this->assertEquals($original, $exported, 'Specialchars quantities in original and exported files are not equal');

        static::api()->login('testtermproposer');
        static::api()->delete('editor/termcollection/'.$collectionId);
    }
}