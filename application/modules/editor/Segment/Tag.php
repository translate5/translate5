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
 * Abstraction for an Internal tag as used in the segment text's
 * This adds serialization/unserialization-capabilities (JSON), cloning capabilities and the general managability of internal tags
 * Generally internal tags must be used with start & end indice relative to the segment texts.
 * Keep in mind that start & end-index work just like counting chars or the substr API in php, the tag starts BEFORE the start index and ends BEFORE the index of the end index, if you want to cover the whole segment the indices are 0 and mb_strlen($segment)
 * To identify the Types of Internal tags a general API editor_Segment_TagCreator is provided
 * 
 * @method editor_Segment_Tag clone(bool $withDataAttribs, bool $withId)
 */
class editor_Segment_Tag extends editor_Tag implements JsonSerializable {
    
    /**
     * @var string
     */
    const TYPE_TRACKCHANGES = 'trackchanges';
    /**
     * @var string
     */
    const TYPE_INTERNAL = 'internal';
    /**
     * @var string
     */
    const TYPE_MQM = 'mqm';
    /**
     * @var string
     */
    const TYPE_QM = 'qm';
    /**
     * A special Type only used for tests and as a default for unknown tags in segment content
     * @var string
     */
    const TYPE_ANY = 'any';
    /**
     * @var string
     */
    const CSS_CLASS_TOOLTIP = 'ownttip';
    /**
     * @var string
     */
    const CSS_CLASS_FALSEPOSITIVE = 't5qfalpos';
    /**
     * @var string
     */
    const DATA_NAME_QUALITYID = 't5qid';
    
    /**
     * The counterpart to ::toJson: creates the tag from the serialized json data
     * @param string $jsonString
     * @throws Exception
     * @return editor_Segment_Tag
     */
    public static function fromJson(string $jsonString) : editor_Segment_Tag {
        try {
            $data = json_decode($jsonString);
            return editor_Segment_TagCreator::instance()->fromJsonData($data);
        } catch (Exception $e) {
            throw new Exception('Could not deserialize editor_Segment_Tag from JSON String '.$jsonString);
        }
    }
    /**
     * Evaluates if the passed type matches our type
     * @param string $nodeName
     * @return bool
     */
    public static function isType(string $type) : bool {
        return ($type == static::$type);
    }
    /**
     * Evaluates if the passed nodename matches our nodename
     * @param string $nodeName
     * @return bool
     */
    public static function hasNodeName(string $name) : bool {
        return ($name == static::$nodeName);
    }
    /**
     * Creates a new instance of the Tag.
     * For Segment-tags always use ::createNew for creating a fresh instance with the neccessary props set (identification-class, nade-name), never use ::create()
     * @param int $startIndex
     * @param int $endIndex
     * @param string $category
     * @return editor_Segment_Tag
     */
    public static function createNew(int $startIndex=0, int $endIndex=0, string $category='') : editor_Segment_Tag {
        $tag = new static($startIndex, $endIndex, $category);
        $tag->addClass($tag->getIdentificationClass());
        return $tag;
    }
    /**
     * Strips all segment tags from a string
     * @param string $markup
     * @return string
     */
    public static function strip(string $markup) : string {
        $markup = preg_replace(editor_Segment_Internal_Tag::REGEX_REMOVE, '', $markup);
        return strip_tags($markup);
    }
    /**
     * @deprecated: do not use with segment tags
     * @param string $tagName
     * @throws Exception
     * @return editor_Tag
     */
    public static function create($tagName) : editor_Tag {
        throw new Exception('Direct instantiation via ::create is not appropriate, use ::createNew instead');
    }
    /**
     * The type of Internal tag (e.g, QC for Quality Control), to be set via inheritance
     * @var string
     */
    protected static $type = null;
    /**
     * The node name for the internal tag. This is set as a static property here instead of setting it dynamicly as in editor_Tag
     * @var string
     */
    protected static $nodeName = null;
    /**
     * Defines the identifying class name for the tag, that all tags of this type must have
     * @var string
     */
    protected static $identificationClass = null;
    /**
     * For compatibility with old code/concepts some quality tags may not use the global date-attribute for the quality ID
     * @var string
     */
    protected static $historicDataNameQid = null;
    /**
     * The start character Index of the Tag in relation to the segment's text
     * The opening tag will be rendered BEFORE this char
     * @var int
     */
    public $startIndex = 0;
    /**
     * The end character Index of the Tag in relation to the segment's text.
     * The closing tag will be rendered BEFORE this char
     * @var int
     */
    public $endIndex = 0;
    /**
     * This saves the order of the tag as found on creation. This is e.g. crucial for multiple singular tags beside each other or singular tags on the start or end of the covered markup that are directly beside tags with content
     * @var int
     */
    public $order = -1;
    /**
     * This saves the order of the parent tag as found on creation (if there is a parent tag). This is e.g. crucial for singular tags contained in other tags or to specify the nesting if tags have identical start & end indices
     * @var int
     */
    public $parentOrder = -1;
    /**
     * Set by editor_TagSequence to indicate that the Tag spans the complete segment text
     * @var bool
     */
    public $isFullLength;
    /**
     * Set by editor_TagSequence to indicate that the Tag was deleted at some time in the segment's history (is in a del-tag)
     * @var bool
     */
    public $wasDeleted;
    /**
     * Set by editor_TagSequence to indicate that the Tag was inserted at some time in the segment's history (is in an ins-tag)
     * @var bool
     */
    public $wasInserted;
    /**
     * References the field the Tag belongs to
     * This property is only set, if the tag is part of a FieldTags container and will not be serialized !
     * @var string
     */
    public $field = null;
    /**
     * References the text content of the tag.
     * This property is only set, if the tag is part of a FieldTags container and will not be serialized !
     * This prop NEVER can be used to change segments in the DB or the like, it has only informative character and is used for internal comparisions
     * @var string
     */
    public $content = '';
    /**
     * Only needed in the rendering process
     * @var array
     */
    public $cuts = [];
    /**
     * The category of tag we have, a further specification of type
     * might not be used by all internal tags
     * @var string
     */
    protected $category = '';
    
