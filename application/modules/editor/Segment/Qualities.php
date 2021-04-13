<?php
/*
 START LICENSE AND COPYRIGHT
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
 
 This file is part of a plug-in for translate5.
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
 
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
 http://www.gnu.org/licenses/gpl.html
 http://www.translate5.net/plugin-exception.txt
 
 END LICENSE AND COPYRIGHT
 */

/**
 * Abstraction to bundle the segment's internal tags per field to have a model to be passed across the quality providers
 */
final class editor_Segment_Qualities {

    /**
     * 
     * @var int
     */
    private $segmentId;
    /**
     * 
     * @var string
     */
    private $taskGuid;
    /**    
     * 
     * @var editor_Models_Db_SegmentQuality
     */
    private $table;
    /**
     *
     * @var editor_Models_Db_SegmentQualityRow[]
     */
    private $existing = [];
    /**
     *
     * @var editor_Models_Db_SegmentQualityRow[]
     */
    private $added = [];
    /**
     * 
     * @param int $segmentId
     * @param string $taskGuid
     */
    public function __construct(int $segmentId, string $taskGuid){
        $this->segmentId = $segmentId;
        $this->taskGuid = $taskGuid;
        $this->table = ZfExtended_Factory::get('editor_Models_Db_SegmentQuality');
        foreach($this->table->fetchBySegment($segmentId) as $quality){
            /* @var $qualityRow editor_Models_Db_SegmentQualityRow */
            $quality->processingState = 'delete';
            $this->existing[] = $quality;
        }
    }
    /**
     * Adds a quality independently of a tag (usually do not use start & end index then)
     * @param string $field
     * @param string $type
     * @param string $category
     * @param int $startIndex
     * @param int $endIndex
     */
    public function add(string $field, string $type, string $category, int $startIndex=0, int $endIndex=-1, $falsePositive=-1){
        $quality = $this->findExistingByProps($field, $type, $category, $startIndex, $endIndex);
        if($quality == NULL){
            $quality = $this->table->createRow();
            /* @var $quality editor_Models_Db_SegmentQualityRow */
            $quality->segmentId = $this->segmentId;
            $quality->taskGuid = $this->taskGuid;
            $quality->field = $field;
            $quality->type = $type;
            $quality->category = $category;
            $quality->startIndex = $startIndex;
            $quality->endIndex = $endIndex;
            $quality->falsePositive = ($falsePositive > -1) ? $falsePositive : 0;
            // new qualities without tags will be saved in a batch
            $quality->processingState = 'new';
            $this->added[] = $quality;
        } else {
            // since all props match we simply can keep te quality, just in case of changed false-positiveness this has to be changed
            if($falsePositive > -1 && $falsePositive !== $quality->falsePositive){
                $quality->falsePositive = $falsePositive;
                $quality->save();
            }
            $quality->processingState = 'keep';
        }
    }
    /**
     * Adds a quality by it's associated tag
     * @param editor_Segment_Tag $tag
     * @param string $field: if not given, it is assumed, the tag has it's field property set and will be used
     */
    public function addByTag(editor_Segment_Tag $tag, string $field=NULL){
        if($field == null){
            $field = $tag->field;
        }
        // find by ID
        $quality = $this->findExistingById($tag->getQualityId());
        // Fallback: find by identity of props (mainly as fallback for not yet processed term tags when updating instance)
        $quality = ($quality == NULL) ? $this->findExistingByTag($tag, $field) : $quality;
        if($quality == NULL){
            // add new quality
            $quality = $this->table->createRow();
            /* @var $quality editor_Models_Db_SegmentQualityRow */
            $quality->segmentId = $this->segmentId;
            $quality->taskGuid = $this->taskGuid;
            $this->setQualityPropsByTag($quality, $tag, $field, true);
            $quality->save();
            $this->added[] = $quality;
        } else {
            // the processing state decides, if the quality will be deleted, saved or stay as is
            if($this->setQualityPropsByTag($quality, $tag, $field, false)){
                $quality->save();
            }
        }
        // QUIRK the false quality prop is currently set by the frontend directly to the quality model. If that ever changes, this code has to change
        $tag->setFalsePositive($quality->falsePositive);
        $tag->setQualityId($quality->id);
        $quality->processingState = 'keep';
    }
    /**
     * Saves the collected qualities back to the DB and resets all our cached qualities
     */
    public function save(){
        $newQualities = [];
        $deleteIds = [];
        foreach($this->existing as $quality){
            if($quality->processingState == 'delete'){
                $deleteIds[] = $quality->id;
            }
        }
        $this->table->deleteByIds($deleteIds);
        foreach($this->added as $quality){
            if($quality->processingState == 'new'){
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
    public function extractNewQualities(){
        $newQualities = [];
        $keptQualities = [];
        foreach($this->added as $quality){
            if($quality->processingState == 'new'){
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
     * @param editor_Models_Db_SegmentQualityRow $quality
     * @param editor_Segment_Tag $tag
     * @param string $field
     * @param bool $changed
     * @return bool
     */
    private function setQualityPropsByTag(editor_Models_Db_SegmentQualityRow $quality, editor_Segment_Tag $tag, string $field, bool $changed) : bool {
        if($quality->field != $field){
            $quality->field = $field;
            $changed = true;
        }
        if($quality->type !== $tag->getType()){
            $quality->type = $tag->getType();
            $changed = true;
        }
        if($quality->category != $tag->getCategory()){
            $quality->category = $tag->getCategory();
            $changed = true;
        }
        if($quality->startIndex !== $tag->startIndex){
            $quality->startIndex = $tag->startIndex;
            $changed = true;
        }
        if($quality->endIndex !== $tag->endIndex){
            $quality->endIndex = $tag->endIndex;
            $changed = true;
        }
        if($tag->getType() == editor_Segment_Tag::TYPE_MQM){
            /* @var $tag editor_Segment_Mqm_Tag */
            if($quality->categoryIndex !== $tag->getCategoryIndex()){
                $quality->categoryIndex = $tag->getCategoryIndex();
                $changed = true;
            }
            if($quality->severity != $tag->getSeverity()){
                $quality->severity = $tag->getSeverity();
                $changed = true;
            }
            if($quality->comment != $tag->getComment()){
                $quality->comment = $tag->getComment();
                $changed = true;
            }
        }
        return $changed;
    }
    /**
     * 
     * @param int $id
     * @return editor_Models_Db_SegmentQualityRow|NULL
     */
    private function findExistingById(int $id) : ?editor_Models_Db_SegmentQualityRow {
        if($id > -1){
            foreach($this->existing as $quality){
                if($quality->id == $id){
                    return $quality;
                }
            }
        }
        return NULL;
    }
    /**
     * Finds an existing quality that matches all given props
     * @param string $field
     * @param string $type
     * @param string $category
     * @param int $startIndex
     * @param int $endIndex
     * @return editor_Models_Db_SegmentQualityRow|NULL
     */
    private function findExistingByProps(string $field, string $type, string $category, int $startIndex, int $endIndex) : ?editor_Models_Db_SegmentQualityRow {
        foreach($this->existing as $quality){
            if($type === $quality->type && $field == $quality->field && $category == $quality->category && $startIndex === $quality->startIndex && $endIndex === $quality->endIndex){
                return $quality;
            }
        }
        return NULL;
    }
    /**
     * Finds an existing quality entry for any tag spanning the whole width
     * @param editor_Segment_Tag $tag
     * @param string $field
     * @return editor_Models_Db_SegmentQualityRow|NULL
     */
    private function findExistingByTag(editor_Segment_Tag $tag, string $field) : ?editor_Models_Db_SegmentQualityRow {
        foreach($this->existing as $quality){
            if($tag->getType() === $quality->type && $field == $quality->field && $tag->startIndex === $quality->startIndex && $tag->endIndex === $quality->endIndex){
                if(($tag->getType() == editor_Segment_Tag::TYPE_MQM && $this->isMqmEqual($tag, $quality)) || $tag->getCategory() == $quality->category){
                    return $quality;
                }
            }
        }
        return NULL;
    }
    /**
     * Checks the MQM specific props of a tag and a quality entry
     * @param editor_Segment_Mqm_Tag $tag
     * @param editor_Models_Db_SegmentQualityRow $quality
     * @return boolean
     */
    private function isMqmEqual(editor_Segment_Mqm_Tag $tag, editor_Models_Db_SegmentQualityRow $quality) : bool {
        return ($tag->getCategoryIndex() === $quality->categoryIndex && $tag->getSeverity() == $quality->severity && $tag->getComment() == $quality->comment);
    }
}
