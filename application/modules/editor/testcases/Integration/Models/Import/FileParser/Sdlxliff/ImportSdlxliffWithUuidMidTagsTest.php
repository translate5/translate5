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

class ImportSdlxliffWithUuidMidTagsTest extends UnitTestAbstract
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
     * @see          \editor_Models_Import_FileParser_Sdlxliff::parseSegment
     * @dataProvider expectedSegmentsProvider
     */
    public function testUserCases(string $filename, array $mrks): void
    {
        $segmentFieldManager = \editor_Models_SegmentFieldManager::getForTaskGuid($this->task->getTaskGuid());

        $parser = new Sdlxliff(
            __DIR__ . '/testfiles/ImportSdlxliffWithUuidMidTagsTest/' . $filename,
            'ImportSdlxliffWithUuidMidTagsTest.sdlxliff',
            (int) $this->file->getId(),
            $this->task,
        );
        $parser->setSegmentFieldManager($segmentFieldManager);

        $sp = new class($this->task, $mrks) extends \editor_Models_Import_SegmentProcessor {
            private int $callCounter = 0;

            public function __construct(
                editor_Models_Task $task,
                private array $mrks,
            ) {
                parent::__construct($task);
            }

            public function process(editor_Models_Import_FileParser $parser)
            {
                $fields = $parser->getFieldContents();

                ImportSdlxliffWithUuidMidTagsTest::assertSame(
                    $this->mrks[$this->callCounter],
                    $fields['target']['original']
                );
                $this->callCounter++;

                return false;
            }
        };

        $parser->addSegmentProcessor($sp);

        $parser->parseFile();
    }

    public function expectedSegmentsProvider(): iterable
    {
        yield 'tags-that-were-wrongly-parsed-as-duplicates' => [
            'tags-that-were-wrongly-parsed-as-duplicates.sdlxliff',
            [
                '<div class="single 6d726b206d747970653d22782d73646c2d6c6f636174696f6e22206d69643d2238346461623439632d363835332d346633362d383736662d643531303263336632393231222f internal-tag ownttip"><span title="&lt;InternalReference/&gt;" class="short">&lt;1/&gt;</span><span data-originalid="mrkSingle" data-length="-1" class="full">&lt;InternalReference/&gt;</span></div>Starter og generator | Nye dele uden depositum - nu med 3 års garanti ✓ Gennemprøvet kvalitet ✓ Nem håndtering ✓ Fordelagtig pris ✓<div class="single 6d726b206d747970653d22782d73646c2d6c6f636174696f6e22206d69643d2234333465663037382d626163312d343639322d393639642d643461353932646234356337222f internal-tag ownttip"><span title="&lt;InternalReference/&gt;" class="short">&lt;2/&gt;</span><span data-originalid="mrkSingle" data-length="-1" class="full">&lt;InternalReference/&gt;</span></div>',
            ],
        ];
    }
}
