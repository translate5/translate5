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

use editor_Plugins_TermTagger_Tag;
use editor_Segment_FieldTags;
use editor_Segment_Tag;
use MittagQI\Translate5\Tag\TagSequence;

/**
 * Fixes Problems of the Termtagger:
 * In situations, the Termtagger scrambles the nesting of tags by putting ins/del tags inside of term-tags
 * TrackChanges tags always must be top-level, otherwise may invalid structures are created
 * To solve this, we either swap the order or we simply remove the term-tag as a last ressort
 * This class is intended to fix nesting problems but not invalid markup, it expects terminology-tags to be aligned
 * on word-boundries !
 *
 * TODO:
 * Whenever we get rid of the internal-tag div/span structure, these then have no text-content anymore and would
 * be prone to be nested mistakenly by the termtagger as well and therefore internal-tags on term-tag boundries
 * would have to be fixed too !
 */
class TermTaggerTagsFixer
{
    private array $nestings = [];

    private array $overlaps = [];

    private array $warnings = [];

    /**
     * @var editor_Segment_Tag[]
     */
    private array $tags;

    public function __construct(
        private editor_Segment_FieldTags $fieldTags,
    ) {
        // we search all term-tags that contain other tags
        $this->tags = $this->fieldTags->getAll();
        foreach ($this->tags as $idx => $tag) {
            if ($tag->getType() === editor_Plugins_TermTagger_Tag::TYPE) {
                $contained = $this->findNested($tag);
                if (count($contained) > 0) {
                    $this->nestings[$idx] = $contained;
                }
                $overlapping = $this->findOverlapping($tag);
                if (count($overlapping) > 0) {
                    $this->overlaps[$idx] = $overlapping;
                }
            }
        }
        // @phpstan-ignore-next-line
        if (TagSequence::DO_DEBUG && (count($this->nestings) > 0 || count($this->overlaps) > 0)) {
            error_log('SEGMENT TAGS: Invalid Terminology-Tags found in TermTaggerTagsFixer');
        }
    }

    /**
     * Retrieves if the tags need fixing
     */
    public function needsFix(): bool
    {
        return count($this->nestings) > 0 || count($this->overlaps) > 0;
    }

    /**
     * @return editor_Segment_Tag[]
     */
    public function getFixedTags(): array
    {
        $toRemove = [];
        if (count($this->nestings) > 0) {
            foreach ($this->nestings as $idx => $nested) { /** @var int[] $nested */
                $termTag = $this->tags[$idx];
                if (count($nested) === 1 &&
                     $this->tags[$nested[0]]->startIndex === $termTag->startIndex &&
                     $this->tags[$nested[0]]->endIndex === $termTag->endIndex
                ) {
                    // if we have a single nested tag that has the same boundries, we swap the nesting
                    $this->swapTags($idx, $nested[0]);
                } elseif (count($nested) === 1 &&
                    $this->tags[$nested[0]]->getType() === editor_Segment_Tag::TYPE_TRACKCHANGES &&
                    $this->tags[$nested[0]]->startIndex === $termTag->startIndex &&
                    $this->tags[$nested[0]]->endIndex === $termTag->startIndex + 1
                ) {
                    // if we have a single ins-tag for the first char of the term, we simply remove it
                    $toRemove[] = $nested[0];
                    $text = $this->fieldTags->getTextPart($this->tags[$nested[0]]->startIndex, $this->tags[$nested[0]]->endIndex);
                    $this->warnings[] = 'Removed trackchanges-tag for the first character of term "' . $text
                        . '" as it created an invalid markup-structure';
                } elseif ($this->hasOnlyNestedStartEnd($termTag, $nested)) {
                    // the term-tag has only zero-length tags at the start and/or end
                    // this is a fixable state
                    $this->fixBoundaryNestingTags($termTag, $nested);
                } else {
                    // otherwise we remove the term-tag as we cannot shrink or slice it
                    $toRemove[] = $idx;
                    $this->addRemoveWarning($idx);
                }
            }
        }
        // overlaps can only happen with TrackChanges-tags
        if (count($this->overlaps) > 0) {
            // we only will accept overlaps of up to 1 char (which may result of changing upper/lowercaseing first char)
            foreach ($this->overlaps as $idx => $overlapping) { /** @var int[] $overlapping */
                $termTag = $this->tags[$idx];
                $allRepairable = true;
                foreach ($overlapping as $oIdx) {
                    $tag = $this->tags[$oIdx]; /** @var editor_Segment_Tag $tag */
                    if (($tag->startIndex < $termTag->startIndex && $tag->endIndex > $termTag->startIndex + 1) ||
                        ($tag->startIndex > $termTag->startIndex && $tag->startIndex < $termTag->endIndex + 1)
                    ) {
                        $allRepairable = false;
                    }
                }
                if ($allRepairable) {
                    foreach ($overlapping as $oIdx) {
                        $tag = $this->tags[$oIdx]; /** @var editor_Segment_Tag $tag */
                        if ($tag->startIndex < $termTag->startIndex) {
                            $tag->endIndex = $termTag->startIndex;
                        } else {
                            $tag->startIndex = $termTag->endIndex;
                        }
                    }
                } else {
                    // otherwise we remove the term-tag as we cannot shrink the trackchanges
                    $toRemove[] = $idx;
                    $this->addRemoveWarning($idx);
                }
            }
        }

        if (count($toRemove) > 0) {
            // remove/change all nesting references for the removed tags
            foreach ($toRemove as $idx) {
                $this->removeNestings($idx);
            }
            // return tag-sequence without the removed
            $tags = [];
            foreach ($this->tags as $idx => $tag) {
                if (! in_array($idx, $toRemove)) {
                    $tags[] = $tag;
                }
            }

            return $tags;
        }

        return $this->tags;
    }

