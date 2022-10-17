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
 * Testcase for TRANSLATE-2362 Mixing XLF id and rid values led to wrong tag numbering
 * For details see the issue.
 */
class Translate2362Test extends editor_Test_JsonTest {

    protected static array $forbiddenPlugins = [
        'editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap',
        'editor_Plugins_NoMissingTargetTerminology_Bootstrap'
    ];

    protected static array $requiredRuntimeOptions = [
        'import.xlf.ignoreFramingTags' => 'all'
    ];

    protected static string $setupUserLogin = 'testlector';

    protected static function setupImport(Config $config): void
    {
        $config
            ->addTask('de', 'en')
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
    }
    
    /**
     * @depends testSegmentValuesAfterImport
     */
    public function testSegmentEditing() {
        //get segment list
        $segments = static::api()->getSegments(null, 10);
        
        //test editing a prefilled segment
        $segToTest = $segments[0];
        $segToTest->targetEdit = str_replace(['cool.', 'is &lt; a'], ['cool &amp; cööler.', 'is &gt; a'], $segToTest->targetEdit);
        static::api()->saveSegment($segToTest->id, $segToTest->targetEdit);
        
        $segToTest = $segments[1];
        $segToTest->targetEdit = str_replace(['comments and CDATA'], ['CDATA and comments'], $segToTest->targetEdit);
        static::api()->saveSegment($segToTest->id, $segToTest->targetEdit);
        
        //check direct PUT result
        $jsonFileName = 'expectedSegments-edited.json';
        $segments = static::api()->getSegments($jsonFileName, 10);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Edited segments are not as expected!');
    }
    
    /**
     * tests the export results
     * @depends testSegmentEditing
     */
    public function testExport() {
        static::api()->login('testmanager');
        $task = static::api()->getTask();
        //start task export
        
        static::api()->get('editor/task/export/id/'.$task->id);
        
        //get the exported file content
        $path = static::api()->getTaskDataDirectory();
        $pathToZip = $path.'export.zip';
        $this->assertFileExists($pathToZip);
        
        $exportedFile = static::api()->getFileContentFromZipPath($pathToZip, $task->taskGuid.'/TRANSLATE-2362-de-en.xlf');
        //file_put_contents(static::api()->getFile('export-TRANSLATE-2362-de-en.xlf', null, false), $exportedFile);
        $expectedResult = static::api()->getFileContent('export-TRANSLATE-2362-de-en.xlf');
        
        $this->assertEquals(rtrim($expectedResult), rtrim($exportedFile), 'Exported result does not equal to export-TRANSLATE-2362-de-en.xlf');
    }
}
