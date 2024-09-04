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

use editor_Segment_Tag;
use editor_Tag;
use editor_TagSequence;
use MittagQI\ZfExtended\Tools\Markup;
use stdClass;

/**
 * Abstraction for a "repair tag" used with the automatic tag repair
 * This Tag is able to evaluate & store additional information about the tags position regarding words in the text, it can represent any kind of tag
 * this information then is used to restore lost or incomplete tags
 * it has capabilities to be rendered as a "request" tag (that will be sent to the service); these request tag render two image-tags instead of an opening & closing tag to increase the chances to be able to restore incomplete tags
 *
 * @property Tag|\editor_TextNode[] $children
 * @phpstan-consistent-constructor
 */
class Tag extends editor_Segment_Tag
{
    /**
     * @var string
     */
    public const REQUEST_TAG_TPL = '<img id="t5tag-@TYPE@-@TAGIDX@" src="example.jpg" />';

    /**
     * @var string
     */
    public const REQUEST_TAG_REGEX = '~(<img\s*id="t5tag\-[a-z]+\-[0-9]+"\s*src="[^>]+"\s*/>)~i';

    /**
     * @var string
     */
    public const COMMENT_NODE_NAME = 't5comment';

    /**
     * @var string
     */
    public const COMMENTS_REPLACE_REGEX = '~<!--(.*)-->~Us';

    /**
     * Prepares comments to be parsed as comment-tags
     */
    public static function replaceComments(string $markup): string
    {
        return preg_replace_callback(static::COMMENTS_REPLACE_REGEX, function ($matches) {
            return '<' . self::COMMENT_NODE_NAME . ' comment="' . Markup::escapeForAttribute($matches[1]) . '" />';
        }, $markup);
    }

    /**
     * Strips all comments out of the markup
     */
    public static function stripComments(string $markup): string
    {
        return preg_replace(self::COMMENTS_REPLACE_REGEX, '', $markup);
    }

    /**
     * Counts the image-tags in the given HTML
     */
    public static function stripImgTags(string $html): string
    {
        return preg_replace('~<img[^>]*>~', '', $html);
    }

    /**
     * Counts the image-tags in the given HTML
     */
    public static function countImgTags(string $html): int
    {
        $count = 0;
        $html = preg_replace('~<img[^>]*>~', '', $html, -1, $count);

        return $count;
    }

    /**
     * Counts preceiding or trailing img-tags (positive for at the start, negative for at the end)
     * Or 0 if there are not only preceiding or following tags
     */
    public static function countImgTagsOnlyStartOrEnd(string $html): int
    {
        $imgtags = self::countImgTagPositions($html);
        if ($imgtags->start > 0 && $imgtags->all === $imgtags->start) {
            return $imgtags->start;
        } elseif ($imgtags->end > 0 && $imgtags->all === $imgtags->end) {
            return -1 * $imgtags->end;
        }

        return 0;
    }

    /**
     * Counts the position of tags: at the start, end or inbetween
     * @return stdClass{all: int,start: int, end: int, inbetween: int}
     */
    public static function countImgTagPositions(string $html): stdClass
    {
        // detect at the start
        $result = new stdClass();
        $result->all = self::countImgTags($html);
        $result->start = self::countImgTagsByPattern($html, '~^<img[^>]*>~');
        $result->end = self::countImgTagsByPattern($html, '~<img[^>]*>$~');
        $result->inbetween = $result->all - ($result->start + $result->end);

        return $result;
    }

    private static function countImgTagsByPattern(string $html, string $pattern): int
    {
        $count = 0;
        $amount = 0;
        do {
            $html = preg_replace($pattern, '', $html, 1, $count);
            $amount += ($count > 0) ? 1 : 0;
        } while ($count > 0);

        return $amount;
    }

    /**
     * We need to expand the singular tags to cover the xliff tags
     * @var string[]
     */
    protected static array $singularTypes = ['img', 'input', 'br', 'hr', 'wbr', 'area', 'col', 'embed', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'command', 'x', 'bx', 'ex', self::COMMENT_NODE_NAME];

    protected static ?string $type = 'repair';

    /**
     * A unique ID that will re-identify the after the request returned and when recreating the original state
     */
    protected int $repairIdx;

