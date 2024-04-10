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

/**
 * Base class for comparing tags or repairing tag faults
 * This code seperates the internal tags from the field tags and finds the counterpart for every non-single internal tag - if there is one
 * This is the base for the Tag-Comparision in the AutoQA as well as the automatic tag repair
 */
class editor_Segment_Internal_TagCheckBase
{
    /**
     * Helper to order an array of tags by their index in the Fieldtags-Model
     */
    public static function compareByIdx(editor_Segment_Internal_Tag $a, editor_Segment_Internal_Tag $b): int
    {
        return $a->_idx - $b->_idx;
    }

    /**
     * @var editor_Segment_Internal_Tag[]
     */
    protected $checkTags = [];

    /**
     * @var int
     */
    protected $numCheckTags = 0;

    /**
     * @var editor_Segment_FieldTags
     */
    protected $fieldTags;

    public function __construct(editor_Segment_FieldTags $toCheck, editor_Segment_FieldTags $against = null)
    {
        $this->fieldTags = $toCheck;
        $this->fieldTags->sort();
        $this->checkTags = $this->extractRelevantTags($toCheck);
        $this->numCheckTags = count($this->checkTags);
        // the structural check can be done without against tags
        $this->findCounterparts();
    }

    /**
     * Extracts the relevant tags for the check out of the field tags (usually all internal-tags)
     * @return editor_Segment_Internal_Tag[]
     */
    protected function extractRelevantTags(editor_Segment_FieldTags $fieldTags): array
    {
        return $fieldTags->getByType(editor_Segment_Tag::TYPE_INTERNAL);
    }

