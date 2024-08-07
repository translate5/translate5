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

use Translate5\MaintenanceCli\FixScript\FixScriptAbstract;

/**
 * Fixes TRANSLATE-3967
 * The nature of the Problem leads to paired tags matched by RID not being matched and the opener
 * having an segment-index of the internal tag indexed before.
 * It seems the tag-index of the closer tag is correctly evaluated and the fix is just, to set the opener-index
 * according the closer index and remove the tag-fault
 */
class Translate3967_TagsPairedByRidFixer extends FixScriptAbstract
{
    public const TASKS_STEP = 10;

    private array $idMap;

    private int $numFixedTasks = 0;

    public function fix(): void
    {
        $this->processMonthTasks($this->getTasksForMonths('01', '02'), '01');
        $this->processMonthTasks($this->getTasksForMonths('02', '03'), '02');
        $this->processMonthTasks($this->getTasksForMonths('03', '04'), '03');
        $this->processMonthTasks($this->getTasksForMonths('04', '05'), '04');
        $this->processMonthTasks($this->getTasksForMonths('05'), '05');

        if ($this->numFixedTasks > 0) {
            $this->info('Successfully fixed ' . $this->numFixedTasks . ' task(s)');
        } else {
            $this->info('No Tasks have been affected, nothing was changed ...');
        }
    }

    private function processMonthTasks(array $taskGuids, string $month): void
    {
        $this->log('Processing ' . count($taskGuids) . ' tasks for month 2024-' . $month . "\n");

        $tasks = [];
        $amount = 0;
        foreach ($taskGuids as $guid) {
            $tasks[] = $guid;
            $amount++;
            if ($amount === self::TASKS_STEP) {
                $this->processTasks($tasks, $month);
                $tasks = [];
                $amount = 0;
            }
        }
        if ($amount > 0) {
            $this->processTasks($tasks, $month);
        }
    }

    private function processTasks(array $taskGuids, string $month): void
    {
        // first, gather all id's of potentially affected segments for the given tasks
        $segmentIds = $this->getAffectedSegmentIdsForTasks($taskGuids);

        if (count($segmentIds) === 0) {
            return;
        }

        $fixedIds = [];
        $fixedTaskIds = [];

        $this->log('AFFECTED SEGMENTS: ' . implode(', ', $segmentIds) . ' in ' . $month . "\n");

        // now fix the tags, segment by segment
        foreach ($segmentIds as $id) {
            try {
                $segment = ZfExtended_Factory::get(editor_Models_Segment::class);
                $segment->load($id);
                $task = editor_ModelInstances::taskByGuid($segment->getTaskGuid());

                // we only care for source & target ... multitarget segments are not in use currently
                $faults = $sourceFaults = 0;
                $faults += $this->checkSegmentField($task, $segment, 'source', 'source');
                $faults += $this->checkSegmentField($task, $segment, 'source', 'sourceEdit');
                $sourceFaults = $faults;
                $faults += $this->checkSegmentField($task, $segment, 'target', 'target');
                $faults += $this->checkSegmentField($task, $segment, 'target', 'targetEdit');

                // if we had faults, save segment
                if ($faults > 0) {
                    // reset quality entries for faulty structure
                    $fields = [];
                    if ($sourceFaults > 0) {
                        $fields[] = 'source';
                    }
                    if ($faults > $sourceFaults) {
                        $fields[] = 'target';
                    }
                    $qualitySql =
                        "DELETE FROM `LEK_segment_quality`"
                        . " WHERE `segmentId` = " . $segment->getId()
                        . " AND `field` " . (
                            count($fields) === 1 ?
                                " = '" . $fields[0] . "'"
                                : " IN '" . implode("','", $fields) . "'"
                        )
                        . " AND `category` = 'internal_tag_structure_faulty'"
                        . " AND `type` = 'internal'";

                    if ($this->debug) {
                        $this->log('SAVE SEGMENT: ' . $segment->getId() . ' in task ' . $task->getId());
                        $this->log('REMOVE QUALITY: ' . $qualitySql);
                    } else {
                        // save the segment and remove quality entries
                        $segment->save();
                        $task->db->getAdapter()->query($qualitySql);
                    }

                    $fixedIds[] = $id;
                    if (! in_array($task->getId(), $fixedTaskIds)) {
                        $fixedTaskIds[] = $task->getId();
                    }
                }
            } catch (Throwable $e) {
                try {
                    $taskGuid = isset($segment) ? $segment->getTaskGuid() : 'UNKNOWN';
                } catch (Throwable) {
                    $taskGuid = 'UNKNOWN';
                }
                $this->error(
                    'TRANSLATE-3967: ERROR, could not fix segment ' . $id . ' in task ' . $taskGuid . ': '
                    . $e->getMessage()
                );
            }
        }

        if (count($fixedIds) > 0) {
            $this->info(
                'Fixed  ' . count($fixedIds) . ' segments in ' . count($fixedTaskIds) . '.tasks:'
                . "\n segments: " . implode(', ', $fixedIds)
                . "\n tasks: " . implode(', ', $fixedTaskIds)
            );
        }

        $this->numFixedTasks += count($fixedTaskIds);
    }