    /**
     * Additional markup to render after our start tag
     */
    protected string $afterStartMarkup = '';

    /**
     * Additional markup to render before our end tag
     */
    protected string $beforeEndMarkup = '';

    /**
     * The number of words we cover
     */
    protected int $numWords;

    /**
     * The number of words before relative to the whole text
     */
    protected int $numWordsBefore;

    /**
     * The number of words after relative to the whole text
     */
    protected int $numWordsAfter;

    /**
     * The number of words before relative to the parent tag
     */
    protected int $numWordsBeforeParent;

    /**
     * If a singular tag is before or after whitespace
     */
    protected bool $isBeforeWhitespace;

    /**
     * The number of words after relative to the parent tag
     */
    protected int $numWordsAfterParent;

    public int $oldStartIndex;

    public int $oldEndIndex;

    /**
     * The repairIdx of our parentTag
     */
    protected int $parentTagIdx;

    /**
     * Defines, if a tag is on the left or right side of the parent tag and does not contain text
     */
    public bool $isLateral = false;

    public function __construct(int $startIndex, int $endIndex, string $category = '', string $nodeName = 'span', int $repairIdx = 0)
    {
        $this->startIndex = $startIndex;
        $this->endIndex = $endIndex;
        $this->category = $category;
        $this->name = strtolower($nodeName);
        $this->repairIdx = $repairIdx;
        $this->singular = in_array($nodeName, static::$singularTypes);
    }

    /**
     * retrieves the unique index of the tag
     */
    public function getRepairIndex(): int
    {
        return $this->repairIdx;
    }

    /**
     * Must only be used in the pre pairing phase to manipulate the repair index for paired internal tags ...
     */
    public function setRepairIndex(int $idx): void
    {
        $this->repairIdx = $idx;
    }

    protected function createBaseClone(): static
    {
        return new static($this->startIndex, $this->endIndex, $this->category, $this->name);
    }

    /**
     * ANY Repair tags shall not be be consolidated
     * {@inheritDoc}
     * @see editor_Segment_Tag::isEqualType()
     */
    public function isEqualType(editor_Tag $tag): bool
    {
        return false;
    }

    public function addAttribute($name, $val = null): static
    {
        // crucial: comment tags must have the unescaped value as comment-text (stored in the ::replaceComments prop)
        if ($val !== null && $this->isComment() && $name === 'comment') {
            // we have to revert the escaping applied in ::replaceComments
            $this->afterStartMarkup = Markup::unescapeFromAttribute($val);
        }
        parent::addAttribute($name, $val);

        return $this;
    }

    /**
     * Repair tags that represent an comment will act specially, e.g. render a comment instead of tags
     */
    protected function isComment(): bool
    {
        return ($this->name === self::COMMENT_NODE_NAME);
    }

    /* re-evaluation API */

    /**
     * captures the position in words when evaluationg the original
     * also resets the text indices
     */
    public function capturePosition(Tags $tags, int $textLength)
    {
        $this->numWords = 0;
        $this->numWordsBefore = 0;
        $this->numWordsAfter = 0;
        $this->numWordsBeforeParent = -1;
        $this->numWordsAfterParent = -1;
        $this->isBeforeWhitespace = false;
        $this->parentTagIdx = -1;
        if (! $this->isSingular() && $this->endIndex > $this->startIndex) {
            $this->numWords = $this->getNumWords($tags);
        } elseif ($this->isSingular()) {
            $this->isBeforeWhitespace = $tags->isWhitespaceCharAt($this->startIndex);
        }
        if ($this->startIndex > 0) {
            $this->numWordsBefore = $tags->countWords($tags->getTextPart(0, $this->startIndex));
        }
        if ($this->endIndex < $textLength) {
            $this->numWordsAfter = $tags->countWords($tags->getTextPart($this->endIndex, $textLength));
        }
        $parentTag = $tags->findByOrder($this->parentOrder);
        if ($parentTag != null) {
            $this->parentTagIdx = $parentTag->getRepairIndex();
            if ($this->startIndex > $parentTag->startIndex) {
                $this->numWordsBeforeParent = $tags->countWords($tags->getTextPart($parentTag->startIndex, $this->startIndex));
            }
            if ($this->endIndex < $parentTag->endIndex) {
                $this->numWordsAfterParent = $tags->countWords($tags->getTextPart($this->endIndex, $parentTag->endIndex));
            }
        }
        $this->oldStartIndex = $this->startIndex;
        $this->oldEndIndex = $this->endIndex;
    }

