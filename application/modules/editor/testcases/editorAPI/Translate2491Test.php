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

use MittagQI\Translate5\Test\Api\Helper;
/**
 * Testcase for TRANSLATE-2540
 */
class Translate2491Test extends editor_Test_JsonTest {

    public function testTermsTransfer(){

        // [1] create empty term collection
        $termCollection = static::api()->postJson('editor/termcollection', [
            'name' => 'Test api collection 2',
            'customerIds' => static::getTestCustomerId()
        ]);
        $this->assertTrue(is_object($termCollection), 'Unable to create a test collection');
        $this->assertEquals('Test api collection 2', $termCollection->name);

        // Remember collectionId
        $collectionId = $termCollection->id;

        // [2] import test tbx
        static::api()->addFile('Term.tbx', static::api()->getFile('Term.tbx'), "application/xml");
        static::api()->postJson('editor/termcollection/import', [
            'collectionId' => $collectionId,
            'customerIds' => static::getTestCustomerId(),
            'mergeTerms' => true
        ]);

        // [3] get languages: german
        $german = static::api()->getJson('editor/language', ['filter' => '[{"operator":"eq","value":"de-DE","property":"rfc5646"}]']);
        $this->assertNotEmpty($german, 'Unable to load the german-language needed for the term search.');
        $german = $german[0];

        // english
        $english = static::api()->getJson('editor/language', ['filter' => '[{"operator":"eq","value":"en-GB","property":"rfc5646"}]']);
        $this->assertNotEmpty($english, 'Unable to load english-language needed for use ');
        $english = $english[0];

        // Log in as proposer
        static::api()->login('testtermproposer');
        self::assertLogin('testtermproposer');

        // [4] find imported term by *-query and en-EN language id
        $termsearch = static::api()->getJson('editor/plugins_termportal_data/search', [
            'query' => '*',
            'collectionIds' => $collectionId,
            'language' => $english->id,
            'start' => 0,
            'limit' => 10
        ]);

        $this->assertTrue(is_object($termsearch), 'No terms are found in the termcollection ' . $collectionId);
        $this->assertNotEmpty($termsearch->data, "No terms are found in the term collection for the search string '*'");

        // Transfer terms to main Translate5 app
        $transfer = static::api()->postJson('editor/plugins_termportal_data/transfer', $taskCfg = [
            'projectName' => '2 terms selected',
            'targetLang' =>  $german->id,
            'translated' =>  0,
            'definition' =>  1,
            'clientId' =>  static::getTestCustomerId(),
            'sourceLang' =>  $english->id,
            'terms' => 'all',
            'except' => array_reverse(array_column($termsearch->data, 'id'))[0],
        ]);

        // Mimic a task-import
        $task = $transfer->step1->rows->projectTasks[0];
        static::api()->waitForTaskImported($task);

        // Open task for whole testcase
        static::api()->setTaskToEdit($task->id);

        // Get segments and check their quantity (1 term and 1 definition-attr for that term, so total 2)
        $segments = static::api()->getSegments(null, 10);
        static::assertEquals(count($segments), 2, 'Not enough segments in the imported task');

        // Set 'Term1 DE' as value for targetEdit-field for segment 1
        // Attr-segment goes before term-segment, so that index 1 is used here
        static::api()->saveSegment($segments[1]->id, 'Term1 DE');

        // Close task
        static::api()->setTaskToOpen($task->id);

        // Re-import into termcollection
        static::api()->get('editor/task/export/id/'.$task->id . '?format=transfer');

        // [10] search for the term attributes
        $terminfo = static::api()->postJson('editor/plugins_termportal_data/terminfo', ['termId' => $termsearch->data[0]->id]);
        $this->assertTrue(is_object($terminfo), 'No data returned by terminfo-call');
        $this->assertTrue(isset($terminfo->siblings->data[1]->term), 'Path "siblings->data[1]->term" not exists in terminfo response');
        $this->assertEquals($terminfo->siblings->data[1]->term, 'Term1 DE', 'German translation for term "Term1 EN" was not imported');

        $task = static::api()->getTask();
        static::api()->deleteTask($task->id);
        // Drop termCollection
        static::api()->delete('editor/termcollection/' . $collectionId);
    }
}
