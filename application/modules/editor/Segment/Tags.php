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
     * The counterpart to ::toJson: creates the tags from the serialized json data
     * @param string $jsonString
     * @throws Exception
     * @return editor_Segment_Tags
     */
    public static function fromJson(editor_Models_Task $task, bool $editorMode, string $jsonString) : editor_Segment_Tags {
        try {
            $data = json_decode($jsonString);
            if($data->taskGuid != $task->getTaskGuid()){
                throw new Exception('Deserialization of editor_Segment_Tags from JSON-Object failed because of task-guid mismatch: '.json_encode($data));
            }
            $tags = new editor_Segment_Tags($task, $editorMode, null, null, $data);
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
     * @param editor_Models_Task $task
     * @param bool $isEditor
     */
    public function __construct(editor_Models_Task $task, bool $isEditor, editor_Models_Segment $segment=NULL, editor_Models_SegmentFieldManager $fieldManager=NULL, stdClass $serializedData=NULL) {
        $this->task = $task;
        $this->editorMode = $isEditor;
        if($serializedData != NULL){            
            $this->initFromJson($serializedData);
        } else if($segment != NULL && $fieldManager != NULL){
            $this->segment = $segment;
            $this->segmentId = $segment->getId();
            $this->init($fieldManager);
        } else {
            throw new Exception('editor_Segment_Tags needs either a segment-instance with field manager or serialized data for instantiation');
        }
    }
    /** 
     * Initializes from scratch (used in the initial quality worker), creates the inital data structure
     * @param editor_Models_SegmentFieldManager $fieldManager
     */ 
    private function init(editor_Models_SegmentFieldManager $fieldManager){
        $this->sourceEditingEnabled = $this->task->getEnableSourceEditing();

        $sourceField = $fieldManager->getFirstSourceName();
        $sourceFieldEditIndex = $fieldManager->getEditIndex($sourceField);
        // in case of an editing process the original source will be handled seperately
        // if we are an import, the original source and source will be handled identically - the "normal"  source is the edited source then
        if($this->editorMode && $this->sourceEditingEnabled){
            // original source (what is the source in all other cases)
            $this->sourceOriginal = new editor_Segment_FieldTags($this->segmentId, $this->segment->get($sourceField), $sourceField, $sourceField);
            // source here is the editable source
            $this->source = new editor_Segment_FieldTags($this->segmentId, $this->segment->get($sourceFieldEditIndex), $sourceFieldEditIndex, $sourceFieldEditIndex);
        } else {
            $this->source = new editor_Segment_FieldTags($this->segmentId, $this->segment->get($sourceField), $sourceField, $sourceFieldEditIndex);
        }
        $this->targets = [];
        foreach ($fieldManager->getFieldList() as $field) {
            /* @var $field Zend_Db_Table_Row */
            if($field->type == editor_Models_SegmentField::TYPE_TARGET && $field->editable) {
                // for unknown reasos, the termtagger uses different field-names on import then when editing
                $to = $fieldManager->getEditIndex($field->name);
                $from = ($this->editorMode) ? $to : $field->name;
                $text = ($this->editorMode) ? $this->segment->get($to) : $this->segment->getTargetEdit();
                $target = new editor_Segment_FieldTags($this->segmentId, $text, $to, $from);
                $this->targets[] = $target;
            }
        }
    }
    /**
     * Saves the current state to the database during the import
     */
    public function save(){
        $this->getSegment()->setSegmentTagsJSON($this->toJson());
    }
    /**
     * Saves all fields back to the segment when the 
     */
    public function flush(){
        if($this->sourceEditingEnabled){
            if($this->editorMode){
                $this->getSegment()->set($this->sourceOriginal->getFieldTo(), $this->sourceOriginal->render());
            } else {
                // Ugly Trick: we save the edit-index in the TO-field
                $this->getSegment()->set($this->source->getFieldTo(), $this->source->render());
            }
        }
        // save source, we save always to the "from" field as the "to" field is maybe used to save the edit-index when importing
        $this->getSegment()->set($this->source->getFieldFrom(), $this->source->render());
        // save targets
        foreach($this->targets as $target){
            /* @var $target editor_Segment_FieldTags */
            if($this->editorMode){
                $this->getSegment()->set($target->getFieldTo(), $target->render());
            } else {
                // when importing, we write to the target as well (encoded in the from field)
                $renderedTarget = $target->render();
                $this->getSegment()->set($target->getFieldFrom(), $renderedTarget);
                $this->getSegment()->set($target->getFieldTo(), $renderedTarget);
            }
        }
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
    public function getFieldTags(){
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
}
