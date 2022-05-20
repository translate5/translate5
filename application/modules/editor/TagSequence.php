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
 * Abstraction to bundle text and tags to an OOP accessible structure
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
 * - the nested Dom Tags then are converted into segment-tags as direct children of this class. These tags represent their position in the markup by start/end indexes pointing to the pure text-content of the markup (represented by the text prop) => Phase II
 * - usually all DOM tags represent a segment tag as all markup should be encapsulated by internal tags
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
abstract class editor_TagSequence implements JsonSerializable {

    /**
     * Can be used to validate the unparsing-process. Use only for Development !!
     * @var boolean
     */
    const VALIDATION_MODE = false;

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
     * The text of the relevant segment field
     * @var string
     */
    protected string $text;
    /**
     * The length of our text
     * @var int
     */
    protected int $textLength;
    /**
     * The tags and their positions within the segment
     * @var editor_Segment_Tag[]
     */
    protected array $tags = [];
    /**
     * @var integer
     */
    protected int $orderIndex = -1;

    /**
     * Sets the internal tags & the text by markup, acts like a constructor
     * @param string $text
     * @throws ZfExtended_Exception
     */
    protected function _setMarkup(string $text=NULL){
        if(!empty($text) && $text != editor_Segment_Tag::strip($text)){
            $this->unparse($text);
            // This debug can be used to evaluate the quality of the DOM parsing
            if(static::VALIDATION_MODE && $this->text != editor_Segment_Tag::strip($text)){
                error_log('=================== PARSED FIELD TEXT DID NOT MATCH PASSED HTML ===================='."\n");
                error_log('RAW TEXT: '.editor_Segment_Tag::strip($text)."\n");
                error_log('FIELD TEXT: '.$this->text."\n");
                error_log('IN:  '.$text."\n");
                error_log('OUT: '.$this->render()."\n");
                error_log('TAGS: '.$this->toJson()."\n");
                error_log('======================================='."\n");
            }
        } else if($text !== NULL) {
            $this->setText($text);
        }
    }
    /**
     * Returns the text without tags
     * @return string
     */
    protected function getText() : string {
        return $this->text;
    }
    /**
     * Sets our text. The text-prop always have to be manipulated with this API
     * @param string $text
     */
    protected function setText(string $text) {
        $this->text = $text;
        $this->textLength = mb_strlen($text);
    }
    /**
     *
     * @return int
     */
    protected function getTextLength() : int {
        return $this->textLength;
    }
    /**
     * Retrieves a part of the segment-text by start & end index
     * Used by editor_Segment_Tag to fill in the segment-texts
     * @param int $start
     * @param int $end
     * @return string
     */
    public function getTextPart(int $start, int $end) : string {
        // prevent any substr magic with negative offsets ...
        if($end > $start){
            return mb_substr($this->text, $start, ($end - $start));
        }
        return '';
    }
    /**
     *
     * @return bool
     */
    public function isEmpty() : bool {
        return ($this->getTextLength() === 0 && !$this->hasTags());
    }
    /**
     * We expect the passed text to be identical
     * @param string $text
     * @return string
     */
    public function setTagsByText(string $text){
        $textBefore = $this->text;
        $this->setText('');
        $this->tags = [];
        $this->orderIndex = -1;
        $this->_setMarkup($text);
        if($this->text != $textBefore){
            $this->logError('E1343', 'Setting the tags by text led to a changed text-content presumably because the encoded tags have been improperly processed');
        }
    }
    /**
     * Adds a Segment tag. Note, that the nesting has to be reflected with the internal order of tags and the parent (referencing the order of the parent element)
     * @param editor_Segment_Tag $tag
     * @param int $order: Do only use if the internal order is know. If not provided, a tag is always added as the last thing at the tags startPosition (e.g. a single tag can not be added inside a present tag's send position without knowing the order)
     * @param int $parent: Order-index of the oparent element with nested tags. If not provided, the added tag will be rendered outside of other tags if possible
     */
    public function addTag(editor_Segment_Tag $tag, int $order=-1, int $parentOrder=-1){
        $tag->isFullLength = ($tag->startIndex == 0 && $tag->endIndex >= $this->getTextLength());
        $tag->content = $this->getTextPart($tag->startIndex, $tag->endIndex);
        if($order < 0){
            $this->orderIndex++;
            $tag->order = $this->orderIndex;
        } else {
            $tag->order = $order;
            $this->orderIndex = max($this->orderIndex, $order);
        }
        $tag->parentOrder = $parentOrder;
        $this->finalizeAddTag($tag);
        $this->tags[] = $tag;
    }
    /**
     * Called after the unparsing Phase to finalize adding a single tag
     * @param editor_Segment_Tag $tag
     */
    protected function finalizeAddTag(editor_Segment_Tag $tag){

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
     *
     * @return editor_Segment_Tag[]
     */
    public function getAll() : array {
        return $this->tags;
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

    public function jsonSerialize() : mixed {
        $data = new stdClass();
        $this->sort();
        $data->tags = [];
        foreach($this->tags as $tag){
            $data->tags[] = $tag->jsonSerialize();
        }
        $data->text = $this->text;
        return $data;
    }

    /* Rendering API */

    /**
     *
     * @param string[] $skippedTypes: if set, internal tags of this type will not be rendered
     * @return string
     */
    public function render(array $skippedTypes=NULL) : string {
        // nothing to do without tags
        if(count($this->tags) == 0){
            return $this->text;
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
                                // TODO FIXME: Add Proper Exception / error-code
                                $this->logError('E9999', 'Two non-splittable tags interleave each other.');
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
        }
        // now we create the nested data-model from the up to now sequential but sorted $rtags model. We also add the text-portions of the segment as text nodes
        // this container just acts as the master container
        $holder = new editor_Segment_AnyTag(0, $this->getTextLength());
        $container = $holder;
        $processed = [ $holder ]; // holds all tags that have been processed
        foreach($clones as $tag){
            // this "mechanic" is just to correct problems with singular tags on the right boundry of non-singular tags: The will be sorted right after the non-singular but may are nested into. We have to correct this ...
            $nearest = $this->findHolderByOrder($processed, $tag);
            if($nearest == NULL){
                $nearest = $container->getNearestContainer($tag); // this is the "normal" way of nesting the sorted cloned tags
            }
            // Will log rendering problems
            if(static::VALIDATION_MODE && $nearest == null){
                error_log("\n============== HOLDER =============\n");
                error_log($holder->toJson());
                error_log("\n============== CONTAINER =============\n");
                error_log($container->toJson());
                error_log("\n============== TAG =============\n");
                error_log($tag->toJson());
                error_log("\n========================================\n");
            }
            // TS-1337: This error happend "in the wild". It can only happen with malformed Markup. We need more data for a proper investigation
            if($nearest == null){
                $errorData = [];
                $errorData['holder'] = $holder->jsonSerialize();
                $errorData['container'] = $container->jsonSerialize();
                $errorData['tag'] = $tag->jsonSerialize();
                $errorData = $this->logError('E1343', 'Rendering TagSequence tags led to a invalid tag structure that could not be processed', $errorData);
                throw new ZfExtended_Exception('editor_TagSequence::render: Rendering failed presumably due to invalid tag structure.'."\n".json_encode($errorData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
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
     * Helper for the rendering-phase: Finds a tag by it's (valid) order-index
     * Please note that this may fails when multiple tags with the same order have been added
     * @param editor_Segment_Tag[] $holders
     * @param editor_Segment_Tag $tag
     * @return editor_Segment_Tag|NULL
     */
    protected function findHolderByOrder(array &$holders, editor_Segment_Tag $tag) : ?editor_Segment_Tag {
        if($tag->parentOrder > -1){
            foreach($holders as $holder){
                if($tag->parentOrder == $holder->order && $holder->canContain($tag)){
                    return $holder;
                }
            }
        }
        return NULL;
    }

    /* Unparsing API */

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
        // sequence the nested tags as our children
        $wrapper->sequenceChildren($this);
        if(static::VALIDATION_MODE){
            $this->sort();
            $length = $this->getTextLength();
            foreach($this->tags as $tag){
                if($tag->endIndex > $length){
                    error_log("\n============== SEGMENT TAG IS OUT OF BOUNDS (TEXT LENGTH: ".$length.") =============\n");
                    error_log($tag->toJson());
                    error_log("\n========================================\n");
                }
            }
        }
        $this->consolidate();
        // Crucial: set the tag-props, also gives inheriting APIs the chance to add more logic to the unparsing
        $this->finalizeUnparse();
    }
    /**
     * unparses markup depending on the configured parser (DOMDocument or HtmlDom)
     * @param string $html
     * @return editor_Segment_Tag
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\CircularException
     * @throws \PHPHtmlParser\Exceptions\ContentLengthException
     * @throws \PHPHtmlParser\Exceptions\LogicalException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     * @throws \PHPHtmlParser\Exceptions\StrictException
     */
    protected function unparseHtml(string $html) : editor_Segment_Tag {
        if(editor_Tag::USE_PHP_DOM){
            // implementation using PHP DOM
            $dom = new editor_Utils_Dom();
            // to make things easier we add a wrapper to hold all tags and only use it's children
            $element = $dom->loadUnicodeElement('<div>'.$html.'</div>');
            if(static::VALIDATION_MODE && mb_substr($dom->saveHTML($element), 5, -6) != $html){
                error_log("\n============== UNPARSED PHP DOM DOES NOT MATCH =============\n");
                error_log(mb_substr($dom->saveHTML($element), 5, -6));
                error_log("\n========================================\n");
                error_log($html);
                error_log("\n========================================\n");
            }
            if($element != NULL){
                return $this->fromDomElement($element, 0);
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
            if(static::VALIDATION_MODE &&  $dom->firstChild()->innerHtml() != $html){
                error_log("\n============== UNPARSED HTML DOM DOES NOT MATCH =============\n");
                error_log($dom->firstChild()->innerHtml());
                error_log("\n========================================\n");
                error_log($html);
                error_log("\n========================================\n");
            }
            return $this->fromHtmlNode($dom->firstChild(), 0);
        }
    }
    /**
     * Called after the unparsing Phase to finalize the found tags
     */
    protected function finalizeUnparse(){
        $num = count($this->tags);
        $textLength = $this->getTextLength();
        for($i=0; $i < $num; $i++){
            $tag = $this->tags[$i];
            $tag->isFullLength = ($tag->startIndex == 0 && $tag->endIndex >= $textLength);
            $tag->content = $this->getTextPart($tag->startIndex, $tag->endIndex);
        }
    }
    /**
     * Creates a nested structure of Internal tags & text-nodes recursively out of a HtmlNode structure
     * @param HtmlNode $node
     * @param int $startIndex
     * @return editor_Segment_Tag
     */
    protected function fromHtmlNode(HtmlNode $node, int $startIndex){
        $children = $node->hasChildren() ? $node->getChildren() : NULL;
        $tag = $this->createFromHtmlNode($node, $startIndex, $children);
        if($children !== NULL){
            foreach($children as $childNode){
                if($childNode->isTextNode()){
                    if($tag->addText($childNode->text())){
                        $startIndex += $tag->getLastChildsTextLength();
                    }
                } else if(is_a($childNode, 'PHPHtmlParser\Dom\Node\HtmlNode')){
                    if($tag->addChild($this->fromHtmlNode($childNode, $startIndex))){
                        $startIndex += $tag->getLastChildsTextLength();
                    }
                } else if(static::VALIDATION_MODE){
                    error_log("\n##### FROM HTML NODE ADDS UNKNOWN NODE TYPE '".get_class($childNode)."' #####\n");
                }
            }
        }
        $tag->endIndex = $startIndex;
        return $tag;
    }
    /**
     * @param HtmlNode $node
     * @param int $startIndex
     * @param array|null $children
     * @return editor_Segment_Tag
     */
    abstract protected function createFromHtmlNode(HtmlNode $node, int $startIndex, array $children=NULL) : editor_Segment_Tag;
    /**
     * Creates a nested structure of Internal tags & text-nodes recursively out of a DOMElement structure
     * This is an alternative implementation using PHP DOM
     * see editor_Tag::USE_PHP_DOM
     * @param DOMElement $element
     * @param int $startIndex
     * @return editor_Segment_Tag
     */
    protected function fromDomElement(DOMElement $element, int $startIndex){
        $children = $element->hasChildNodes() ? $element->childNodes : NULL;
        $tag = $this->createFromDomElement($element, $startIndex, $children);
        if($children !== NULL){
            for($i = 0; $i < $children->length; $i++){
                $child = $children->item($i);
                if($child->nodeType == XML_TEXT_NODE){
                    if($tag->addText(editor_Tag::convertDOMText($child->nodeValue))){
                        $startIndex += $tag->getLastChildsTextLength();
                    }
                } else if($child->nodeType == XML_ELEMENT_NODE){
                    if($tag->addChild($this->fromDomElement($child, $startIndex))){
                        $startIndex += $tag->getLastChildsTextLength();
                    }
                } else if(static::VALIDATION_MODE){
                    error_log("\n##### FROM DOM ELEMENT ADDS UNWANTED ELEMENT TYPE '".$child->nodeType."' #####\n");
                }
            }
        }
        $tag->endIndex = $startIndex;
        return $tag;
    }
    /**
     * @param DOMElement $element
     * @param int $startIndex
     * @param DOMNodeList|null $children
     * @return editor_Segment_Tag
     */
    abstract protected function createFromDomElement(DOMElement $element, int $startIndex, DOMNodeList $children=NULL) : editor_Segment_Tag;
    /**
     * Joins Tags that are equal and directly beneath each other
     * Also removes any internal connections between the tags
     * Joins paired tags, removes obsolete tags
     */
    protected function consolidate(){
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
    protected function swapOrder(editor_Segment_Tag $tag1, editor_Segment_Tag $tag2, string $propName){
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
     * Helper to set the del/ins properties
     * @param int $start
     * @param int $end
     * @param string $propName
     */
    protected function setContainedTagsProp(int $start, int $end, int $order, string $propName){
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
    protected function fixParentOrders(){
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

    /* Logging API */

    /**
     *
     * @return ZfExtended_Logger
     * @throws Zend_Exception
     */
    protected function createLogger() : ZfExtended_Logger {
        return Zend_Registry::get('logger')->cloneMe('editor.tagsequence');
    }
    /**
     *
     * @param string $code
     * @param string $msg
     * @param array $errorData
     * @return array
     * @throws Zend_Exception
     */
    protected function logError(string $code, string $msg, array $errorData=[]) : array {
        $this->createLogger()->error($code, $msg, $this->addErrorDetails($errorData));
        return $errorData;
    }
    /**
     *
     * @param array $errorData
     */
    protected function addErrorDetails(array &$errorData){
        $errorData['text'] = $this->text;
    }

    /* Validation API */

    /* Debugging API */

    /**
     * Debug output
     * @return string
     */
    public function debug(){
        $newline = "\n";
        $debug = 'TEXT: "'.trim($this->text).'"'.$newline;
        for($i=0; $i < count($this->tags); $i++){
              $debug .= 'TAG '.$i.':'.$newline.trim($this->tags[$i]->debug()).$newline;
        }
        return $debug;
    }
}
