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
 * Checks Internal tags in the edited Segments for validity (all tags present, structure correct (closing tags following opening tags without interleaves)
 *
 */
class editor_Segment_Internal_TagCheck extends editor_Segment_Quality_Provider {

    /**
     * Using the internal tag type
     * @var string
     */
    protected static $type = editor_Segment_Tag::TYPE_INTERNAL;
    
    public function processSegment(editor_Models_Task $task, Zend_Config $taskConfig, editor_Segment_Tags $tags, bool $forImport) : editor_Segment_Tags {
        
        if(!$taskConfig->runtimeOptions->autoQA->enableInternalTagCheck || $forImport){
            return $tags;
        }
        // 1) Tag check: Bei Translation: Internal Tags gegen Source prüfen, bei Review: gegen original Target  (insofern gesetzt)          
        $isTranslationTask = $task->getEmptyTargets();
        $against = $tags->getOriginalOrNormalSource();
        if(!$isTranslationTask){
            $originalTarget = $tags->getOriginalTarget();
            if(!$originalTarget->isEmpty()){
                $against = $originalTarget;
            }
        }
        if($against != null){
            $data = [];
            foreach($tags->getTargets() as $toCheck){ /* @var $toCheck editor_Segment_Fieldtags */
                $comparision = new editor_Segment_Internal_TagComparision($toCheck, $against);
                foreach($comparision->getStati() as $status){
                    if(!array_key_exists($status, $data)){
                        $data[$status] = [];
                    }
                    // group the fields by category
                    $data[$status][] = $toCheck->getField();
                }
            }
            foreach($data as $category => $fields){
                $tags->addQuality($fields, editor_Segment_Tag::TYPE_INTERNAL, $category);
            }
        }
        return $tags;
    }
    
    public function translateType(ZfExtended_Zendoverwrites_Translate $translate) : string {
        return $translate->_('Internal tags');
    }
    
    public function translateCategory(ZfExtended_Zendoverwrites_Translate $translate, string $category) : string {
        switch($category){
            case editor_Segment_Internal_TagComparision::TAGS_MISSING:
                return $translate->_('Internal tags are missing');
                
            case editor_Segment_Internal_TagComparision::TAGS_ADDED:
                return $translate->_('Internal tags have been added');
                
            case editor_Segment_Internal_TagComparision::TAG_STRUCTURE_FAULTY:
                return $translate->_('The internal tags have an incorrect structure');
        }
        return NULL;
    }

}