    public function hasWarnings(): bool
    {
        return count($this->warnings) > 0;
    }

    public function getWarnings(): string
    {
        return implode('; ', $this->warnings);
    }

    private function findNested(editor_Segment_Tag $termTag): array
    {
        $children = [];
        foreach ($this->tags as $idx => $tag) {
            if ($tag->order !== $termTag->order &&
                $this->isRelevant($tag) &&
                $tag->startIndex >= $termTag->startIndex &&
                $tag->endIndex <= $termTag->endIndex &&
                $tag->parentOrder === $termTag->order
            ) {
                $children[] = $idx;
            }
        }

        return $children;
    }

    private function findOverlapping(editor_Segment_Tag $termTag): array
    {
        $children = [];
        foreach ($this->tags as $idx => $tag) {
            if ($tag->order !== $termTag->order &&
                $this->isRelevant($tag, false) &&
                (($tag->startIndex > $termTag->startIndex &&
                        $tag->startIndex < $termTag->endIndex &&
                        $tag->endIndex > $termTag->endIndex) ||
                    ($tag->startIndex < $termTag->startIndex &&
                        $tag->endIndex > $termTag->startIndex &&
                        $tag->endIndex < $termTag->endIndex))
            ) {
                $children[] = $idx;
            }
        }

        return $children;
    }

    /**
     * Only Trackchanges or Internal tag may cause Problems, MQM are just marker-images
     */
    private function isRelevant(editor_Segment_Tag $tag, bool $withInternal = true): bool
    {
        return $tag->getType() === editor_Segment_Tag::TYPE_TRACKCHANGES ||
            ($withInternal && $tag->getType() === editor_Segment_Tag::TYPE_INTERNAL);
    }

    /**
     * Adds an error for the given tag#s removal
     */
    private function addRemoveWarning(int $idx): void
    {
        $text = $this->fieldTags->getTextPart($this->tags[$idx]->startIndex, $this->tags[$idx]->endIndex);
        $this->warnings[] = 'Removed terminology-tag for "' . $text . '" as it created an invalid markup-structure';
    }

    /**
     * Completely swaps the two tags
     * In this process we do not need to care for nesting children of the now inner tag
     * as it we lso change the order ...
     */
    private function swapTags(int $outerIdx, int $innerIdx): void
    {
        $outer = $this->tags[$outerIdx];
        $inner = $this->tags[$innerIdx];
        $outerParent = $outer->parentOrder;
        $outerOrder = $outer->order;
        // swap order-props
        $outer->order = $inner->order;
        $inner->order = $outerOrder;
        $inner->parentOrder = $outerParent;
        $outer->parentOrder = $inner->order;
        // and finally swap position in array
        $this->tags[$outerIdx] = $inner;
        $this->tags[$innerIdx] = $outer;
    }

    /**
     * Adjusts parent-orders for removed tags
     */
    private function removeNestings(int $idx): void
    {
        $removed = $this->tags[$idx];
        $newParentOrder = $removed->parentOrder;
        foreach ($this->tags as $tag) {
            if ($tag !== $removed && $tag->parentOrder === $removed->order) {
                $tag->parentOrder = $newParentOrder;
            }
        }
    }

