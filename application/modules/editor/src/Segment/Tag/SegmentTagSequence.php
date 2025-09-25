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

namespace MittagQI\Translate5\Segment\Tag;

use DOMElement;
use DOMNodeList;
use editor_Segment_NewlineTag;
use editor_Segment_PlaceholderTag;
use editor_Segment_Tag;
use editor_Segment_TagCreator;
use editor_Segment_TrackChanges_DeleteTag;
use editor_Segment_TrackChanges_InsertTag;
use MittagQI\Translate5\Tag\TagSequence;
use PHPHtmlParser\Dom\Node\HtmlNode;
use stdClass;
use ZfExtended_Exception;

/**
 * Abstraction to bundle the segment's text and it's internal tags to an OOP accessible structure
 * The structure of the tags in this class is a simple sequence, any nesting / interleaving is covered with rendering /
 * unparsing
 */
class SegmentTagSequence extends TagSequence
{
    public function __construct(?string $text)
    {
        $this->_setMarkup($text);
    }

    /**
     * Returns the field text (which covers the textual contents of internal tags as well !)
     */
    public function getFieldText(bool $stripTrackChanges = false, bool $condenseBlanks = true): string
    {
        if ($stripTrackChanges && (count($this->tags) > 0)) {
            return $this->getFieldTextWithoutTrackChanges($condenseBlanks);
        }

        return $this->text;
    }

    /**
     * Retrieves our field-text lines.
     * This means, that all TrackChanges Del Contents are removed and our field-text is splitted by all existing
     * Internal Newline tags
     * @return string[]
     */
    public function getFieldTextLines(bool $condenseBlanks = true): array
    {
        $clone = $this->cloneWithoutTrackChanges([editor_Segment_Tag::TYPE_INTERNAL], $condenseBlanks);
        $clone->replaceTagsForLines();

        return explode(editor_Segment_NewlineTag::RENDERED, $clone->render());
    }

    public function getFieldTextLength(bool $stripTrackChanges = false, bool $condenseBlanks = true): int
    {
        if ($stripTrackChanges && (count($this->tags) > 0)) {
            return mb_strlen($this->getFieldTextWithoutTrackChanges($condenseBlanks));
        }

        return $this->getTextLength();
    }

    public function isFieldTextEmpty(bool $stripTrackChanges = false, bool $condenseBlanks = true): bool
    {
        if ($stripTrackChanges && (count($this->tags) > 0)) {
            return ($this->getFieldTextLength(true, $condenseBlanks) == 0);
        }

        return ($this->getTextLength() === 0);
    }

    /**
     * Retrieves the internal tags of a certain type
     * @return editor_Segment_Tag[]
     */
    public function getByType(string $type, bool $includeDeleted = false): array
    {
        $result = [];
        foreach ($this->tags as $tag) {
            if ($tag->getType() == $type && ($includeDeleted || ! $tag->wasDeleted)) {
                $result[] = $tag;
            }
        }

        return $result;
    }

    /**
     * Removes the internal tags of a certain type
     */
    public function removeByType(string $type, bool $skipDeleted = false)
    {
        $result = [];
        $replace = false;
        foreach ($this->tags as $tag) {
            if ($tag->getType() == $type && (! $skipDeleted || ! $tag->wasDeleted)) {
                $replace = true;
            } else {
                $result[] = $tag;
            }
        }
        if ($replace) {
            $this->tags = $result;
            $this->fixParentOrders();
        }
    }

