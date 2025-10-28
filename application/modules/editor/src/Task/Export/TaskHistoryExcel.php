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
declare(strict_types=1);

namespace MittagQI\Translate5\Task\Export;

use editor_Models_LanguageResources_LanguageResource;
use editor_Models_Segment;
use editor_Models_Segment_AutoStates;
use editor_Models_Segment_InternalTag;
use editor_Models_Segment_Iterator;
use editor_Models_SegmentField;
use editor_Models_Task;
use editor_Workflow_Default;
use MittagQI\Translate5\Repository\SegmentHistoryDataRepository;
use MittagQI\Translate5\Repository\SegmentHistoryRepository;
use MittagQI\ZfExtended\Controller\Response\Header;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ReflectionException;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_NoAccessException;

/**
 * Export the whole task as an Excel-file
 */
class TaskHistoryExcel
{
    protected Spreadsheet $excel;

    protected editor_Models_Task $task;

    /**
     * @var editor_Models_LanguageResources_LanguageResource[]
     */
    protected array $languageResourceCache = [];

    private editor_Workflow_Default $workflow;

    /**
     * @var string[]
     */
    private array $toBeUsedSteps;

    /**
     * @throws Exception
     */
    public function __construct(editor_Models_Task $task)
    {
        $this->task = $task;
        $this->workflow = $this->task->getTaskActiveWorkflow();
        $this->toBeUsedSteps = $this->workflow->getStepChain();
        //we remove the workflowEnded since this is not needed for history export
        array_pop($this->toBeUsedSteps);
        $this->initExcel();
    }

    /**
     * @throws Exception
     */
    private function initExcel(): void
    {
        $this->excel = new Spreadsheet();

        $stringBinder = new StringValueBinder();
        $stringBinder->setFormulaConversion(true);
        $this->excel->setValueBinder($stringBinder);

        //default format
        $this->excel->getDefaultStyle()->getAlignment()
            // vertical align: top;
            ->setVertical(Alignment::VERTICAL_TOP)
            // text-align: left;
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            // auto-wrap text to new line;
            ->setWrapText(true);

        $sheet = $this->excel->getActiveSheet();
        $sheet->setTitle('task history');

        // set font-size to "14" for the whole sheet
        $sheet->getParent()->getDefaultStyle()->applyFromArray([
            'font' => [
                'size' => '14',
            ],
        ]);

        // set column width
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(50);

        // write fieldnames in header
        $sheet->setCellValue('A1', 'Nr');
        $sheet->setCellValue('B1', 'LanguageResource Type ID');
        $sheet->setCellValue('C1', 'LanguageResource Name');
        $sheet->setCellValue('D1', 'Source');

        $col = 'E';
        $idx = 1;
        foreach ($this->toBeUsedSteps as $step) {
            if ($step == $this->workflow::STEP_NO_WORKFLOW) {
                $step = 'Pretranslation';
            } else {
                $step = ($idx++) . '. ' . $step;
            }
            $sheet->getColumnDimension($col)->setWidth(50);
            $sheet->setCellValue($col . '1', $step);
            $col++;
        }
        $sheet->getStyle('A1:' . $col . '1')->getFont()->setBold(true);
    }

    /**
     * does the export
     * @param string $fileName where the XLS should go to
     * @throws ReflectionException
     */
    protected function export(string $fileName): void
    {
        // task view must exist
        $this->task->createMaterializedView();

        // load segment tagger to extract pure text from segments
        $internalTag = ZfExtended_Factory::get(editor_Models_Segment_InternalTag::class);

        // create a segment-iterator to get all segments of this task as a list of editor_Models_Segment objects
        $segments = ZfExtended_Factory::get(editor_Models_Segment_Iterator::class, [$this->task->getTaskGuid()]);

        $sheet = $this->excel->getActiveSheet();

        $setString = function (string $col, int $row, string $content) use ($sheet) {
            $sheet->setCellValueExplicit($col . $row, $content, DataType::TYPE_STRING);
        };

        // write the segments into the excel
        $row = 2; //row 1 = headlines
        foreach ($segments as $segment) {
            $col = 'A';
            // First column: Segment number → as implemented
            $sheet->setCellValue(($col++) . $row, $segment->getSegmentNrInTask());

            // Second column: Language Resource resource_id
            //   used for pre-translation (editor_Services_Deepl_1, editor_Services_t5memory_1, etc.)
            // Third column: Language Resource name (name given by the PM, when creating the language resource)
            $uuid = $segment->meta()->getPreTransLangResUuid();
            if (is_null($uuid)) {
                $setString($col++, $row, 'Not pre-translated');
                $setString($col++, $row, 'Not pre-translated');
            } else {
                $langRes = $this->getLanguageResource($uuid);
                $setString($col++, $row, $langRes->getResourceId());
                $setString($col++, $row, $langRes->getName());
            }

            // Fifth column: Source
            $setString($col++, $row, $internalTag->toExcel($segment->getSource()));

            // Sixth column: pre-translated target
            // Seventh column: target at end of first workflow step
            // Eighth column: target at end of second workflow step and so
            // this is calculated by getTargetsByStep
            $targets = $this->getTargetsByStep($segment);
            foreach ($targets as $target) {
                $setString($col++, $row, $internalTag->toExcel($target['target'] ?? ''));
            }

            $row++;
        }
        // .. then send the excel
        $writer = new Xlsx($this->excel);
        $writer->save($fileName);
    }

