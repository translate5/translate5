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

use MittagQI\Translate5\Segment\Processing\State;
/**
 * Abstraction to bundle the segment's internal tags per field to have a model to be passed across the quality providers
 *
 * TODO: The FieldTags are created with the additional params $additionalSaveTo and $termTaggerName: This is somehow dirty and should be avoided by enhancing Logic here and in the Termtagger
 */
final class editor_Segment_Tags implements JsonSerializable {

    /**
     * Creates segment-tags from a segment.
     * When the tags-model is saved, it will be written to the segment directly
     *
     * @param editor_Models_Task $task
     * @param string $processingMode
     * @param editor_Models_Segment $segment
     * @return editor_Segment_Tags
     * @throws Exception
     */
    public static function fromSegment(editor_Models_Task $task, string $processingMode, editor_Models_Segment $segment) : editor_Segment_Tags {
        return new editor_Segment_Tags($task, $processingMode, $segment);
    }

    /**
     * Instantiates a tags-model for use in a Segment Processor/Looper/Worker
     * If there is no json data in the State model, it will be fetched from the segment instead
     * The prosessed data will be saved to the State, not the segment !
     * @param editor_Models_Task $task
     * @param string $processingMode
     * @param State $tagsState
     * @return editor_Segment_Tags
     * @throws Exception
     */
    public static function fromState(editor_Models_Task $task, string $processingMode, State $tagsState) : editor_Segment_Tags {
        if($tagsState->hasTagsJson()){
            return self::fromJson($task, $processingMode, $tagsState->getTagsJson(), $tagsState);
        }
        return new editor_Segment_Tags($task, $processingMode, $tagsState->getSegment(), null, $tagsState);
    }

    /**
     * Creates the segment-tags from JSON
     * @param editor_Models_Task $task
     * @param string $processingMode
     * @param string $jsonString
     * @param State|null $tagsState
     * @return editor_Segment_Tags
     * @throws Exception
     */
    public static function fromJson(editor_Models_Task $task, string $processingMode, string $jsonString, State $tagsState = null) : editor_Segment_Tags {
        $data = json_decode($jsonString);
        if(empty($data)){
            throw new Exception('Deserialization of editor_Segment_Tags from JSON-Object failed, invalid JSON string.');
        }
        if($data->taskGuid != $task->getTaskGuid()){
            throw new Exception('Deserialization of editor_Segment_Tags from JSON-Object failed because of task-guid mismatch: '.$tagsState->getTagsJson());
        }
        return new editor_Segment_Tags($task, $processingMode, null, $data, $tagsState);
    }

    /**
     *
     * @var editor_Segment_FieldTags|null
     */
    private ?editor_Segment_FieldTags $sourceOriginal = null;
    /**
     *
     * @var editor_Segment_FieldTags|null
     */
    private ?editor_Segment_FieldTags $source = null;
    /**
     *
     * @var editor_Segment_FieldTags[]
     */
    private array $targets;
    /**
     * A read-only field that is only used as reference for some QA tests
     * @var editor_Segment_FieldTags|null
     */
    private ?editor_Segment_FieldTags $targetOriginal = null;
    /**
     *
     * @var int
     */
    private int $targetOriginalIdx = -1;
    /**
     *
     * @var editor_Models_Task
     */
    private editor_Models_Task $task;
    /**
     * see modes in editor_Segment_Processing
     * @var string
     */
    private string $processingMode;
    /**
     *
     * @var bool
     */
    private bool $isImport;
    /**
     *
     * @var int
     */
    private int $segmentId;
    /**
     *
     * @var editor_Models_Segment|null
     */
    private ?editor_Models_Segment $segment = null;
    /**
     *
     * @var editor_Segment_Qualities|null
     */
    private ?editor_Segment_Qualities $qualities = null;
    /**
     *
     * @var State|null
     */
    private ?State $processingState = null;

