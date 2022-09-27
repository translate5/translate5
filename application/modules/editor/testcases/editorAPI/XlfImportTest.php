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
 * BasicSegmentEditingTest imports a simple task, checks imported values,
 * edits segments and checks then the edited ones again on correct content
 */
class XlfImportTest extends editor_Test_JsonTest {
    public static function setUpBeforeClass(): void {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
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
        $api->setTaskToEdit($task->id);
    }
    
    /**
     * Testing segment values directly after import
     */
    public function testSegmentValuesAfterImport() {
        
        //FIXME: This test is to be considered incomplete!!!
        // it must be continued on continuing the XLF import.
        
        //FIXME get task and test wordcount!!!
        //get segment list (just the ones of the first file for that tests)
        $jsonFileName = 'expectedSegments.json';
        $segments = $this->api()->getSegments($jsonFileName, 47);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Imported segments are not as expected!');
    }
    
    /**
     * Tests if whitespace is preserved correctly, according to the XLIFF specification.
     * Needs $this->config->runtimeOptions->import->xlf->preserveWhitespace to be false!
     */
    public function testPreserveWhitespace() {
        $jsonFileName = 'expectedSegmentsPreserveWhitespace.json';
        $segments = $this->api()->getSegments($jsonFileName, 200, 47);
        $this->assertSegmentsEqualsJsonFile('expectedSegmentsPreserveWhitespace.json', $segments, 'Imported segments are not as expected!');
    }
    
    /**
     * @depends testSegmentValuesAfterImport
     * @depends testPreserveWhitespace
     */
    public function testSegmentEditing() {
        //get segment list (just the ones of the first file for that tests)
        $segments = $this->api()->getSegments(null, 41);
        $this->assertNotEmpty($segments, 'No segments are found in the Task!');
        
        require_once 'Models/Segment/TagAbstract.php';
        require_once 'Models/Segment/InternalTag.php';

        foreach($segments as $idx => $segToEdit) {
            if(empty($segToEdit->editable)) {
                continue;
            }
            if(empty($segToEdit->targetEdit)) {
                $contentToUse = $segToEdit->source;
            }
            else {
                $contentToUse = $segToEdit->targetEdit;
            }
            //in the segments 34 and 39 the tags are swapping position
            if($segToEdit->segmentNrInTask == "34" || $segToEdit->segmentNrInTask == "39") {
                $tagger = new editor_Models_Segment_InternalTag();
                $editedData = $contentToUse.' - edited'.$segToEdit->segmentNrInTask;
                $found = [];
                //replace all tags with a placeholder
                $editedData = $tagger->replace($editedData, function($matches) use (&$found) {
                    $idx = count($found);
                    $key = '<splitter id="'.$idx.'">';
                    $found[$key] = $matches[0];
                    return $key;
                });
                //replace the placeholder back to the original tag, but swap positions before, by reversing the array:
                $editedData = str_replace(array_keys($found), array_reverse(array_values($found)), $editedData);
            }
            else {
                $editedData = $contentToUse.' - edited'.$segToEdit->segmentNrInTask;
            }
            $segmentData = $this->api()->prepareSegmentPut('targetEdit', $editedData, $segToEdit->id);
            $this->api()->putJson('editor/segment/'.$segToEdit->id, $segmentData);
        }
        
        //test editing of segments with preserved whitespace and segment length count
        $segments = array_merge(
            $this->api()->getSegments(null, 6, 80),
            $this->api()->getSegments(null, 1, 106),
            $this->api()->getSegments(null, 18, 116),
        );
        foreach($segments as $idx => $segToEdit) {
            $content = strlen($segToEdit->target) > 0 ? $segToEdit->target : $segToEdit->source;
            $segmentData = $this->api()->prepareSegmentPut('targetEdit', $content.' - edited', $segToEdit->id);
            $this->api()->putJson('editor/segment/'.$segToEdit->id, $segmentData);
        }
        
        /**
         * Tests if whitespace is preserved correctly, according to the XLIFF specification.
         * Needs $this->config->runtimeOptions->import->xlf->preserveWhitespace to be false!
         */

        $jsonFileName = 'expectedSegmentsPreserveWhitespaceAfterEdit.json';
        $segments = $this->api()->getSegments($jsonFileName, 200, 47);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Edited segments are not as expected!');
        
        $task = $this->api()->getTask();
        //start task export
        $this->checkExport($task, 'editor/task/export/id/'.$task->id, '01-ibm-opentm2.xlf', 'ibm-opentm2-export-normal.xlf');
        //start task export with diff
        // diff export will be disabled for XLF!
    }
    
        
    /**
     * check if the whitespace between mrk tags on the import are also exported again
     * @depends testSegmentEditing
     */
    public function testMissingMrks() {
        $task = $this->api()->getTask();
        //start task export
        $this->checkExport($task, 'editor/task/export/id/'.$task->id, '04-segmentation.xlf', 'export-segmentation.xlf');
    }

