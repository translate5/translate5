<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/* * #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */

/**
 * Segment Entity Objekt
 */
class editor_Models_Segment_EditablesFinder {
    const LIMIT_INCREASE = 400;
    
    /**
     * @var Zend_Db_Table_Select
     */
    protected $basicSelect;
    
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;
    
    /**
     * is true if we have also to look for the first editable, false if not
     * @var boolean
     */
    protected $findFirst = true;
    
    /**
     * suffix "Filtered" means filtered by autostate 
     * @var array
     */
    protected $foundEditables = array(
            'firstRow' => null,
            'prevRow' => null,
            'nextRow' => null,
            'firstRowFiltered' => null,
            'prevRowFiltered' => null,
            'nextRowFiltered' => null,
    );
    
    /**
     * the original requested row offset
     * @var integer
     */
    protected $offset;
    
    /**
     * the original requested row limit
     * @var integer
     */
    protected $limit;
    
    /**
     * @param Zend_Db_Adapter_Abstract $db
     * @param Zend_Db_Table_Select $s
     */
    public function __construct(Zend_Db_Adapter_Abstract $db, Zend_Db_Table_Select $s) {
        $this->db = $db;
        $this->basicSelect = $s;
    }
    
    /**
     * calculcates the next/prev editable segments and return the segment informations as array
     * @param integer $offset
     * @param integer $limit
     * @param array $autoStateIds
     * @param integer $total
     * @return array
     */
    public function find(integer $offset, integer $limit, array $autoStateIds, integer $total) {
        //find first only if initial offset was 0
        $this->findFirst = $offset === 0; 
        $this->offset = $offset;
        $this->limit = $limit;
        
        //increase our affected segments window
        $limit = (int) ($limit + 2 * self::LIMIT_INCREASE); //increase the limit about 400 segments in every direction
        $offsetCurrent = (int) max($offset - self::LIMIT_INCREASE, 0); //decrease the offset to search in the 400 segments before
        
        //first fetch returns -400 segments before current page and +400 after current page
        $firstFetch = $this->fetchEditables($offsetCurrent, $limit);
        $this->processEditables($firstFetch);
        $this->processEditables($firstFetch, $autoStateIds);
        
        //all further fetch calls, has only to be done, if there are remaining segments
        
        //initial prevOffset calculation for == 0 exclusion
        $prevOffset = $offsetCurrent;
        while($prevOffset > 0 && $this->hasMissingPrev()) {
            $prevOffset = max($prevOffset - $limit, 0);
            $fetch = $this->fetchEditables($prevOffset, $limit);
            $this->processEditables($fetch);
            $this->processEditables($fetch, $autoStateIds);
        }
        
        //setting current as nextOffset, current + limit > total are already fetched, so no entry in loop is needed
        $nextOffset = $offsetCurrent;
        while($this->hasMissingNext() && (($nextOffset + $limit) < $total)) {
            $nextOffset = $nextOffset + $limit;
            $fetch = $this->fetchEditables($nextOffset, $limit);
            $this->processEditables($fetch);
            $this->processEditables($fetch, $autoStateIds);
        }
        return $this->foundEditables;
    }
    /**
     * each fetch has the same DB cost (performance, duration) as a the same plain select
     * returns 
     * @param unknown $offset
     * @param unknown $limit
     */
    protected function fetchEditables($offset, $limit) {
        //since we can't add our rowidx variable with Zend methods, 
        //  we produce our basic select and add the rowidx with string operations then
        //  should produce something like this:
        //    select rowidx, id, autoStateId from (
        //      select @rank := @rank + 1 rowidx, id, editable 
        //      from (select @rank:= 0) ranker, LEK_segment_view_63a4c234c7dae7ec9bbf408f018f8dfe
        //      where matchrate > 0 order by autoStateId limit 0,1000 ) segments 
        //    where editable = 1 order by rowidx'; 
        
        $assembledSql = $this->basicSelect->assemble();
        
        //add rowidx field
        $assembledSql = str_ireplace('^SELECT', 'SELECT @rowidx := @rowidx + 1 rowidx, ', '^'.$assembledSql);
        
        //add rowidx init table
        $assembledSql = str_ireplace(' from ', ' FROM (select @rowidx:= 0) idxer, ', $assembledSql);
        
        //add surrounding select to get only the editable ones and the rowidx
        $assembledSql = 'select rowidx, id, autoStateId from ('.$assembledSql.' limit ';
        $assembledSql .= $offset.','.$limit.') segments where editable = 1 order by rowidx';
        
        return $this->db->query($assembledSql)->fetchAll(Zend_Db::FETCH_OBJ);
    }
    /**
     * 
     * @param array $rows
     * @param array $autoStatesToIgnore
     */
    protected function processEditables(array $rows, array $autoStatesToIgnore = array()) {
        $useAutoStateFilter = !empty($autoStatesToIgnore);
        $suffix = $useAutoStateFilter ? 'Filtered' : '';
        foreach($rows as $row) {
            if($useAutoStateFilter && in_array($row->autoStateId, $autoStatesToIgnore)){
                continue;
            }
            //don't overwrite already found editables
            $prevNotFound = $this->foundEditables['prevRow'.$suffix] === null;
            if($row->rowidx < $this->offset && $prevNotFound){
                $this->foundEditables['prevRow'.$suffix] = $row;
            }
            $firstNotFound = $this->foundEditables['firstRow'.$suffix] === null;
            if($this->findFirst && $row->rowidx >= $this->offset && $firstNotFound){
                $this->foundEditables['firstRow'.$suffix] = $row;
            }
            $nextNotFound = $this->foundEditables['nextRow'.$suffix] === null;
            if($row->rowidx > ($this->offset + $this->limit) && $nextNotFound){
                $this->foundEditables['nextRow'.$suffix] = $row;
                return;
            }
            //if everything found, then return out of loop
            if(!($prevNotFound || ($this->findFirst && $firstNotFound) || $nextNotFound)) {
                return;
            }
        }
    }
    /**
     * returns true if there are missing editables in "previous" direction
     */
    protected function hasMissingPrev() {
        return $this->foundEditables['prevRow'] === null || $this->foundEditables['prevRowFiltered'] === null;
    }
    /**
     * returns true if there are missing editables in "next" direction
     */
    protected function hasMissingNext() {
        return $this->foundEditables['nextRow'] === null || $this->foundEditables['nextRowFiltered'] === null;
    }
}