    /**
     * @param editor_Models_Task $task
     * @param string $processingMode
     * @param editor_Models_Segment|null $segment
     * @param stdClass|null $serializedData
     * @param State|null $tagsState
     * @throws Exception
     */
    public function __construct(editor_Models_Task $task, string $processingMode, editor_Models_Segment $segment = null, stdClass $serializedData = null, State $tagsState = null) {
        $this->task = $task;
        $this->processingMode = $processingMode;
        $this->isImport = ($processingMode == editor_Segment_Processing::IMPORT);
        $this->segment = $segment;
        $this->processingState = $tagsState;
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
     * TODO: the ugly ttName-logic should be removed and instead add prop for the originationg field of the field-text should be added !!
     */
    private function init(){
        $fieldManager = editor_Models_SegmentFieldManager::getForTaskGuid($this->task->getTaskGuid());
        $sourceEditingEnabled = $this->task->getEnableSourceEditing();
        $sourceField = $fieldManager->getFirstSourceName();
        $sourceFieldEditIndex = $fieldManager->getEditIndex($sourceField);
        // in case of an editing process the original source will be handled seperately
        // if we are an import, the original source and source will be handled identically - the "normal"  source is the edited source then (exception: the post-import adding of terms via "Analysis" where the source & edited source might already differ)
        // TODO: this assumes, that these fields are already copied at this point of the import
        $hasOriginalSource = ($sourceEditingEnabled) ? (!$this->isImport || $this->segment->get($sourceFieldEditIndex) != $this->segment->get($sourceField)) : false;
        if($hasOriginalSource){
            // original source (what is the source in all other cases)
            $this->sourceOriginal = new editor_Segment_FieldTags($this->task, $this->segmentId, $this->segment->get($sourceField), $sourceField, $sourceField, NULL, 'SourceOriginal');
            // source here is the editable source
            $this->source = new editor_Segment_FieldTags($this->task, $this->segmentId, $this->segment->get($sourceFieldEditIndex), $sourceField, $sourceFieldEditIndex);
        } else {
            // on import with enabled source editing, we copy the source as editedSource as well
            $additionalSaveTo = ($sourceEditingEnabled) ? $sourceFieldEditIndex : NULL;
            $this->source = new editor_Segment_FieldTags($this->task, $this->segmentId, $this->segment->get($sourceField), $sourceField, $sourceField, $additionalSaveTo);
        }
        $this->targets = [];

        if($this->processingMode == editor_Segment_Processing::ALIKE){

            // in a Alike copying process, only the first target will be processed.
            // TODO: this is not compliant with the multitarget tasks but can only be changed when the code in the AlikesegmentConroller is multifield capable
            $firstTarget = $fieldManager->getFirstTargetName();
            $editIndex = $fieldManager->getEditIndex($firstTarget);
            $target = new editor_Segment_FieldTags($this->task, $this->segmentId, $this->segment->get($editIndex), $firstTarget, $editIndex);
            $this->targets[] = $target;
            $this->targetOriginal = $target;
            $this->targetOriginalIdx = 0;

        } else {

            foreach ($fieldManager->getFieldList() as $field) {
                /* @var $field Zend_Db_Table_Row */
                if($field->type == editor_Models_SegmentField::TYPE_TARGET && $field->editable) {
                    $editIndex = $fieldManager->getEditIndex($field->name);
                    // special when we have an import but the fields are different this might is
                    if($this->isImport && $this->segment->get($field->name) != $this->segment->get($editIndex)){
                        $target = new editor_Segment_FieldTags($this->task, $this->segmentId, $this->segment->get($field->name), $field->name, $field->name);
                        $this->targets[] = $target;
                        if($this->targetOriginal == null){
                            $this->targetOriginal = $target;
                            $this->targetOriginalIdx = count($this->targets);
                        }
                        $this->targets[] = new editor_Segment_FieldTags($this->task, $this->segmentId, $this->segment->get($editIndex), $field->name, $editIndex);
                    } else {
                        // when importing, the field will be saved as edit field & as normal field
                        $additionalSaveTo = ($this->isImport) ? $field->name : NULL;
                        // the field name sent to the termtagger differs between import and editing (WHY?)
                        $ttField = ($this->isImport) ? $field->name : $editIndex;
                        $this->targets[] = new editor_Segment_FieldTags($this->task, $this->segmentId, $this->segment->get($editIndex), $field->name, $editIndex, $additionalSaveTo, $ttField);
                        // the first target will be the original target as needed for some Quality checks
                        if($this->targetOriginal == null){
                            $this->targetOriginal = new editor_Segment_FieldTags($this->task, $this->segmentId, $this->segment->get($field->name), $field->name, $field->name);
                        }
                    }
                }
            }
        }
    }

    /**
     * Saves the current state to the segment-tags cache. This API is used while the threaded import
     * @param bool $saveQualities: for special cases we may not want the saving of qualities
     * @param bool $saveSegmentContent: for special cases we may not want the saving se segment-content, either to the segment or the processing-model during operations
     */
    public function save(bool $saveQualities = true, bool $saveSegmentContent = true)
    {
        // is the current processing saving directly back to the segment or the processing/state model ?
        if ($this->isStateProcessing()) {
            // we save to the temporary tags-model & set the processing-state to PROCESSED
            if($saveSegmentContent){
                $this->processingState->saveTagsJson($this->toJson());
            } else {
                $this->processingState->setProcessed();
            }
        } else {
            // saving to the segment-table directly
            if($saveSegmentContent){
                $this->saveToSegment();
            }
        }
        if($saveQualities){
            $this->getQualities()->save();
        }
    }

    /**
     * Saves all fields back to the segment after an operation has finished or when processing segments directly (edit, alike)
     * When using this, do not forget to handle the qualities seperately !
     */
    public function saveToSegment()
    {
        if ($this->hasOriginalSource()) {
            // we do know that the original source just has a single save-to field
            $this->getSegment()->set($this->sourceOriginal->getDataField(), $this->sourceOriginal->render());
        }
        // save source
        if ($this->hasSource()) {
            foreach ($this->source->getSaveToFields() as $saveTo) {
                $this->getSegment()->set($saveTo, $this->source->render());
            }
        }
        foreach ($this->targets as $target) {
            foreach ($target->getSaveToFields() as $saveTo) {
                $this->getSegment()->set($saveTo, $target->render());
            }
        }
        $this->getSegment()->save();
    }

    /**
     * Retrieves, if the segment shall be saved to the segment table or to the processing model when calling save()
     * @return bool
     */
    public function isStateProcessing(): bool
    {
        return ($this->processingState !== null && editor_Segment_Processing::isStateProcessing($this->processingMode));
    }

    /**
     *
     * @return string
     */
    public function getProcessingMode() : string {
        return $this->processingMode;
    }
    /**
     *
     * @return boolean
     */
    public function hasSource() : bool {
        return ($this->source != null);
    }
    /**
     *
     * @return boolean
     */
    public function hasOriginalSource() : bool {
        return ($this->sourceOriginal != null);
    }
    /**
     *
     * @return boolean
     */
    public function hasOriginalTarget() : bool {
        return ($this->targetOriginal != null);
    }
    /**
     * Retrieves, if the contents of any targets have been changed compared to the original target.
     * Empty targets will be ignored and trackchanges tags will be stripped. This means, that contents reverted to the original state by the editor will be seen as "unchanged"
     * @return bool
     */
    public function hasEditedTargets() : bool {
        return count($this->getEditedTargetFields()) > 0;
    }
    /**
     * Retrieves the target fields that are edited compared to the original target
     * Empty targets will be ignored and trackchanges tags will be stripped. This means, that contents reverted to the original state by the editor will be seen as "unchanged"
     * @return array
     */
    public function getEditedTargetFields() : array {
        return $this->getChangedTargetFields(true);
    }
    /**
     * Retrieves the target fields that are NOT edited compared to the original target
     * Empty targets will be ignored and trackchanges tags will be stripped. This means, that contents reverted to the original state by the editor will be seen as "unchanged"
     * @return array
     */
    public function getUneditedTargetFields() : array {
        return $this->getChangedTargetFields(false);
    }
    /**
     * Retrieves all targets that have been changed, either edited or not edited
     * @param boolean $edited
     * @return array
     */
    private function getChangedTargetFields(bool $edited) : array {
        $changedTargets = [];
        if($this->hasOriginalTarget()){
            // only internal tags will be allowed for the equation
            $filteredTypes = [ editor_Segment_Tag::TYPE_INTERNAL ];
            $renderedOriginal = $this->targetOriginal->cloneFiltered($filteredTypes)->render();
            foreach($this->getTargets() as $target){
                // create Clone with stripped trackchanges
                $renderedTarget = $target->cloneWithoutTrackChanges($filteredTypes)->render();
                if(!$target->isEmpty() && (($edited === true && $renderedTarget != $renderedOriginal) || ($edited === false && $renderedTarget == $renderedOriginal))){
                    $changedTargets[] = $target->getField();
                }
            }
        }
        return $changedTargets;
    }
    /**
     *
     * @return editor_Segment_FieldTags|null
     */
    public function getSource() : ?editor_Segment_FieldTags {
        return $this->source;
    }
    /**
     *
     * @return editor_Segment_FieldTags|null
     */
    public function getOriginalSource() : ?editor_Segment_FieldTags {
        return $this->sourceOriginal;
    }
    /**
     * Retrieves the original source in case of an editable source or the source otherwise
     * @return editor_Segment_FieldTags|null
     */
    public function getOriginalOrNormalSource() : ?editor_Segment_FieldTags {
        if($this->hasOriginalSource()){
            return $this->sourceOriginal;
        }
        return $this->source;
    }
    /**
     *
     * @return editor_Segment_FieldTags[]
     */
    public function getTargets(): array {
        return $this->targets;
    }
    /**
     *
     * @return editor_Segment_FieldTags|null
     */
    public function getOriginalTarget() : ?editor_Segment_FieldTags {
        return $this->targetOriginal;
    }
    /**
     *
     * @return editor_Segment_FieldTags|NULL
     */
    public function getFirstTarget() : ?editor_Segment_FieldTags {
        if(count($this->targets) > 0){
            return $this->targets[0];
        }
        return NULL;
    }
    /**
     * Retrieves ALL our field tags
     * @return editor_Segment_FieldTags[]
     */
    private function getFieldTags(): array {
        $tags = $this->getTargets();
        if($this->hasSource()){
            array_unshift($tags, $this->source);
        }
        if($this->hasOriginalSource()){
            array_unshift($tags, $this->sourceOriginal);
        }
        return $tags;
    }
    /**
     * Retrieves all field tags that can be edited
     * @return editor_Segment_FieldTags[]
     */
    private function getEditableFieldTags(): array {
        $tags = $this->getTargets();
        if($this->hasOriginalSource() && $this->hasSource()){
            array_unshift($tags, $this->source);
        }
        return $tags;
    }
    /**
     *
     * @return int
     */
    public function getSegmentId(): int {
        return $this->segmentId;
    }
    /**
     *
     * @return editor_Models_Segment
     */
    public function getSegment() : editor_Models_Segment {
        if($this->segment == null){
            $this->segment = ZfExtended_Factory::get('editor_Models_Segment');
            /* @var $segment editor_Models_Segment */
            $this->segment->load($this->segmentId);
        }
        return $this->segment;
    }

    /* Internal Tags API */

    /**
     * Removes the tags of the passed type in all our field tags
     * @param string $type
     */
    public function removeTagsByType(string $type){
        foreach($this->getFieldTags() as $fieldTags){
            $fieldTags->removeByType($type);
        }
    }
    /**
     * Removes all field tags leaving just the pure text content
     */
    public function removeAllTags(){
        foreach($this->getFieldTags() as $fieldTags){
            $fieldTags->removeAll();
        }
    }
    /**
     * Retrieves all tags from all our field tags
     * @param string $type
     * @return editor_Segment_Tag[]
     */
    public function getTagsByType(string $type) : array {
        $result = [];
        foreach($this->getFieldTags() as $fieldTags){
            $result = array_merge($result, $fieldTags->getByType($type));
        }
        return $result;
    }
    /**
     * Retrieves all tags from all our field tags and ranges them by field
     * @param string $type
     * @return array
     */
    public function getTagsByTypeForField(string $type) : array {
        $result = [];
        foreach($this->getFieldTags() as $fieldTags){
            $field = $fieldTags->getField();
            if(!array_key_exists($field, $result)){
                $result[$field] = [];
            }
            $result[$field] = array_merge($result[$field], $fieldTags->getByType($type));
        }
        return $result;
    }
    /**
     * Removes the tags of the passed type in all our editable field tags
     * @param string $type
     */
    public function removeEditableTagsByType(string $type){
        foreach($this->getEditableFieldTags() as $fieldTags){
            $fieldTags->removeByType($type);
        }
    }
    /**
     * Retrieves all tags from all our editable field tags
     * @param string $type
     * @return editor_Segment_Tag[]
     */
    public function getEditableTagsByType(string $type) : array {
        $result = [];
        foreach($this->getEditableFieldTags() as $fieldTags){
            $result = array_merge($result, $fieldTags->getByType($type));
        }
        return $result;
    }

    /* SegmentQuality API */

    /**
     * Adds a general quality to the tags (segment-quality model)
     * Do NOT use this API to add a quality that has related segment tags, use ::addQualityByTag instead
     * Note, that the qualities will be saved seperately from the tags-model and are NOT serialized
     * This also means, that during the import-process, the quality-entries will be written before the tags are written AFTER the import
     * @param string $field
     * @param string $type
     * @param string $category
     * @param int $startIndex
     * @param int $endIndex
     * @param stdClass|array|null $additionalData
     * @param bool $hidden
     */
    public function addQuality(
        string $field,
        string $type,
        string $category,
        int $startIndex=0,
        int $endIndex=-1,
        stdClass|array $additionalData = null,
        bool $hidden = false
    ): void
    {
        $this->getQualities()->add($field, $type, $category, $startIndex, $endIndex, $additionalData, $hidden);
    }
    /**
     * Drops a general quality from the tags (segment-quality model)
     * Do NOT use this API to drop a quality that has related segment tags
     * @param string $field
     * @param string $type
     * @param string $category
     * @param int $startIndex
     * @param int $endIndex
     * @param stdClass|null $additionalData: a FLAT object with additional data needed for re-identification. Deeper nested objects will be ignored
     */
    public function dropQuality(string $field, string $type, string $category, int $startIndex=0, int $endIndex=-1, stdClass $additionalData=NULL){
        $this->getQualities()->drop($field, $type, $category, $startIndex, $endIndex, $additionalData);
    }
    /**
     * Adds a quality entry by tag
     * @param editor_Segment_Tag $tag
     * @param string|null $field
     */
    public function addQualityByTag(editor_Segment_Tag $tag, string $field=NULL){
        $this->getQualities()->addByTag($tag, $field);
    }
    /**
     * Adds a quality to the tags (segment-quality model) for all target fields
     * @param string $type
     * @param string $category
     */
    public function addAllTargetsQuality(string $type, string $category){
        foreach($this->getAllTargetFields() as $field){
            $this->getQualities()->add($field, $type, $category, 0, -1);
        }
    }
    /**
     * Inits our qualities for alike-segment processing. This API has to be called before the segment-tags are actually processed
     * @param editor_Segment_Alike_Qualities $alikeQualities
     */
    public function initAlikeQualities(editor_Segment_Alike_Qualities $alikeQualities){
        if($this->qualities == NULL){
            $this->qualities = new editor_Segment_Qualities($this->segmentId, $this->task->getTaskGuid(), $this->processingMode, $alikeQualities);
        } else {
            throw new Exception('Called ::initAlikeQualities() after segment processing actually had started');
        }
    }
    /**
     * Clones all qualities of the given type from the original segment over to the alike segment
     * @param string $type
     */
    public function cloneAlikeQualitiesByType(string $type){
        $this->getQualities()->cloneAlikeType($type);
    }
    /**
     * Returnes the names of all our target fields
     * @return array
     */
    private function getAllTargetFields() : array {
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
    public function extractNewQualities() : array {
        return $this->getQualities()->extractNewQualities();
    }
    /**
     * internal
     * @return editor_Segment_Qualities
     */
    public function getQualities() : editor_Segment_Qualities {
        if($this->qualities == NULL){
            $this->qualities = new editor_Segment_Qualities($this->segmentId, $this->task->getTaskGuid(), $this->processingMode);
        }
        return $this->qualities;
    }

    /* Serialization API */

    /**
     *
     * @return string
     */
    public function toJson(): string {
        return json_encode($this->jsonSerialize(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    /**
     *
     * {@inheritDoc}
     * @see JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize() : stdClass {
        $data = new stdClass();
        $data->taskGuid = $this->task->getTaskGuid();
        $data->segmentId = $this->segmentId;
        $data->processingMode = $this->processingMode;
        $data->targets = [];
        foreach($this->targets as $tag){
            $data->targets[] = $tag->jsonSerialize();
        }
        $data->source = ($this->hasSource()) ? $this->source->jsonSerialize() : false;
        $data->sourceOriginal = ($this->hasOriginalSource()) ? $this->sourceOriginal->jsonSerialize() : false;
        if($this->targetOriginalIdx > -1){
            $data->targetOriginalIdx = $this->targetOriginalIdx;
        } else if($this->targetOriginal != NULL) {
            $data->targetOriginal = $this->targetOriginal->jsonSerialize();
        }
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
            $this->source = ($data->source) ? editor_Segment_FieldTags::fromJsonData($this->task, $data->source) : NULL;
            $this->targets = [];
            foreach($data->targets as $targetData){
                $this->targets[] = editor_Segment_FieldTags::fromJsonData($this->task, $targetData);
            }
            if(!$this->isImport && $this->task->getEnableSourceEditing() && $data->sourceOriginal){
                $this->sourceOriginal = editor_Segment_FieldTags::fromJsonData($this->task, $data->sourceOriginal);
            }
            if(property_exists($data, 'targetOriginalIdx')){
                $this->targetOriginalIdx = $data->targetOriginalIdx;
                $this->targetOriginal = $this->targets[$this->targetOriginalIdx];
            } else if(property_exists($data, 'targetOriginal')){
                $this->targetOriginal = editor_Segment_FieldTags::fromJsonData($this->task, $data->targetOriginal);
            }
        } catch (Exception $e) {
            throw new Exception('Deserialization of editor_Segment_Tags from JSON-Object failed because of invalid data: '.json_encode($data));
        }
    }

    /**
     * @return editor_Models_Task
     */
    public function getTask(): editor_Models_Task {
        return $this->task;
    }

    /**
     * Debug output
     * @return string
     */
    public function debug(): string {
        $debug = '';
        $newline = "\n";
        if($this->source != NULL){
            $debug .= 'SOURCE '.$this->source->debugProps().': '.trim($this->source->render()).$newline;
        }
        if($this->sourceOriginal != NULL){
            $debug .= 'SOURCE ORIGINAL '.$this->sourceOriginal->debugProps().': '.trim($this->sourceOriginal->render()).$newline;
        }
        for($i=0; $i < count($this->targets); $i++){
            $debug .= 'TARGET '.$i.' '.$this->targets[$i]->debugProps().': '.trim($this->targets[$i]->render()).$newline;
        }
        return $debug;
    }

    /**
     * Debug formatted JSON
     * @return string
     */
    public function debugJson(): string {
        return (string) json_encode($this->jsonSerialize(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /**
     * Debug formatted JSON
     * @return string
     */
    public function debugQualities(): string {
        return $this->getQualities()->debug();
    }
}
