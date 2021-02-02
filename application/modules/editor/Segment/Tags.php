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
class editor_Segment_Tags implements JsonSerializable {
    
    /**
     * 
     * @param editor_Models_Task $task
     * @param bool $editorMode
     * @param editor_Models_Segment[] $segments
     * @param bool $useTagsModel: optional, enables the segment data being taken from the serialized data if available as neccessary in the import-process
     * @return editor_Segment_Tags[]
     */
    public static function fromSegments(editor_Models_Task $task, bool $editorMode, array $segments, bool $useTagsModel=true) : array {
        $tags = [];
        foreach($segments as $segment){
            $tags[] = self::fromSegment($task, $editorMode, $segment, $useTagsModel);
        }
        return $tags;
    }
    /**
     * Creates segment-tags from a segment.
     * If the segment already has a tags-model saved, it is created by JSON, otherwise by the current segment data
     * 
     * @param editor_Models_Task $task
     * @param bool $editorMode
     * @param editor_Models_Segment $segment
     * @param bool $useTagsModel. optional, enables the segment data being taken from the serialized data if available as neccessary in the import-process
     * @return editor_Segment_Tags
     */
    public static function fromSegment(editor_Models_Task $task, bool $editorMode, editor_Models_Segment $segment, bool $useTagsModel=true) : editor_Segment_Tags {
        if($useTagsModel && $segment->hasSegmentTagsJSON()){
            return self::fromJson($task, $editorMode, $segment->getSegmentTagsJSON(), $segment);
        }
        return new editor_Segment_Tags($task, $editorMode, $segment);
    }    
    /**
     * The counterpart to ::toJson: creates the tags from the serialized json data
     * @param string $jsonString
     * @throws Exception
     * @return editor_Segment_Tags
     */
    public static function fromJson(editor_Models_Task $task, bool $editorMode, string $jsonString, editor_Models_Segment $segment=NULL) : editor_Segment_Tags {
        try {
            $data = json_decode($jsonString);
            if($data->taskGuid != $task->getTaskGuid()){
                throw new Exception('Deserialization of editor_Segment_Tags from JSON-Object failed because of task-guid mismatch: '.json_encode($data));
            }
            $tags = new editor_Segment_Tags($task, $editorMode, $segment, $data);
            $tags->initFromJson($data);
            return $tags;
        } catch (Exception $e) {
            throw new Exception('Could not deserialize editor_Segment_Tags from JSON-Object '.json_encode($data));
        }
    }
    /**
     *
     * @var editor_Segment_FieldTags
     */
    private $sourceOriginal = NULL;
    /**
     *
     * @var editor_Segment_FieldTags
     */
    private $source;
    /**
     *
     * @var editor_Segment_FieldTags[]
     */
    private $targets;
    /**
     *
     * @var editor_Models_Task
     */
    private $task;
    /**
     *
     * @var bool
     */
    private $editorMode;
    /**
     *
     * @var bool
     */
    private $sourceEditingEnabled;
    /**
     *
     * @var int
     */
    private $segmentId;
    /**
     *
     * @var editor_Models_Segment
     */
    private $segment = null;
    /**
     * 
     * @var editor_Models_Db_SegmentQualityRow[]
     */
    private $qualities = [];
    /**
     * 
     * @var editor_Models_Db_SegmentQuality
     */
    private $qualityTable = NULL;
    
