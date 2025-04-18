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

use DOMNodeList;
use editor_Segment_Internal_Tag;
use editor_TagSequence;
use Exception;
use PHPHtmlParser\Dom\Node\HtmlNode;
use PHPHtmlParser\DTO\Tag\AttributeDTO;
use Throwable;
use ZfExtended_Exception;

/**
 * General Extension to use te FieldTags Model for a general Tag-Repair (not specific for internal or xliff tags)
 * The (nested) tags of a text (segment) will be converted to a sequence of simple image-tags representing the opening/closing tags of the original markup
 * This increases the chances to restore a tag where only the closing tag is returned by the service (DeepL)
 * Afterwards the original tags will be restored from the (potentially incomplete) markup sent back, lost tags will be attempted to insert/restored at their "scaled" word-position
 * In a worst-case scenario the pure text of the original markup will be restored (although until now there is no test/example for such scenario; it is a more theoretical case)
 * The processing of internal tags is pretty sophisticated, as they will be handled as tags with two children in the unparsing, then morph to singular tags holding their rendered contents as afterStart/beforeEnd Markup
 * and finally when pairing the paired (opening/closing) internal tags return to be non-singular tags but one hierarchy level higher compared to the unparsing. Therefore the "prepare pairing" phase had to be added
 *
 * Diagram of usage:
 * Create instance from original, valid markup     ->      Send "request markup" to the service [::getRequestHtml()]     ->     Restore original markup from service-result [::recreateTags($returnedMarkup)]
 *
 * Logic of restoration:
 * Try to restore from service-result    -> fails     Restore from stripped service-result     -> fails     Return stripped service-result
 *
 * @method Tag unparseHtml(string $html)
 * @property Tag[] $tags
 */
class Tags extends editor_TagSequence
{
    /**
     * @var bool
     */
    public const DO_DEBUG = false;

    protected static string $logger_domain = 'editor.tagrepair';

    /**
     * Each tag must have a unique id to be re-identifyable
     */
    private int $tagIdxCount = 0;

    /**
     * Holds the Markup send by request
     */
    private string $requestHtml;

    /**
     * Holds the text length of the markup as on instantiation
     */
    private int $originalTextLength;

    /**
     * Holds the number of words of the markup as on instantiation
     */
    private int $originalNumWords;

    /**
     * Holds any paired tags for the prapare pairing phase
     * @var Tag[]
     */
    private array $pairedTags = [];

    /**
     * Holds the markup the TagRepair was created with
     * Not to be confused with ::originalMarkup in editor_TagSequence
     */
    private string $originalHtml;

    /* general API */

    /**
     * Creates a new tag-repair for the given markup
     * The given markup must be syntactically valid markup !
     * @throws ZfExtended_Exception
     */
    public function __construct(string $markup, bool $preserveComments = false)
    {
        $this->originalHtml = $markup;
        if ($preserveComments) {
            $markup = Tag::replaceComments($markup);
        } else {
            $markup = Tag::stripComments($markup);
        }
        $this->_setMarkup($markup);
        // quirk: when no markup is contained, unparse will not be called and thus requestHtml remains empty
        if (count($this->tags) == 0) {
            $this->requestHtml = $this->text;
        }
        if (self::DO_DEBUG) {
            error_log('CONSTRUCT RepairTags for text [' . strip_tags($markup) . ']' . "\n" . '    Markup is: [' . $markup . ']' . "\n" . '    Request markup is: [' . $this->requestHtml . ']');
        }
    }

    /**
     * Retrieves the HTML prepared to be sent by request
     */
    public function getRequestHtml(): string
    {
        return $this->requestHtml;
    }