    /**
     * unsets our text positions
     */
    public function invalidatePosition(): void
    {
        $this->startIndex = -1;
        $this->endIndex = -1;
    }

    /**
     * Retrieves the number of words currently covered by the tag
     */
    public function getNumWords(Tags $tags): int
    {
        if ($this->isSingular()) {
            return 0;
        }

        return $tags->countWords($tags->getTextPart($this->startIndex, $this->endIndex));
    }

    /**
     * Recreates the tag position for a full, non-singular tag
     */
    public function reEvaluateTagPosition(Tags $tags, int $textLength, float $wordRatio, float $textRatio): void
    {
        // both positions could be restored
        if ($this->startIndex >= 0 && $this->endIndex >= 0) {
            // we have to care about all thinkable mishaps
            if ($this->startIndex >= $textLength && $this->endIndex >= $textLength) {
                $this->startIndex = $this->endIndex = $textLength;
            } elseif ($this->endIndex >= $textLength) {
                $this->endIndex = $textLength;
            } elseif ($this->startIndex > $this->endIndex) {
                if ($this->startIndex >= $textLength) {
                    $this->startIndex = $this->endIndex = $textLength;
                } else {
                    // in case the end-position is before the start we invalidate the end as we judge the start-position to be more important. This is purely a matter of taste though
                    $this->endIndex = -1;
                }
            }
        }
        $parentTag = null;
        $parentTagValid = false;
        $parentNumWords = $numWords = $halfNumWords = 0;
        // trick: if start and/or end index is lost, we first try to reEvaluate a parent tag if there was one
        if (($this->startIndex < 0 || $this->endIndex < 0) && $this->parentTagIdx > -1) {
            $parentTag = $tags->findByTagIdx($this->parentTagIdx);
            if ($parentTag != null) {
                if ($parentTag->startIndex < 0 || $parentTag->endIndex < 0) {
                    $parentTag->reEvaluateTagPosition($tags, $textLength, $wordRatio, $textRatio);
                }
                // we only take a parentTag into account if it is found and has content
                if ($parentTag->startIndex >= 0 && $parentTag->endIndex > $parentTag->startIndex) {
                    $parentTagValid = true;
                    $parentNumWords = $parentTag->getNumWords($tags);
                    $numWords = round(($parentNumWords / ($this->numWords + $this->numWordsBeforeParent + $this->numWordsAfterParent)) * $this->numWords);
                    $halfNumWords = floor(ceil(($parentNumWords / ($this->numWords + $this->numWordsBeforeParent + $this->numWordsAfterParent)) * $this->numWords) / 2);
                }
            }
        }
        // no indexes could be restored, the tag was completely lost !
        if ($this->startIndex < 0 && $this->endIndex < 0) {
            // if we have a faound parent tag, we will restore relative to it's position
            if ($parentTagValid) {
                if ($parentNumWords > 1) {
                    // calculate relative number of words
                    $oldCenter = $this->oldStartIndex + (($this->oldEndIndex - $this->oldStartIndex) / 2);
                    $oldParentCenter = $parentTag->oldStartIndex + (($parentTag->oldEndIndex - $parentTag->oldStartIndex) / 2);
                    $parentCenter = $parentTag->startIndex + (($parentTag->endIndex - $parentTag->startIndex) / 2);
                    $relativeCenter = round($parentCenter + (($oldCenter - $oldParentCenter) * $textRatio));
                    $this->startIndex = $tags->getPrevWordsPosition($relativeCenter, $halfNumWords);
                    $this->endIndex = $tags->getNextWordsPosition($relativeCenter, $halfNumWords);
                    if ($this->startIndex < $parentTag->startIndex) {
                        $this->startIndex = $parentTag->startIndex;
                        $this->endIndex = min($tags->getNextWordsPosition($this->startIndex, $numWords), $parentTag->endIndex);
                    } elseif ($this->endIndex > $parentTag->endIndex) {
                        $this->endIndex = $parentTag->endIndex;
                        $this->startIndex = max($tags->getPrevWordsPosition($this->endIndex, $numWords), $parentTag->startIndex);
                    }
                } else {
                    $this->startIndex = $parentTag->startIndex;
                    $this->endIndex = $parentTag->endIndex;
                }
            } else {
                // we will restore it from the scaled center, what mostly will be wrong but there is nothing we can do ..
                $numWords = round($this->numWords * $wordRatio);
                if ($this->oldStartIndex == 0) {
                    $this->startIndex = 0;
                    $this->endIndex = $tags->getNextWordsPosition(0, $numWords);
                } else {
                    $this->endIndex = $tags->getClosestWordPosition(round($this->oldEndIndex * $textRatio));
                    $this->startIndex = $tags->getPrevWordsPosition($this->endIndex, $numWords);
                }
            }
        }
        // only the start-tag got lost
        if ($this->startIndex < 0) {
            if ($parentTagValid) {
                $this->startIndex = max($tags->getPrevWordsPosition($this->endIndex, $numWords), $parentTag->startIndex);
            } else {
                $numWords = round(($this->numWords) * $wordRatio);
                $this->startIndex = $tags->getPrevWordsPosition($this->endIndex, $numWords);
            }
        }
        // only the end-tag got lost
        if ($this->endIndex < 0) {
            if ($parentTagValid) {
                $this->endIndex = min($tags->getNextWordsPosition($this->startIndex, $numWords), $parentTag->endIndex);
            } else {
                $numWords = round(($this->numWords) * $wordRatio);
                $this->endIndex = $tags->getNextWordsPosition($this->startIndex, $numWords);
            }
        }
    }

