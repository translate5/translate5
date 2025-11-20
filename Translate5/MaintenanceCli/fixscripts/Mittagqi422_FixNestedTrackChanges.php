<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

use MittagQI\Translate5\Segment\Tag\SegmentTagSequence;
use Translate5\MaintenanceCli\FixScript\FixScriptAbstract;

/**
 * Fixes faulty (nested) TrackChanges-Tags due to problems with the new RichText-Editor
 * The editor was released in ## [7.28.0] - 2025-08-15, so we just care for segments created after this date
 */
class Mittagqi422_FixNestedTrackChanges extends FixScriptAbstract
{
    public const TASK_BATCH = 10;

    private array $affectedTaskGuids;

    private int $numFixedSegments;

    public function fix(): void
    {
        $this->numFixedSegments = 0;
        // we fetch all task-guids after the release - these may be a few thousand for industry clients
        $this->affectedTaskGuids = $this->db->fetchCol(
            "SELECT `taskGuid` FROM LEK_task WHERE `created` > '2025-08-15 00:00:00'"
        );
        $this->info(count($this->affectedTaskGuids) . ' tasks potentially affected after 2025-08-15' . "\n");

        $batch = [];
        foreach ($this->affectedTaskGuids as $taskGuid) {
            if (count($batch) > self::TASK_BATCH) {
                $this->processTaskBatch($batch);
                $batch = [];
            }
            $batch[] = $taskGuid;
        }
        if (count($batch) > 0) {
            $this->processTaskBatch($batch);
        }

        if ($this->numFixedSegments > 0) {
            $this->io->warning('Fixed ' . $this->numFixedSegments . ' segments.');
        } else {
            $this->io->success('No segments have to be fixed.');
        }
    }

    private function processTaskBatch(array $taskGuids): void
    {
        $affectedSegments = $this->db->fetchAssoc(
            "SELECT `segmentId`, `name` FROM `LEK_segment_data` WHERE" .
            " `taskGuid` IN ('" . implode("','", $taskGuids) . "')" .
            " AND (`edited` REGEXP '<ins[^>]*>(?:(?!<\/ins>)[\\\\s\\\\S])*<del' OR `edited` REGEXP '<del[^>]*>(?:(?!<\/del>)[\\\\s\\\\S])*<ins')"
        );
        // array like: segmentId => [fields] where field ias "source" or "target" and will always be the edited variant ...
        $segmentMap = [];
        foreach ($affectedSegments as $segment) {
            if (array_key_exists($segment['segmentId'], $segmentMap)) {
                $segmentMap[$segment['segmentId']][] = $segment['name'];
            } else {
                $segmentMap[$segment['segmentId']] = [$segment['name']];
            }
        }
        foreach ($segmentMap as $segmentId => $fields) {
            $this->processSegment($segmentId, $fields);
        }
    }

    private function processSegment(int $segmentId, array $fields): void
    {
        try {
            $segment = ZfExtended_Factory::get(editor_Models_Segment::class);
            $segment->load($segmentId);
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $this->error('Segment with id "' . $segmentId . '" could not be found or other errors occured: ' .
                $e->getMessage());

            return;
        }

        $savedFields = [];

        $history = $segment->getNewHistoryEntity();

        foreach ($fields as $field) {
            $markup = $segment->get($field . 'Edit');
            $tags = new SegmentTagSequence($markup);

            if ($tags->hasNestedTrackChangesTags()) {
                $tags->normalizeTrackChangesTags();
                $newMarkup = $tags->render();
                $toSort = $segment->stripTags($newMarkup, $field === 'source');

                if ($this->doFix) {
                    $segment->set($field . 'Edit', $newMarkup);
                    $segment->set($field . 'EditToSort', $toSort);
                }

                $savedFields[] = $field . 'Edit';
            }
        }

        if (count($savedFields) === 0) {
            return;
        }

        if ($this->doFix) {
            $history->save();
            $segment->save();
        }

        $this->info(
            $this->doFix ? 'Fixed ' : 'Will fix ' . 'Segment ' . $segmentId . ', Nr. ' . $segment->getSegmentNrInTask() .
            ' in task ' . $segment->getTaskGuid() . ' where fields "' .
            implode('", "', $savedFields) . '" were affected' . "\n"
        );

        $this->numFixedSegments++;
    }
}
