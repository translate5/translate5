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
 * Testcase for the AutoQA feature
 * Tests the special case, an internal tag has a faulty HTML structure (opening/closing tags in wrong order, internal tags interleaving)
 */
class QualityFaultyTest extends editor_Test_JsonTest {
    
    /**
     * If set to true, all tests will be re-recorded !
     * @var boolean
     */
    static $captureMode = false; 
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
        
         
        $api->addImportFile('editorAPI/MainTest/qm-terminology-en-de.zip');
        $api->import($task);
        
        $api->addUser('testlector');
        
        //login in setUpBeforeClass means using this user in whole testcase!
        $api->login('testlector');
        
        $task = $api->getTask();
           //open task for whole testcase
        $api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'edit', 'id' => $task->id));
        
        // we need some segments to play with
        static::$segments = $api->requestJson('editor/segment?page=1&start=0&limit=10');
        
        static::assertEquals(10, count(static::$segments), 'Not enough segments in the imported task');
    }
    /**
     * manipulate two segments and invalidate their internal tag structure
     */
    public function testMakeSegmentsFaulty() {
        
        // we will edit segments 1 & 3
        
        // we made the tags overlapping
        $segment1Edit = 'Apache wird vielleicht multithreaded gebaut indem <div class="open 672069643d2233323822 internal-tag ownttip"><span class="short" title="&lt;var class=&quot;filename&quot;&gt;">&lt;1&gt;</span><span class="full" data-originalid="328" data-length="-1">&lt;var class=&quot;filename&quot;&gt;</span></div>worker selektiert wird.<div class="open 672069643d2233323922 internal-tag ownttip"><span class="short" title="&lt;var class=&quot;filename&quot;&gt;">&lt;2&gt;</span><span class="full" data-originalid="329" data-length="-1">&lt;var class=&quot;filename&quot;&gt;</span></div> MPM,<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/var&gt;">&lt;/1&gt;</span><span class="full" data-originalid="328" data-length="-1">&lt;/var&gt;</span></div> statt dem standard modul <div class="term supersededTerm exact" title="" data-tbxid="term_44427" data-t5qid="25122">prefork</div><div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/var&gt;">&lt;/2&gt;</span><span class="full" data-originalid="329" data-length="-1">&lt;/var&gt;</span></div> MPM, wenn Apache gebaut wird.';
        
        // we changed opener & closer
        $segment3Edit = '<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/strong&gt;">&lt;/1&gt;</span><span class="full" data-originalid="343" data-length="-1">&lt;/strong&gt;</span></div>Hinweis<div class="open 672069643d2233343322 internal-tag ownttip"><span class="short" title="&lt;strong class=&quot;note&quot;&gt;">&lt;1&gt;</span><span class="full" data-originalid="343" data-length="-1">&lt;strong class=&quot;note&quot;&gt;</span></div>:';
        
        // edit segment 1 and make their internal tags overlap
        $segment1 = static::$segments[1];
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', $segment1Edit, $segment1->id);
        $segment1 = $this->api()->requestJson('editor/segment/'.$segment1->id, 'PUT', $segmentData);
        if(static::$captureMode){ file_put_contents($this->api()->getFile('testSegment1EditTarget.json', null, false), json_encode($segment1, JSON_PRETTY_PRINT)); }
        $this->assertSegmentEqualsJsonFile('testSegment1EditTarget.json', $segment1);

        // edit segment 3, swap open/close tag
        $segment3 = static::$segments[1];
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', $segment3Edit, $segment3->id);
        $segment3 = $this->api()->requestJson('editor/segment/'.$segment3->id, 'PUT', $segmentData);
        if(static::$captureMode){ file_put_contents($this->api()->getFile('testSegment3EditTarget.json', null, false), json_encode($segment3, JSON_PRETTY_PRINT)); }
        $this->assertSegmentEqualsJsonFile('testSegment3EditTarget.json', $segment3);
    }
    /**
     * Tests the generally availability and validity of the filter tree
     */
    public function testFilterQualityTree(){
        
        $tree = $this->api()->requestJson('/editor/quality', 'GET', [], [], true);
        if(static::$captureMode){ file_put_contents($this->api()->getFile('expectedQualityFilterFaulty.json', null, false), json_encode($tree, JSON_PRETTY_PRINT)); }
        $this->assertModelEqualsJsonFile('FilterQuality', 'expectedQualityFilterFaulty.json', $tree);
    }
    /**
     * Test the task qualities after being edited
     * @depends testMakeSegmentsFaulty
     */
    public function testTaskQualityTree(){
        
        $tree = $this->api()->requestJson('editor/quality/task?&taskGuid='.urlencode(self::$api->getTask()->taskGuid));
        if(static::$captureMode){ file_put_contents($this->api()->getFile('expectedTaskQualitiesFaulty.json', null, false), json_encode($tree, JSON_PRETTY_PRINT)); }
        $this->assertModelEqualsJsonFile('TaskQuality', 'expectedTaskQualitiesFaulty.json', $tree);
    }
    /**
     * Tests the task-Tooltip of the Task-Grid with the Faulty icons
     * @depends testMakeSegmentsFaulty
     */
    public function testTaskTooltip(){
        
        $file = $this->api()->getFile('expectedTaskToolTipFaulty.html', null, false);
        $response = $this->api()->request('editor/quality/tasktooltip?&taskGuid='.urlencode(self::$api->getTask()->taskGuid));
        $markup = $response->getBody();
        if(static::$captureMode){ file_put_contents($file, $markup); }
        $this->assertStringContainsString('</table>', $markup, 'Task Qualities ToolTip Markup does not match');
        $this->assertStringContainsString('<span class="x-grid-symbol t5-quality-faulty">', $markup, 'Task Qualities ToolTip Markup does not match'); // number of all MQMs
        $this->assertEquals(file_get_contents($file), $markup, 'Task Qualities ToolTip Markup does not match'); // this test mignt has to be adjusted due to the translation problematic
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
