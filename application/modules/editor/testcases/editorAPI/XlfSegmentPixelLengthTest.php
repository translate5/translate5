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
 * XlfSegmentLengthTest imports a simple task and checks imported values about the segment lengths
 * edits segments and checks then the edited ones again on correct content
 */
class XlfSegmentPixelLengthTest extends editor_Test_JsonTest {
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
     * Other constellations of the segment length count are implicitly tested in the XlfImportTest!
     */
    public function testSegmentValuesAfterImport() {
        //get segment list (just the ones of the first file for that tests)
        $jsonFileName = 'expectedSegments.json';
        $segments = $this->api()->getSegments($jsonFileName, 20);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Imported segments are not as expected!');
    }
    
    /**
     * @depends testSegmentValuesAfterImport
     */
    public function testSegmentEditing() {
        //get segment list (just the ones of the first file for that tests)
        $segments = $this->api()->getSegments(null, 20);
        $this->assertNotEmpty($segments, 'No segments are found in the Task!');
        
        require_once 'Models/Segment/TagAbstract.php';
        require_once 'Models/Segment/InternalTag.php';

        //the first three segments remain unedited, since content is getting to long with edited content
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
            $editedData = $contentToUse.' - edited'.$segToEdit->segmentNrInTask;
            $segmentData = $this->api()->prepareSegmentPut('targetEdit', $editedData, $segToEdit->id);
            $this->api()->putJson('editor/segment/'.$segToEdit->id, $segmentData);
        }
        
        $jsonFileName = 'expectedSegmentsEdited.json';
        $segments = $this->api()->getSegments($jsonFileName, 20);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Edited segments are not as expected!');
        
        $task = $this->api()->getTask();
        //start task export
        $this->checkExport($task, 'editor/task/export/id/'.$task->id, 'mrkothercontentlength-en-de.xlf', 'expected-export.xlf');
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
    
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        self::$api->deleteTask($task->id, 'testmanager', 'testlector');
    }
}