    /**
     * Checks if a internal tag of a certain type is present
     */
    public function hasType(string $type, bool $includeDeleted = false): bool
    {
        foreach ($this->tags as $tag) {
            if ($tag->getType() == $type && ($includeDeleted || ! $tag->wasDeleted)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves if there are trackchanges tags present
     */
    public function hasTrackChanges(): bool
    {
        return $this->hasType(editor_Segment_Tag::TYPE_TRACKCHANGES, true);
    }

    /**
     * Checks if a internal tag of a certain type and class is present
     */
    public function hasTypeAndClass(string $type, string $className, bool $includeDeleted = false): bool
    {
        foreach ($this->tags as $tag) {
            if ($tag->getType() == $type && $tag->hasClass($className) && ($includeDeleted || ! $tag->wasDeleted)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if a internal tag of a certain type is present that has at least one of the given classnames
     * @param string[] $classNames
     */
    public function hasTypeAndClasses(string $type, array $classNames, bool $includeDeleted = false): bool
    {
        foreach ($this->tags as $tag) {
            if ($tag->getType() == $type && $tag->hasClasses($classNames) && ($includeDeleted || ! $tag->wasDeleted)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves if there are tags of the specified class and type that either are between the given boundaries
     * or overlap with the section defined by the given boundaries
     */
    public function hasTypeAndClassBetweenIndices(
        string $type,
        string $className,
        int $fromIdx,
        int $toIdx,
        bool $includeDeleted = false,
    ): bool {
        foreach ($this->tags as $tag) {
            if ($tag->getType() === $type &&
                $tag->hasClass($className) &&
                ($includeDeleted || ! $tag->wasDeleted) &&
                (
                    ($tag->startIndex >= $fromIdx && $tag->startIndex < $toIdx) ||
                    ($tag->endIndex >= $fromIdx && $tag->endIndex < $toIdx)
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves, if there are any (by default non-deleted) tags with a text-length of "0" (internal tags)
     * at the given index that are not nested into the tags left/right of that position
     */
    public function hasNonNestedTagsAtIndex(
        int $index,
        editor_Segment_Tag $leftTag,
        editor_Segment_Tag $rightTag,
        bool $includeDeleted = false,
    ): bool {
        foreach ($this->tags as $tag) {
            if (($includeDeleted || ! $tag->wasDeleted) &&
                $tag !== $leftTag &&
                $tag !== $rightTag &&
                $tag->startIndex === $index &&
                $tag->endIndex === $index &&
                (
                    $tag->parentOrder === -1 ||
                    ($tag->parentOrder !== $leftTag->order && $tag->parentOrder !== $rightTag->order)
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves, how many internal tags representing whitespace, are present
     */
    public function getNumLineBreaks(): int
    {
        $numLineBreaks = 0;
        foreach ($this->tags as $tag) {
            /** @var \editor_Segment_Internal_Tag $tag */
            if ($tag->getType() == editor_Segment_Tag::TYPE_INTERNAL && $tag->isNewline()) {
                $numLineBreaks++;
            }
        }

        return $numLineBreaks;
    }

    /* Unparsing API */

    protected function finalizeUnparse(): void
    {
        parent::finalizeUnparse();
        // setting the wasDeleted / wasInserted properties of our tags
        $this->evaluateDeletedInserted();
    }

    protected function createFromHtmlNode(HtmlNode $node, int $startIndex, array $children = null): editor_Segment_Tag
    {
        return editor_Segment_TagCreator::instance()->fromHtmlNode($node, $startIndex);
    }

    protected function createFromDomElement(DOMElement $element, int $startIndex, DOMNodeList $children = null): editor_Segment_Tag
    {
        return editor_Segment_TagCreator::instance()->fromDomElement($element, $startIndex);
    }

    /* Cloning API */

    protected function createClone(): self
    {
        return new self($this->text);
    }

    /**
     * Clones the tags with only the types of tags specified
     * Note, that you will not be able to filter trackchanges-tags out, use ::cloneWithoutTrackChanges instead for this
     */
    public function cloneFiltered(array $includedTypes = null, bool $finalize = true): static
    {
        /** @var static $clonedTags */
        $clonedTags = $this->createClone();
        foreach ($this->tags as $tag) {
            if ($tag->getType() == editor_Segment_Tag::TYPE_TRACKCHANGES || ($includedTypes == null || in_array($tag->getType(), $includedTypes))) {
                $clonedTags->addTag($tag->clone(true, true), $tag->order, $tag->parentOrder);
            }
        }
        if ($finalize) {
            $clonedTags->fixParentOrders();
        }

        return $clonedTags;
    }

    /**
     * Clones without trackchanges tags. Deleted contents (in del-tags) will be removed and all text-lengths/indices
     * will be adjusted
     */
    public function cloneWithoutTrackChanges(array $includedTypes = null, bool $condenseBlanks = true): static
    {
        $clonedTags = $this->cloneFiltered($includedTypes, false);
        // if no trackchanges-tags were removed, the method will not fix parent orders...
        if (! $clonedTags->deleteTrackChangesTags($condenseBlanks)) {
            $clonedTags->fixParentOrders();
        }

        return $clonedTags;
    }

    /**
     * Replaces the tag at the given index to a placehandler.
     * Be aware, that this can only be done with singular tags currently and otherwise leads to an exception
     * @throws ZfExtended_Exception
     */
    public function toPlaceholderAt(int $index, string $placeholder): editor_Segment_PlaceholderTag
    {
        if ($index < count($this->tags)) {
            $tag = $this->tags[$index];
            if ($tag->isSingular()) {
                $this->tags[$index] = new editor_Segment_PlaceholderTag($tag->startIndex, $tag->endIndex, $placeholder);

                return $this->tags[$index];
            }

            throw new ZfExtended_Exception('Only singular Segment-tags can currently be turned to placeholder-tags');
        }

        throw new ZfExtended_Exception('toPlaceholderAt: Index out of boundaries');
    }

    /**
     * Removes all TrackChanges tags, also deletes all contents of del-tags
     * Returns, if stuff was removed and the tags resorted/reordered
     */
    private function deleteTrackChangesTags(bool $condenseBlanks = true): bool
    {
        $this->sort(); // making sure we're in order
        $this->evaluateDeletedInserted(); // ensure this is properly set (normally always the case)
        foreach ($this->condenseTrackChangesDelTags(true) as $tag) {
            if ($tag->getType() === editor_Segment_Tag::TYPE_TRACKCHANGES) {
                /** @var editor_Segment_TrackChanges_DeleteTag|editor_Segment_TrackChanges_InsertTag $tag */
                $tag->wasDeleted = true;
                if ($tag->isDeleteTag() && $tag->endIndex > $tag->startIndex) {
                    if ($condenseBlanks) {
                        $boundries = $this->getRemovableBlanksBoundries($tag->startIndex, $tag->endIndex);
                        if ($boundries->left < $tag->startIndex && $boundries->right > $tag->endIndex) {
                            // if there are removable blanks on both sides it is meaningless, on which side we leave one
                            $tag->startIndex = $boundries->left;
                            $tag->endIndex = $boundries->right - 1;
                        }
                    }
                    $this->cutIndicesOut($tag->startIndex, $tag->endIndex);
                }
            }
        }
        $newTags = [];
        $hasDeletedTags = false;
        foreach ($this->tags as $tag) {
            // removes the del-tags. The "hole punching" may created more deleted tags - should not happen though
            if ($tag->wasDeleted) {
                $hasDeletedTags = true;
            } else {
                if ($tag->wasInserted) {
                    $tag->wasInserted = false;
                }
                $newTags[] = $tag;
            }
        }
        // only resort & reorder if there were tags deleted
        if ($hasDeletedTags) {
            $this->tags = $newTags;
            $this->fixParentOrders();
            $this->sort();
        }

        return $hasDeletedTags;
    }

    /**
     * Condenses all trackchanges del-tags, that immediately follow on each other.
     * This is crucial to properly calculate whitespace before and after when removing <del>-tags
     * Note, that this will not return tags with no content-length (e.g. trackchanges containing just an internal tag),
     * or nested del tags: those tags will only be flagged for deletion ...
     * This API is just a helper to filter out those <del>-tags, that do not need to be "stamped out"
     * The returned array contains the "meaningful" <del>-tags and all other types (ncluding <ins>)
     * @return editor_Segment_Tag[]
     */
    private function condenseTrackChangesDelTags(bool $forDeletion = false): array
    {
        $tags = [];
        $lastTC = null;
        foreach ($this->tags as $tag) {
            /** @var \editor_Segment_TrackChanges_DeleteTag $tag */
            if ($tag->getType() === editor_Segment_Tag::TYPE_TRACKCHANGES && $tag->isDeleteTag()) {
                if ($tag->endIndex === $tag->startIndex || $tag->wasDeleted || $tag->wasInserted) {
                    if ($forDeletion) {
                        $tag->wasDeleted = true;
                    }
                } elseif (
                    $lastTC !== null &&
                    $lastTC->endIndex === $tag->startIndex &&
                    ! $this->hasNonNestedTagsAtIndex($tag->startIndex, $lastTC, $tag)
                ) {
                    $lastTC->endIndex = $tag->endIndex;
                    if ($forDeletion) {
                        $tag->wasDeleted = true;
                    }
                } else {
                    // only when deleting TC tags after using this method, we can (potentially) manipulate them,
                    // otherwise we need to use clones to not manipulate the existing structure
                    $lastTC = ($forDeletion) ? $tag : $tag->clone(true, true);
                    $tags[] = $lastTC;
                }
            } else {
                $tags[] = $tag;
            }
        }

        return $tags;
    }

    /**
     * Retrieves the boundries of a del-tag increased by the blanks that can be removed without affecting other tags
     */
    private function getRemovableBlanksBoundries(int $start, int $end): stdClass
    {
        $length = $this->getFieldTextLength();
        $boundries = new stdClass();
        $boundries->left = $start;
        $boundries->right = $end;
        // increase the boundries to cover all blanks left and right
        while (($boundries->left - 1) > 0 && $this->getTextPart($boundries->left - 1, $boundries->left) === ' ') {
            $boundries->left -= 1;
        }
        while (($boundries->right + 1) < $length && $this->getTextPart($boundries->right, $boundries->right + 1) === ' ') {
            $boundries->right += 1;
        }
        // reduce the boundries if there are tags covered
        foreach ($this->tags as $tag) {
            if (! $tag->wasDeleted && $tag->getType() != editor_Segment_Tag::TYPE_TRACKCHANGES) {
                if ($tag->startIndex >= $boundries->left && $tag->startIndex <= $start) {
                    $boundries->left = $tag->startIndex;
                }
                if ($tag->endIndex <= $boundries->right && $tag->endIndex >= $end) {
                    $boundries->right = $tag->endIndex;
                }
            }
        }

        return $boundries;
    }

    /**
     * Removes the text-portion from our field-text and our tags
     */
    private function cutIndicesOut(int $start, int $end): void
    {
        $dist = $end - $start;
        if ($dist <= 0) {
            return;
        }
        // adjust the tags
        foreach ($this->tags as $tag) {
            // the tag is only affected if not completely  before the hole
            if ($tag->endIndex > $start) {
                // if we're completely behind, just shift
                if ($tag->startIndex >= $end) {
                    $tag->startIndex -= $dist;
                    $tag->endIndex -= $dist;
                } elseif ($tag->startIndex >= $start && $tag->endIndex <= $end) {
                    // singular boundry tags will only be shifted
                    if ($tag->endIndex == $start || $tag->startIndex == $end) {
                        $tag->startIndex -= $dist;
                        $tag->endIndex -= $dist;
                    } else {
                        // this can only happen, if non-trackchanges tags overlap with trackchanges tags.
                        // TODO: generate an error here ?
                        if ($tag->getType() != editor_Segment_Tag::TYPE_TRACKCHANGES &&
                            ! $tag->wasDeleted && static::DO_DEBUG
                        ) {
                            error_log("\n##### TRACKCHANGES CLONING: FOUND TAG THAT HAS TO BE REMOVED ' .
                            'ALTHOUGH NOT MARKED AS DELETED ($start|$end) " . $tag->debugProps() . " #####\n");
                        }
                        $tag->startIndex = $tag->endIndex = 0;
                        $tag->wasDeleted = true;
                    }
                } else {
                    // tag is somehow overlapping the hole
                    $tag->startIndex = ($tag->startIndex <= $start) ? $tag->startIndex : $start;
                    $tag->endIndex = ($tag->endIndex >= $end) ? ($tag->endIndex - $dist) : ($end - $dist);
                }
            }
        }
        // adjust the field text
        $length = $this->getFieldTextLength();
        $newFieldText = ($start > 0) ? $this->getTextPart(0, $start) : '';
        $newFieldText .= ($end < $length) ? $this->getTextPart($end, $length) : '';
        $this->setText($newFieldText);
    }

    /**
     * Retrieves the text with the TrackChanges removed
     */
    private function getFieldTextWithoutTrackChanges(bool $condenseBlanks = true): string
    {
        $this->sort();
        $text = '';
        $start = 0;
        $length = $this->getFieldTextLength();
        foreach ($this->condenseTrackChangesDelTags() as $tag) {
            /** @var \editor_Segment_TrackChanges_TrackChangesTag $tag */
            // the tag is only affected if not completely before the hole
            if (
                $tag->getType() === editor_Segment_Tag::TYPE_TRACKCHANGES &&
                $tag->isDeleteTag() &&
                $tag->endIndex > $tag->startIndex &&
                $tag->endIndex > $start
            ) {
                $boundries = ($condenseBlanks) ? $this->getRemovableBlanksBoundries($tag->startIndex, $tag->endIndex) : null;
                if ($boundries != null && $boundries->left < $tag->startIndex && $boundries->right > $tag->endIndex) {
                    // if there are removable blanks on both sides, it is meaningless on which side we leave one
                    if ($boundries->left > $start) {
                        $text .= $this->getTextPart($start, $boundries->left);
                    }
                    $start = $boundries->right - 1;
                } else {
                    if ($tag->startIndex > $start) {
                        $text .= $this->getTextPart($start, $tag->startIndex);
                    }
                    $start = $tag->endIndex;
                }
            }
        }
        if ($start < $length) {
            $text .= $this->getTextPart($start, $length);
        }

        return $text;
    }

    /**
     * Special API to render all internal newline tags as lines
     * This expects TrackChanges Tags to be removed, otherwise the result will contain trackchanges contents
     */
    private function replaceTagsForLines(): void
    {
        $tags = [];
        foreach ($this->tags as $tag) {
            // the tag is only affected if not completely  before the hole
            /** @var \editor_Segment_Internal_Tag $tag */
            if ($tag->getType() === editor_Segment_Tag::TYPE_INTERNAL && $tag->isNewline()) {
                $tags[] = editor_Segment_NewlineTag::createNew($tag->startIndex, $tag->endIndex);
            }
        }
        $this->tags = $tags;
    }

    /**
     * Sets the deleted / inserted properties for all tags inside trackchanges-tags.
     * This is the last step of unparsing the tags and deserialization from JSON
     * It is also crucial for evaluating qualities because only non-deleted tags will count
     * Mistakenly nested ins/del tags will be corrected here by ignoring them by marking them as "deleted"
     * NOTE, that this mechanic works just over 2 nesting-levels
     * and it's absolutely possible to create deeper nested bullshit ...
     */
    protected function evaluateDeletedInserted(): void
    {
        // first, invalidate deleted/inserted props & find ins/del tags
        $typeTC = editor_Segment_Tag::TYPE_TRACKCHANGES;
        $delTags = [];
        $insTags = [];
        foreach ($this->tags as $tag) {
            $tag->wasDeleted = $tag->wasInserted = false;
            if ($tag->getType() === $typeTC) {
                /** @var \editor_Segment_TrackChanges_TrackChangesTag $tag */
                if ($tag->isDeleteTag()) {
                    $delTags[] = $tag;
                } else {
                    $insTags[] = $tag;
                }
            }
        }
        // first, mark all nested TC tags in top-level DEL as deleted
        foreach ($delTags as $tag) {
            if ($tag->parentOrder === -1) {
                $this->setContainedTagsProp($tag, 'wasDeleted', $typeTC);
            }
        }
        // to cover 2 levels, repeat for inner tags
        foreach ($insTags as $tag) {
            if ($tag->wasDeleted) {
                $this->setContainedTagsProp($tag, 'wasDeleted', $typeTC);
            }
        }
        foreach ($delTags as $tag) {
            if ($tag->wasDeleted) {
                $this->setContainedTagsProp($tag, 'wasDeleted', $typeTC);
            }
        }
        // mark all inner inserted tags as such - if the insertion was not deleted
        foreach ($insTags as $tag) {
            if (! $tag->wasDeleted) {
                $this->setContainedTagsProp($tag, 'wasInserted');
            } elseif ($tag->wasDeleted) {
                $this->setContainedTagsProp($tag, 'wasDeleted');
            }
        }
        // lastly mark all inner deleted tags as such
        foreach ($delTags as $tag) {
            if (! $tag->wasInserted) {
                $this->setContainedTagsProp($tag, 'wasDeleted');
            }
        }
    }

    /**
     * Helper to set the del/ins properties
     */
    protected function setContainedTagsProp(editor_Segment_Tag $outer, string $propName, string $type = null): void
    {
        foreach ($this->tags as $tag) {
            if ($tag !== $outer &&
                $tag->startIndex >= $outer->startIndex &&
                $tag->endIndex <= $outer->endIndex &&
                ($type === null || $tag->getType() === $type) &&
                (
                    ($tag->startIndex > $outer->startIndex && $tag->endIndex < $outer->endIndex) ||
                    $tag->parentOrder === $outer->order
                )
            ) {
                $tag->$propName = true;
            }
        }
    }

    /* Debugging API */

    /**
     * Debug formatted JSON
     */
    public function debugJson(): string|false
    {
        return json_encode($this->jsonSerialize(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
