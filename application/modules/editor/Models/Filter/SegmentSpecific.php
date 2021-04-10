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
     * @var array
     */
    protected $segmentIdFilter = null;
    /**
     * used to filter for qualities
     * @var array
     */
    protected $qmFilter = null;
    
    /**
     * sets the fields which should be filtered lowercase
     * @param array $fields
     */
    public function setSegmentFields(array $fields) {
        $this->segmentFields = $fields;
    }
    /**
     * sets the quality filter. This is a "OR" filter that is handled seperately from the main filtering
     * @param array $segmentIds
     * @param array $qmIds
     */
    public function setQualityFilter(array $segmentIds, array $qmIds) {
        $this->segmentIdFilter = $segmentIds;
        $this->qmFilter = $qmIds;
    }
    /**
     * Applies the additional qualities filter
     * {@inheritDoc}
     * @see ZfExtended_Models_Filter::applyFurtherFilters()
     */
    public function applyToSelect(Zend_Db_Select $select, $applySort = true) {
        
        parent::applyToSelect($select, $applySort);
        
        $colPrefix = (empty($this->defaultTable)) ? '' : '`'.$this->defaultTable.'`.';
        $adapter = $this->entity->db->getAdapter();
        $conditions = [];
        if($this->qmFilter != null && count($this->qmFilter) > 0){
            foreach($this->qmFilter as $qmId){
                $conditions[] = $adapter->quoteInto($colPrefix.'qmId = ?', $qmId);
                $conditions[] = $adapter->quoteInto($colPrefix.'qmId LIKE ?', '%;'.$qmId);
                $conditions[] = $adapter->quoteInto($colPrefix.'qmId LIKE ?', $qmId.';%');
                $conditions[] = $adapter->quoteInto($colPrefix.'qmId LIKE ?', '%;'.$qmId.';%');
            }
        }
        if($this->segmentIdFilter != null && count($this->segmentIdFilter) > 0){
            $conditions[] = (count($this->segmentIdFilter) == 1) ?
                $adapter->quoteInto($colPrefix.'id = ?', $this->segmentIdFilter[0], 'INTEGER')
                : $adapter->quoteInto($colPrefix.'id IN (?)', $this->segmentIdFilter, 'INTEGER');
        }
        if(count($conditions) > 0){            
            $this->select->where(implode(' OR ', $conditions));
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