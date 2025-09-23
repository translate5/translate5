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

use editor_Segment_Internal_ContentTag as ContentTag;
use MittagQI\Translate5\ContentProtection\NumberProtector;
use MittagQI\Translate5\Segment\Tag\Placeable;
use MittagQI\Translate5\Tag\TagSequence;
use MittagQI\ZfExtended\Tools\Markup;
use PHPHtmlParser\Dom\Node\AbstractNode;
use PHPHtmlParser\Dom\Node\HtmlNode;

/**
 * Represents an Internal tag
 * Example <div class="single 123 internal-tag ownttip"><span title="&lt;ph ax:element-id=&quot;0&quot;&gt;&amp;lt;variable linkid=&quot;123&quot; name=&quot;1002&quot;&amp;gt;Geräte, Detailmaß A&amp;lt;/variable&amp;gt;&lt;/ph&gt;" class="short">&lt;1/&gt;</span><span data-originalid="6f18ea87a8e0306f7c809cb4f06842eb" data-length="-1" class="full">&lt;ph id=&quot;1&quot; ax:element-id=&quot;0&quot;&gt;&amp;lt;variable linkid=&quot;123&quot; name=&quot;1002&quot;&amp;gt;Geräte Detailmaß A&amp;lt;/variable&amp;gt;&lt;/ph&gt;</span></div>
 * The inner Content Tags are stored as special Tags editor_Segment_Internal_ContentTag
 */
final class editor_Segment_Internal_Tag extends editor_Segment_Tag
{
    /**
     * REGEX to remove internal tags from a markup string
     * Based on the internal-tag template, see editor_ImageTag::$htmlTagTpl
     * NOTE: only opening tag-brackets "<" are reliably escaped in the contents of the inner spans - what is against XML specs unfortunately
     * NOTE: the tilte-attribute of the first inner "short" span may contain unescaped markup - what again is against XML specs unfortunately
     * NOTE: editor_Models_Segment_InternalTag::REGEX_INTERNAL_TAGS does stumble over the afromentioned problems, that's why here is another regex
     * @var string
     */
    public const REGEX_REMOVE = '~<div\s*class="[^"]*internal-tag[^"]*"[^>]*><span[^>]*title="[^"]*"[^>]*>[^<]*</span><span[^>]*full[^>]*>[^<]*</span></div>~s';

    /**
     * As above, but only for single internal tags
     * Is agnostic to the order of internal-tag / single but expects, they can not be contained more than once
     */
    public const REGEX_REMOVE_SINGLE = '~<div\s*class="[^"]*(single|internal-tag)[^"]*(internal-tag|single)[^"]*"[^>]*><span[^>]*title="[^"]*"[^>]*>[^<]*</span><span[^>]*full[^>]*>[^<]*</span></div>~s';

    /**
     * Same as above, but intended to capture the contents of the classes & short-tag (which encodes the internal tag index)
     */
    public const REGEX_CAPTURE = '~<div\s*class="([^"]*internal-tag[^"]*)"[^>]*><span[^>]*title="[^"]*"[^>]*>([^<]*)</span><span[^>]*full[^>]*>[^<]*</span></div>~s';

    /**
     * @var string
     */
    public const CSS_CLASS = 'internal-tag';

    /**
     * @var string
     */
    public const CSS_CLASS_SINGLE = 'single';

    /**
     * @var string
     */
    public const CSS_CLASS_OPEN = 'open';

    /**
     * @var string
     */
    public const CSS_CLASS_CLOSE = 'close';

    /**
     * @var string
     */
    public const CSS_CLASS_NBSP = 'nbsp';

    /**
     * @var string
     */
    public const CSS_CLASS_NEWLINE = 'newline';

    /**
     * @var string
     */
    public const CSS_CLASS_SPACE = 'space';

    /**
     * @var string
     */
    public const CSS_CLASS_TAB = 'tab';

    /**
     * @var string
     */
    public const CSS_CLASS_CHAR = 'char';

    protected static ?string $type = editor_Segment_Tag::TYPE_INTERNAL;

    protected static ?string $nodeName = 'div';

