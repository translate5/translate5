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

use PHPHtmlParser\Dom\Node\HtmlNode;

/**
 * Abstraction to bundle the segment's text and it's internal tags to an OOP accessible structure
 * The structure of the tags in this class is a simple sequence, any nesting / interleaving is covered with rendering / unparsing
 */
class editor_Segment_FieldTags extends editor_TagSequence {
    
    /**
     * The counterpart to ::toJson: creates the tags from the serialized JSON data
     * @param editor_Models_Task $task
     * @param string $jsonString
     * @throws Exception
     * @return editor_Segment_FieldTags
     */
    public static function fromJson(editor_Models_Task $task, string $jsonString) : editor_Segment_FieldTags {
        try {
            return static::fromJsonData($task, json_decode($jsonString));
        } catch (Exception $e) {
            throw new Exception('Could not deserialize editor_Segment_FieldTags from JSON '.$jsonString);
        }
    }
    /**
     * Creates the tags from deserialized JSON data
     * @param editor_Models_Task $task
     * @param stdClass $data
     * @throws Exception
     * @return editor_Segment_FieldTags
     */
    public static function fromJsonData(editor_Models_Task $task, stdClass $data) : editor_Segment_FieldTags {
        try {
            $tags = new editor_Segment_FieldTags($task, $data->segmentId, $data->text, $data->field, $data->dataField, $data->saveTo, $data->ttName);
            $creator = editor_Segment_TagCreator::instance();
            foreach($data->tags as $tag){
                $segmentTag = $creator->fromJsonData($tag);
                $tags->addTag($segmentTag, $segmentTag->order, $segmentTag->parentOrder);
            }
            // crucial: we do not serialize the deleted/inserted props as they're serialized indirectly with the trackchanges tags
            // so we have to re-evaluate these props now
            $tags->evaluateDeletedInserted();
            return $tags;
        } catch (Exception $e) {
            throw new Exception('Could not deserialize editor_Segment_FieldTags from deserialized JSON-Data '.json_encode($data));
        }
    }
    /**
     * The task the segments belong to
     * @var editor_Models_Task
     */
    private $task;
    /**
     * The id of the segment we refer to
     * @var int
     */
    private $segmentId;
    /**
     * The field our fieldtext comes from e.g. 'source', 'target'
     * @var string
     */
    private $field;
    /**
     * The data-index our fieldtext comes from e.g. 'targetEdit'
     * @var string
     */
    private $dataField;
    /**
     * The field of the segment's data we will be saved to
     * @var string
     */
    private $saveTo;
    /**
     * Special Helper to Track the field-name as used in the TermTagger Code
     * TODO: Check, if this is really neccessary
     * @var string
     */
    private $ttName;

