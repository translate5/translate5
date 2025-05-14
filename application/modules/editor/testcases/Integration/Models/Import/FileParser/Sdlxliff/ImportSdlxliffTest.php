<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Integration\Models\Import\FileParser\Sdlxliff;

use editor_Models_File;
use editor_Models_Import_FileParser;
use editor_Models_Import_FileParser_Sdlxliff as Sdlxliff;
use editor_Models_Task;
use MittagQI\Translate5\Test\UnitTestAbstract;
use ZfExtended_Factory;
use ZfExtended_Utils;

class ImportSdlxliffTest extends UnitTestAbstract
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
    public function testSuccess(string $file, string $expected, string $field, int $checkSegmentWithNr): void
    {
        $segmentFieldManager = \editor_Models_SegmentFieldManager::getForTaskGuid($this->task->getTaskGuid());

        $parser = new Sdlxliff(
            $file,
            'ImportSdlxliffTest.sdlxliff',
            (int) $this->file->getId(),
            $this->task,
        );
        $parser->setSegmentFieldManager($segmentFieldManager);

        $sp = new class($this->task, $expected, $field, $checkSegmentWithNr) extends \editor_Models_Import_SegmentProcessor {
            private int $segmentWithNr = 0;

            public function __construct(
                editor_Models_Task $task,
                private string $expected,
                private string $field,
                private readonly int $checkSegmentWithNr,
            ) {
                parent::__construct($task);
            }

            public function process(editor_Models_Import_FileParser $parser)
            {
                $this->segmentWithNr++;

                if ($this->segmentWithNr !== $this->checkSegmentWithNr) {
                    return false;
                }

                $fields = $parser->getFieldContents();

                ImportSdlxliffWithQuickInsertsListTagsTest::assertSame($this->expected, $fields[$this->field]['original']);

                return false;
            }
        };

        $parser->addSegmentProcessor($sp);

        $parser->parseFile();
    }

    public function expectedSegmentsProvider(): iterable
    {
        yield 'simple g tags' => [
            'file' => __DIR__ . '/testfiles/ImportSdlxliffTest/success.sdlxliff',
            'expected' => '<div class="open 672069643d2237383322 internal-tag ownttip"><span title="&lt;Hyper&gt;" class="short">&lt;1&gt;</span><span data-originalid="783" data-length="-1" class="full">&lt;Hyper&gt;</span></div><div class="open 672069643d2237383422 internal-tag ownttip"><span title="&lt;fct:Marker fct:MarkerType=&quot;Hypertext&quot;&gt;" class="short">&lt;2&gt;</span><span data-originalid="784" data-length="-1" class="full">&lt;fct:Marker fct:MarkerType=&quot;Hypertext&quot;&gt;</span></div>Gasflasche /s<div class="close 2f67 internal-tag ownttip"><span title="&lt;/fct:Marker&gt;" class="short">&lt;/2&gt;</span><span data-originalid="784" data-length="-1" class="full">&lt;/fct:Marker&gt;</span></div><div class="close 2f67 internal-tag ownttip"><span title="&lt;/Hyper&gt;" class="short">&lt;/1&gt;</span><span data-originalid="783" data-length="-1" class="full">&lt;/Hyper&gt;</span></div>https://manuals.fronius.com/html/4204102960',
            'field' => 'source',
            'checkSegmentWithNr' => 2,
        ];
    }
}
