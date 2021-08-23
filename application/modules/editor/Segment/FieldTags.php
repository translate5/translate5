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
 * The rendering will take care about interleaving and nested tags and may part a tag into chunks
 * When Markup is unserialized multiple chunks in a row of an internal tag will be joined to a single tag and the structure will be re-sequenced
 * Keep in mind that start & end-index work just like counting chars or the substr API in php, the tag starts BEFORE the start index and ends BEFORE the end index, if you want to cover the whole segment the indices are 0 and mb_strlen($segment)
 * tags that are immediate siblings can be identified by having the same end/start index
 * To identify the Types of Internal tags a general API editor_Segment_TagCreator is provided
 * Tag types can be registered via the Quality Provider registry in editor_Segment_Quality_Manager or (if not quality related) directly in the editor_Segment_TagCreator registry
 * Most existing segment tags are known by the editor_Segment_TagCreator evaluation API
 * 
 * The creation of the internal tags is done in 3 phases
 * - the unparsing starts either on instantiation (setting the markup from a segment's field text - thus the class-name - or by calling setTagsByText($markup)
 * - the passed markup is unparsed with either PHPHtmlParser or DOMDocument as configured in editor_Tag
 * - this creates a deep nested tag structure => Phase I
 * - the nested Dom Tags then are converted into segment-tags as direct children of this class. These tags represent their position in the markup by start/end indexes pointing to the pure text-content of the markup (represented by the fieldText prop) => Phase II
 * - usually all DOM tags represent a segment tag as all markup should be encapsulated by internal tags
 * - Note that in this class the fieldText also contains the visible Markup of the internal Tags (=> length of FieldTags) whilst in the Segment entities API those chars do not count as textual content of the segment obviously
 * - All Tags, that can not be converted to an actual segnment-tag will end up as "any" internal tag "editor_Segment_AnyTag" Those tags will not be consolidated so they will be renderd the way they are and no further processing is applied
 * - After "flattening" all tags of the same type with the same properties (category, as defined in the corresponding tag classes) are joined to one tag. This is the consolidation Phase => Phase III
 * - In the consolidation phase those tags representing a "virtual tag" by consisting of two singular tags (img) with "open" and "close" classes (-> e.g. MQM) are identified and united as a single tag (pairing API)
 * - After the consolidation the properties of the segment tags we hold are consumable for other APIs
 * 
 * Rendering
 * - When rendering the markup the contained segment tags are maybe broken up into several parts because they may overlap.
 * - In this process usually the left overlapping tag will be broken into parts. Some tags like the internal tags can not be splitted (isSplitable API)
 * - that means, in the frontend we may have multiple chunks representing one segment tag !
 * - it is possible, that overlapping tags in the frontend (-> MQM), where the order is "user defined", may have a different branching after being processed. This can also happen, when more tags are added (Termtagger, ...)
 * - To retrieve a proper Markup - especially with the singular tags to be in a useful order - sorting the tags is crucial and ensures a correct structure
 * 
 * Compatibility Problems
 * - generally the AutoQA adds some data-attributes to existing classes
 * - The generated markup may be different then in earlier times (order of attributes!)
 * - This may creates problems with regex-based tag processing that relies on a fixed order of attributes or css-classes
 * - Generally, RegEx based processing of Markup often fails with nested Markup (especially when the expressions cover the start and end tag) and should be replaced with OOP code
 */
class editor_Segment_FieldTags implements JsonSerializable {
    
    /**
     * Can be used to validate the unparsing-process. Use only for Development !!
     * @var boolean
     */
    const VALIDATION_MODE = false;
    /**
     * The counterpart to ::toJson: creates the tags from the serialized JSON data
     * @param editor_Models_Task $task
     * @param string $jsonString
     * @throws Exception
     * @return editor_Segment_FieldTags
     */
    public static function fromJson(editor_Models_Task $task, string $jsonString) : editor_Segment_FieldTags {
        try {
            return self::fromJsonData($task, json_decode($jsonString));
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
            $tags = new editor_Segment_FieldTags($task, $data->segmentId, $data->fieldText, $data->field, $data->dataField, $data->saveTo, $data->ttName);
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
     * Helper to sort Internal tags or rendered tags by startIndex
     * This is a central part of the rendering logic
     * Note, that for rendering, tags, that potentially contain other tags, must come first, otherwise this will lead to rendering errors
     * The nesting may be corrected with the ::findHolderByOrder API but for rendering this "longer first" logic must apply
     * @param editor_Segment_Tag $a
     * @param editor_Segment_Tag $b
     * @return int
     */
    public static function compare(editor_Segment_Tag $a, editor_Segment_Tag $b){
        if($a->startIndex === $b->startIndex){
            // only tags at the exact same position that do not contain each other will need the order-property evaluated when sorting !
            if($b->endIndex == $a->endIndex && $a->order > -1 && $b->order > -1 && $a->parentOrder != $b->order && $a->order != $b->parentOrder){
                return $a->order - $b->order;
            }
            // crucial: we must make sure, that a "normal" tag may contain a single tag at the same index (no text-content). Thus, the normal tags always must weight less / come first
            if($a->isSingular() && !$b->isSingular()){
                return 1;
            } else if(!$a->isSingular() && $b->isSingular()){
                return -1;
            }
            return $b->endIndex - $a->endIndex;
        }
        return $a->startIndex - $b->startIndex;
    }
    /**
     * Sorting of children of segment tags in the rendering phase: Here the singular tags (where startIndex == endIndex) MUST come first!
     * This is crucial for the text-distribution to work properly (::addSegmentText)
     * @param editor_Segment_Tag $a
     * @param editor_Segment_Tag $b
     * @return number
     */
    public static function compareChildren(editor_Segment_Tag $a, editor_Segment_Tag $b){
        if($a->startIndex === $b->startIndex){
             // only tags at the exact same position that do not contain each other will need the order-property evaluated when sorting !
            if($b->endIndex == $a->endIndex && $a->order > -1 && $b->order > -1){
                return $a->order - $b->order;
            }
            return $a->endIndex - $b->endIndex;
        }
        return $a->startIndex - $b->startIndex;
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
     * The text of the relevant segment field
     * This text unfortunately covers the text-contents of Internal Tags
     * @var string
     */
    private $fieldText;
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
     * The tags and their positions within the segment
     * @var editor_Segment_Tag[]
     */
    private $tags = [];
    /**
     * @var integer
     */
    private $orderIndex = -1;
    
    /**
     * 
     * @param editor_Models_Task $task
     * @param int $segmentId
     * @param string $fieldText: the text content of the segment field
     * @param string $field: the field name, e.g. source or target
     * @param string $dataField: the field's data index, e.g targetEdit
     * @param string $saveTo: only used for processing within editor_Segment_Tags, adds a dataField / field index, the segment will be saved to when flushed or saved
     * @param string $ttName: only used for processing within editor_Segment_Tags
     */
    public function __construct(editor_Models_Task $task, int $segmentId, ?string $fieldText, string $field, string $dataField, string $additionalSaveTo=NULL, string $ttName=NULL) {
        $this->task = $task;
        $this->segmentId = $segmentId;
        $this->fieldText = '';
        $this->field = $field;
        $this->dataField = $dataField;
        $this->saveTo = $additionalSaveTo;
        $this->ttName = ($ttName == NULL) ? $field : $ttName;
        // if HTML was passed as field text we have to unparse it
        if(!empty($fieldText) && $fieldText != editor_Segment_Tag::strip($fieldText)){
            $this->unparse($fieldText);
        } else if($fieldText !== NULL) {
            $this->fieldText = $fieldText;
        }
        // This debug can be used to evaluate the quality of the DOM parsing
        if(self::VALIDATION_MODE && $this->fieldText != editor_Segment_Tag::strip($fieldText)){
            error_log('=================== PARSED FIELD TEXT DID NOT MATCH PASSED HTML ===================='."\n");
            error_log('FAULTY FIELDTAGS FOR SEGMENT '.$this->segmentId."\n");
            error_log('RAW TEXT: '.editor_Segment_Tag::strip($fieldText)."\n");
            error_log('FIELD TEXT: '.$this->fieldText."\n");
            error_log('IN:  '.$fieldText."\n");
            error_log('OUT: '.$this->render()."\n");
            error_log('TAGS: '.$this->toJson()."\n");
            error_log('======================================='."\n");
        }
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
        return $this->fieldText;
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
     * @return string
     */
    public function getFieldTextLength(bool $stripTrackChanges=false, bool $condenseBlanks=true) : int {
        if($stripTrackChanges && (count($this->tags) > 0)){
            return mb_strlen($this->getFieldTextWithoutTrackChanges($condenseBlanks));
        }
        return mb_strlen($this->fieldText);
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
        return mb_strlen($this->fieldText) == 0;
    }
    /**
     * 
     * @return bool
     */
    public function isEmpty() : bool {
        return ($this->isFieldTextEmpty() && !$this->hasTags());
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
     * We expect the passed text to be identical
     * @param string $text
     * @return string
     */
    public function setTagsByText(string $text){
        $textBefore = $this->fieldText;
        $this->fieldText = '';
        $this->tags = [];
        $this->orderIndex = -1;
        $this->unparse($text);
        // this checks if the new Tags may changed the text-content which must not happen during quality checks
        if($this->fieldText != $textBefore){
            $logger = Zend_Registry::get('logger')->cloneMe('editor.segment.fieldtags');
            $logger->warn(
                'E1343',
                'Setting the FieldTags tags by text led to a changed text-content presumably because the encoded tags have been improperly processed',
                ['segmentId' => $this->segmentId, 'textBefore' => $textBefore, 'textAfter' => $this->fieldText ]
            );
        }
    }
    /**
     * Adds a Segment tag. Note, that the nesting has to be reflected with the internal order of tags and the parent (referencing the order of the parent element)
     * @param editor_Segment_Tag $tag
     * @param int $order: Do only use if the internal order is know. If not provided, a tag is always added as the last thing at the tags startPosition (e.g. a single tag can not be added inside a present tag's send position without knowing the order)
     * @param int $parent: Order-index of the oparent element with nested tags. If not provided, the added tag will be rendered outside of other tags if possible
     */
    public function addTag(editor_Segment_Tag $tag, int $order=-1, int $parentOrder=-1){
        $tag->isFullLength = ($tag->startIndex == 0 && $tag->endIndex >= $this->getFieldTextLength());
        $tag->field = $this->field; // we transfer our field to the tag for easier handling of our segment-tags
        $tag->content = $this->getFieldTextPart($tag->startIndex, $tag->endIndex);
        if($order < 0){
            $this->orderIndex++;
            $tag->order = $this->orderIndex;
        } else {
            $tag->order = $order;
            $this->orderIndex = max($this->orderIndex, $order);
        }
        $tag->parentOrder = $parentOrder;
        $this->tags[] = $tag;
    }
    /**
     * Retrieves the tag at a certain index
     * @param int $index
     * @return editor_Segment_Tag|NULL
     */
    public function getAt($index){
        if($index < count($this->tags)){
            return $this->tags[$index];
        }
        return NULL;
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
     * @param boolean $includeDeleted: if set, field tags that represent deleted content will be processed as well
     */
    public function removeByType(string $type, bool $includeDeleted=false){
        $result = [];
        $replace = false;
        foreach($this->tags as $tag){
            if($tag->getType() != $type || (!$includeDeleted && $tag->wasDeleted)){
                $result[] = $tag;
            } else {
                $replace = true;
            }
        }
        if($replace){
            $this->tags = $result;
            $this->fixParentOrders();
        }
    }
    /**
     * Removes all tags, so only the raw text will be left
     */
    public function removeAll(){
        $this->tags = [];
        $this->orderIndex = -1;
    }
    /**
     * 
     * @return bool
     */
    public function hasTags() : bool {
        return (count($this->tags) > 0);
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
    /**
     * Sorts the items ascending, takes the second index into account when items have the same startIndex
     */
    public function sort(){
        usort($this->tags, array($this, 'compare'));
    }
    
    /* Serialization API */
    
    /**
     *
     * @return string
     */
    public function toJson(){
        return json_encode($this->jsonSerialize(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    public function jsonSerialize(){
        $data = new stdClass();
        $this->sort();
        $data->tags = [];
        foreach($this->tags as $tag){
            $data->tags[] = $tag->jsonSerialize();
        }
        $data->segmentId = $this->segmentId;
        $data->fieldText = $this->fieldText;
        $data->field = $this->field;
        $data->dataField = $this->dataField;
        $data->saveTo = $this->saveTo;
        $data->ttName = $this->ttName;
        return $data;
    }
    
    /* Rendering API */
    
    /**
     * 
     * @param string[] $skippedTypes: if set, internal tags of this type will not be rendered
     * @return string
     */
    public function render(array $skippedTypes=NULL) : string {
   
        if(count($this->tags) == 0){
            return $this->fieldText;
        }
        $this->sort();
        // first, clone our tags to have a disposable rendering model. This may split some tags that are allowed to overlap into "subtags" (like mqm)
        $clones = [];
        /* @var $clones editor_Segment_Tag[] */
        foreach($this->tags as $tag){
            $tag->addRenderingClone($clones);
        }
        usort($clones, array($this, 'compare'));
        $numClones = count($clones);
        // cutting the overlaps
        if($numClones > 1){
            // first, evaluate the needed cuts
            for($i = 0; $i < $numClones; $i++){
                $tag = $clones[$i];
                if($i < $numClones - 1){
                    for($j = $i + 1; $j < $numClones; $j++){
                        $compare = $clones[$j];
                        if($compare->startIndex < $tag->endIndex && $compare->endIndex > $tag->endIndex){
                            if($tag->isSplitable() || $compare->isSplitable()){
                                // add cut to the tag that actually is cutable
                                $bread = ($tag->isSplitable()) ? $tag : $compare;
                                $knife = ($tag->isSplitable()) ? $compare : $tag;
                                // add a cut only, if it's not there already
                                if(!in_array($knife->startIndex, $bread->cuts)){
                                    $bread->cuts[] = $knife->startIndex;
                                }
                            } else {
                                // we have an overlap with tags, that both are not allowed to overlap. this must not happen.
                                // TODO FIXME: Add Proper Exception
                                $logger = Zend_Registry::get('logger')->cloneMe('editor.segment.tags');
                                /* @var $logger ZfExtended_Logger */
                                $logger->error('E9999', 'Two non-splittable tags interleave each other. Segment-ID: '.$this->segmentId);
                                // we simply do not add the next tag which will not be rendered this way
                            }
                        }
                    }
                }
            }
            // then clone the cutted tags into pieces
            for($i = 0; $i < $numClones; $i++){
                if(count($clones[$i]->cuts) > 0){
                    sort($clones[$i]->cuts, SORT_NUMERIC);
                    $last = $clones[$i];
                    $end = $last->endIndex;
                    foreach($clones[$i]->cuts as $cut){
                        $last->endIndex = $cut;
                        $last = $clones[$i]->cloneForRendering();
                        $last->startIndex = $cut;
                        $last->endIndex = $end;
                        $clones[] = $last;
                    }
                }
            }
            usort($clones, array($this, 'compare'));
            $numClones = count($clones);
        }
        // now we create the nested data-model from the up to now sequential but sorted $rtags model. We also add the text-portions of the segment as text nodes
        // this container just acts as the master container 
        $holder = new editor_Segment_AnyTag(0, $this->getFieldTextLength());
        $container = $holder;
        $processed = [ $holder ]; // holds all tags that have been processed
        foreach($clones as $tag){
            // this "mechanic" is just to correct problems with singular tags on the right boundry of non-singular tags: The will be sorted right after the non-singular but may are nested into. We have to correct this ...
            $nearest = $this->findHolderByOrder($processed, $tag);
            if($nearest == NULL){
                $nearest = $container->getNearestContainer($tag); // this is the "normal" way of nesting the sorted cloned tags
            }
            // Will log rendering problems
            if(self::VALIDATION_MODE && $nearest == null){
                error_log("\n============== HOLDER =============\n");
                error_log($holder->toJson());
                error_log("\n============== CONTAINER =============\n");
                error_log($container->toJson());
                error_log("\n============== TAG =============\n");
                error_log($tag->toJson());
                error_log("\n========================================\n");
            }
            $nearest->addChild($tag);
            $container = $tag;
            $processed[] = $tag;
        }
        // distributes the text-portions to the now re-nested structure
        $holder->addSegmentText($this);
        $processed = $clones = null;
        // finally, render the holder's children
        return $holder->renderChildren($skippedTypes);
    }
    /**
     * Retrieves a part of the segment-text by start & end index
     * Used by editor_Segment_Tag to fill in the segment-texts
     * @param int $start
     * @param int $end
     * @return string
     */
    public function getFieldTextPart(int $start, int $end) : string {
        // prevent any substr magic with negative offsets ...
        if($end > $start){
            return mb_substr($this->fieldText, $start, ($end - $start));
        }
        return '';
    }
    
    /* Unparsing API */

    /**
     * Unparses Segment markup into FieldTags
     * @param string $html
     * @throws Exception
     */
    public function unparse(string $html) {
        if(editor_Tag::USE_PHP_DOM){
            // implementation using PHP DOM
            $dom = new editor_Utils_Dom();
            // to make things easier we add a wrapper to hold all tags and only use it's children
            $element = $dom->loadUnicodeElement('<div>'.$html.'</div>');
            if(self::VALIDATION_MODE && substr($dom->saveHTML($element), 5, -6) != $html){
                error_log("\n============== UNPARSED PHP DOM DOES NOT MATCH =============\n");
                error_log(substr($dom->saveHTML($element), 5, -6));
                error_log("\n========================================\n");
                error_log($html);
                error_log("\n========================================\n");
            }
            if($element != NULL){
                $wrapper = $this->fromDomElement($element, 0);
             } else {
                throw new Exception('Could not unparse Internal Tags from Markup '.$html);
            }
        } else {
            // implementation using PHPHtmlParser
            $dom = editor_Tag::createDomParser();
            // to make things easier we add a wrapper to hold all tags and only use it's children
            $dom->loadStr('<div>'.$html.'</div>');
            if($dom->countChildren() != 1){
                throw new Exception('Could not unparse Internal Tags from Markup '.$html);
            }
            if(self::VALIDATION_MODE &&  $dom->firstChild()->innerHtml() != $html){
                error_log("\n============== UNPARSED HTML DOM DOES NOT MATCH =============\n");
                error_log($dom->firstChild()->innerHtml());
                error_log("\n========================================\n");
                error_log($html);
                error_log("\n========================================\n");
            }
            $wrapper = $this->fromHtmlNode($dom->firstChild(), 0);
        }
        // set our field text
        $this->fieldText = $wrapper->getText();
        if(self::VALIDATION_MODE){
            if($wrapper->getTextLength() != $this->getFieldTextLength()){ error_log("\n##### WRAPPER TEXT LENGTH ".$wrapper->getTextLength()." DOES NOT MATCH FIELD TEXT LENGTH: ".$this->getFieldTextLength()." #####\n"); }
            if($wrapper->endIndex != $this->getFieldTextLength()){ error_log("\n##### WRAPPER END INDEX ".$wrapper->endIndex." DOES NOT MATCH FIELD TEXT LENGTH: ".$this->getFieldTextLength()." #####\n"); }
        }
        // sequence the nested tags as our children
        $wrapper->sequenceChildren($this);
        if(self::VALIDATION_MODE){
            $this->sort();
            $length = $this->getFieldTextLength();
            foreach($this->tags as $tag){
                if($tag->endIndex > $length){
                    error_log("\n============== SEGMENT TAG IS OUT OF BOUNDS (TEXT LENGTH: ".$length.") =============\n");
                    error_log($tag->toJson());
                    error_log("\n========================================\n");
                }
            }
        }
        $this->consolidate();
        // Crucial: set the tag-props
        $this->addTagProps();
        // finally, we set the wasDeleted / wasInserted properties of our tags
        $this->evaluateDeletedInserted();
    }
    /**
     * Clones the tags with only the types of tags specified
     * Note, that you will not be able to filter trackchanges-tags out, use ::cloneWithoutTrackChanges instead for this
     * @param array $includedTypes
     * @param bool $finalize: Usually required, fixes any lost order-connections
     * @return editor_Segment_FieldTags
     */
    public function cloneFiltered(array $includedTypes=NULL, bool $finalize=true) : editor_Segment_FieldTags {
        $clonedTags = new editor_Segment_FieldTags($this->task, $this->segmentId, $this->fieldText, $this->field, $this->dataField, $this->saveTo, $this->ttName);
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
     * Helper for the rendering-phase: Finds a tag by it's (valid) order-index
     * Please note that this may fails when multiple tags with the same order have been added
     * @param editor_Segment_Tag[] $holders
     * @param editor_Segment_Tag $tag
     * @return editor_Segment_Tag|NULL
     */
    private function findHolderByOrder(array &$holders, editor_Segment_Tag $tag) : ?editor_Segment_Tag {
        if($tag->parentOrder > -1){
            foreach($holders as $holder){
                if($tag->parentOrder == $holder->order && $holder->canContain($tag)){
                    return $holder;
                }
            }
        }
        return NULL;
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
        while(($boundries->left - 1) > 0 && $this->getFieldTextPart($boundries->left - 1, $boundries->left) == ' '){
            $boundries->left -= 1;
        }
        while(($boundries->right + 1) < $length && $this->getFieldTextPart($boundries->right, $boundries->right + 1) == ' '){
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
     * Removes the text-portion from our field-text and the our tags
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
                        if($tag->getType() != editor_Segment_Tag::TYPE_TRACKCHANGES && !$tag->wasDeleted && self::VALIDATION_MODE){
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
        $newFieldText = ($start > 0) ? $this->getFieldTextPart(0, $start) : '';
        $newFieldText .= ($end < $length) ? $this->getFieldTextPart($end, $length) : '';
        $this->fieldText = $newFieldText;
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
                        $text .= $this->getFieldTextPart($start, $boundries->left);
                    }
                    $start = $boundries->right - 1;
                } else {
                    if($tag->startIndex > $start){
                        $text .= $this->getFieldTextPart($start, $tag->startIndex);
                    }
                    $start = $tag->endIndex;
                }
            }
        }
        if($start < $length){
            $text .= $this->getFieldTextPart($start, $length);
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
     * Creates a nested structure of Internal tags & text-nodes recursively out of a HtmlNode structure
     * @param HtmlNode $node
     * @param int $startIndex
     * @return editor_Segment_Tag
     */
    private function fromHtmlNode(HtmlNode $node, int $startIndex){
        $tag = editor_Segment_TagCreator::instance()->fromHtmlNode($node, $startIndex);
        if($node->hasChildren()){
            foreach($node->getChildren() as $childNode){
                if($childNode->isTextNode()){
                    if($tag->addText($childNode->text())){
                        $startIndex += $tag->getLastChildsTextLength();
                    }
                } else if(is_a($childNode, 'PHPHtmlParser\Dom\Node\HtmlNode')){
                    if($tag->addChild($this->fromHtmlNode($childNode, $startIndex))){
                        $startIndex += $tag->getLastChildsTextLength();
                    }
                } else if(self::VALIDATION_MODE){
                    error_log("\n##### FROM HTML NODE ADDS UNKNOWN NODE TYPE '".get_class($childNode)."' #####\n");
                }
            }
        }
        $tag->endIndex = $startIndex;
        return $tag;
    }
    /**
     * Creates a nested structure of Internal tags & text-nodes recursively out of a DOMElement structure
     * This is an alternative implementation using PHP DOM
     * see editor_Tag::USE_PHP_DOM
     * @param DOMElement $element
     * @param int $startIndex
     * @return editor_Segment_Tag
     */
    private function fromDomElement(DOMElement $element, int $startIndex){
        $tag = editor_Segment_TagCreator::instance()->fromDomElement($element, $startIndex);
        if($element->hasChildNodes()){
            for($i = 0; $i < $element->childNodes->length; $i++){
                $child = $element->childNodes->item($i);
                if($child->nodeType == XML_TEXT_NODE){
                    if($tag->addText(editor_Tag::convertDOMText($child->nodeValue))){
                        $startIndex += $tag->getLastChildsTextLength();
                    }
                } else if($child->nodeType == XML_ELEMENT_NODE){
                    if($tag->addChild($this->fromDomElement($child, $startIndex))){
                        $startIndex += $tag->getLastChildsTextLength();
                    }
                } else if(self::VALIDATION_MODE){
                    error_log("\n##### FROM DOM ELEMENT ADDS UNWANTED ELEMENT TYPE '".$child->nodeType."' #####\n");
                }
            }
        }
        $tag->endIndex = $startIndex;
        return $tag;
    }
    /**
     * Joins Tags that are equal and directly beneath each other
     * Also removes any internal connections between the tags
     * Joins paired tags, removes obsolete tags
     */
    private function consolidate(){
        $this->sort();
        $numTags = count($this->tags);
        if($numTags > 1){
            $tags = [];
            $last = $this->tags[0];
            $last->resetChildren();
            $tags[] = $last;
            for($i=1; $i < $numTags; $i++){
                // when a tag is a paired opener we try to find it's counterpart and remove it from the chain
                if($last->isPairedOpener()){
                    for($j = $i; $j < $numTags; $j++){
                        // if we found the counterpart (the opener could pair it) this closer will be removed from our chain
                        if($this->tags[$j]->isPairedCloser() && $last->getType() == $this->tags[$j]->getType() && $last->pairWith($this->tags[$j])){
                            array_splice($this->tags, $j, 1);
                            $numTags--;
                            break;
                        }
                    }
                }
                // we may already removed the current element, so check
                if($i < $numTags){
                    $tag = $this->tags[$i];
                    // we join only tasks that are splitable of course ...
                    if($last->isSplitable() && $tag->isSplitable() && $tag->isEqual($last) && $last->endIndex == $tag->startIndex){
                        $last->endIndex = $tag->endIndex;
                    } else {
                        $last = $tag;
                        $last->resetChildren();
                        $tags[] = $last;
                    }
                }
            }
            // last step: remove obsolete tags and paired closers that found no counterpart
            $this->tags = [];
            foreach($tags as $tag){
                if($tag->isObsolete() || $tag->isPairedCloser()){
                    $tag->onConsolidationRemoval();
                } else {
                    $this->tags[] = $tag;
                }
            }
            // the tags that were singular but now are real tags (paired tags) may have a improper nesting. We have to correct that
            // it can be assumed, all tags have a proper order here. Since when rendering, the paired tags again will be singular, we correct the nesting by applying a proper order & rightOrder
            foreach($this->tags as $inner){
                foreach($this->tags as $outer){
                    if($outer->startIndex > $inner->endIndex){
                        break;
                    } else {
                        // the $outer->order != $inner->order condition ensures, a tag will not contain itself!
                        if($outer->order != $inner->order && $inner->isPaired() && $outer->isPaired() && $outer->startIndex <= $inner->startIndex && $outer->endIndex >= $inner->endIndex){
                            // this ensures, that when tags with the same start & end index have the order respected for nesting
                            if($outer->startIndex < $inner->startIndex || $outer->endIndex > $inner->endIndex || $outer->order < $inner->order){
                                // if we detect a "wrong order" for nested paired tags (this assumes, all paired tags have the public property "rightOrder")
                                if($inner->startIndex == $outer->startIndex && $inner->order < $outer->order){
                                    $this->swapOrder($outer, $inner, 'order');
                                }
                                if($inner->endIndex == $outer->endIndex && $inner->rightOrder > $outer->rightOrder) {
                                    $this->swapOrder($outer, $inner, 'rightOrder');
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    /**
     * Swaps the order (or rightOrder for paired tags) of two tags, adjusts any connected tags
     * @param editor_Segment_Tag $tag1
     * @param editor_Segment_Tag $tag2
     * @param string $propName
     */
    private function swapOrder(editor_Segment_Tag $tag1, editor_Segment_Tag $tag2, string $propName){
        $cache = $tag1->$propName;
        $tag1->$propName = $tag2->$propName;
        $tag2->$propName = $cache;
        // the order must be adjusted in all other tags that may are nested into to the swapped tags
        if($propName == 'order'){
            foreach($this->tags as $tag){
                if($tag->order != $tag1->order && $tag->order != $tag2->order && ($tag->parentOrder == $tag1->order || $tag->parentOrder == $tag2->order)){
                    $tag->parentOrder = ($tag->parentOrder == $tag1->order) ? $tag2->order : $tag1->order;
                }
            }
        }
    }
    /**
     * Adds the 'isFullLength' and 'field' prop to the tags, which are needed by consuming APIs and calls the finalize for the tags indicate, the tag creation phase is finished
     */
    private function addTagProps(){
        $num = count($this->tags);
        $textLength = $this->getFieldTextLength();
        for($i=0; $i < $num; $i++){
            $tag = $this->tags[$i];
            $tag->isFullLength = ($tag->startIndex == 0 && $tag->endIndex >= $textLength);
            $tag->field = $this->field;
            $tag->content = $this->getFieldTextPart($tag->startIndex, $tag->endIndex);
            $tag->finalize($this, $this->task);
        }
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
    /**
     * Helper to set the del/ins properties
     * @param int $start
     * @param int $end
     * @param string $propName
     */
    private function setContainedTagsProp(int $start, int $end, int $order, string $propName){
        foreach($this->tags as $tag){
            if($tag->startIndex >= $start && $tag->endIndex <= $end && $tag->getType() != editor_Segment_Tag::TYPE_TRACKCHANGES){
                if(!($tag->endIndex == $start || $tag->startIndex == $end) || $tag->parentOrder == $order){
                    $tag->$propName = true;
                }
            }
        }
    }
    /**
     * Removes any parentOrder indices that point to non-existing indices
     */
    private function fixParentOrders(){
        $orders = [];
        foreach($this->tags as $tag){
            $orders[] = $tag->order;
        }
        foreach($this->tags as $tag){
            if(!in_array($tag->parentOrder, $orders)){
                $tag->parentOrder = -1;
            }
        }
    }
    /**
     * Debug output
     * @return string
     */
    public function debug(){
        $newline = "\n";
        $debug = 'FIELD TEXT: "'.trim($this->fieldText).'"'.$newline;
          for($i=0; $i < count($this->tags); $i++){
              $debug .= 'TAG '.$i.':'.$newline.trim($this->tags[$i]->debug()).$newline;
        }
        return $debug;
    }
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