    /**
     * 
     * @param editor_Models_Task $task
     * @param int $segmentId
     * @param string $text: the text content of the segment field
     * @param string $field: the field name, e.g. source or target
     * @param string $dataField: the field's data index, e.g targetEdit
     * @param string $saveTo: only used for processing within editor_Segment_Tags, adds a dataField / field index, the segment will be saved to when flushed or saved
     * @param string $ttName: only used for processing within editor_Segment_Tags
     */
    public function __construct(editor_Models_Task $task, int $segmentId, ?string $text, string $field, string $dataField, string $additionalSaveTo=NULL, string $ttName=NULL) {
        $this->task = $task;
        $this->segmentId = $segmentId;
        $this->field = $field;
        $this->dataField = $dataField;
        $this->saveTo = $additionalSaveTo;
        $this->ttName = ($ttName == NULL) ? $field : $ttName;
        $this->_setMarkup($text);
    }
    /**
     * 
     * @return number
     */
    public function getSegmentId() : int {
        return $this->segmentId;
    }
    /**
     *
     * @return string
     */
    public function getField() : string {
        return $this->field;
    }
    /**
     *
     * @return editor_Models_Task
     */
    public function getTask() : editor_Models_Task {
        return $this->task;
    }
    /**
     * Retrieves the field's data index as defined by editor_Models_SegmentFieldManager::getDataLocationByKey
     * @return string
     */
    public function getDataField() : string {
        return $this->dataField;
    }
    /**
     * Returns the field text (which covers the textual contents of internal tags as well !)
     * @param bool $stripTrackChanges: if set, trackchanges will be removed
     * @param bool $condenseBlanks: if set, a removed trackchanges will have a condensed whitespace for the removed tags
     * @return string
     */
    public function getFieldText(bool $stripTrackChanges=false, bool $condenseBlanks=true) : string {
        if($stripTrackChanges && (count($this->tags) > 0)){
            return $this->getFieldTextWithoutTrackChanges($condenseBlanks);
        }
        return $this->text;
    }
    /**
     * Retrieves our field-text lines.
     * This means, that all TrackChanges Del Contents are removed and our fild-text is splitted by all existing Internal Newline tags
     * @param bool $condenseBlanks
     * @return string[]
     */
    public function getFieldTextLines(bool $condenseBlanks=true) : array {
        $clone = $this->cloneWithoutTrackChanges([ editor_Segment_Tag::TYPE_INTERNAL ], $condenseBlanks);
        $clone->replaceTagsForLines();
        return explode(editor_Segment_NewlineTag::RENDERED, $clone->render());
    }
    /**
     *
     * @param bool $stripTrackChanges: if set, trackchanges will be removed
     * @param bool $condenseBlanks: if set, a removed trackchanges will have a condensed whitespace for the removed tags
     * @return int
     */
    public function getFieldTextLength(bool $stripTrackChanges=false, bool $condenseBlanks=true) : int {
        if($stripTrackChanges && (count($this->tags) > 0)){
            return mb_strlen($this->getFieldTextWithoutTrackChanges($condenseBlanks));
        }
        return $this->getTextLength();
    }
    /**
     *
     * @param bool $stripTrackChanges: if set, trackchanges will be removed
     * @param bool $condenseBlanks: if set, a removed trackchanges will have a condensed whitespace for the removed tags
     * @return bool
     */
    public function isFieldTextEmpty(bool $stripTrackChanges=false, bool $condenseBlanks=true) : bool {
        if($stripTrackChanges && (count($this->tags) > 0)){
            return ($this->getFieldTextLength(true, $condenseBlanks) == 0);
        }
        return ($this->getTextLength() === 0);
    }
    /**
     * Evaluates, if the bound field is a source field
     * @return bool
     */
    public function isSourceField() : bool {
        return (substr($this->field, 0, 6) == 'source');
    }
    /**
     * Evaluates, if the bound field is a target field
     * @return bool
     */
    public function isTargetField() : bool {
        return !$this->isSourceField();
    }
    /**
     * TODO: might be unneccessary
     * @return string
     */
    public function getTermtaggerName() : string {
        return $this->ttName;
    }
    /**
     * Called after the unparsing Phase to finalize a single tag
     * @param editor_Segment_Tag $tag
     */
    protected function finalizeAddTag(editor_Segment_Tag $tag){
        $tag->field = $this->field; // we transfer our field to the tag for easier handling of our segment-tags
    }
    /**
     *
     * @return string[]
     */
    public function getSaveToFields() : array {
        $fields = [ $this->dataField ];
        if(!empty($this->saveTo)){
            $fields[] = $this->saveTo;
        }
        return $fields;
    }
    /**
     * Retrieves the internal tags of a certain type
     * @param string $type
     * @param boolean $includeDeleted: if set, internal tags that represent deleted content will be processed as well
     * @return editor_Segment_Tag[]
     */
    public function getByType(string $type, bool $includeDeleted=false) : array {
        $result = [];
        foreach($this->tags as $tag){
            if($tag->getType() == $type && ($includeDeleted || !$tag->wasDeleted)){
                $result[] = $tag;
            }
        }
        return $result;
    }
    /**
     * Removes the internal tags of a certain type
     * @param string $type
     * @param boolean $skipDeleted: if set, field tags that represent deleted content will be skipped
     */
    public function removeByType(string $type, bool $skipDeleted=false){
        $result = [];
        $replace = false;
        foreach($this->tags as $tag){
            if($tag->getType() == $type && (!$skipDeleted || !$tag->wasDeleted)){
                $replace = true;
            } else {
                $result[] = $tag;
            }
        }
        if($replace){
            $this->tags = $result;
            $this->fixParentOrders();
        }
    }
    /**
     * Checks if a internal tag of a certain type is present
     * @param string $type
     * @param boolean $includeDeleted: if set, internal tags that represent deleted content will be processed as well
     * @return boolean
     */
    public function hasType(string $type, bool $includeDeleted=false) : bool {
        foreach($this->tags as $tag){
            if($tag->getType() == $type && ($includeDeleted || !$tag->wasDeleted)){
                return true;
            }
        }
        return false;
    }
    /**
     * Retrieves if there are trackchanges tags present
     * @return bool
     */
    public function hasTrackChanges() : bool {
        return $this->hasType(editor_Segment_Tag::TYPE_TRACKCHANGES, true);
    }
    /**
     * Checks if a internal tag of a certain type and class is present
     * @param string $type
     * @param string $className
     * @return bool
     */
    public function hasTypeAndClass(string $type, string $className, bool $includeDeleted=false) : bool {
        foreach($this->tags as $tag){
            if($tag->getType() == $type && $tag->hasClass($className) && ($includeDeleted || !$tag->wasDeleted)){
                return true;
            }
        }
        return false;
    }
    /**
     * Checks if a internal tag of a certain type is present that has at least one of the given classnames
     * @param string $type
     * @param string[] $classNames
     * @return bool
     */
    public function hasTypeAndClasses(string $type, array $classNames, bool $includeDeleted=false) : bool {
        foreach($this->tags as $tag){
            if($tag->getType() == $type && $tag->hasClasses($classNames) && ($includeDeleted || !$tag->wasDeleted)){
                return true;
            }
        }
        return false;
    }
    /**
     * Retrieves, how many internal tags representing whitespace, are present
     * @return int
     */
    public function getNumLineBreaks() : int {
        $numLineBreaks = 0;
        foreach($this->tags as $tag){
            if($tag->getType() == editor_Segment_Tag::TYPE_INTERNAL && $tag->isNewline()){
                $numLineBreaks++;
            }
        }
        return $numLineBreaks;
    }

