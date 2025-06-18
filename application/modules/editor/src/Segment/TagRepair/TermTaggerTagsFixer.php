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
use editor_TagSequence;

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
        if (editor_TagSequence::DO_DEBUG && (count($this->nestings) > 0 || count($this->overlaps) > 0)) {
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
                    // if we have a single nested tag that hase the same boundries, we swap the nesting
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
                // crucial: exclude internal tags that are before or after the term-tag by order
                ! (
                    $tag->startIndex === $tag->endIndex &&
                    (($tag->startIndex === $termTag->startIndex && $tag->order < $termTag->order) ||
                    ($tag->startIndex === $termTag->endIndex && $tag->order > $termTag->order))
                )
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
                ($tag->startIndex > $termTag->startIndex && $tag->startIndex < $termTag->endIndex && $tag->endIndex > $termTag->endIndex ||
                $tag->startIndex < $termTag->startIndex && $tag->endIndex > $termTag->startIndex && $tag->endIndex < $termTag->endIndex)
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
}
