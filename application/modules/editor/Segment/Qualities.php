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

/**
 * Abstraction to bundle the segment's internal tags per field to have a model to be passed across the quality providers
 * These APIs are meant to be used by editor_Segment_Tags only !
 */
final class editor_Segment_Qualities
{
    /**
     * @var int
     */
    private $segmentId;

    /**
     * @var string
     */
    private $taskGuid;

    /**
     * see modes in editor_Segment_Processing
     * @var string
     */
    private $processingMode;

    /**
     * @var editor_Models_Db_SegmentQuality
     */
    private $table;

    /**
     * @var editor_Models_Db_SegmentQualityRow[]
     */
    private $existing = [];

    /**
     * @var editor_Models_Db_SegmentQualityRow[]
     */
    private $added = [];

    /**
     * @var editor_Segment_Alike_Qualities
     */
    private $alikeQualities = null;

    public function __construct(int $segmentId, string $taskGuid, string $processingMode, editor_Segment_Alike_Qualities $alikeQualities = null)
    {
        $this->segmentId = $segmentId;
        $this->taskGuid = $taskGuid;
        $this->processingMode = $processingMode;
        $this->table = ZfExtended_Factory::get('editor_Models_Db_SegmentQuality');
        // we only overwrite/adjust existing entries when editing or saving Alike segments
        if ($this->processingMode == editor_Segment_Processing::EDIT || $this->processingMode == editor_Segment_Processing::ALIKE) {
            // QM-qualities will not be processed with the segment-tags at all as they are not related to the segment's texts but relate on the whole segment
            foreach ($this->table->fetchFiltered(null, $segmentId, editor_Segment_Tag::TYPE_QM, true) as $quality) {
                /* @var $qualityRow editor_Models_Db_SegmentQualityRow */
                $quality->processingState = 'delete';
                $this->existing[] = $quality;
            }
            if ($this->processingMode == editor_Segment_Processing::ALIKE) {
                $this->alikeQualities = $alikeQualities;
            }
        }
        // error_log('PROCESS QUALITIES FOR SEGMENT '.$this->segmentId.', MODE '.$this->processingMode.', EXISTING: '.count($this->existing));
    }

    /**
     * Adds a quality independently of a tag (usually do not use start & end index then)
     * NOTE that the $additopnal data only can be a flat Object !
     */
    public function add(
        string $field,
        string $type,
        string $category,
        int $startIndex,
        int $endIndex,
        stdClass|array|null $additionalData = null,
        bool $hidden = false
    ): void {
        // we can not compare the text indices because qualities added vie ->add() are qualities that relate to the whole segment content !
        $quality = $this->findExistingByProps($field, $type, $category, $additionalData);
        if ($quality == null) {
            $quality = $this->table->createRow([], Zend_Db_Table_Abstract::DEFAULT_DB);
            /* @var $quality editor_Models_Db_SegmentQualityRow */
            $quality->segmentId = $this->segmentId;
            $quality->taskGuid = $this->taskGuid;
            $quality->field = $field;
            $quality->type = $type;
            $quality->category = $category;
            $quality->startIndex = $startIndex;
            $quality->endIndex = $endIndex;
            $quality->falsePositive = 0;
            $quality->hidden = $hidden;
            if ($additionalData !== null) {
                $quality->setAdditionalData($additionalData);
            }
            // new qualities without tags will be saved in a batch
            $quality->processingState = 'new';
            $this->added[] = $quality;
        } else {
            if ($quality->startIndex != $startIndex || $quality->endIndex != $endIndex) {
                $quality->startIndex = $startIndex;
                $quality->endIndex = $endIndex;
                $quality->save();
            }
            $quality->processingState = 'keep';
        }
    }

    /**
     * Drops a quality independently of a tag
     * NOTE that the $additopnal data only can be a flat Object !
     */
    public function drop(string $field, string $type, string $category, int $startIndex, int $endIndex, stdClass $additionalData = null)
    {
        $quality = $this->findExistingByProps($field, $type, $category, $additionalData);
        if ($quality) {
            $quality->delete();
        }
    }

