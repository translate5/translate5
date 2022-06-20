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

namespace MittagQI\Translate5\Segment\TagRepair;

/**
 * Abstraction for an Internal "repair tag" used with the automatic tag repair
 * This Tag is able to evaluate & store additional information about the tags position regarding words in the text, it represents an internal tag
 * While unparsing, these are full div tags holding their internal span as children
 * In a special prepare pairing phase, internal tags will add their inner tags as afterStart/beforeEnd Markup and turn to singular tags
 * In the pairing phase, the opener/closing tags will even be joined to a full tag back again to ensure, the repairing creates valid internal tags and thus turn back to full tags but one hierarchy level higher
 * the essential part of the prepare pairing phase is, that paired internal tags need to transfere the repair-index to the closing tag as otherwise they would render a wrong repair-index for the request markup
 */
class InternalTag extends Tag {

    protected static $type = \editor_Segment_Tag::TYPE_INTERNAL;

    /**
     * Internal flag that tells us, if we have already been requested and thus turned our internal tags to afterStart/beforeEndMarkup and we can be paired
     * @var bool
     */
    private bool $wasPrepared = false;
    /**
     * Internal flag that tells us, if we have been paired in the consolidation-phase
     * @var bool
     */
    private bool $paired = false;
    /**
     * The Index of the internal tag.
     * @var int
     */
    private int $tagIndex = -1;

    /**
     * retrieves the internal tag index
     * @return int
     */
    public function getTagIndex(){
        return $this->tagIndex;
    }
    /**
     * As soon as our internal spans are added we act as singular tags
     * {@inheritDoc}
     * @see \editor_Tag::isSingular()
     */
    public function isSingular() : bool {
        if($this->wasPrepared){
            return !$this->paired;
        }
        return false;
    }
    /**
     * Internal tags must not be splitted nor joined !
     * {@inheritDoc}
     * @see \editor_Segment_Tag::isSplitable()
     */
    public function isSplitable() : bool {
        return false;
    }
    /**
     * Must be overwritten to ensure, the internal tag does add it's text nor text-length to the field-tags
     * {@inheritDoc}
     * @see \editor_Tag::getText()
     */
    public function getText(){
        return '';
    }
    /**
     * Must be overwritten to ensure, the internal tag does add it's text nor text-length to the field-tags
     * {@inheritDoc}
     * @see \editor_Tag::getTextLength()
     */
    public function getTextLength(){
        return 0;
    }
    /**
     * Must be overwritten to ensure, the internal tag does add it's text nor text-length to the field-tags
     * {@inheritDoc}
     * @see \editor_Tag::getLastChildsTextLength()
     */
    public function getLastChildsTextLength() : int {
        return 0;
    }
    /**
     * {@inheritDoc}
     * @see \editor_Tag::createBaseClone()
     * @return InternalTag|Tag
     */
    protected function createBaseClone(){
        return new InternalTag($this->startIndex, $this->endIndex, $this->category, $this->name);
    }

    /* Consolidation API */

    /**
     * Retrieves, if the tag potentially can be paired in the pairing phase
     * @return bool
     */
    public function canBePaired() : bool {
        return true;
    }
    /**
     * This API is called before consolidation and before rendering for request
     * It de-objectifies our children as afterStart markup and evaluates the internal tagIndex
     * In case this tag is no real internal-tag (but has the class, what theoretically can happen for any markup, our contents then would not be translated
     * @param Tag $tag
     */
    public function preparePairing(){
        // prepare our children
        if($this->hasChildren()){
            foreach($this->children as $child){
                // extract our tag-index from our short content tag
                if($child->hasClass(\editor_Segment_Internal_ContentTag::CSS_CLASS_SHORT)){
                    $contentTag = \editor_Segment_Internal_ContentTag::fromTag($child);
                    $this->tagIndex = $contentTag->getTagIndex();
                }
                $this->afterStartMarkup .= $child->render();
            }
        }
        $this->children = [];
        $this->wasPrepared = true;
    }
    /**
     * This API is called before consolidation and before rendering for request
     * @param Tag $tag
     */
    public function prePairWith(Tag $tag){
        if(is_a($tag, 'MittagQI\Translate5\Segment\TagRepair\InternalTag') && $this->tagIndex == $tag->getTagIndex()){
            // In case we found our pairing counterpart we must set it's tag index to ours to ensure, the request is rendered with proper indexes
            $tag->setRepairIndex($this->repairIdx);
        }
    }
    /**
     * Retrieves, if we have been paired
     * @return bool
     */
    public function isPaired() : bool {
        return $this->paired;
    }
    /**
     * Retrieves, if we are searching for our counterpart in the consolidation phase
     * @return bool
     */
    public function isPairedOpener() : bool {
        return ($this->tagIndex > -1 && $this->hasClass(\editor_Segment_Internal_Tag::CSS_CLASS_OPEN));
    }
    /**
     * Retrieves, if we are a potential counterpart
     * @return bool
     */
    public function isPairedCloser() : bool {
        return ($this->tagIndex > -1 && $this->hasClass(\editor_Segment_Internal_Tag::CSS_CLASS_CLOSE));
    }
    /**
     * The passed $tag is a tag if the same type and is a paired closer
     * This is the place where a un-paired internal tag changes to a full paired one
     * {@inheritDoc}
     * @see editor_Segment_Tag::pairWith()
     */
    public function pairWith(\editor_Segment_Tag $tag) : bool {
        if(is_a($tag, 'MittagQI\Translate5\Segment\TagRepair\InternalTag') && $this->tagIndex == $tag->getTagIndex()){
            $this->paired = true;
            $this->endIndex = $tag->startIndex;
            $this->rightOrder = $tag->order;
            $this->beforeEndMarkup = $tag->render();
            return true;
        }
        return false;
    }

    /* Request Rendering API */

    /**
     * We will mimic a singular tag for requests
     * @return string
     */
    public function renderForRequest() : string {
        // render our request-tags
        if($this->isPairedOpener()){
            return $this->renderRequestTag('start');
        } else if($this->isPairedCloser()){
            return $this->renderRequestTag('end');
        } else {
            return $this->renderRequestTag('singular');
        }
    }
    /**
     * renders the children for request
     * @return string
     */
    public function renderChildrenForRequest() : string {
        return '';
    }

    /* Rendering API */

    /**
     * We will render the complete tag for normal rendering if handled
     * @param bool $withDataAttribs
     * @return string
     */
    protected function renderStart(bool $withDataAttribs=true) : string {
        return '<' . $this->getName() . $this->renderAttributes($withDataAttribs) . '>' . $this->afterStartMarkup . '</' . $this->getName() . '>';
    }
    /**
     * May returns the closing internal tag in case of a paired internal tag
     * @return string
     */
    protected function renderEnd() : string {
        return $this->beforeEndMarkup;
    }
}