    /**
     * Finds for an opener the corresponding closer, no matter if there are overlaps or anything else
     */
    protected function findCounterparts(): void
    {
        for ($i = 0; $i < $this->numCheckTags; $i++) {
            $this->checkTags[$i]->_idx = $i;
            if ($this->checkTags[$i]->isOpening() && $this->checkTags[$i]->counterpart == null) {
                $tagIndex = $this->checkTags[$i]->getTagIndex();
                if ($tagIndex > -1) {
                    // finding counterpart forward
                    if ($i < $this->numCheckTags - 1) {
                        for ($j = $i + 1; $j < $this->numCheckTags; $j++) {
                            if ($j != $i && $this->checkTags[$j]->isClosing()
                                && $this->checkTags[$j]->counterpart == null
                                && $this->checkTags[$j]->getTagIndex() === $tagIndex) {
                                $this->checkTags[$i]->counterpart = $this->checkTags[$j];
                                $this->checkTags[$j]->counterpart = $this->checkTags[$i];

                                break;
                            }
                        }
                    }
                    // finding counterpart backwards if forward didn't work
                    if ($this->checkTags[$i]->counterpart == null && $i > 1) {
                        for ($j = $i - 1; $j >= 0; $j--) {
                            if ($j != $i && $this->checkTags[$j]->isClosing()
                                && $this->checkTags[$j]->counterpart == null
                                && $this->checkTags[$j]->getTagIndex() === $tagIndex) {
                                $this->checkTags[$i]->counterpart = $this->checkTags[$j];
                                $this->checkTags[$j]->counterpart = $this->checkTags[$i];

                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Checks, if the given internal tag is structurally valid.
     * That means, the tag is either single or it has a counterpart that comes behind the tag (for a opener)
     * and there are no overlapping tags in-between
     * @param editor_Segment_Internal_Tag[] $tags
     */
    protected function isStructurallyValid(array $tags, int $index): bool
    {
        $tag = $tags[$index];
        // single tags are always valid
        if ($tag->isSingle()) {
            return true;
        }
        // ... and double-tags without counterpart always invalid
        if ($tag->counterpart == null) {
            return false;
        }
        if ($tag->isOpening()) {
            if ($tag->counterpart->_idx < $tag->_idx || $tag->counterpart->startIndex < $tag->endIndex) {
                return false;
            }

            return $this->hasNoOverlaps(
                $tags,
                $tag->_idx,
                $tag->counterpart->_idx,
                $tag->endIndex,
                $tag->counterpart->startIndex,
                $tag->hasSameTextIndex($tag->counterpart)
            );
        } else {
            if ($tag->_idx < $tag->counterpart->_idx || $tag->startIndex < $tag->counterpart->endIndex) {
                return false;
            }

            return $this->hasNoOverlaps(
                $tags,
                $tag->counterpart->_idx,
                $tag->_idx,
                $tag->counterpart->endIndex,
                $tag->startIndex,
                $tag->hasSameTextIndex($tag->counterpart)
            );
        }
    }

    /**
     * Evaluates if all tags from the given start to the given end index are between the given text-indices
     * It does not take care, if the tags in-between are not valid in terms of structure
     * @param editor_Segment_Internal_Tag[] $tags
     */
    protected function hasNoOverlaps(
        array $tags,
        int $startIndex,
        int $endIndex,
        int $startTextIndex,
        int $endTextIndex,
        bool $hasSameTextIndex
    ): bool {
        if ($startIndex < ($endIndex - 1)) {
            for ($i = $startIndex + 1; $i < $endIndex; $i++) {
                $tag = $tags[$i];
                if ($hasSameTextIndex && ! $tag->isSingle() && $tag->counterpart != null) {
                    // when dealing with a same text-index sequence we can only accept other same-index pairs
                    // that have indexes between those of the checked tags
                    // theoretically we should also check the orders (as the order decides in case of same-index tags)
                    // but it seems the preceiding run of fixSameIndexSequences already fixes this
                    // if ever more problems arise with non-detected same-text-index-tags,
                    // this would be a potential place to detect such problems
                    if (! $tag->hasSameTextIndex($tag->counterpart)
                        || $tag->counterpart->_idx >= $endIndex
                        || $tag->counterpart->_idx <= $startIndex
                    ) {
                        return false;
                    }
                }
                if ($tag->startIndex < $startTextIndex || $tag->endIndex > $endTextIndex) {
                    return false;
                }
                if ($tag->counterpart != null
                    && ($tag->counterpart->startIndex < $startTextIndex
                        || $tag->counterpart->endIndex > $endTextIndex)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Finds all indexes wich have more then one tag with start === end on them
     * Returns structure like
     * [
     *      textIndex => [                  // the textIndex is the start/end index of all tags
     *          tagIndex => [               // the tagIndex is the original index in checktags (-> may has gaps)
     *              { ... }                 // the tag
     *          ]
     *  ]
     * ]
     *
     * @param editor_Segment_Internal_Tag[] $tags
     */
    protected function findSameIndexSequences(array $tags): array
    {
        $sequences = [];
        // order all singular or multiple tags with identfixSameIndexClusterical start/end by index
        foreach ($tags as $index => $tag) {
            // we ignore singular tags here as they can not be overlapping/interleaving
            if (! $tag->isSingle() && $tag->counterpart !== null && $tag->startIndex === $tag->endIndex) {
                if (! array_key_exists($tag->startIndex, $sequences)) {
                    $sequences[$tag->startIndex] = [];
                }
                $sequences[$tag->startIndex][] = $tag;
            }
        }
        // remove all sequences that have less than 2 elements
        $filteredSequences = [];
        foreach ($sequences as $sequence) {
            if (count($sequence) > 1) {
                $filteredSequences[] = $sequence;
            }
        }

        return $filteredSequences;
    }

    /**
     * Checks wether a sequence of tags on the same text-index has a proper sequential structure
     * @return array: an array of indices of opening tags which refer to the passed array $tagsOnTextIndex and do overlap
     */
    protected function checkSameIndexSequence(array $tagsOnTextIndex): array
    {
        $faultyIndices = [];
        $numTags = count($tagsOnTextIndex);
        foreach ($tagsOnTextIndex as $index => $tag) { /* @var editor_Segment_Internal_Tag $tag */
            if ($tag->isOpening() && $this->isOverlappingInSequence($tag, $index, $tagsOnTextIndex, $numTags)) {
                $faultyIndices[$index] = 1;
            }
        }

        return array_keys($faultyIndices);
    }

    /**
     * Checks if a opening internal tag on index $index is overlapping with any other tag in passed sequence
     */
    protected function isOverlappingInSequence(
        editor_Segment_Internal_Tag $opener,
        int $index,
        array $tagsOnIndex,
        int $numTags
    ): bool {
        for ($i = 0; $i < $numTags; $i++) {
            if ($i != $index) {
                $lIdx = $tagsOnIndex[$i]->_idx;
                $ridx = $tagsOnIndex[$i]->counterpart->_idx;
                if (($lIdx > $opener->_idx && $lIdx < $opener->counterpart->_idx && $ridx > $opener->counterpart->_idx)
                    || ($ridx > $opener->_idx && $ridx < $opener->counterpart->_idx) && $lIdx < $opener->_idx) {
                    // add to existing cluster stretching it's boundaries
                    return true;
                }
            }
        }

        return false;
    }
}
