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
use MittagQI\Translate5\Segment\QueuedBatchUpdateWorker;
use MittagQI\Translate5\Test\Enums\TestUser;
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\ImportTestAbstract;

/**
 * SegmentWorkflowTest imports a test task, adds workflow users, edits segments and finishes then the task.
 * The produced changes.xml and the workflow steps of the segments are checked.
 */
class SegmentWorkflowTest extends ImportTestAbstract
{
    public const WORKFLOW_COMPLEX = 'complex';

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
            ->addUser(TestUser::TestTranslator->value, workflowStep: 'firsttranslation')
            ->addUser(TestUser::TestLector->value, DefaultWorkflow::STATE_WAITING, 'review1stlanguage')
            ->addUser(TestUser::TestTranslator->value, DefaultWorkflow::STATE_WAITING, 'review2ndlanguage')
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
        static::api()->login(TestUser::TestLector->value);
        $task = static::api()->reloadTask();
        $this->assertEquals(DefaultWorkflow::STATE_WAITING, $task->userState);

        //workflowstep is still the initial one
        $this->assertEquals('firsttranslation', $task->workflowStepName);

        //check that USER_TRANSLATOR is open (for translation)
        static::api()->login(TestUser::TestTranslator->value);
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
        static::api()->login(TestUser::TestLector->value);
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

        //reload the task and test the current workflow progress
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
        static::api()->login(TestUser::TestTranslator->value);
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

    /**
     * @throws Zend_Http_Client_Exception
     * @depends testSecondReviewStep
     */
    public function testDraftUndraftSegment(): void
    {
        $segments = static::api()->getSegments();
        $segToTest = $segments[0];

        // set one segment to draft state
        $this->updateSegmentStatus($segToTest->id, editor_Models_Segment_AutoStates::DRAFT);

        // check that job can not be finished with segments in draft state
        $json = static::api()->setTaskToFinished(failOnError: false);
        $this->assertEquals('409', $json->status);
        $this->assertEquals('E1751', $json->errorCode);

        // finalize segment
        $this->updateSegmentStatus($segToTest->id);

        // verify that no segments are in draft state
        $segments = static::api()->getSegments();
        $drafts = array_filter($segments, function ($item) {
            return (int) $item->autoStateId === editor_Models_Segment_AutoStates::DRAFT;
        });
        $this->assertEmpty($drafts);
    }

    /**
     * checks set all segments to draft state and that job can not be finished with a segment in draft state
     *
     * @throws Zend_Http_Client_Exception
     * @depends testDraftUndraftSegment
     */
    public function testSetAllSegmentsToDraftsAndTaskFinishError()
    {
        //static::api()->setTaskToEdit();
        $segments = static::api()->getSegments();
        $firstSegment = $segments[0];
        // set all segments to draft state
        $task = static::api()->getTask();
        $this->updateSegmentStatus($firstSegment->id, editor_Models_Segment_AutoStates::DRAFT, (int) $task->id);
        $this->waitForWorker(QueuedBatchUpdateWorker::class, $task);
        // verify that all segments are in draft state

        $segments = static::api()->getSegments();
        $notDrafts = array_filter($segments, function ($item) {
            return (int) $item->autoStateId !== editor_Models_Segment_AutoStates::DRAFT;
        });
        $this->assertEmpty($notDrafts);
        // check that job can not be finished with segments in draft state
        $json = static::api()->setTaskToFinished(failOnError: false);
        $this->assertEquals('409', $json->status);
        $this->assertEquals('E1751', $json->errorCode);
    }

    /**
     * @throws Zend_Http_Client_Exception
     * @depends testSetAllSegmentsToDraftsAndTaskFinishError
     */
    public function testFinalizeAllSegments()
    {
        $task = static::api()->reloadTask();
        $this->assertEquals('edit', $task->userState);

        $segments = static::api()->getSegments();
        // finalize all segments one by one
        foreach ($segments as $segment) {
            $this->updateSegmentStatus($segment->id);
        }

        // finalize all at once doesn't work for some reason
        //$this->updateSegmentStatus($segments[0]->id, editor_Models_Segment_AutoStates::PENDING, true);
        //$this->waitForWorker(QueuedBatchUpdateWorker::class, $task);

        /*WARN ZfExtended_NoAccessException: E9999 - In the meantime, the task was opened in read-only mode.
        in core in phpstorm://open?file=/application/modules/editor/src/Workflow/Assert/WriteableWorkflowAssert.php&line=89
        User: system (system user) ({00000000-0000-0000-0000-000000000000})

        #0 in phpstorm://open?file=/application/modules/editor/src/Segment/Operation/UpdateSegmentOperation.php&line=106 MittagQI\Translate5\Workflow\Assert\WriteableWorkflowAssert->assert('{f6dfc97b-2bb6-...', '{00000000-0000-...', Object(MittagQI\Translate5\Segment\Operation\DTO\ContextDto))
        #1 in phpstorm://open?file=/application/modules/editor/src/Segment/SyncStatus/SyncStatusService.php&line=230 MittagQI\Translate5\Segment\Operation\UpdateSegmentOperation->update(Object(editor_Models_Segment), Object(MittagQI\Translate5\Segment\Operation\DTO\UpdateSegmentDto), Object(MittagQI\Translate5\Segment\Operation\DTO\ContextDto), Object(MittagQI\Translate5\User\Model\User))
        */

        static::api()->setTaskToFinished(); // throws error if there are segments in draft state
    }

    protected function assertSegment($expected, $actual): void
    {
        $this->assertEquals($expected, preg_replace('#data-tbxid="[0-9a-f-]{36}"#', 'data-tbxid="X"', $actual));
    }

    /**
     * @throws Zend_Http_Client_Exception
     */
    private function updateSegmentStatus(
        int $segmentId,
        int $autoStateId = editor_Models_Segment_AutoStates::PENDING,
        int $allSegmentsInTaskId = 0
    ) {
        if (! $allSegmentsInTaskId) {
            static::api()->saveSegment($segmentId, additionalPutData: [
                'autoStateId' => $autoStateId,
            ]);

            return;
        }
        $params = [
            'filter' => '[]',
            'qualities' => '',
            'segmentId' => $segmentId,
        ];
        if ($autoStateId === editor_Models_Segment_AutoStates::DRAFT) {
            $params['draft'] = 1;
        }
        static::api()->post('editor/taskid/' . $allSegmentsInTaskId . '/segment/syncstatus/batch', $params);
    }
}
