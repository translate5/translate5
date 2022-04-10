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
 * General Extension to use te FieldTags Model for a general Tag-Repair (not specific to internal or xliff tags)
 *
 * @method editor_Segment_TagRepair_Tag unparseHtml(string $html)
 * @property editor_Segment_TagRepair_Tag[] $tags
 */
class editor_Segment_TagRepair_Tags extends editor_TagSequence {

    const DO_DEBUG = false;
    /**
     * Each tag must have a unique id to be re-identifyable
     * @var int
     */
    private int $tagIdxCount = 0;
    /**
     * Holds the Markup send by request
     * @var string
     */
    private string $requestHtml;
    /* general API */

    /**
     * Creates a new tag-repair for the given markup
     * The given markup must be syntactically valid markup !
     * @param string $markup
     * @throws ZfExtended_Exception
     */
    public function __construct(string $markup) {
        $this->_setMarkup($markup);
    }
    /**
     * Retrieves the HTML prepared to be sent by request
     * @return string
     */
    public function getRequestHtml() : string {
        return $this->requestHtml;
    }
    /**
     * Provides the returned html from request and in return get's the fixed and re-applied markup
     * @param string $html
     * @return string
     * @throws ZfExtended_Exception
     */
    public function recreateTags(string $html) : string {
        $this->reEvaluate($html);
        try {
            return $this->render();
        } catch (Exception $e) {
            // TODO: what to do here ?
            return '';
        }
    }

    /* re-evaluation API */

