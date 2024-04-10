<?php
/*
 START LICENSE AND COPYRIGHT

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a plug-in for translate5.
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
 http://www.gnu.org/licenses/gpl.html
 http://www.translate5.net/plugin-exception.txt

 END LICENSE AND COPYRIGHT
 */

/**
 * The Quality provider
 * This class just provides the translations for the filter backend
 */
class editor_Segment_Numbers_QualityProvider extends editor_Segment_Quality_Provider
{
    /**
     * Quality type
     *
     * @var string
     */
    protected static $type = 'numbers';

    /**
     * Flag indicating whether this quality has categories
     *
     * @var bool
     */
    protected static $hasCategories = true;

    /**
     * Method to check whether this quality is turned On
     */
    public function isActive(Zend_Config $qualityConfig, Zend_Config $taskConfig): bool
    {
        return $qualityConfig->enableSegmentNumbersCheck == 1;
    }

    /**
     * Check segment against quality
     *
     * {@inheritDoc}
     * @see editor_Segment_Quality_Provider::processSegment()
     */
    public function processSegment(editor_Models_Task $task, Zend_Config $qualityConfig, editor_Segment_Tags $tags, string $processingMode): editor_Segment_Tags
    {
        // If this check is turned Off in config - return $tags
        if (! $qualityConfig->enableSegmentNumbersCheck == 1) {
            return $tags;
        }

        // If processing mode is 'alike'
        if ($processingMode == editor_Segment_Processing::ALIKE) {
            // the only task in an alike process is cloning the qualities ...
            $tags->cloneAlikeQualitiesByType(static::$type);

            // Else
        } elseif ($processingMode == editor_Segment_Processing::EDIT || editor_Segment_Processing::isOperation($processingMode)) {
            // Get segment shortcut
            $segment = $tags->getSegment();

            // Distinct states
            $states = [];

            // Include snc lib
            include_once APPLICATION_PATH . '/modules/editor/Segment/Numbers/SNC/snc_main.php';

            // Foreach target
            foreach ($tags->getTargets() as $target) { /* @var $target editor_Segment_FieldTags */
                // If the target is empty, we do not need to check
                if (! $target->isEmpty()) {
                    // Do check
                    $check = new editor_Segment_Numbers_Check($task, $target->getField(), $segment);

                    // Get error cases grouped by category
                    foreach ($check->getStates() as $category => $cases) {
                        // Multiple error cases of same category can be added for same target
                        // so for each case we add a quality-record, but we don't add info
                        // that will make possible to distunguish between cases of same category within same target
                        // due to that there was a decision to postpone that development
                        foreach ($cases as $case) {
                            $tags->addQuality($target->getField(), static::$type, $category);
                        }
                    }
                }
            }
        }

        // Return
        return $tags;
    }

    /**
     * Translate quality type
     *
     * @throws Zend_Exception
     */
    public function translateType(ZfExtended_Zendoverwrites_Translate $translate): ?string
    {
        return $translate->_('Zahlen');
    }

    public function translateCategory(
        ZfExtended_Zendoverwrites_Translate $translate,
        string $category,
        ?editor_Models_Task $task
    ): ?string {
        return match ($category) {
            editor_Segment_Numbers_Check::NUM1 => $translate->_('Zahlen Quelle ≠ Ziel'),
            editor_Segment_Numbers_Check::NUM2 => $translate->_('Alphanumerische Zeichenfolgen: Unterschiede'),
            editor_Segment_Numbers_Check::NUM3 => $translate->_('Formatänderung (Datumsangaben u.ä.)'),
            editor_Segment_Numbers_Check::NUM4 => $translate->_('Trenner nicht lokalisiert'),
            editor_Segment_Numbers_Check::NUM5 => $translate->_('Formatierung 1000er-Zahl geändert'),
            editor_Segment_Numbers_Check::NUM6 => $translate->_('Unterschiedliche Minuszeichen'),
            editor_Segment_Numbers_Check::NUM7 => $translate->_('Trenner aus Quelle geändert'),
            editor_Segment_Numbers_Check::NUM8 => $translate->_('Zahlwort aus Quelle als Zahl in Ziel gefunden'),
            editor_Segment_Numbers_Check::NUM9 => $translate->_('Zahl aus Quelle als Zahlwort in Ziel gefunden'),
            editor_Segment_Numbers_Check::NUM10 => $translate->_('Formatänderung (Ordinalzahlen, führende Null u.ä.)'),
            editor_Segment_Numbers_Check::NUM11 => $translate->_('Untersch. Zeichen/Formatierung für Zahlen-Intervall'),
            editor_Segment_Numbers_Check::NUM12 => $translate->_('1000er-Trenner nicht erlaubt'),
            editor_Segment_Numbers_Check::NUM13 => $translate->_('Dubiose Zahl aus Quelle unverändert in Ziel'),
            default => null,
        };
    }

    /**
     * Translate category tooltip
     *
     * @return string|null
     */
    public function translateCategoryTooltip(ZfExtended_Zendoverwrites_Translate $translate, string $category, editor_Models_Task $task): string
    {
        switch ($category) {
            case editor_Segment_Numbers_Check::NUM13:
                return $translate->_('Falls es sich dabei um keine Dezimalzahl, sondern eine  Liste mit fehlenden Leerzeichen zwischen Listenelementen handelt, bitte im Ziel Leerzeichen zwischen Listenelementen einfügen. Bei falsch verwendetem Dezimaltrenner in der Quelle bitte Meldung ignorieren.');
        }

        return '';
    }

    /**
     * Categories in this quality
     */
    public function getAllCategories(?editor_Models_Task $task): array
    {
        return [
            editor_Segment_Numbers_Check::NUM1,
            editor_Segment_Numbers_Check::NUM2,
            editor_Segment_Numbers_Check::NUM3,
            editor_Segment_Numbers_Check::NUM4,
            editor_Segment_Numbers_Check::NUM5,
            editor_Segment_Numbers_Check::NUM6,
            editor_Segment_Numbers_Check::NUM7,
            editor_Segment_Numbers_Check::NUM8,
            editor_Segment_Numbers_Check::NUM9,
            editor_Segment_Numbers_Check::NUM10,
            editor_Segment_Numbers_Check::NUM11,
            editor_Segment_Numbers_Check::NUM12,
            editor_Segment_Numbers_Check::NUM13,
        ];
    }
}
