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
 * Represents an Internal tag
 * Example <div class="single 123 internal-tag ownttip"><span title="&lt;ph ax:element-id=&quot;0&quot;&gt;&amp;lt;variable linkid=&quot;123&quot; name=&quot;1002&quot;&amp;gt;Geräte, Detailmaß A&amp;lt;/variable&amp;gt;&lt;/ph&gt;" class="short">&lt;1/&gt;</span><span data-originalid="6f18ea87a8e0306f7c809cb4f06842eb" data-length="-1" class="full">&lt;ph id=&quot;1&quot; ax:element-id=&quot;0&quot;&gt;&amp;lt;variable linkid=&quot;123&quot; name=&quot;1002&quot;&amp;gt;Geräte Detailmaß A&amp;lt;/variable&amp;gt;&lt;/ph&gt;</span></div>
 * The inner Content Tags are stored as special Tags editor_Segment_Internal_ContentTag
 * 
 * @method editor_Segment_Internal_Tag clone(boolean $withDataAttribs)
 * @method editor_Segment_Internal_Tag createBaseClone()
 */
final class  editor_Segment_Internal_Tag extends editor_Segment_Tag {
    
    /**
     * @var string
     */
    const CSS_CLASS = 'internal-tag';
    /**
     * @var string
     */
    const CSS_CLASS_SINGLE = 'single';
    /**
     * @var string
     */
    const CSS_CLASS_OPEN = 'open';
    /**
     * @var string
     */
    const CSS_CLASS_CLOSE = 'close';
    /**
     * @var string
     */
    const CSS_CLASS_NBSP = 'nbsp';
    /**
     * @var string
     */
    const CSS_CLASS_NEWLINE = 'newline';
    /**
     * @var string
     */
    const CSS_CLASS_SPACE = 'space';
    /**
     * @var string
     */
    const CSS_CLASS_TAB = 'tab';
    
    protected static $type = editor_Segment_Tag::TYPE_INTERNAL;

    protected static $nodeName = 'div';
    
    protected static $identificationClass = self::CSS_CLASS;
    /**
     * 
     * @var editor_Segment_Internal_ContentTag[]
     */
    private $contentTags = NULL;
    /**
     * 
     * @var editor_Segment_Internal_ContentTag
     */
    private $shortTag = NULL;
    /**
     *
     * @var editor_Segment_Internal_ContentTag
     */
    private $fullTag = NULL;
    /**
     * API needed for cloning
     * @param editor_Segment_Internal_ContentTag $tag
     */
    private function addContentTag(editor_Segment_Internal_ContentTag $tag){
        if($this->contentTags === NULL){
            $this->contentTags = [];
        }
        $this->contentTags[] = $tag;
        if($tag->isShort()){
            $this->shortTag = $tag;
        } else if($tag->isFull()){
            $this->fullTag = $tag;
        }
    }
    /**
     * Evaluates if we wrap/represent a single HTML Tag of the segments content
     * @return boolean
     */
    public function isSingle(){
        return $this->hasClass(self::CSS_CLASS_SINGLE);
    }
    /**
     * Evaluates if we wrap/represent a opening HTML Tag of the segments content
     * @return boolean
     */
    public function isOpening(){
        return $this->hasClass(self::CSS_CLASS_OPEN);
    }
    /**
     * Evaluates if we wrap/represent a closing HTML Tag of the segments content
     * @return boolean
     */
    public function isClosing(){
        return $this->hasClass(self::CSS_CLASS_CLOSE);
    }
    /**
     * Evaluates, if the internal tag represents a whitespace tag
     * @return boolean
     */
    public function isWhitespace(){
        return ($this->isSingle() && ($this->hasClass(self::CSS_CLASS_NEWLINE) || $this->hasClass(self::CSS_CLASS_NBSP) || $this->hasClass(self::CSS_CLASS_SPACE) || $this->hasClass(self::CSS_CLASS_TAB)));
    }
    /**
     * Retrieves the original index of the internal tag within the segment
     * @return int
     */
    public function getTagIndex(){
        if($this->shortTag != NULL){
            return $this->shortTag->getTagIndex();
        }
        return -1;
    }
    /**
     * 
     * @return string|NULL
     */
    public function getOriginalId(){
        if($this->fullTag != NULL && $this->fullTag->hasData('originalid')){
            return $this->fullTag->getData('originalid');
        }
        return NULL;
    }
    /**
     * 
     * @return int
     */
    public function getContentLength(){
        if($this->fullTag != NULL && $this->fullTag->hasData('length')){
            return $this->fullTag->getData('length');
        }
        return 0;
    }
    /**
     * Retrieves a hash that can be used to compare tags
     * @return string
     */
    public function getHash(){
        return md5($this->render());
    }
    
