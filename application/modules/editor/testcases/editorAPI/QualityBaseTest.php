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
 * Testcase for all endpoints of the AutoQA feature
 * One Problem that might occur is, that the text's (usually text-prop) of the quality models in fact are translated strings that obviously can change. One solution would be, to not compare those props
 */
class QualityBaseTest extends editor_Test_JsonTest {
    
    /**
     * 
     * @var stdClass[]
     */
    static $segments = [];
    
    public static function setUpBeforeClass(): void {
       
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);

        $task = array(
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
            'lockLocked' => 1,
        );
        
        self::assertAppState();
        self::assertNeededUsers();
        self::assertLogin('testmanager');

        $tests = array(
            'runtimeOptions.autoQA.enableInternalTagCheck' => 1,
            'runtimeOptions.autoQA.enableEdited100MatchCheck' => 1,
            'runtimeOptions.autoQA.enableUneditedFuzzyMatchCheck' => 1,
            'runtimeOptions.autoQA.enableMqmTags' => 1,
            'runtimeOptions.autoQA.enableQm' => 1
        );
        self::$api->testConfig($tests);
        
         
        $api->addImportFile('editorAPI/MainTest/csv-with-mqm-en-de.zip');
        $api->import($task);
        
        $api->addUser('testlector');
        
        //login in setUpBeforeClass means using this user in whole testcase!
        $api->login('testlector');
        
