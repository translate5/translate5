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
class editor_Segment_Consistent_QualityProvider extends editor_Segment_Quality_Provider {

    /**
     * Quality type
     *
     * @var string
     */
    protected static $type = 'consistent';

    /**
     * Flag indicating whether this quality has categories
     *
     * @var bool
     */
    public static $hasCategories = true;

    /**
     * Method to check whether this quality is turned On
     *
     * @param Zend_Config $qualityConfig
     * @param Zend_Config $taskConfig
     * @return bool
     */
    public function isActive(Zend_Config $qualityConfig, Zend_Config $taskConfig) : bool {
        return $qualityConfig->enableSegmentConsistentCheck == 1;
    }

    /**
     * Check segment against quality
     *
     * {@inheritDoc}
     * @see editor_Segment_Quality_Provider::processSegment()
     */
    public function processSegment(editor_Models_Task $task, Zend_Config $qualityConfig, editor_Segment_Tags $tags, string $processingMode) : editor_Segment_Tags
    {
        // If this check is turned Off in config - return $tags
        if (!$qualityConfig->enableSegmentConsistentCheck == 1) {
            return $tags;
        }

        // If processing mode is 'alike' - the only task in an alike process is cloning the qualities
        if ($processingMode == editor_Segment_Processing::ALIKE) {
            $tags->cloneAlikeQualitiesByType(static::$type);
        }

        // Return tags
        return $tags;
    }

    /**
     * Check task segments against quality
     *
     * {@inheritDoc}
     * @see editor_Segment_Quality_Provider::processSegments()
     */
    public function processSegments(editor_Models_Task $task, Zend_Config $qualityConfig, string $processingMode) {

        // If this check is turned Off in config - return $tags
        if (!$qualityConfig->enableSegmentConsistentCheck == 1) return;

        // Else
        if (!($processingMode == editor_Segment_Processing::EDIT || editor_Segment_Processing::isOperation($processingMode)))
            return;

        // Get check object, containing detected quality categories
        $check = new editor_Segment_Consistent_Check($task);

        // Drop task's existing qualities of `type` = `consistent`
        $task->clearQualitiesByType(editor_Segment_Consistent_QualityProvider::qualityType());

        // Process check results
        foreach ($check->getStates() as $segmentId => $qualityCategoryA) {

            // Load segment
            $_segment = ZfExtended_Factory::get('editor_Models_Segment'); $_segment->load($segmentId);

            // Get tags
            $_tags = editor_Segment_Tags::fromSegment($task, $processingMode, $_segment, false);

            // Foreach target - add qualities
            foreach ($_tags->getTargets() as $_target) {
                foreach ($qualityCategoryA as $qualityCategoryI) {
                    $_tags->addQuality($_target->getField(), static::$type, $qualityCategoryI);
                }
            }

            // Flush changes
            $_tags->flush();
        }
    }

    /**
     * Translate quality type
     *
     * @param ZfExtended_Zendoverwrites_Translate $translate
     * @return string|null
     * @throws Zend_Exception
     */
    public function translateType(ZfExtended_Zendoverwrites_Translate $translate) : ?string {
        return $translate->_('Einheitlichkeit');
    }

    /**
     * Translate quality type tooltip
     *
     * @param ZfExtended_Zendoverwrites_Translate $translate
     * @return string|null
     * @throws Zend_Exception
     */
    public function translateTypeTooltip(ZfExtended_Zendoverwrites_Translate $translate) : ?string {
        return '';
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
            case editor_Segment_Consistent_Check::SOURCE: return $translate->_('Uneinheitliche Quelle');
            case editor_Segment_Consistent_Check::TARGET: return $translate->_('Uneinheitliches Ziel');
        }
        return NULL;
    }

    /**
     * Translate category tooltip
     *
     * @param ZfExtended_Zendoverwrites_Translate $translate
     * @param string $category
     * @param editor_Models_Task $task
     * @return string|null
     */
    public function translateCategoryTooltip(ZfExtended_Zendoverwrites_Translate $translate, string $category, editor_Models_Task $task) : ?string {
        switch($category){
            case editor_Segment_Consistent_Check::SOURCE: return $translate->_('Findet Segmente mit dem selben Ziel, aber unterschiedlicher Quelle (Tags werden ignoriert)');
            case editor_Segment_Consistent_Check::TARGET: return $translate->_('Findet Segmente mit der selben Quelle, aber unterschiedlichem Ziel (Tags werden ignoriert)');
        }
        return '';
    }

    /**
     * Categories in this quality
     *
     * @param editor_Models_Task $task
     * @return array
     */
    public function getAllCategories(editor_Models_Task $task) : array {
        return [
            editor_Segment_Consistent_Check::SOURCE,
            editor_Segment_Consistent_Check::TARGET,
        ];
    }
}
