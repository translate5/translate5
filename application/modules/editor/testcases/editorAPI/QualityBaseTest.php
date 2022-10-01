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

    protected static array $requiredRuntimeOptions = [
        'autoQA.enableInternalTagCheck' => 1,
        'autoQA.enableEdited100MatchCheck' => 1,
        'autoQA.enableUneditedFuzzyMatchCheck' => 1,
        'autoQA.enableMqmTags' => 1,
        'autoQA.enableQm' => 1
    ];
    /**
     * 
     * @var stdClass[]
     */
    private static $segments = [];
    
    public static function beforeTests(): void {

        $task = array(
            'sourceLang' => 'en',
            'targetLang' => 'de',
            'edit100PercentMatch' => true,
            'lockLocked' => 1,
        );

        static::api()->addImportFile('MainTest/csv-with-mqm-en-de.zip');
        static::api()->import($task);
        
        static::api()->addUser('testlector');
        
        //login in beforeTests means using this user in whole testcase!
        static::api()->login('testlector');
        
        $task = static::api()->getTask();
           //open task for whole testcase
        static::api()->setTaskToEdit($task->id);
        
        // we need some segments to play with
        static::$segments = static::api()->getSegments(null, 10);
        
        static::assertEquals(count(static::$segments), 10, 'Not enough segments in the imported task');
    }
   
    /**
     * Tests the generally availability and validity of the filter tree
     */
    public function testFilterQualityTree(){
        $fileName = 'expectedQualityFilter.json';
        $tree = static::api()->getJsonTree('/editor/quality', [], $fileName);
        $this->assertModelEqualsJsonFile('FilterQuality', $fileName, $tree);
    }
    /**
     * Checks the validity of the task qualities
     */
    public function testTaskQualityTree(){
        $fileName = 'expectedTaskQualities.json';
        $tree = static::api()->getJson('editor/quality/task?&taskGuid='.urlencode(static::api()->getTask()->taskGuid), [], $fileName);
        $this->assertModelEqualsJsonFile('TaskQuality', $fileName, $tree);
    }
    /**
     * Tests the task-Tooltip of the Task-Grid
     */
    public function testTaskTooltip(){
        $fileName = 'expectedTaskToolTip.html';
        $result = static::api()->getRaw('editor/quality/tasktooltip?&taskGuid='.urlencode(self::$api->getTask()->taskGuid), [], $fileName);
        $this->assertFalse(static::api()->isJsonResultError($result), 'Task Qualities ToolTip Markup could not be requested');
        $this->assertStringContainsString('</table>', $result->data, 'Task Qualities ToolTip Markup does not match');
        $this->assertStringContainsString('<td>487</td>', $result->data, 'Task Qualities ToolTip Markup does not match'); // number of all MQMs
        $this->assertFileContents($fileName, $result->data, 'Task Qualities ToolTip Markup does not match'); // this test might has to be adjusted due to the translation problematic
    }
    /**
     * Tests the qualities fetched for a segment
     */
    public function testSegmentQualities(){
        $fileName = 'expectedSegmentQualities0.json';
        $qualities = static::api()->getJson('/editor/quality/segment?segmentId='.static::$segments[0]->id, [], $fileName);
        $qualityFilter = editor_Test_Model_Filter::createMulti('type', ['mqm', 'spellcheck']);
        $this->assertModelsEqualsJsonFile('SegmentQuality', $fileName, $qualities, '', $qualityFilter);
        
        $fileName = 'expectedSegmentQualities4.json';
        $qualities = static::api()->getJson('/editor/quality/segment?segmentId='.static::$segments[4]->id, [], $fileName);
        $qualityFilter = editor_Test_Model_Filter::createSingle('type', 'mqm');
        $this->assertModelsEqualsJsonFile('SegmentQuality', $fileName, $qualities, '', $qualityFilter);
        
        $fileName = 'expectedSegmentQualities9.json';
        $qualities = static::api()->getJson('/editor/quality/segment?segmentId='.static::$segments[9]->id, [], $fileName);
        $qualityFilter = editor_Test_Model_Filter::createMulti('type', ['mqm', 'spellcheck']);
        $this->assertModelsEqualsJsonFile('SegmentQuality', $fileName, $qualities, '', $qualityFilter);
    }
    /**
     * Tests the adding / removal of QM qualities
     */
    public function testSetUnsetQm(){
        
        $fileName = 'expectedSetSegmentQmAdd0.json';
        $actual = static::api()->getJson('/editor/quality/segmentqm?segmentId='.static::$segments[0]->id.'&categoryIndex=4&qmaction=add', [], $fileName);
        $this->assertModelEqualsJsonFileRow('SegmentQuality', $fileName, $actual);
        
        $fileName = 'expectedSetSegmentQmAdd4.json';
        $actual = static::api()->getJson('/editor/quality/segmentqm?segmentId='.static::$segments[4]->id.'&categoryIndex=1&qmaction=add', [], $fileName);
        $this->assertModelEqualsJsonFileRow('SegmentQuality', $fileName, $actual);
        
        $fileName = 'expectedSetSegmentQmAdd9.json';
        $actual = static::api()->getJson('/editor/quality/segmentqm?segmentId='.static::$segments[9]->id.'&categoryIndex=3&qmaction=add', [], $fileName);
        $this->assertModelEqualsJsonFileRow('SegmentQuality', $fileName, $actual);
        
        $fileName = 'expectedSetSegmentQmRemove0.json';
        $actual = static::api()->getJson('/editor/quality/segmentqm?segmentId='.static::$segments[0]->id.'&categoryIndex=4&qmaction=remove', [], $fileName);
        $this->assertModelEqualsJsonFileRow('SegmentQuality', $fileName, $actual);
    }
    /**
     * Tests setting a quality to false positive
     * @depends testSetUnsetQm
     */
    public function testSetFalsePositive(){

        // we test with the mqm-qualities only
        $qualities = $this->_fetchMqmQualities(static::$segments[0]->id);
        $qualities0id = $qualities[0]->id; // needed for later revert false positive test
        
        $fileName = 'expectedFalsePositive0-0.json';
        $actual = static::api()->getJson('/editor/quality/falsepositive?id='.$qualities0id.'&falsePositive=1', [], $fileName);
        $this->assertObjectEqualsJsonFile($fileName, $actual);

        $fileName = 'expectedFalsePositive0-1.json';
        $actual = static::api()->getJson('/editor/quality/falsepositive?id='.$qualities[1]->id.'&falsePositive=1', [], $fileName);
        $this->assertObjectEqualsJsonFile($fileName, $actual);
        
        $fileName = 'expectedFalsePositive0-2.json';
        $actual = static::api()->getJson('/editor/quality/falsepositive?id='.$qualities[2]->id.'&falsePositive=1', [], $fileName);
        $this->assertObjectEqualsJsonFile($fileName, $actual);
        
        $qualities = $this->_fetchMqmQualities(static::$segments[9]->id);

        $fileName = 'expectedFalsePositive9-0.json';
        $actual = static::api()->getJson('/editor/quality/falsepositive?id='.$qualities[0]->id.'&falsePositive=1', [], $fileName);
        $this->assertObjectEqualsJsonFile($fileName, $actual);

        $fileName = 'expectedNotFalsePositive0-0.json';
        $actual = static::api()->getJson('/editor/quality/falsepositive?id='.$qualities0id.'&falsePositive=0', [], $fileName);
        $this->assertObjectEqualsJsonFile($fileName, $actual);
    }

    /**
     * Tests the filter & task model again after the added Qms & added falsePositives
     * @depends testSetUnsetQm
     * @depends testSetFalsePositive
     */
    public function testFilterAndTaskAfterChanges(){
        
        $fileName = 'expectedQualityFilterChanged.json';
        $tree = static::api()->getJsonTree('/editor/quality', [], $fileName);
        $this->assertModelEqualsJsonFile('FilterQuality', $fileName, $tree);
        
        $fileName = 'expectedTaskQualitiesChanged.json';
        $tree = static::api()->getJson('/editor/quality/task?&taskGuid='.urlencode(static::api()->getTask()->taskGuid), [], $fileName);
        $this->assertModelEqualsJsonFile('TaskQuality', $fileName, $tree);
    }

    public static function afterTests(): void {
        $task = static::api()->getTask();
        static::api()->deleteTask($task->id, 'testmanager', 'testlector');
    }

    /**
     * @param int $segmentId
     * @return array
     */
    private function _fetchMqmQualities(int $segmentId) : array {
        $qualities = static::api()->getJson('/editor/quality/segment?segmentId='.$segmentId);
        $mqmQualities = [];
        foreach($qualities as $quality){
            if($quality->type == 'mqm'){
                $mqmQualities[] = $quality;
            }
        }
        return $mqmQualities;
    }
}