    protected static ?string $identificationClass = self::CSS_CLASS;

    public int $_idx;

    public int $_sidx;

    /**
     * Replaces all Internal Tags in a segment-text
     */
    public static function replaceInternalTags(string $markup, string $replacement = ''): string
    {
        return preg_replace(self::REGEX_REMOVE, $replacement, $markup);
    }

    /**
     * Replaces all singular Internal Tags in a segment-text
     */
    public static function replaceSingleInternalTags(string $markup, string $replacement = ''): string
    {
        return preg_replace(self::REGEX_REMOVE_SINGLE, $replacement, $markup);
    }

    /**
     * Provides validating a list of DOMchildren to be the inner elements of a proper internal tag
     * This API is only needed where it is not known, if we deal with translate5 segment text or common markup (e.g. via InstantTranslate)
     */
    public static function domElementChildrenAreInternalTagChildren(DOMNodeList $domChildren = null): bool
    {
        if ($domChildren === null || $domChildren->count() != 2) {
            return false;
        }
        $item0 = $domChildren->item(0);
        $item1 = $domChildren->item(1);
        if ($item0 == null || $item0->nodeType !== XML_ELEMENT_NODE || strtolower($item0->nodeName) !== 'span') {
            return false;
        }
        if ($item1 == null || $item1->nodeType !== XML_ELEMENT_NODE || strtolower($item1->nodeName) !== 'span') {
            return false;
        }

        return true;
    }

    /**
     * Provides validating an array of HtmlNode children to be the inner elements of a proper internal tag
     * This API is only needed where it is not known, if we deal with translate5 segment text or common markup (e.g. via InstantTranslate)
     * @param AbstractNode[]|null $htmlChildren
     */
    public static function htmlNodeChildrenAreInternalTagChildren(array $htmlChildren = null): bool
    {
        if ($htmlChildren === null || count($htmlChildren) != 2) {
            return false;
        }
        $tag0Tag = (is_a($htmlChildren[0], HtmlNode::class)) ? $htmlChildren[0]->getTag() : null;
        $tag1Tag = (is_a($htmlChildren[1], HtmlNode::class)) ? $htmlChildren[0]->getTag() : null;
        if ($tag0Tag != null && $tag1Tag != null && strtolower($tag0Tag->name()) === 'span' && strtolower($tag1Tag->name()) === 'span') {
            return true;
        }

        return false;
    }

    /**
     * Helper to visualize internal tags in a markup string. The tags are turned to what is visualized in the frontend, <1>...</1> or <2/>
     */
    public static function visualizeTags(string $markup): string
    {
        return preg_replace_callback(self::REGEX_CAPTURE, function ($matches) {
            return Markup::unescapeText($matches[2]);
        }, $markup);
    }

    /**
     * @var ContentTag[]
     */
    private ?array $contentTags = null;

    private ?ContentTag $shortTag = null;

    private ?ContentTag $fullTag = null;

    /**
     * Prop is needed for the tag-comparision and tag-repair and represents the counterpart
     */
    public ?editor_Segment_Internal_Tag $counterpart = null;

    /**
     * API needed for cloning
     */
    private function addContentTag(ContentTag $tag): void
    {
        if ($this->contentTags === null) {
            $this->contentTags = [];
        }
        $this->contentTags[] = $tag;
        if ($tag->isShort()) {
            $this->shortTag = $tag;
        } elseif ($tag->isFull()) {
            $this->fullTag = $tag;
        }
    }

    /**
     * Evaluates if we wrap/represent a single HTML Tag of the segments content
     * @return boolean
     */
    public function isSingle(): bool
    {
        return $this->hasClass(self::CSS_CLASS_SINGLE);
    }

    /**
     * Evaluates if we wrap/represent a opening HTML Tag of the segments content
     * @return boolean
     */
    public function isOpening(): bool
    {
        return $this->hasClass(self::CSS_CLASS_OPEN);
    }

    /**
     * Evaluates if we wrap/represent a closing HTML Tag of the segments content
     * @return boolean
     */
    public function isClosing(): bool
    {
        return $this->hasClass(self::CSS_CLASS_CLOSE);
    }

