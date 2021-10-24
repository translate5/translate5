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
 * Checks Internal tags in the edited Segments for validity (all tags present, structure correct (closing tags following opening tags without interleaves)
 *
 */
class editor_Segment_Internal_Provider extends editor_Segment_Quality_Provider {

    protected static $type = editor_Segment_Tag::TYPE_INTERNAL;
    
    protected static $segmentTagClass = 'editor_Segment_Internal_Tag';
    
    public function isActive(Zend_Config $qualityConfig, Zend_Config $taskConfig) : bool {
        return ($qualityConfig->enableInternalTagCheck == 1);
    }

    public function processSegment(editor_Models_Task $task, Zend_Config $qualityConfig, editor_Segment_Tags $tags, string $processingMode) : editor_Segment_Tags {
        
        if(!$qualityConfig->enableInternalTagCheck){
            return $tags;
        }
        // 1) Tag check: Bei Translation: Internal Tags gegen Source prüfen, bei Review: gegen original Target (insofern gesetzt)          
        $isTranslationTask = $task->getEmptyTargets();
        $against = $tags->getOriginalOrNormalSource();
        if(!$isTranslationTask){
            $originalTarget = $tags->getOriginalTarget();
            if(!$originalTarget->isEmpty()){
                $against = $originalTarget;
            }
        }
        foreach($tags->getTargets() as $toCheck){ /* @var $toCheck editor_Segment_Fieldtags */
            $comparision = new editor_Segment_Internal_TagComparision($toCheck, $against);
            foreach($comparision->getStati() as $status){
                $tags->addQuality($toCheck->getField(), editor_Segment_Tag::TYPE_INTERNAL, $status);
            }
        }
        return $tags;
    }
    
    public function translateType(ZfExtended_Zendoverwrites_Translate $translate) : ?string {
        return $translate->_('Interne Tags');
    }
    
    public function translateCategory(ZfExtended_Zendoverwrites_Translate $translate, string $category, editor_Models_Task $task) : ?string {
        switch($category){
            case editor_Segment_Internal_TagComparision::TAGS_MISSING:
                return $translate->_('Interne Tags fehlen');
                
            case editor_Segment_Internal_TagComparision::TAGS_ADDED:
                return $translate->_('Interne Tags wurden hinzugefügt');
            
            case editor_Segment_Internal_TagComparision::WHITESPACE_MISSING:
                return $translate->_('Whitespace wurde entfernt');
                
            case editor_Segment_Internal_TagComparision::WHITESPACE_ADDED:
                return $translate->_('Whitespace wurde hinzugefügt');

            case editor_Segment_Internal_TagComparision::TAG_STRUCTURE_FAULTY:
                return $translate->_('Interne Tags haben eine ungültige Struktur');
            // this is a virtual category that con not be found in the database  
            case editor_Segment_Internal_TagComparision::TAG_STRUCTURE_FAULTY_NONEDITABLE:
                return $translate->_('Interne Tags nicht editierbarer Segmente haben eine ungültige Struktur');
        }
        return NULL;
    }
    
    public function getAllCategories(editor_Models_Task $task) : array {
        return [
            editor_Segment_Internal_TagComparision::TAGS_MISSING,
            editor_Segment_Internal_TagComparision::TAGS_ADDED,
            editor_Segment_Internal_TagComparision::WHITESPACE_ADDED,
            editor_Segment_Internal_TagComparision::WHITESPACE_MISSING,
            editor_Segment_Internal_TagComparision::TAG_STRUCTURE_FAULTY
        ];
    }
    
    public function isFullyChecked(Zend_Config $qualityConfig, Zend_Config $taskConfig) : bool {
        return ($qualityConfig->enableInternalTagCheck == 1);
    }
    
    public function isExportedTag() : bool {
        return true;
    }
}
