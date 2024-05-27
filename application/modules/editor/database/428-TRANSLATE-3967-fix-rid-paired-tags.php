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

//uncomment the following line, so that the file is not marked as processed:
$this->doNotSavePhpForDebugging = false;

$SCRIPT_IDENTIFIER = '428-TRANSLATE-3967-fix-rid-paired-tags.php';

/**
 * Fixes TRANSLATE-3967
 * The nature of the Problem leads to paired tags matched by RID not being matched and the opener
 * having an segment-index of the internal tag indexed before.
 * It seems the tag-index of the closer tag is correctly evaluated and the fix is just, to set the opener-index
 * according the closer index and remove the tag-fault
 */
class TagsPairedByRidFixer
{
    /**
     * When debugging, additional output is generated and nothing changed in the DB
     */
    public const DO_DEBUG = false;

    public function fix()
    {
        // first, gather all id's of potentially affected segments
        $segmentIds = $this->getAffectedSegmentIds();
        $fixedIds = [];
        $fixedTaskIds = [];

        if (self::DO_DEBUG) {
            error_log('AFFECTED SEGMENTS: ' . implode(', ', $segmentIds));
        }

        // now fix the tags, segment by segment
        foreach ($segmentIds as $id) {
            try {
                $segment = ZfExtended_Factory::get(editor_Models_Segment::class);
                $segment->load($id);
                $task = editor_ModelInstances::taskByGuid($segment->getTaskGuid());

                // we only care for source & target ... multitarget segments are not in use currently
                $faults = $sourceFaults = 0;
                $faults += $this->checkSegmentField($task, $segment, 'source', 'source');
                $faults += $this->checkSegmentField($task, $segment, 'sourceEdit', 'source');
                $sourceFaults = $faults;
                $faults += $this->checkSegmentField($task, $segment, 'target', 'target');
                $faults += $this->checkSegmentField($task, $segment, 'targetEdit', 'target');

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

                    if (self::DO_DEBUG) {
                        error_log('SAVE SEGMENT: ' . $segment->getId() . ' in task ' . $task->getId());
                        error_log('REMOVE QUALITY: ' . $qualitySql);
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
                    $taskGuid = $segment->getTaskGuid();
                } catch (Throwable) {
                    $taskGuid = 'UNKNOWN';
                }
                error_log(
                    'TRANSLATE-3967: ERROR, could not fix segment ' . $id . ' in task ' . $taskGuid . ': '
                    . $e->getMessage()
                );
            }
        }

        if (count($fixedIds) > 0) {
            error_log(
                'TRANSLATE-3967' . "\n"
                . ' Fixed  ' . count($fixedIds) . ' segments in ' . count($fixedTaskIds) . '.tasks:'
                . "\n segments: " . implode(', ', $fixedIds)
                . "\n tasks: " . implode(', ', $fixedTaskIds)
            );
        }
    }

    private function getAffectedSegmentIds(): array
    {
        $db = ZfExtended_Factory::get(editor_Models_Db_SegmentData::class);
        $query =
            "SELECT DISTINCT `LEK_segment_data`.`segmentId` FROM `LEK_segment_data`, `LEK_segments`"
            . " WHERE (`original` LIKE '%rid=&quot;%' OR `original` LIKE '%rid=\"%' OR `edited` LIKE '%rid=&quot;%' OR `edited` LIKE '%rid=\"%')" // "rid" attribute is in segment source or target
            . " AND `LEK_segment_data`.`segmentId` = `LEK_segments`.`id`" // join with segments
            . " AND `LEK_segments`.`timestamp` > '2024-04-30 00:00:00'" // earliest release day of 7.4.0
            . " ORDER BY `LEK_segment_data`.`segmentId` ASC";

        return $db->getAdapter()->fetchCol($query);
    }

    /**
     * Fixes the given field on the given segment. Returns 1, if field needed to be fixed, otherwise 0
     */
    private function checkSegmentField(editor_Models_Task $task, editor_Models_Segment $segment, string $field, string $dataName): int
    {
        $markup = $segment->get($field);
        if (! empty($markup)) {
            // create field-tags
            $fieldTags = new editor_Segment_FieldTags(
                $task,
                (int) $segment->getId(),
                $markup,
                $dataName,
                $field
            );

            // create fixed markup
            $markupFixed = $this->fixSegmentField($fieldTags, $dataName);
            if (! empty($markupFixed)) {
                if (self::DO_DEBUG) {
                    error_log('FIXED SEGMENT ' . $segment->getId() . ":");
                    error_log(' BEFORE: ' . $this->debugSegmentMarkup($markup));
                    error_log('  AFTER: ' . $this->debugSegmentMarkup($markupFixed));
                }
                $segment->set($field, $markupFixed);

                return 1;
            }
        }

        return 0;
    }

    /**
     * Retrieves the fixed Markup or null, if nothing needs to be fixed
     */
    private function fixSegmentField(editor_Segment_FieldTags $fieldTags, string $dataName): ?string
    {
        $internalTags = $fieldTags->getByType(editor_Segment_Tag::TYPE_INTERNAL);
        if (count($internalTags) > 0) {
            $allIndices = [];
            $ridTags = []; // nested array of tags with RID
            $lowestIndex = -1;
            $changed = false;
            foreach ($internalTags as $tag) {
                /* @var editor_Segment_Internal_Tag $tag */
                $tag->_rid = $tag->getUnderlyingRid();
                $tag->_id = $tag->getUnderlyingId();
                $tagIndex = $tag->getTagIndex();
                $allIndices[] = $allIndices;
                if ($tagIndex > -1 && ($lowestIndex === -1 || $tagIndex < $lowestIndex)) {
                    $lowestIndex = $tagIndex;
                }
                if ($tag->_rid > -1) {
                    if (! array_key_exists($tag->_rid, $ridTags)) {
                        $ridTags[$tag->_rid] = [];
                    }
                    $ridTags[$tag->_rid][] = $tag;
                }
            }
            if (! empty($ridTags)) {
                // fix tag-indices for all tag-pairs where it is not properly set
                foreach ($ridTags as $rid => $tagPair) {
                    /* @var editor_Segment_Internal_Tag[] $tagPair */
                    if (count($tagPair) !== 2) {
                        throw new Exception('FAULTY STRUCTURE: ' . count($tagPair) . ' segment(s) for RID ' . $rid);
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
                    if ($tagPair[0]->getTagIndex() !== $tagPair[1]->getTagIndex()) {
                        // FIX FAULTY PAIRED TAG
                        $before = $tagPair[0]->getShortTagMarkup() . ' ... ' . $tagPair[1]->getShortTagMarkup();
                        if ($tagPair[0]->isOpening()) {
                            $tagPair[0]->setTagIndex($tagPair[1]->getTagIndex());
                        } else {
                            $tagPair[1]->setTagIndex($tagPair[0]->getTagIndex());
                        }
                        $changed = true;

                        if (self::DO_DEBUG) {
                            $after = $tagPair[0]->getShortTagMarkup() . ' ... ' . $tagPair[1]->getShortTagMarkup();
                            error_log(
                                'FIXED internal tag paired by RID ' . $rid . ' from "' . $before . '" to "' . $after . '"'
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

$fixer = new TagsPairedByRidFixer();
$fixer->fix();
