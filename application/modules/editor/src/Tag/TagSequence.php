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

namespace MittagQI\Translate5\Tag;

use DOMElement;
use DOMNodeList;
use editor_Models_Segment_Exception;
use editor_Segment_AnyTag;
use editor_Segment_Tag;
use editor_Tag;
use Exception;
use JsonSerializable;
use MittagQI\ZfExtended\Tools\Markup;
use PHPHtmlParser\Dom\Node\HtmlNode;
use stdClass;
use Zend_Registry;
use ZfExtended_Dom;
use ZfExtended_ErrorCodeException;
use ZfExtended_Logger;

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
abstract class TagSequence implements JsonSerializable
{
    /**
     * Can be used to debug and validate the unparsing-process. Use only for Development !!
     * @var boolean
     */
    public const DO_DEBUG = false;

    /**
     * Mode for the replaced rendering: Strips all Markup & internal tags
     */
    public const MODE_STRIPPED = 'stripped';

    /**
     * Mode for the replaced rendering: Strip all Markup, use "labeled" contents (e.g. "↵" nfor newline tags)
     */
    public const MODE_LABELED = 'labeled';

    /**
     * Mode for the replaced rendering: Strip all Markup, render whitespace-tags & special chars in their original form
     */
    public const MODE_ORIGINAL = 'original';

    /**
     * Defines the error-domain to log
     */
    protected static string $logger_domain = 'editor.tagsequence';

    /**
     * Helper to sort Internal tags or rendered tags by startIndex
     * This is a central part of the rendering logic
     * Note, that for rendering, tags, that potentially contain other tags, must come first, otherwise this will lead to rendering errors
     * Note, that the sorting of tags must not reperesent the order-property (!!!)
     * The nesting may be corrected with the ::findHolderByOrder API but for rendering this "longer first" logic must apply
     */
    public static function compare(editor_Segment_Tag $a, editor_Segment_Tag $b): int
    {
        if ($a->startIndex === $b->startIndex) {
            // only tags at the exact same position that do not contain each other will need the order-property evaluated when sorting !
            if ($b->endIndex === $a->endIndex) {
                if ($a->order > -1 && $b->order > -1) {
                    return $a->order - $b->order; // both have an order: compare it
                } elseif ($a->order === -1) {
                    return -1; // the not-nested always comes first
                } else {
                    return 1; // the not-nested always comes first
                }
            }
            // crucial: we must make sure, that a "normal" tag may contain a single tag at the same index (no text-content). Thus, the normal tags always must weight less / come first
            if ($a->isSingular() && ! $b->isSingular()) {
                return 1;
            } elseif (! $a->isSingular() && $b->isSingular()) {
                return -1;
            }

            return $b->endIndex - $a->endIndex;
        }

        return $a->startIndex - $b->startIndex;
    }

    /**
     * Sorting of children of segment tags in the rendering phase: Here the singular tags (where startIndex == endIndex) MUST come first!
     * This is crucial for the text-distribution to work properly (::addSegmentText)
     */
    public static function compareChildren(editor_Segment_Tag $a, editor_Segment_Tag $b): int
    {
        if ($a->startIndex === $b->startIndex) {
            // only tags at the exact same position that do not contain each other will need the order-property evaluated when sorting !
            if ($b->endIndex == $a->endIndex && $a->order > -1 && $b->order > -1) {
                return $a->order - $b->order;
            }

            return $a->endIndex - $b->endIndex;
        }

        return $a->startIndex - $b->startIndex;
    }

    /**
     * The text of the relevant segment field
     */
    protected string $text;

    /**
     * The length of our text
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
     * If set, all errors/exceptions ar captured instead of logging them.
     */
    protected bool $captureErrors = true;

    /**
     * Holds captured errors
     * @var ZfExtended_ErrorCodeException[]
     */
    protected array $capturedErrors = [];

    /**
     * Holds the initially set markup for later logging
     */
    private ?string $originalMarkup = null;