    /**
     * The Constructor parameters must not be changed in extending classes, otherwise the ::fromJson API will fail !
     * @param int $startIndex
     * @param int $endIndex
     * @param string $category
     * @throws Exception
     */
    public function __construct(int $startIndex, int $endIndex, string $category='') {
        if(static::$type == null || static::$nodeName == null){
            throw new Exception('Direct instantiation of editor_Segment_Tag is not appropriate, type and nodeName must not be NULL');
        }
        parent::__construct(static::$nodeName);
        $this->startIndex = $startIndex;
        $this->endIndex = $endIndex;
        $this->category = $category;
    }
    /**
     * Overwritten API to take the skipped types parameter into account
     * {@inheritDoc}
     * @see editor_Tag::render()
     */
    public function render(array $skippedTypes=NULL) : string {
        if($skippedTypes != NULL && in_array($this->getType(), $skippedTypes)){
            return $this->renderChildren($skippedTypes);
        }
        return $this->renderStart().$this->renderChildren($skippedTypes).$this->renderEnd();
    }
    /**
     * 
     * @return string
     */
    public function toJson(){
        return json_encode($this->jsonSerialize(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    /**
     * {@inheritDoc}
     * @see editor_Tag::createBaseClone()
     * @return editor_Segment_Tag
     */
    protected function createBaseClone(){
        $className = get_class($this);
        return new $className($this->startIndex, $this->endIndex, $this->category);
    }
    /**
     * Opposing to the base-class the node-name with internal text is fixed with the type usually (not with editor_Segment_AnyTag ...)
     * {@inheritDoc}
     * @see editor_Tag::getName()
     */
    public function getName() : string {
        if(static::$nodeName !== null){
            return static::$nodeName;
        }
        return parent::getName();
    }
    /**
     * Retrieves the type of internal tag
     * @return string
     */
    public function getType() : string {
        return static::$type;
    }
    /**
     * Retrieves the category
     * NOTE: any connection between classes and categories must be coded in the inheriting class
     * @return string
     */
    public function getCategory() : string {
        return $this->category;
    }
    /**
     * All tags of this type must have this class, but note, that this may not be a undoubtable way to identify tags of this type
     * @return string
     */
    public function getIdentificationClass() : string {
        return static::$identificationClass;
    }
    /**
     * Sets the category
     * NOTE: any connection between classes and categories must be coded in the inheriting class
     * @param string $category
     * @return string
     */
    public function setCategory(string $category) : editor_Segment_Tag {
        $this->category = $category;
        return $this;
    }
    /**
     * Retrieves if the tag is of a category that is relevant for quality management
     * @return boolean
     */
    public function hasCategory() : bool {
        return !empty($this->category);
    }
    /**
     * 
     * @return bool
     */
    public function hasQualityId() : bool {
        // Compatibility with old code: if there is a existing entry we simply turn it into the current format
        if(static::$historicDataNameQid != NULL && $this->hasData(static::$historicDataNameQid)){
            // transfer only if not present
            if(!array_key_exists('data-'.static::DATA_NAME_QUALITYID, $this->attribs)){
                $this->attribs['data-'.static::DATA_NAME_QUALITYID] = $this->attribs['data-'.static::$historicDataNameQid];
            }
            unset($this->attribs['data-'.static::$historicDataNameQid]);
            return ctype_digit($this->getData(static::DATA_NAME_QUALITYID));
        }
        return ($this->hasData(static::DATA_NAME_QUALITYID) && ctype_digit($this->getData(static::DATA_NAME_QUALITYID)));
    }
    /**
     * Retrieves the quality ID. If not encoded in the tag, returns -1
     * @return int
     */
    public function getQualityId() : int {
        if($this->hasQualityId()){
            return intval($this->getData(static::DATA_NAME_QUALITYID));
        }
        return -1;
    }
    /**
     * 
     * @param int $qualityId
     * @return editor_Segment_Tag
     */
    public function setQualityId(int $qualityId) : editor_Segment_Tag {
        $this->setData(static::DATA_NAME_QUALITYID, strval($qualityId));
        return $this;
    }
    /**
     * Retrieves the data name for the quality entity id
     * @return string
     */
    public function getDataNameQualityId() : string {
        return static::DATA_NAME_QUALITYID;
    }
    /**
     * Retrieves additional data to identify a tag from quality entries
     * This data is stored in the quality entries
     * This API can only be used after tags are part of a FieldTags container / unparsed from a segment
     * This normally covers just a hash of the tags content, to add more data extend this method
     * @return stdClass
     */
    public function getAdditionalData() : stdClass {
        $data = new stdClass();
        $data->hash = md5($this->content);
        return $data;
    }
    /**
     * Identifies a tag by a quality entry from the DB
     * This is needed only for the persistance of the falsePositive flag, all other props will be re-evaluated anyway
     * NOTE: this default implementation checks for the position in the segment OR the content of the tag. Note, that this implementation hypothetically can produce false results when qualities with the same content exist multiple times in the segment
     * @param editor_Models_Db_SegmentQualityRow $quality
     * @return boolean
     */
    public function isQualityEqual(editor_Models_Db_SegmentQualityRow $quality) : bool {
        return ($this->isQualityGenerallyEqual($quality) && $this->isQualityContentEqual($quality));
    }
    /**
     * Checks if type & category are equal between us and a quality entry from DB
     * @param editor_Models_Db_SegmentQualityRow $quality
     * @return boolean
     */
    protected function isQualityGenerallyEqual(editor_Models_Db_SegmentQualityRow $quality) : bool {
        return ($this->getType() === $quality->type && $this->getCategory() == $quality->category);
    }
    /**
     * Checks, if the quality content is equal. The base implementation compares the position in the segment or the content/text in the tag (comparing the tag content-hashes)
     * This default implementation is not very specific (using text-position) and may leads to confusion of tags of the same type & category
     * @param editor_Models_Db_SegmentQualityRow $quality
     * @return boolean
     */
    protected function isQualityContentEqual(editor_Models_Db_SegmentQualityRow $quality) : bool {
        return (($this->startIndex === $quality->startIndex && $this->endIndex === $quality->endIndex) || $quality->isAdditionalDataEqual($this->getAdditionalData()));
    }
    /**
     * Retrieves if the tag has the false-positive decorator
     * @return bool
     */
    public function isFalsePositive() : bool {
        return $this->hasClass(static::CSS_CLASS_FALSEPOSITIVE);
    }
    /**
     * Retrieves the false-positiveness as DB val / int
     * @return int
     */
    public function getFalsePositiveVal() : int {
        if($this->isFalsePositive()){
            return 1;
        }
        return 0;
    }
    /**
     * 
     * @param int $falsePositive: database value as from LEK_segment_quality
     * @return editor_Segment_Tag
     */
    public function setFalsePositive(int $falsePositive=1) : editor_Segment_Tag {
        if($falsePositive == 1){
            $this->addClass(static::CSS_CLASS_FALSEPOSITIVE);
        } else {
            $this->removeClass(static::CSS_CLASS_FALSEPOSITIVE);
        }
        return $this;
    }

    public function jsonSerialize() : mixed {
        $data = new stdClass();
        $data->type = static::$type;
        $data->name = $this->getName();
        $data->category = $this->getCategory();
        $data->startIndex = $this->startIndex;
        $data->endIndex = $this->endIndex;
        $data->order = $this->order;
        $data->parentOrder = $this->parentOrder;
        $data->classes = $this->classes;
        $data->attribs = editor_Tag::encodeAttributes($this->attribs);
        $this->furtherSerialize($data);
        return $data;
    }
    /**
     * 
     * @param stdClass $data
     */
    public function jsonUnserialize(stdClass $data){
        $this->category = $data->category;
        $this->startIndex = $data->startIndex;
        $this->endIndex = $data->endIndex;
        $this->order = $data->order;
        $this->parentOrder = $data->parentOrder;
        $this->classes = $data->classes;
        $this->attribs = editor_Tag::decodeAttributes($data->attribs);
        $this->furtherUnserialize($data);
    }
    /**
     * Use in inheriting classes for further serialization
     * @param stdClass $data
     */
    protected function furtherSerialize(stdClass $data){
        
    }
    /**
     * Use in inheriting classes for further unserialization
     * @param stdClass $data
     */
    protected function furtherUnserialize(stdClass $data){
        
    }
    /**
     * Additionally to the base function we check also for the same segment tag type
     * {@inheritDoc}
     * @see editor_Tag::isEqual()
     */
    public function isEqual(editor_Tag $tag, bool $withDataAttribs=true) : bool {
        return ($this->isEqualType($tag) && parent::isEqual($tag, $withDataAttribs));
    }
    /**
     * Determines, if Internal tags are of an equal type
     * {@inheritDoc}
     * @see editor_Tag::isEqualType()
     */
    public function isEqualType(editor_Tag $tag) : bool {
        if(is_a($tag, 'editor_Segment_Tag') && $tag->getType() == $this->getType()){
            return true;
        }
        return false;
    }
    /**
     * Retrieves, if the tag can be splitted (to solve overlappings with other tags). This means, that identical tags will be joined when consolidating
     * API is used in the consolidation phase only
     * @return bool
     */
    public function isSplitable() : bool {
        return true;
    }
    /**
     * In the consolidation phase all obsolete segment tags will be discarded
     * API is used after the consolidation phase only
     * @return bool
     */
    public function isObsolete() : bool {
        return false;
    }
    /**
     * This method is called when in the end of the consolidation phase obsolete tags and paired-closers (that found no openers) are removed
     * It can be used to log stuff, throw exceptions, etc.
     * API is used after the consolidation phase only
     */
    public function onConsolidationRemoval() {
        
    }
    /**
     * Some Internal Tags are IMG-tags that are paired (parted into an opening and closing tag represented by images)
     * This API can be used after the consolidation to identify paired tags
     * @return bool
     */
    public function isPaired() : bool {
        return false;
    }
    /**
     * Some Internal Tags are IMG-tags that are paired (parted into an opening and closing tag represented by images)
     * These tags will be joined to one tag in the consolidation process.
     * API is used in the consolidation phase only
     * @return bool
     */
    public function isPairedOpener() : bool {
        return false;
    }
    /**
     * The counterpart to ::isPairedOpener()
     * API is used in the consolidation phase only
     * @return bool
     */
    public function isPairedCloser() : bool {
        return false;
    }
    /**
     * In the process of joining paired tags this API will be used. The passed tag will be removed when true is returned
     * API is used in the consolidation phase only
     * @param editor_Segment_Tag $tag
     * @return bool
     */
    public function pairWith(editor_Segment_Tag $tag) : bool {
        return false;
    }
    /**
     * Checks, if this segment tag can contain the passed segment tag
     * API is used in the rendering process only
     * @param editor_Segment_Tag $tag
     * @return boolean
     */
    public function canContain(editor_Segment_Tag $tag) : bool {
        if(!$this->isSingular()){
            if($this->startIndex <= $tag->startIndex && $this->endIndex >= $tag->endIndex){
                // when tag are aligned with our boundries it is unclear if they are inside or outside, so let's decide by the parentship on creation
                if(($tag->endIndex == $this->startIndex || $tag->startIndex == $this->endIndex)){
                    return ($tag->parentOrder == $this->order);
                }
                return true;
            }
        }
        return false;
    }
    /**
     * Finds the next container that can contain the passed tag
     * API is used in the rendering phase only
     * @param editor_Segment_Tag $tag
     * @return editor_Segment_Tag|NULL
     */
    public function getNearestContainer(editor_Segment_Tag $tag) : ?editor_Segment_Tag {
        if($this->canContain($tag)){
            return $this;
        }
        if($this->parent != null && is_a($this->parent, 'editor_Segment_Tag')){
            return $this->parent->getNearestContainer($tag);
        }
        return null;
    }
    /**
     * For rendering all tags are cloned and the orders need to be cloned as well
     * @param editor_Segment_Tag $from
     */
    public function cloneOrder(editor_Segment_Tag $from){
        $this->order = $from->order;
        $this->parentOrder = $from->parentOrder;
    }
    /**
     * Clones the tag to create the rendering model
     * API is used in the rendering phase only
     * @return editor_Segment_Tag
     */
    public function cloneForRendering(){
        $clone = $this->clone(true);
        $clone->cloneOrder($this);
        return $clone;
    }
    /**
     * Adds our rendering clone to the rendering queue
     * API is used in the rendering phase only
     * @param array $renderingQueue
     */
    public function addRenderingClone(array &$renderingQueue){
        $renderingQueue[] = $this->cloneForRendering();
    }
    /**
     * After the nested structure of tags is set this fills in the text-chunks of the segments text
     * CRUCIAL: at this point only editor_Segment_Tag must be added as children !
     * API is used in the rendering process only
     * @param editor_TagSequence $tags
     */
    public function addSegmentText(editor_TagSequence $tags){
        if($this->startIndex < $this->endIndex){
            if($this->hasChildren()){
                // crucial: we need to sort our children as tags with the same start-position but length 0 must come first
                usort($this->children, array('editor_TagSequence', 'compareChildren'));
                // fill the text-gaps around our children with text-parts of the segments & fill our children with text
                $chldrn = [];
                $last = $this->startIndex;
                foreach($this->children as $child){
                    if(is_a($child, 'editor_Segment_Tag')){
                        /* @var $child editor_Segment_Tag */
                        if($last < $child->startIndex){
                            $chldrn[] = editor_Tag::createText($tags->getTextPart($last, $child->startIndex));
                        }
                        $child->addSegmentText($tags);
                        $chldrn[] = $child;
                        $last = $child->endIndex;
                    }
                }
                if($last < $this->endIndex){
                    $chldrn[] = editor_Tag::createText($tags->getTextPart($last, $this->endIndex));
                }
                $this->children = $chldrn;
            } else {
                $this->addText($tags->getTextPart($this->startIndex, $this->endIndex));
            }
        }
    }

    /* Unparsing API */

    /**
     * Adds us and all our children to the segment tags
     * @param int $parentOrder
     */
    public function sequence(editor_TagSequence $tags, int $parentOrder){
        $tags->addTag($this, -1, $parentOrder);
        $this->sequenceChildren($tags, $this->order);
    }
    /**
     * Adds all our children to the segment tags
     * @param editor_TagSequence $tags
     * @param int $parentOrder
     */
    public function sequenceChildren(editor_TagSequence $tags, int $parentOrder=-1){
        if($this->hasChildren()){
            foreach($this->children as $child){
                if(is_a($child, 'editor_Segment_Tag')){
                    /* @var $child editor_Segment_Tag */
                    $child->sequence($tags, $parentOrder);
                }
            }
        }
    }
    /**
     * This API finishes the Field Tags generation. It is the final step and finishing work can be made here, e.g. the evaluation of task specific data
     * @param editor_TagSequence $tags
     * @param editor_Models_task $task
     */
    public function finalize(editor_TagSequence $tags, editor_Models_task $task){
        
    }

    /* Debugging API */

    /**
     * Debug output
     * @return string
     */
    public function debug(){
        $debug = '';
        $newline = "\n";
        $debug .= 'RENDERED: '.trim($this->render()).$newline;
        $debug .= 'START: '.$this->startIndex.' | END: '.$this->endIndex.' | FULLENGTH: '.($this->isFullLength?'true':'false').$newline;
        $debug .= 'DELETED: '.($this->wasDeleted?'true':'false').' | INSERTED: '.($this->wasInserted?'true':'false').$newline;
        return $debug;
    }

    public function debugProps() : string {
        return '['.$this->startIndex.'|'.$this->endIndex.'|'.$this->order.'|'.$this->parentOrder.']';
    }
}
