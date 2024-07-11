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

use editor_Models_Segment_AutoStates as AutoStates;
use editor_Workflow_Default as DefaultWorkflow;
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\ImportTestAbstract;

/**
 * SegmentWorkflowTest imports a test task, adds workflow users, edits segments and finishes then the task.
 * The produced changes.xml and the workflow steps of the segments are checked.
 */
class SegmentWorkflowTest extends ImportTestAbstract
{
    public const WORKFLOW_COMPLEX = 'complex';

    public const USER_REVIEWER = 'testlector';

    public const USER_TRANSLATOR = 'testtranslator';

    protected static bool $termtaggerRequired = true;

    protected static array $requiredPlugins = [
        'editor_Plugins_TermTagger_Bootstrap',
    ];

    protected static array $forbiddenPlugins = [
        'editor_Plugins_ManualStatusCheck_Bootstrap',
    ];

    protected static array $requiredRuntimeOptions = [
        'editor.notification.saveXmlToFile' => 1,
        'runtimeOptions.import.edit100PercentMatch' => 0,
    ];

    /**
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    protected static function setupImport(Config $config): void
    {
        $config
            ->addTask('en', 'de', -1, 'simple-en-de.zip')
            ->addUser(self::USER_TRANSLATOR, workflowStep: 'firsttranslation')
            ->addUser(self::USER_REVIEWER, DefaultWorkflow::STATE_WAITING, 'review1stlanguage')
            ->addUser(self::USER_TRANSLATOR, DefaultWorkflow::STATE_WAITING, 'review2ndlanguage')
            ->setProperty('edit100PercentMatch', 0)
            ->setProperty('workflow', self::WORKFLOW_COMPLEX)
            ->setProperty('taskName', static::NAME_PREFIX . 'SegmentWorkflowTest');
    }

    public function testSegmentLocking(): void
    {
        $this->assertEquals(
            self::WORKFLOW_COMPLEX,
            static::api()->getTask()->workflow,
            'Task workflow is not as expected'
        );
    }

    /**
     * @depends testSegmentLocking
     * @throws Zend_Http_Client_Exception
     */
    public function testWorkflowSetup()
    {
        //check that USER_REVIEWER (in review1stlanguage) is waiting
        static::api()->login(self::USER_REVIEWER);
        $task = static::api()->reloadTask();
        $this->assertEquals(DefaultWorkflow::STATE_WAITING, $task->userState);

        //workflowstep is still the initial one
        $this->assertEquals('firsttranslation', $task->workflowStepName);

        //check that USER_TRANSLATOR is open (for translation)
        static::api()->login(self::USER_TRANSLATOR);
        $this->assertEquals(DefaultWorkflow::STATE_OPEN, static::api()->reloadTask()->userState);

        //USER_TRANSLATOR finish step translation
        static::api()->setTaskToEdit();
        $segments = static::api()->getSegments();

        //add missing translation by translator
        static::api()->saveSegment($segments[6]->id, 'Apache 2.x  auf Unix-Systemen');
        static::api()->setTaskToFinished();
        static::api()->setTaskToOpen();

        // workflow step changed now
        $this->assertEquals('review1stlanguage', static::api()->reloadTask()->workflowStepName);

        //USER_TRANSLATOR is now in waiting
        $this->assertEquals(DefaultWorkflow::STATE_FINISH, static::api()->reloadTask()->userState);

        //check that USER_REVIEWER (in review1stlanguage) is now open
        static::api()->login(self::USER_REVIEWER);
        $task = static::api()->reloadTask();
        $this->assertEquals(DefaultWorkflow::STATE_OPEN, $task->userState);
    }

