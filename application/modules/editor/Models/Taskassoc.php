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
 * Tmmt TaskAssoc Entity Object
 * 
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method integer getTmmtId() getTmmtId()
 * @method void setTmmtId() setTmmtId(integer $tmmtid)
 * @method string getTaskGuid() getTaskGuid()
 * @method void setTaskGuid() setTaskGuid(string $taskGuid)
 * @method boolean getSegmentsUpdateable() getSegmentsUpdateable()
 * @method void setSegmentsUpdateable() setSegmentsUpdateable(boolean $updateable)
 */
class editor_Models_Taskassoc extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Taskassoc';
    protected $validatorInstanceClass = 'editor_Models_Validator_Taskassoc'; //→ here the new validator class
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
        
        //this ensures that taskGuid does not contain evil content from userland
        $taskGuid = $task->getTaskGuid();
        
        $db = $this->db;
        $adapter = $db->getAdapter();
        
        $s = $db->select()
        ->setIntegrityCheck(false)
        ->from(array("tmmt" => "LEK_languageresources_tmmt"), array("tmmt.*","ta.id AS taskassocid", "ta.segmentsUpdateable"))
        ->join(array("la"=>"LEK_languageresources_languages"), 'tmmt.id=la.languageResourceId',array('la.sourceLang AS sourceLang','la.targetlang AS targetLang'))
        ->where('la.sourceLang=?',$task->getSourceLang())
        ->where('la.targetLang=?',$task->getTargetLang());

        //check filter is set true when editor needs a list of all used TMs/MTs
        if($this->filter->hasFilter('checked')) {
            //if checked filter is set, we keep the taskGuid as filter argument,
            // but remove additional checked filter and checked info 
            $this->filter->deleteFilter('checked');
            $checkColumns = '';
        }
        else {
            $this->filter->deleteFilter('taskGuid');
            $checkColumns = [
                //checked is true when an assoc entry was found
                "checked" => $adapter->quoteInto('IF(ta.taskGuid = ?,\'true\',\'false\')', $taskGuid),
                //segmentsUpdateable is true when an assoc entry was found and the real value was true too
                "segmentsUpdateable" => $adapter->quoteInto('IF(ta.taskGuid = ?,segmentsUpdateable,0)', $taskGuid),
            ];
        }
        
        $on = $adapter->quoteInto('ta.tmmtId = tmmt.id AND ta.taskGuid = ?', $taskGuid);
        $s->joinLeft(["ta"=>"LEK_languageresources_taskassoc"], $on, $checkColumns);
        
        return $this->loadFilterdCustom($s);
    }
    /**
     * Returns join between taskassoc table and task table for tmmt's id list
     * @param array $tmmtids
     */
    public function getTaskInfoForTmmts($tmmtids){
        if(empty($tmmtids)) {
            return [];
        }
        $s = $this->db->select()
        ->from(array("assocs" => "LEK_languageresources_taskassoc"), array("assocs.id","assocs.taskGuid","task.taskName","task.state","task.lockingUser","task.taskNr","assocs.tmmtId"))
        ->setIntegrityCheck(false)
        ->join(array("task" => "LEK_task"),"assocs.taskGuid = task.taskGuid","")
        ->where('assocs.tmmtId in (?)', $tmmtids)
        ->group('assocs.id');
        return $this->db->fetchAll($s)->toArray();
    }
    
    /***
     * Get all available tmms for the language combination as in the provided task.
     * @param string $taskGuid
     * @return array
     */
    public function getAssocTasksWithResources($taskGuid){
        $serviceManager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $serviceManager editor_Services_Manager */
        
        $resources = [];
        
        $getResource = function(string $serviceType, string $id) use ($resources, $serviceManager) {
            if (!empty($resources[$id])) {
                return $resources[$id];
            }
            return $resources[$id] = $serviceManager->getResourceById($serviceType, $id);
        };
        
        $result = $this->loadByAssociatedTaskAndLanguage($taskGuid);
        
        foreach($result as &$tmmt) {
            $resource = $getResource($tmmt['serviceType'], $tmmt['resourceId']);
            if(!empty($resource)) {
                $tmmt = array_merge($tmmt, $resource->getMetaData());
            }
        }
        
        return $result;
    }
    
    /***
     * Get all assocs by $taskGuids
     * If no $taskGuids are provided, all assoc will be loaded
     * @param array $taskGuids
     * @return array
     */
    public function loadByTaskGuids($taskGuids=array()){
        $s=$this->db->select();
        if(!empty($taskGuids)){
            $s->where('taskGuid IN(?)',$taskGuids);
        }
        return $this->db->fetchAll($s)->toArray();
    }
    
    /***
     * Get all tasks that are assigned to the provided tmmtId.
     * @param integer $tmmtId
     * @return array
     */
    public function getAssocTasksByTmmtId($tmmtId){
        $s = $this->db->select()
        ->where('tmmtId=?',$tmmtId);
        return $this->db->fetchAll($s)->toArray();
    }
}