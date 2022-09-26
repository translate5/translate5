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

require_once 'Translate1804Test.php';

/**
 * Testcase for TRANSLATE-1804 Segments containing only 0 are not imported
 * we can not mix up CSV and other import files so we keep the CSV test separatly
 * Reason why we can not mix CSV and others: This would lead to multiple source and target columns, since a source and target are added by default for the other file types,
 * and for CSV is added then another source and target columns, since the column labels do not match. For multiple CSV files the column labels are used to align the columns.
 */
class Translate1804CsvTest extends \Translate1804Test {
    // just extend the base class, the api helper determines the file paths along the class name
    
    /**
     * tests the export results
     * @depends testSegmentValuesAfterImport
     * @param stdClass $task
     * @param string $exportUrl
     * @param string $fileToCompare
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
        
        $exportedFile = static::api()->getFileContentFromZipPath($pathToZip, $task->taskGuid.'/01-csv-en-de.csv');
        //file_put_contents(static::api()->getFile('export-01-csv-en-de-new.csv', null, false), $exportedFile);
        $expectedResult = static::api()->getFileContent('export-01-csv-en-de.csv');
        
        $this->assertEquals(rtrim($expectedResult), rtrim($exportedFile), 'Exported result does not equal to export-assert.sdlxliff');
    }
}