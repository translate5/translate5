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
 * Set the task specific filter when special filter is active
 */
class editor_Models_Filter_TaskSpecific extends ZfExtended_Models_Filter_ExtJs6 {

    /**
     * sets several field mappings (field name in frontend differs from that in backend)
     * should be called after setDefaultFilter
     * @param array|NULL $sortColMap
     * @param array|NULL $filterTypeMap
     */
    function setMappings($sortColMap = null, $filterTypeMap = null) {
        parent::setMappings($sortColMap,$filterTypeMap);
        
        //if the task state filter is set, set the filter table
        $taskState=null;
        if($this->hasFilter('state',$taskState)){
            
            $db=$this->entity->db;
            $taskTable=$db->info($db::NAME);
            $taskStateValues=$taskState->value;
            
            //set the task table
            $taskState->table=$taskTable;
            
            //is state locked active
            $locked=!empty($taskStateValues) && in_array('locked', $taskStateValues);
            
            //if locked filter state is active, add the or filter
            if($locked){
                
                //remove the user task state filter
                $this->deleteFilter('state');
                
                $orFilter = new stdClass();
                $orFilter->type = 'orExpression';
                $orFilter->field = '';
                $orFilter->value = [];
                
                //add the locked filter
                $filter = new stdClass();
                $filter->field = 'locked';
                $filter->type = 'notIsNull';
                $filter->value ='';
                $filter->table=$taskTable;
                $orFilter->value[] =$filter;
                
                //remove state locked from the state values
                if (($key = array_search('locked',$taskStateValues)) !== false) {
                    unset($taskStateValues[$key]);
                }
                
                if(!empty($taskStateValues)){
                    //add all other state filter values
                    $filter = new stdClass();
                    $filter->field = 'state';
                    $filter->type = 'list';
                    $filter->value = $taskStateValues;
                    $filter->table =$taskTable;
                    $orFilter->value[] = $filter;
                }
                
                $this->addFilter($orFilter);
            }
        }
        
        //check if one of the set filters is userState filter
        $userStateFilter=null;
        if(!$this->hasFilter('userState',$userStateFilter)){
            return;
        }
        //if the user filter is used, apply the current user as additional TaskAssocFilter
        $user = new Zend_Session_Namespace('user');
        $filter = new stdClass();
        $filter->field = 'userGuid';
        $filter->type = 'string';
        $filter->comparison = 'eq';
        $filter->value = $user->data->userGuid;
        $filter->table = $userStateFilter->type->getTable();
        $this->addFilter($filter);
    }
}