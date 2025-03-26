<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of ZfExtended library

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
https://www.gnu.org/licenses/lgpl-3.0.txt

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
             https://www.gnu.org/licenses/lgpl-3.0.txt

END LICENSE AND COPYRIGHT
*/

declare(strict_types=1);

namespace MittagQI\Translate5\Task\Overview;

use editor_Models_Segment_AutoStates;
use editor_Models_Segment_Utility;
use editor_Models_SegmentField;
use editor_Models_Task as Task;
use MittagQI\Translate5\Repository\SegmentRepository;
use MittagQI\Translate5\Segment\Dto\SegmentView;
use MittagQI\Translate5\Segment\SegmentFieldManagerFactory;
use MittagQI\Translate5\Task\Overview\SegmentFormatter\SegmentFormatterInterface;
use ZfExtended_Zendoverwrites_Translate;

class SegmentDataProvider
{
    /**
     * @param iterable<(callable(Task $task, string $segment, bool $isSource): string)|SegmentFormatterInterface> $segmentFormatters
     */
    public function __construct(
        private readonly SegmentFieldManagerFactory $segmentFieldManagerFactory,
        private readonly ZfExtended_Zendoverwrites_Translate $translate,
        private readonly SegmentRepository $segmentRepository,
        private readonly editor_Models_Segment_AutoStates $segmentAutoStates,
        private readonly editor_Models_Segment_Utility $segmentUtility,
        private readonly iterable $segmentFormatters,
    ) {
    }

    /**
     * @param iterable<SegmentView>|null $segments
     */
    public function getSegmentDataTable(Task $task, ?iterable $segments = null): SegmentDataTable
    {
        $showImportTargetColumn = $task->getConfig()->runtimeOptions->editor->notification?->showImportTargetColumn;
        $sfm = $this->segmentFieldManagerFactory->getManager($task->getTaskGuid());

        $fieldsToShow = [];

        foreach ($sfm->getFieldList() as $field) {
            if ($field->type == editor_Models_SegmentField::TYPE_RELAIS) {
                continue;
            }
            //show the original source
            if ($field->type === editor_Models_SegmentField::TYPE_SOURCE) {
                $fieldsToShow[$field->name] = $this->translate->_($field->label);
            }

            // show the target on import if configured
            if ($field->type === editor_Models_SegmentField::TYPE_TARGET && $showImportTargetColumn) {
                $fieldsToShow[$field->name] = $this->translate->_($field->label);
            }

            //if field is editable (source or target), show the edited data
            if ($field->editable) {
                $fieldsToShow[$sfm->getEditIndex($field->name)] = sprintf(
                    $this->translate->_('%s - bearbeitet'),
                    $this->translate->_($field->label)
                );
            }
        }

        $header = new SegmentDataHeader();

        $header->add(SegmentDataHeader::FIELD_NR, $this->translate->_('Nr.'));

        foreach ($fieldsToShow as $id => $label) {
            $header->add($id, $label);
        }

        $header->add(SegmentDataHeader::FIELD_STATUS, $this->translate->_('Status'));
        $header->add(SegmentDataHeader::FIELD_MANUAL_QS, $this->translate->_('Manuelle QS (ganzes Segment)'));
        $header->add(SegmentDataHeader::FIELD_EDIT_STATUS, $this->translate->_('Bearbeitungsstatus'));
        $header->add(SegmentDataHeader::FIELD_MATCH_RATE, $this->translate->_('Matchrate'));
        $header->add(SegmentDataHeader::FIELD_COMMENTS, $this->translate->_('Kommentare'));

        if ($segments === null) {
            $segments = $this->segmentRepository->getSegmentsViewData($task);
        }

        $stateMap = $this->segmentAutoStates->getLabelMap($this->translate);
        $segmentDataTable = new SegmentDataTable($header);

        /** @var SegmentView $segment */
        foreach ($segments as $segment) {
            $row = new SegmentDataRow();
            $state = $stateMap[$segment->getValue('autoStateId')] ?? '- not found -';

            foreach ($header->getFields() as $field) {
                $row[$field] = match ($field->id) {
                    SegmentDataHeader::FIELD_NR => $segment->getValue('segmentNrInTask'),
                    SegmentDataHeader::FIELD_STATUS => $this->translate->_(
                        $this->segmentUtility->convertStateId($segment->getValue('stateId'))
                    ),
                    SegmentDataHeader::FIELD_MANUAL_QS => $segment->getValue('qualities') ?? [],
                    SegmentDataHeader::FIELD_EDIT_STATUS => $state,
                    SegmentDataHeader::FIELD_MATCH_RATE => $segment->getValue('matchRate'),
                    SegmentDataHeader::FIELD_COMMENTS => $segment->getValue('comments'),

                    default => $this->formatSegment($task, $segment->getValue($field->id), str_contains($field->id, 'source')),
                };
            }

            $segmentDataTable->addRow($row);
        }

        return $segmentDataTable;
    }

    private function formatSegment(Task $task, ?string $segment, bool $isSource): string
    {
        if ($segment === null) {
            return '';
        }

        foreach ($this->segmentFormatters as $formatter) {
            $segment = $formatter($task, $segment, $isSource);
        }

        return $segment;
    }
}