    /**
     * Provides the returned html from request and in return get's the fixed and re-applied markup
     * @throws ZfExtended_Exception
     */
    public function recreateTags(string $html, bool $detectUntranslated = true): string
    {
        // when the result is just a minor variation of the requested markup we return the original
        if ($detectUntranslated && $this->seemsUntranslated($html)) {
            return $this->originalHtml;
        }
        // when the result has all tags clustered on the start or end and this clusteringis substantially different
        // then in the source
        if ($this->invalidateDueToClusteredTags(Tag::countImgTagsOnlyStartOrEnd($html))) {
            if (self::DO_DEBUG) {
                error_log('INVALIDATE sent markup due to detected tag-clustering!');
            }
            $html = Tag::stripImgTags($html);
        }

        $this->evaluate();
        $this->invalidate();
        $this->reEvaluate($html);

        try {
            // rendering may produce errors with overlapping internal tags.
            // these errors will be automatically repaired by adjusting the start-index of the overlapping tag
            // therefore we dismiss these errors as this mechanic can be seen as part of our repair capabilities
            $this->captureErrors = true;
            $rendered = $this->render();
            // reset captured errors
            if (self::DO_DEBUG && count($this->capturedErrors) > 0) {
                error_log('RENDEREING RepairTags CREATED ERRORS:' . "\n");
                error_log('VISUALIZED MARKUP: ' . editor_Segment_Internal_Tag::visualizeTags($rendered) . "\n");
                // TODO FIXME: create an API in ZfExtended_ErrorCodeException for this
                foreach ($this->capturedErrors as $exception) {
                    $extraLog = '';
                    foreach ($exception->getErrors() as $key => $val) {
                        $extraLog .= "   \n" . $key . ': ' . $val;
                    }
                    error_log("\n\n" . $exception->getErrorCode() . ': ' . $exception->getMessage() . $extraLog);
                }
            }
            $this->capturedErrors = [];
            $this->captureErrors = false;

            if (self::DO_DEBUG) {
                error_log('RECREATE RepairTags successful recreation: [' . $rendered . ']' . "\n");
            }

            return $rendered;
        } catch (Throwable) {
            // reset captured errors
            $this->captureErrors = false;
            if (self::DO_DEBUG && count($this->capturedErrors) > 0) {
                error_log('RENDEREING RepairTags BY DISMISSING REQUESTED TAGS' . "\n");
                foreach ($this->capturedErrors as $item) {
                    error_log("\n" . $item->debug());
                }
            }
            $this->capturedErrors = [];
            // fallback: recreate original structure from scratch (without any sent tags)
            $this->invalidate();
            $this->reEvaluate(strip_tags($html));
            $rendered = $this->render();
            if (self::DO_DEBUG) { // @phpstan-ignore-line
                error_log('RECREATE RepairTags recreation failed, created fallback: [' . $rendered . ']' . "\n");
            }

            // if this still produces errors we may create tag-errors which will be reported or even lead to an exception
            return $rendered;
        }
    }

    /**
     * If the returned Markup is textually identical to the sent stuff
     * (whitespace differences do not count) we simply return the original...
     */
    private function seemsUntranslated(string $returnedHtml): bool
    {
        return trim(preg_replace('~\s+~', ' ', strip_tags($this->requestHtml))) ===
            trim(preg_replace('~\s+~', ' ', strip_tags($returnedHtml)));
    }

    /**
     * If we detected that in the returned markup all tags are clustered on the front or back,
     * we must decide, if we should invalidate this result
     */
    private function invalidateDueToClusteredTags(int $clusterSize): bool
    {
        // a single tag on beginning or end is no cluster
        if ($clusterSize > 1 || $clusterSize < -1) {
            $imgtags = Tag::countImgTagPositions($this->requestHtml);
            $isClustered = $imgtags->start === $imgtags->all || $imgtags->end === $imgtags->all;
            // if we do also have clustered tags it must have the same sign, otherwise invalidate
            if ($isClustered) {
                return $imgtags->start > 0 && $clusterSize < 0 || $imgtags->end > 0 && $clusterSize > 0;
            }
            // if the "distance" of the cluster is more than 1 we also invalidate
            if ($clusterSize > 0) {
                return ($clusterSize - $imgtags->start) > 1;
            }

            return ((-1 * $clusterSize) - $imgtags->end) > 1;
        }

        return false;
    }

    /* re-evaluation API */

