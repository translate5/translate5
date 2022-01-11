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
 * Translate678ExportDiffEntityTest imports a simple task, where the segments contain htmlentities
 * this were changed and afterwards a diff export is done. The entities may not be destroyed by the diff, but completly encapsulated.
 * See therefore:
 * TRANSLATE-678
 *
 * See also:
 * CsvEncodingTest
 */
class Translate678ExportDiffEntityTest extends \ZfExtended_Test_ApiTestcase {
    protected static $expectedCsvResult;
    
    public static function setUpBeforeClass(): void {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $task = array(
            'taskName' => 'API Testing::'.__CLASS__, //no date in file name possible here!
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
        );
        
        $appState = self::assertTermTagger();
        self::assertNotContains('editor_Plugins_ManualStatusCheck_Bootstrap', $appState->pluginsLoaded, 'Plugin ManualStatusCheck should not be activated for this test case!');
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        
        $api->addImportFile($api->getFile('apiTest.csv'), 'application/csv');
        
        $api->import($task);
    }
    
    /**
     * edits the segment data with " and ' characters, also changing < to >
     */
    public function testEditing() {
        $task = $this->api()->getTask();
        //open task for whole testcase
        $this->api()->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'edit', 'id' => $task->id));
        
        //get segment list
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        $segToEdit = $segments[0];
        
        //swap < and >
        $editedData = 'Target with "special" \'chars\' &amp; greaters &gt; and &lt; lessers';
        
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', $editedData, $segToEdit->id);
        $this->api()->requestJson('editor/segment/'.$segToEdit->id, 'PUT', $segmentData);
    }

    /**
     * tests the special characters in the exported data
     *   especially the flipped <> chars in the export diff
     * @depends testEditing
     */
    public function testExport() {
        $task = $this->api()->getTask();

        //start task export with diff
        $this->api()->request('editor/task/export/id/'.$task->id.'/diff/1');

        //get the exported file content
        $path = $this->api()->getTaskDataDirectory();
        $pathToZip = $path.'export.zip';
        $this->assertFileExists($pathToZip);
        
        $exportedData = $this->api()->getFileContentFromZipPath($pathToZip, $task->taskGuid.'/apiTest.csv');
        
        $expectedData = $this->api()->getFileContent('apiTest.csv');
        //insert the swapped <> characters into the expectedData for comparsion
        $expectedData = str_replace(array('< and >'), '<ins>></ins><del><</del> and <ins><</ins><del>></del>', $expectedData);

        $this->assertEquals(rtrim($expectedData), rtrim($exportedData), 'Exported result does not equal to '.$expectedData);
    }
    
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'open', 'id' => $task->id));
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}