    /**
     * Recreates the tag position for a singular tag
     */
    public function reEvaluateSingularTagPosition(Tags $tags, int $textLength, float $wordRatio, float $textRatio): void
    {
        if ($this->startIndex < 0) {
            // our position can not be found
            if ($this->parentTagIdx > -1) {
                $parentTag = $tags->findByTagIdx($this->parentTagIdx);
                // when singular tags are evaluated, all full tags are already evaluated
                if ($parentTag != null && $parentTag->startIndex >= 0 && $parentTag->endIndex >= 0) {
                    if ($parentTag->startIndex == $parentTag->endIndex) {
                        $this->startIndex = $this->endIndex = $parentTag->startIndex;
                    } elseif ($this->numWordsAfterParent > $this->numWordsBeforeParent) {
                        $numWords = round($this->numWordsAfterParent * $wordRatio);
                        $this->startIndex = max($tags->getPrevWordsPosition($parentTag->endIndex, $numWords, $this->isBeforeWhitespace), $parentTag->startIndex);
                    } else {
                        $numWords = round($this->numWordsBeforeParent * $wordRatio);
                        $this->startIndex = min($tags->getNextWordsPosition($parentTag->startIndex, $numWords, ! $this->isBeforeWhitespace), $parentTag->endIndex);
                    }
                }
            }
            // if no parentTag was found, we have to evaluate relative to the holder
            if ($this->startIndex < 0) {
                if ($this->numWordsAfter > $this->numWordsBefore) {
                    $numWords = round($this->numWordsAfter * $wordRatio);
                    $this->startIndex = $tags->getPrevWordsPosition($textLength, $numWords, $this->isBeforeWhitespace);
                } else {
                    $numWords = round($this->numWordsBefore * $wordRatio);
                    $this->startIndex = $tags->getNextWordsPosition(0, $numWords, ! $this->isBeforeWhitespace);
                }
            }
            if ($this->endIndex < 0) {
                $this->endIndex = $this->startIndex;
            }
        }
    }

    /* sequencing API */

    public function sequence(editor_TagSequence $tags, int $parentOrder): void
    {
        $this->prepareSequencing();
        parent::sequence($tags, $parentOrder);
    }

