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
 * Provides Qualities for TM usage, what is pretty simple: We just have the direct use of a 100% match and the non-edited fuzzy match
 */
class editor_Segment_MatchRate_Provider extends editor_Segment_Quality_Provider
{
    /**
     * @var string
     */
    public const EDITED_100PERCENT_MATCH = 'edited_100percent_match';

    /**
     * @var string
     */
    public const UNEDITED_FUZZY_MATCH = 'unedited_fuzzy_match';

    /**
     * Using the internal tag type
     * @var string
     */
    protected static $type = 'matchrate';

    public function isActive(Zend_Config $qualityConfig, Zend_Config $taskConfig): bool
    {
        return ($qualityConfig->enableUneditedFuzzyMatchCheck == 1 || $qualityConfig->enableEdited100MatchCheck == 1);
    }

    public function processSegment(editor_Models_Task $task, Zend_Config $qualityConfig, editor_Segment_Tags $tags, string $processingMode): editor_Segment_Tags
    {
        if (! $qualityConfig->enableUneditedFuzzyMatchCheck && (! $qualityConfig->enableEdited100MatchCheck || $processingMode == editor_Segment_Processing::IMPORT)) {
            return $tags;
        }

        if ($processingMode == editor_Segment_Processing::ALIKE) {
            // the only task in an alike process is cloning the qualities ...
            $tags->cloneAlikeQualitiesByType(static::$type);
        } else {
            // we need the segment to evaluate
            $segment = $tags->getSegment();

            // no need to check for edited 100% matches on import
            if ($processingMode != editor_Segment_Processing::IMPORT && $qualityConfig->enableEdited100MatchCheck && $segment->getMatchRate() >= 100 && $segment->isFromTM()) {
                $editedTargetFields = $tags->getEditedTargetFields();
                if (count($editedTargetFields) > 0) {
                    foreach ($editedTargetFields as $targetField) {
                        $tags->addQuality($targetField, static::$type, self::EDITED_100PERCENT_MATCH);
                    }
                }
            }
            // Fuzzy Match check must be done on import (where it can be assumed that all targets are set with the match) and for taken over TMs otherwise
            if ($qualityConfig->enableUneditedFuzzyMatchCheck && $segment->getMatchRate() < 100 && $segment->isInteractive() === false) {
                if ($segment->isPretranslated()) {
                    $tags->addAllTargetsQuality(static::$type, self::UNEDITED_FUZZY_MATCH);
                } elseif ($segment->isEditedTM()) {
                    $uneditedTargetFields = $tags->getEditedTargetFields();
                    if (count($uneditedTargetFields) > 0) {
                        foreach ($uneditedTargetFields as $targetField) {
                            $tags->addQuality($targetField, static::$type, self::UNEDITED_FUZZY_MATCH);
                        }
                    }
                }
            }
        }

        return $tags;
    }

    public function hasSegmentTags(): bool
    {
        return false;
    }

    public function translateType(ZfExtended_Zendoverwrites_Translate $translate): ?string
    {
        return $translate->_('Nutzung von TM-Treffern');
    }

    public function translateCategory(
        ZfExtended_Zendoverwrites_Translate $translate,
        string $category,
        ?editor_Models_Task $task
    ): ?string {
        return match ($category) {
            editor_Segment_MatchRate_Provider::UNEDITED_FUZZY_MATCH => $translate->_('Unbearbeiteter Fuzzy'),
            editor_Segment_MatchRate_Provider::EDITED_100PERCENT_MATCH => $translate->_('Bearbeiteter 100% Match'),
            default => null,
        };
    }

    public function getAllCategories(?editor_Models_Task $task): array
    {
        return [
            editor_Segment_MatchRate_Provider::UNEDITED_FUZZY_MATCH,
            editor_Segment_MatchRate_Provider::EDITED_100PERCENT_MATCH,
        ];
    }

    public function isFullyChecked(Zend_Config $qualityConfig, Zend_Config $taskConfig): bool
    {
        return ($qualityConfig->enableUneditedFuzzyMatchCheck == 1 && $qualityConfig->enableEdited100MatchCheck == 1);
    }
}
