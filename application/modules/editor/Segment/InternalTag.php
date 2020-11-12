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
 * This adds serialization-capabilities but keep in mind that the serialization will not take the children (nested structures) into account
 * Keep in mind that start & end-index work just like counting chars in php, if you want to cover the whole segment the indices are 0 and strlen($segment) 
 * 
 * @method editor_Segment_InternalTag clone(boolean $withDataAttribs)
 * @method editor_Segment_InternalTag createBaseClone()
 * @method editor_Segment_InternalTag cloneProps(editor_Tag $tag, boolean $withDataAttribs)
 */
class editor_Segment_InternalTag extends editor_Tag implements JsonSerializable {
    
    /**
     * @var string
     */
    const TYPE_QUALITYCONTROL = 'QC';
    /**
     * @var string
     */
    const TYPE_ANY = 'ANY';
    /**
     * The counterpart to ::toJson: creates the tag from the serialized json data
     * @param string $jsonString
     * @throws Exception
     * @return editor_Segment_InternalTag
     */
    public static function fromJson($jsonString) : editor_Segment_InternalTag {
        try {
            $data = json_decode($jsonString);
            $tag = ZfExtended_Factory::get($data->phpclass, [$data->startIndex, $data->endIndex, $data->category]);
            /* @var $tag editor_Segment_InternalTag */
            $tag->setFromJson($data);
            return $tag;
        } catch (Exception $e) {
            throw new Exception('Could not deserialize editor_Segment_InternalTag from JSON-Object '.json_encode($data));
        }
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
            throw new Exception('Direct instantiation of editor_Segment_InternalTag is not appropriate, type and nodeName must not be NULL');
        }
        parent::__construct(static::$nodeName);
        $this->startIndex = $startIndex;
        $this->endIndex = $endIndex;
        $this->category = $category;
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
     * @return editor_Segment_InternalTag
     */
    protected function createBaseClone(){
        return ZfExtended_Factory::get(get_class($this), [$this->startIndex, $this->endIndex, $this->category]);
    }
    /**
     * Opposing to the base-class the node-name with internal taxt is fixed with the type usually (not with editor_Segment_AnyInternalTag ...)
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
     * @return string
     */
    public function getCategory() : string {
        return $this->category;
    }
    
    public function jsonSerialize(){
        $data = new stdClass();
        $data->phpclass = get_class($this);
        $data->type = static::$type;
        $data->category = $this->getCategory();
        $data->startIndex = $this->startIndex;
        $data->endIndex = $this->endIndex;
        $data->classes = $this->classes;
        $data->attribs = $this->attribs;
        $this->furtherSerialize($data);
        return $data;
    }
    /**
     * 
     * @param stdClass $data
     */
    protected function setFromJson(stdClass $data){
        $this->category = $data->category;
        $this->startIndex = $data->startIndex;
        $this->endIndex = $data->endIndex;
        $this->classes = $data->classes;
        $this->attribs = $data->attribs;
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
     * Checks, if this internal tag can contain the passed internal tag
     * @param editor_Segment_InternalTag $tag
     * @return boolean
     */
    public function canContain(editor_Segment_InternalTag $tag){
        if($this->startIndex <= $tag->startIndex && $this->endIndex >= $tag->endIndex){
            return true;
        }
        return false;
    }
    /**
     * Finds the next container that can contain the passed tag
     * @param editor_Segment_InternalTag $tag
     * @return editor_Segment_InternalTag|NULL
     */
    public function getNearestContainer(editor_Segment_InternalTag $tag){
        if($this->canContain($tag)){
            return $this;
        }
        if($this->parent != null && is_a($this->parent, 'editor_Segment_InternalTag')){
            return $this->parent->getNearestContainer($tag);
        }
        return null;
    }
    /**
     * Finds the Topmost container
     * @return editor_Tag|editor_Segment_InternalTag
     */
    public function getTopmostContainer(){
        if($this->parent != null && is_a($this->parent, 'editor_Segment_InternalTag')){
            return $this->parent->getTopmostContainer($tag);
        }
        return $this;
    }
    /**
     * After the nested structure of tags is set this fills in the text-chunks of the segments text
     * CRUCIAL: at this point only editor_Segment_InternalTag must be added as children !
     * @param editor_Segment_Tags $tags
     */
    public function addSegmentText(editor_Segment_Tags $tags){
        if($this->startIndex < $this->endIndex){
            if($this->hasChildren()){
                // fil the text-gaps around our children with text-parts of the segments & fill our children with text
                $chldrn = [];
                $last = $this->startIndex;
                foreach($this->children as $child){
                    /* @var $child editor_Segment_InternalTag */
                    if($last < $child->startIndex){
                        $chldrn[] = editor_Tag::createText($tags->getSegmentTextPart($last, $child->startIndex));
                    }
                    $child->addSegmentText($tags);
                    $chldrn[] = $child;
                    $last = $child->endIndex;
                }
                if($last < $this->endIndex){
                    $chldrn[] = editor_Tag::createText($tags->getSegmentTextPart($last, $this->endIndex));
                }
                $this->children = $chldrn;
            } else {
                $this->addText($tags->getSegmentTextPart($this->startIndex, $this->endIndex));
            }
        }
    }
}
