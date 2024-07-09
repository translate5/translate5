<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Functional\Models\Import\FileParser\Sdlxliff;

use editor_Models_File;
use editor_Models_Import_FileParser;
use editor_Models_Import_FileParser_Sdlxliff as Sdlxliff;
use editor_Models_Task;
use MittagQI\Translate5\Test\UnitTestAbstract;
use ZfExtended_Factory;
use ZfExtended_Utils;

class ImportSdlxliffWithQuickInsertsListTagsTest extends UnitTestAbstract
{
    private editor_Models_Task $task;

    private editor_Models_File $file;

    public function setUp(): void
    {
        parent::setUp();

        $customer = ZfExtended_Factory::get(\editor_Models_Customer_Customer::class);
        $customer->loadByDefaultCustomer();

        $workflow = ZfExtended_Factory::get(\editor_Models_Workflow::class);
        $workflow->loadByName('default');

        $workflowStep = ZfExtended_Factory::get(\editor_Models_Workflow_Step::class);
        /** @var array{id: int} $step */
        $step = $workflowStep->loadByWorkflow($workflow)[0];

        $this->task = ZfExtended_Factory::get(editor_Models_Task::class);
        $this->task->setTaskGuid(ZfExtended_Utils::uuid());
        $this->task->setTaskNr('1');
        $this->task->setCustomerId((int) $customer->getId());
        $this->task->setState('Import');
        $this->task->setTaskName('Test Task');
        $this->task->setTaskType('translate');
        $this->task->setWorkflow($workflow->getName());
        $this->task->setWorkflowStep($step['id']);
        $this->task->setSourceLang(4);
        $this->task->setTargetLang(5);
        $this->task->save();

        $this->file = ZfExtended_Factory::get(editor_Models_File::class);
        $this->file->setTaskGuid($this->task->getTaskGuid());
        $this->file->save();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->file->delete();
        $this->task->delete();
    }

    /**
     * @see \editor_Models_Import_FileParser_Sdlxliff::parseSegment
     * @dataProvider expectedSegmentsProvider
     */
    public function testSuccess(string $expectedTarget): void
    {
        $segmentFieldManager = \editor_Models_SegmentFieldManager::getForTaskGuid($this->task->getTaskGuid());

        $parser = new Sdlxliff(
            __DIR__ . '/testfiles/ImportSdlxliffWithQuickInsertsListTagsTest/success.sdlxliff',
            'ImportSdlxliffWithQuickInsertsListTags.sdlxliff',
            (int) $this->file->getId(),
            $this->task,
        );
        $parser->setSegmentFieldManager($segmentFieldManager);

        $sp = new class($this->task, $expectedTarget) extends \editor_Models_Import_SegmentProcessor {
            public function __construct(
                editor_Models_Task $task,
                private string $expectedTarget,
            ) {
                parent::__construct($task);
            }

            public function process(editor_Models_Import_FileParser $parser)
            {
                $fields = $parser->getFieldContents();

                ImportSdlxliffWithQuickInsertsListTagsTest::assertSame($this->expectedTarget, $fields['source']['original']);

                return false;
            }
        };

        $parser->addSegmentProcessor($sp);

        $parser->parseFile();
    }

    public function expectedSegmentsProvider(): iterable
    {
        yield '2 "Bold" ids + 1 "SmallCaps"' => [
            '<div class="open 672069643d22426f6c6422 internal-tag ownttip"><span title="Bold" class="short">&lt;1&gt;</span><span data-originalid="Bold" data-length="-1" class="full">Bold</span></div>Source text 1.<div class="close 2f67 internal-tag ownttip"><span title="Bold" class="short">&lt;/1&gt;</span><span data-originalid="Bold" data-length="-1" class="full">Bold</span></div> <div class="open 672069643d22426f6c6422 internal-tag ownttip"><span title="Bold" class="short">&lt;2&gt;</span><span data-originalid="Bold" data-length="-1" class="full">Bold</span></div>Source text 2.<div class="close 2f67 internal-tag ownttip"><span title="Bold" class="short">&lt;/2&gt;</span><span data-originalid="Bold" data-length="-1" class="full">Bold</span></div> <div class="open 672069643d22536d616c6c4361707322 internal-tag ownttip"><span title="SmallCaps" class="short">&lt;3&gt;</span><span data-originalid="SmallCaps" data-length="-1" class="full">SmallCaps</span></div>Source text 3.<div class="close 2f67 internal-tag ownttip"><span title="SmallCaps" class="short">&lt;/3&gt;</span><span data-originalid="SmallCaps" data-length="-1" class="full">SmallCaps</span></div>',
        ];
    }

    public function testThrowsExceptionOnInvalidFormatTagId(): void
    {
        $segmentFieldManager = \editor_Models_SegmentFieldManager::getForTaskGuid($this->task->getTaskGuid());

        $parser = new Sdlxliff(
            __DIR__ . '/testfiles/ImportSdlxliffWithQuickInsertsListTagsTest/throwsExceptionOnInvalidFormatTagId.sdlxliff',
            'ImportSdlxliffWithQuickInsertsListTags.sdlxliff',
            (int) $this->file->getId(),
            $this->task,
        );
        $parser->setSegmentFieldManager($segmentFieldManager);

        self::expectException(\editor_Models_Import_FileParser_Exception::class);
        self::expectExceptionCode(1609);

        $parser->parseFile();
    }
}
