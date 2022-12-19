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

use MittagQI\Translate5\Test\Import\Config;

/**
 * BasicSegmentEditingTest imports a simple task, checks imported values,
 * edits segments and checks then the edited ones again on correct content
 */
class XlfImportTest extends editor_Test_JsonTest {

    protected static array $forbiddenPlugins = [
        'editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap',
        'editor_Plugins_NoMissingTargetTerminology_Bootstrap'
    ];

    protected static array $requiredRuntimeOptions = [
        'import.xlf.preserveWhitespace' => 0,
    ];

    protected static string $setupUserLogin = 'testlector';

    protected static function setupImport(Config $config): void
    {
        $config
            ->addTask('en', 'de')
            ->addUploadFolder('testfiles')
            ->addTaskConfig('runtimeOptions.autoQA.enableSegmentSpellCheck', '0')
            ->setToEditAfterImport();
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
        $segments = static::api()->getSegments($jsonFileName, 47);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Imported segments are not as expected!');
    }
    
    /**
     * Tests if whitespace is preserved correctly, according to the XLIFF specification.
     * Needs $this->config->runtimeOptions->import->xlf->preserveWhitespace to be false!
     */
    public function testPreserveWhitespace() {
        $jsonFileName = 'expectedSegmentsPreserveWhitespace.json';
        $segments = static::api()->getSegments($jsonFileName, 200, 47);
        $this->assertSegmentsEqualsJsonFile('expectedSegmentsPreserveWhitespace.json', $segments, 'Imported segments are not as expected!');
    }
    
    /**
     * @depends testSegmentValuesAfterImport
     * @depends testPreserveWhitespace
     */
    public function testSegmentEditing() {
        //get segment list (just the ones of the first file for that tests)
        $segments = static::api()->getSegments(null, 41);
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
            } else {
                $editedData = $contentToUse.' - edited'.$segToEdit->segmentNrInTask;
            }
            static::api()->saveSegment($segToEdit->id, $editedData);
        }
        
        //test editing of segments with preserved whitespace and segment length count
        $segments = array_merge(
            static::api()->getSegments(null, 6, 80),
            static::api()->getSegments(null, 1, 106),
            static::api()->getSegments(null, 18, 116),
        );
        foreach($segments as $idx => $segToEdit) {
            $content = strlen($segToEdit->target) > 0 ? $segToEdit->target : $segToEdit->source;

            //segments 84, 85, 86 are too long then and should trigger segment validation
            if(in_array($segToEdit->segmentNrInTask, [84, 85, 86])) {
                static::api()->allowHttpStatusOnce(422);
                $result = (array) static::api()->saveSegment($segToEdit->id, $content.' - edited');
                $this->assertEquals(422, $result['httpStatus'], 'Segment ['.$segToEdit->segmentNrInTask.'] is returning wrong HTTP Status.');
                $this->assertEquals('The data of the saved segment is not valid. The segment content is either to long or to short.', $result['errorMessage'], 'Segment ['.$segToEdit->segmentNrInTask.'] is returning wrong or no error.');
            }
            else {
                static::api()->saveSegment($segToEdit->id, $content.' - edited');
            }
        }
        
        /**
         * Tests if whitespace is preserved correctly, according to the XLIFF specification.
         * Needs $this->config->runtimeOptions->import->xlf->preserveWhitespace to be false!
         */

        $jsonFileName = 'expectedSegmentsPreserveWhitespaceAfterEdit.json';
        $segments = static::api()->getSegments($jsonFileName, 200, 47);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Edited segments are not as expected!');
        
        $task = static::api()->getTask();
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
        $task = static::api()->getTask();
        //start task export
        $this->checkExport($task, 'editor/task/export/id/'.$task->id, '04-segmentation.xlf', 'export-segmentation.xlf');
    }

    /**
     * check if the whitespace between mrk tags on the import are also exported again
     * @depends testSegmentEditing
     */
    public function testAcrossXlf() {
        $task = static::api()->getTask();
        //start task export
        $this->checkExport($task, 'editor/task/export/id/'.$task->id, '03-across.xlf', 'export-across.xlf');
    }
    
    /**
     * check if the whitespace between mrk tags on the import are also exported again
     * @depends testSegmentEditing
     */
    public function testPreserveContentBetweenMrk() {
        $task = static::api()->getTask();
        //start task export
        $this->checkExport($task, 'editor/task/export/id/'.$task->id, '02-preserveWhitespace.xlf', 'preserveWhitespace-exporttest.xlf');
    }
    
    /**
     * check if several fixed issues are still fixed
     * @depends testSegmentEditing
     */
    public function testIssueExports() {
        $task = static::api()->getTask();
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
        static::api()->login('testmanager');
        static::api()->get($exportUrl);

        //get the exported file content
        $path = static::api()->getTaskDataDirectory();
        $pathToZip = $path.'export.zip';
        $this->assertFileExists($pathToZip);
        $exportedFile = static::api()->getFileContentFromZipPath($pathToZip, '/'.$fileToExport);

        if(static::api()->isCapturing()) {
            file_put_contents(static::api()->getFile($fileToCompare, null, false), rtrim($exportedFile));
        }

        //compare it
        $expectedResult = static::api()->getFileContent($fileToCompare);
        $this->assertEquals(rtrim($expectedResult), rtrim($exportedFile), 'Exported result does not equal to '.$fileToCompare);
    }
    
    /**
     * Is incomplete since we could not change the import->xlf->preserveWhitespace config from inside the test
     * Needs task templates therefore
     */
    public function testPreserveAllWhitespace() {
        $this->markTestIncomplete('Could not be tested due missing task template functionality to set the preserve config to true.');
    }
}