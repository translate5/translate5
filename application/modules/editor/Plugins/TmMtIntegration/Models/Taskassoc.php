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
class editor_Plugins_TmMtIntegration_Models_Taskassoc extends ZfExtended_Models_Entity_Abstract {
	protected $dbInstanceClass = 'editor_Plugins_TmMtIntegration_Models_Db_Taskassoc';
	protected $validatorInstanceClass = 'editor_Plugins_TmMtIntegration_Models_Validator_Taskassoc'; //→ here the new validator class

	public function loadByTaskGuid($taskGuid) {
		return $this->loadRow('taskGuid = ?', $taskGuid);
	}
	
	public function loadByAssociatedTaskAndLanguage($taskGuid) {
        
	    $task = ZfExtended_Factory::get('editor_Models_Task');
	    /* @var $task editor_Models_Task */
	    $task->loadByTaskGuid((string) $taskGuid);
	    
	    $this->filter->deleteFilter("taskGuid");
	    $this->filter->addFilter((object)[
	            'field' => 'sourceLang',
	            'type' =>  'numeric',
	            'comparison' => 'eq',
	            'table' => 'tmmt',//only needed for join
	            'value' => $task->getSourceLang(),
	    ]);
	    $this->filter->addFilter((object)[
	            'field' => 'targetLang',
	            'table' => 'tmmt',
	            'comparison' => 'eq',
	            'type' =>  'numeric',
	            'value' => $task->getTargetLang(),
	    ]);
	    
	    $db = $this->db;
	    $s = $db->select()
	    ->setIntegrityCheck(false)
	    ->from(array("tmmt" => "LEK_tmmtintegration_tmmt"), array("tmmt.*","ta.id AS taskassocid"))
	    ->joinLeft(
	            array("ta"=>"LEK_tmmtintegration_taskassoc"),
	            "ta.tmmtId = tmmt.id",
	            array("checked" => "IF(ta.taskGuid = '".$taskGuid."','true','false')"));
	    return $this->loadFilterdCustom($s);
	}
}