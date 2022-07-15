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
 * Testcase for TRANSLATE-2538
 */
class QualitySpellCheckTest extends editor_Test_JsonTest {

    /**
     * @var array
     */
    public static $taskA = [];

    /**
     * @throws Zend_Exception
     */
    public static function setUpBeforeClass(): void {

        // Prepare initial API instance
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);

        // Check app state
        $appState = self::assertAppState();

        // Assert users. Last authed user is testmanager
        self::assertNeededUsers();
        self::assertLogin('testmanager');

        // Array of csv-files for each to be imported as separate task
        $csvA = [
            'ten segments --- de-DE en-US' => 10, // 10 - expected qty of segments to be imported
        ];

        // Foreach csv file specified in $numA array
        foreach ($csvA as $name => $segmentQty) {

            // Use separate clone of api object for use with each task
            $api = self::$taskA[$name]['api'] = clone self::$api;

            // Setup planned qty of segments to be imported for further check
            self::$taskA[$name]['planQty'] = $segmentQty;

            // Detect source and target languages from filename
            preg_match('~ --- ([^ ]+) ([^ ]+)$~', $name, $lang);

            // Get absolute file path to be used as 1st arg in below addImportFile() call
            $abs = $api->getFile($rel = 'testfiles/' . $name . '.csv');

            // Print the step where we are
            // error_log("\nCreating task based on file: '$rel', source lang: '{$lang[1]}', target lang: '{$lang[2]}'\n");

            // Add csv-file for import
            $api->addImportFile($abs);

            // Do import
            $api->import([
                'sourceLang' => $lang[1],
                'targetLang' => $lang[2],
                'edit100PercentMatch' => true,
                'lockLocked' => 1,
            ]);

            // Get task
            $task = self::$taskA[$name]['task'] = $api->getTask();

            // Print imported task ID
            // error_log("Task ID: {$task->id} \n");
        }
    }

    /**
     * Tests the generally availability and validity of the filter tree
     */
    public function testFilterQualityTrees(){

        // Foreach task based on imported csv file
        foreach (self::$taskA as $name => $env) {

            // Print the step where we are
            // error_log("\nTesting task based on file: $name.csv\n");

            // Open task for whole testcase
            $env['api']->requestJson('editor/task/' . $env['task']->id, 'PUT', ['userState' => 'edit', 'id' => $env['task']->id]);

            // Get segments and check their quantity
            $factQty = count($env['api']->requestJson('editor/segment?page=1&start=0&limit=10'));
            static::assertEquals($factQty, $env['planQty'], 'Not enough segments in the imported task');

            // Check qualities
            $jsonFile = "$name.json";
            $tree = $env['api']->getJsonTree('/editor/quality', [], $jsonFile);
            $treeFilter = editor_Test_Model_Filter::createSingle('qtype', 'spellcheck');
            $this->assertModelEqualsJsonFile('FilterQuality', $jsonFile, $tree, '', $treeFilter);

            // Close task
            $env['api']->requestJson('editor/task/' . $env['task']->id, 'PUT', ['userState' => 'open', 'id' => $env['task']->id]);
        }
    }

    /**
     * Cleanup
     */
    public static function tearDownAfterClass(): void {

        // Foreach task based on imported csv file
        foreach (self::$taskA as $name => $env) {

            // Print the step where we are
            // error_log("\nDeleting task based on file: $name.csv\n");

            // Delete task
            $env['api']->requestJson('editor/task/' . $env['task']->id, 'DELETE');
        }
    }
}