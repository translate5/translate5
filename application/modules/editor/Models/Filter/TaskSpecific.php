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
        $filter->table=$userStateFilter->type->getSearchTable();
        $this->addFilter($filter);
    }
}