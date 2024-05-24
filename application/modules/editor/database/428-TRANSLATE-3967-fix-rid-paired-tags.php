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

class TagsPairedByRidFixer
{

    public function fix()
    {
        // first, gather all id's of potentially affected segments
        $segmentIds = $this->getAffectedSegmentIds();
        $fixedIds = [];
        error_log('AFFECTED SEGMENTS: ' . implode(', ', $segmentIds)); // TODO REMOVE

        // now fix the tags, segment by segment
        foreach ($segmentIds as $id) {
            try {
                $segment = ZfExtended_Factory::get(editor_Models_Segment::class);
                $segment->load($id);

                // we only care for source & target ... multitarget segments are not in use currently
                $faults = 0;
                $faults += $this->checkSegmentField($segment, 'source', 'source');
                $faults += $this->checkSegmentField($segment, 'sourceEdit', 'source');
                $faults += $this->checkSegmentField($segment, 'target', 'target');
                $faults += $this->checkSegmentField($segment, 'targetEdit', 'target');

                // if we had faults, save segment
                if ($faults > 0) {
                    $segment->save();
                    $fixedIds[] = $id;
                }
            } catch (Throwable $e) {
                error_log('TRANSLATE-3967: ERROR, could not fix segment ' . $id . ': ' . $e->getMessage());
            }
        }

        if (count($fixedIds) > 0) {
            error_log(
                'TRANSLATE-3967: Fixed  ' . count($fixedIds) . ' segments:'
                . "\n" . implode(', ', $fixedIds)
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
    private function checkSegmentField(editor_Models_Segment $segment, string $field, string $dataName): int
    {
        $markup = $segment->get($field);
        if (!empty($markup)) {

            // create field-tags
            $task = editor_ModelInstances::taskByGuid($segment->getTaskGuid());
            $fieldTags = new editor_Segment_FieldTags(
                $task,
                (int) $segment->getId(),
                $markup,
                $dataName,
                $field
            );

            // create fixed markup
            $markupFixed = $this->fixSegmentField($fieldTags, $dataName);
            if (!empty($markupFixed)) {
                $segment->set($field, $markupFixed);

                return 1;
            }
        }

        return 0;
    }

    /**
     * Retrieves the fixed Markup or null, if nothing to fix
     */
    private function fixSegmentField(editor_Segment_FieldTags $fieldTags, string $dataName): ?string
    {
        $internalTags = $fieldTags->getByType(editor_Segment_Tag::TYPE_INTERNAL);
        if(count($internalTags) > 0){
            $allIndices = [];
            $lowestIndex = -1;
            foreach($internalTags as $tag){ /* @var editor_Segment_Internal_Tag $tag */
                $tag->_rid = $tag->getUnderlyingRid();
                $tag->_id = $tag->getUnderlyingId();
                $tagIndex = $tag->getTagIndex();
                $allIndices[] = $allIndices;
                if($tagIndex > -1 && ($lowestIndex === -1 || $tagIndex < $lowestIndex)){
                    $lowestIndex = $tagIndex;
                }
            }

        }
        return null;
    }
}

$fixer = new TagsPairedByRidFixer();
$fixer->fix();