    /**
     * Analyses the original word structure and stores them
     */
    private function evaluate()
    {
        $this->originalTextLength = $this->getTextLength();
        $this->originalNumWords = $this->countWords($this->text);
        $numTags = count($this->tags);
        for ($i = 0; $i < $numTags; $i++) {
            $this->tags[$i]->capturePosition($this, $this->originalTextLength);
        }
        if (static::DO_DEBUG) {
            error_log('RE-EVALUATE: chars before: ' . $this->originalTextLength . ', words before: ' . $this->originalNumWords . ', num tags before: ' . $numTags);
            for ($i = 0; $i < $numTags; $i++) {
                error_log('RE-EVALUATE before tag ' . $i . ': ( idx: ' . $this->tags[$i]->getRepairIndex() . ' | start: ' . $this->tags[$i]->startIndex . ' | end: ' . $this->tags[$i]->endIndex . ' | num words: ' . $this->tags[$i]->getNumWords($this) . ')');
            }
        }
    }

    /**
     * Marks all tags as invalid by invalidating their position properties
     */
    private function invalidate()
    {
        $numTags = count($this->tags);
        for ($i = 0; $i < $numTags; $i++) {
            $this->tags[$i]->invalidatePosition();
        }
    }

    /**
     * Re-evaluate our tags by the passed markup
     */
    private function reEvaluate(string $html)
    {
        $numTags = count($this->tags);
        if ($numTags < 1) {
            $this->setText(strip_tags($html));

            return;
        }
        $text = '';
        $textLength = 0;
        $parts = preg_split(Tag::REQUEST_TAG_REGEX, $html, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $part) {
            if (preg_match(Tag::REQUEST_TAG_REGEX, $part) === 1) {
                // a tag
                try {
                    // decompose the tag, errors will be catched
                    $tagParts = explode('-', explode('"', $part)[1]);
                    $tagType = $tagParts[1]; // can be "start", "end" or "singular"
                    $tag = $this->findByTagIdx(intval($tagParts[2]));
                    if ($tag != null && ($tagType == 'start' || $tagType == 'end' || $tagType == 'singular')) {
                        if ($tagType == 'start' || $tagType == 'singular') {
                            $tag->startIndex = $textLength;
                        }
                        if ($tagType == 'end' || $tagType == 'singular') {
                            $tag->endIndex = $textLength;
                        }
                    }
                } catch (Throwable) {
                    // we simply ignore an unparsable tag, what can we do ?
                    error_log('Segment/TagRepair/Tags: could not evaluate the request-tag ' . $part);
                }
            } else {
                // a text
                $text .= strip_tags(strval($part));
                $textLength = mb_strlen($text);
            }
        }
        $this->setText($text);
        // re-evaluate the word-positions of our tags and restore the text-indices
        $textLength = $this->getTextLength();
        $wordRatio = $this->countWords($this->text) / $this->originalNumWords;
        $textRatio = ($this->originalTextLength === 0) ? 1 : $textLength / $this->originalTextLength;
        // first the "real" non-singular tags
        for ($i = 0; $i < $numTags; $i++) {
            if (! $this->tags[$i]->isSingular()) {
                $this->tags[$i]->reEvaluateTagPosition($this, $textLength, $wordRatio, $textRatio);
            }
        }
        // first the singular tags (which may refer to the normal tags as their parent)
        for ($i = 0; $i < $numTags; $i++) {
            if ($this->tags[$i]->isSingular()) {
                $this->tags[$i]->reEvaluateSingularTagPosition($this, $textLength, $wordRatio, $textRatio);
            }
        }
        if (static::DO_DEBUG) {
            error_log('RE-EVALUATE: chars after: ' . $textLength . ', words after: ' . $this->countWords($this->text) . ', num tags after: ' . $numTags);
            for ($i = 0; $i < $numTags; $i++) {
                error_log('RE-EVALUATE recreated tag ' . $i . ': ( idx: ' . $this->tags[$i]->getRepairIndex() . ' | start: ' . $this->tags[$i]->startIndex . ' | end: ' . $this->tags[$i]->endIndex . ' | num words: ' . $this->tags[$i]->getNumWords($this) . ')');
            }
        }
    }

