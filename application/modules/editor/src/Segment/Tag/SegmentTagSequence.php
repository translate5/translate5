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
use editor_Segment_TrackChanges_DeleteTag as DeleteTag;
use editor_Segment_TrackChanges_InsertTag as InsertTag;
use editor_Segment_TrackChanges_TrackChangesTag as TrackChangesTag;
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
     * Find a tag by order in our own tags
     */
    protected function findTagByOrder(int $order): ?editor_Segment_Tag
    {
        foreach ($this->tags as $tag) {
            if ($tag->order === $order) {
                return $tag;
            }
        }

        return null;
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
            if ($tag->getType() === editor_Segment_Tag::TYPE_TRACKCHANGES || ($includedTypes == null || in_array($tag->getType(), $includedTypes))) {
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
        if ($clonedTags->hasNestedTrackChangesTags()) {
            $clonedTags->normalizeTrackChangesTags();
        }
        // if no trackchanges-tags were removed, the method will not fix parent orders...
        if (! $clonedTags->deleteTrackChangesTags($condenseBlanks)) {
            $clonedTags->fixParentOrders();
        }

        return $clonedTags;
    }

    /**
     * Evaluate, if a cleanup of nested track-changes tags is neccessary
     */
    public function hasNestedTrackChangesTags(): bool
    {
        foreach ($this->tags as $tag) {
            if ($tag->getType() === editor_Segment_Tag::TYPE_TRACKCHANGES && $tag->parentOrder !== -1) {
                $tag = $this->findTagByOrder($tag->parentOrder);
                if ($tag !== null && $tag->getType() === editor_Segment_Tag::TYPE_TRACKCHANGES) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Fixes nesting Problems that occur because the frontend may produces nested TrackChanges tags
     * The cleanup will have the following logic:
     * - All <del>/<ins> tags in <del>-tags will be removed
     * - All <del> tags in <ins> tags will be deleted (with contents),
     *   when they have been crated after the surrounding <ins> tag
     * - Further nestings in "<del> in <ins>" will be ignored/removed
     * - <ins> in <ins> will be ignored/removed
     * QUIRK: in this process, we will use both the "removed" flag AND the "wasDeleted" flag
     */
    public function normalizeTrackChangesTags(): void
    {
        // making sure we're in order
        $this->sort();
        // first, invalidate deleted/inserted props & find ins/del tags
        $typeTC = editor_Segment_Tag::TYPE_TRACKCHANGES;
        $tcTags = [];
        foreach ($this->tags as $tag) {
            $tag->wasDeleted = $tag->wasInserted = $tag->removed = false;
            if ($tag->getType() === $typeTC) {
                /** @var TrackChangesTag $tag */
                $tcTags[] = $tag;
            }
        }
        $hasRemoved = $hasDeleted = false;
        // mark all inner del & ins tags
        // nested del in ins, that are newer will be marked as deleted
        foreach ($tcTags as $tag) {
            if ($tag->parentOrder === -1) {
                if ($this->markNestedTrackChangesTags($tag, $tcTags)) {
                    $hasRemoved = true;
                }
            }
        }
        // no removed tags - nothing more to do
        if (! $hasRemoved) {
            return;
        }
        // remove all tags marked for removal
        $newTags = [];
        foreach ($this->tags as $tag) {
            if ($tag->removed) {
                error_log('SegmentTagSequence::normalizeTrackChangesTags: Removed nested TrackChanges-Tag' .
                    ' (contents have not been removed): ' . $tag->render());
                // crucial: parent order for nested tags needs to be shifted to the parent ...
                $this->changeParentOrder($tag->order, $tag->parentOrder);
            } else {
                $newTags[] = $tag;
                if ($tag->wasDeleted) {
                    $hasDeleted = true;
                }
            }
        }
        $this->tags = $newTags;
        // last thing, remove all tags marked for deletion
        if ($hasDeleted) {
            foreach ($this->tags as $tag) {
                /** @var DeleteTag $tag */
                if ($tag->wasDeleted && $tag->getType() === $typeTC && $tag->isDeleteTag()) {
                    if ($tag->endIndex > $tag->startIndex) {
                        $this->cutIndicesOut($tag);
                    } elseif ($tag->hasZeroLength()) {
                        $this->markNestedZeroLengthTagsAsDeleted($tag);
                    }
                }
            }
            $newTags = [];
            foreach ($this->tags as $tag) {
                // removes the del-tags. The "hole punching" may created more deleted tags - should not happen though
                if ($tag->wasDeleted) {
                    if ($tag->getType() === $typeTC) {
                        error_log('SegmentTagSequence::normalizeTrackChangesTags: Deleted nested TrackChanges-Tag' .
                            ' with it’s contents: ' . $tag->render());
                    }
                } else {
                    $newTags[] = $tag;
                }
            }
            $this->tags = $newTags;
        }

        $this->fixParentOrders();
        $this->sort();
    }

    /**
     * Mark nested ins/del for removal. nested <del> in <ins> will be deleted when first level & newer
     * @param TrackChangesTag[] $tags
     */
    private function markNestedTrackChangesTags(TrackChangesTag $outer, array $tags): bool
    {
        $marked = false;
        foreach ($tags as $tag) {
            if (! $tag->removed &&
                ! $tag->wasDeleted &&
                $tag !== $outer &&
                $tag->startIndex >= $outer->startIndex &&
                $tag->endIndex <= $outer->endIndex &&
                $tag->parentOrder !== -1
            ) {
                if ($outer->isInsertTag()) {
                    // delete-tags in insert-tags will be substracted from the insert if they are newer than the insert
                    // only first nesting level is handled here, everything inside will be removed ...
                    if ($tag->isDeleteTag() &&
                        $tag->parentOrder === $outer->order &&
                        $tag->getDataTimestamp() >= $outer->getDataTimestamp()
                    ) {
                        $tag->wasDeleted = true;
                    } else {
                        $tag->removed = true;
                    }
                } else {
                    $tag->removed = true;
                }
                $marked = true;
            }
        }

        return $marked;
    }

    /**
     * Helper to mark inner Zero-length tags in zero-length del-tags as deleted
     */
    private function markNestedZeroLengthTagsAsDeleted(DeleteTag $parentTag): void
    {
        foreach ($this->tags as $tag) {
            if (! $tag->wasDeleted &&
                $tag->hasZeroLength() &&
                $tag->parentOrder === $parentTag->order &&
                $tag->startIndex === $parentTag->startIndex &&
                $tag->endIndex === $parentTag->endIndex
            ) {
                $tag->wasDeleted = true;
            }
        }
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
        foreach ($this->condenseTrackChangesDelTags() as $tag) {
            if ($tag->getType() === editor_Segment_Tag::TYPE_TRACKCHANGES) {
                /** @var DeleteTag|InsertTag $tag */
                $tag->wasDeleted = true;
                if ($tag->isDeleteTag()) {
                    if ($tag->endIndex > $tag->startIndex) {
                        if ($condenseBlanks) {
                            $boundries = $this->getRemovableBlanksBoundries($tag->startIndex, $tag->endIndex);
                            if ($boundries->left < $tag->startIndex && $boundries->right > $tag->endIndex) {
                                // if there are removable blanks on both sides it is meaningless, on which side we leave one
                                $tag->startIndex = $boundries->left;
                                $tag->endIndex = $boundries->right - 1;
                            }
                        }
                        $this->cutIndicesOut($tag);
                    } elseif ($tag->hasZeroLength()) {
                        $this->markNestedZeroLengthTagsAsDeleted($tag);
                    }
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
     * This API must only be used for deletion of TC tags
     * @return editor_Segment_Tag[]
     */
    private function condenseTrackChangesDelTags(): array
    {
        $tags = [];
        $lastTC = null;
        foreach ($this->tags as $tag) {
            /** @var DeleteTag $tag */
            if ($tag->getType() === editor_Segment_Tag::TYPE_TRACKCHANGES && $tag->isDeleteTag()) {
                if ($tag->endIndex === $tag->startIndex || $tag->wasDeleted || $tag->wasInserted) {
                    $tag->wasDeleted = true;
                } elseif (
                    $lastTC !== null &&
                    $lastTC->endIndex === $tag->startIndex &&
                    ! $this->hasNonNestedTagsAtIndex($tag->startIndex, $lastTC, $tag)
                ) {
                    $lastTC->endIndex = $tag->endIndex;
                    $tag->wasDeleted = true;
                } else {
                    $lastTC = $tag;
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
     * Does mark inner tags of the cutout as deleted
     */
    private function cutIndicesOut(DeleteTag $deletedTag): void
    {
        $start = $deletedTag->startIndex;
        $end = $deletedTag->endIndex;
        $dist = $end - $start;
        if ($dist <= 0) {
            return;
        }
        // adjust the tags
        foreach ($this->tags as $tag) {
            // the tag is only affected if not completely  before the hole
            if ($tag->endIndex > $start && $tag !== $deletedTag) {
                // if we're completely behind, just shift
                if ($tag->startIndex > $end || ($tag->startIndex === $end && ! $tag->hasZeroLength())) {
                    $tag->startIndex -= $dist;
                    $tag->endIndex -= $dist;
                    // tag is inside cutout
                } elseif ($tag->startIndex >= $start && $tag->endIndex <= $end) {
                    // singular boundry zero length tags will only be shifted - if not nested
                    if (($tag->startIndex === $start || $tag->endIndex === $end) && $tag->hasZeroLength()) {
                        // if tag is nested mark as deleted
                        if ($tag->parentOrder > -1 && $tag->parentOrder === $deletedTag->order) {
                            $tag->wasDeleted = true;
                        } else {
                            $tag->startIndex -= $dist;
                            $tag->endIndex -= $dist;
                        }
                        // tag is completely inside
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
                    // tag is somehow overlapping the hole
                } else {
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
        $clone = $this->cloneWithoutTrackChanges(null, $condenseBlanks);

        return $clone->getFieldText();
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
     */
    protected function evaluateDeletedInserted(): void
    {
        foreach ($this->tags as $tag) {
            /** @var TrackChangesTag $tag */
            if ($tag->getType() === editor_Segment_Tag::TYPE_TRACKCHANGES) {
                $propName = ($tag->isDeleteTag()) ? 'wasDeleted' : 'wasInserted';
                $this->setContainedTagsProp($tag, $propName);
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
