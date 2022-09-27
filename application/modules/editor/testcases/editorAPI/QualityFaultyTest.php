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

        static::api()->addImportFile('MainTest/qm-terminology-en-de.zip');
        static::api()->import($task);
        static::api()->reloadTask();
        
        static::api()->addUser('testlector');
        
        //login in beforeTests means using this user in whole testcase!
        static::api()->login('testlector');
        
        $task = static::api()->getTask();
           //open task for whole testcase
        static::api()->setTaskToEdit($task->id);
        
        // we need some segments to play with
        static::$segments = static::api()->getSegments(null, 10);
        
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
        $jsonFile = 'testSegment1EditTarget.json';
        $segment1 = static::api()->saveSegment($segment1->id, $segment1Edit, null, $jsonFile);
        $this->assertSegmentEqualsJsonFile($jsonFile, $segment1);

        // edit segment 3, swap open/close tag
        $segment3 = static::$segments[1];
        $jsonFile = 'testSegment3EditTarget.json';
        $segment3 = static::api()->saveSegment($segment3->id, $segment3Edit, null, $jsonFile);
        $this->assertSegmentEqualsJsonFile($jsonFile, $segment3);
    }
    /**
     * Tests the generally availability and validity of the filter tree
     */
    public function testFilterQualityTree(){
        $jsonFile = 'expectedQualityFilterFaulty.json';
        $tree = static::api()->getJsonTree('/editor/quality', [], $jsonFile);
        $treeFilter = editor_Test_Model_Filter::createSingle('qtype', 'internal');
        $this->assertModelEqualsJsonFile('FilterQuality', $jsonFile, $tree, '', $treeFilter);
    }
    /**
     * Test the task qualities after being edited
     * @depends testMakeSegmentsFaulty
     */
    public function testTaskQualityTree(){
        $jsonFile = 'expectedTaskQualitiesFaulty.json';
        $tree = static::api()->getJson('editor/quality/task?&taskGuid='.urlencode(static::api()->getTask()->taskGuid), [], $jsonFile);
        $treeFilter = editor_Test_Model_Filter::createSingle('qtype', 'internal');
        $this->assertModelEqualsJsonFile('TaskQuality', $jsonFile, $tree, '', $treeFilter);
    }
    /**
     * Tests the task-Tooltip of the Task-Grid with the Faulty icons
     * @depends testMakeSegmentsFaulty
     */
    public function testTaskTooltip(){
        
        $file = static::api()->getFile('expectedTaskToolTipFaulty.html', null, false);
        $response = static::api()->get('editor/quality/tasktooltip?&taskGuid='.urlencode(static::api()->getTask()->taskGuid));
        $markup = $response->getBody();
        if(static::api()->isCapturing()){ file_put_contents($file, $markup); }
        $this->assertStringContainsString('</table>', $markup, 'Task Qualities ToolTip Markup does not match');
        $this->assertStringContainsString('<span class="x-grid-symbol t5-quality-faulty">', $markup, 'Task Qualities ToolTip Markup does not match'); // number of all MQMs
        $this->assertEquals(file_get_contents($file), $markup, 'Task Qualities ToolTip Markup does not match'); // this test mignt has to be adjusted due to the translation problematic
    }


    public static function afterTests(): void {
        $task = static::api()->getTask();
        static::api()->deleteTask($task->id, 'testmanager', 'testlector');
    }
}
