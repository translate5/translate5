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
 * Testcase for QualityBaseTest Mixing XLF id and rid values led to wrong tag numbering
 * For details see the issue.
 */
class QualityBaseTest extends editor_Test_JsonTest {
    
    public static function setUpBeforeClass(): void {
       
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $task = array(
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
            'lockLocked' => 1,
        );
        
        $appState = self::assertAppState();
        self::assertNeededUsers();
        self::assertLogin('testmanager');

        $tests = array(
            'runtimeOptions.autoQA.enableInternalTagCheck' => 1,
            'runtimeOptions.autoQA.enableEdited100MatchCheck' => 1,
            'runtimeOptions.autoQA.enableUneditedFuzzyMatchCheck' => 1,
            'runtimeOptions.autoQA.enableMqmTags' => 1,
            'runtimeOptions.autoQA.enableQm' => 1
        );
        self::$api->testConfig($tests);
        
         
        $api->addImportFile('editorAPI/MainTest/csv-with-mqm-en-de.zip');
        $api->import($task);
        
        $api->addUser('testlector');
        
        //login in setUpBeforeClass means using this user in whole testcase!
        $api->login('testlector');
        
        $task = $api->getTask();
        //open task for whole testcase
        $api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'edit', 'id' => $task->id));
    }
    /**
     * Tests the generally availability and validity of the filter tree
     */
    public function testFilterQualityTree(){
        $tree = $this->api()->requestJson('/editor/quality');
        // file_put_contents($this->api()->getFile('/expectedQualityFilter.json', null, false), json_encode($tree, JSON_PRETTY_PRINT));
        $this->assertModelEqualsJsonFile('FilterQuality', 'expectedQualityFilter.json', $tree);
    }
    
    
    
    /**

    public function testSegmentValuesAfterImport(){
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=10');

        file_put_contents($this->api()->getFile('/expectedSegments.json', null, false), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertEquals(self::$api->getFileContent('expectedSegments.json'), $data, 'Imported segments are not as expected!');
    }
    

    public function testSegmentEditing() {
        //get segment list
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=10');
        
        //test editing a prefilled segment
        $segToTest = $segments[0];
        
        $segToTest->targetEdit = str_replace(['cool.', 'is &lt; a'], ['cool &amp; cööler.', 'is &gt; a'], $segToTest->targetEdit);
        
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', $segToTest->targetEdit, $segToTest->id);
        $this->api()->requestJson('editor/segment/'.$segToTest->id, 'PUT', $segmentData);
        
        //check direct PUT result
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=10');

        file_put_contents($this->api()->getFile('/expectedSegments-edited.json', null, false), json_encode($data,JSON_PRETTY_PRINT));
        $this->assertEquals(self::$api->getFileContent('expectedSegments-edited.json'), $data, 'Edited segments are not as expected!');
    }
    

    public function testExport() {
        self::$api->login('testmanager');
        $task = $this->api()->getTask();
        //start task export
        
        $this->api()->request('editor/task/export/id/'.$task->id);
        
        //get the exported file content
        $path = $this->api()->getTaskDataDirectory();
        $pathToZip = $path.'export.zip';
        $this->assertFileExists($pathToZip);
        
        $exportedFile = $this->api()->getFileContentFromZipPath($pathToZip, $task->taskGuid.'/QualityBaseTest-de-en.xlf');

        file_put_contents($this->api()->getFile('export-QualityBaseTest-de-en.xlf', null, false), $exportedFile);
        $expectedResult = $this->api()->getFileContent('export-QualityBaseTest-de-en.xlf');
        
        $this->assertEquals(rtrim($expectedResult), rtrim($exportedFile), 'Exported result does not equal to export-QualityBaseTest-de-en.xlf');
    }
    
    */

    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testlector');
        self::$api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'open', 'id' => $task->id));
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}
