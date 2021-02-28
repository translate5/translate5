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
     * A read-only field that is only used as reference for some QA tests
     * @var editor_Segment_FieldTags[]
     */
    private $targetOriginal = null;
    /**
     *
     * @var int
     */
    private $targetOriginalIdx = -1;
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
        $sourceEditingEnabled = $this->task->getEnableSourceEditing();
        $sourceField = $fieldManager->getFirstSourceName();
        $sourceFieldEditIndex = $fieldManager->getEditIndex($sourceField);
        // in case of an editing process the original source will be handled seperately
        // if we are an import, the original source and source will be handled identically - the "normal"  source is the edited source then (exception: the post-import adding of terms via "Analysis" where the source & edited source might already differ)
        // TODO: this assumes, that these fields are already copied at this point of the import
        $hasOriginalSource = ($sourceEditingEnabled) ? ($this->editorMode || $this->segment->get($sourceFieldEditIndex) != $this->segment->get($sourceField)) : false;
        if($hasOriginalSource){
            // original source (what is the source in all other cases)
            $this->sourceOriginal = new editor_Segment_FieldTags($this->segmentId, $sourceField, $this->segment->get($sourceField), $sourceField, 'SourceOriginal');
            // source here is the editable source
            $this->source = new editor_Segment_FieldTags($this->segmentId, $sourceField, $this->segment->get($sourceFieldEditIndex), $sourceFieldEditIndex, $sourceField);
        } else {
            // on import with enabled source editing, we copy the source as editedSource as well
            $saveTo = ($sourceEditingEnabled) ? [$sourceField, $sourceFieldEditIndex] : $sourceField;
            $this->source = new editor_Segment_FieldTags($this->segmentId, $sourceField, $this->segment->get($sourceField), $saveTo, $sourceField);
        }
        $this->targets = [];
        foreach ($fieldManager->getFieldList() as $field) {
            /* @var $field Zend_Db_Table_Row */
            if($field->type == editor_Models_SegmentField::TYPE_TARGET && $field->editable) {
                $editIndex = $fieldManager->getEditIndex($field->name);
                // special when we have an import but the fields are different this might is 
                if(!$this->editorMode && $this->segment->get($field->name) != $this->segment->get($editIndex)){
                    $target = new editor_Segment_FieldTags($this->segmentId, $field->name, $this->segment->get($field->name), $field->name, $field->name);
                    $this->targets[] = $target;
                    if($this->targetOriginal == null){
                        $this->targetOriginal = $target;
                        $this->targetOriginalIdx = count($this->targets);
                    }
                    $target = new editor_Segment_FieldTags($this->segmentId, $field->name, $this->segment->get($editIndex), $editIndex, $editIndex);
                    $this->targets[] = $target;
                } else {
                    // when importing, the field will be saved as edit field & as normal field
                    $saveTo = ($this->editorMode) ? $editIndex : [$field->name, $editIndex];
                    // the field name sent to the termtagger differs between import and editing (WHY?)
                    $ttField = ($this->editorMode) ? $editIndex : $field->name;
                    $target = new editor_Segment_FieldTags($this->segmentId, $field->name, $this->segment->get($editIndex), $saveTo, $ttField);
                    $this->targets[] = $target;
                    // the first target will be the original target as needed for some Quality checks
                    if($this->targetOriginal == null){
                        $this->targetOriginal = new editor_Segment_FieldTags($this->segmentId, $field->name, $this->segment->get($field->name), $field->name, $field->name);
                    }
                }
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
    public function hasOriginalSource(){
        return ($this->sourceOriginal != null);
    }
    /**
     *
     * @return boolean
     */
    public function hasOriginalTarget(){
        return ($this->targetOriginal != null);
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
     * Retrieves the original source in case of an editable source or the source otherwise
     * @return editor_Segment_FieldTags
     */
    public function getOriginalOrNormalSource(){
        if($this->hasOriginalSource()){
            return $this->sourceOriginal;
        }
        return $this->source;
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
     * @return editor_Segment_FieldTags
     */
    public function getOriginalTarget(){
        return $this->targetOriginal;
    }
    /**
     *
     * @return editor_Segment_FieldTags|NULL
     */
    public function getFirstTarget(){
        if(count($this->targets) > 0){
            return $this->targets[0];
        }
        return NULL;
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
        if($this->sourceOriginal != null){
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
     * Checks if any fields have one or more tags of the given type
     * @param string $type
     * @return bool
     */
    public function hasTagsOfType(string $type) : bool {
        foreach($this->getFieldTags() as $fieldTags){
            if($fieldTags->hasType($type)){
                return true;
            }
        }
        return false;
    }
    /**
     * Checks if any fields have one or more tags of the given type and classname
     * @param string $type
     * @param string $className
     * @return bool
     */
    public function hasTagsOfTypeAndClass(string $type, string $className) : bool {
        foreach($this->getFieldTags() as $fieldTags){
            if($fieldTags->hasTypeAndClass($type, $className)){
                return true;
            }
        }
        return false;
    }
    /**
     * Retrieves the field names of any fields that have one or more tags of the given type and classname
     * @param string $type
     * @param string $className
     * @return string[]
     */
    public function getFieldsByTagsTypeAndClass(string $type, string $className) : array {
        $fields = [];
        foreach($this->getFieldTags() as $fieldTags){
            if($fieldTags->hasTypeAndClass($type, $className)){
                $fields[] = $fieldTags->getField();
            }
        }
        return array_unique($fields);
    }
    /**
     * Removes the tags of the passed type in all our FieldTags
     * @param string $type
     */
    public function removeTagsByType(string $type){
        foreach($this->getFieldTags() as $fieldTags){
            $fieldTags->removeByType($type);
        }
    }
    /**
     * Retrieves all tags from all our field tags
     * @param string $type
     * @return editor_Segment_Tag[]
     */
    public function getTagsByType(string $type){
        $result = [];
        foreach($this->getFieldTags() as $fieldTags){
            $result = array_merge($result, $fieldTags->getByType($type));
        }
        return $result;
    }
    
    /* SegmentQuality API */
    
    /**
     * Adds a general quality to the tags (segment-quality model)
     * Note, that the qualities will be saved seperately from the tags-model and is NOT serialized
     * This also means, that during the import-process, the quality-entries will be written before the tags are written AFTER the import
     * @param string[]|string $fields
     * @param string $type
     * @param string $category
     * @param int $startIndex
     * @param int $endIndex
     */
    public function addQuality($fields, string $type, string $category, int $startIndex=0, int $endIndex=-1) {
        $row = $this->getQualityTable()->createRow();
        /* @var $row editor_Models_Db_SegmentQualityRow */
        $row->segmentId = $this->segmentId;
        $row->taskGuid = $this->task->getTaskGuid();
        if(is_array($fields)){
            $row->setFields(array_unique($fields));
        } else {
            $row->setField($fields);
        }
        $row->type = $type;
        $row->category = $category;
        $row->startIndex = $startIndex;
        $row->endIndex = $endIndex;
        $row->falsePositive = 0; // TODO AUTOQA: this means, when we re-set qualities a former existing false positive flag will not persist
        $row->mqmType = -1;
        $row->severity = NULL;
        $row->comment = NULL;
        $this->qualities[] = $row;
    }
    /**
     * Adds a quality to the tags (segment-quality model) for all target fields
     * @param string $type
     * @param string $category
     */
    public function addAllTargetsQuality(string $type, string $category) {
        $this->addQuality($this->getAllTargetFields(), $type, $category);
    }
    /**
     * Adds a MQM Quality to the tags (segment-quality model)
     * @param string $field
     * @param int $typeIndex
     * @param string $severity
     * @param string $comment
     * @param int $startIndex
     * @param int $endIndex
     */
    public function addManualQuality(string $field, int $typeIndex, string $severity, string $comment, int $startIndex=0, int $endIndex=-1) {
        $row = $this->getQualityTable()->createRow();
        /* @var $row editor_Models_Db_SegmentQualityRow */
        $row->segmentId = $this->segmentId;
        $row->taskGuid = $this->task->getTaskGuid();
        $row->setField($field);
        $row->type = editor_Segment_Tag::TYPE_MANUALQUALITY;
        $row->category = NULL;
        $row->startIndex = $startIndex;
        $row->endIndex = $endIndex;
        $row->falsePositive = 0; // TODO AUTOQA: this means, when we re-set qualities a former existing false positive flag will not persist
        $row->mqmType = $typeIndex;
        $row->severity = $severity;
        $row->comment = $comment;
        $this->qualities[] = $row;
    }
    /**
     * Returnes the names of all our target fields
     * @return array
     */
    private function getAllTargetFields(){
        $fields = array();
        foreach($this->getTargets() as $target){
            $fields[] = $target->getField();
        }
        return array_unique($fields);
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
        editor_Models_Db_SegmentQuality::saveRows($this->qualities);
        $this->qualities = [];
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
        if($this->targetOriginalIdx > -1){
            $data->targetOriginalIdx = $this->targetOriginalIdx;
        } else if($this->targetOriginal != NULL) {
            $data->targetOriginal = $this->targetOriginal->jsonSerialize();
        }
        $data->editorMode = $this->editorMode;
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
            $this->source = editor_Segment_FieldTags::fromJsonData($data->source);
            $this->targets = [];
            foreach($data->targets as $targetData){
                $this->targets[] = editor_Segment_FieldTags::fromJsonData($targetData);
            }
            if($this->editorMode && $this->task->getEnableSourceEditing() && property_exists($data, 'sourceOriginal')){
                $this->sourceOriginal = editor_Segment_FieldTags::fromJsonData($data->sourceOriginal);
            }
            if(property_exists($data, 'targetOriginalIdx')){
                $this->targetOriginalIdx = $data->targetOriginalIdx;
                $this->targetOriginal = $this->targets[$this->targetOriginalIdx];
            } else if(property_exists($data, 'targetOriginal')){
                $this->targetOriginal = editor_Segment_FieldTags::fromJsonData($data->targetOriginal);
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
    /**
     * Debug output
     * @return string
     */
    public function debug(){
        $debug = '';
        if($this->source != NULL){
            $debug .= 'SOURCE: '.htmlspecialchars($this->source->render())."\n";
        }
        if($this->sourceOriginal != NULL){
            $debug .= 'SOURCE ORIGINAL: '.htmlspecialchars($this->sourceOriginal->render())."\n";
        }
        for($i=0; $i < count($this->targets); $i++){
            $debug .= 'TARGET '.$i.': '.htmlspecialchars($this->targets[$i]->render())."\n";
        }
        return $debug;
    }
}
