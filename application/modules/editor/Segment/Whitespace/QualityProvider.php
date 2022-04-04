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
 * 
 * The Quality provider 
 * This class just provides the translations for the filter backend
 */
class editor_Segment_Whitespace_QualityProvider extends editor_Segment_Quality_Provider {

    /**
     * Quality type
     *
     * @var string
     */
    protected static $type = 'whitespace';

    /**
     * Flag indicating whether this quality has categories
     *
     * @var bool
     */
    protected static $hasCategories = true;

    /**
     * Method to check whether this quality is turned On
     *
     * @param Zend_Config $qualityConfig
     * @param Zend_Config $taskConfig
     * @return bool
     */
    public function isActive(Zend_Config $qualityConfig, Zend_Config $taskConfig) : bool {
        return $qualityConfig->enableSegmentWhitespaceCheck == 1 || true;
    }

    /**
     * Check segment against quality
     *
     * {@inheritDoc}
     * @see editor_Segment_Quality_Provider::processSegment()
     */
    public function processSegment(editor_Models_Task $task, Zend_Config $qualityConfig, editor_Segment_Tags $tags, string $processingMode) : editor_Segment_Tags {

        // If this check is turned Off in config - return $tags
        if (!$qualityConfig->enableSegmentWhitespaceCheck == 1) {
            //return $tags;
        }

        // If processing mode is 'alike'
        if ($processingMode == editor_Segment_Processing::ALIKE){

            // the only task in an alike process is cloning the qualities ...
            $tags->cloneAlikeQualitiesByType(static::$type);

        // Else
        } else if ($processingMode == editor_Segment_Processing::EDIT || editor_Segment_Processing::isOperation($processingMode)) {

            // Get segment shortcut
            $segment = $tags->getSegment();

            // Distinct states
            $states = [];

            // Foreach target
            foreach ($tags->getTargets() as $target) { /* @var $target editor_Segment_FieldTags */

                // Do check
                $check = new editor_Segment_Whitespace_Check($task, $target, $segment);

                // Collect distinct states
                $states += $check->getStates();
            }

            // Process check results
            foreach ($states as $state) {
                $tags->addQuality('target', static::$type, $state);
            }
        }

        // Return
        return $tags;
    }

    /**
     * Translate quality type
     *
     * @param ZfExtended_Zendoverwrites_Translate $translate
     * @return string|null
     * @throws Zend_Exception
     */
    public function translateType(ZfExtended_Zendoverwrites_Translate $translate) : ?string {
        return $translate->_('Leerraum am Anfang/Ende');
    }

    /**
     * Translate category
     *
     * @param ZfExtended_Zendoverwrites_Translate $translate
     * @param string $category
     * @param editor_Models_Task $task
     * @return string|null
     */
    public function translateCategory(ZfExtended_Zendoverwrites_Translate $translate, string $category, editor_Models_Task $task) : ?string {
        switch($category){
            case editor_Segment_Whitespace_Check::BEG_TAG_SPACE: return $translate->_('Leerzeichen am Anfang');
            case editor_Segment_Whitespace_Check::NBSP_BEG: return $translate->_('Geschützes Leerzeichen am Anfang');
            case editor_Segment_Whitespace_Check::TAB_BEG: return $translate->_('Tab am Anfang');
            case editor_Segment_Whitespace_Check::LNBR_BEG: return $translate->_('Umbruch am Anfang');

            case editor_Segment_Whitespace_Check::SPACE_TAG_END: return $translate->_('Leerzeichen am Ende');
            case editor_Segment_Whitespace_Check::NBSP_END: return $translate->_('Geschützes Leerzeichen am Ende');
            case editor_Segment_Whitespace_Check::TAB_END: return $translate->_('Tab am Ende');
            case editor_Segment_Whitespace_Check::LNBR_END: return $translate->_('Umbruch am Ende');

            case editor_Segment_Whitespace_Check::SPACE_LNBR: return $translate->_('Leerzeichen vor Zeilenumbruch');
            case editor_Segment_Whitespace_Check::LNBR_SPACE: return $translate->_('Leerzeichen nach Zeilenumbruch');
        }
        return NULL;
    }

    /**
     * Categories in this quality
     *
     * @param editor_Models_Task $task
     * @return array
     */
    public function getAllCategories(editor_Models_Task $task) : array {
        return [
            editor_Segment_Whitespace_Check::BEG_TAG_SPACE,
            editor_Segment_Whitespace_Check::NBSP_BEG,
            editor_Segment_Whitespace_Check::TAB_BEG,
            editor_Segment_Whitespace_Check::LNBR_BEG,

            editor_Segment_Whitespace_Check::SPACE_TAG_END,
            editor_Segment_Whitespace_Check::NBSP_END,
            editor_Segment_Whitespace_Check::TAB_END,
            editor_Segment_Whitespace_Check::LNBR_END,

            editor_Segment_Whitespace_Check::SPACE_LNBR,
            editor_Segment_Whitespace_Check::LNBR_SPACE,
        ];
    }
}
