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
 * Abstraction for an Internal tag as used in the segment text's
 * This adds serialization/unserialization-capabilities (JSON), cloning capabilities and the general managability of internal tags
 * Generally internal tags must be used with start & end indice relative to the segment texts.
 * Keep in mind that start & end-index work just like counting chars or the substr API in php, the tag starts BEFORE the start index and ends BEFORE the index of the end index, if you want to cover the whole segment the indices are 0 and mb_strlen($segment)
 * To identify the Types of Internal tags a general API editor_Segment_TagCreator is provided
 * 
 * @method editor_Segment_Tag clone(boolean $withDataAttribs)
 * @method editor_Segment_Tag createBaseClone()
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
    public static function fromJson($jsonString) : editor_Segment_Tag {
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
        $tag = new self($startIndex, $endIndex, $category);
        $tag->addClass($tag->getIdentificationClass());
        return $tag;
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
     * Set by editor_Segment_FieldTags to indicate that the Tag spans the complete segment text
     * @var bool
     */
    public $isFullLength;
    /**
     * References the field the Tag belongs to
     * This property is only set, if the tag is part of a FieldTags container and will not be serialized  !
     * @var string
     */
    public $field = null;
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
        if($skippedTypes != NULL && is_array($skippedTypes) && in_array($this->getType(), $skippedTypes)){
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
    public function setCategory(string $category) : string {
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
            if(!array_key_exists('data-'.self::DATA_NAME_QUALITYID, $this->attribs)){
                $this->attribs['data-'.self::DATA_NAME_QUALITYID] = $this->attribs['data-'.static::$historicDataNameQid];
            }
            unset($this->attribs['data-'.static::$historicDataNameQid]);
            return ctype_digit($this->getData(self::DATA_NAME_QUALITYID));
        }
        return ($this->hasData(self::DATA_NAME_QUALITYID) && ctype_digit($this->getData(self::DATA_NAME_QUALITYID)));
    }
    /**
     * Retrieves the quality ID. If not encoded in the tag, returns -1
     * @return int
     */
    public function getQualityId() : int {
        if($this->hasQualityId()){
            return intval($this->getData(self::DATA_NAME_QUALITYID));
        }
        return -1;
    }
    /**
     * 
     * @param int $qualityId
     * @return editor_Tag|editor_Segment_Tag
     */
    public function setQualityId(int $qualityId) {
        return $this->setData(self::DATA_NAME_QUALITYID, strval($qualityId));
        return $this;
    }
    /**
     * Retrieves the data name for the quality entity id
     * @return string
     */
    public function getDataNameQualityId() : string {
        return self::DATA_NAME_QUALITYID;
    }
    /**
     * 
     * @return bool
     */
    public function isFalsePositive() : bool {
        return $this->hasClass(self::CSS_CLASS_FALSEPOSITIVE);
    }
    /**
     * 
     * @param int $falsePositive: database value as from LEK_segment_quality
     * @return editor_Segment_Tag
     */
    public function setFalsePositive(int $falsePositive=1) {
        if($falsePositive == 1){
            $this->addClass(self::CSS_CLASS_FALSEPOSITIVE);
        } else {
            $this->removeClass(self::CSS_CLASS_FALSEPOSITIVE);
        }
        return $this;
    }

    public function jsonSerialize(){
        $data = new stdClass();
        $data->type = static::$type;
        $data->name = $this->getName();
        $data->category = $this->getCategory();
        $data->startIndex = $this->startIndex;
        $data->endIndex = $this->endIndex;
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
     * Evaluates, if the tag can be splitted (interleaved with other tags). Apart from internal tags this is the case for all other tags
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
     * Checks, if this internal tag can contain the passed internal tag
     * API is used in the rendering process only
     * @param editor_Segment_Tag $tag
     * @return boolean
     */
    public function canContain(editor_Segment_Tag $tag){
        if(!$this->isSingular()){
            if($this->startIndex <= $tag->startIndex && $this->endIndex >= $tag->endIndex){
                return true;
            }
        }
        return false;
    }
    /**
     * Finds the next container that can contain the passed tag
     * API is used in the rendering process only
     * @param editor_Segment_Tag $tag
     * @return editor_Segment_Tag|NULL
     */
    public function getNearestContainer(editor_Segment_Tag $tag){
        if($this->canContain($tag)){
            return $this;
        }
        if($this->parent != null && is_a($this->parent, 'editor_Segment_Tag')){
            return $this->parent->getNearestContainer($tag);
        }
        return null;
    }
    /**
     * After the nested structure of tags is set this fills in the text-chunks of the segments text
     * CRUCIAL: at this point only editor_Segment_Tag must be added as children !
     * API is used in the rendering process only
     * @param editor_Segment_FieldTags $tags
     */
    public function addSegmentText(editor_Segment_FieldTags $tags){
        if($this->startIndex < $this->endIndex){
            if($this->hasChildren()){
                // fil the text-gaps around our children with text-parts of the segments & fill our children with text
                $chldrn = [];
                $last = $this->startIndex;
                foreach($this->children as $child){
                    if(is_a($child, 'editor_Segment_Tag')){
                        /* @var $child editor_Segment_Tag */
                        if($last < $child->startIndex){
                            $chldrn[] = editor_Tag::createText($tags->getFieldTextPart($last, $child->startIndex));
                        }
                        $child->addSegmentText($tags);
                        $chldrn[] = $child;
                        $last = $child->endIndex;
                    }
                }
                if($last < $this->endIndex){
                    $chldrn[] = editor_Tag::createText($tags->getFieldTextPart($last, $this->endIndex));
                }
                $this->children = $chldrn;
            } else {
                $this->addText($tags->getFieldTextPart($this->startIndex, $this->endIndex));
            }
        }
    }
    /**
     * Adds us and all our children to the segment tags
     * @param editor_Segment_FieldTags $tags
     */
    public function sequence(editor_Segment_FieldTags $tags){
        $tags->addTag($this);
        $this->sequenceChildren($tags);
    }
    /**
     * Adds all our children to the segment tags
     * @param editor_Segment_FieldTags $tags
     */
    public function sequenceChildren(editor_Segment_FieldTags $tags){
        if($this->hasChildren()){
            foreach($this->children as $child){
                if(is_a($child, 'editor_Segment_Tag')){
                    /* @var $child editor_Segment_Tag */
                    $child->sequence($tags);
                }
            }
        }
    }
}