    private function getTasksForMonths(string $start, string $end = null): array
    {
        $query =
            "SELECT DISTINCT `LEK_task`.`taskGuid` FROM `LEK_files`, `LEK_task`"
            . " WHERE `LEK_files`.`fileParser` = 'editor_Models_Import_FileParser_Xlf'"
            . " AND `LEK_files`.`taskGuid` = `LEK_task`.taskGuid AND `LEK_task`.`taskType` != 'project'"
            . " AND `LEK_task`.`created` >= '2024-" . $start . "-01 00:00:00'";
        if ($end !== null) {
            $query .= " AND `LEK_task`.`created` < '2024-" . $end . "-01 00:00:00'";
        }
        $query .= " ORDER BY `LEK_task`.`id` ASC";

        return $this->db->fetchCol($query);
    }

    private function getAffectedSegmentIdsForTasks(array $taskGuids): array
    {
        $query =
            "SELECT DISTINCT `segmentId` FROM `LEK_segment_data`"
            . " WHERE `taskGuid` IN ('" . implode("','", $taskGuids) . "')"
            . " AND (`original` LIKE '%ax:element-id%' OR `edited` LIKE '%ax:element-id%')" // we generally need across-namespaced id's for the BUG to show up ...
            . " AND (`original` LIKE '%rid=&quot;%' OR `original` LIKE '%rid=\"%' OR `edited` LIKE '%rid=&quot;%' OR `edited` LIKE '%rid=\"%')" // ... and an "rid" attribute (in souce or target)
            . " ORDER BY `LEK_segment_data`.`segmentId` ASC";

        return $this->db->fetchCol($query);
    }

    /**
     * Fixes the given field on the given segment. Returns 1, if field needed to be fixed, otherwise 0
     */
    private function checkSegmentField(
        editor_Models_Task $task,
        editor_Models_Segment $segment,
        string $field,
        string $dataName,
    ): int {
        $markup = $segment->get($dataName);
        if (! empty($markup)) {
            // create field-tags
            $fieldTags = new editor_Segment_FieldTags(
                $task,
                (int) $segment->getId(),
                $markup,
                $field,
                $dataName
            );

            // create fixed markup
            $markupFixed = $this->fixSegmentField($fieldTags, $field);
            if (! empty($markupFixed)) {
                if ($this->debug) {
                    $this->log('FIXED SEGMENT ' . $segment->getId() . ":");
                    $this->log(' BEFORE: ' . $this->debugSegmentMarkup($markup));
                    $this->log('  AFTER: ' . $this->debugSegmentMarkup($markupFixed) . "\n");
                }
                $segment->set($dataName, $markupFixed);

                return 1;
            }
        }

        return 0;
    }

