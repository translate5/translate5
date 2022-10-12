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
 * SegmentWorkflowTest imports a test task, adds workflow users, edits segments and finishes then the task.
 * The produced changes.xml and the workflow steps of the segments are checked. 
 */
class SegmentWorkflowTest extends editor_Test_ImportTest {

    protected static bool $termtaggerRequired = true;

    protected static array $forbiddenPlugins = [
        'editor_Plugins_ManualStatusCheck_Bootstrap'
    ];

    protected static array $requiredRuntimeOptions = ['editor.notification.saveXmlToFile' => 1];

    protected static function setupImport(Config $config): void
    {
        $config
            ->addTask('en', 'de', -1, 'simple-en-de.zip')
            ->addUser('testlector')
            ->addUser('testtranslator', 'waiting', 'translatorCheck')
            ->setProperty('taskName', static::NAME_PREFIX . 'SegmentWorkflowTest'); // TODO FIXME: we better generate data independent from resource-names ...
    }

    public function testTranslator() {
        //Implement tests for the new role translator and workflowstep translating!
        $this->markTestIncomplete("Implement tests for the new role translator and workflowstep translating!");
    }

    /**
     * edits some segments as lector, finish then the task
     * - checks for correct changes.xliff
     * - checks if task is open for translator and finished for lector
     * - modifies also segments with special characters to test encoding in changes.xml
     */
    public function testWorkflowFinishAsLector() {
        //check that testtranslator is waiting
        static::api()->login('testtranslator');
        $this->assertEquals('waiting', static::api()->reloadTask()->userState);
        
        //check that testlector is open
        static::api()->login('testlector');
        $this->assertEquals('open', static::api()->reloadTask()->userState);
        
        $task = static::api()->getTask();
        //open task for whole testcase
        static::api()->setTaskToEdit($task->id);
        
        //get segment list
        $segments = static::api()->getSegments();

        //initial segment finish count for workflow step lektoring
        $segmentFinishCount=0;
        //edit two segments
        $segToTest = $segments[2];
        static::api()->saveSegment($segToTest->id, 'PHP Handbuch');

        //the segment is reviewed, increment the finish count
        $segmentFinishCount++;
        
        $segToTest = $segments[6];
        $nbsp = json_decode('"\u00a0"');
        //the first "\u00a0 " (incl. the trailing whitespace) will be replaced by the content sanitizer to a single whitespace
        //the second single "\u00a0" must result in a single whitespace
        static::api()->saveSegment($segToTest->id, 'Apache'.$nbsp.' 2.x'.$nbsp.'auf'.$nbsp.$nbsp.'Unix-Systemen');

        //the segment is reviewed, increment the finish count
        $segmentFinishCount++;
        
        //edit a segment with special characters
        $segToTest = $segments[4];
        //multiple normal spaces should also be converted to single spaces
        static::api()->saveSegment($segToTest->id, "Installation auf   Unix-Systemen &amp; Umlaut Test äöü &lt; &lt;ichbinkeintag&gt; - bearbeitet durch den Testcode");
        
        //the segment is reviewed, increment the finish count
        $segmentFinishCount++;
        
        $segments = static::api()->getSegments();

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
        $reloadProgresTask= static::api()->reloadTask();
        //check if the local segmentFinishCount is the same as the calculated one for the task
        $this->assertEquals($segmentFinishCount, $reloadProgresTask->segmentFinishCount,'The segment finish count is not the same as the calculated one for the task!');
        
        //finishing the task
        $res = static::api()->setTaskToFinished($task->id);
        $this->assertEquals('finished', static::api()->reloadTask()->userState);
        
        //get the changes file
        $path = static::api()->getTaskDataDirectory();
        $foundChangeFiles = glob($path.'changes*.xliff');
        $this->assertNotEmpty($foundChangeFiles, 'No changes*.xliff file was written for taskGuid: '.$task->taskGuid);
        $foundChangeFile = end($foundChangeFiles);
        $this->assertFileExists($foundChangeFile);
        
        //no direct file assert equals possible here, since our diff format contains random sdl:revids
        //this revids has to be replaced before assertEqual
        $approvalFileContent = static::api()->replaceChangesXmlContent(static::api()->getFileContent('testWorkflowFinishAsLector-assert-equal.xliff'));
        $toCheck = static::api()->replaceChangesXmlContent(file_get_contents($foundChangeFile));
        $this->assertXmlStringEqualsXmlString($approvalFileContent, $toCheck);
        
        //check that task is finished for lector now
        $this->assertEquals('finished', static::api()->reloadTask()->userState);
        
        //check that task is open for translator now
        static::api()->login('testtranslator');
        $this->assertEquals('open', static::api()->reloadTask()->userState);
    }
}