    /**
     * 
     * @param editor_Models_Task $task
     * @param bool $isEditor
     */
    public function __construct(editor_Models_Task $task, bool $isEditor, editor_Models_Segment $segment=NULL, stdClass $serializedData=NULL) {
        $this->task = $task;
        $this->editorMode = $isEditor;
        $this->segment = $segment;
        if($serializedData != NULL){            
            $this->initFromJson($serializedData);
         } else if($segment != NULL){
            $this->segmentId = $segment->getId();
            $this->init();
        } else {
            throw new Exception('editor_Segment_Tags needs either a segment-instance with field manager or serialized data for instantiation');
        }
    }
    /** 
     * Initializes from scratch (used in the initial quality worker), creates the inital data structure
     * @param editor_Models_SegmentFieldManager $fieldManager
     */ 
    private function init(){
        $fieldManager = editor_Models_SegmentFieldManager::getForTaskGuid($this->task->getTaskGuid());
        $this->sourceEditingEnabled = $this->task->getEnableSourceEditing();
        $sourceField = $fieldManager->getFirstSourceName();
        $sourceFieldEditIndex = $fieldManager->getEditIndex($sourceField);
        // in case of an editing process the original source will be handled seperately
        // if we are an import, the original source and source will be handled identically - the "normal"  source is the edited source then
        if($this->editorMode && $this->sourceEditingEnabled){
            // original source (what is the source in all other cases)
            $this->sourceOriginal = new editor_Segment_FieldTags($this->segmentId, $this->segment->get($sourceField), $sourceField, 'SourceOriginal');
            // source here is the editable source
            $this->source = new editor_Segment_FieldTags($this->segmentId, $this->segment->get($sourceFieldEditIndex), $sourceFieldEditIndex, $sourceField);
        } else {
            $saveTo = ($this->sourceEditingEnabled) ? [$sourceField, $sourceFieldEditIndex] : $sourceField;
            // on import with enabled source editing, we copy the source as editedSource as well
            $this->source = new editor_Segment_FieldTags($this->segmentId, $this->segment->get($sourceField), $saveTo, $sourceField);
        }
        $this->targets = [];
        foreach ($fieldManager->getFieldList() as $field) {
            /* @var $field Zend_Db_Table_Row */
            if($field->type == editor_Models_SegmentField::TYPE_TARGET && $field->editable) {
                $editIndex = $fieldManager->getEditIndex($field->name);
                // when importing, the field will be saved as edit field & as normal field
                $saveTo = ($this->editorMode) ? $editIndex : [$field->name, $editIndex];
                // the field name sent to the termtagger differs between import and editing (WHY?)
                $ttField = ($this->editorMode) ? $editIndex : $field->name;
                // when importing, we use the target edit
                $text = ($this->editorMode) ? $this->segment->get($editIndex) : $this->segment->getTargetEdit();
                $target = new editor_Segment_FieldTags($this->segmentId, $text, $saveTo, $ttField);
                $this->targets[] = $target;
            }
        }
    }
    /**
     * Saves all fields back to the segment when the import is finished or when editing segments
     * @param boolean $flushQualities: whem set to true, the segment-qualities will be saved as well
     */
    public function flush($saveQualities=true){

        if($this->hasOriginalSource()){
             // we do know that the original source just has a single save-to field
             $this->getSegment()->set($this->sourceOriginal->getFirstSaveToField(), $this->sourceOriginal->render());
        }
        // save source
        foreach($this->source->getSaveToFields() as $saveTo){
            $this->getSegment()->set($saveTo, $this->source->render());
        }
        foreach($this->targets as $target){
            /* @var $target editor_Segment_FieldTags */
            foreach($target->getSaveToFields() as $saveTo){
                $this->getSegment()->set($saveTo, $target->render());
            }
        }
        $this->getSegment()->save();
        // save qualities if wanted
        if($saveQualities){
            $this->saveQualities();
        }
    }
    /**
     * Saves the current state to the segment-tags cache. This API is used while the threaded import
     * @param string $providerKey
     */
    public function save(string $providerKey){
        $this->getSegment()->saveSegmentTagsJSON($this->toJson(), $providerKey);
    }
    /**
     * 
     * @return boolean
     */
    public function isEditor(){
        return $this->editorMode;
    }
    /**
     * 
     * @return boolean
     */
    public function isSourceEditingEnabled(){
        return $this->sourceEditingEnabled;
    }
    /**
     *
     * @return boolean
     */
    public function hasOriginalSource(){
        return ($this->sourceOriginal != null);
    }
    /**
     * 
     * @return editor_Segment_FieldTags
     */
    public function getSource(){
        return $this->source;
    }
    /**
     * 
     * @return editor_Segment_FieldTags
     */
    public function getOriginalSource(){
        return $this->sourceOriginal;
    }
    /**
     * 
     * @return editor_Segment_FieldTags[]
     */
    public function getTargets(){
        return $this->targets;
    }
    /**
     * 
     * @return editor_Segment_FieldTags[]
     */
    private function getFieldTags(){
        $tags = $this->getTargets();
        if($this->source != null){
            array_unshift($tags, $this->source);
        }
        if($this->sourceEditingEnabled && $this->sourceOriginal != null){
            array_unshift($tags, $this->sourceOriginal);
        }
        return $tags;
    }
    /**
     * 
     * @return int
     */
    public function getSegmentId(){
        return $this->segmentId;
    }
    /**
     * 
     * @return editor_Models_Segment
     */
    public function getSegment(){
        if($this->segment == null){
            $this->segment = ZfExtended_Factory::get('editor_Models_Segment');
            /* @var $segment editor_Models_Segment */
            $this->segment->load($this->segmentId);
        }
        return $this->segment;
    }
    
