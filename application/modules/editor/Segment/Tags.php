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
     * @var editor_Segment_FieldTags[]
     */
    private $targets;
    /**
     *
     * @var editor_Segment_FieldTags
     */
    private $source;
    /**
     *
     * @var editor_Segment_FieldTags
     */
    private $sourceOriginal = NULL;
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
     * @var string[]
     */
    private $fields;
    /**
     *
     * @var int
     */
    private $segmentId;
    /**
     *
     * @var editor_Models_Segment
     */
    public $segment;
    
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
        } else if($segment != NULL){
            $this->segment = $segment;
            $this->segmentId = $segment->getId();
            if(empty($fieldManager)){
                $fieldManager = editor_Models_SegmentFieldManager::getForTaskGuid($this->task->getTaskGuid());
            }
            $this->init($fieldManager);
        } else {
            throw new Exception('editor_Segment_Tags needs either a segment-instance or serialized data for instantiation');
        }
    }
    /**
     * 
     * @param editor_Models_SegmentFieldManager $fieldManager
     */
    private function init(editor_Models_SegmentFieldManager $fieldManager){
        $this->sourceEditingEnabled = ($this->editorMode && $this->task->getEnableSourceEditing());
        $this->fields = [];

    }
    
    private function __tmp(){
        
        // fetch editor & import
        $fieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        /* @var $fieldManager editor_Models_SegmentFieldManager */
        $fieldManager->initFields($this->task->getTaskGuid());
        $segmentFields = $fieldManager->getFieldList();
        $enableSourceEditing = ($isEditor && $this->task->getEnableSourceEditing());
        
        if($enableSourceEditing){
            $service->sourceFieldName = $fieldManager->getEditIndex($fieldManager->getFirstSourceName());
            $service->sourceFieldNameOriginal = $fieldManager->getFirstSourceName();
        } else {
            $service->sourceFieldName = $fieldManager->getFirstSourceName();
        }
        foreach ($segments as $segment) { /* @var $segment editor_Models_Segment */
            
            $sourceText = $segment->get($service->sourceFieldName);
            $sourceTextOriginal = ($enableSourceEditing) ? $segment->get($service->sourceFieldNameOriginal) : $sourceText;
            $firstField = true;
            
            foreach ($segmentFields as $field) {
                if($field->type != editor_Models_SegmentField::TYPE_TARGET || !$field->editable) {
                    continue;
                }
                $targetFieldName = ($isEditor) ? $fieldManager->getEditIndex($field->name) : $field->name;
                if ($enableSourceEditing && $firstField) {
                    $service->addSegment($segment->getId(), 'SourceOriginal', $sourceTextOriginal, $segment->get($targetFieldName));
                    $firstField = false;
                }
                if($isEditor){
                    $targetText = $segment->get($targetFieldName);
                } else {
                    $targetText = $segment->getTargetEdit();
                }
                $service->addSegment($segment->getId(), $targetFieldName, $sourceText, $targetText);
            }
        }
        // save editor
        foreach ($results as $result){
            if($result->field == 'SourceOriginal') {
                $segment->set($communicationService->sourceFieldNameOriginal, $result->source);
                continue;
            }
            if(!$sourceTextTagged){
                $segment->set($communicationService->sourceFieldName, $result->source);
                $sourceTextTagged = true;
            }
            $segment->set($result->field, $result->target);
        }
        // save import
        foreach ($responses as $segmentId => $responseGroup) {
            $segment->load($segmentId);
            
            $segment->set($sourceFieldName, $responseGroup[0]->source);
            if ($this->task->getEnableSourceEditing()) {
                $segment->set($fieldManager->getEditIndex($sourceFieldName), $responseGroup[0]->source);
            }
            foreach ($responseGroup as $response) {
                $segment->set($response->field, $response->target);
                $segment->set($fieldManager->getEditIndex($response->field), $response->target);
            }
            $segment->save();
            $segment->meta()->setTermtagState(editor_Plugins_TermTagger_Configuration::SEGMENT_STATE_TAGGED);
            $segment->meta()->save();
        }
    }
    private function __init(editor_Models_SegmentFieldManager $fieldManager){
        $this->sourceEditingEnabled = ($this->editorMode && $this->task->getEnableSourceEditing());
        // evaluate the fields we have to deal with
        if($this->sourceEditingEnabled){
            $this->sourceFieldName = $fieldManager->getEditIndex($fieldManager->getFirstSourceName());
            $this->sourceFieldNameOriginal = $fieldManager->getFirstSourceName();
        } else {
            $this->sourceFieldName = $fieldManager->getFirstSourceName();
        }
        $segmentFields = $fieldManager->getFieldList();
        foreach ($segmentFields as $field) {
            if($field->type != editor_Models_SegmentField::TYPE_TARGET || !$field->editable) {
                continue;
            }
            $targetFieldName = ($this->editorMode) ? $fieldManager->getEditIndex($field->name) : $field->name;
            if ($enableSourceEditing && $firstField) {
                $service->addSegment($segment->getId(), 'SourceOriginal', $sourceTextOriginal, $segment->get($targetFieldName));
                $firstField = false;
            }
            if($isEditor){
                $targetText = $segment->get($targetFieldName);
            } else {
                $targetText = $segment->getTargetEdit();
            }
            $service->addSegment($segment->getId(), $targetFieldName, $sourceText, $targetText);
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
        $data->fields = $this->fields;
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
            $this->sourceEditingEnabled = ($this->editorMode && $data->sourceEditingEnabled);
            $this->source = editor_Segment_FieldTags::fromJsonData($data->source);
            $this->targets = [];
            foreach($data->targets as $targetData){
                $this->targets[] = editor_Segment_FieldTags::fromJsonData($targetData);
            }
            if($this->sourceEditingEnabled && property_exists($data, 'sourceOriginal')){
                $this->sourceOriginal = editor_Segment_FieldTags::fromJsonData($data->sourceOriginal);
            }
            $this->fields = $data->fields;
        } catch (Exception $e) {
            throw new Exception('Deserialization of editor_Segment_Tags from JSON-Object failed because of invalid data: '.json_encode($data));
        }
    }
}
