<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */

/**
 * converts the given Filter and Sort String from ExtJS to an object structure appliable to a Zend Select Object
 * 
 * The implemented filter logic is a "or" based status filter, depending on the state displayed in the taskGrid.
 * That means, the tasks must not be ended expect one wants to filter the ended explicitly.
 * 
 * @author Marc Mittag
 */
class editor_Models_Filter_TaskSpecific extends ZfExtended_Models_Filter_ExtJs {
    const STATE_LOCKED = 'locked';
    
    const USER_STATE = 'user_state_';
    const TASK_STATE = 'task_state_';
    
    protected static $isUserAssocNeeded = false;
    
    /**
     * refactor the state filter given by client, separates task and user states
     * @param boolean $loadAllAllowed optional, if true current user is allowed to see all tasks
     */
    public function convertStates($loadAllAllowed = false) {
        //get affected filters: field = state
        foreach($this->filter as $key => $filter) {
            if(!is_object($filter) || empty($filter->field) || $filter->field !== 'state') {
                continue;
            }
            $states = is_array($filter->value) ? $filter->value : array($filter->value);
            $locked = in_array(self::STATE_LOCKED, $states);
            $isLoadAllOpen = $loadAllAllowed && in_array(self::USER_STATE.'open', $states);
            break;
        }
        
        //end if no filter values found
        if(empty($states)) {
            return;
        }
        
        //remove old state $filter provided by client
        unset($this->filter[$key]); 
        
        $orFilter = new stdClass();
        $orFilter->type = 'orExpression';
        $orFilter->value = array();
        
        $userStates = array();
        $taskStates = array();
        
        //helper to correct the given state values to filter
        $stateFill = function($key, $state, &$list) {
            if(strpos($state, $key) === 0) {
                $list[] = str_replace($key, '', $state);
            }
        };
        
        //separate the states into user and task states
        foreach($states as $state) {
            $stateFill(self::USER_STATE, $state, $userStates);
            $stateFill(self::TASK_STATE, $state, $taskStates);
        }
        
        //add the filters as separate new filter objects
        $filter = new stdClass();
        $filter->field = 'state';
        $filter->type = 'list';
        self::$isUserAssocNeeded = !empty($userStates);
        $task = $this->entity;
        
        //adds the additional locked filter
        $l = new stdClass();
        $l->field = 'locked';
        $l->value = ''; //we have to provide a value
        $l->_table = $task::TABLE_ALIAS;
        
        if($locked){
            //if the locked filter is set, we have to include them by OR
            $l->type = 'notIsNull';
            $orFilter->value[] = $l;
        }
        else {
            //if no locked filter is set, we have to exclude all locked tasks
            $l->type = 'isNull';
            $this->filter[] = $l;
        }
        
        if(!empty($taskStates)){
            $filter->value = $taskStates;
            $filter->_table = $task::TABLE_ALIAS;
            $orFilter->value[] = $filter;
        }
        
        if(self::$isUserAssocNeeded){
            $and = new stdClass();
            $and->type = 'andExpression';
            $and->value = array();
            
            $open = clone $filter;
            $open->value = array('open');
            //$open->type = 'eq';
            $open->_table = $task::TABLE_ALIAS;
            $and->value[] = $open;
            
            $f = clone $filter;
            $f->value = $userStates;
            $f->_table = $task::ASSOC_TABLE_ALIAS;
            $and->value[] = $f; //connect by AND taskOpen and userState
            
            $orFilter->value[] = $and;
        }
        
        //if the user is allowed to see all tasks, and he is filtering the open tasks
        // we have also to consider task_state_open on not associated (isNull) tasks, not only user_state_open
        if($isLoadAllOpen){
            $and = new stdClass();
            $and->type = 'andExpression';
            $and->value = array();
            
            $open = clone $filter;
            $open->value = array('open');
            //$open->type = 'eq';
            $open->_table = $task::TABLE_ALIAS;
            $and->value[] = $open;
            
            $f = new stdClass();
            $f->field = 'state';
            $f->value = '';
            $f->type = 'isNull';
            $f->_table = $task::ASSOC_TABLE_ALIAS;
            $and->value[] = $f;
            
            $orFilter->value[] = $and;
        }
        
        //add the new OR filter to the filter list
        $this->filter[] = $orFilter;
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Models_Filter_ExtJs::initFilterData()
     */
    protected function initFilterData($filter) {
        if(isset($filter->_table) && self::$isUserAssocNeeded) {
            $filter->table = $filter->_table;
            unset ($filter->_table);
        }
        parent::initFilterData($filter);
    }
    
    /**
     * returns if the currently set filter data needs a joined UserAssocTable
     * @return boolean
     */
    public function isUserAssocNeeded() {
        return self::$isUserAssocNeeded;
    }
    
    /**
     * set the isUserAssocNeeded state
     * @param boolean $isNeeded
     */
    public function setUserAssocNeeded($isNeeded = true) {
        self::$isUserAssocNeeded = $isNeeded;
    }
}