    /**
     * Sets the internal tags & the text by markup, acts like a constructor
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\CircularException
     * @throws \PHPHtmlParser\Exceptions\ContentLengthException
     * @throws \PHPHtmlParser\Exceptions\LogicalException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     * @throws \PHPHtmlParser\Exceptions\StrictException
     */
    protected function _setMarkup(string $text = null): void
    {
        if (! empty($text) && $text != editor_Segment_Tag::strip($text)) {
            // for better error-tracking, we cache the initial markup to be able to log it
            $this->originalMarkup = $text;
            $this->unparse($text);
            // This debug can be used to evaluate the quality of the DOM parsing
            if (static::DO_DEBUG && $this->text != editor_Segment_Tag::strip($text)) {
                error_log('=================== PARSED FIELD TEXT DID NOT MATCH PASSED HTML ====================' . "\n");
                error_log('RAW TEXT: ' . editor_Segment_Tag::strip($text) . "\n");
                error_log('FIELD TEXT: ' . $this->text . "\n");
                error_log('IN:  ' . $text . "\n");
                error_log('OUT: ' . $this->render() . "\n");
                error_log('TAGS: ' . $this->toJson() . "\n");
                error_log('=======================================' . "\n");
            }
        } elseif ($text !== null) {
            $this->setText($text);
        }
    }

    /**
     * Returns the original Markup set via _setMarkup
     */
    protected function getOriginalMarkup(): string
    {
        return $this->originalMarkup ?? '';
    }

    /**
     * Returns the text without tags
     */
    protected function getText(): string
    {
        return $this->text;
    }

    /**
     * Sets our text. The text-prop always have to be manipulated with this API
     */
    protected function setText(string $text): void
    {
        $this->text = $text;
        $this->textLength = mb_strlen($text);
    }

    protected function getTextLength(): int
    {
        return $this->textLength;
    }

    /**
     * Retrieves a part of the segment-text by start & end index
     * Used by editor_Segment_Tag to fill in the segment-texts
     */
    public function getTextPart(int $start, int $end): string
    {
        // prevent any substr magic with negative offsets ...
        if ($end > $start) {
            return mb_substr($this->text, $start, ($end - $start));
        }

        return '';
    }

    public function isEmpty(): bool
    {
        return ($this->getTextLength() === 0 && ! $this->hasTags());
    }

    /**
     * We expect the passed text to be identical
     * @throws \ZfExtended_Exception
     */
    public function setTagsByText(string $text): void
    {
        $textBefore = $this->text;
        $this->setText('');
        $this->tags = [];
        $this->orderIndex = -1;
        $this->_setMarkup($text);
        if ($this->text != $textBefore) {
            $extraData = [
                'textBefore' => $textBefore,
            ];
            if ($this->originalMarkup !== null) {
                $extraData['originalMarkup'] = $this->originalMarkup;
            }
            $this->logError('E1343', 'Setting the tags by text led to a changed text-content presumably because the encoded tags have been improperly processed', $extraData);
        }
    }

