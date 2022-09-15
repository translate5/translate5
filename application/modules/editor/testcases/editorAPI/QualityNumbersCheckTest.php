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
class QualityNumbersCheckTest extends editor_Test_JsonTest {

    public static function setUpBeforeClass(): void {
        // Prepare initial API instance
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);

        // Check app state
        $appState = self::assertAppState();

        // Assert users. Last authed user is testmanager
        self::assertNeededUsers();
        self::assertLogin('testmanager');
    }

    public function testTask0(){
        $this->performTestForTask('num1..num11 except num7 --- de-DE en-US', 10);
    }

    public function testTask1(){
        $this->performTestForTask('num7 --- de-DE ru-RU', 1);
    }

    public function testTask2(){
        $this->performTestForTask('num12,num13 --- en-GB de-DE', 2);
    }

    private function performTestForTask(string $taskName, int $expectedSegmentQuantity){

        // Detect source and target languages from filename
        $lang = [];
        preg_match('~ --- ([^ ]+) ([^ ]+)$~', $taskName, $lang);

        // Get absolute file path to be used as 1st arg in below addImportFile() call
        $absolutePath = self::$api->getFile('testfiles/' . $taskName . '.csv');

        // Print the step where we are
        // error_log("\nCreating task based on file: 'testfiles/" . $taskName . ".csv', source lang: '{$lang[1]}', target lang: '{$lang[2]}'\n");

        // Add csv-file for import
        self::$api->addImportFile($absolutePath);

        // Do import
        self::$api->import([
            'sourceLang' => $lang[1],
            'targetLang' => $lang[2],
            'edit100PercentMatch' => true,
            'lockLocked' => 1,
        ]);

        // Get task
        $task = self::$api->getTask();

        // Print the step where we are
        // error_log("\nTesting task based on file: 'testfiles/" . $taskName . ".csv'\n");

        // Open task for whole testcase
        self::$api->requestJson('editor/task/' . $task->id, 'PUT', ['userState' => 'edit', 'id' => $task->id]);

        // Get segments and check their quantity
        $segmentQuantity = count(self::$api->requestJson('editor/segment?page=1&start=0&limit=10'));
        static::assertEquals($expectedSegmentQuantity, $segmentQuantity, 'Not enough segments in the imported task');

        // Check qualities
        $jsonFile = $taskName.'.json';
        $tree = self::$api->getJsonTree('/editor/quality', [], $jsonFile);
        $treeFilter = editor_Test_Model_Filter::createSingle('qtype', 'numbers');
        $this->assertModelEqualsJsonFile('FilterQuality', $jsonFile, $tree, '', $treeFilter);

        // Close task
        self::$api->requestJson('editor/task/' . $task->id, 'PUT', ['userState' => 'open', 'id' => $task->id]);

        // Print the step where we are
        // error_log("\nDeleting task based on file: 'testfiles/" . $taskName . ".csv'\n");

        // Delete task
        self::$api->requestJson('editor/task/' . $task->id, 'DELETE');
    }
}