    /**
     * check if the whitespace between mrk tags on the import are also exported again
     * @depends testSegmentEditing
     */
    public function testAcrossXlf() {
        $task = $this->api()->getTask();
        //start task export
        $this->checkExport($task, 'editor/task/export/id/'.$task->id, '03-across.xlf', 'export-across.xlf');
    }
    
    /**
     * check if the whitespace between mrk tags on the import are also exported again
     * @depends testSegmentEditing
     */
    public function testPreserveContentBetweenMrk() {
        $task = $this->api()->getTask();
        //start task export
        $this->checkExport($task, 'editor/task/export/id/'.$task->id, '02-preserveWhitespace.xlf', 'preserveWhitespace-exporttest.xlf');
    }
    
    /**
     * check if several fixed issues are still fixed
     * @depends testSegmentEditing
     */
    public function testIssueExports() {
        $task = $this->api()->getTask();
        //start task export
        $this->checkExport($task, 'editor/task/export/id/'.$task->id, '05-Translate1971-de-en.xlf', 'Translate1971-exporttest.xlf');
        $this->checkExport($task, 'editor/task/export/id/'.$task->id, '06-Translate2525-de-en.xlf', 'Translate2525-exporttest.xlf');
        $this->checkExport($task, 'editor/task/export/id/'.$task->id, '09-Translate2786-en-de.xlf', 'Translate2786-exporttest.xlf');
    }
    
    /**
     * tests the export results
     * @param stdClass $task
     * @param string $exportUrl
     * @param string $fileToExport
     * @param string $fileToCompare
     */
    protected function checkExport(stdClass $task, $exportUrl, $fileToExport, $fileToCompare) {
        $this->api()->login('testmanager');
        $this->api()->get($exportUrl);

        //get the exported file content
        $path = $this->api()->getTaskDataDirectory();
        $pathToZip = $path.'export.zip';
        $this->assertFileExists($pathToZip);
        $exportedFile = $this->api()->getFileContentFromZipPath($pathToZip, $task->taskGuid.'/'.$fileToExport);

        if($this->api()->isCapturing()) {
            file_put_contents($this->api()->getFile($fileToCompare, null, false), rtrim($exportedFile));
        }

        //compare it
        $expectedResult = $this->api()->getFileContent($fileToCompare);
        $this->assertEquals(rtrim($expectedResult), rtrim($exportedFile), 'Exported result does not equal to '.$fileToCompare);
    }
    
    /**
     * Is incomplete since we could not change the import->xlf->preserveWhitespace config from inside the test
     * Needs task templates therefore
     */
    public function testPreserveAllWhitespace() {
        $this->markTestIncomplete('Could not be tested due missing task template functionality to set the preserve config to true.');
    }
    
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        self::$api->deleteTask($task->id, 'testmanager', 'testlector');
    }
}