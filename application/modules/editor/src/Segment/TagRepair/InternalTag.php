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
     * Internal flag that tells us, if in the unparsing phace we successfully intercepted the unparsing of tags
     * If not, then as a fallback we act just as a normal repairTag
     * @var bool
     */
    private bool $wasHandled = false;

    public function isSingular() : bool {
        return ($this->singular || $this->wasHandled);
    }
    /**
     * Overwritten to ensure, all APIs work as expected
     * @return bool
     */
    public function hasChildren(){
        if($this->wasHandled){
            return false;
        }
        return parent::hasChildren();
    }
    /**
     * {@inheritDoc}
     * @see \editor_Tag::createBaseClone()
     * @return InternalTag|Tag
     */
    protected function createBaseClone(){
        if($this->wasHandled){
            return new InternalTag($this->startIndex, $this->endIndex, $this->category, $this->name);
        }
        return new Tag($this->startIndex, $this->endIndex, $this->category, $this->name);
    }
    /**
     * @param Tags $tags
     * @return int
     */
    public function getNumWords(Tags $tags) : int {
        if($this->wasHandled){
            return 0;
        }
        return parent::getNumWords($tags);
    }


    /* unparsing API */

    /**
     * We intercept the unparsing and save the passed nodes as our internal content
     * It is ensured here, that two span's are passed...
     * @param \DOMNodeList $domElementChildren
     * @param editor_TagSequence $tagSequence
     * @return bool
     */
    public function handleDomElementChildrenInternally(\DOMNodeList $domElementChildren, \editor_TagSequence $tagSequence) : bool {
        try {
            $span1 = $tagSequence->createUnchainedTagFromDomElement($domElementChildren->item(0));
            $span2 = $tagSequence->createUnchainedTagFromDomElement($domElementChildren->item(1));
            $this->afterStartMarkup = $span1->render().$span2->render();
            $this->wasHandled = true;
            $this->singular = true;
            return true;
        } catch (Exception $e) {
            // we will act as a normal Tag when we could not intercept the children
            return false;
        }
    }
    /**
     * We intercept the unparsing and save the passed nodes as our internal content
     * It is ensured here, that two span's are passed...
     * @param array|null $htmlNodeChildren
     * @param editor_TagSequence $tagSequence
     * @return bool
     */
    public function handleHtmlNodeChildrenInternally(array $htmlNodeChildren, \editor_TagSequence $tagSequence) : bool {
        try {
            $span1 = $tagSequence->createUnchainedTagFromHtmlNode($htmlNodeChildren[0]);
            $span2 = $tagSequence->createUnchainedTagFromHtmlNode($htmlNodeChildren[1]);
            $this->afterStartMarkup = $span1->render().$span2->render();
            $this->wasHandled = true;
            $this->singular = true;
            return true;
        } catch (Exception $e) {
            // we will act as a normal Tag when we could not intercept the children
            return false;
        }
    }

    /* Request Rendering API */

    /**
     * We will mimic a singular tag for requests
     * @return string
     */
    public function renderForRequest() : string {
        if($this->wasHandled){
            return $this->renderRequestTag('singular');
        }
        return parent::renderForRequest();
    }
    /**
     * renders the children for request
     * @return string
     */
    public function renderChildrenForRequest() : string {
        if($this->wasHandled){
            return '';
        }
        return parent::renderChildrenForRequest();
    }

    /* Rendering API */

    /**
     * We will render the complete tag for normal rendering if handled
     * @param bool $withDataAttribs
     * @return string
     */
    protected function renderStart(bool $withDataAttribs=true) : string {
        if($this->wasHandled) {
            return '<' . $this->getName() . $this->renderAttributes($withDataAttribs) . '>' . $this->afterStartMarkup . '</' . $this->getName() . '>';
        }
        return parent::renderStart($withDataAttribs);
    }
    /**
     * No end-tag needed if handled
     * @return string
     */
    protected function renderEnd() : string {
        if($this->wasHandled) {
            return '';
        }
        return parent::renderEnd();
    }
    /**
     * No renderable children if handled
     * @param array|null $skippedTypes
     * @return string
     */
    public function renderChildren(array $skippedTypes=NULL) : string {
        if($this->wasHandled) {
            return '';
        }
        return parent::renderChildren($skippedTypes);
    }
}
