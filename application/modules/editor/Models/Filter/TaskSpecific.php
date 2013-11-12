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
 * @author Marc Mittag
 */
class editor_Models_Filter_TaskSpecific extends ZfExtended_Models_Filter_ExtJs {
    const STATE_LOCKED = 'locked';
    
    const USER_STATE = 'user_state_';
    const TASK_STATE = 'task_state_';
    
    protected $isUserAssocNeeded = false;
    
    protected function init() {
        $this->separateStates();
        parent::init();
    }
    
    /**
     * refactor the state filter given by client, separates task and user states
     */
    protected function separateStates() {
        //get affected filters: field = state
        foreach($this->filter as $key => $filter) {
            if(!is_object($filter) || empty($filter->field) || $filter->field !== 'state') {
                continue;
            }
            $states = is_array($filter->value) ? $filter->value : array($filter->value);
            if(in_array(self::STATE_LOCKED, $states)){
                $locked = new stdClass();
                $locked->field = 'locked';
                $locked->type = 'notIsNull';
                $locked->value = ''; //we have to provide a value
            }
            break;
        }
        
        //end if no filter values found
        if(empty($states)) {
            return;
        }
        
        //remove old state $filter provided by client
        unset($this->filter[$key]); 
        
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
        $this->isUserAssocNeeded = !empty($userStates);
        $task = $this->entity;
        
        //adds the additional locked filter
        if(!empty($locked)){
            $filter->_table = $task::TABLE_ALIAS;
            $this->filter[] = $locked;
        }
        
        if(empty($taskStates)){
            //if a user State filter is set, with should to consider only open tasks:
            if($this->isUserAssocNeeded) {
                $f = clone $filter;
                $f->value = array('open');
                $f->_table = $task::TABLE_ALIAS;
                $this->filter[] = $f;
            }
        } else {
            $filter->value = $taskStates;
            $filter->_table = $task::TABLE_ALIAS;
            $this->filter[] = $filter;
        }
        if($this->isUserAssocNeeded){
            $f = clone $filter;
            $f->value = $userStates;
            $f->_table = $task::ASSOC_TABLE_ALIAS;
            $this->filter[] = $f;
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Models_Filter_ExtJs::initFilterData()
     */
    public function initFilterData($filter) {
        if(isset($filter->_table) && $this->isUserAssocNeeded) {
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
        return $this->isUserAssocNeeded;
    }
    
    /**
     * set the isUserAssocNeeded state
     * @param boolean $isNeeded
     */
    public function setUserAssocNeeded($isNeeded = true) {
        $this->isUserAssocNeeded = $isNeeded;
    }
}