    /**
     * Drop qualities by type
     *
     * @throws Zend_Db_Table_Row_Exception
     */
    public function dropByType(string $field, string $type)
    {
        foreach ($this->getExisting() as $quality) {
            if ($quality->field == $field && $quality->type === $type) {
                $quality->delete();
            }
        }
    }

    /**
     * Adds a quality by it's associated tag
     */
    public function addByTag(editor_Segment_Tag $tag, string $field = null)
    {
        if ($field === null) {
            $field = $tag->field;
        }
        $changed = false;
        // find by ID
        $quality = $this->findExistingById($tag->getQualityId());
        // Fallback: find by identity of props (mainly as fallback for not yet processed term tags when updating instance)
        $quality = ($quality == null) ? $this->findExistingByTag($tag, $field) : $quality;
        if ($quality == null) {
            // add new quality
            $quality = $this->table->createRow([], Zend_Db_Table_Abstract::DEFAULT_DB);
            /* @var $quality editor_Models_Db_SegmentQualityRow */
            $quality->segmentId = $this->segmentId;
            $quality->taskGuid = $this->taskGuid;
            $quality->falsePositive = 0;
            $changed = $this->setQualityPropsByTag($quality, $tag, $field, true);
            $this->added[] = $quality;
        } else {
            // it may be unneccessary to save the quality if everything stayed the way it was
            $changed = $this->setQualityPropsByTag($quality, $tag, $field, false);
        }
        // CRUCIAL: the false quality prop is currently set by the frontend directly async to the quality model.
        // so, normally the DB has priority of the frontend and the DB-val is transfered to the tag.
        // the reason is, that the changing in the HTML editor may fails and some qualities simply have no tags in the markup. If that ever changes, this code has to change
        // exception from this is the alike copying, where we reflect the copied tags completely
        if ($this->processingMode == editor_Segment_Processing::ALIKE) {
            if ($quality->falsePositive != $tag->getFalsePositiveVal()) {
                $quality->falsePositive = $tag->getFalsePositiveVal();
                $changed = true;
            }
        } else {
            $tag->setFalsePositive($quality->falsePositive);
        }
        // if anything changed, we need to save
        if ($changed) {
            $quality->save();
        }
        // CRUCIAL: transfer our ID back to the tag, otherwise it will not be identifyable in the editor nor in the next editing
        $tag->setQualityId($quality->id);
        // triggers to keep the tag in the current state when saving the qualities
        $quality->processingState = 'keep';
    }

    /**
     * Just needed for quality cloning in Alike processing
     */
    public function addNew(editor_Models_Db_SegmentQualityRow $row)
    {
        $row->segmentId = $this->segmentId;
        $row->processingState = 'new';
        $this->added[] = $row;
    }

    /**
     * Clones qualities from the originating segment in a alike segment processing
     * @throws Exception
     */
    public function cloneAlikeType(string $type)
    {
        if ($this->processingMode != editor_Segment_Processing::ALIKE) {
            throw new Exception('Called ::cloneAlikeType() but the current processing mode is not ALIKE');
        } elseif ($this->alikeQualities == null) {
            throw new Exception('Called ::cloneAlikeType() but the AlikeQualities are not set');
        } else {
            $this->alikeQualities->cloneForType($type, $this);
        }
    }

    /**
     * Saves the collected qualities back to the DB and resets all our cached qualities
     */
    public function save()
    {
        $newQualities = [];
        $deleteIds = [];
        foreach ($this->existing as $quality) {
            if ($quality->processingState == 'delete') {
                $deleteIds[] = $quality->id;
            }
        }
        $this->table->deleteByIds($deleteIds);

        foreach ($this->added as $quality) {
            if ($quality->processingState == 'new') {
                $newQualities[] = $quality;
            }
        }
        editor_Models_Db_SegmentQuality::saveRows($newQualities);
        $this->added = [];
        $this->existing = [];
    }

