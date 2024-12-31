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

class ImportSdlxliffWithXHtmlCTypeTagsTest extends UnitTestAbstract
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
            __DIR__ . '/testfiles/ImportSdlxliffWithXHtmlCTypeTagsTest/' . $filename,
            'ImportSdlxliffWithXHtmlCTypeTagsTest.sdlxliff',
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

                ImportSdlxliffWithXHtmlCTypeTagsTest::assertSame(
                    $this->mrks[$this->callCounter],
                    $fields['source']['original']
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
                '<div class="open 672069643d2231313622 internal-tag ownttip"><span title="&lt;g id=&quot;e7OrITgKjYEYtZvL&quot; ctype=&quot;x-html-P&quot;&gt;" class="short">&lt;1&gt;</span><span data-originalid="116" data-length="-1" class="full">&lt;g id=&quot;e7OrITgKjYEYtZvL&quot; ctype=&quot;x-html-P&quot;&gt;</span></div><div class="open 672069643d2231313722 internal-tag ownttip"><span title="&lt;g id=&quot;-sIb3ZFluzkP4jQr&quot; ctype=&quot;x-html-STRONG&quot;&gt;" class="short">&lt;2&gt;</span><span data-originalid="117" data-length="-1" class="full">&lt;g id=&quot;-sIb3ZFluzkP4jQr&quot; ctype=&quot;x-html-STRONG&quot;&gt;</span></div>Der Cobot ist so gestaltet, dass er sicher mit Menschen interagieren kann.<div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/2&gt;</span><span data-originalid="117" data-length="-1" class="full">&lt;/g&gt;</span></div><div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/1&gt;</span><span data-originalid="116" data-length="-1" class="full">&lt;/g&gt;</span></div><div class="open 672069643d2231313622 internal-tag ownttip"><span title="&lt;g id=&quot;e7OrITgKjYEYtZvL&quot; ctype=&quot;x-html-P&quot;&gt;" class="short">&lt;3&gt;</span><span data-originalid="116" data-length="-1" class="full">&lt;g id=&quot;e7OrITgKjYEYtZvL&quot; ctype=&quot;x-html-P&quot;&gt;</span></div><div class="open 672069643d2231313722 internal-tag ownttip"><span title="&lt;g id=&quot;-sIb3ZFluzkP4jQr&quot; ctype=&quot;x-html-STRONG&quot;&gt;" class="short">&lt;4&gt;</span><span data-originalid="117" data-length="-1" class="full">&lt;g id=&quot;-sIb3ZFluzkP4jQr&quot; ctype=&quot;x-html-STRONG&quot;&gt;</span></div><div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/4&gt;</span><span data-originalid="117" data-length="-1" class="full">&lt;/g&gt;</span></div><div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/3&gt;</span><span data-originalid="116" data-length="-1" class="full">&lt;/g&gt;</span></div>',
                '<div class="open 672069643d2231313622 internal-tag ownttip"><span title="&lt;g id=&quot;e7OrITgKjYEYtZvL&quot; ctype=&quot;x-html-P&quot;&gt;" class="short">&lt;1&gt;</span><span data-originalid="116" data-length="-1" class="full">&lt;g id=&quot;e7OrITgKjYEYtZvL&quot; ctype=&quot;x-html-P&quot;&gt;</span></div><div class="open 672069643d2231313722 internal-tag ownttip"><span title="&lt;g id=&quot;-sIb3ZFluzkP4jQr&quot; ctype=&quot;x-html-STRONG&quot;&gt;" class="short">&lt;2&gt;</span><span data-originalid="117" data-length="-1" class="full">&lt;g id=&quot;-sIb3ZFluzkP4jQr&quot; ctype=&quot;x-html-STRONG&quot;&gt;</span></div><div class="open 672069643d2231313822 internal-tag ownttip"><span title="&lt;g id=&quot;s-OrVst9Fj8b_qgp&quot; ctype=&quot;x-html-U&quot;&gt;" class="short">&lt;3&gt;</span><span data-originalid="118" data-length="-1" class="full">&lt;g id=&quot;s-OrVst9Fj8b_qgp&quot; ctype=&quot;x-html-U&quot;&gt;</span></div>Ein Cobot ist jedoch nicht automatisch sicher!<div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/3&gt;</span><span data-originalid="118" data-length="-1" class="full">&lt;/g&gt;</span></div><div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/2&gt;</span><span data-originalid="117" data-length="-1" class="full">&lt;/g&gt;</span></div><div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/1&gt;</span><span data-originalid="116" data-length="-1" class="full">&lt;/g&gt;</span></div><div class="open 672069643d2231313922 internal-tag ownttip"><span title="&lt;g id=&quot;SZZohtJBYXRBbpzl&quot; ctype=&quot;x-html-P&quot;&gt;" class="short">&lt;4&gt;</span><span data-originalid="119" data-length="-1" class="full">&lt;g id=&quot;SZZohtJBYXRBbpzl&quot; ctype=&quot;x-html-P&quot;&gt;</span></div><div class="open 672069643d2231323022 internal-tag ownttip"><span title="&lt;g id=&quot;6QhTgFhxi1LORZR-&quot; ctype=&quot;x-html-STRONG&quot;&gt;" class="short">&lt;5&gt;</span><span data-originalid="120" data-length="-1" class="full">&lt;g id=&quot;6QhTgFhxi1LORZR-&quot; ctype=&quot;x-html-STRONG&quot;&gt;</span></div>Je nach Anwendungsfall müssen unterschiedliche Sicherheitsparameter eingestellt werden, damit es ein möglichst geringes Risiko für den Menschen gibt.<div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/5&gt;</span><span data-originalid="120" data-length="-1" class="full">&lt;/g&gt;</span></div><div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/4&gt;</span><span data-originalid="119" data-length="-1" class="full">&lt;/g&gt;</span></div>',
                '<div class="open 672069643d2231313922 internal-tag ownttip"><span title="&lt;g id=&quot;SZZohtJBYXRBbpzl&quot; ctype=&quot;x-html-P&quot;&gt;" class="short">&lt;1&gt;</span><span data-originalid="119" data-length="-1" class="full">&lt;g id=&quot;SZZohtJBYXRBbpzl&quot; ctype=&quot;x-html-P&quot;&gt;</span></div><div class="open 672069643d2231323022 internal-tag ownttip"><span title="&lt;g id=&quot;6QhTgFhxi1LORZR-&quot; ctype=&quot;x-html-STRONG&quot;&gt;" class="short">&lt;2&gt;</span><span data-originalid="120" data-length="-1" class="full">&lt;g id=&quot;6QhTgFhxi1LORZR-&quot; ctype=&quot;x-html-STRONG&quot;&gt;</span></div> Manchmal lässt sich das Risiko aber nicht ausreichend reduzieren und es werden dennoch zusätzliche Sicherheitseinrichtungen benötigt (z.B. Schutzumhausung, Laser-Scanner, ...). <div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/2&gt;</span><span data-originalid="120" data-length="-1" class="full">&lt;/g&gt;</span></div><div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/1&gt;</span><span data-originalid="119" data-length="-1" class="full">&lt;/g&gt;</span></div>',
                '<div class="open 672069643d2231313922 internal-tag ownttip"><span title="&lt;g id=&quot;SZZohtJBYXRBbpzl&quot; ctype=&quot;x-html-P&quot;&gt;" class="short">&lt;1&gt;</span><span data-originalid="119" data-length="-1" class="full">&lt;g id=&quot;SZZohtJBYXRBbpzl&quot; ctype=&quot;x-html-P&quot;&gt;</span></div><div class="open 672069643d2231323022 internal-tag ownttip"><span title="&lt;g id=&quot;6QhTgFhxi1LORZR-&quot; ctype=&quot;x-html-STRONG&quot;&gt;" class="short">&lt;2&gt;</span><span data-originalid="120" data-length="-1" class="full">&lt;g id=&quot;6QhTgFhxi1LORZR-&quot; ctype=&quot;x-html-STRONG&quot;&gt;</span></div>Außerdem besteht bei der manuellen Bedienung eines Roboters immer ein Restrisiko.<div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/2&gt;</span><span data-originalid="120" data-length="-1" class="full">&lt;/g&gt;</span></div><div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/1&gt;</span><span data-originalid="119" data-length="-1" class="full">&lt;/g&gt;</span></div>',
            ],
        ];
    }
}