    /**
     * @param int[] $nestedIdxs
     */
    private function hasOnlyNestedStartEnd(editor_Segment_Tag $holder, array $nestedIdxs): bool
    {
        foreach ($nestedIdxs as $idx) {
            $tag = $this->tags[$idx];
            if (! ($tag->hasZeroLength() &&
                ($holder->startIndex === $tag->startIndex || $holder->endIndex === $tag->endIndex) &&
                ($tag->parentOrder === $holder->order || $this->isNestedInOneOf($tag, $nestedIdxs)))
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int[] $tagIdxs
     */
    private function isNestedInOneOf(editor_Segment_Tag $inner, array $tagIdxs): bool
    {
        foreach ($tagIdxs as $idx) {
            if ($inner->order !== $this->tags[$idx]->order &&
                $inner->parentOrder === $this->tags[$idx]->order &&
                $inner->startIndex >= $this->tags[$idx]->startIndex &&
                $inner->endIndex <= $this->tags[$idx]->endIndex
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fixes a situation, where zero-length tags (usually internal tags) are nested at the start or
     * end-index of a terminology-tag. This situation can be solved by un-nesting the nested tags and
     * change the orders of the tags.
     * Being the parent, the termtag comes first, then the nested start(s), then the nested end(s)
     * The new order needs to be: Now unnested starts, termtag, unnested ends
     * The unnesting is applied by giving the nested tags the same parent as the term tag (usually level -1 = segment)
     * @param int[] $nestedIdxs
     */
    private function fixBoundaryNestingTags(editor_Segment_Tag $termTag, array $nestedIdxs): void
    {
        // find the tags to "unnest" - note, that thes can be further nested,
        // e.g. can a zero-length <ins> contain an internal tag
        $before = [];
        $beforeOrders = [];
        $after = [];
        foreach ($nestedIdxs as $idx) {
            if ($this->tags[$idx]->startIndex === $termTag->startIndex &&
                $this->tags[$idx]->endIndex === $termTag->startIndex
            ) {
                $before[] = $this->tags[$idx];
                $beforeOrders[] = $this->tags[$idx]->order;
            } elseif ($this->tags[$idx]->startIndex === $termTag->endIndex &&
                $this->tags[$idx]->endIndex === $termTag->endIndex
            ) {
                $after[] = $this->tags[$idx];
            } else {
                throw new \ZfExtended_Exception(
                    'Faulty logic of detection of starting/ending nested tags in TermTaggerTagsFixer'
                );
            }
        }
        // the befores / afters now only hold direct descendants.
        // but the befores need to be the complete abcestry to change all orders !
        if (count($before) > 0) {
            $added = true;
            while ($added) {
                $added = false;
                foreach ($this->tags as $tag) {
                    // add all tags nested in any of the existing before's
                    if ($tag !== $termTag &&
                        $tag->parentOrder !== -1 && // top-level cannot match
                        ! in_array($tag->order, $beforeOrders) && // otherwise loop runs forever
                        in_array($tag->parentOrder, $beforeOrders) // we are nested
                    ) {
                        $before[] = $tag;
                        $beforeOrders[] = $tag->order;
                        $added = true;
                    }
                }
            }
        }
        // lets sort the "befores" ... we do not know if really in order
        usort($before, [$this->fieldTags, 'compare']);
        // now pull the "befores" ... these will include all nestings, not just direct descendants
        $oldToNew = [];
        $order = $oldTermTagOrder = $termTag->order;
        // change orders of all before's to start with term-tag
        foreach ($before as $tag) {
            $oldOrder = $tag->order;
            $tag->order = $order;
            $order = $oldOrder;
            $oldToNew[$oldOrder] = $tag->order;
        }
        // unnesting direct descendants of the term-tag &
        // change parent order for all nestings pointing to a reordered tag
        foreach ($before as $tag) {
            if ($tag->parentOrder === $oldTermTagOrder) {
                $tag->parentOrder = $termTag->parentOrder;
            } elseif (array_key_exists($tag->parentOrder, $oldToNew)) {
                $tag->parentOrder = $oldToNew[$tag->parentOrder];
            }
        }
        // termtags follows
        $termTag->order = $order;
        // "afters" will be unnested only
        foreach ($after as $tag) {
            // we unnest direct descendants of the term-tag
            if ($tag->parentOrder === $oldTermTagOrder) {
                $tag->parentOrder = $termTag->parentOrder;
            }
        }
    }
}