    /* Internal Tags API */
    
    /**
     * 
     * @param string $type
     * @return bool
     */
    public function hasInternalTagsOfType(string $type) : bool {
        foreach($this->getFieldTags() as $fieldTags){
            if($fieldTags->hasType($type)){
                return true;
            }
        }
        return false;
    }
    /**
     * Removes the tags of the passed type in all our FieldTags
     * @param string $type
     */
    public function removeInternalTagsByType(string $type){
        foreach($this->getFieldTags() as $fieldTags){
            $fieldTags->removeByType($type);
        }
    }
    /**
     * Retrieves all tags from all our field tags
     * @param string $type
     * @return editor_Segment_InternalTag[]
     */
    public function getInternalTagsByType(string $type){
        $result = [];
        foreach($this->getFieldTags() as $fieldTags){
            $result = array_merge($result, $fieldTags->getByType($type));
        }
        return $result;
    }
    
    /* SegmentQuality API */
    
    /**
     * Adds a quality to the tags (segment-qulity model)
     * Note, that the qualities will be saved seperately from the tags-model and is NOT serialized
     * This also means, that during the import-process, the quality-entries will be written before the tags are written AFTER the import
     * @param string[]|string $fields
     * @param string $type
     * @param string $msgkey
     * @param string $category
     * @param int $startIndex
     * @param int $endIndex
     */
    public function addQuality($fields, string $type, string $msgkey, string $category=NULL, int $startIndex=0, int $endIndex=-1) {
        $row = $this->getQualityTable()->createRow();
        /* @var $row editor_Models_Db_SegmentQualityRow */
        $row->segmentId = $this->segmentId;
        $row->taskGuid = $this->task->getTaskGuid();
        $row->setFields($fields);
        $row->type = $type;
        $row->msgkey = $msgkey;
        $row->category = $category;
        $row->startIndex = $startIndex;
        $row->endIndex = $endIndex;
        $this->qualities[] = $row;
    }
    /**
     * 
     * @return editor_Models_Db_SegmentQualityRow[]
     */
    public function getQualities(){
        return $this->qualities;
    }
    /**
     * Saves all set qualities to the database after deleting the current ones
     */
    public function saveQualities(){
        editor_Models_Db_SegmentQuality::deleteForSegment($this->segmentId);
        if(count($this->qualities) > 0){
            editor_Models_Db_SegmentQuality::saveRows($this->qualities);
            $this->qualities = [];
        }
    }
    
    /* Serialization API */
    
    /**
     *
     * @return string
     */
    public function toJson(){
        return json_encode($this->jsonSerialize(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    /**
     * 
     * {@inheritDoc}
     * @see JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize(){
        $data = new stdClass(); 
        $data->taskGuid = $this->task->getTaskGuid();
        $data->segmentId = $this->segmentId;
        $data->targets = [];
        foreach($this->targets as $tag){
            $data->targets[] = $tag->jsonSerialize();
        }
        $data->source = $this->source->jsonSerialize();
        $data->sourceOriginal = ($this->sourceOriginal == NULL) ? false : $this->sourceOriginal->jsonSerialize();
        $data->editorMode = $this->editorMode;
        $data->sourceEditingEnabled = $this->sourceEditingEnabled;
        return $data;
    }
    /**
     * 
     * @param stdClass $data
     * @throws Exception
     */
    private function initFromJson(stdClass $data){
        try {
            $this->segmentId = $data->segmentId;
            $this->sourceEditingEnabled = $data->sourceEditingEnabled;
            $this->source = editor_Segment_FieldTags::fromJsonData($data->source);
            $this->targets = [];
            foreach($data->targets as $targetData){
                $this->targets[] = editor_Segment_FieldTags::fromJsonData($targetData);
            }
            if($this->editorMode && $this->sourceEditingEnabled){
                $this->sourceOriginal = editor_Segment_FieldTags::fromJsonData($data->sourceOriginal);
            }
        } catch (Exception $e) {
            throw new Exception('Deserialization of editor_Segment_Tags from JSON-Object failed because of invalid data: '.json_encode($data));
        }
    }
    /**
     * 
     * @return editor_Models_Db_SegmentQuality
     */
    private function getQualityTable(){
        if($this->qualityTable == NULL){
            $this->qualityTable = ZfExtended_Factory::get('editor_Models_Db_SegmentQuality');
        }
        return $this->qualityTable;
    }
}
