<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * CsvMqmTest tests the correct export MQM Tags.
 *   Especially the cases of overlapping and misordered MQM tags
 */
class CsvMqmTest extends \ZfExtended_Test_ApiTestcase {
    const CSV_TARGET = 'target is coming from test edit';
    
    protected $testData = array(
        'M',
        '<n-o#5#1989>',
        'it den Einstellungen UNIPOL./FIX.SETPT oder BIPO',
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
        'es se',
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
    public static function setUpBeforeClass(): void {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $task = array(
            'taskName' => 'API Testing::'.__CLASS__, //no date in file name possible here!
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
        );
        
        $appState = self::assertTermTagger();
        self::assertNotContains('editor_Plugins_ManualStatusCheck_Bootstrap', $appState->pluginsLoaded, 'Plugin ManualStatusCheck should not be activated for this test case!');
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        
        $api->addImportPlain("id,source,target\n".'1,"source not needed here","'.self::CSV_TARGET.'"'."\n".'2,"zeile 2","row 2"');
        $api->import($task);
    }
    
    /**
     * tests if config is correct for using our test CSV
     */
    public function testCsvSettings() {
        $tests = array(
            'runtimeOptions.import.csv.delimiter' => ',',
            'runtimeOptions.import.csv.enclosure' => '"',
            'runtimeOptions.import.csv.fields.mid' => 'id',
            'runtimeOptions.import.csv.fields.source' => 'source',
        );
        self::$api->testConfig($tests);
    }
    
    
    /**
     * Check imported data and add MQM to the target by editing it
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

        $editedData = $this->compileMqmTags($this->testData);
        
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', $editedData, $segToEdit->id);
        $this->api()->requestJson('editor/segment/'.$segToEdit->id, 'PUT', $segmentData);
        
        //editing second segment
        $segToEdit = $segments[1];
        
        $test2 = array(
                'nice',
                '<c-c#6#1>', //wrong open close order!
                'test',
                '<c-o#3#2>',
                'data',
                '<c-o#3#3>', //overlapping here
                'to',
                '<c-c#3#2>', //and here
                'test',
                '<c-o#6#1>', //wrong open close order!
                'wrong',
                '<c-c#3#3>',
                'order'
        );
        
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', $this->compileMqmTags($test2), $segToEdit->id);
        $this->api()->requestJson('editor/segment/'.$segToEdit->id, 'PUT', $segmentData);
    }
    
    /**
     * In our above testdata the mqm img tags were replaced for better readability
     * this method creates the img tags out of the meta annotation
     * @param array $data
     * @return string
     */
    protected function compileMqmTags(array $data) {
        //replacing img tags for better readability!
        $severity = array('c' => 'critical', 'n' => 'null');
        $tags = array('o' => 'open', 'c' => 'close');
        $dir = array('o' => 'left', 'c' => 'right');
        
        return join('', array_map(function($tag) use ($severity, $tags, $dir){
            return preg_replace_callback('/<([a-z])-([a-z])#([0-9]+)#([0-9]+)>/', function ($hit) use ($severity, $tags, $dir) {
                $type = $hit[3];
                $id = $hit[4];
                $css = $severity[$hit[1]].' qmflag ownttip '.$tags[$hit[2]].' qmflag-'.$type;
                $img = '/modules/editor/images/imageTags/qmsubsegment-'.$type.'-'.$dir[$hit[2]].'.png';
                return sprintf('<img  class="%s" data-t5qid="ext-%s" data-comment="" src="%s" />', $css, $id, $img);
            }, $tag);
        }, $data));
    }
    
    /**
     * test if MQM tags are as expected in exported data
     * @depends testEditingSegmentWithMqm
     */
    public function testExport() {
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
        $this->api()->request($exportUrl);

        //get the exported file content
        $path = $this->api()->getTaskDataDirectory();
        $pathToZip = $path.'export.zip';
        $this->assertFileExists($pathToZip);
        $exportedFile = $this->api()->getFileContentFromZipPath($pathToZip, $task->taskGuid.'/apiTest.csv');
        self::$api->isCapturing() && file_put_contents($this->api()->getFile($fileToCompare, null, false), $exportedFile);
        //compare it
        $expectedResult = $this->api()->getFileContent($fileToCompare);
        $foundIds = [];
        
        //since the mqm ids are generated on each test run differently,
        //we have to replace them, by a unified counter, so that we can compare both files.
        //Just replacing the ids with a fixed text is no solution, since we can not recognize nesting errors then.
        $idReplacer = function($matches) use (&$foundIds){
            //since matches array is not filled up on first matches,
            //we just have to check the length of the matches
            $numMatches = count($matches);
            if($numMatches == 5 && $matches[4] !== ''){
                $id = $matches[4];
                $box = 'idref=""%s""';
            } else if($numMatches == 4 && $matches[2] !== '' && $matches[3] !== ''){
                $id = $matches[2];
                $box = 'xml:id=""xoverlappingTagId-%s_'.$matches[3].'""';
            } else if($numMatches == 2 && $matches[1] !== ''){
                $id = $matches[1];
                $box = 'xml:id=""x%s""';
            } else {
                error_log('ID MATCHING FAILED: '.print_r($matches,1));
            }
            $key = array_search($id, $foundIds, true);
            if($key === false) {
                $key = count($foundIds);
                $foundIds[] = $id;
            }
            return sprintf($box, $key);
        };
        $regex = '/xml:id=""x([0-9]+)""|xml:id=""xoverlappingTagId-([0-9]+)_([0-9]+)""|idref=""([0-9]+)""/';
        
        $foundIds = [];
        $expectedResult = preg_replace_callback($regex, $idReplacer, $expectedResult);
        $foundIds = [];
        $exportedFile = preg_replace_callback($regex, $idReplacer, $exportedFile);
        $this->assertEquals(rtrim($expectedResult), rtrim($exportedFile), 'Exported result does not equal to '.$fileToCompare);
    }
    
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        self::$api->login('testlector'); //logout testmanager to close task
        self::$api->login('testmanager'); //login again to delete
        self::$api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'open', 'id' => $task->id));
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}
