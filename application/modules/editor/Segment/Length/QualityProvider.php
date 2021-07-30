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
 * Adds the Segment Quality Entries for the QM tags that are referenscing he whole segment and are set completely outside of the segment processing
 * This class just provides the translations for the filter backend
 */
class editor_Segment_Length_QualityProvider extends editor_Segment_Quality_Provider {
    
    const TOO_LONG = 'too_long';
    
    const TOO_SHORT = 'too_short';

    protected static $type = 'length';

    public function isActive(Zend_Config $qualityConfig, Zend_Config $taskConfig) : bool {
        return ($qualityConfig->enableSegmentLengthCheck == 1);
    }
    
    public function processSegment(editor_Models_Task $task, Zend_Config $qualityConfig, editor_Segment_Tags $tags, string $processingMode) : editor_Segment_Tags {
        
        if(!$qualityConfig->enableSegmentLengthCheck){
            return $tags;
        }
        if($processingMode == editor_Segment_Processing::ALIKE){
            
            // the only task in an alike process is cloning the qualities ...
            $tags->cloneAlikeQualitiesByType(static::$type);
            
        } else if($processingMode == editor_Segment_Processing::EDIT || $processingMode == editor_Segment_Processing::IMPORT) {
            
            $segment = $tags->getSegment();
            // on Import, check only pretranslated segments
            if($processingMode == editor_Segment_Processing::IMPORT && !$segment->isPretranslated()){
                return $tags;
            }
            // TODO: limit to an imported pretranslation & editing
            
            
            $data = $segment->getDataObject();
            $meta = (property_exists($data, 'metaCache') && !empty($data->metaCache)) ? json_decode($data->metaCache, true) : NULL;
            if($meta != NULL){
                
                $sizeUnit = empty($meta['sizeUnit']) ? editor_Models_Segment_PixelLength::SIZE_UNIT_XLF_DEFAULT : $meta['sizeUnit'];
                $isPixelBased = ($sizeUnit == editor_Models_Segment_PixelLength::SIZE_UNIT_FOR_PIXELMAPPING);
                
                
                
                
            }
        }
        return $tags;
    }
    
    public function translateType(ZfExtended_Zendoverwrites_Translate $translate) : ?string {
        return $translate->_('Längen Prüfung');
    }
    /**
     *
    segment longer than allowed
    segment relevantly shorter than allowed ("more than x% shorter than allowed OR at least Y Pixel shorter than allowed" / x and y definable in config and overwriteable on instance, client and import level)

     */
    public function translateCategory(ZfExtended_Zendoverwrites_Translate $translate, string $category, editor_Models_Task $task) : ?string {
        switch($category){
            
            case self::TOO_LONG:
                return $translate->_('Interne Tags fehlen');
                
            case self::TOO_SHORT:
                return $translate->_('Interne Tags wurden hinzugefügt');
        }
        return NULL;
    }
    
    public function getAllCategories(editor_Models_Task $task) : array {
        return [
            self::TOO_LONG,
            self::TOO_SHORT
        ];
    }
}