    /**
     * edits some segments as reviewer, finish then the task
     * modifies also segments with special characters to test encoding in changes.xml
     *
     * @depends testWorkflowSetup
     * @throws Zend_Http_Client_Exception
     */
    public function testReviewAndFinish(): void
    {
        //open task for whole testcase
        static::api()->setTaskToEdit();

        //get segment list
        $segments = static::api()->getSegments();

        //initial segment finish count for workflow step lektoring
        $segmentFinishCount = 0;
        //edit two segments
        $segToTest = $segments[2];
        static::api()->saveSegment($segToTest->id, 'PHP Handbuch');

        //assert segment is locked in step review1stlanguage
        $this->assertEquals(AutoStates::LOCKED, $segments[3]->autoStateId, 'Segment is not locked for Reviewer.');

        //the segment is reviewed, increment the finish count
        $segmentFinishCount++;

        $segToTest = $segments[6];
        $nbsp = json_decode('"\u00a0"');
        //the first "\u00a0 " (incl. the trailing whitespace) will be replaced by
        // the content sanitizer to a single whitespace
        //the second single "\u00a0" must result in a single whitespace
        static::api()->saveSegment(
            $segToTest->id,
            'Apache' . $nbsp . ' 2.x' . $nbsp . 'auf' . $nbsp . $nbsp . 'Unix-Systemen'
        );

        //the segment is reviewed, increment the finish count
        $segmentFinishCount++;

        //edit a segment with special characters
        $segToTest = $segments[4];
        //multiple normal spaces should also be converted to single spaces
        static::api()->saveSegment(
            $segToTest->id,
            "Installation auf   Unix-Systemen &amp; Umlaut Test äöü &lt; &lt;ichbinkeintag&gt; "
            . "- bearbeitet durch den Testcode"
        );

        //the segment is reviewed, increment the finish count
        $segmentFinishCount++;

        $segments = static::api()->getSegments();

        //bulk check of all workflowStepNr fields
        $workflowStepNr = array_map(function ($item) {
            return $item->workflowStepNr;
        }, $segments);
        $this->assertEquals(['0', '0', '2', '0', '2', '0', '2'], $workflowStepNr);

        //bulk check of all autoStateId fields
        $workflowStep = array_map(function ($item) {
            return $item->workflowStep;
        }, $segments);

        $this->assertEquals(
            ['', '', 'review1stlanguage', '', 'review1stlanguage', '', 'review1stlanguage'],
            $workflowStep
        );

        //reloat the task and test the current workflow progress
        $reloadProgresTask = static::api()->reloadTask();

        //check if the local segmentFinishCount is the same as the calculated one for the task
        $this->assertEquals(
            $segmentFinishCount,
            $reloadProgresTask->segmentFinishCount,
            'The segment finish count is not the same as the calculated one for the task!'
        );

        //finishing the task - triggers an autoQA
        static::api()->setTaskToFinished();

        $task = static::api()->reloadTask();
        $this->assertEquals('finished', $task->userState);
        $this->assertEquals(
            'review2ndlanguage',
            $task->workflowStepName,
            'Workflow Step is not as expected'
        );
        // waiting for autoQA (triggered implicit by workflow on finish above)
        $this->waitForWorker(editor_Task_Operation_FinishingWorker::class, $task);
        static::api()->setTaskToOpen();
    }

    /**
     * checks for correct changes.xliff contents
     * checks if task is open for translator in step review2ndlanguage and finished for reviewer
     *
     * @depends testReviewAndFinish
     * @throws Zend_Http_Client_Exception
     */
    public function testChangesXliffAfterReview(): void
    {
        //get the changes file
        $path = static::api()->getTaskDataDirectory();
        $foundChangeFiles = glob($path . 'changes*.xliff');
        $this->assertNotEmpty(
            $foundChangeFiles,
            'No changes*.xliff file was written for taskGuid: ' . static::api()->getTask()->taskGuid
        );
        $foundChangeFile = end($foundChangeFiles);
        $this->assertFileExists($foundChangeFile);

        //no direct file assert equals possible here, since our diff format contains random sdl:revids
        //this revids has to be replaced before assertEqual
        $approvalFileContent = static::api()->replaceChangesXmlContent(
            static::api()->getFileContent('testWorkflowFinishAsLector-assert-equal.xliff')
        );

        $toCheck = static::api()->replaceChangesXmlContent(file_get_contents($foundChangeFile));
        $this->assertXmlStringEqualsXmlString($approvalFileContent, $toCheck);

        //check that task is finished for lector now
        $this->assertEquals('finished', static::api()->reloadTask()->userState);
    }

    /**
     * @throws Zend_Http_Client_Exception
     * @depends testChangesXliffAfterReview
     */
    public function testSecondReviewStep(): void
    {
        //check that task is open for translator now
        static::api()->login(self::USER_TRANSLATOR);
        $this->assertEquals('open', static::api()->reloadTask()->userState);

        static::api()->setTaskToEdit();
        $segments = static::api()->getSegments();

        //assert segment is not locked anymore (by editor_Workflow_Actions::changeEdit100PercentMatch workflow usage)
        $this->assertEquals(AutoStates::PRETRANSLATED, $segments[3]->autoStateId, 'Autostate is not as expected');

        $this->assertSegment(
            'Installation and <div class="term preferredTerm exact transFound" title="" data-tbxid="X">'
                . 'Configuration</div>',
            $segments[3]->source
        );
        $this->assertSegment(
            'Installation und <div class="term preferredTerm exact" title="" data-tbxid="X">Konfiguration</div>',
            $segments[3]->targetEdit
        );
    }

    protected function assertSegment($expected, $actual): void
    {
        $this->assertEquals($expected, preg_replace('#data-tbxid="[0-9a-f-]{36}"#', 'data-tbxid="X"', $actual));
    }
}