    protected function getTargetsByStep(editor_Models_Segment $segment): array
    {
        $history = SegmentHistoryRepository::create();
        $historyEntries = array_reverse($history->loadBySegmentId((int) $segment->getId()));
        $historyData = new SegmentHistoryDataRepository();
        $historyDataEntries = $historyData->loadBySegmentId(
            (int) $segment->getId(),
            editor_Models_SegmentField::TYPE_TARGET
        );

        $ids = array_column($historyDataEntries, 'segmentHistoryId');
        $historyDataEntries = array_combine($ids, $historyDataEntries);

        //unify history entries and current active segment content into $segmentData array
        $segmentData = [];
        foreach ($historyEntries as $entry) {
            if (isset($historyDataEntries[$entry['id']])) {
                $target = $historyDataEntries[$entry['id']];
                $target = strlen($target['edited']) > 0 ? $target['edited'] : $target['original'];
            } else {
                $target = '';
            }

            $segmentData[] = [
                'workflowStep' => $entry['workflowStep'],
                'pretrans' => $entry['pretrans'],
                'autoStateId' => $entry['autoStateId'],
                'workflowStepNr' => $entry['workflowStepNr'],
                'target' => $target,
                'type' => 'history',
            ];
        }
        $segmentData[] = [
            'workflowStep' => $segment->getWorkflowStep(),
            'pretrans' => $segment->getPretrans(),
            'autoStateId' => $segment->getAutoStateId(),
            'workflowStepNr' => $segment->getWorkflowStepNr(),
            'type' => 'currentContent',
            'target' => $segment->getTargetEdit(),
        ];

        //sort to latest usable target per workflow step
        $result = array_fill_keys($this->toBeUsedSteps, []);

        //the initial last used step is the second defined in the chain (normally translation),
        // since the first is always "no workflow" used for pretranslations here (which is the fallback).
        $lastUsedStep = $this->toBeUsedSteps[1] ?? $this->toBeUsedSteps[0];

        foreach ($segmentData as $entry) {
            //untranslated entries are ignored (so in excel they occur as empty string)
            if ($entry['autoStateId'] == editor_Models_Segment_AutoStates::NOT_TRANSLATED) {
                continue;
            }
            //all pretranslated stuff is collected in "no workflow" since this is the first step in chain by definition
            if ((int) $entry['pretrans'] >= $segment::PRETRANS_INITIAL && empty($entry['workflowStep'])) {
                $result[$this->workflow::STEP_NO_WORKFLOW] = $entry;

                continue;
            }

            //if the entry does not belong to the workflow chain (like PM Check),
            // we assume it to belong to the last used one of the previous valid step:
            if (! array_key_exists($entry['workflowStep'], $result)) {
                $result[$lastUsedStep] = $entry;

                continue;
            }

            // we just overwrite previous found entries for that step (initial sorting is correct for that)
            $result[$entry['workflowStep']] = $entry;
            $lastUsedStep = $entry['workflowStep'];
        }

        return $result;
    }

    /**
     * returns the language resource with the given uuid, returns a "not found" lang res if nothing found with that uuid
     */
    protected function getLanguageResource(string $uuid): editor_Models_LanguageResources_LanguageResource
    {
        if (! empty($this->languageResourceCache[$uuid])) {
            return $this->languageResourceCache[$uuid];
        }
        $langRes = new editor_Models_LanguageResources_LanguageResource();

        try {
            $langRes->loadByUuid($uuid);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            $langRes->setName('not found');
            $langRes->setResourceId('not found');
        }

        return $this->languageResourceCache[$uuid] = $langRes;
    }

    /**
     * export xls from stored task, returns true if file was created
     * @throws ReflectionException
     */
    public function exportAsFile(string $fileName): bool
    {
        $this->export($fileName);

        return true;
    }

    /**
     * provides the excel as download to the browser
     * @throws ReflectionException
     * @throws ZfExtended_NoAccessException
     */
    public function exportAsDownload(): void
    {
        // output: first send headers
        if (! $this->exportAsFile('php://output')) {
            throw new ZfExtended_NoAccessException('Task is in use by another user!');
        }
        Header::sendDownload(
            $this->task->getTasknameForDownload('.xlsx'),
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'max-age=0'
        );
        exit;
    }
}
