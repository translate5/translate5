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
 * SegmentWorkflowTest imports a test task, adds workflow users, edits segments and finishes then the task.
 * The produced changes.xml and the workflow steps of the segments are checked. 
 */
class SegmentWorkflowTest extends \ZfExtended_Test_ApiTestcase {
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
        $api->addImportFile('SegmentWorkflowTest/simple-en-de.zip');
        $api->import($task);
        
        //FIXME improve this test by using two lector users to test after all finish with multiple users
        $api->reloadTask();
        $api->addUser('testlector');
        $api->reloadTask();
        $api->addUser('testtranslator', 'waiting', 'translatorCheck');
    }
    
    public function testTranslator() {
        //Implement tests for the new role translator and workflowstep translating!
        $this->markTestIncomplete("Implement tests for the new role translator and workflowstep translating!");
    }
    
    /**
     * tests if config is correct for testing changes.xliff 
     */
    public function testSaveXmlToFile() {
        $config = $this->api()->getJson('editor/config', array(
            'filter' => '[{"type":"string","value":"runtimeOptions.editor.notification.saveXmlToFile","property":"name","operator":"like"}]',
        ));
        $this->assertCount(1, $config);
        $this->assertEquals(1, $config[0]->value);
    }
    
    /**
     * edits some segments as lector, finish then the task
     * - checks for correct changes.xliff
     * - checks if task is open for translator and finished for lector
     * - modifies also segments with special characters to test encoding in changes.xml
     */
    public function testWorkflowFinishAsLector() {
        //check that testtranslator is waiting
        $this->api()->login('testtranslator');
        $this->assertEquals('waiting', $this->api()->reloadTask()->userState);
        
        //check that testlector is open
        $this->api()->login('testlector');
        $this->assertEquals('open', $this->api()->reloadTask()->userState);
        
        $task = $this->api()->getTask();
        //open task for whole testcase
        $this->api()->putJson('editor/task/'.$task->id, array('userState' => 'edit', 'id' => $task->id));
        
        //get segment list
        $segments = $this->api()->getSegments();

        //initial segment finish count for workflow step lektoring
        $segmentFinishCount=0;
        //edit two segments
        $segToTest = $segments[2];
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', 'PHP Handbuch', $segToTest->id);
        $this->api()->putJson('editor/segment/'.$segToTest->id, $segmentData);

        //the segment is reviewed, increment the finish count
        $segmentFinishCount++;
        
        $segToTest = $segments[6];
        $nbsp = json_decode('"\u00a0"');
        //the first "\u00a0 " (incl. the trailing whitespace) will be replaced by the content sanitizer to a single whitespace
        //the second single "\u00a0" must result in a single whitespace
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', 'Apache'.$nbsp.' 2.x'.$nbsp.'auf'.$nbsp.$nbsp.'Unix-Systemen', $segToTest->id);
        $this->api()->putJson('editor/segment/'.$segToTest->id, $segmentData);
        
        //the segment is reviewed, increment the finish count
        $segmentFinishCount++;
        
        //edit a segment with special characters
        $segToTest = $segments[4];
        //multiple normal spaces should also be converted to single spaces
        $segmentData = $this->api()->prepareSegmentPut('targetEdit', "Installation auf   Unix-Systemen &amp; Umlaut Test äöü &lt; &lt;ichbinkeintag&gt; - bearbeitet durch den Testcode", $segToTest->id);
        $this->api()->putJson('editor/segment/'.$segToTest->id, $segmentData);
        
        //the segment is reviewed, increment the finish count
        $segmentFinishCount++;
        
        $segments = $this->api()->getSegments();

        //bulk check of all workflowStepNr fields
        $workflowStepNr = array_map(function($item){
            return $item->workflowStepNr;
        }, $segments);
        $this->assertEquals(array('0','0','1','0','1','0','1'), $workflowStepNr);
        
        //bulk check of all autoStateId fields
        $workflowStep = array_map(function($item){
            return $item->workflowStep;
        }, $segments);
        $this->assertEquals(array('','','reviewing','','reviewing','','reviewing'), $workflowStep);
        
        //reloat the task and test the current workflow progress
        $reloadProgresTask= $this->api()->reloadTask();
        //check if the local segmentFinishCount is the same as the calculated one for the task
        $this->assertEquals($segmentFinishCount, $reloadProgresTask->segmentFinishCount,'The segment finish count is not the same as the calculated one for the task!');
        
        //finishing the task
        $res = $this->api()->putJson('editor/task/'.$task->id, array('userState' => 'finished', 'id' => $task->id));
        $this->assertEquals('finished', $this->api()->reloadTask()->userState);
        
        //get the changes file
        $path = $this->api()->getTaskDataDirectory();
        $foundChangeFiles = glob($path.'changes*.xliff');
        $this->assertNotEmpty($foundChangeFiles, 'No changes*.xliff file was written for taskGuid: '.$task->taskGuid);
        $foundChangeFile = end($foundChangeFiles);
        $this->assertFileExists($foundChangeFile);
        
        //no direct file assert equals possible here, since our diff format contains random sdl:revids
        //this revids has to be replaced before assertEqual
        $approvalFileContent = $this->api()->replaceChangesXmlContent($this->api()->getFileContent('testWorkflowFinishAsLector-assert-equal.xliff'));
        $toCheck = $this->api()->replaceChangesXmlContent(file_get_contents($foundChangeFile));
        $this->assertXmlStringEqualsXmlString($approvalFileContent, $toCheck);
        
        //check that task is finished for lector now
        $this->assertEquals('finished', $this->api()->reloadTask()->userState);
        
        //check that task is open for translator now
        $this->api()->login('testtranslator');
        $this->assertEquals('open', $this->api()->reloadTask()->userState);
    }
    
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        //open task for whole testcase
        self::$api->login('testmanager');
        self::$api->delete('editor/task/'.$task->id);
    }
}