    /**
     * Re-evaluate our tags by the passed markup
     * @param string $html
     */
    private function reEvaluate(string $html) {
        $numTags = count($this->tags);
        if($numTags < 1){
            $this->setText(strip_tags($html));
            return;
        }
        // capture the word-positions of our tags and invalidate the text-indices
        $textLengthBefore = $this->getTextLength();
        $numWordsBefore = $this->countWords($this->text);

        if(self::DO_DEBUG){
            error_log('RE-EVALUATE: chars before: ' .$textLengthBefore.', words before: '.$numWordsBefore.', num tags before: '.$numTags);
            for($i=0; $i < $numTags; $i++){
                error_log('RE-EVALUATE before tag '.$i.': ( idx: '.$this->tags[$i]->getTagIndex().' | start: '.$this->tags[$i]->startIndex.' | end: '.$this->tags[$i]->endIndex.' | num words: '.$this->tags[$i]->getNumWords($this).')');
            }
        }

        for($i=0; $i < $numTags; $i++){
            $this->tags[$i]->capturePosition($this, $textLengthBefore);
        }
        $text = '';
        $textLength = 0;
        $parts = preg_split(editor_Segment_TagRepair_Tag::REQUEST_TAG_REGEX, $html, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        foreach($parts as $part){
            if(preg_match(editor_Segment_TagRepair_Tag::REQUEST_TAG_REGEX, $part) === 1){
                // a tag
                try {
                    // decompose the tag, errors will be catched
                    $tagParts = explode('-', explode('"', $part)[1]);
                    $tagType = $tagParts[1]; // can be "start", "end" or "singular"
                    $tag = $this->findByTagIdx(intval($tagParts[2]));
                    if($tag != NULL && ($tagType == 'start' || $tagType == 'end' || $tagType == 'singular')){
                        if($tagType == 'start' || $tagType == 'singular'){
                            $tag->startIndex = $textLength;
                        }
                        if($tagType == 'end' || $tagType == 'singular'){
                            $tag->endIndex = $textLength;
                        }
                    }
                } catch (Exception $e) {
                    // we simply ignore an unparsable tag, what can we do ?
                    error_log('editor_Segment_TagRepair_Tags: could not evaluate the request-tag '.$part);
                }
            } else {
                // a text
                $text .= strval(strip_tags($part));
                $textLength = mb_strlen($text);
            }
        }
        $this->setText($text);
        // re-evaluate the word-positions of our tags and restore the text-indices
        $textLength = $this->getTextLength();
        $wordRatio = $this->countWords($this->text) / $numWordsBefore;
        $textRatio = $textLength / $textLengthBefore;
        // first the "real" non-singular tags
        for($i=0; $i < $numTags; $i++){
            if(!$this->tags[$i]->isSingular()){
                $this->tags[$i]->reEvaluateTagPosition($this, $textLength, $wordRatio, $textRatio);
            }
        }
        // first the singular tags (which may refer to the normal tags as their parent)
        for($i=0; $i < $numTags; $i++){
            if($this->tags[$i]->isSingular()){
                $this->tags[$i]->reEvaluateSingularTagPosition($this, $textLength, $wordRatio, $textRatio);
            }
        }
        if(self::DO_DEBUG){
            error_log('RE-EVALUATE: chars after: ' .$textLength.', words after: '.$this->countWords($this->text).', num tags after: '.$numTags);
            for($i=0; $i < $numTags; $i++){
                error_log('RE-EVALUATE recreated tag '.$i.': ( idx: '.$this->tags[$i]->getTagIndex().' | start: '.$this->tags[$i]->startIndex.' | end: '.$this->tags[$i]->endIndex.' | num words: '.$this->tags[$i]->getNumWords($this).')');
            }
        }
    }
    /**
     * Helper API to count the words in a string. str_word_count cannot be taken since it does not support multibyte locales
     * @param string $text
     * @return int
     */
    public function countWords(string $text) : int {
        return count(explode(' ', preg_replace('/\s+/', ' ', trim($text))));
    }
    /**
     * get a text-position the given number of words to the right
     * @param int $pos
     * @param int $words
     * @param bool $afterWhitespace
     * @return int
     */
    public function getNextWordsPosition(int $pos, int $words, bool $afterWhitespace=false) : int {
        if($pos < $this->getTextLength()){
            // if the start is a whitespace we forward to the next non-whitespace
            while($this->isWhitespaceCharAt($pos) && $pos < $this->getTextLength()){
                $pos++;
            }
            $wasWhitespace = false;
            while($pos < $this->getTextLength()){
                $pos++;
                $isWhitespace = $this->isWhitespaceCharAt($pos);
                // we count the chunks down with every whitespace/non-whitespace change
                if($isWhitespace && !$wasWhitespace){
                    $words--;
                }
                if($words === 0){
                    if($afterWhitespace){
                        while($this->isWhitespaceCharAt($pos) && $pos < $this->getTextLength()){
                            $pos++;
                        }
                    }
                    return $pos;
                }
                $wasWhitespace = $isWhitespace;
            }
        }
        return $this->getTextLength();
    }
    /**
     * get a text-position the given number of words to the left
     * @param int $pos
     * @param int $words
     * @param bool $beforeWhitespace
     * @return int
     */
    public function getPrevWordsPosition(int $pos, int $words, bool $beforeWhitespace=false) : int {
        if($pos > 0){
            // if the start is a whitespace we rewind to the next non-whitespace
            while($pos > 0 && $this->isWhitespaceCharAt($pos - 1)){
                $pos--;
            }
            $wasWhitespace = false;
            while($pos >= 0){
                $pos--;
                $isWhitespace = $this->isWhitespaceCharAt($pos);
                // we count the chunks down with every whitespace/non-whitespace change
                if($isWhitespace && !$wasWhitespace){
                    $words--;
                }
                if($words == 0){
                    if($beforeWhitespace){
                        while($pos > 0 && $this->isWhitespaceCharAt($pos - 1)){
                            $pos--;
                        }
                        return $pos;
                    }
                    return min($pos + 1, $this->getTextLength());
                }
                $wasWhitespace = $isWhitespace;
            }
        }
        return 0;
    }
    /**
     * Retrieves the closest word-boundry to a position
     * @param int $pos
     * @return int
     */
    public function getClosestWordPosition(int $pos) : int {
        $next = $this->getNextWordsPosition(max($pos - 1, 0), 1);
        $prev = $this->getPrevWordsPosition(min($pos + 1, $this->getTextLength()), 1);
        if(abs($pos - $next) < abs($pos - $prev)){
            return $next;
        }
        return $prev;
    }
    /**
     * @param string $char
     * @return bool
     */
    protected function isWhitespaceChar(string $char) : bool {
        return ($char == ' ' || $char == "\r" || $char == "\n" || $char == "\t");
    }
    /**
     * @param int $pos
     * @return bool
     */
    public function isWhitespaceCharAt(int $pos) : bool {
        return $this->isWhitespaceChar($this->getTextCharAt($pos));
    }
    /**
     * @param int $pos
     * @return string
     */
    protected function getTextCharAt(int $pos) : string {
        if($pos < $this->getTextLength()){
            return mb_substr($this->text, $pos, 1);
        }
        return '';
    }

    /* Tag index API */

    /**
     * @param int $tagIdx
     * @return editor_Segment_TagRepair_Tag|null
     */
    public function findByTagIdx(int $tagIdx) : ?editor_Segment_TagRepair_Tag {
        foreach($this->tags as $tag){
            if($tag->getTagIndex() === $tagIdx){
                return $tag;
            }
        }
        return NULL;
    }
    /**
     * @param int $order
     * @return editor_Segment_TagRepair_Tag|null
     */
    public function findByOrder(int $order) : ?editor_Segment_TagRepair_Tag {
        if($order > -1){
            foreach($this->tags as $tag){
                if($tag->order === $order){
                    return $tag;
                }
            }
        }
        return NULL;
    }

    /* unparsing API */

    /**
     * Unparses Segment markup into FieldTags
     * @param string $html
     * @throws Exception
     */
    public function unparse(string $html) {
        // decompose html into a wrapping tag
        $wrapper = $this->unparseHtml($html);
        // set our field text
        $this->setText($wrapper->getText());
        if(static::VALIDATION_MODE){
            if($wrapper->getTextLength() != $this->getTextLength()){ error_log("\n##### WRAPPER TEXT LENGTH ".$wrapper->getTextLength()." DOES NOT MATCH FIELD TEXT LENGTH: ".$this->getTextLength()." #####\n"); }
            if($wrapper->endIndex != $this->getTextLength()){ error_log("\n##### WRAPPER END INDEX ".$wrapper->endIndex." DOES NOT MATCH FIELD TEXT LENGTH: ".$this->getTextLength()." #####\n"); }
        }
        // after unparsing we need to save the markup we send as request
        $this->requestHtml = $wrapper->renderChildrenForRequest();
        // sequence the nested tags as our children
        $wrapper->sequenceChildren($this);
        $this->consolidate();
        // finalize unparsing
        $this->finalizeUnparse();
    }

    /* Creation API */

    /**
     *
     * @param HtmlNode $node
     * @param int $startIndex
     * @return editor_Segment_Tag
     * @throws \stringEncode\Exception
     */
    protected function createFromHtmlNode(HtmlNode $node, int $startIndex) : editor_Segment_Tag {
        $classNames = [];
        $attributes = [];
        $domTag = $node->getTag();
        foreach($domTag->getAttributes() as $name => $attrib){
            /* @var $attrib AttributeDTO */
            if($name == 'class'){
                $classNames = explode(' ', trim($attrib->getValue()));
            } else {
                $attributes[$name] = $attrib->getValue();
            }
        }
        return $this->createRepairTag($classNames, $attributes, $domTag->name(), $startIndex);
    }
    /**
     *
     * @param DOMElement $element
     * @param int $startIndex
     * @return editor_Segment_Tag
     */
    protected function createFromDomElement(DOMElement $element, int $startIndex) : editor_Segment_Tag {
        $classNames = [];
        $attributes = [];
        if($element->hasAttributes()){
            foreach ($element->attributes as $attr) {
                if($attr->nodeName == 'class'){
                    $classNames = explode(' ', trim($attr->nodeValue));
                } else {
                    $attributes[$attr->nodeName] = $attr->nodeValue;
                }
            }
        }
        return $this->createRepairTag($classNames, $attributes, $element->nodeName, $startIndex);
    }
    /**
     *
     * @param array $classNames
     * @param array $attributes
     * @param string $nodeName
     * @param int $startIndex
     * @return editor_Segment_TagRepair_Tag
     */
    private  function createRepairTag(array $classNames, array $attributes, string $nodeName, int $startIndex) : editor_Segment_TagRepair_Tag {
        $tag = new editor_Segment_TagRepair_Tag($startIndex, 0, '', $nodeName, $this->tagIdxCount);
        $this->tagIdxCount++;
        if(count($classNames) > 0){
            foreach($classNames as $cname){
                $tag->addClass($cname);
            }
        }
        if(count($attributes) > 0){
            foreach($attributes as $name => $val){
                $tag->addAttribute($name, $val);
            }
        }
        return $tag;
    }
}
