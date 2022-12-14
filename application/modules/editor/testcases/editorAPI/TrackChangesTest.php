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

class TrackChangesTest extends editor_Test_ImportTest {

    protected static $expectedCsvResult;

    protected static array $requiredPlugins = [
        'editor_Plugins_TrackChanges_Init'
    ];

    protected static function setupImport(Config $config): void
    {
        $config
            ->addTask('en', 'de', -1, 'testcase-de-en.xlf')
            ->setToEditAfterImport()
            ->setProperty('taskName', static::NAME_PREFIX . 'TrackChangesTest'); // TODO FIXME: we better generate data independent from resource-names ...
    }

    /**
     * test for issue TRANSLATE-1267: content between two track changes del tags is getting deleted on some circumstances
     * Given a special construction of del tags was leading to much deleted content on the export
     */
    public function testTranslate1267() {
        $task = static::api()->getTask();
        
        //get segment list
        $segments = static::api()->getSegments();
        $segToEdit = $segments[0];
                
        //add content with ins del tags, here without attributes for better readability
        $editedData = 'This <del>was</del><ins>is</ins> the <ins>house</ins><del>castle</del> of St. Nicholas.';
        
        $editedData = $this->addTrackChangesAttributes($editedData);
        static::api()->saveSegment($segToEdit->id, $editedData);
    }

    /**
     * Adds the needed user infos as attributs to the del / ins tags. Currently fixed to testmanager
     * @param string $content
     * @return string
     */
    protected function addTrackChangesAttributes($content) {
        $attributes = 'data-userguid="{00000000-0000-0000-C100-CCDDEE000001}" data-username="Manager Test" data-usercssnr="usernr1" data-workflowstep="reviewing1" data-timestamp="2018-05-16T11:10:28+02:00"';
        $content = str_replace('<del>', '<del class="trackchanges ownttip deleted" '.$attributes.'>', $content);
        return str_replace('<ins>', '<ins class="trackchanges ownttip" '.$attributes.'>', $content);
    }
    
    /**
     * tests the special characters in the exported data
     * @depends testTranslate1267
     */
    public function testExport() {
        $task = static::api()->getTask();
        
        //start task export with diff
        static::api()->get('editor/task/export/id/'.$task->id.'/');
        
        //get the exported file content
        $path = static::api()->getTaskDataDirectory();
        $pathToZip = $path.'export.zip';
        $this->assertFileExists($pathToZip);
        
        //get the exported file content
        $exportedFile = static::api()->getFileContentFromZipPath($pathToZip, '/testcase-de-en.xlf');
        $expectedResult = static::api()->getFileContent('expected-export-testcase-de-en.xlf');
        //file_put_contents('/home/tlauria/foo1.xlf', rtrim($expectedResult));
        //file_put_contents('/home/tlauria/www/translate5-master/application/modules/editor/testcases/editorAPI/TrackChangesTest/new-export.xlf', rtrim($exportedFile));
        //compare it
       $this->assertEquals(rtrim($expectedResult), rtrim($exportedFile), 'Exported result does not equal to expected-export-testcase-de-en.xlf');
    }
}