    /**
     * Retrieves the fixed Markup or null, if nothing needs to be fixed
     */
    private function fixSegmentField(editor_Segment_FieldTags $fieldTags, string $field): ?string
    {
        $internalTags = $fieldTags->getByType(editor_Segment_Tag::TYPE_INTERNAL);
        if ($field === 'source') {
            $this->idMap = [];
        }
        if (count($internalTags) > 0) {
            $usedIndices = [];
            $ridTags = []; // nested array of tags with RID
            $lowestIndex = -1;
            $highestIndex = -1;
            $changed = false;
            foreach ($internalTags as $tag) {
                /** @var editor_Segment_Internal_Tag $tag */
                /** @phpstan-ignore-next-line */
                $tag->_rid = $tag->getUnderlyingRid();
                /** @phpstan-ignore-next-line */
                $tag->_id = $tag->getUnderlyingId();
                $tagIndex = $tag->getTagIndex();
                // single tags will create the map of used tag-indices
                /** @phpstan-ignore-next-line */
                if ($tag->isSingle() || $tag->_rid === -1) {
                    $usedIndices[] = $tagIndex;
                } elseif ($field === 'source') {
                    // we will need the tag-indices of paired tags of the source for finding indices in the target
                    /** @phpstan-ignore-next-line */
                    $this->idMap[$tag->_id] = $tagIndex;
                }
                // lowest & highest index
                if ($tagIndex > -1 && ($lowestIndex === -1 || $tagIndex < $lowestIndex)) {
                    $lowestIndex = $tagIndex;
                }
                if ($tagIndex > -1 && ($highestIndex === -1 || $tagIndex > $highestIndex)) {
                    $highestIndex = $tagIndex;
                }
                /** @phpstan-ignore-next-line */
                if ($tag->_rid > -1) {
                    /** @phpstan-ignore-next-line */
                    if (! array_key_exists($tag->_rid, $ridTags)) {
                        /** @phpstan-ignore-next-line */
                        $ridTags[$tag->_rid] = [];
                    }
                    /** @phpstan-ignore-next-line */
                    $ridTags[$tag->_rid][] = $tag;
                }
            }
            if (! empty($ridTags)) {
                // fix tag-indices for all rid-matched tag-pairs where they are not properly set
                foreach ($ridTags as $rid => $tagPair) {
                    /** @var editor_Segment_Internal_Tag[] $tagPair */
                    // check unfixable anomalies
                    if (count($tagPair) !== 2) {
                        throw new Exception(
                            'FAULTY STRUCTURE: ' . count($tagPair)
                            . ' segment(s) for RID ' . $rid
                        );
                    }
                    if ($tagPair[0]->getTagIndex() === -1 || $tagPair[1]->getTagIndex() === -1) {
                        throw new Exception(
                            'FAULTY STRUCTURE: Internal tag(s) with missing tag-index (short-tag-number)'
                        );
                    }
                    if (
                        $tagPair[0]->isSingle() ||
                        $tagPair[1]->isSingle() ||
                        ($tagPair[0]->isOpening() && ! $tagPair[1]->isClosing()) ||
                        ($tagPair[0]->isClosing() && ! $tagPair[1]->isOpening())
                    ) {
                        throw new Exception(
                            'FAULTY STRUCTURE: Paired internal tag(s) are actually not opening/closing '
                        );
                    }
                    // check fixable problems
                    $index0 = $tagPair[0]->getTagIndex();
                    $index1 = $tagPair[1]->getTagIndex();
                    $sourceIndex = -1;
                    // in case of a target-field we search for a source-index by id
                    if ($field === 'target') {
                        /** @phpstan-ignore-next-line */
                        $tagId = ($tagPair[0]->isOpening()) ? $tagPair[1]->_id : $tagPair[0]->_id;
                        $sourceIndex = (array_key_exists($tagId, $this->idMap) &&
                            ! in_array($this->idMap[$tagId], $usedIndices)) ? $this->idMap[$tagId] : -1;
                    }
                    // the usual case are differeing tag-indices, where in most cases (but not all!) the closer-index
                    // represents the tag-index/short-tag-nr to use
                    // some framing-tags also have a wrong target index compared to the source
                    if ($index0 !== $index1 ||
                        in_array($index0, $usedIndices) ||
                        ($sourceIndex > 0 && $index0 != $sourceIndex)
                    ) {
                        // the paired tag is faulty
                        $before = $tagPair[0]->getShortTagMarkup() . ' ... ' . $tagPair[1]->getShortTagMarkup();
                        // usually the closer-tag has the correct tag-index/short-tag-nr
                        $repairIndex = ($tagPair[0]->isOpening()) ? $index1 : $index0;
                        // in case we found a matching source-index in a target, we take it
                        if ($sourceIndex > 0) {
                            $repairIndex = $sourceIndex;
                        }
                        // in case the closer-index is already in use we generate a new one.
                        // this may creates a non-sequential numbering what is irrelevant
                        elseif (in_array($repairIndex, $usedIndices)) {
                            $highestIndex++;
                            $repairIndex = $highestIndex;
                            $usedIndices[] = $highestIndex;
                        }
                        $tagPair[0]->setTagIndex($repairIndex);
                        $tagPair[1]->setTagIndex($repairIndex);
                        $changed = true;

                        if ($this->debug) {
                            $after = $tagPair[0]->getShortTagMarkup() . ' ... ' . $tagPair[1]->getShortTagMarkup();
                            $this->log(
                                'FIXED internal tag paired by RID ' . $rid
                                . ' from "' . $before . '" to "' . $after . '"'
                            );
                        }
                    }
                }
            }
            if ($changed) {
                return $fieldTags->render();
            }
        }

        return null;
    }

    /**
     * Helper to check the results when debugging
     */
    private function debugSegmentMarkup(string $html): string
    {
        $pattern = '#<div\s*class="(open|close|single)[^"]*"\s*.*?(?!</div>)<span[^>]*>([^<]+)</span>'
            . '<span[^>]*data-originalid="([^"]*).*?(?!</div>).</div>#s';

        return preg_replace_callback($pattern, function ($matches) {
            return html_entity_decode($matches[2]);
        }, $html);
    }
}
