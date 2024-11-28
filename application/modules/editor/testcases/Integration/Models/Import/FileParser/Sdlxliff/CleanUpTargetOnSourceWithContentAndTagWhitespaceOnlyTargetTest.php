<?php

namespace MittagQI\Translate5\Test\Integration\Models\Import\FileParser\Sdlxliff;

use editor_Models_Config;
use editor_Models_Customer_Customer;
use editor_Models_File;
use editor_Models_Import_FileParser;
use editor_Models_Import_FileParser_Sdlxliff as Sdlxliff;
use editor_Models_Task;
use editor_Models_TaskUserTracking;
use editor_Models_Workflow;
use editor_Models_Workflow_Step;
use MittagQI\Translate5\Test\UnitTestAbstract;
use ZfExtended_Factory;
use ZfExtended_Utils;

class CleanUpTargetOnSourceWithContentAndTagWhitespaceOnlyTargetTest extends UnitTestAbstract
{
    private editor_Models_Task $task;

    private editor_Models_File $file;

    public function setUp(): void
    {
        parent::setUp();

        $customer = ZfExtended_Factory::get(editor_Models_Customer_Customer::class);
        $customer->loadByDefaultCustomer();

        $workflow = ZfExtended_Factory::get(editor_Models_Workflow::class);
        $workflow->loadByName('default');

        $workflowStep = ZfExtended_Factory::get(editor_Models_Workflow_Step::class);
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

        $config = ZfExtended_Factory::get(editor_Models_Config::class);
        $config->loadByName('runtimeOptions.import.sdlxliff.cleanUpTargetOnSourceWithContentAndTagWhitespaceOnlyTarget');
        $config->setValue('0');
        $config->save();

        $this->file->delete();
        $this->task->delete();
    }

    /**
     * @dataProvider expectedSegmentsProvider
     */
    public function test(string $configValue, string $expectedTarget): void
    {
        $config = ZfExtended_Factory::get(editor_Models_Config::class);
        $config->loadByName('runtimeOptions.import.sdlxliff.cleanUpTargetOnSourceWithContentAndTagWhitespaceOnlyTarget');
        $config->setValue($configValue);
        $config->save();

        $db = \Zend_Db_Table::getDefaultAdapter();
        $trackingTable = ZfExtended_Factory::get(editor_Models_TaskUserTracking::class)->db;
        $tableStatus = $db->fetchRow("SHOW TABLE STATUS WHERE Name = '{$trackingTable->info($trackingTable::NAME)}'");

        $trackId = (int) $tableStatus['Auto_increment'];

        $expectedTarget = str_replace(
            [
                'translate5::id',
                'sylvi::id',
                'marc::id',
            ],
            [
                $trackId++,
                $trackId++,
                $trackId++,
            ],
            $expectedTarget
        );
        $segmentFieldManager = \editor_Models_SegmentFieldManager::getForTaskGuid($this->task->getTaskGuid());

        $parser = new Sdlxliff(
            __DIR__ . '/testfiles/CleanUpTargetOnSourceWithContentAndTagWhitespaceOnlyTargetTest.sdlxliff',
            'CleanUpTargetOnSourceWithContentAndTagWhitespaceOnlyTargetTest.sdlxliff',
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
                CleanUpTargetOnSourceWithContentAndTagWhitespaceOnlyTargetTest::assertSame($this->expectedTarget, $fields['target']['original']);

                return false;
            }
        };

        $parser->addSegmentProcessor($sp);

        $parser->parseFile();
    }

    public function expectedSegmentsProvider(): iterable
    {
        yield 'config active' => [
            'configValue' => '1',
            'target' => '',
        ];

        yield 'config inactive' => [
            'configValue' => '0',
            'target' => '<div class="open 672069643d22323722207869643d2232623734336562342d343635382d343539322d613433652d64356262386432643739383722 internal-tag ownttip"><span title="&lt;hyperlink value=&quot;https://www.translate5.net/author/marc&quot;&gt;" class="short">&lt;1&gt;</span><span data-originalid="27" data-length="-1" class="full">&lt;hyperlink value=&quot;https://www.translate5.net/author/marc&quot;&gt;</span></div><div class="open 672069643d22323722207869643d2232623734336562342d343635382d343539322d613433652d643562623864326437393837222073646c3a656e643d2266616c7365222f internal-tag ownttip"><span title="&lt;hyperlink value=&quot;https://www.translate5.net/author/marc&quot;&gt;" class="short">&lt;1&gt;</span><span data-originalid="27" data-length="-1" class="full">&lt;hyperlink value=&quot;https://www.translate5.net/author/marc&quot;&gt;</span></div><div class="close 2f67 internal-tag ownttip"><span title="&lt;/hyperlink&gt;" class="short">&lt;/1&gt;</span><span data-originalid="27" data-length="-1" class="full">&lt;/hyperlink&gt;</span></div>',
        ];
    }
}
