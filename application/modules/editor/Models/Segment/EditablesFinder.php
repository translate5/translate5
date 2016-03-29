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
 * This is the expected SQL, where the result is sorted after matchrate, 
 * and the segment to compare has a matchRate of 20 and id 923695
    SELECT count(n.id) FROM `VIEW` n,(
        SELECT `matchRate`,`id` 
        FROM `VIEW` 
        WHERE `matchRate` >= '20'  → this depends on the set sort, >= for ASC, <= for DESC and on the search direction
        AND id > '923695'          → for ID we assume always > for search next and < for search prev, independant of sort direction
        AND autoStateId not in (0,4)   → here add editable and autoState filter 
        ORDER BY `matchRate` ASC ,id ASC 
        LIMIT 1
    ) pos 
    where (n.matchRate <= pos.matchRate 
          OR (n.matchRate = pos.matchRate AND n.id < pos.id))  
    ORDER BY n.`matchRate` ASC ,n.id ASC
    
    Beide Mengen nach der watchlist filtern
    
    the matchrate and id values are depending on the current segment, from where to look
    
    For prev/next and asc/desc this results in the following where statements:
    where X_ values are the current segments values, and P_ the values of the next/prev segment
    
    INNER                               OUTER
    ASC NEXT                            ASC NEXT/PREV
    F > X_F || F = X_F && ID > X_ID     F < P_F || F = P_F && ID < P_ID
    
    DESC NEXT                           DESC NEXT/PREV
    F < X_F || F = X_F && ID > X_ID     F > P_F || F = P_F && ID > P_ID
    
    ASC PREV                            
    F < X_F || F = X_F && ID < X_ID     
    
    DESC PREV                           
    F > X_F || F = X_F && ID < X_ID     
 *
 */
class editor_Models_Segment_EditablesFinder {
    /**
     * @var editor_Models_Segment
     */
    protected $segment = null;
    
    /**
     * filter instances used for inner and outer SQL
     * @var ZfExtended_Models_Filter
     */
    protected $filterInner;
    protected $filterOuter;
    
    /**
     * sort parameters which are added as sort and filter conditions
     * @var array
     */
    protected $sortParameter;
    
    /**
     * sort parameters which are added as sort and filter conditions
     * @var array
     */
    protected $fieldsToSelect;
    
    
    /**
     * @param editor_Models_Segment $segment
     */
    public function __construct(editor_Models_Segment $segment) {
        $this->segment = $segment;
        
        $this->filterInner = clone $this->segment->getFilter();
        $this->filterInner->setDefaultTable((string) $this->segment->db);
        
        $this->filterOuter = clone $this->segment->getFilter();
        $this->filterOuter->setDefaultTable('list');
        
        // remove id field, since this is added internally
        $this->sortParameter = $this->segment->getFilter()->getSort();
        $this->fieldsToSelect = array();
        foreach($this->sortParameter as $id => $sort) {
            $this->fieldsToSelect[] = $sort->property;
            if($sort->property !== 'id'){
                unset($this->sortParameter[$id]); 
            }
        }
    }
    
    /**
     * calculcates the next/prev editable segment and return the segment position as integer and null if there is no next/prev segment
     * 
     * @param editor_Models_Segment $this
     * @param boolean $next
     * @param array $autoStateIds
     * @return NULL|integer
     */
    public function find(boolean $next, array $autoStateIds = null) {
        $outerSql = $this->getOuterSql();

        $this->prepareInnerFilter($autoStateIds);
        $innerSql = $this->getInnerSql();
        
        foreach($this->sortParameter as $sort) {
            $isAsc = strtolower($sort->direction) === 'asc';
            $prop = $this->getSortProperty($sort);

            //if we ever will have multiple sort parameters, this should work out of the box because of the loop
            $this->addSortInner($innerSql, $prop, $next, $isAsc);
            $this->addSortOuter($outerSql, $prop, $isAsc);
        }
        
        $outerSql->from(array('pos' => $innerSql), null);
        $this->filterOuter->applyToSelect($outerSql);

        $this->debug($outerSql);
        
        $stmt = $this->segment->db->getAdapter()->query($outerSql);
        $res = $stmt->fetch();
        if(empty($res)) {
            return null;
        }
        return (int) $res['cnt'];
    }
    