    /* Serialization API */
    
    public function jsonSerialize() : mixed {
        $data = parent::jsonSerialize();
        $data->segmentId = $this->segmentId;
        $data->field = $this->field;
        $data->dataField = $this->dataField;
        $data->saveTo = $this->saveTo;
        $data->ttName = $this->ttName;
        return $data;
    }

    /* Unparsing API */

    /**
     * Called after the unparsing phase to finalize all tags
     */
    protected function finalizeUnparse(){
        $num = count($this->tags);
        $textLength = $this->getFieldTextLength();
        for($i=0; $i < $num; $i++){
            $tag = $this->tags[$i];
            $tag->isFullLength = ($tag->startIndex == 0 && $tag->endIndex >= $textLength);
            $tag->content = $this->getTextPart($tag->startIndex, $tag->endIndex);
            $tag->field = $this->field;
            $tag->finalize($this, $this->task);
        }
        // after unserialization, we set the wasDeleted / wasInserted properties of our tags
        $this->evaluateDeletedInserted();
    }
    /**
     * @param HtmlNode $node
     * @param int $startIndex
     * @return editor_Segment_Tag
     */
    protected function createFromHtmlNode(HtmlNode $node, int $startIndex) : editor_Segment_Tag {
        return editor_Segment_TagCreator::instance()->fromHtmlNode($node, $startIndex);
    }
    /**
     * @param DOMElement $element
     * @param int $startIndex
     * @return editor_Segment_Tag
     */
    protected function createFromDomElement(DOMElement $element, int $startIndex) : editor_Segment_Tag {
        return editor_Segment_TagCreator::instance()->fromDomElement($element, $startIndex);
    }

    /* Cloning API */

