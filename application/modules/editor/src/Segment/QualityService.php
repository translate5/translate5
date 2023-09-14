<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a paid plug-in for translate5.

 The translate5 core software and its freely downloadable plug-ins are licensed under an AGPLv3 open-source license
 (https://www.gnu.org/licenses/agpl-3.0.en.html).
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 Paid translate5 plugins can deviate from standard AGPLv3 licensing and therefore constitute an
 exception. As such, translate5 plug-ins can be licensed under either AGPLv3 or GPLv3 (see below for details).

 Briefly summarized, a GPLv3 license dictates the same conditions as its AGPLv3 variant, except that it
 does not require the program (plug-in, in this case) to direct users toward its download location if it is
 only being used via the web in a browser.
 This enables developers to write custom plug-ins for translate5 and keep them private, granted they
 meet the GPLv3 licensing conditions stated above.
 As the source code of this paid plug-in is under open source GPLv3 license, everyone who did obtain
 the source code could pass it on for free or paid to other companies or even put it on the web for
 free download for everyone.

 As this would undermine completely the financial base of translate5s development and the translate5
 community, we at MittagQI would not longer support a company or supply it with updates for translate5,
 that would pass on the source code to third parties.

 Of course as long as the code stays within the company who obtained it, you are free to do
 everything you want with the source code (within the GPLv3 boundaries), like extending it or installing
 it multiple times.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5. This plug-in exception allows using GPLv3 for translate5 plug-ins,
 although translate5 core is licensed under AGPLv3.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/
declare(strict_types=1);

namespace MittagQI\Translate5\Segment;

use editor_ModelInstances;
use editor_Models_Db_SegmentQuality;
use editor_Models_Task;
use editor_Models_TaskUserAssoc;
use editor_Segment_Internal_TagComparision;
use editor_Segment_Quality_Manager;
use editor_Segment_Tag;
use Generator;
use Zend_Db;
use ZfExtended_Factory;

class QualityService
{
    public const ERROR_MASSAGE_PLEASE_SOLVE_ERRORS = 'Bitte lösen Sie alle Fehler der folgenden Kategorie ' .
    'ODER setzen Sie sie auf “falscher Fehler”:<br/>{categories}';
    private editor_Segment_Quality_Manager $manager;

    public function __construct()
    {
        $this->manager = editor_Segment_Quality_Manager::instance();
    }

    public function taskHasCriticalErrors(string $taskGuid, ?editor_Models_TaskUserAssoc $tua = null): bool
    {
        return $this->criticalErrorsIterator($taskGuid, $tua)->valid();
    }

    /**
     * @return string[]
     */
    public function getErroredCriticalCategories(string $taskGuid, ?editor_Models_TaskUserAssoc $tua = null): array
    {
        $categories = [];
        foreach ($this->criticalErrorsIterator($taskGuid, $tua, true) as $error) {
            $categories[] = $error['label'];
        }

        return $categories;
    }

    private function criticalErrorsIterator(
        string $taskGuid,
        ?editor_Models_TaskUserAssoc $tua,
        bool $includeLabels = false
    ): Generator {

        $task = editor_ModelInstances::taskByGuid($taskGuid);
        $taskConfig = $task->getConfig();

        if (empty($taskConfig->runtimeOptions->autoQA->mustBeZeroErrorsQualities)) {
            return [];
        }

        $labels = [];

        if ($includeLabels) {
            foreach ($this->manager->getActiveTypeToCategoryMap($task, $taskConfig) as $key => $label) {
                $labels[$key] = $label;
            }
        }

        $quality = ZfExtended_Factory::get(editor_Models_Db_SegmentQuality::class);
        $select = $quality->getAdapter()->select();
        $select
            ->from(
                ['qualities' => $quality->getName()],
                [
                    'qualities.type',
                    'qualities.category',
                    'count(qualities.id) as total',
                    'sum(qualities.falsePositive) as falsePositive'
                ]
            )
            // we need the editable prop for assigning structural faults of non-editable segments a virtual category
            ->from(['segments' => 'LEK_segments'], 'segments.editable')
            ->where('qualities.segmentId = segments.id')
            ->where('qualities.hidden = 0')
            // we want qualities from editable segments, only exception are structural internal tag errors
            // as usual, Zend Selects do not provide proper bracketing, so we're crating this manually here
            ->where('segments.editable = 1')
            ->where('qualities.taskGuid = ?', $taskGuid)
            ->group(['qualities.type', 'qualities.category']);

        if ($tua) {
            $step = $tua->getWorkflowStepName();

            if ($tua->isSegmentrangedTaskForStep($task, $step)) {
                $assignedSegments = $tua->getAllAssignedSegmentsByUserAndStep(
                    $task->getTaskGuid(),
                    $tua->getUserGuid(),
                    $step
                );

                if (!empty($assignedSegments)) {
                    $select->where('segments.segmentNrInTask IN (?)', $assignedSegments);
                }
            }
        }

        $stmt = $quality->getAdapter()->query($select);

        while ($row = $stmt->fetch(Zend_Db::FETCH_ASSOC)) {
            if (
                $row['total'] > $row['falsePositive']
                && $this->manager->mustBeZeroErrors($row['type'], $row['category'], $task)
            ) {
                if ($includeLabels) {
                    $row['label'] = $labels["{$row['type']}:{$row['category']}"];
                }

                yield $row;
            }
        }
    }
}