    /**
     * adds the where statement to the inner SELECT, the SQL differs for the following cases:
        ASC NEXT     sortField > currentSortValue || sortField = currentSortValue && idField > currentIdValue
        DESC NEXT    sortField < currentSortValue || sortField = currentSortValue && idField > currentIdValue
        ASC PREV     sortField < currentSortValue || sortField = currentSortValue && idField < currentIdValue
        DESC PREV    sortField > currentSortValue || sortField = currentSortValue && idField < currentIdValue
     * @param unknown $sql
     * @param unknown $prop
     * @param unknown $next
     * @param unknown $isAsc
     */
    protected function addSortInner(Zend_Db_Table_Select $sql, string $prop, boolean $next, boolean $isAsc) {
        $value = $this->segment->get($prop);
            
        $idComparator = $next ? '>' : '<';
        $comparator = ($isAsc xor $next) ? '<' : '>';
        //id comparator depends only on prev/next, since order for id is always ASC!
        $f = $this->segment->db.'.`'.$prop.'` ';
        $sql->where('('.$f.$comparator.' ?', $value);
        $sql->orWhere('('.$f.'= ?', $value);
        $sql->where($f.$comparator.' ? ))', $this->segment->getId());
    }
    
    /**
     * adds the where statement to the outer select, the SQL differs for the following cases: 
                ASC NEXT/PREV   sortField < innerSortValue || sortField = innerSortValue && idField < innerIdValue
                DESC NEXT/PREV  sortField > innerSortValue || sortField = innerSortValue && idField > innerIdValue
     * @param Zend_Db_Table_Select $sql
     * @param string $prop
     * @param boolean $isAsc
     */
    protected function addSortOuter(Zend_Db_Table_Select $sql, string $prop, boolean $isAsc) {
        //id comparator depends only on prev/next, since order for id is always ASC!
        $comparator = $isAsc ? '<' : '>';
        $where = 'list.`%1$s` %2$s pos.`%1$s`';
        $sql->where('('.sprintf($where, $prop, $comparator));
        $sql->orWhere('('.sprintf($where, $prop, ' = '));
        $sql->where(sprintf($where, 'id', $comparator).'))');
    }
    
    /**
     * prepares the outer SQL and returns it
     * @return Zend_Db_Table_Select
     */
    protected function getOuterSql() {
        $outerSql = $this->segment->db->select()
            ->from(array('list' => $this->segment->db), new Zend_Db_Expr('if(count(pos.id), count(list.id), null) AS cnt'));
        return $this->segment->addWatchlistJoin($outerSql, 'list');
    }
    
    /**
     * prepares the inner SQL and returns it
     * @return Zend_Db_Table_Select
     */
    protected function getInnerSql() {
        $innerSql = $this->segment->db->select()
            ->from($this->segment->db, $this->fieldsToSelect)
            ->limit(1);
        $innerSql = $this->segment->addWatchlistJoin($innerSql);
        return $this->filterInner->applyToSelect($innerSql);
    }
    
    /**
     * prepares the inner filter: adds the filterung condition for only editable and if provided the filter for specific autostates 
     * @param array $autoStateIds
     */
    protected function prepareInnerFilter(array $autoStateIds = null) {
        if(!empty($autoStateIds)) {
            $this->filterInner->addFilter((object)[
                'field' => 'autoStateId',
                'type' => 'notInList',
                'value' => $autoStateIds,
            ]);
        }
        $this->filterInner->addFilter((object)[
            'field' => 'editable',
            'value' => 1,
            'type' => 'boolean',
        ]);
    }
    
    /**
     * prepares and returns the sort property to be used
     * @param stdClass $sort
     * @return string
     */
    protected function getSortProperty(stdClass $sort) {
        //mapSort adds also a table, this is not needed here!
        $prop = $this->segment->getFilter()->mapSort($sort->property); 
        $prop = explode('.', $prop);
        return end($prop);
    }
    
    protected function debug() {
        return;
        //debug sql:
        file_put_contents('/tmp/foo.sql', $outerSql);
        //exec('sqlformat --reindent --keywords upper --identifiers lower /tmp/foo.sql', $out);
        exec('sqlformat --reindent --keywords upper /tmp/foo.sql', $out);
        error_log("\n".join("\n", $out));
    }
}