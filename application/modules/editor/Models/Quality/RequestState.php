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
 * Decodes the filter qualities sent by request
 * This is used for the qualities itself as well as the segment grid filter
 * The state is sent as a single string with the following "encoding": $quality_type1:$quality_category1,$quality_type2,$quality_type3:$quality_category3,...|$filter_mode|$quality_type1:$quality_category1,$quality_type2,$quality_type3:$quality_category3,...
 * These 3 parts represent 0: the checked filters, 1: the current filter mode, 2: the collapsed filters
 * NOTE: the user-restriction is not related to the Request obviously since it is session based but it is needed everywhere the other filters are needed ...
 */
class editor_Models_Quality_RequestState {
    
    /**
     * 
     * @var editor_Models_Task
     */
    private $task;
    /**
     * Represents the sent filter mode
     * @var string
     */
    private $mode;
    /**
     * Represents the sent checked qualities list
     * @var string
     */
    private $checked;
    /**
     * Representsa the sent collapsed qualities list
     * @var string
     */
    private $collapsed;
    /**
     * Represents the transformed list of qualities
     * @var string
     */
    private $catsByType = NULL;
    /**
     * The current user restriction
     * @var string
     */
    private $userGuid = NULL;
    /**
     * 
     * @var int
     */
    private $editableRestriction = NULL;
    /**
     * 
     * @var array
     */
    private $segmentIdsRestriction = NULL;
    /**
     * 
     * @var boolean
     */
    private $hasEditableFaults = false;
   
    public function __construct(string $requestValue=NULL, editor_Models_Task $task){
        $this->task = $task;
        if($requestValue != NULL && strpos($requestValue, '|') !== false){
            $parts = explode('|', $requestValue);
            $this->checked = (count($parts) < 1 || empty($parts[0]) || $parts[0] == 'NONE' || $parts[0] == 'root') ? '' : $parts[0];
            $this->mode = (count($parts) < 2 || empty($parts[1])) ? editor_Models_Quality_AbstractView::FILTER_MODE_DEFAULT : $parts[1];
            $this->collapsed = (count($parts) < 3 || empty($parts[2]) || $parts[2] == 'NONE') ? '' : $parts[2];
        }
        //  our user restriction, depends on if the user is a normal editor or has the right to manage qualities (and thus sees other users qualities)
        if(!ZfExtended_Acl::getInstance()->isInAllowedRoles(editor_User::instance()->getRoles(), 'frontend', 'editorManageQualities')){
            $this->userGuid = editor_User::instance()->getGuid();
        }
    }
    /**
     * parses the request for segment filtering
     */
    private function createSegmentFilters(){
        if($this->catsByType === null){
            $this->catsByType = [];
            $hasNonEditableFaults = false;
            $hasOtherCats = false;
            if($this->checked != ''){
                foreach(explode(',', $this->checked) as $quality){
                    if(strpos($quality, ':') !== false){
                        list($type, $category) = explode(':', $quality);
                        if(!array_key_exists($type, $this->catsByType)){
                            $this->catsByType[$type] = [];
                        }
                        if($type == editor_Segment_Tag::TYPE_INTERNAL && ($category == editor_Segment_Internal_TagComparision::TAG_STRUCTURE_FAULTY || $category == editor_Segment_Internal_TagComparision::TAG_STRUCTURE_FAULTY_NONEDITABLE)){
                            $this->catsByType[$type][] = editor_Segment_Internal_TagComparision::TAG_STRUCTURE_FAULTY;
                            if($category == editor_Segment_Internal_TagComparision::TAG_STRUCTURE_FAULTY){
                                $this->hasEditableFaults = true;
                                $hasOtherCats = true;
                            } else {
                                $hasNonEditableFaults = true;
                            }
                        } else {
                            $this->catsByType[$type][] = $category;
                            $hasOtherCats = true;
                        }
                    }
                }
                // prevent the potential duplication of the TAG_STRUCTURE_FAULTY category and evaluate the needed editable restriction
                if($hasNonEditableFaults){
                    $this->catsByType[editor_Segment_Tag::TYPE_INTERNAL] = array_unique($this->catsByType[editor_Segment_Tag::TYPE_INTERNAL]);
                    $this->editableRestriction = ($hasOtherCats) ? NULL : 0;
                } else {
                    $this->editableRestriction = 1;
                }
            }
        }
    }
    /**
     * Retrieves the current filter mode
     * @return string
     */
    public function getFilterMode() : string {
        return $this->mode;
    }
    /**
     * Retrieves a flat hashtable of checked qualities
     * Structure like [ 'mqm' => true, 'mqm:mqm_1' => true, 'term' => true, 'term:termSuperseded' => true, ....  ]
     * @return array
     */
    public function getCheckedList() : array {
        $list = [];
        if($this->checked != ''){
            foreach(explode(',', $this->checked) as $typeCat){
                $list[$typeCat] = true;
            }
        }
        return $list;
    }
    /**
     * Retrieves a flat hashtable of collapsed qualities
     * Structure like [ 'mqm' => true, 'mqm:mqm_1' => true, 'term' => true, 'term:termSuperseded' => true, ....  ]
     * @return array
     */
    public function getCollapsedList() : array {
        $list = [];
        if($this->collapsed != ''){
            foreach(explode(',', $this->collapsed) as $typeCat){
                $list[$typeCat] = true;
            }
        }
        return $list;
    }
    /**
     * Retrieves, if we have checked categories
     * @return bool
     */
    public function hasCheckedCategoriesByType() : bool {
        $this->createSegmentFilters();
        return (count($this->catsByType) > 0);
    }
    /**
     * Retrieves a nested hashtable of checked qualities as used for editor_Models_Db_SegmentQuality::getSegmentIdsForQualityFilter
     * Structure like [ 'mqm' => true, 'mqm:mqm_1' => true, 'term' => true, 'term:termSuperseded' => true, ....  ]
     * @return array
     */
    public function getCheckedCategoriesByType() : array {
        $this->createSegmentFilters();
        return $this->catsByType;
    }
    