    /**
     * Clones the tags with only the types of tags specified
     * Note, that you will not be able to filter trackchanges-tags out, use ::cloneWithoutTrackChanges instead for this
     * @param array $includedTypes
     * @param bool $finalize: Usually required, fixes any lost order-connections
     * @return editor_Segment_FieldTags
     */
    public function cloneFiltered(array $includedTypes=NULL, bool $finalize=true) : editor_Segment_FieldTags {
        $clonedTags = new editor_Segment_FieldTags($this->task, $this->segmentId, $this->text, $this->field, $this->dataField, $this->saveTo, $this->ttName);
        foreach($this->tags as $tag){
            if($tag->getType() == editor_Segment_Tag::TYPE_TRACKCHANGES || ($includedTypes == NULL || in_array($tag->getType(), $includedTypes))){
                $clonedTags->addTag($tag->clone(true, true), $tag->order, $tag->parentOrder);
            }
        }
        if($finalize){
            $clonedTags->fixParentOrders();
        }
        return $clonedTags;
    }
    /**
     * Clones without trackchanges tags. Deleted contents (in del-tags) will be removed and all text-lengths/indices will be adjusted
     * @param array $includedTypes: if set, filters the existing types of tags to the specified
     * @param bool $condenseBlanks: if set (default), blanks around del-tags will be condensed, mimics behaviour of editor_Models_Segment_TrackChangeTag::removeTrackChanges
     * @return editor_Segment_FieldTags
     */
    public function cloneWithoutTrackChanges(array $includedTypes=NULL, bool $condenseBlanks=true) : editor_Segment_FieldTags {
        $clonedTags = $this->cloneFiltered($includedTypes, false);
        if(!$clonedTags->deleteTrackChangesTags($condenseBlanks)){
            $clonedTags->fixParentOrders();
        }
        return $clonedTags;
    }
    /**
     * Removes all TrackChanges tags, also deletes all contents of del-tags
     * @param boolean $condenseBlanks
     * @return boolean
     */
    private function deleteTrackChangesTags($condenseBlanks=true) : bool {
        $this->evaluateDeletedInserted(); // ensure this is properly set (normally always the case)
        $this->sort(); // making sure we're in order
        $hasTrackChanges = false;
        foreach($this->tags as $tag){
            if($tag->getType() == editor_Segment_Tag::TYPE_TRACKCHANGES){
                $tag->wasDeleted = true;
                if($tag->isDeleteTag() && $tag->endIndex > $tag->startIndex){
                    if($condenseBlanks){
                        $boundries = $this->getRemovableBlanksBoundries($tag->startIndex, $tag->endIndex);
                        if($boundries->left < $tag->startIndex && $boundries->right > $tag->endIndex){
                            // if there are removable blanks on both sides it is meaningless, on which side we leave one
                            $tag->startIndex = $boundries->left;
                            $tag->endIndex = $boundries->right - 1;
                        }
                    }
                    $this->cutIndicesOut($tag->startIndex, $tag->endIndex);
                }
                $hasTrackChanges = true;
            }
        }
        if($hasTrackChanges){
            $newTags = [];
            foreach($this->tags as $tag){
                // removes the del-tags the "hole punching" may created more deleted tags - should not happen though
                if(!$tag->wasDeleted){
                    if($tag->wasInserted){
                        $tag->wasInserted = null;
                    }
                    $newTags[] = $tag;
                }
            }
            $this->tags = $newTags;
            $this->fixParentOrders();
            $this->sort();
        }
        return $hasTrackChanges;
    }
    /**
     * Retrieves the boundries of a del-tag increased by the blanks that can be removed without affecting other tags
     * @param int $start
     * @param int $end
     * @return stdClass
     */
    private function getRemovableBlanksBoundries(int $start, int $end) : stdClass {
        $length = $this->getFieldTextLength();
        $boundries = new stdClass();
        $boundries->left = $start;
        $boundries->right = $end;
        // increase the boundries to cover all blanks left and right
        while(($boundries->left - 1) > 0 && $this->getTextPart($boundries->left - 1, $boundries->left) == ' '){
            $boundries->left -= 1;
        }
        while(($boundries->right + 1) < $length && $this->getTextPart($boundries->right, $boundries->right + 1) == ' '){
            $boundries->right += 1;
        }
        // reduce the boundries if there are tags covered
        foreach($this->tags as $tag){
            if(!$tag->wasDeleted && $tag->getType() != editor_Segment_Tag::TYPE_TRACKCHANGES){
                if($tag->startIndex >= $boundries->left && $tag->startIndex <= $start){
                    $boundries->left = $tag->startIndex;
                }
                if($tag->endIndex <= $boundries->right && $tag->endIndex >= $end){
                    $boundries->right = $tag->endIndex;
                }
            }
        }
        return $boundries;        
    }
    /**
     * Removes the text-portion from our field-text and our tags
     * @param int $start
     * @param int $end
     */
    private function cutIndicesOut(int $start, int $end) {
        $dist = $end - $start;
        if($dist <= 0){
            return;
        }
        // adjust the tags
        foreach($this->tags as $tag){            
            // the tag is only affected if not completely  before the hole
            if($tag->endIndex > $start){                
                // if we're completely behind, just shift
                if($tag->startIndex >= $end){
                    $tag->startIndex -= $dist;
                    $tag->endIndex -= $dist;
                } else if($tag->startIndex >= $start && $tag->endIndex <= $end) {
                    // singular boundry tags will only be shifted
                    if($tag->endIndex == $start || $tag->startIndex == $end){
                        $tag->startIndex -= $dist;
                        $tag->endIndex -= $dist;
                    } else {
                        // this can only happen, if non-trackchanges tags overlap with trackchanges tags. TODO: generate an error here ?
                        if($tag->getType() != editor_Segment_Tag::TYPE_TRACKCHANGES && !$tag->wasDeleted && static::VALIDATION_MODE){
                            error_log("\n##### TRACKCHANGES CLONING: FOUND TAG THAT HAS TO BE REMOVED ALTHOUGH NOT MARKED AS DELETED ($start|$end) ".$tag->debugProps()." #####\n");
                        }
                        $tag->startIndex = $tag->endIndex = 0;
                        $tag->wasDeleted = true;
                    }
                } else {
                    // tag is somehow overlapping the hole
                    $tag->startIndex = ($tag->startIndex <= $start) ? $tag->startIndex : $start;
                    $tag->endIndex = ($tag->endIndex >= $end) ? ($tag->endIndex - $dist) : ($end - $dist);
                }
            }
        }
        // adjust the field text
        $length = $this->getFieldTextLength();
        $newFieldText = ($start > 0) ? $this->getTextPart(0, $start) : '';
        $newFieldText .= ($end < $length) ? $this->getTextPart($end, $length) : '';
        $this->setText($newFieldText);
    }
    /**
     * Retrieves the text with the TrackChanges removed
     * @param boolean $condenseBlanks
     * @return string
     */
    private function getFieldTextWithoutTrackChanges(bool $condenseBlanks=true) : string {
        $this->sort();
        $text = '';
        $start = 0;
        $length = $this->getFieldTextLength();
        foreach($this->tags as $tag){
            // the tag is only affected if not completely  before the hole
            if($tag->getType() == editor_Segment_Tag::TYPE_TRACKCHANGES && $tag->isDeleteTag() && $tag->endIndex > $tag->startIndex && $tag->endIndex > $start){
                $boundries = ($condenseBlanks) ? $this->getRemovableBlanksBoundries($tag->startIndex, $tag->endIndex) : NULL;
                if($boundries != NULL && $boundries->left < $tag->startIndex && $boundries->right > $tag->endIndex){
                    // if there are removable blanks on both sides it is meaningless, on which side we leave one
                    if($boundries->left > $start){
                        $text .= $this->getTextPart($start, $boundries->left);
                    }
                    $start = $boundries->right - 1;
                } else {
                    if($tag->startIndex > $start){
                        $text .= $this->getTextPart($start, $tag->startIndex);
                    }
                    $start = $tag->endIndex;
                }
            }
        }
        if($start < $length){
            $text .= $this->getTextPart($start, $length);
        }
        return $text;
    }
    /**
     * Special API to render all internal newline tags as lines
     * This expects TrackChanges Tags to be removed, otherwise the result will contain trackchanges contents
     */
    private function replaceTagsForLines() {
        $tags = [];
        foreach($this->tags as $tag){
            // the tag is only affected if not completely  before the hole
            if($tag->getType() == editor_Segment_Tag::TYPE_INTERNAL && $tag->isNewline()){
                $tags[] = editor_Segment_NewlineTag::createNew($tag->startIndex, $tag->endIndex);
            }
        }
        $this->tags = $tags;
    }
    /**
     * Sets the deleted / inserted properties for all tags. 
     * This is the last step of unparsing the tags and deserialization from JSON
     * It is also crucial for evaluating qualities because only non-deleted tags will count
     */
    private function evaluateDeletedInserted(){
        foreach($this->tags as $tag){
            if($tag->getType() == editor_Segment_Tag::TYPE_TRACKCHANGES){
                $propName = ($tag->isDeleteTag()) ? 'wasDeleted' : 'wasInserted';
                $this->setContainedTagsProp($tag->startIndex, $tag->endIndex, $tag->order, $propName);
            }
        }
    }

    /* Logging API */

    /**
     *
     * @param array $errorData
     */
    protected function addErrorDetails(array &$errorData){
        $errorData['segmentId'] = $this->segmentId;
        $errorData['taskId'] = $this->task->getId();
        $errorData['taskGuid'] = $this->task->getTaskGuid();
        $errorData['taskName'] = $this->task->getTaskName();
    }

    /* Debugging API */

    /**
     * Debug state of our segment props
     * @return string
     */
    public function debugProps(){
        return '[ segment:'.$this->segmentId.' | field:'.$this->field.' | dataField:'.$this->dataField.' | saveTo:'.$this->saveTo.' ]';
    }
    /**
     * Debug formatted JSON
     * @return string
     */
    public function debugJson(){
        return json_encode($this->jsonSerialize(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
