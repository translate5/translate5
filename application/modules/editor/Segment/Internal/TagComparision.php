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
 * Compares two field-tags if they have the same amount of internal tags in the same order
 */
class editor_Segment_Internal_TagComparision {
    
    /**
     * 
     * @var string;
     */
    private $status = NULL;
    /**
     * 
     * @param editor_Segment_FieldTags $toCheck
     * @param editor_Segment_FieldTags $against
     */
    public function __construct(editor_Segment_FieldTags $toCheck, editor_Segment_FieldTags $against){
        $toCheck->sort();
        $against->sort();
        $this->status = array();
        $checkTags = $toCheck->getByType(editor_Segment_Tag::TYPE_INTERNAL);
        /* @var $checkTags editor_Segment_Internal_Tag[] */
        $againstTags = $against->getByType(editor_Segment_Tag::TYPE_INTERNAL);
        /* @var $againstTags editor_Segment_Internal_Tag[] */
        $numCheckTags = count($checkTags);
        $numAgainstTags = count($checkTags);
        if($numCheckTags == $numAgainstTags){
            if($numAgainstTags > 0){
                for($i=0; $i < $numCheckTags; $i++){
                    if(!$checkTags[$i]->hasEqualClasses($againstTags[$i]) || !$checkTags[$i]->hasEqualName($againstTags[$i])){
                        $this->status = editor_Segment_Internal_TagCheck::WRONG_ORDER;
                        return;
                    }
                }
            }
        } else {
            $this->status = editor_Segment_Internal_TagCheck::MISSING;
        }
    }
    /**
     * 
     * @return string[]
     */
    public function getStatus(){
        return $this->status;
    }
}