    /* *************************************** Overwritten Tag API *************************************** */
    
    /**
     * Internal tags must not be splitted nor joined !
     * {@inheritDoc}
     * @see editor_Segment_Tag::isSplitable()
     */
    public function isSplitable() : bool {
        return false;
    }
    /**
     * Internal tags are only equal when their content is equal as well
     * {@inheritDoc}
     * @see editor_Tag::isEqual()
     */
    public function isEqual(editor_Tag $tag, bool $withDataAttribs=true) : bool {
        if(parent::isEqual($tag, $withDataAttribs)){
            return $tag->renderChildren() == $this->renderChildren();
        }
        return false;
    }
    
    public function getText(){
        if($this->contentTags === null){
            return parent::getText();
        }
        $text = '';
        foreach($this->contentTags as $contentTag){
            $text .= $contentTag->getText();
        }
        return $text;
    }

    public function getTextLength(){
        if($this->contentTags === null){
            return parent::getTextLength();
        }
        $length = 0;
        foreach($this->contentTags as $contentTag){
            $length += $contentTag->getTextLength();
        }
        return $length;
    }
    /**
     * This renders our inner HTML
     * {@inheritDoc}
     * @see editor_Tag::renderChildren()
     */
    public function renderChildren(array $skippedTypes=NULL) : string {
        if($this->contentTags === null){
            return parent::renderChildren($skippedTypes);
        }
        $html = '';
        foreach($this->contentTags as $contentTag){
            $html .= $contentTag->render();
        }
        return $html;
    }
    /**
     * We do not add children to the tags-container but we build our inner tags from the tags-structure
     * {@inheritDoc}
     * @see editor_Segment_Tag::sequenceChildren()
     */
    public function sequenceChildren(editor_Segment_FieldTags $tags, int $parentOrder=-1){
        if($this->hasChildren()){
            foreach($this->children as $child){
                $this->addContentTag(editor_Segment_Internal_ContentTag::fromTag($child));
            }
        }
    }
    /**
     * Handled internally
     * {@inheritDoc}
     * @see editor_Segment_Tag::addSegmentText()
     */
    public function addSegmentText(editor_Segment_FieldTags $tags){
        if($this->startIndex < $this->endIndex){
            $this->addText($tags->getFieldTextPart($this->startIndex, $this->endIndex));
        }
    }
    
    public function clone(bool $withDataAttribs=false, bool $withId=false){
        $clone = parent::clone($withDataAttribs, $withId);
        /* @var $clone editor_Segment_Internal_Tag */
        foreach($this->contentTags as $contentTag){
            $clone->addContentTag($contentTag->clone(true));
        }
        return $clone;
    }

    protected function furtherSerialize(stdClass $data){
        $data->contentTags = [];
        foreach($this->contentTags as $contentTag){
            $data->contentTags[] = $contentTag->jsonSerialize();
        }
    }
    
    protected function furtherUnserialize(stdClass $data){
        if(property_exists($data, 'contentTags')){
            foreach($data->contentTags as $data){
                $this->addContentTag(editor_Segment_Internal_ContentTag::fromJsonData($data));
            }
        }
    }
}
