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
 * TODO ANNOTATE
 * enableUneditedFuzzyMatchCheck
 * enableEdited100MatchCheck
 */
class editor_Segment_MatchRate_Provider extends editor_Segment_Quality_Provider {
    
    /**
     * @var string
     */
    const EDITED_100PERCENT_MATCH = 'edited_100percent_match';
    /**
     * @var string
     */
    const UNEDITED_FUZZY_MATCH = 'unedited_fuzzy_match';
    /**
     * Using the internal tag type
     * @var string
     */
    protected static $type = 'matchrate';
    
    public function processSegment(editor_Models_Task $task, Zend_Config $qualityConfig, editor_Segment_Tags $tags, string $processingMode) : editor_Segment_Tags {

        if((!$qualityConfig->enableUneditedFuzzyMatchCheck && !$qualityConfig->enableEdited100MatchCheck) || ($processingMode == editor_Segment_Processing::IMPORT && !$qualityConfig->enableUneditedFuzzyMatchCheck)){
            return $tags;
        }
        $segment = $tags->getSegment();
        // no need to check for edited 100% matches on import
        if($qualityConfig->enableEdited100MatchCheck && $processingMode != editor_Segment_Processing::IMPORT){
            // TODO AUTOQA klären: 100% oder >= 100% ???
            if($segment->isEdited() && $segment->getMatchRate() >= 100){
                $tags->addAllTargetsQuality(static::$type, self::EDITED_100PERCENT_MATCH);
            }
        }
        if($qualityConfig->enableUneditedFuzzyMatchCheck){
            if($segment->isPretranslated() && $segment->getMatchRate() < 100){
                $tags->addAllTargetsQuality(static::$type, self::UNEDITED_FUZZY_MATCH);
            }
        }
        return $tags;
    }
    
    public function translateType(ZfExtended_Zendoverwrites_Translate $translate) : string {
        return $translate->_('Match-Analyse');
    }
    
    public function translateCategory(ZfExtended_Zendoverwrites_Translate $translate, string $category, editor_Models_Task $task) : string {
        switch($category){
            case editor_Segment_MatchRate_Provider::UNEDITED_FUZZY_MATCH:
                return $translate->_('Unbearbeiteter Fuzzy');
                
            case editor_Segment_MatchRate_Provider::EDITED_100PERCENT_MATCH:
                return $translate->_('Bearbeiteter 100% Match');
        }
        return NULL;
    }
    
    public function isFullyChecked(Zend_Config $qualityConfig) : bool {
        return ($qualityConfig->enableUneditedFuzzyMatchCheck && $qualityConfig->enableEdited100MatchCheck);
    }
}
