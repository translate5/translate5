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
 */
class InternalTag extends Tag {

    /**
     * Internal flag that tells us, if we have already been sequenced
     * @var bool
     */
    private bool $wasSequenced = false;

    /**
     * As soon as our internal spans are added we act as singular tags
     * {@inheritDoc}
     * @see editor_Tag::isSingular()
     */
    public function isSingular() : bool {
        return ($this->wasSequenced);
    }
    /**
     * Internal tags must not be splitted nor joined !
     * {@inheritDoc}
     * @see editor_Segment_Tag::isSplitable()
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
     * Must be overwritten to ensure it's working correct within the Repair Tags logic
     * @param Tags $tags
     * @return int
     */
    public function getNumWords(Tags $tags) : int {
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

    /* Sequencing API */

    /**
     * We process all our children here as internal/lateral markup
     * {@inheritDoc}
     * @see Tag::prepareSequencing()
     */
    public function prepareSequencing(){
        $this->afterStartMarkup = '';
        if($this->hasChildren()){
            foreach($this->children as $child){
                $this->afterStartMarkup .= $child->render();
            }
        }
        $this->children = [];
        $this->wasSequenced = true;
    }

    /* Request Rendering API */

    /**
     * We will mimic a singular tag for requests
     * @return string
     */
    public function renderForRequest() : string {
        return $this->renderRequestTag('singular');
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
     * No end-tag needed if handled
     * @return string
     */
    protected function renderEnd() : string {
        return '';
    }
}