    /**
     * Retrieves all "really" new qualities (added ones not yet savednot yet saved).
     * The cached new qualities will be reset when calling this API, so the consuming code is responsible to save those
     * @return editor_Models_Db_SegmentQualityRow[]
     */
    public function extractNewQualities(): array
    {
        $newQualities = [];
        $keptQualities = [];
        foreach ($this->added as $quality) {
            if ($quality->processingState == 'new') {
                $newQualities[] = $quality;
            } else {
                $keptQualities[] = $quality;
            }
        }
        $this->added = $keptQualities;

        return $newQualities;
    }

    /**
     * Transfers all props from a segment-tag to the quality entry and tracks, if the existing data had to be changed
     * This will not process the falePositive val
     */
    private function setQualityPropsByTag(editor_Models_Db_SegmentQualityRow $quality, editor_Segment_Tag $tag, string $field, bool $changed): bool
    {
        if ($quality->field != $field) {
            $quality->field = $field;
            $changed = true;
        }
        if ($quality->type !== $tag->getType()) {
            $quality->type = $tag->getType();
            $changed = true;
        }
        if ($quality->category != $tag->getCategory()) {
            $quality->category = $tag->getCategory();
            $changed = true;
        }
        if ($quality->startIndex !== $tag->startIndex) {
            $quality->startIndex = $tag->startIndex;
            $changed = true;
        }
        if ($quality->endIndex !== $tag->endIndex) {
            $quality->endIndex = $tag->endIndex;
            $changed = true;
        }
        $additionalData = $tag->getAdditionalData();
        if (! $quality->isAdditionalDataEqual($additionalData)) {
            $quality->setAdditionalData($additionalData);
            $changed = true;
        }
        if ($tag->getType() == editor_Segment_Tag::TYPE_MQM) {
            /* @var $tag editor_Segment_Mqm_Tag */
            if ($quality->categoryIndex !== $tag->getCategoryIndex()) {
                $quality->categoryIndex = $tag->getCategoryIndex();
                $changed = true;
            }
            if ($quality->severity != $tag->getSeverity()) {
                $quality->severity = $tag->getSeverity();
                $changed = true;
            }
            if ($quality->comment != $tag->getComment()) {
                $quality->comment = $tag->getComment();
                $changed = true;
            }
        }

        return $changed;
    }

    private function findExistingById(int $id): ?editor_Models_Db_SegmentQualityRow
    {
        if ($id > -1) {
            foreach ($this->existing as $quality) {
                if ($quality->id == $id) {
                    return $quality;
                }
            }
        }

        return null;
    }

    /**
     * Finds an existing quality that was not yet found that matches all given props. This is expected to be a quality without segment tags and thus must match the whole width of the segment
     * Needed for persistance of falsePositive only
     */
    private function findExistingByProps(string $field, string $type, string $category, stdClass|array|null $additionalData): ?editor_Models_Db_SegmentQualityRow
    {
        foreach ($this->existing as $quality) {
            if ($quality->processingState == 'delete' && $type === $quality->type && $field == $quality->field && $category == $quality->category && $quality->isAdditionalDataEqual($additionalData)) {
                return $quality;
            }
        }

        return null;
    }

    /**
     * Finds an existing quality entry that was not yet found for a segment tag
     * Needed for persistance of falsePositive only
     */
    private function findExistingByTag(editor_Segment_Tag $tag, string $field): ?editor_Models_Db_SegmentQualityRow
    {
        foreach ($this->existing as $quality) {
            if ($quality->processingState == 'delete' && $tag->isQualityEqual($quality)) {
                return $quality;
            }
        }

        return null;
    }

    /**
     * @return editor_Models_Db_SegmentQualityRow[]
     */
    public function getExisting()
    {
        return $this->existing;
    }

    /**
     * @param string $newline
     */
    public function debug($newline = "\n"): string
    {
        $text = 'Segment-qualities for segment ' . $this->segmentId . $newline . '  Existing qualities:';
        foreach ($this->existing as $quality) {
            $text .= $newline . '    ' . $quality->debug();
        }
        $text .= $newline . '  Added qualities:';
        foreach ($this->added as $quality) {
            $text .= $newline . '    ' . $quality->debug();
        }

        return $text;
    }
}
