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
class XlfSegmentLengthTest extends editor_Test_JsonTest {

    protected static array $forbiddenPlugins = [
        'editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap',
        'editor_Plugins_NoMissingTargetTerminology_Bootstrap'
    ];

    protected static array $requiredRuntimeOptions = [
        'import.xlf.preserveWhitespace' => 0
    ];

    public static function beforeTests(): void {

        $task = array(
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
            'lockLocked' => 1,
        );
        
        self::assertAppState();

        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');

        static::assertConfigs();
        
        $zipfile = static::api()->zipTestFiles('testfiles/','XLF-test.zip');
        
        static::api()->addImportFile($zipfile);
        static::api()->import($task);
        
        static::api()->addUser('testlector');
        
        //login in beforeTests means using this user in whole testcase!
        static::api()->login('testlector');
        
        $task = static::api()->getTask();
        //open task for whole testcase
        static::api()->setTaskToEdit($task->id);
    }
    
    /**
     * Testing segment values directly after import
     * Other constellations of the segment length count are implicitly tested in the XlfImportTest!
     */
    public function testSegmentValuesAfterImport() {
        //get segment list (just the ones of the first file for that tests)
        $jsonFileName = 'expectedSegments.json';
        $segments = static::api()->getSegments($jsonFileName, 20);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Imported segments are not as expected!');
    }
    
    /**
     * @depends testSegmentValuesAfterImport
     */
    public function testSegmentEditing() {
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
            static::api()->saveSegment($segToEdit->id, $editedData);
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
        //compare it
        $expectedResult = static::api()->getFileContent($fileToCompare);
        //file_put_contents('/home/tlauria/foo1.xlf', rtrim($expectedResult));
        //file_put_contents('/home/tlauria/foo2.xlf', rtrim($exportedFile));
        //file_put_contents('/home/tlauria/foo-'.$fileToCompare, rtrim($exportedFile));
        $this->assertEquals(rtrim($expectedResult), rtrim($exportedFile), 'Exported result does not equal to '.$fileToCompare);
    }
    
    public static function afterTests(): void {
        $task = static::api()->getTask();
        static::api()->deleteTask($task->id, 'testmanager', 'testlector');
    }
}