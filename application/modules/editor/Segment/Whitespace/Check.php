<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Checks the consistency of translations: Segments with an identical target but different sources or with identical sources but different targets
 * This Check can only be done for all segments of a task at once
 */
class editor_Segment_Whitespace_Check
{
    public const TAG_SPACE_BEG = 'tag_space_begin';

    public const NBSP_BEG = 'nbsp_begin';

    public const TAB_BEG = 'tab_begin';

    public const SPACE_BEG = 'space_begin';

    public const LNBR_BEG = 'lnbr_begin';

    public const SPACE_TAG_END = 'space_tag_end';

    public const NBSP_END = 'nbsp_end';

    public const TAB_END = 'tab_end';

    public const SPACE_END = 'space_end';

    public const LNBR_END = 'lnbr_end';

    public const SPACE_LNBR = 'space_lnbr';

    public const LNBR_SPACE = 'lnbr_space';

    /**
     * @var array
     */
    private $states = [];

    public function __construct(editor_Models_Task $task, editor_Segment_FieldTags $fieldTags, editor_Models_Segment $segment)
    {
        // Get field text with tags rendered into placeholders
        $fieldRenderedText = $fieldTags->renderReplaced(editor_TagSequence::MODE_ORIGINAL);

        // Detect space at the beginning
        if (str_starts_with($fieldRenderedText, ' ')) {
            $this->states[self::SPACE_BEG] = self::SPACE_BEG;
        }

        // Detect space at the end
        if (str_ends_with($fieldRenderedText, ' ')) {
            $this->states[self::SPACE_END] = self::SPACE_END;
        }

        // If no tags - do nothing
        if (! $fieldTags->hasTags()) {
            return;
        }

        // Stips trackchanges tags
        /** @var editor_Segment_FieldTags $tags */
        $tags = $fieldTags->cloneWithoutTrackChanges();

        // Get end index
        $endIndex = $tags->getFieldTextLength();

        // Get field text
        $fieldText = $tags->getFieldText(true, false);

        // Get tags qty
        $tagsQty = count($tags = $tags->getAll());

        // Foreach tag (excluding trackchanges-tags)
        /** @var editor_Segment_Internal_Tag $tag */
        foreach ($tags as $idx => $tag) {
            // If it's not an internal tag - skip
            if ($tag->getType() != editor_Segment_Tag::TYPE_INTERNAL) {
                continue;
            }

            // Check whether tag is located at the beginning and/or ending
            $sideA = [];
            if ($tag->startIndex === 0 && $idx == 0) {
                $sideA['BEG'] = [0, self::TAG_SPACE_BEG, +1];
            }
            if ($tag->endIndex === $endIndex && $idx == $tagsQty - 1) {
                $sideA['END'] = [-1, self::SPACE_TAG_END, -1];
            }

            // Foreach side
            foreach ($sideA as $side => $info) {
                // Get neighbour tag, e.g. tag coming after/before current tag, if there is such
                $neighbour = $tags[$idx + $info[2]] ?? false;

                // Check whether this neighbour comes right after/before current tag
                if ($neighbour) {
                    $neighbour = $neighbour->startIndex === $tag->startIndex;
                }

                // If it's a ordinary space and if there is no neighbour right after/before current tag
                if (mb_substr($fieldText, $tag->startIndex + $info[0], 1) == ' ' && ! $neighbour) {
                    // Append quality category
                    $this->states[$info[1]] = $info[1];
                }
            }

            // Detect the exact kind of whitespace
            if ($tag->isNbsp()) {
                $kind = 'NBSP';
            } elseif ($tag->isTab()) {
                $kind = 'TAB';
            } elseif ($tag->isNewline()) {
                $kind = 'LNBR';
            } else {
                $kind = false;
            }

            // If kind is detected
            if ($kind) {
                // If tag location at the beginning and/or ending detected
                foreach ($sideA as $side => $info) {
                    // Get quality category by referring to a correct constant
                    $const = constant('self::' . $kind . '_' . $side);

                    // Add to states
                    $this->states[$const] = $const;
                }

                // If whitespace kind is linebreak
                if ($kind == 'LNBR') {
                    // Check prev and next character
                    foreach ([
                        self::SPACE_LNBR => -1,
                        self::LNBR_SPACE => 0,
                    ] as $category => $shift) {
                        // If it's a ordinary space
                        if (mb_substr($fieldText, $tag->startIndex + $shift, 1) == ' ') {
                            // Append quality category
                            $this->states[$category] = $category;
                        }
                    }
                }
            }
        }
    }

    /**
     * Retrieves the evaluated states
     * @return string[]
     */
    public function getStates()
    {
        return $this->states;
    }

    /**
     * @return boolean
     */
    public function hasStates()
    {
        return count($this->states) > 0;
    }
}
