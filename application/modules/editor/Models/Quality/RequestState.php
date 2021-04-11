<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * The state is sent as a single string with the following "encoding": $quality_type1:$quality_category1,$quality_type2,$quality_type3:$quality_category3,...|$filter_mode
 */
class editor_Models_Quality_RequestState {
    
    /**
     * Represents the sent filter mode
     * @var string
     */
    private $mode;
    /**
     * Represents the sent qualities list
     * @var string
     */
    private $checked;
    /**
     * Represents the transformed list of qualities
     * @var string
     */
    private $catsByType = null;
    /**
     * Represents the transformed list of qmIds
     * @var string
     */
    private $qmIds = null;
    
    public function __construct(string $requestValue){
        list($this->checked, $this->mode) = explode('|', $requestValue);
        if(empty($this->mode)){
            $this->mode = editor_Models_Quality_AbstractView::FILTER_MODE_DEFAULT;
        }
        // filter various unwanted states of the checked qualities out
        if(empty($this->checked) || $this->checked == 'NONE'|| $this->checked == 'root'){
            $this->checked = '';
        }
    }
    /**
     * parses the request for segment filtering
     */
    private function createSegmentFilters(){
        if($this->catsByType === null){
            $this->catsByType = [];
            $this->qmIds = [];
            if($this->checked != ''){
                // we add only QMs if we are not filtering for false psitives !
                $addQms = ($this->getFalsePositiveRestriction() !== 1);
                foreach(explode(',', $this->checked) as $quality){
                    if(strpos($quality, ':') !== false){
                        list($type, $category) = explode(':', $quality);
                        if($type == editor_Segment_Tag::TYPE_QM){
                            if($addQms){
                                // the qm-category is assembled as 'qm_'.$qmId
                                list($type, $qmId) = explode('_', $category);
                                $this->qmIds[] = $qmId;
                            }
                        } else {
                            if(!array_key_exists($type, $this->catsByType)){
                                $this->catsByType[$type] = [];
                            }
                            $this->catsByType[$type][] = $category;
                        }
                    }
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
    public function hasCheckedCategoriesByType() : bool {
        $this->createSegmentFilters();
        return (count($this->catsByType) > 0);
    }
    /**
     * Retrieves a flat list of $qmIds that were encoded as qualities
     * @return array
     */
    public function getCheckedQmIds() : array {
        $this->createSegmentFilters();
        return $this->qmIds;
    }
    /**
     * Retrieves if we have checked qmIds
     * @return array
     */
    public function hasCheckedQmIds() : bool {
        $this->createSegmentFilters();
        return (count($this->qmIds) > 0);
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
}