        $task = $api->getTask();
           //open task for whole testcase
        $api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'edit', 'id' => $task->id));
        
        // we need some segments to play with
        static::$segments = $api->requestJson('editor/segment?page=1&start=0&limit=10');
        
        static::assertEquals(count(static::$segments), 10, 'Not enough segments in the imported task');
    }
   
    /**
     * Tests the generally availability and validity of the filter tree
     */
    public function testFilterQualityTree(){
        $fileName = 'expectedQualityFilter.json';
        $tree = $this->api()->getJsonTree('/editor/quality', [], $fileName);
        $this->assertModelEqualsJsonFile('FilterQuality', $fileName, $tree);
    }
    /**
     * Checks the validity of the task qualities
     */
    public function testTaskQualityTree(){
        $fileName = 'expectedTaskQualities.json';
        $tree = $this->api()->getJson('editor/quality/task?&taskGuid='.urlencode(self::$api->getTask()->taskGuid), [], $fileName);
        $this->assertModelEqualsJsonFile('TaskQuality', $fileName, $tree);
    }
    /**
     * Tests the task-Tooltip of the Task-Grid
     */
    public function testTaskTooltip(){
        $fileName = 'expectedTaskToolTip.html';
        $markup = $this->api()->getRaw('editor/quality/tasktooltip?&taskGuid='.urlencode(self::$api->getTask()->taskGuid), [], $fileName);
        $this->assertStringContainsString('</table>', $markup, 'Task Qualities ToolTip Markup does not match');
        $this->assertStringContainsString('<td>487</td>', $markup, 'Task Qualities ToolTip Markup does not match'); // number of all MQMs 
        $this->assertFileContents($fileName, $markup, 'Task Qualities ToolTip Markup does not match'); // this test might has to be adjusted due to the translation problematic
    }
    /**
     * Tests the qualities fetched for a segment
     */
    public function testSegmentQualities(){
        $fileName = 'expectedSegmentQualities0.json';
        $qualities = $this->api()->getJson('/editor/quality/segment?segmentId='.static::$segments[0]->id, [], $fileName);
        $this->assertModelsEqualsJsonFile('SegmentQuality', $fileName, $qualities);
        
        $fileName = 'expectedSegmentQualities4.json';
        $qualities = $this->api()->getJson('/editor/quality/segment?segmentId='.static::$segments[4]->id, [], $fileName);
        $this->assertModelsEqualsJsonFile('SegmentQuality', $fileName, $qualities);
        
        $fileName = 'expectedSegmentQualities9.json';
        $qualities = $this->api()->getJson('/editor/quality/segment?segmentId='.static::$segments[9]->id, [], $fileName);
        $this->assertModelsEqualsJsonFile('SegmentQuality', $fileName, $qualities);
    }
    /**
     * Tests the adding / removal of QM qualities
     */
    public function testSetUnsetQm(){
        
        $fileName = 'expectedSetSegmentQmAdd0.json';
        $actual = $this->api()->getJson('/editor/quality/segmentqm?segmentId='.static::$segments[0]->id.'&categoryIndex=4&qmaction=add', [], $fileName);
        $this->assertModelEqualsJsonFileRow('SegmentQuality', $fileName, $actual);
        
        $fileName = 'expectedSetSegmentQmAdd4.json';
        $actual = $this->api()->getJson('/editor/quality/segmentqm?segmentId='.static::$segments[4]->id.'&categoryIndex=1&qmaction=add', [], $fileName);
        $this->assertModelEqualsJsonFileRow('SegmentQuality', $fileName, $actual);
        
        $fileName = 'expectedSetSegmentQmAdd9.json';
        $actual = $this->api()->getJson('/editor/quality/segmentqm?segmentId='.static::$segments[9]->id.'&categoryIndex=3&qmaction=add', [], $fileName);
        $this->assertModelEqualsJsonFileRow('SegmentQuality', $fileName, $actual);
        
        $fileName = 'expectedSetSegmentQmRemove0.json';
        $actual = $this->api()->getJson('/editor/quality/segmentqm?segmentId='.static::$segments[0]->id.'&categoryIndex=4&qmaction=remove', [], $fileName);
        $this->assertModelEqualsJsonFileRow('SegmentQuality', $fileName, $actual);
    }
    /**
     * Tests setting a quality to false positive
     * @depends testSetUnsetQm
     */
    public function testSetFalsePositive(){
        // http://translate5.local/editor/quality/falsepositive?_dc=1620166854404&id=13158&falsePositive=1
        $qualities = $this->api()->requestJson('/editor/quality/segment?segmentId='.static::$segments[0]->id);
        $qualityId = $qualities[0]->id;
        
        $fileName = 'expectedFalsePositive0-0.json';
        $actual = $this->api()->getJson('/editor/quality/falsepositive?id='.$qualities[0]->id.'&falsePositive=1', [], $fileName);
        $this->assertObjectEqualsJsonFile($fileName, $actual);
        
        $fileName = 'expectedFalsePositive0-1.json';
        $actual = $this->api()->getJson('/editor/quality/falsepositive?id='.$qualities[1]->id.'&falsePositive=1', [], $fileName);
        $this->assertObjectEqualsJsonFile($fileName, $actual);
        
        $fileName = 'expectedFalsePositive0-2.json';
        $actual = $this->api()->getJson('/editor/quality/falsepositive?id='.$qualities[2]->id.'&falsePositive=1', [], $fileName);
        $this->assertObjectEqualsJsonFile($fileName, $actual);
        
        $qualities = $this->api()->getJson('/editor/quality/segment?segmentId='.static::$segments[9]->id);
        
        $fileName = 'expectedFalsePositive9-0.json';
        $actual = $this->api()->getJson('/editor/quality/falsepositive?id='.$qualities[0]->id.'&falsePositive=1', [], $fileName);
        $this->assertObjectEqualsJsonFile($fileName, $actual);
        
        $fileName = 'expectedNotFalsePositive0-0.json';
        $actual = $this->api()->getJson('/editor/quality/falsepositive?id='.$qualityId.'&falsePositive=0', [], $fileName);
        $this->assertObjectEqualsJsonFile($fileName, $actual);
    }
    /**
     * Tests the filter & task model again after the added Qms & added falsePositives
     * @depends testSetUnsetQm
     * @depends testSetFalsePositive
     */
    public function testFilterAndTaskAfterChanges(){
        
        $fileName = 'expectedQualityFilterChanged.json';
        $tree = $this->api()->getJsonTree('/editor/quality', [], $fileName);
        $this->assertModelEqualsJsonFile('FilterQuality', $fileName, $tree);
        
        $fileName = 'expectedTaskQualitiesChanged.json';
        $tree = $this->api()->getJson('editor/quality/task?&taskGuid='.urlencode(self::$api->getTask()->taskGuid), [], $fileName);
        $this->assertModelEqualsJsonFile('TaskQuality', $fileName, $tree);
    }

    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testlector');
        self::$api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'open', 'id' => $task->id));
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
    }
}
