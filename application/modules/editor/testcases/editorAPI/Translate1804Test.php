<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Testcase for TRANSLATE-1804 Segments containing only 0 are not imported
 */
class Translate1804Test extends editor_Test_Segment {
    public static function setUpBeforeClass(): void {
        self::$api = $api = new ZfExtended_Test_ApiHelper(get_called_class());
        
        //$api->xdebug = true;
        
        $task = array(
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
            'lockLocked' => 1,
        );
        
        $appState = self::assertAppState();
        self::assertNotContains('editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap', $appState->pluginsLoaded, 'Plugin LockSegmentsBasedOnConfig should not be activated for this test case!');
        self::assertNotContains('editor_Plugins_NoMissingTargetTerminology_Bootstrap', $appState->pluginsLoaded, 'Plugin NoMissingTargetTerminology should not be activated for this test case!');
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        
        $tests = array(
            'runtimeOptions.import.xlf.preserveWhitespace' => 0,
        );
        self::$api->testConfig($tests);
        
        $zipfile = $api->zipTestFiles('testfiles/','XLF-test.zip');
        
        $api->addImportFile($zipfile);
        $api->import($task);
        
        $api->addUser('testlector');
        
        //login in setUpBeforeClass means using this user in whole testcase!
        $api->login('testlector');
        
        $task = $api->getTask();
        //open task for whole testcase
        $api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'edit', 'id' => $task->id));
    }
    
    /**
     * Testing segment values directly after import
     */
    public function testSegmentValuesAfterImport() {
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=10');
        
        //we need a copy of the segmentIds, since assertSegmentsEqualsJsonFile would remove them
        $ids = array_column($segments, 'id');
        
        $this->assertSegmentsEqualsJsonFile('expectedSegments.json', $segments, 'Imported segments are not as expected!');
        
        $testContent = '<ins class="trackchanges ownttip" data-usertrackingid="2330" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2020-05-14T12:30:33+02:00">0</ins>';
        
        //saving a plain 0
        $segment = $this->api()->prepareSegmentPut('targetEdit', "0", $ids[3]);
        $this->api()->requestJson('editor/segment/'.$ids[3], 'PUT', $segment);
        
        //saving a 0 with track changes
        $segment = $this->api()->prepareSegmentPut('targetEdit', $testContent, $ids[4]);
        $this->api()->requestJson('editor/segment/'.$ids[4], 'PUT', $segment);
        
        //saving a plain 0
        $segment = $this->api()->prepareSegmentPut('targetEdit', "0", $ids[6]);
        $this->api()->requestJson('editor/segment/'.$ids[6], 'PUT', $segment);
        //saving a 0 with track changes
        $segment = $this->api()->prepareSegmentPut('targetEdit', $testContent, $ids[7]);
        $this->api()->requestJson('editor/segment/'.$ids[7], 'PUT', $segment);
        
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=10');

        $this->assertSegmentsEqualsJsonFile('expectedSegments-edited.json', $segments, 'Edited segments are not as expected!');
    }
    
    /**
     * tests the export results
     * @depends testSegmentValuesAfterImport
     */
    public function testExport() {
        self::$api->login('testmanager');
        $task = $this->api()->getTask();
        //start task export
        
        $this->api()->request('editor/task/export/id/'.$task->id);
        //$fileToCompare;
        
        //get the exported file content
        $path = $this->api()->getTaskDataDirectory();
        $pathToZip = $path.'export.zip';
        $this->assertFileExists($pathToZip);
        
        $exportedFile = $this->api()->getFileContentFromZipPath($pathToZip, $task->taskGuid.'/02-sdlxliff-en-de.sdlxliff');
        //file_put_contents($this->api()->getFile('export-02-sdlxliff-en-de-new.sdlxliff', null, false), $exportedFile);
        $expectedResult = $this->api()->getFileContent('export-02-sdlxliff-en-de.sdlxliff');
        
        $exportedFile = $this->api()->getFileContentFromZipPath($pathToZip, $task->taskGuid.'/03-xlf-en-de.xlf');
        //file_put_contents($this->api()->getFile('export-03-xlf-en-de-new.xlf', null, false), $exportedFile);
        $expectedResult = $this->api()->getFileContent('export-03-xlf-en-de.xlf');
        
        $this->assertEquals(rtrim($expectedResult), rtrim($exportedFile), 'Exported result does not equal to export-assert.sdlxliff');
    }
    
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testlector');
        self::$api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'open', 'id' => $task->id));
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}