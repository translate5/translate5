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
class editor_Segment_Qm_Provider extends editor_Segment_Quality_Provider {

    protected static $type = editor_Segment_Tag::TYPE_QM;
    /**
     * 
     * @var string[]
     */
    private $typesByIndex = null;
    /**
     * Creates the category of a QM tag out of it's category index (which will be saved seperately - what can be seen as a redundancy)
     * @param int $categoryIndex
     * @return string
     */
    public static function createCategoryVal(int $categoryIndex) : string {
        return editor_Segment_Tag::TYPE_QM.'_'.strval($categoryIndex);
    }
    
    public function processSegment(editor_Models_Task $task, Zend_Config $qualityConfig, editor_Segment_Tags $tags, string $processingMode) : editor_Segment_Tags {
        if($processingMode == editor_Segment_Processing::ALIKE){
            // the only task we ever have to do is cloning the qm qualities in the alike copying process
            $tags->cloneAlikeQualitiesByType(self::$type);
        }
        return $tags;
    }
    
    public function translateType(ZfExtended_Zendoverwrites_Translate $translate) : ?string {
        return $translate->_('Manuelle QS (ganzes Segment)');
    }
    
    public function translateCategory(ZfExtended_Zendoverwrites_Translate $translate, string $category, editor_Models_Task $task) : ?string {
        $typesByIndex = $this->getTypesByIndex();
        $categoryIndex = intval(str_replace(editor_Segment_Tag::TYPE_QM.'_', '', $category)); // see editor_Models_Db_SegmentQuality::addMqm how we evaluate the index from the category
        if(isset($typesByIndex[$categoryIndex])){
            return $translate->_($typesByIndex[$categoryIndex]);
        }
        // not worth an exception, should not happen if configuration correct
        return 'UNKNOWN QM-TYPE-ID '.$categoryIndex;
    }
    
    public function getAllCategories(editor_Models_Task $task) : array {
        $typesByIndex = array_keys($this->getTypesByIndex());
        return array_map(function ($id){ return editor_Segment_Qm_Provider::createCategoryVal($id); }, $typesByIndex);
    }
    
    public function hasSegmentTags() : bool {
        return false;
    }
    /**
     * 
     * @return string[]
     */
    private function getTypesByIndex() : array {
        if($this->typesByIndex == null){
            $config =Zend_Registry::get('config');
            $this->typesByIndex = $config->runtimeOptions->segments->qualityFlags->toArray();
        }
        return $this->typesByIndex;
    }
}