    /**
     * retrieves the marker class representing the tag-type
     */
    public function getTagTypeClass(): string
    {
        if ($this->isSingle()) {
            return self::CSS_CLASS_SINGLE;
        }
        if ($this->isOpening()) {
            return self::CSS_CLASS_OPEN;
        }
        if ($this->isClosing()) {
            return self::CSS_CLASS_CLOSE;
        }

        return '';
    }

    /**
     * Evaluates, if the internal tag represents a whitespace tag
     * @return boolean
     */
    public function isWhitespace(): bool
    {
        return ($this->isSingle() && ($this->hasClass(self::CSS_CLASS_NEWLINE) || $this->hasClass(self::CSS_CLASS_NBSP) || $this->hasClass(self::CSS_CLASS_SPACE) || $this->hasClass(self::CSS_CLASS_TAB)));
    }

    /**
     * Evaluates, if the internal tag represents a special character that was turned to a tag to protect it from processing
     * @return boolean
     */
    public function isSpecialCharacter(): bool
    {
        return ($this->isSingle() && $this->hasClass(self::CSS_CLASS_CHAR));
    }

    /**
     * Evaluates, if the internal tag represents a newline
     * @return boolean
     */
    public function isNewline(): bool
    {
        return ($this->isSingle() && $this->hasClass(self::CSS_CLASS_NEWLINE));
    }

    /**
     * Evaluates, if the internal tag represents a non-breaking space
     * @return boolean
     */
    public function isNbsp(): bool
    {
        return ($this->isSingle() && $this->hasClass(self::CSS_CLASS_NBSP));
    }

    /**
     * Evaluates, if the internal tag represents a (breaking) space
     * @return boolean
     */
    public function isSpace(): bool
    {
        return ($this->isSingle() && $this->hasClass(self::CSS_CLASS_SPACE));
    }

    /**
     * Evaluates, if the internal tag represents a tab
     * @return boolean
     */
    public function isTab(): bool
    {
        return ($this->isSingle() && $this->hasClass(self::CSS_CLASS_TAB));
    }

    public function isPlaceable(): bool
    {
        return ($this->isSingle() && $this->hasClass(Placeable::MARKER_CLASS));
    }

    public function isNumber(): bool
    {
        return $this->isSingle() && $this->hasClass(NumberProtector::TAG_NAME);
    }

    /**
     * Retrieves the original index of the internal tag within the segment
     */
    public function getTagIndex(): int
    {
        if ($this->shortTag != null) {
            return $this->shortTag->getTagIndex();
        }

        return -1;
    }

    /**
     * Sets the tag-index of the inner short-tag
     * IMPORTANT: only meant e.g. for repair-scripts. The tag-index is set in the import
     * and must not be changed thereafter
     */
    public function setTagIndex(int $index): void
    {
        if ($this->shortTag != null) {
            if ($this->isOpening()) {
                $html = '&lt;' . $index . '&gt;';
            } elseif ($this->isClosing()) {
                $html = '&lt;/' . $index . '&gt;';
            } else {
                $html = '&lt;' . $index . '/&gt;';
            }
            $this->shortTag->setInnerHTML($html);
        }
    }

    public function getOriginalId(): ?string
    {
        if ($this->fullTag != null && $this->fullTag->hasData('originalid')) {
            return $this->fullTag->getData('originalid');
        }

        return null;
    }

    /**
     * Retrieves the potential rid-attripute of the underlying bpt/ept tag in case of a paired tag
     */
    public function getUnderlyingRid(): int
    {
        if ($this->shortTag != null && ($this->isOpening() || $this->isClosing())) {
            return $this->findIntAttribInTitle($this->shortTag->getAttribute('title'), 'rid');
        }

        return -1;
    }

    public function getUnderlyingId(): int
    {
        if ($this->shortTag != null) {
            return $this->findIntAttribInTitle($this->shortTag->getAttribute('title'), 'id');
        }

        return -1;
    }

