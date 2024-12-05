<?php

namespace MittagQI\Translate5\Test\Integration\Models\Export\FileParser\Sdlxliff;

use editor_Models_File;
use editor_Models_Import_FileParser_Sdlxliff;
use editor_Models_Segment as Segment;
use editor_Models_Segment_AutoStates as AutoStates;
use editor_Models_Segment_MatchRateType as MatchRateType;
use editor_Models_Task;
use MittagQI\Translate5\Task\Import\SkeletonFile;
use MittagQI\Translate5\Test\UnitTestAbstract;
use ZfExtended_Factory;
use ZfExtended_Utils;

class SdlxliffMarkPreTranslatedSegmentAsDraftOnExportTest extends UnitTestAbstract
{
    private editor_Models_Task $task;

    private editor_Models_File $file;

    private string $taskDataPath;

    public function setUp(): void
    {
        parent::setUp();

        $this->taskDataPath = __DIR__ . '/testfiles/SdlxliffMarkPreTranslatedSegmentAsDraftOnExportTest/';

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

        $segment1 = ZfExtended_Factory::get(Segment::class);

        $segment1->init([
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

        $segment1->setFieldContents($sm, [
            'source' => [
                'original' => 'Source text 1.',
            ],
            'target' => [
                'original' => '',
                'edited' => 'Target text 1.',
            ],
        ]);

        $segment1->save();

        $segment2 = ZfExtended_Factory::get(Segment::class);

        $segment2->init([
            'taskGuid' => $this->task->getTaskGuid(),
            'userGuid' => '{00000000-0000-0000-0000-000000000000}',
            'segmentNrInTask' => 2,
            'fileId' => $this->file->getId(),
            'pretrans' => Segment::PRETRANS_INITIAL,
            'autoStateId' => AutoStates::PRETRANSLATED,
            'matchRate' => 75,
            'matchRateType' => implode(';', [
                MatchRateType::PREFIX_PRETRANSLATED,
                MatchRateType::TYPE_MT,
                'DeepL - EN-DE',
            ]),
            'mid' => 20,
        ]);

        $segment2->setFieldContents($sm, [
            'source' => [
                'original' => 'Source text 2.',
            ],
            'target' => [
                'original' => '',
                'edited' => 'Target text 2.',
            ],
        ]);

        $segment2->save();

        $segment3 = ZfExtended_Factory::get(Segment::class);

        $segment3->init([
            'taskGuid' => $this->task->getTaskGuid(),
            'userGuid' => '{00000000-0000-0000-0000-000000000000}',
            'segmentNrInTask' => 3,
            'fileId' => $this->file->getId(),
            'pretrans' => Segment::PRETRANS_INITIAL,
            'autoStateId' => AutoStates::PRETRANSLATED,
            'matchRate' => 80,
            'matchRateType' => implode(';', [
                MatchRateType::PREFIX_PRETRANSLATED,
                MatchRateType::TYPE_MT,
                'DeepL - EN-DE',
            ]),
            'mid' => 21,
        ]);

        $segment3->setFieldContents($sm, [
            'source' => [
                'original' => 'Source text 3.',
            ],
            'target' => [
                'original' => '',
                'edited' => 'Target text 3.',
            ],
        ]);

        $segment3->save();

        $fileData = file_get_contents($this->taskDataPath . '1seg-tag-ws-only-target.sdlxliff');

        $skeletonFile = new SkeletonFile($this->task);

        $skeletonFile->saveToDisk(
            $this->file,
            str_replace(
                ['SEGMENT_ID_1', 'SEGMENT_ID_2', 'SEGMENT_ID_3'],
                [$segment1->getId(), $segment2->getId(), $segment3->getId()],
                $fileData
            )
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->file->delete();
        ZfExtended_Utils::recursiveDelete($this->task->getAbsoluteTaskDataPath());
        @unlink($this->taskDataPath . 'result-1seg-tag-ws-only-target.sdlxliff');
    }

    public function testMarkPreTranslatedSegmentAsDraftOnExport(): void
    {
        $parser = new \editor_Models_Export_FileParser_Sdlxliff(
            $this->task,
            (int) $this->file->getId(),
            $this->taskDataPath . 'result-1seg-tag-ws-only-target.sdlxliff',
        );
        $parser->saveFile();

        self::assertFileEquals(
            $this->taskDataPath . 'expected-export-1seg-tag-ws-only-target.sdlxliff',
            $this->taskDataPath . 'result-1seg-tag-ws-only-target.sdlxliff'
        );
    }
}
