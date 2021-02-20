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
 * The inner Markup will be stored as a HTML-String to avoid having those structures in the holding FieldTags
 * In the Future we may change that and store our inner HTML as Objects
 */
final class  editor_Segment_Internal_Tag extends editor_Segment_Tag {
    
    const CSS_CLASS = 'internal-tag';
    
    protected static $type = editor_Segment_Tag::TYPE_INTERNAL;

    protected static $nodeName = 'div';

    /**
     * 
     * @var string
     */
    private $innerHTML = NULL;
    /**
     * Needed for cloneing
     * @param string $html
     */
    protected function setInnerHTML($html){
        $this->innerHTML = $html;
    }
    /**
     * Needed for comparing
     * @param string $html
     */
    protected function getInnerHTML($html){
        return $this->innerHTML;
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
        if(parent::isEqual($tag, $withDataAttribs) && get_class($tag) == get_class($this)){
            return $tag->getInnerHTML() == $this->innerHTML;
        }
        return false;
    }
    
    public function getText(){
        if($this->innerHTML !== null){
            return strip_tags($this->innerHTML);
        }
        return parent::getText();
    }
    
    public function getTextLength(){
        if($this->innerHTML !== null){
            return mb_strlen(strip_tags($this->innerHTML));
        }
        return parent::getTextLength();
    }
    /**
     * This renders our inner HTML
     * {@inheritDoc}
     * @see editor_Tag::renderChildren()
     */
    public function renderChildren(array $skippedTypes=NULL) : string {
        return $this->innerHTML;
    }
    /**
     * We do not add children to th etags-container but we build our inner HTML in that case
     * {@inheritDoc}
     * @see editor_Segment_Tag::sequenceChildren()
     */
    public function sequenceChildren(editor_Segment_FieldTags $tags){
        $this->innerHTML = '';
        if($this->hasChildren()){
            foreach($this->children as $child){
                $this->innerHTML .= $child->render();
            }
        }
        $this->children = [];
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
    
    public function clone($withDataAttribs=false){
        $clone = parent::clone($withDataAttribs);
        /* @var $clone editor_Segment_Internal_Tag */
        $clone->setInnerHTML($this->innerHTML);
        return $clone;
    }
    
    
    protected function furtherSerialize(stdClass $data){
        $data->innerHTML = $this->innerHTML;
    }
    
    protected function furtherUnserialize(stdClass $data){
        if(property_exists($data, 'innerHTML')){
            $this->innerHTML = $data->innerHTML;
        }
    }
}
