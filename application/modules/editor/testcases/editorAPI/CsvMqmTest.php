<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * CsvEncodingTest imports a CSV with several special characters
 * The test task will be edited and exported. The generated changes.xml and 
 * exported file will then be checked for correct encoded content.
 */
class CsvMqmTest extends \ZfExtended_Test_ApiTestcase {
    const CSV_TARGET = 'target is coming from test edit';
    
    protected $testData = array(
        'M',
        '<n-o#5#1989>',
        'it den Einstellungen UNIPOL./FIX.SETPT ode',
        '<c-c#6#1985>',
        'r BIPO',
        '<c-o#1#1990>',
        'L./FIX.',
        '<n-o#3#1991>',
        'SETPT',
        '<c-o#10#1982>',
        ', kann ',
        '<n-o#3#1992>',
        'das ',
        '<c-o#6#1983>',
        'setpoint',
        '<c-c#5#1983>',
        ' au',
        '<c-o#13#1986>',
        'c',
        '<c-o#18#1987>',
        'h',
        '<n-o#19#1988>',
        ' ',
        '<n-c#3#1992>',
        '<c-o#16#1984>',
        'über',
        '<c-c#10#1982>',
        ' Anschlüssen',
        '<c-c#16#1984>',
        ' ausgew',
        '<c-c#13#1986>',
        'ählt werden (fest',
        '<c-c#18#1987>',
        'es ',
        '<c-o#6#1985>',
        'se',
        '<n-c#3#1991>',
        '<c-c#1#1990>',
        'tpoi',
        '<n-c#5#1989>',
        '<n-o#8#1993>',
        'nt).',
        '<n-c#19#1988>',
        '<n-c#8#1993>',
    );
    
    /**
     * Setting up the test task by fresh import, adds the lector and translator users
     */
    public static function setUpBeforeClass() {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $task = array(
            'taskName' => 'API Testing::'.__CLASS__, //no date in file name possible here!
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
        );
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        $appState = self::assertTermTagger();
        self::assertNotContains('editor_Plugins_ManualStatusCheck_Bootstrap', $appState->pluginsLoaded, 'Plugin ManualStatusCheck may not be activated for this test case!');
        
        $api->addImportPlain("mid,quelle,target\n".'1,"source not needed here","'.self::CSV_TARGET.'"');
        $api->import($task);
    }
    
    /**
     * tests if config is correct for using our test CSV
     */
    public function testCsvSettings() {
        $tests = array(
            'runtimeOptions.import.csv.delimiter' => ',',
            'runtimeOptions.import.csv.enclosure' => '"',
            'runtimeOptions.import.csv.fields.mid' => 'mid',
            'runtimeOptions.import.csv.fields.source' => 'quelle',
        );
        self::$api->testConfig($tests);
    }
    
    
    /**
     * tests the specialcharacters encoding after import, edits some segments as lector, finish then the task
     * - checks for correct changes.xliff
     * - checks if task is open for translator and finished for lector
     * - modifies also segments with special characters to test encoding in changes.xml
     * @depends testCsvSettings
     */
    public function testEditingSegmentWithMqm() {
        $task = $this->api()->getTask();
        //open task for whole testcase
        $this->api()->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'edit', 'id' => $task->id));
        
        //get segment list
        $segments = $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        $segToEdit = $segments[0];
        
        //asserts that our content was imported properly
        $this->assertEquals(self::CSV_TARGET, $segToEdit->targetEdit);

        //replacing img tags for better readability!
        $severity = array('c' => 'critical', 'n' => 'null');
        $tags = array('o' => 'open', 'c' => 'close');
        $dir = array('o' => 'left', 'c' => 'right');
        
        $editedData = join('', array_map(function($tag) use ($severity, $tags, $dir){
            return preg_replace_callback('/<([a-z])-([a-z])#([0-9]+)#([0-9]+)>/', function ($hit) use ($severity, $tags, $dir) {
                $type = $hit[3];
                $id = $hit[4];
                $css = $severity[$hit[1]].' qmflag ownttip '.$tags[$hit[2]].' qmflag-'.$type;
                $img = '/modules/editor/images/imageTags/qmsubsegment-'.$type.'-'.$dir[$hit[2]].'.png';
                return sprintf('<img  class="%s" data-seq="ext-%s" data-comment="" src="%s" />', $css, $id, $img);
            }, $tag);
        }, $this->testData));
        
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', $editedData, $segToEdit->id);
        $this->api()->requestJson('editor/segment/'.$segToEdit->id, 'PUT', $segmentData);
    }
    
    /**
     * tests the special characters in the exported data
     * @depends testEditingSegmentWithMqm
     */
    public function testExport() {
        $this->markTestIncomplete("test in draft mode, has to be completed!");
        $task = $this->api()->getTask();
        //start task export 
        $this->checkExport($task, 'editor/task/export/id/'.$task->id, 'cascadingMqm-export-assert-equal.csv');
        //start task export with diff 
        $this->checkExport($task, 'editor/task/export/id/'.$task->id.'/diff/1', 'cascadingMqm-exportdiff-assert-equal.csv');
    }
    
    /**
     * tests the export results
     * @param stdClass $task
     * @param string $exportUrl
     * @param string $fileToCompare
     */
    protected function checkExport(stdClass $task, $exportUrl, $fileToCompare) {
        $this->api()->login('testmanager');
        $this->api()->request($exportUrl);

        //get the exported file content
        $path = $this->api()->getTaskDataDirectory();
        $pathToZip = $path.'export.zip';
        $this->assertFileExists($pathToZip);
        $exportedFile = $this->api()->getFileContentFromZipPath($pathToZip, $task->taskGuid.'/apiTest.csv');
        //compare it
        $expectedResult = $this->api()->getFileContent($fileToCompare);
        $this->assertEquals($expectedResult, $exportedFile);
    }
    
    public static function tearDownAfterClass() {
        $task = self::$api->getTask();
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}
