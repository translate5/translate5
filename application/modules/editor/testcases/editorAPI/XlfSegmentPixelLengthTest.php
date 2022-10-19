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
 * XlfSegmentLengthTest imports a simple task and checks imported values about the segment lengths
 * edits segments and checks then the edited ones again on correct content
 */
class XlfSegmentPixelLengthTest extends editor_Test_JsonTest {

    protected static array $forbiddenPlugins = [
        'editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap',
        'editor_Plugins_NoMissingTargetTerminology_Bootstrap'
    ];

    protected static array $requiredRuntimeOptions = [
        'import.xlf.preserveWhitespace' => 0
    ];

    protected static string $setupUserLogin = 'testlector';

    protected static function setupImport(Config $config): void
    {
        $config
            ->addTask('en', 'de')
            ->addUploadFolder('testfiles')
            ->setToEditAfterImport();
    }

    /**
     * Testing segment values directly after import
     * Other constellations of the segment length count are implicitly tested in the XlfImportTest!
     */
    public function testSegmentsAfterImportAndAfterEdit() {

        //get segment list (just the ones of the first file for that tests)
        $jsonFileName = 'expectedSegments.json';
        $segments = static::api()->getSegments($jsonFileName, 20);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Imported segments are not as expected!');

        //get segment list (just the ones of the first file for that tests)
        $segments = static::api()->getSegments(null, 20);
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
            if(in_array($segToEdit->segmentNrInTask, [1,2,3,13,15])) {
                static::api()->allowHttpStatusOnce(422);
                $result = (array) static::api()->saveSegment($segToEdit->id, $editedData);
                $this->assertEquals(422, $result['httpStatus'], 'Segment ['.$segToEdit->segmentNrInTask.'] is returning wrong HTTP Status.');
                $this->assertEquals('The data of the saved segment is not valid. The segment content is either to long or to short.', $result['errorMessage'], 'Segment ['.$segToEdit->segmentNrInTask.'] is returning wrong or no error.');
            }
            else {
                static::api()->saveSegment($segToEdit->id, $editedData);
            }
        }
        
        $jsonFileName = 'expectedSegmentsEdited.json';
        $segments = static::api()->getSegments($jsonFileName, 20);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Edited segments are not as expected!');
        
        $task = static::api()->getTask();
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
        static::api()->login('testmanager');
        static::api()->get($exportUrl);

        //get the exported file content
        $path = static::api()->getTaskDataDirectory();
        $pathToZip = $path.'export.zip';
        $this->assertFileExists($pathToZip);
        $exportedFile = static::api()->getFileContentFromZipPath($pathToZip, $task->taskGuid.'/'.$fileToExport);

        if(static::api()->isCapturing()) {
            file_put_contents(static::api()->getFile($fileToCompare, null, false), rtrim($exportedFile));
        }

        //compare it
        $expectedResult = static::api()->getFileContent($fileToCompare);
        $this->assertEquals(rtrim($expectedResult), rtrim($exportedFile), 'Exported result does not equal to '.$fileToCompare);
    }
}