    /**
     * Internal parser to find stuff in the encapsulated tag
     */
    private function findIntAttribInTitle(string $title, string $attributeName): int
    {
        if (! empty($title)) {
            $title = str_replace('&quot;', '"', $title);
            $pattern = '~ ' . $attributeName . '\s*=\s*"([0-9]+)"~';
            $matches = [];
            if (preg_match($pattern, $title, $matches) === 1 && count($matches) === 2) {
                return (int) $matches[1];
            }
        }

        return -1;
    }

    public function getContentLength(): int
    {
        if ($this->fullTag != null && $this->fullTag->hasData('length')) {
            return (int) $this->fullTag->getData('length');
        }

        return 0;
    }

    /**
     * Retrieves a hash that can be used to compare tags
     */
    public function getComparisionHash(): string
    {
        // we use our visual representation like "</6>" as key to compare tags
        if ($this->shortTag != null) {
            return Markup::unescapeAllQuotes($this->shortTag->getText());
        }

        return md5($this->render());
    }

    /**
     * Renders the short-tag as unescaped markup like "<5/>"
     */
    public function getShortTagMarkup(): string
    {
        return Markup::unescapeAllQuotes($this->shortTag->getText());
    }

    /**
     * Retrieves the shown Content/Markup of an placeable, number-tag or special char
     * For number-tags, this may differs if for source/target
     */
    public function getReplacedContent(bool $isSource = true): string
    {
        if ($this->isPlaceable()) {
            return Markup::unescape($this->fullTag->getContent());
        }
        if ($this->isSpecialCharacter()) {
            // special characters will only be rendered if they have a textual representation,
            // @see editor_Models_Segment_Whitespace::PROTECTED_CHARACTERS
            $text = $this->fullTag->getContent();

            return str_starts_with($text, '[') ? '' : $text;
        }
        if ($this->isNumber()) {
            $dataAttrib = $isSource ? 'source' : 'target';

            return $this->fullTag->getData($dataAttrib);
        }

        return $this->fullTag->getText();
    }

    /* *************************************** Overwritten Tag API *************************************** */

    /**
     * As soon as our internal spans are added we act as singular tags
     */
    public function isSingular(): bool
    {
        return ($this->contentTags !== null);
    }

    /**
     * Internal tags must not be splitted nor joined !
     */
    public function isSplitable(): bool
    {
        return false;
    }

    /**
     * Internal tags are only equal when their content is equal as well
     */
    public function isEqual(editor_Tag $tag, bool $withDataAttribs = true): bool
    {
        if (parent::isEqual($tag, $withDataAttribs)) {
            return $tag->renderChildren() == $this->renderChildren();
        }

        return false;
    }

    /**
     * Must be overwritten to ensure, the internal tag does add it's text nor text-length to the field-tags
     */
    public function getText(): string
    {
        return '';
    }

    /**
     * Must be overwritten to ensure, the internal tag does add it's text nor text-length to the field-tags
     */
    public function getTextLength(): int
    {
        return 0;
    }

    /**
     * The length as defined by the data-attribute
     */
    public function getDataLength(): int
    {
        return ($this->hasData('length')) ? intval($this->getData('length')) : 1;
    }

    /**
     * Must be overwritten to ensure, the internal tag does add it's text nor text-length to the field-tags
     */
    public function getLastChildsTextLength(): int
    {
        return 0;
    }

    /**
     * This renders our inner HTML
     */
    public function renderChildren(array $skippedTypes = null): string
    {
        if ($this->contentTags === null) {
            return parent::renderChildren($skippedTypes);
        }
        $html = '';
        foreach ($this->contentTags as $contentTag) {
            $html .= $contentTag->render();
        }

        return $html;
    }

    /**
     * Renders the replaced contents what differs for internal tags depending on the mode
     */
    public function renderReplaced(string $mode): string
    {
        $content = '';
        // QUIRK: is the feature with length-attributes for whitespace-tags still in use ?
        if ($mode === TagSequence::MODE_STRIPPED) {
            return '';
        } elseif ($mode === TagSequence::MODE_LABELED) {
            $content = $this->getLabeledContent();
        } elseif ($mode === TagSequence::MODE_ORIGINAL) {
            $content = $this->getOriginalContent();
        }
        $length = $this->getDataLength();
        if ($length === 1) {
            return $content;
        } elseif ($length > 1) {
            return str_repeat($content, $length);
        }

        return '';
    }

