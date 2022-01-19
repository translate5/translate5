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
class QualitySegmentEmptyTest extends editor_Test_JsonTest {

    /**
     * @var array
     */
    public static $segments = [];

    /**
     * @throws Zend_Exception
     */
    public static function setUpBeforeClass(): void {

        // Prepare api instance
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);

        // Check app state
        $appState = self::assertAppState();

        // Last authed user is testmanager
        self::assertNeededUsers();
        self::assertLogin('testmanager');

        // Import xlf-file
        $api->addImportFile($api->getFile('testfiles/TRANSLATE-2540-en-de.xlf'));
        $api->import([
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
            'lockLocked' => 1,
        ]);

        // Login in setUpBeforeClass means using this user in whole testcase!
        self::$api->addUser('testlector');
        self::$api->login('testlector');

        // Get task
        $task = self::$api->getTask();

        // Open task for whole testcase
        $api->requestJson('editor/task/' . $task->id, 'PUT', ['userState' => 'edit', 'id' => $task->id]);

        // Get segments and check their quantity
        static::$segments = $api->requestJson('editor/segment?page=1&start=0&limit=10');
        static::assertEquals(count(static::$segments), 2, 'Not enough segments in the imported task');
    }

    /**
     * Test the qualities fetched for a segment
     */
    public function testSegmentQualities(){
        $fileName = 'expectedSegmentQualities.json';
        $qualities = self::$api->getJson('/editor/quality/segment?segmentId=' . static::$segments[0]->id, [], $fileName);
        $this->assertModelsEqualsJsonFile('SegmentQuality', $fileName, $qualities);
    }

    /**
     * Cleanup
     */
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        self::$api->login('testlector');
        self::$api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'open', 'id' => $task->id));
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}