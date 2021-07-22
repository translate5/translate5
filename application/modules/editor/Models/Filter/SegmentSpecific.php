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
 * overrides the segment filters, so that segment content is filtered with ignored case although we use bin collation in DB
 */
class editor_Models_Filter_SegmentSpecific extends ZfExtended_Models_Filter_ExtJs6 {

    /**
     * internal saved segment field names
     * @var array
     */
    protected $segmentFields = null;
    /**
     * used to filter for qualities
     * @var editor_Models_Quality_RequestState
     */
    protected $qualityState = NULL;
    /**
     * sets the fields which should be filtered lowercase
     * @param array $fields
     */
    public function setSegmentFields(array $fields) {
        $this->segmentFields = $fields;
    }
    /**
     * sets the quality filter. This is a "OR" filter that is handled seperately from the main filtering
     * @param editor_Models_Quality_RequestState $requestState
     * @param editor_Models_Task $task
     */
    public function setQualityFilter(editor_Models_Quality_RequestState $requestState) {
        $this->qualityState = $requestState;
        // if a quality filter is applied we must filter the qualities so that normal users just see their qualities
        // this filtering is done in the main select and nod in the filtered segment-ids via ::getSegmentIdsForQualityFilter because it's much cheaper to do it there
        if($requestState->hasCheckedCategoriesByType() && $requestState->hasUserRestriction()){
            // if the returned data is NULL this means, the state workflow does not justify filtering
            $filteredSegmentNrs = $requestState->getUserRestrictedSegmentNrs();
            if($filteredSegmentNrs !== NULL){
                $filter = new stdClass();
                $filter->field = 'segmentNrInTask';
                if(count($filteredSegmentNrs) < 2){
                    $filter->type = 'numeric';
                    $filter->comparison = 'eq';
                    // an empty segmentNr selection indicates no editable segments, we use the impossible nr -1 then
                    $filter->value = (count($filteredSegmentNrs) == 1) ? $filteredSegmentNrs[0] : -1;
                } else {
                    $filter->type = 'list';
                    $filter->comparison = 'in';
                    $filter->value = $filteredSegmentNrs;
                }
                $this->addFilter($filter);
            }
        }
        // normally, quaality filtering filters for editable segments
        if($requestState->hasEditableRestriction()){
            $filter = new stdClass();
            $filter->field = 'editable';
            $filter->type = 'numeric';
            $filter->comparison = 'eq';
            $filter->value = $requestState->getEditableRestriction();
            $this->addFilter($filter);
        }
    }
    /**
     * Overwritten to apply the additional qualities filter
     * {@inheritDoc}
     * @see ZfExtended_Models_Filter::applyToSelect()
     */
    public function applyToSelect(Zend_Db_Select $select, $applySort = true) {
        
        parent::applyToSelect($select, $applySort);
        
        if($this->qualityState != NULL){
            $colPrefix = (empty($this->defaultTable)) ? '' : '`'.$this->defaultTable.'`.';
            $conditions = [];
            if($this->qualityState->hasCheckedCategoriesByType()){
                // Note: the quality state's user filter is handled with a filter on the segment table so we don't need it here
                $segmentIds = $this->qualityState->getSegmentIdsRestriction();
                if(count($segmentIds) > 1){
                    $this->select->where($colPrefix.'id IN (?)', $segmentIds, Zend_Db::INT_TYPE);
                } else if(count($segmentIds) == 1){
                    $this->select->where($colPrefix.'id = ?', $segmentIds[0], Zend_Db::INT_TYPE);
                } else {
                    // no segment ids, trigger empty result
                    // error_log('editor_Models_Filter_SegmentSpecific: TRIGGER EMPTY SEGMENT-IDs');
                    $this->select->where('1 = 0');
                }
            }
            if(count($conditions) > 0){
                $this->select->where(implode(' OR ', $conditions));
            }
        }
        return $this->select;
    }
    /**
     * @param string $field
     * @param string $value
     */
    protected function applyString($field, $value) {
        if(is_null($this->segmentFields)) {
            throw new ZfExtended_Exception(__CLASS__.'::SegmentFields not initialized!');
        }
        
        //strip table name from field for mapping
        $fields = explode('.', $field);
        if(count($fields) > 1) {
            $singleField = trim(end($fields),'`');
            $table = trim(reset($fields), '`');
        }
        else {
            $singleField = trim($field, '`');
        }
        
        if(empty($this->_sortColMap[$singleField])) {
            parent::applyString($field, $value);
            return;
        }
        $field = $this->_sortColMap[$singleField];
        if(!empty($table)) {
            $field = $table.'`.`'.$field;
        }
        $this->where(' lower(`'.$field.'`) like lower(?) COLLATE utf8mb4_bin', '%'.$value.'%');
    }
}