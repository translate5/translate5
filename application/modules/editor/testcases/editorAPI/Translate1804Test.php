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
 * Testcase for TRANSLATE-1804 Segments containing only 0 are not imported
 */
class Translate1804Test extends editor_Test_JsonTest {

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
     */
    public function testSegmentValuesAfterImport() {

        $jsonFileName = 'expectedSegments.json';
        $segments = static::api()->getSegments($jsonFileName, 10);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Imported segments are not as expected!');

        //we need a copy of the segmentIds, since assertSegmentsEqualsJsonFile would remove them
        $ids = array_column($segments, 'id');
        
        $testContent = '<ins class="trackchanges ownttip" data-usertrackingid="2330" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2020-05-14T12:30:33+02:00">0</ins>';
        
        //saving a plain 0
        static::api()->saveSegment($ids[3], '0');
        
        //saving a 0 with track changes
        static::api()->saveSegment($ids[4], $testContent);

        //saving a plain 0
        static::api()->saveSegment($ids[6], '0');

        //saving a 0 with track changes
        static::api()->saveSegment($ids[7], $testContent);

        $jsonFileName = 'expectedSegments-edited.json';
        $segments = static::api()->getSegments($jsonFileName, 10);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Edited segments are not as expected!');
    }
    
    /**
     * tests the export results
     * @depends testSegmentValuesAfterImport
     */
    public function testExport() {
        static::api()->login('testmanager');
        $task = static::api()->getTask();
        //start task export
        
        static::api()->get('editor/task/export/id/'.$task->id);
        //$fileToCompare;
        
        //get the exported file content
        $path = static::api()->getTaskDataDirectory();
        $pathToZip = $path.'export.zip';
        $this->assertFileExists($pathToZip);
        
        $exportedFile = static::api()->getFileContentFromZipPath($pathToZip, $task->taskGuid.'/02-sdlxliff-en-de.sdlxliff');
        //file_put_contents(static::api()->getFile('export-02-sdlxliff-en-de-new.sdlxliff', null, false), $exportedFile);
        $expectedResult = static::api()->getFileContent('export-02-sdlxliff-en-de.sdlxliff');
        
        $exportedFile = static::api()->getFileContentFromZipPath($pathToZip, $task->taskGuid.'/03-xlf-en-de.xlf');
        //file_put_contents(static::api()->getFile('export-03-xlf-en-de-new.xlf', null, false), $exportedFile);
        $expectedResult = static::api()->getFileContent('export-03-xlf-en-de.xlf');
        
        $this->assertEquals(rtrim($expectedResult), rtrim($exportedFile), 'Exported result does not equal to export-assert.sdlxliff');
    }
}