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
    public static function beforeTests(): void {
        // Import xlf-file
        static::api()->addImportFile(static::api()->getFile('testfiles/TRANSLATE-2540-en-de.xlf'));
        static::api()->import([
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
            'lockLocked' => 1,
        ]);

        // Login in beforeTests means using this user in whole testcase!
        static::api()->addUser('testlector');
        static::api()->login('testlector');

        // Get task
        $task = static::api()->getTask();

        // Open task for whole testcase
        static::api()->setTaskToEdit($task->id);

        // Get segments and check their quantity
        static::$segments = static::api()->getSegments(null, 10);
        static::assertEquals(count(static::$segments), 2, 'Not enough segments in the imported task');
    }

    /**
     * Test the qualities fetched for a segment
     */
    public function testSegmentQualities(){
        $fileName = 'expectedSegmentQualities.json';
        $qualities = static::api()->getJson('/editor/quality/segment?segmentId=' . static::$segments[0]->id, [], $fileName);
        $filter = editor_Test_Model_Filter::createSingle('type', 'empty');
        $this->assertModelsEqualsJsonFile('SegmentQuality', $fileName, $qualities, '', $filter);
    }

    /**
     * Cleanup
     */
    public static function afterTests(): void {
        $task = static::api()->getTask();
        static::api()->deleteTask($task->id, 'testmanager', 'testlector');
    }
}