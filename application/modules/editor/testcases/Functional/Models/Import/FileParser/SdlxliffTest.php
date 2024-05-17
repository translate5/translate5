<?php

namespace MittagQI\Translate5\Test\Functional\Models\Import\FileParser;

use editor_Models_File;
use editor_Models_Import_FileParser;
use editor_Models_Import_FileParser_Sdlxliff as Sdlxliff;
use editor_Models_Task;
use editor_Models_TaskUserTracking;
use editor_Test_UnitTest;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Utils;

class SdlxliffTest extends editor_Test_UnitTest
{
    private editor_Models_Task $task;

    private editor_Models_File $file;

    public function setUp(): void
    {
        parent::setUp();

        Zend_Registry::get('PluginManager')->setActive('TrackChanges', true);

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

        Zend_Registry::get('PluginManager')->setActive('TrackChanges', false);

        $this->file->delete();
        $this->task->delete();
    }

    /**
     * @dataProvider expectedSegmentsProvider
     */
    public function testImportSegmentWithTrackChanges(string $expectedTarget): void
    {
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
            __DIR__ . '/testfiles/formatierte-Datei.odt.sdlxliff',
            'formatierte-Datei.odt.sdlxliff',
            (int) $this->file->getId(),
            $this->task,
        );
        $parser->setSegmentFieldManager($segmentFieldManager);

        $sp = new class($this->task, $expectedTarget) extends \editor_Models_Import_SegmentProcessor {
            public function __construct(
                editor_Models_Task $task,
                private string $expectedTarget
            ) {
                parent::__construct($task);
            }

            public function process(editor_Models_Import_FileParser $parser)
            {
                $fields = $parser->getFieldContents();
                SdlxliffTest::assertSame($this->expectedTarget, $fields['target']['original']);

                return false;
            }
        };

        $parser->addSegmentProcessor($sp);

        $parser->parseFile();
    }

    public function expectedSegmentsProvider(): iterable
    {
        yield 'opener deleted. single tag.' => [
            'amer<del class="trackchanges ownttip deleted" data-usertrackingid="translate5::id" data-usercssnr="usernr1" data-workflowstep="default4" data-timestamp="2024-04-24T18:18:50+02:00" ><div class="open 672069643d22323722207869643d2232623734336562342d343635382d343539322d613433652d643562623864326437393837222073646c3a656e643d2266616c7365222f internal-tag ownttip"><span title="&lt;hyperlink value=&quot;https://www.translate5.net/author/marc&quot;&gt;" class="short">&lt;1&gt;</span><span data-originalid="27" data-length="-1" class="full">&lt;hyperlink value=&quot;https://www.translate5.net/author/marc&quot;&gt;</span></div></del>vides<ins class="trackchanges ownttip" data-usertrackingid="sylvi::id" data-usercssnr="usernr2" data-workflowstep="default4" data-timestamp="2024-04-24T18:18:59+02:00" ><div class="open 672069643d22323722207869643d2232623734336562342d343635382d343539322d613433652d643562623864326437393837222073646c3a656e643d2266616c7365222f internal-tag ownttip"><span title="&lt;hyperlink value=&quot;https://www.translate5.net/author/marc&quot;&gt;" class="short">&lt;1&gt;</span><span data-originalid="27" data-length="-1" class="full">&lt;hyperlink value=&quot;https://www.translate5.net/author/marc&quot;&gt;</span></div></ins>poussage<div class="close 2f67 internal-tag ownttip"><span title="&lt;/hyperlink&gt;" class="short">&lt;/1&gt;</span><span data-originalid="27" data-length="-1" class="full">&lt;/hyperlink&gt;</span></div>à COLD 19, 2021 è <div class="open 672069643d22323822207869643d2239333636643432632d303631352d346535362d383133632d64333363666266343561656422 internal-tag ownttip"><span title="&lt;hyperlink value=&quot;https://www.translate5.net/category/presse&quot;&gt;" class="short">&lt;2&gt;</span><span data-originalid="28" data-length="-1" class="full">&lt;hyperlink value=&quot;https://www.translate5.net/category/presse&quot;&gt;</span></div>soupapes<div class="close 2f67 internal-tag ownttip"><span title="&lt;/hyperlink&gt;" class="short">&lt;/2&gt;</span><span data-originalid="28" data-length="-1" class="full">&lt;/hyperlink&gt;</span></div> à<ins class="trackchanges ownttip" data-usertrackingid="marc::id" data-usercssnr="usernr3" data-workflowstep="default4" data-timestamp="2024-04-24T18:19:09+02:00" ><div class="open 672069643d22323722207869643d2232623734336562342d343635382d343539322d613433652d643562623864326437393837222073646c3a656e643d2266616c7365222f internal-tag ownttip"><span title="&lt;hyperlink value=&quot;https://www.translate5.net/author/marc&quot;&gt;" class="short">&lt;1&gt;</span><span data-originalid="27" data-length="-1" class="full">&lt;hyperlink value=&quot;https://www.translate5.net/author/marc&quot;&gt;</span></div></ins><div class="open 672069643d22323922207869643d2236386364356438372d643461632d343933332d623164332d37393534653163643861393522 internal-tag ownttip"><span title="&lt;hyperlink value=&quot;https://www.translate5.net/2021/03/19/das-translate5-konsortium-ein-vollumfaengliches-open-source-cloud-uebersetzungssystem#respond&quot;&gt;" class="short">&lt;3&gt;</span><span data-originalid="29" data-length="-1" class="full">&lt;hyperlink value=&quot;https://www.translate5.net/2021/03/19/das-translate5-konsortium-ein-vollumfaengliches-open-source-cloud-uebersetzungssystem#respond&quot;&gt;</span></div>0 collaboration<div class="close 2f67 internal-tag ownttip"><span title="&lt;/hyperlink&gt;" class="short">&lt;/3&gt;</span><span data-originalid="29" data-length="-1" class="full">&lt;/hyperlink&gt;</span></div>',
        ];
    }
}