    /**
     * Adds a Segment tag. Note, that the nesting has to be reflected with the internal order of tags and the parent (referencing the order of the parent element)
     */
    public function addTag(editor_Segment_Tag $tag, int $order = -1, int $parentOrder = -1): void
    {
        $tag->isFullLength = ($tag->startIndex == 0 && $tag->endIndex >= $this->getTextLength());
        $tag->content = $this->getTextPart($tag->startIndex, $tag->endIndex);
        if ($order < 0) {
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
     */
    protected function finalizeAddTag(editor_Segment_Tag $tag): void
    {
    }

    /**
     * Retrieves the tag at a certain index
     */
    public function getAt(int $index): ?editor_Segment_Tag
    {
        if ($index < count($this->tags)) {
            return $this->tags[$index];
        }

        return null;
    }

    /**
     * @return editor_Segment_Tag[]
     */
    public function getAll(): array
    {
        return $this->tags;
    }

    /**
     * Removes all tags, so only the raw text will be left
     */
    public function removeAll(): void
    {
        $this->tags = [];
        $this->orderIndex = -1;
    }

    public function hasTags(): bool
    {
        return (count($this->tags) > 0);
    }

    public function numTags(): int
    {
        return count($this->tags);
    }

    /**
     * Sorts the items ascending, takes the second index into account when items have the same startIndex
     */
    public function sort(): void
    {
        usort($this->tags, [$this, 'compare']);
    }

    /* Serialization API */

    public function toJson(): false|string
    {
        return json_encode($this->jsonSerialize(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function jsonSerialize(): stdClass
    {
        $data = new stdClass();
        $this->sort();
        $data->tags = [];
        foreach ($this->tags as $tag) {
            $data->tags[] = $tag->jsonSerialize();
        }
        $data->text = $this->text;

        return $data;
    }

    /* Rendering API */

    /**
     * Renders the tag-sequence
     */
    public function render(array $skippedTypes = null): string
    {
        // nothing to do without tags
        if (count($this->tags) == 0) {
            return $this->text;
        }
        // create holder and render it's children
        $holder = $this->createRenderingHolder();

        return $holder->renderChildren($skippedTypes);
    }

    /**
     * Renders the tag-sequence in replaced mode, what means with markup stripped
     * and some special adjustments depending on the mode
     * @throws \Zend_Exception
     * @throws ZfExtended_ErrorCodeException
     */
    public function renderReplaced(string $mode = self::MODE_STRIPPED): string
    {
        // nothing to do without tags
        if (count($this->tags) == 0) {
            return $this->text;
        }
        // create holder and render it's children
        $holder = $this->createRenderingHolder();

        return $holder->renderReplaced($mode);
    }

    /**
     * @throws \Zend_Exception
     * @throws editor_Models_Segment_Exception
     */
    private function createRenderingHolder(): editor_Segment_AnyTag
    {
        $this->sort();
        // first, clone our tags to have a disposable rendering model. This may split some tags that are allowed to overlap into "subtags" (like mqm)
        $clones = [];
        /* @var $clones editor_Segment_Tag[] */
        foreach ($this->tags as $tag) {
            $tag->addRenderingClone($clones);
        }
        usort($clones, [$this, 'compare']);
        $numClones = count($clones);
        // cutting the overlaps
        if ($numClones > 1) {
            // first, evaluate the needed cuts
            for ($i = 0; $i < $numClones; $i++) {
                $tag = $clones[$i];
                if ($i < $numClones - 1) {
                    for ($j = $i + 1; $j < $numClones; $j++) {
                        $compare = $clones[$j];
                        if ($compare->startIndex < $tag->endIndex && $compare->endIndex > $tag->endIndex) {
                            if ($tag->isSplitable() || $compare->isSplitable()) {
                                // add cut to the tag that actually is cutable
                                $bread = ($tag->isSplitable()) ? $tag : $compare;
                                $knife = ($tag->isSplitable()) ? $compare : $tag;
                                // add a cut only, if it's not there already
                                if (! in_array($knife->startIndex, $bread->cuts)) {
                                    $bread->cuts[] = $knife->startIndex;
                                }
                            } else {
                                // we have an overlap with tags, that both are not allowed to overlap. this must not happen.
                                // we report this error but continue with rendering, the overlapping tag will be adjusted in the cutting phase automatically
                                $errorData = [];
                                $errorData['overlappedTag'] = $tag->toJson();
                                $errorData['overlappingTag'] = $compare->toJson();
                                $this->logError('E1391', 'Two non-splittable tags interleave each other.', $errorData);
                            }
                        }
                    }
                }
            }
            // then clone the cutted tags into pieces
            for ($i = 0; $i < $numClones; $i++) {
                if (count($clones[$i]->cuts) > 0) {
                    sort($clones[$i]->cuts, SORT_NUMERIC);
                    $last = $clones[$i];
                    $end = $last->endIndex;
                    foreach ($clones[$i]->cuts as $cut) {
                        $last->endIndex = $cut;
                        $last = $clones[$i]->cloneForRendering();
                        $last->startIndex = $cut;
                        $last->endIndex = $end;
                        $clones[] = $last;
                    }
                }
            }
            usort($clones, [$this, 'compare']);
        }
        // now we create the nested data-model from the up to now sequential but sorted $rtags model. We also add the text-portions of the segment as text nodes
        // this container just acts as the master container
        $holder = new editor_Segment_AnyTag(0, $this->getTextLength());
        $container = $holder;
        $processed = [$holder]; // holds all tags that have been processed
        foreach ($clones as $tag) {
            // this "mechanic" is just to correct problems with singular tags on the right boundry of non-singular tags: The will be sorted right after the non-singular but may are nested into. We have to correct this ...
            $nearest = $this->findHolderByOrder($processed, $tag);
            if ($nearest == null) {
                $nearest = $container->getNearestContainer($tag); // this is the "normal" way of nesting the sorted cloned tags
            }
            // Will log rendering problems
            if (static::DO_DEBUG && $nearest === null) {
                error_log("\nERROR RENDERING TAG-SEQUENCE: Nearest Container not found");
                error_log("\n============== HOLDER =============\n");
                error_log($holder->toJson());
                error_log("\n============== CONTAINER =============\n");
                error_log($container->toJson());
                error_log("\n============== TAG =============\n");
                error_log($tag->toJson());
                if ($this->originalMarkup !== null) {
                    error_log("\n============== ORIGINAL MARKUP =============\n");
                    error_log($this->originalMarkup);
                }
                error_log(htmlspecialchars($this->debugStructure($clones)));
            }
            // TS-1337: This error happend "in the wild". It can only happen with malformed Markup. We need more data for a proper investigation
            if ($nearest === null) {
                $errorData = [];
                if ($this->originalMarkup !== null) {
                    $errorData['originalMarkup'] = $this->originalMarkup;
                }
                $errorData['holder'] = $holder->toJson();
                $errorData['container'] = $container->toJson();
                $errorData['tag'] = $tag->toJson();

                $errorData = $this->logError('E1610', 'Rendering TagSequence tags led to a invalid tag structure that could not be processed', $errorData);

                throw new editor_Models_Segment_Exception('E1610', $errorData);
            }
            $nearest->addChild($tag);
            $container = $tag;
            $processed[] = $tag;
        }
        // distributes the text-portions to the now re-nested structure
        $holder->addSegmentText($this);
        $processed = $clones = null;

        // this holder is the base for all renering APIs
        return $holder;
    }

    /**
     * Helper for the rendering-phase: Finds a tag by it's (valid) order-index
     * Please note that this may fails when multiple tags with the same order have been added
     * @param editor_Segment_Tag[] $holders
     */
    protected function findHolderByOrder(array $holders, editor_Segment_Tag $tag): ?editor_Segment_Tag
    {
        if ($tag->parentOrder > -1) {
            foreach ($holders as $holder) {
                if (! $holder->removed && $tag->parentOrder === $holder->order && $holder->canContain($tag)) {
                    return $holder;
                }
            }
        }

        return null;
    }

    /* Unparsing API */

    /**
     * Unparses Segment markup into FieldTags
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\CircularException
     * @throws \PHPHtmlParser\Exceptions\ContentLengthException
     * @throws \PHPHtmlParser\Exceptions\LogicalException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     * @throws \PHPHtmlParser\Exceptions\StrictException
     */
    public function unparse(string $html): void
    {
        // decompose html into a wrapping tag
        $wrapper = $this->unparseHtml($html);
        // set our field text
        $this->setText($wrapper->getText());
        if (static::DO_DEBUG) {
            if ($wrapper->getTextLength() != $this->getTextLength()) {
                error_log("\n##### WRAPPER TEXT LENGTH " . $wrapper->getTextLength() . " DOES NOT MATCH FIELD TEXT LENGTH: " . $this->getTextLength() . " #####\n");
            }
            if ($wrapper->endIndex != $this->getTextLength()) {
                error_log("\n##### WRAPPER END INDEX " . $wrapper->endIndex . " DOES NOT MATCH FIELD TEXT LENGTH: " . $this->getTextLength() . " #####\n");
            }
        }
        // sequence the nested tags as our children
        $wrapper->sequenceChildren($this);
        if (static::DO_DEBUG) {
            $this->sort();
            $length = $this->getTextLength();
            foreach ($this->tags as $tag) {
                if ($tag->endIndex > $length) {
                    error_log("\n============== SEGMENT TAG IS OUT OF BOUNDS (TEXT LENGTH: " . $length . ") =============\n");
                    error_log($tag->toJson());
                    error_log("\n========================================\n");
                }
            }
        }
        $this->consolidate();
        // Crucial: set the tag-props, also gives inheriting APIs the chance to add more logic to the unparsing
        $this->finalizeUnparse();
        $this->sort();
    }

    /**
     * unparses markup depending on the configured parser (DOMDocument or HtmlDom)
     * @throws \PHPHtmlParser\Exceptions\ChildNotFoundException
     * @throws \PHPHtmlParser\Exceptions\CircularException
     * @throws \PHPHtmlParser\Exceptions\ContentLengthException
     * @throws \PHPHtmlParser\Exceptions\LogicalException
     * @throws \PHPHtmlParser\Exceptions\NotLoadedException
     * @throws \PHPHtmlParser\Exceptions\StrictException
     */
    protected function unparseHtml(string $html): editor_Segment_Tag
    {
        if (editor_Tag::USE_DOM_DOCUMENT) { // @phpstan-ignore-line
            // implementation using PHP DOM
            $dom = new ZfExtended_Dom();
            // to make things easier we add a wrapper to hold all tags and only use it's children
            $element = $dom->loadUnicodeElement('<div>' . $html . '</div>');
            if (static::DO_DEBUG && mb_substr($dom->saveXML($element), 5, -6) != $html) {
                error_log("\n============== UNPARSED PHP DOM DOES NOT MATCH =============\n");
                error_log(mb_substr($dom->saveXML($element), 5, -6));
                error_log("\n========================================\n");
                error_log($html);
                error_log("\n========================================\n");
            }
            if ($element != null) {
                return $this->fromDomElement($element, 0);
            } else {
                throw new Exception('Could not unparse Internal Tags from Markup ' . $html);
            }
        } else {
            // implementation using PHPHtmlParser
            $dom = editor_Tag::createDomParser();
            // to make things easier we add a wrapper to hold all tags and only use it's children
            $dom->loadStr('<div>' . $html . '</div>');
            if ($dom->countChildren() != 1) {
                throw new Exception('Could not unparse Internal Tags from Markup ' . $html);
            }
            if (static::DO_DEBUG && $dom->firstChild()->innerHtml() != $html) {
                error_log("\n============== UNPARSED HTML DOM DOES NOT MATCH =============\n");
                error_log($dom->firstChild()->innerHtml());
                error_log("\n========================================\n");
                error_log($html);
                error_log("\n========================================\n");
            }
            // we do know the first child is a HTML-Node as it is the '<div>' added on loadStr
            $firstChild = $dom->firstChild(); /** @var HtmlNode $firstChild */

            return $this->fromHtmlNode($firstChild, 0);
        }
    }

    /**
     * Called after the unparsing Phase to finalize the found tags
     */
    protected function finalizeUnparse(): void
    {
        // setting contents & fulllength props
        $num = count($this->tags);
        $textLength = $this->getTextLength();
        for ($i = 0; $i < $num; $i++) {
            $tag = $this->tags[$i];
            $tag->isFullLength = ($tag->startIndex == 0 && $tag->endIndex >= $textLength);
            $tag->content = $this->getTextPart($tag->startIndex, $tag->endIndex);
        }
    }

    /**
     * Creates a nested structure of Internal tags & text-nodes recursively out of a HtmlNode structure
     * @throws \PHPHtmlParser\Exceptions\CircularException
     */
    protected function fromHtmlNode(HtmlNode $node, int $startIndex): editor_Segment_Tag
    {
        $children = $node->hasChildren() ? $node->getChildren() : null;
        $tag = $this->createFromHtmlNode($node, $startIndex, $children);
        if ($children !== null) {
            foreach ($children as $childNode) {
                if ($childNode->isTextNode()) {
                    if ($tag->addText($childNode->text())) {
                        $startIndex += $tag->getLastChildsTextLength();
                    }
                } elseif (is_a($childNode, HtmlNode::class)) {
                    if ($tag->addChild($this->fromHtmlNode($childNode, $startIndex))) {
                        $startIndex += $tag->getLastChildsTextLength();
                    }
                } elseif (static::DO_DEBUG) {
                    error_log("\n##### FROM HTML NODE ADDS UNKNOWN NODE TYPE '" . get_class($childNode) . "' #####\n");
                }
            }
        }
        $tag->endIndex = $startIndex;

        return $tag;
    }

    abstract protected function createFromHtmlNode(HtmlNode $node, int $startIndex, array $children = null): editor_Segment_Tag;

    /**
     * Creates a nested structure of Internal tags & text-nodes recursively out of a DOMElement structure
     * This is an alternative implementation using PHP DOM
     * see editor_Tag::USE_DOM_DOCUMENT
     * @throws Exception
     */
    protected function fromDomElement(DOMElement $element, int $startIndex): editor_Segment_Tag
    {
        $children = $element->hasChildNodes() ? $element->childNodes : null;
        $tag = $this->createFromDomElement($element, $startIndex, $children);
        if ($children !== null) {
            for ($i = 0; $i < $children->length; $i++) {
                $child = $children->item($i);
                if ($child->nodeType == XML_TEXT_NODE) {
                    /** @var \DOMText $child */
                    if ($tag->addText(Markup::escapeText($child->nodeValue))) {
                        $startIndex += $tag->getLastChildsTextLength();
                    }
                } elseif ($child->nodeType == XML_ELEMENT_NODE) {
                    /** @var DOMElement $child */
                    if ($tag->addChild($this->fromDomElement($child, $startIndex))) {
                        $startIndex += $tag->getLastChildsTextLength();
                    }
                } elseif (static::DO_DEBUG) {
                    error_log("\n##### FROM DOM ELEMENT ADDS UNWANTED ELEMENT TYPE '" . $child->nodeType . "' #####\n");
                }
            }
        }
        $tag->endIndex = $startIndex;

        return $tag;
    }

    abstract protected function createFromDomElement(DOMElement $element, int $startIndex, DOMNodeList $children = null): editor_Segment_Tag;

    /**
     * Joins Tags that are equal and directly beneath each other
     * Also removes any internal connections between the tags
     * Joins paired tags, removes obsolete tags
     */
    protected function consolidate(): void
    {
        $this->sort();
        $numTags = count($this->tags);
        if ($numTags > 1) {
            $tags = [];
            $last = $this->tags[0];
            $last->resetChildren();
            $tags[] = $last;
            // 1) remove paired closers
            for ($i = 1; $i < $numTags; $i++) {
                // when a tag is a paired opener we try to find it's counterpart and remove it from the chain
                // paired opener/closer tags do not create problems with nesting as they are ony virtually paired
                if ($last->isPairedOpener()) {
                    for ($j = $i; $j < $numTags; $j++) {
                        // if we found the counterpart (the opener could pair it) this closer will be removed from our chain
                        if ($this->tags[$j]->isPairedCloser() && $last->isOfType($this->tags[$j]->getType()) && $last->pairWith($this->tags[$j])) {
                            array_splice($this->tags, $j, 1);
                            $numTags--;

                            break;
                        }
                    }
                }
                // we may already removed the current element, so check
                if ($i < $numTags) {
                    $last = $this->tags[$i];
                    $last->resetChildren();
                    $tags[] = $last;
                }
            }
            // 2) join mergable tags
            $numTags = count($tags);
            for ($i = 0; $i < $numTags - 1; $i++) {
                $last = $tags[$i];
                for ($j = $i + 1; $j < $numTags; $j++) {
                    $tag = $tags[$j];
                    // we join only tags that are splitable of course ...
                    if ($tag->isSplitable() && $tag->isEqual($last) && $last->endIndex === $tag->startIndex) {
                        // we need to care for any holders of the tag, which may cannot contain it anymore
                        $lastHolder = $this->findHolderByOrder($tags, $last);
                        $tagHolder = $this->findHolderByOrder($tags, $tag);
                        $last->endIndex = $tag->endIndex;
                        // all nested tags of the second part now belong to the merged first tag
                        $this->changeParentOrder($tag->order, $last->order);
                        // correct potential holders if they still can contain the composition
                        if ($lastHolder !== null && $lastHolder->canContain($last)) {
                            $last->parentOrder = $lastHolder->order;
                        } elseif ($tagHolder !== null && $tagHolder->canContain($last)) {
                            $last->parentOrder = $tagHolder->order;
                        }
                        // check, if the composition now can hold the former holders - and do so
                        if ($lastHolder !== null && $last->canContain($lastHolder)) {
                            // we may need to change the order, as containing tags usually come first
                            if ($lastHolder->order < $last->order) {
                                $this->swapOrder($lastHolder, $last, 'order');
                            }
                            $lastHolder->parentOrder = $last->order;
                            $last->parentOrder = -1; // we are now containing our ex-parent !
                        }
                        if ($tagHolder !== null && $last->canContain($tagHolder)) {
                            // we may need to change the order, as containing tags usually come first
                            if ($tagHolder->order < $last->order) {
                                $this->swapOrder($tagHolder, $last, 'order');
                            }
                            $tagHolder->parentOrder = $last->order;
                        }
                        // the now changed tag may has an invalid parent-order - because the parent is too small now
                        // we again use the find-holder API - which only returns holders that can contain the tag
                        if ($last->parentOrder > -1 && $this->findHolderByOrder($tags, $last) === null) {
                            $last->parentOrder = -1;
                        }
                        $tag->removed = true;
                    } elseif ($tag->startIndex > $last->endIndex) {
                        // since the tags are ordered by start-index we can finish as soon we are "behind" the last-tag
                        break;
                    }
                }
            }
            // 3) last step: remove obsolete tags and paired closers that found no counterpart
            $this->tags = [];
            foreach ($tags as $tag) {
                if (! $tag->removed) {
                    if ($tag->isObsolete() || $tag->isPairedCloser()) {
                        $tag->onConsolidationRemoval();
                    } else {
                        $this->tags[] = $tag;
                    }
                }
            }
            // the tags that were singular but now are real tags (paired tags) may have a improper nesting. We have to correct that
            // it can be assumed, all tags have a proper order here. Since when rendering, the paired tags again will be singular, we correct the nesting by applying a proper order & rightOrder
            foreach ($this->tags as $inner) {
                foreach ($this->tags as $outer) {
                    if ($outer->startIndex > $inner->endIndex) {
                        break;
                    } else {
                        // the $outer->order != $inner->order condition ensures, a tag will not contain itself!
                        if ($outer->order != $inner->order && $inner->isPaired() && $outer->isPaired() && $outer->startIndex <= $inner->startIndex && $outer->endIndex >= $inner->endIndex) {
                            // this ensures, that when tags with the same start & end index have the order respected for nesting
                            if ($outer->startIndex < $inner->startIndex || $outer->endIndex > $inner->endIndex || $outer->order < $inner->order) {
                                // if we detect a "wrong order" for nested paired tags (this assumes, all paired tags have the public property "rightOrder")
                                if ($inner->startIndex == $outer->startIndex && $inner->order < $outer->order) {
                                    $this->swapOrder($outer, $inner, 'order');
                                }
                                if ($inner->endIndex == $outer->endIndex && $inner->rightOrder > $outer->rightOrder) {
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
     */
    protected function swapOrder(editor_Segment_Tag $tag1, editor_Segment_Tag $tag2, string $propName): void
    {
        $cache = $tag1->$propName;
        $tag1->$propName = $tag2->$propName;
        $tag2->$propName = $cache;
        // the order must be adjusted in all other tags that may are nested into to the swapped tags
        if ($propName === 'order') {
            foreach ($this->tags as $tag) {
                if (! $tag->removed && $tag->order !== $tag1->order && $tag->order !== $tag2->order &&
                    ($tag->parentOrder === $tag1->order || $tag->parentOrder === $tag2->order)
                ) {
                    $tag->parentOrder = ($tag->parentOrder === $tag1->order) ? $tag2->order : $tag1->order;
                }
            }
        }
    }

    /**
     * Removes any parentOrder indices that point to non-existing indices
     */
    protected function fixParentOrders(): void
    {
        $orders = [];
        foreach ($this->tags as $tag) {
            $orders[] = $tag->order;
        }
        foreach ($this->tags as $tag) {
            if (! in_array($tag->parentOrder, $orders)) {
                $tag->parentOrder = -1;
            }
        }
    }

    /**
     * Changes the parent-order of tags in case of tag-merging
     */
    protected function changeParentOrder(int $from, int $to): void
    {
        foreach ($this->tags as $tag) {
            if ($tag->parentOrder === $from) {
                $tag->parentOrder = $to;
            }
        }
    }

    /* Logging API */

    /**
     * @throws \Zend_Exception
     */
    protected function createLogger(): ZfExtended_Logger
    {
        return Zend_Registry::get('logger')->cloneMe(static::$logger_domain);
    }

    /**
     * @throws \Zend_Exception
     */
    protected function logError(string $code, string $msg, array $errorData = []): array
    {
        $this->addErrorDetails($errorData);
        if ($this->captureErrors) {
            // when capturing the errors/exceptions the cade initiating the capture is responsible for processing them !
            $error = new ZfExtended_ErrorCodeException($code, $errorData);
            $error->setMessage($msg);
            $error->setDomain(static::$logger_domain);
            $this->capturedErrors[] = $error;
        } else {
            $this->createLogger()->error($code, $msg, $errorData);
        }

        return $errorData;
    }

    /**
     * To be extended in inheriting classes
     */
    protected function addErrorDetails(array &$errorData): void
    {
        $errorData['text'] = $this->text;
    }

    /* Debugging API */

    /**
     * Debug output
     */
    public function debug(): string
    {
        $newline = "\n";
        $debug = 'TEXT: "' . trim($this->text) . '"' . $newline;
        for ($i = 0; $i < count($this->tags); $i++) {
            $debug .= 'TAG ' . $i . ':' . $newline . trim($this->tags[$i]->debug()) . $newline . $newline;
        }

        return $debug;
    }

    /**
     * Debug rendering relevant props
     * @param editor_Segment_Tag[]|null $segmentTags
     */
    public function debugStructure(array $segmentTags = null): string
    {
        $tags = ($segmentTags === null) ? $this->tags : $segmentTags;
        $debug = "\n============== TAG STRUCTURE: " . count($tags) . " Tags  ==============\n";
        for ($i = 0; $i < count($tags); $i++) {
            $debug .= ($tags[$i]->debugProps() . "\n");
        }

        return $debug . "\n";
    }
}
