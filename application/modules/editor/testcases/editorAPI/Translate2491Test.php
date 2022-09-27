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
 * Testcase for TRANSLATE-2540
 */
class Translate2491Test extends editor_Test_JsonTest {

    /***
     * The current active collection
     * @var integer
     */
    protected static $collectionId;

    /**
     * @throws Zend_Exception
     */
    public static function setUpBeforeClass(): void {

        // Prepare api instance
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);

        // Last authed user is testmanager
        self::assertNeededUsers();

        // Load customer
        self::assertCustomer();
    }

    /**
     * Test the qualities fetched for a segment
     */
    public function testTermsTransfer(){

        // [1] create empty term collection
        $termCollection = $this->api()->postJson('editor/termcollection', [
            'name' => 'Test api collection 2',
            'customerIds' => $this->api()->getCustomer()->id
        ]);
        $this->assertTrue(is_object($termCollection), 'Unable to create a test collection');
        $this->assertEquals('Test api collection 2', $termCollection->name);

        // Remember collectionId
        self::$collectionId = $termCollection->id;

        // [2] import test tbx
        $this->api()->addFile('Term.tbx', $this->api()->getFile('Term.tbx'), "application/xml");
        $this->api()->postJson('editor/termcollection/import', [
            'collectionId' => self::$collectionId,
            'customerIds' => $this->api()->getCustomer()->id,
            'mergeTerms' => true
        ]);

        // [3] get languages: german
        $german = $this->api()->getJson('editor/language', ['filter' => '[{"operator":"eq","value":"de-DE","property":"rfc5646"}]']);
        $this->assertNotEmpty($german, 'Unable to load the german-language needed for the term search.');
        $german = $german[0];

        // english
        $english = $this->api()->getJson('editor/language', ['filter' => '[{"operator":"eq","value":"en-GB","property":"rfc5646"}]']);
        $this->assertNotEmpty($english, 'Unable to load english-language needed for use ');
        $english = $english[0];

        // Log in as proposer
        self::$api->login('testtermproposer');
        self::assertLogin('testtermproposer');

        // [4] find imported term by *-query and en-EN language id
        $termsearch = $this->api()->getJson('editor/plugins_termportal_data/search', [
            'query' => '*',
            'collectionIds' => self::$collectionId,
            'language' => $english->id,
            'start' => 0,
            'limit' => 10
        ]);

        $this->assertTrue(is_object($termsearch), 'No terms are found in the termcollection ' . self::$collectionId);
        $this->assertNotEmpty($termsearch->data, "No terms are found in the term collection for the search string '*'");

        // Transfer terms to main Translate5 app
        $transfer = $this->api()->postJson('editor/plugins_termportal_data/transfer', $taskCfg = [
            'projectName' => '2 terms selected',
            'targetLang' =>  $german->id,
            'translated' =>  0,
            'definition' =>  1,
            'clientId' =>  $this->api()->getCustomer()->id,
            'sourceLang' =>  $english->id,
            'terms' => 'all',
            'except' => array_reverse(array_column($termsearch->data, 'id'))[0],
        ]);

        // Wait for import
        $this->api()->setTask($task = $transfer->step1->rows->projectTasks[0]);
        $task->originalSourceLang = $taskCfg['sourceLang'];
        $task->originalTargetLang = $taskCfg['targetLang'];

        if($task->taskType == ZfExtended_Test_ApiHelper::INITIAL_TASKTYPE_PROJECT) {
            $this->api()->checkProjectTasksStateLoop();
        }
        else {
            $this->api()->checkTaskStateLoop();
        }

        // Open task for whole testcase
        $this->api()->setTaskToEdit($task->id);

        // Get segments and check their quantity (1 term and 1 definition-attr for that term, so total 2)
        $segments = $this->api()->getSegments(null, 10);
        static::assertEquals(count($segments), 2, 'Not enough segments in the imported task');

        // Set 'Term1 DE' as value for targetEdit-field for segment 1
        // Attr-segment goes before term-segment, so that index 1 is used here
        $this->api()->putJson('editor/segment/'.$segments[1]->id, $this->api()->prepareSegmentPut('targetEdit', 'Term1 DE', $segments[1]->id));

        // Close task
        $this->api()->setTaskToOpen($task->id);

        // Re-import into termcollection
        $this->api()->get('editor/task/export/id/'.$task->id . '?format=transfer');

        // [10] search for the term attributes
        $terminfo = $this->api()->postJson('editor/plugins_termportal_data/terminfo', ['termId' => $termsearch->data[0]->id]);
        $this->assertTrue(is_object($terminfo), 'No data returned by terminfo-call');
        $this->assertTrue(isset($terminfo->siblings->data[1]->term), 'Path "siblings->data[1]->term" not exists in terminfo response');
        $this->assertEquals($terminfo->siblings->data[1]->term, 'Term1 DE', 'German translation for term "Term1 EN" was not imported');
    }

    /**
     * Cleanup
     */
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        self::$api->deleteTask($task->id);
        // Drop termCollection
        self::$api->delete('editor/termcollection/' . self::$collectionId);
    }
}