    /**
     * This Method is called before the tag is sequenced when unparsing
     * We then add any tags at the end or start of our children to the start / end markup
     */
    public function prepareSequencing(): void
    {
        if ($this->hasChildren()) {
            // we mark all nodes from the start & from the end, that have no text before/after them as lateral
            $numChildren = count($this->children);
            $hasLateral = false;
            for ($i = 0; $i < $numChildren; $i++) {
                if ($this->children[$i]->getTextLength() > 0) {
                    break;
                }
                if (! $this->children[$i]->isText()) {
                    $hasLateral = $this->children[$i]->isLateral = true;
                }
            }
            for ($i = $numChildren - 1; $i >= 0; $i--) {
                if ($this->children[$i]->getTextLength() > 0) {
                    break;
                }
                if (! $this->children[$i]->isText()) {
                    $hasLateral = $this->children[$i]->isLateral = true;
                }
            }
            if ($hasLateral) {
                $children = [];
                $isLeft = true;
                for ($i = 0; $i < $numChildren; $i++) {
                    if (! $this->children[$i]->isText() && $this->children[$i]->isLateral) {
                        if ($isLeft) {
                            $this->afterStartMarkup .= $this->children[$i]->render();
                        } else {
                            $this->beforeEndMarkup .= $this->children[$i]->render();
                        }
                    } else {
                        $children[] = $this->children[$i];
                        $isLeft = false;
                    }
                }
                $this->children = $children;
            }
        }
    }

    /* Request Rendering API */

    public function renderForRequest(): string
    {
        if ($this->isSingular()) {
            return $this->renderRequestTag('singular');
        }

        // instead of a opener & closer we render
        return $this->renderRequestTag('start')
            . $this->renderChildrenForRequest()
            . $this->renderRequestTag('end');
    }

    /**
     * renders the children for request
     */
    public function renderChildrenForRequest(): string
    {
        $html = '';
        if ($this->hasChildren()) {
            foreach ($this->children as $child) {
                // we have either a TextNode or a repair-tag
                if ($child->isText()) {
                    $html .= $child->render();
                } else {
                    /** @var Tag $child */
                    $html .= $child->renderForRequest();
                }
            }
        }

        return $html;
    }

    protected function renderRequestTag(string $type): string
    {
        $tag = str_replace('@TYPE@', $type, static::REQUEST_TAG_TPL);

        return str_replace('@TAGIDX@', strval($this->repairIdx), $tag);
    }

    /* Consolidation / pre pairing API */

    /**
     * Retrieves, if the tag potentially can be paired in the pairing phase
     */
    public function canBePaired(): bool
    {
        return false;
    }

    /**
     * This API is called before consolidation and before rendering for request
     */
    public function preparePairing(): void
    {
    }

    /**
     * This API is called before consolidation and before rendering for request
     */
    public function prePairWith(Tag $tag): void
    {
    }

    /* Rendering API */

    protected function renderStart(bool $withDataAttribs = true): string
    {
        if ($this->isComment()) {
            return '<!--' . $this->afterStartMarkup . '-->';
        }

        return parent::renderStart($withDataAttribs) . $this->afterStartMarkup;
    }

    protected function renderEnd(): string
    {
        return $this->beforeEndMarkup . parent::renderEnd();
    }

    public function cloneForRendering(): static
    {
        $clone = $this->clone(true, true);
        $clone->cloneOrder($this);
        $clone->setInnerMarkup($this->afterStartMarkup, $this->beforeEndMarkup);

        return $clone;
    }

    protected function setInnerMarkup(string $afterStartMarkup, string $beforeEndMarkup): void
    {
        $this->afterStartMarkup = $afterStartMarkup;
        $this->beforeEndMarkup = $beforeEndMarkup;
    }

    /* Serialization API */

    /**
     * Use in inheriting classes for further serialization
     */
    protected function furtherSerialize(stdClass $data): void
    {
        $data->repairIdx = $this->repairIdx;
        $data->afterStartMarkup = $this->afterStartMarkup;
        $data->beforeEndMarkup = $this->beforeEndMarkup;
    }

    /**
     * Use in inheriting classes for further unserialization
     */
    protected function furtherUnserialize(stdClass $data): void
    {
        $this->repairIdx = intval($data->repairIdx);
        $this->afterStartMarkup = $data->afterStartMarkup;
        $this->beforeEndMarkup = $data->beforeEndMarkup;
    }
}