    /**
     * Retrieves, if we have checked categories
     * @return bool
     */
    public function getSegmentIdsRestriction() : array {
        if($this->segmentIdsRestriction === NULL){
            $this->segmentIdsRestriction = editor_Models_Db_SegmentQuality::getSegmentIdsForQualityFilter($this, $this->task->getTaskGuid());
        }
        return $this->segmentIdsRestriction;
    }
    
    /**
     * Retrieves the needed restriction for the falsePositive column
     * @return int|NULL
     */
    public function getFalsePositiveRestriction() : ?int {
        switch($this->mode){
            case 'falsepositive':
            case 'false_positive':
            case 'fp':
                return 1;
            case 'error':
            case 'notfalsepositive':
            case 'nfp':
                return 0;
            case 'all':
            case 'both':
            default:
                return NULL;
        }
    }
    /**
     * Retrieves if we have a false-positive restriction
     * @return bool
     */
    public function hasFalsePositiveRestriction() : bool {
        return ($this->getFalsePositiveRestriction() !== NULL);
    }
    /**
     * Retrieves, if the shown segments should be restricted to editable segments in the main segment filter
     * @return bool
     */
    public function hasEditableRestriction() : bool {
        $this->createSegmentFilters();
        return ($this->editableRestriction !== NULL);
    }
    /**
     * 
     * @return int
     */
    public function getEditableRestriction() : ?int {
        $this->createSegmentFilters();
        return $this->editableRestriction;
    }
    /**
     * Retrieves if we have a user restriction (by userGuid)
     * @return bool
     */
    public function hasUserRestriction() : bool {
        return ($this->userGuid != NULL);
    }
    /**
     * Retrieves, if the category TAG_STRUCTURE_FAULTY of the type "internal" was present
     * @return bool
     */
    public function hasCategoryEditableInternalTagFaults() : bool {
        $this->createSegmentFilters();
        return $this->hasEditableFaults;
    }
    /**
     * Retrieves the segment-nrs the current user can edit in case we have a restricted segment range from worklow.
     * Returns NULL if no restriction is in place. An empty array means, the user can edit no segments
     * @return array|NULL
     */
    public function getUserRestrictedSegmentNrs() : ?array {
        if($this->userGuid != NULL){
            $tua = editor_Models_Loaders_Taskuserassoc::loadByTaskForceWorkflowRole($this->userGuid, $this->task);
            /* @var $tua editor_Models_TaskUserAssoc */
            $step = $tua->getWorkflowStepName();
            if(!$tua->isSegmentrangedTaskForStep($this->task, $step)){
                return NULL;
            }
            return $tua->getAllAssignedSegmentsByUserAndStep($this->task->getTaskGuid(), $this->userGuid, $step);
        }
        return NULL;
    }
}