    /**
     * Provides the content for the replaced labeled mode
     */
    private function getLabeledContent(): string
    {
        if ($this->isSpecialCharacter()) {
            return editor_Models_Segment_Whitespace::LABEL_CHARACTER;
        } elseif ($this->isNewline()) {
            return editor_Models_Segment_Whitespace::LABEL_NEWLINE;
        } elseif ($this->isTab()) {
            return editor_Models_Segment_Whitespace::LABEL_TAB;
        } elseif ($this->isNbsp()) {
            return editor_Models_Segment_Whitespace::LABEL_NBSP;
        } elseif ($this->isSpace()) {
            return editor_Models_Segment_Whitespace::LABEL_SPACE;
        }

        return '';
    }

    /**
     * Provides the content for the replaced original mode
     */
    private function getOriginalContent(): string
    {
        if ($this->isSpecialCharacter()) {
            return '□';
        } elseif ($this->isNewline()) {
            return "\n";
        } elseif ($this->isTab()) {
            return "\t";
        } elseif ($this->isNbsp()) {
            return " ";
        } elseif ($this->isSpace()) {
            return ' ';
        } elseif ($this->isNumber()) {
            // Here dash-characters are used as they do not result in spellcheck-error
            // Initially, the idea was to use dash-character sequence of the same length
            // as protected number (e.g. '5,600' => '-----'), but the way of how number-tags
            // are processed during offsets calculation for spellcheck highlighting in browser
            // works in a way that assumes that a fixed-length placeholder should be used, see
            // controller/SegmentQualitiesBase.js:applyCustomMatches(), so just '---' is used here and there
            return '---';
        }

        return '';
    }

    /**
     * Needs to be overwritten to ignore the singular-prop when rendering
     */
    protected function renderStart(bool $withDataAttribs = true): string
    {
        return '<' . $this->getName() . $this->renderAttributes($withDataAttribs) . '>';
    }

    /**
     * Needs to be overwritten to ignore the singular-prop when rendering
     */
    protected function renderEnd(): string
    {
        return '</' . $this->getName() . '>';
    }

    /**
     * We do not add children to the tags-container but we build our inner tags from the tags-structure
     */
    public function sequenceChildren(TagSequence $tags, int $parentOrder = -1): void
    {
        if ($this->hasChildren()) {
            foreach ($this->children as $child) {
                if ($child->isText()) {
                    throw new Exception(
                        'Invalid internal-tag: Content or whitespace between the inner tags is not allowed [' .
                        $this->renderChildren() . ']'
                    );
                }
                $this->addContentTag(ContentTag::fromTag($child));
            }
        }
    }

    /**
     * Handled internally
     */
    public function addSegmentText(TagSequence $tags): void
    {
        if ($this->startIndex < $this->endIndex) {
            $this->addText($tags->getTextPart($this->startIndex, $this->endIndex));
        }
    }

    public function clone(bool $withDataAttribs = false, bool $withId = false): static
    {
        $clone = parent::clone($withDataAttribs, $withId);
        /* @var $clone editor_Segment_Internal_Tag */
        foreach ($this->contentTags as $contentTag) {
            $clone->addContentTag($contentTag->clone(true));
        }

        return $clone;
    }

    protected function furtherSerialize(stdClass $data): void
    {
        $data->contentTags = [];
        foreach ($this->contentTags as $contentTag) {
            $data->contentTags[] = $contentTag->jsonSerialize();
        }
    }

    protected function furtherUnserialize(stdClass $data): void
    {
        if (property_exists($data, 'contentTags')) {
            foreach ($data->contentTags as $data) {
                $this->addContentTag(ContentTag::fromJsonData($data));
            }
        }
    }

    public function debugTag(): string
    {
        return '<' . ($this->isClosing() ? '/' : '') . $this->getTagIndex() . ($this->isSingle() ? '/' : '') . '>';
    }
}
