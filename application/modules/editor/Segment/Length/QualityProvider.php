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
class editor_Segment_Length_QualityProvider extends editor_Segment_Quality_Provider {

    protected static $type = 'length';
    /**
     * Holds the current restriction based on the quality config
     * This needs to be evaluated only once per request so it is static
     * @var editor_Segment_Length_Restriction
     */
    private static $restriction = NULL;
    /**
     * 
     * @return editor_Segment_Length_Restriction
     */
    public static function getRestriction(Zend_Config $qualityConfig, Zend_Config $taskConfig){
        if(static::$restriction === NULL){
            static::$restriction = new editor_Segment_Length_Restriction($qualityConfig, $taskConfig);
        }
        return static::$restriction;
    }

    public function isActive(Zend_Config $qualityConfig, Zend_Config $taskConfig) : bool {
        return ($qualityConfig->enableSegmentLengthCheck == 1 && static::getRestriction($qualityConfig, $taskConfig)->active);
    }
    /**
     * 
     * {@inheritDoc}
     * @see editor_Segment_Quality_Provider::processSegment()
     */
    public function processSegment(editor_Models_Task $task, Zend_Config $qualityConfig, editor_Segment_Tags $tags, string $processingMode) : editor_Segment_Tags {

        if(!$qualityConfig->enableSegmentLengthCheck == 1){
            return $tags;
        }
        $restriction = static::getRestriction($qualityConfig, $task->getConfig());
        if(!$restriction->active){
            return $tags;
        }
        if($processingMode == editor_Segment_Processing::ALIKE || $processingMode == editor_Segment_Processing::EDIT || editor_Segment_Processing::isOperation($processingMode)) {
            
            $segment = $tags->getSegment();
            // on Import, check only pretranslated segments
            if($processingMode == editor_Segment_Processing::IMPORT && !$segment->isPretranslated()){
                return $tags;
            }
            foreach($tags->getTargets() as $target){ /* @var $target editor_Segment_FieldTags */
                // if the target is empty, we do not need to check
                if(!$target->isEmpty()){
                    $check = new editor_Segment_Length_Check($target, $segment, $restriction);
                    if($check->hasStates()){
                        foreach($check->getStates() as $state){
                            $tags->addQuality($target->getField(), static::$type, $state);
                        }
                    }
                }
            }
        }
        return $tags;
    }
    
    public function translateType(ZfExtended_Zendoverwrites_Translate $translate) : ?string {
        return $translate->_('Längen Prüfung');
    }

    public function translateCategory(ZfExtended_Zendoverwrites_Translate $translate, string $category, editor_Models_Task $task) : ?string {
        switch($category){
            
            case editor_Segment_Length_Check::TOO_LONG:
                return $translate->_('Segment ist länger als erlaubt');
                
            case editor_Segment_Length_Check::NOT_LONG_ENOUGH:
                $translation = $translate->_('Segment ist relevant kürzer als erlaubt (mehr als {0}% oder min. {1} Pixel oder min. {2} Zeichen zu kurz)');
                // ugly: we need the task-config to evaluate the translation
                $taskConfig = $task->getConfig();
                $restriction = static::getRestriction($taskConfig->runtimeOptions->autoQA, $taskConfig);
                return str_replace('{0}', strval($restriction->maxLengthMinPercent), str_replace('{1}', strval($restriction->maxLengthMinPixel), str_replace('{2}', strval($restriction->maxLengthMinChars), $translation)));
                
            case editor_Segment_Length_Check::TOO_SHORT:
                return $translate->_('Segment ist kürzer als erlaubt');
                
            case editor_Segment_Length_Check::TOO_MANY_LINES:
                return $translate->_('Zu viele Zeilenumbrüche im Segment');
        }
        return NULL;
    }
    
    public function getAllCategories(editor_Models_Task $task) : array {
        return [
            editor_Segment_Length_Check::TOO_SHORT,
            editor_Segment_Length_Check::NOT_LONG_ENOUGH,
            editor_Segment_Length_Check::TOO_LONG,
            editor_Segment_Length_Check::TOO_MANY_LINES
        ];
    }
}