    /**
     * Helper API to count the words in a string. str_word_count cannot be taken since it does not support multibyte locales
     */
    public function countWords(string $text): int
    {
        return count(explode(' ', preg_replace('/\s+/', ' ', trim($text))));
    }

    /**
     * get a text-position the given number of words to the right
     */
    public function getNextWordsPosition(int $pos, int $words, bool $afterWhitespace = false): int
    {
        if ($words === 0) {
            return ($afterWhitespace) ? $this->forwardAfterWhitespace($pos) : $pos;
        }
        if ($pos < $this->getTextLength()) {
            // if the start is a whitespace we forward to the next non-whitespace
            while ($this->isWhitespaceCharAt($pos) && $pos < $this->getTextLength()) {
                $pos++;
            }
            $wasWhitespace = false;
            while ($pos < $this->getTextLength()) {
                $pos++;
                $isWhitespace = $this->isWhitespaceCharAt($pos);
                // we count the chunks down with every whitespace/non-whitespace change
                if ($isWhitespace && ! $wasWhitespace) {
                    $words--;
                }
                if ($words === 0) {
                    return ($afterWhitespace) ? $this->forwardAfterWhitespace($pos) : $pos;
                }
                $wasWhitespace = $isWhitespace;
            }
        }

        return $this->getTextLength();
    }

    /**
     * get a text-position the given number of words to the left
     */
    public function getPrevWordsPosition(int $pos, int $words, bool $beforeWhitespace = false): int
    {
        if ($words === 0) {
            return ($beforeWhitespace) ? $this->rewindBeforeWhitespace($pos) : min($pos + 1, $this->getTextLength());
        }
        if ($pos > 0) {
            // if the start is a whitespace we rewind to the next non-whitespace
            while ($pos > 0 && $this->isWhitespaceCharAt($pos - 1)) {
                $pos--;
            }
            $wasWhitespace = false;
            while ($pos >= 0) {
                $pos--;
                $isWhitespace = $this->isWhitespaceCharAt($pos);
                // we count the chunks down with every whitespace/non-whitespace change
                if ($isWhitespace && ! $wasWhitespace) {
                    $words--;
                }
                if ($words === 0) {
                    return ($beforeWhitespace) ? $this->rewindBeforeWhitespace($pos) : min($pos + 1, $this->getTextLength());
                }
                $wasWhitespace = $isWhitespace;
            }
        }

        return 0;
    }

    /**
     * Forwards a position that is known to be before whitespace behind it
     */
    private function forwardAfterWhitespace(int $position): int
    {
        while ($this->isWhitespaceCharAt($position) && $position < $this->getTextLength()) {
            $position++;
        }

        return $position;
    }

    /**
     * Forwards a position that is known to be after whitespace before it
     */
    private function rewindBeforeWhitespace(int $position): int
    {
        while ($position > 0 && $this->isWhitespaceCharAt($position - 1)) {
            $position--;
        }

        return $position;
    }

    /**
     * Retrieves the closest word-boundry to a position
     */
    public function getClosestWordPosition(int $pos): int
    {
        $next = $this->getNextWordsPosition(max($pos - 1, 0), 1);
        $prev = $this->getPrevWordsPosition(min($pos + 1, $this->getTextLength()), 1);
        if (abs($pos - $next) < abs($pos - $prev)) {
            return $next;
        }

        return $prev;
    }

    protected function isWhitespaceChar(string $char): bool
    {
        return ($char == ' ' || $char == "\r" || $char == "\n" || $char == "\t");
    }

    public function isWhitespaceCharAt(int $pos): bool
    {
        return $this->isWhitespaceChar($this->getTextCharAt($pos));
    }

    protected function getTextCharAt(int $pos): string
    {
        if ($pos < $this->getTextLength()) {
            return mb_substr($this->text, $pos, 1);
        }

        return '';
    }

    /* Tag index API */

    public function findByTagIdx(int $tagIdx): ?Tag
    {
        foreach ($this->tags as $tag) {
            if ($tag->getRepairIndex() === $tagIdx) {
                return $tag;
            }
        }

        return null;
    }

