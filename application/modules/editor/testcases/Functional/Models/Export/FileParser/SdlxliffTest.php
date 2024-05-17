<?php

namespace MittagQI\Translate5\Test\Functional\Models\Export\FileParser;

use editor_Models_File;
use editor_Models_Import_FileParser_Sdlxliff;
use editor_Models_Segment as Segment;
use editor_Models_Segment_AutoStates as AutoStates;
use editor_Models_Segment_MatchRateType as MatchRateType;
use editor_Models_Task;
use editor_Test_UnitTest;
use MittagQI\Translate5\Task\Import\SkeletonFile;
use ZfExtended_Factory;
use ZfExtended_Utils;

class SdlxliffTest extends editor_Test_UnitTest
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

        $this->task->initTaskDataDirectory();

        $this->file = ZfExtended_Factory::get(editor_Models_File::class);
        $this->file->setTaskGuid($this->task->getTaskGuid());
        $this->file->setFileName('1seg-tag-ws-only-target.sdlxliff');
        $this->file->setFileParser(editor_Models_Import_FileParser_Sdlxliff::class);
        $this->file->save();

        $sm = new \editor_Models_SegmentFieldManager();
        $sm->initFields($this->task->getTaskGuid());

        $segment = ZfExtended_Factory::get(Segment::class);

        $segment->init([
            'taskGuid' => $this->task->getTaskGuid(),
            'userGuid' => '{00000000-0000-0000-0000-000000000000}',
            'segmentNrInTask' => 1,
            'fileId' => $this->file->getId(),
            'pretrans' => Segment::PRETRANS_INITIAL,
            'autoStateId' => AutoStates::PRETRANSLATED,
            'matchRate' => 70,
            'matchRateType' => implode(';', [
                MatchRateType::PREFIX_PRETRANSLATED,
                MatchRateType::TYPE_MT,
                'DeepL - EN-DE',
            ]),
            'mid' => 19,
        ]);

        $segment->setFieldContents($sm, [
            'source' => [
                'original' => '<div class="single 736f667452657475726e2f newline internal-tag ownttip"><span class="short" title="&lt;1/&gt;: Newline">&lt;1/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div> <div class="single 73706163652074733d2232303230323032303230323032303230323032303230323032303230323032303230323032303230323032303230323032303230323022206c656e6774683d223237222f space internal-tag ownttip"><span class="short" title="&lt;2/&gt;: 27 whitespace characters">&lt;2/&gt;</span><span class="full" data-originalid="space" data-length="27">···························</span></div><div class="open 672069643d22343322 internal-tag ownttip"><span class="short" title="↵">&lt;3&gt;</span><span class="full" data-originalid="43" data-length="-1">↵                    </span></div>Produktneuheiten<div class="close 2f67 internal-tag ownttip"><span class="short" title="">&lt;/3&gt;</span><span class="full" data-originalid="43" data-length="-1"></span></div><div class="single 636861722074733d2265323830383222206c656e6774683d2231222f char internal-tag ownttip"><span class="short" title="&lt;4/&gt;: En Space">&lt;4/&gt;</span><span class="full" data-originalid="char" data-length="1">□</span></div><div class="single 736f667452657475726e2f newline internal-tag ownttip"><span class="short" title="&lt;5/&gt;: Newline">&lt;5/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div> <div class="single 73706163652074733d2232303230323032303230323032303230323032303230323032303230323032303230323032303230323032303230323032303230323022206c656e6774683d223237222f space internal-tag ownttip"><span class="short" title="&lt;6/&gt;: 27 whitespace characters">&lt;6/&gt;</span><span class="full" data-originalid="space" data-length="27">···························</span></div><div class="open 672069643d22343422 internal-tag ownttip"><span class="short" title="↵">&lt;7&gt;</span><span class="full" data-originalid="44" data-length="-1">↵                    </span></div>Entdecken Sie die neuesten, topaktuellen Designentwicklungen von AXOR <div class="close 2f67 internal-tag ownttip"><span class="short" title="">&lt;/7&gt;</span><span class="full" data-originalid="44" data-length="-1"></span></div><div class="single 736f667452657475726e2f newline internal-tag ownttip"><span class="short" title="&lt;8/&gt;: Newline">&lt;8/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div> <div class="single 73706163652074733d2232303230323032303230323032303230323032303230323032303230323032303230323032303230323032303230323032303230323022206c656e6774683d223237222f space internal-tag ownttip"><span class="short" title="&lt;9/&gt;: 27 whitespace characters">&lt;9/&gt;</span><span class="full" data-originalid="space" data-length="27">···························</span></div><div class="single 736f667452657475726e2f newline internal-tag ownttip"><span class="short" title="&lt;10/&gt;: Newline">&lt;10/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div> <div class="single 73706163652074733d2232303230323032303230323032303230323032303230323032303230323032303230323032303230323032303230323032303230323022206c656e6774683d223237222f space internal-tag ownttip"><span class="short" title="&lt;11/&gt;: 27 whitespace characters">&lt;11/&gt;</span><span class="full" data-originalid="space" data-length="27">···························</span></div><div class="open 672069643d22343522 internal-tag ownttip"><span class="short" title="↵">&lt;12&gt;</span><span class="full" data-originalid="45" data-length="-1">↵                    </span></div>Erlebnisse<div class="close 2f67 internal-tag ownttip"><span class="short" title="">&lt;/12&gt;</span><span class="full" data-originalid="45" data-length="-1"></span></div><div class="single 736f667452657475726e2f newline internal-tag ownttip"><span class="short" title="&lt;13/&gt;: Newline">&lt;13/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div> <div class="single 73706163652074733d2232303230323032303230323032303230323032303230323032303230323032303230323032303230323032303230323032303230323022206c656e6774683d223237222f space internal-tag ownttip"><span class="short" title="&lt;14/&gt;: 27 whitespace characters">&lt;14/&gt;</span><span class="full" data-originalid="space" data-length="27">···························</span></div><div class="open 672069643d22343622 internal-tag ownttip"><span class="short" title="↵">&lt;15&gt;</span><span class="full" data-originalid="46" data-length="-1">↵                    </span></div>Inspirierende Einblicke in Lebensräume und Hotels mit AXOR weltweit<div class="close 2f67 internal-tag ownttip"><span class="short" title="">&lt;/15&gt;</span><span class="full" data-originalid="46" data-length="-1"></span></div><div class="single 736f667452657475726e2f newline internal-tag ownttip"><span class="short" title="&lt;16/&gt;: Newline">&lt;16/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div> <div class="single 73706163652074733d223230323032303230323032303230323032303230323032303230323032303230323032303230323032303230323022206c656e6774683d223233222f space internal-tag ownttip"><span class="short" title="&lt;17/&gt;: 23 whitespace characters">&lt;17/&gt;</span><span class="full" data-originalid="space" data-length="23">·······················</span></div>',
            ],
            'target' => [
                'original' => '',
                'edited' => '<div class="single 736f667452657475726e2f newline internal-tag ownttip"><span class="short" title="&lt;1/&gt;: Newline">&lt;1/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div> <div class="single 73706163652074733d2232303230323032303230323032303230323032303230323032303230323032303230323032303230323032303230323032303230323022206c656e6774683d223237222f space internal-tag ownttip"><span class="short" title="&lt;2/&gt;: 27 whitespace characters">&lt;2/&gt;</span><span class="full" data-originalid="space" data-length="27">···························</span></div><div class="open 672069643d22343322 internal-tag ownttip"><span class="short" title="↵">&lt;3&gt;</span><span class="full" data-originalid="43" data-length="-1">↵                   ~@#!WS~</span></div>Produktneuheiten<div class="close 2f67 internal-tag ownttip"><span class="short" title="">&lt;/3&gt;</span><span class="full" data-originalid="43" data-length="-1"></span></div><div class="single 636861722074733d2265323830383222206c656e6774683d2231222f char internal-tag ownttip"><span class="short" title="&lt;4/&gt;: En Space">&lt;4/&gt;</span><span class="full" data-originalid="char" data-length="1">□</span></div><div class="single 736f667452657475726e2f newline internal-tag ownttip"><span class="short" title="&lt;5/&gt;: Newline">&lt;5/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div> <div class="single 73706163652074733d2232303230323032303230323032303230323032303230323032303230323032303230323032303230323032303230323032303230323022206c656e6774683d223237222f space internal-tag ownttip"><span class="short" title="&lt;6/&gt;: 27 whitespace characters">&lt;6/&gt;</span><span class="full" data-originalid="space" data-length="27">···························</span></div><div class="open 672069643d22343422 internal-tag ownttip"><span class="short" title="↵">&lt;7&gt;</span><span class="full" data-originalid="44" data-length="-1">↵                   ~@#!WS~</span></div>Entdecken Sie die neuesten, topaktuellen Designentwicklungen von AXOR <div class="close 2f67 internal-tag ownttip"><span class="short" title="">&lt;/7&gt;</span><span class="full" data-originalid="44" data-length="-1"></span></div><div class="single 736f667452657475726e2f newline internal-tag ownttip"><span class="short" title="&lt;10/&gt;: Newline">&lt;10/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div> <div class="single 73706163652074733d2232303230323032303230323032303230323032303230323032303230323032303230323032303230323032303230323032303230323022206c656e6774683d223237222f space internal-tag ownttip"><span class="short" title="&lt;11/&gt;: 27 whitespace characters">&lt;11/&gt;</span><span class="full" data-originalid="space" data-length="27">···························</span></div><div class="open 672069643d22343522 internal-tag ownttip"><span class="short" title="↵">&lt;12&gt;</span><span class="full" data-originalid="45" data-length="-1">↵                   ~@#!WS~</span></div>Erlebnisse<div class="close 2f67 internal-tag ownttip"><span class="short" title="">&lt;/12&gt;</span><span class="full" data-originalid="45" data-length="-1"></span></div><div class="single 736f667452657475726e2f newline internal-tag ownttip"><span class="short" title="&lt;13/&gt;: Newline">&lt;13/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div> <div class="single 73706163652074733d2232303230323032303230323032303230323032303230323032303230323032303230323032303230323032303230323032303230323022206c656e6774683d223237222f space internal-tag ownttip"><span class="short" title="&lt;14/&gt;: 27 whitespace characters">&lt;14/&gt;</span><span class="full" data-originalid="space" data-length="27">···························</span></div><div class="open 672069643d22343622 internal-tag ownttip"><span class="short" title="↵">&lt;15&gt;</span><span class="full" data-originalid="46" data-length="-1">↵                   ~@#!WS~</span></div>Inspirierende Einblicke in Lebensräume und Hotels mit AXOR weltweit<div class="close 2f67 internal-tag ownttip"><span class="short" title="">&lt;/15&gt;</span><span class="full" data-originalid="46" data-length="-1"></span></div><div class="single 736f667452657475726e2f newline internal-tag ownttip"><span class="short" title="&lt;16/&gt;: Newline">&lt;16/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div> <div class="single 73706163652074733d223230323032303230323032303230323032303230323032303230323032303230323032303230323032303230323022206c656e6774683d223233222f space internal-tag ownttip"><span class="short" title="&lt;17/&gt;: 23 whitespace characters">&lt;17/&gt;</span><span class="full" data-originalid="space" data-length="23">·······················</span></div><ins class="trackchanges ownttip" data-usertrackingid="772" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2024-05-13T16:03:25+03:00"> </ins><ins class="trackchanges ownttip" data-usertrackingid="772" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2024-05-13T16:03:25+03:00">jnjbj<div class="single 736f667452657475726e2f newline internal-tag ownttip"><span class="short" title="&lt;18/&gt;: Newline">&lt;18/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div></ins>',
            ],
        ]);

        $segment->save();

        $fileData = file_get_contents(__DIR__ . '/SdlxliffTest/testfiles/1seg-tag-ws-only-target.sdlxliff');

        $skeletonFile = new SkeletonFile($this->task);
        $skeletonFile->saveToDisk($this->file, str_replace('SEGMENT_ID', $segment->getId(), $fileData));
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->file->delete();
        ZfExtended_Utils::recursiveDelete($this->task->getAbsoluteTaskDataPath());
        @unlink(__DIR__ . '/SdlxliffTest/testfiles/result-1seg-tag-ws-only-target.sdlxliff');
    }

    public function testMarkPreTranslatedSegmentAsDraftOnExport(): void
    {
        $parser = new \editor_Models_Export_FileParser_Sdlxliff(
            $this->task,
            (int) $this->file->getId(),
            __DIR__ . '/SdlxliffTest/testfiles/result-1seg-tag-ws-only-target.sdlxliff',
        );
        $parser->saveFile();

        self::assertFileEquals(
            __DIR__ . '/SdlxliffTest/testfiles/expected-export-1seg-tag-ws-only-target.sdlxliff',
            __DIR__ . '/SdlxliffTest/testfiles/result-1seg-tag-ws-only-target.sdlxliff'
        );
    }
}
