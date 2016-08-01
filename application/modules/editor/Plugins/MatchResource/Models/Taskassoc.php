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
class editor_Plugins_MatchResource_Models_Taskassoc extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Plugins_MatchResource_Models_Db_Taskassoc';
    protected $validatorInstanceClass = 'editor_Plugins_MatchResource_Models_Validator_Taskassoc'; //→ here the new validator class
    /**
     * loads one assoc entry, returns the loaded row as array
     * 
     * @param string $taskGuid
     * @param integer $tmmtId
     * @return Ambigous <multitype:, array>
     */
    public function loadByTaskGuidAndTm(string $taskGuid, $tmmtId) {
        try {
            $s = $this->db->select()
                ->where('tmmtId = ?', $tmmtId)
                ->where('taskGuid = ?', $taskGuid);
            $row = $this->db->fetchRow($s);
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        if (!$row) {
            $this->notFound(__CLASS__ . '#taskGuid + tmmtId', $taskGuid.' + '.$tmmtId);
        }
        //load implies loading one Row, so use only the first row
        $this->row = $row;
        return $this->row->toArray();
    }
    
    /**
     * loads all associated tmmt's to one taskGuid
     * @param unknown $taskGuid
     * @return Ambigous <Zend_Db_Table_Row_Abstract, NULL>
     */
    public function loadByTaskGuid($taskGuid) {
        return $this->loadRow('taskGuid = ?', $taskGuid);
    }
    
    /**
     * returns a list of all available tmmt's for one language combination
     * The language combination is determined from the task given by taskGuid
     * If a filter "checked" is set, then only the associated tmmt's to the given task are listed
     * If the "checked" filter is omitted, all available tmmt's for the language are listed, 
     *      the boolean field checked provides the info if the tmmt is associated to the task or not 
     *        
     * @param string $taskGuid
     * @return multitype:
     */
    public function loadByAssociatedTaskAndLanguage($taskGuid) {
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid((string) $taskGuid);
        
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
        ->from(array("tmmt" => "LEK_matchresource_tmmt"), array("tmmt.*","ta.id AS taskassocid"));

        if($this->filter->hasFilter('checked')) {
            //if checked filter is set, we keep the taskGuid as filter argument,
            // but remove additional checked filter and checked info 
            $this->filter->deleteFilter('checked');
            $checked = '';
        }
        else {
            $this->filter->deleteFilter('taskGuid');
            $checked = array("checked" => "IF(ta.taskGuid = '".$taskGuid."','true','false')");
        }
        
        $s->joinLeft(
                array("ta"=>"LEK_matchresource_taskassoc"),
                "ta.tmmtId = tmmt.id", $checked);
        return $this->loadFilterdCustom($s);
    }
    /**
     * Returns join between taskassoc table and task table for tmmt's id list
     * @param array $tmmtids
     */
    public function getTaskGuidsForTmmts($tmmtids){
        $s = $this->db->select()
        ->from(array("assocs" => "LEK_matchresource_taskassoc"), array("assocs.id","assocs.taskGuid","task.taskName","assocs.tmmtId"))
        ->setIntegrityCheck(false)
        ->join(array("task" => "LEK_task"),"assocs.taskGuid = task.taskGuid","")
        ->where('assocs.tmmtId in (?)', $tmmtids)
        ->group('assocs.id');
        return $this->db->fetchAll($s)->toArray();
    }
}