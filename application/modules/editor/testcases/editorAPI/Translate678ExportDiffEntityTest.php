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
 * Translate678ExportDiffEntityTest imports a simple task, where the segments contain htmlentities
 * this were changed and afterwards a diff export is done. The entities may not be destroyed by the diff, but completly encapsulated.
 * See therefore:
 * TRANSLATE-678
 *
 * See also:
 * CsvEncodingTest
 */
class Translate678ExportDiffEntityTest extends editor_Test_ImportTest {

    protected static bool $termtaggerRequired = true;

    protected static array $forbiddenPlugins = [
        'editor_Plugins_ManualStatusCheck_Bootstrap'
    ];

    protected static $expectedCsvResult;

    protected static function setupImport(Config $config): void
    {
        $config
            ->addTask('en', 'de', -1, 'apiTest.csv')
            ->setToEditAfterImport();
    }

    /**
     * edits the segment data with " and ' characters, also changing < to >
     */
    public function testEditing() {

        //get segment list
        $segments = static::api()->getSegments();
        $segToEdit = $segments[0];
        
        //swap < and >
        $editedData = 'Target with "special" \'chars\' &amp; greaters &gt; and &lt; lessers';
        static::api()->saveSegment($segToEdit->id, $editedData);
    }

    /**
     * tests the special characters in the exported data
     *   especially the flipped <> chars in the export diff
     * @depends testEditing
     */
    public function testExport() {
        $task = static::api()->getTask();

        //start task export with diff
        static::api()->get('editor/task/export/id/'.$task->id.'/diff/1');

        //get the exported file content
        $path = static::api()->getTaskDataDirectory();
        $pathToZip = $path.'export.zip';
        $this->assertFileExists($pathToZip);
        
        $exportedData = static::api()->getFileContentFromZipPath($pathToZip, '/apiTest.csv');
        
        $expectedData = static::api()->getFileContent('apiTest.csv');
        //insert the swapped <> characters into the expectedData for comparsion
        $expectedData = str_replace(array('< and >'), '<ins>></ins><del><</del> and <ins><</ins><del>></del>', $expectedData);

        $this->assertEquals(rtrim($expectedData), rtrim($exportedData), 'Exported result does not equal to '.$expectedData);
    }
}