    public function findByOrder(int $order): ?Tag
    {
        if ($order > -1) {
            foreach ($this->tags as $tag) {
                if ($tag->order === $order) {
                    return $tag;
                }
            }
        }

        return null;
    }

    /* unparsing API */

    /**
     * Unparses Segment markup into FieldTags
     * @throws Exception
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
        // before rendering the request markup, we need to prepare paired tags (which need to manipulate the repairIndex in case of a successfull pairing)
        $this->preparePairing();
        // after unparsing we need to save the markup we send as request
        $this->requestHtml = $wrapper->renderChildrenForRequest();
        // sequence the nested tags as our children
        $wrapper->sequenceChildren($this);
        // consolidation / pairing
        $this->consolidate();
        // finalize unparsing
        $this->finalizeUnparse();
    }

    /* Consolidation / pre-pairing API */

    /**
     * Prepares the consolidation/pairing of tags
     * The paired closer tags need to know their openers tag index and use it for rendering the request (which is done before sequencing!)
     */
    protected function preparePairing()
    {
        foreach ($this->pairedTags as $tag) {
            $tag->preparePairing();
        }
        foreach ($this->pairedTags as $tag) {
            if ($tag->isPairedOpener()) {
                foreach ($this->pairedTags as $otherTag) {
                    if ($otherTag->isPairedCloser() && $otherTag->isOfType($tag->getType())) {
                        $tag->prePairWith($otherTag);
                    }
                }
            }
        }
    }

    /* Creation API */

    /**
     * @throws Exception
     */
    protected function createFromHtmlNode(HtmlNode $node, int $startIndex, array $children = null): \editor_Segment_Tag
    {
        $classNames = [];
        $attributes = [];
        $domTag = $node->getTag();
        foreach ($domTag->getAttributes() as $name => $attrib) {
            /* @var $attrib AttributeDTO */
            if ($name == 'class') {
                $classNames = explode(' ', trim($attrib->getValue()));
            } else {
                $attributes[$name] = $attrib->getValue();
            }
        }

        return $this->createRepairTag($classNames, $attributes, $domTag->name(), $startIndex, null, $children);
    }

    protected function createFromDomElement(\DOMElement $element, int $startIndex, DOMNodeList $children = null): \editor_Segment_Tag
    {
        $classNames = [];
        $attributes = [];
        if ($element->hasAttributes()) {
            foreach ($element->attributes as $attr) {
                if ($attr->nodeName == 'class') {
                    $classNames = explode(' ', trim($attr->nodeValue));
                } else {
                    $attributes[$attr->nodeName] = $attr->nodeValue;
                }
            }
        }

        return $this->createRepairTag($classNames, $attributes, $element->nodeName, $startIndex, $children, null);
    }

    private function createRepairTag(array $classNames, array $attributes, string $nodeName, int $startIndex, DOMNodeList $domChildren = null, array $htmlChildren = null): Tag
    {
        // InternalTag needs special processing to prevent them to be manipulated and to pair the open/close-pairs
        // Since we may deal with user-generated markup here, we not only rely on the class but also inspect the children to avoid quirks
        if (in_array(editor_Segment_Internal_Tag::CSS_CLASS, $classNames) && editor_Segment_Internal_Tag::hasNodeName($nodeName)
                && (editor_Segment_Internal_Tag::domElementChildrenAreInternalTagChildren($domChildren) || editor_Segment_Internal_Tag::htmlNodeChildrenAreInternalTagChildren($htmlChildren))) {
            $tag = new InternalTag($startIndex, 0, '', $nodeName, $this->tagIdxCount);
        } else {
            $tag = new Tag($startIndex, 0, '', $nodeName, $this->tagIdxCount);
        }
        // we need to prepare any paired tags before consolidation, so we need to know them before sequencing
        if ($tag->canBePaired()) {
            $this->pairedTags[] = $tag;
        }
        $this->tagIdxCount++;
        if (count($classNames) > 0) {
            foreach ($classNames as $cname) {
                $tag->addClass($cname);
            }
        }
        if (count($attributes) > 0) {
            foreach ($attributes as $name => $val) {
                $tag->addAttribute($name, $val);
            }
        }

        return $tag;
    }
}
