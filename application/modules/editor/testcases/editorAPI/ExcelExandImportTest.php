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
 * ExcelExandImportTest.php imports a simple task, checks export of excel and reimport then
 */
class ExcelExandImportTest extends editor_Test_JsonTest {
    
    /**
     * @var string contains the file name to the downloaded excel
     */
    protected static $tempExcel;

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

        static::api()->addImportFile(static::api()->getFile('testcase-en-de.xlf'));
        static::api()->import($task);
    }
    
    /**
     * Test the excel export
     */
    public function testExcelExport() {
        $task = static::api()->getTask();
        //start task export
        
        //get the excel
        $response = static::api()->get('editor/task/'.$task->id.'/excelexport');
        self::$tempExcel = $tempExcel = tempnam(sys_get_temp_dir(), 't5testExcel');
        file_put_contents($tempExcel, $response->getBody());
        
        $this->assertFileExists($tempExcel, 'Excel file could not be created');
        
        $zip = new ZipArchive;
        $res = $zip->open($tempExcel);
        $this->assertTrue($res, 'Exported Excelfile could not opened for injecting edits');
        $strings = $zip->getFromName('xl/sharedStrings.xml');
        $zip->addFromString('xl/sharedStrings.xml', str_replace('Testtext', 'Testtext - edited', $strings));
        $zip->close();
    }
    
    /**
     * @depends testExcelExport
     */
    public function testTaskStatus() {
        static::api()->reloadTask();
        $task = static::api()->getTask();
        $this->assertEquals('*translate5InternalLock*ExcelExported', $task->lockedInternalSessionUniqId);
        $this->assertEquals('{00000000-0000-0000-0000-000000000000}', $task->lockingUser);
        $this->assertEquals('ExcelExported', $task->state);
        $this->assertNotEmpty($task->locked);
    }
    
    /**
     * @depends testExcelExport
     */
    public function testReimport() {
        static::api()->addFile('excelreimportUpload', self::$tempExcel, 'application/data');
        static::api()->post('editor/task/'.static::api()->getTask()->id.'/excelreimport');
        static::api()->reloadTask();
        $task = static::api()->getTask();
        $this->assertEmpty($task->lockingUser, 'Task is locked by user '.$task->lockingUser);
        $this->assertEmpty($task->lockedInternalSessionUniqId, 'Task is locked by sessionUniqId '.$task->lockedInternalSessionUniqId);
        $this->assertEquals('open', $task->state);
        $this->assertEmpty($task->locked);
        
        //open task
        static::api()->setTaskToEdit($task->id);
        
        $jsonFileName = 'expectedSegments.json';
        $segments = static::api()->getSegments($jsonFileName, 47);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Imported segments are not as expected!');
    }
    
    public static function afterTests(): void {
        $task = static::api()->getTask();
        static::api()->deleteTask($task->id, 'testmanager');
    }
}