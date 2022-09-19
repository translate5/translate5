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

namespace MittagQI\Translate5\LanguageResource;

use Zend_Db_Expr;
use Zend_Db_Table_Row_Abstract;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Abstract;

/**
 * LanguageResource TaskAssoc Entity Object
 *
 * @method integer getId() getId()
 * @method void setId() setId(int $id)
 * @method integer getLanguageResourceId() getLanguageResourceId()
 * @method void setLanguageResourceId() setLanguageResourceId(int $languageResourceid)
 * @method string getTaskGuid() getTaskGuid()
 * @method void setTaskGuid() setTaskGuid(string $taskGuid)
 * @method boolean getSegmentsUpdateable() getSegmentsUpdateable()
 * @method void setSegmentsUpdateable() setSegmentsUpdateable(bool $updateable)
 * @method integer getAutoCreatedOnImport() getAutoCreatedOnImport()
 * @method void setAutoCreatedOnImport() setAutoCreatedOnImport(int $autoCreatedOnImport)
 */
class TaskAssociation extends ZfExtended_Models_Entity_Abstract {

    protected $dbInstanceClass = 'MittagQI\Translate5\LanguageResource\Db\TaskAssociation';
    protected $validatorInstanceClass = 'MittagQI\Translate5\LanguageResource\Validator\TaskAssociation'; //→ here the new validator class
    /**
     * loads one assoc entry, returns the loaded row as array
     *
     * @param string $taskGuid
     * @param int $languageResourceId
     * @return array
     */
    public function loadByTaskGuidAndTm(string $taskGuid, $languageResourceId) {
        try {
            $s = $this->db->select()
                ->where('languageResourceId = ?', $languageResourceId)
                ->where('taskGuid = ?', $taskGuid);
            $row = $this->db->fetchRow($s);
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        if (!$row) {
            $this->notFound(__CLASS__ . '#taskGuid + languageResourceId', $taskGuid.' + '.$languageResourceId);
        }
        //load implies loading one Row, so use only the first row
        $this->row = $row;
        return $this->row->toArray();
    }
    
    /**
     * loads all associated languageResource's to one taskGuid
     * @param string $taskGuid
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function loadByTaskGuid($taskGuid) {
        return $this->loadRow('taskGuid = ?', $taskGuid);
    }
    
    /**
     * returns a list of all available languageResource's for one language combination
     * The language combination is determined from the task given by taskGuid
     * If the requested task is a project, all available resources for the project tasks will be returned.
     * If a filter "checked" is set, then only the associated languageResource's to the given task are listed
     * If the "checked" filter is omitted, all available languageResource's for the language are listed,
     *      the boolean field checked provides the info if the languageResource is associated to the task or not
     *
     * ("The function is meant to be called only by rest call"!)
     *
     * @param string $taskGuid
     * @return mixed
     */
    public function loadByAssociatedTaskAndLanguage(string $taskGuid): mixed
    {
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid((string) $taskGuid);
        
        if($task->isProject()){
            //get all project tasks and get the resources for each task
            $projectGuids=array_column($task->loadProjectTasks($task->getProjectId(),true), 'taskGuid');
            $result=[];
            foreach ($projectGuids as $pg){
                $result=array_merge($result,$this->loadByAssociatedTaskAndLanguage($pg));
            }
            return array_filter(array_values($result));
        }
        //this ensures that taskGuid does not contain evil content from userland
        $taskGuid = $task->getTaskGuid();
        
        $db = $this->db;
        $adapter = $db->getAdapter();
        
        $languageModel=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $languageModel editor_Models_Languages */
        
        //get source and target language fuzzies
        $sourceLangs=$languageModel->getFuzzyLanguages($task->getSourceLang(),'id',true);
        $targetLangs=$languageModel->getFuzzyLanguages($task->getTargetLang(),'id',true);
        
        //get all available services
        $services=ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $services editor_Services_Manager */
        $allservices=$services->getAll();
        
        $this->filter->addTableForField('taskGuid', 'ta');
        $s = $db->select()
        ->setIntegrityCheck(false)
        ->from(array("languageResource" => "LEK_languageresources"),
            array(
                new Zend_Db_Expr($adapter->quote($task->getTaskName()).' as taskName'),
                new Zend_Db_Expr($adapter->quote($taskGuid).' as taskGuid'),
                "languageResource.id AS languageResourceId","languageResource.langResUuid", "languageResource.name",
                "languageResource.color","languageResource.resourceId", "languageResource.serviceType",
                "languageResource.serviceName","languageResource.specificData", "languageResource.timestamp",
                "languageResource.resourceType", "languageResource.writeSource",
                "ta.id AS taskassocid",
                "ta.segmentsUpdateable"))
        ->join(array("la"=>"LEK_languageresources_languages"), 'languageResource.id=la.languageResourceId',array('la.sourceLang AS sourceLang','la.targetlang AS targetLang'))
        ->where('la.sourceLang IN(?)',$sourceLangs)
        ->where('la.targetLang IN(?)',$targetLangs)
        ->where('languageResource.serviceType IN(?)',$allservices);
        
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
        
        $on = $adapter->quoteInto('ta.languageResourceId = languageResource.id AND ta.taskGuid = ?', $taskGuid);
        $s->joinLeft(["ta"=>"LEK_languageresources_taskassoc"], $on, $checkColumns);
        
        // Only match resources can be associated to a task, that are associated to the same client as the task is.
        $s->join(array("cu"=>"LEK_languageresources_customerassoc"), 'languageResource.id=cu.languageResourceId',array('cu.customerId AS customerId'))
        ->where('cu.customerId=?',$task->getCustomerId());
        
        $s->group('languageResource.id');
        return $this->loadFilterdCustom($s);
    }
    
    /***
     * Load the associated language resources to a task by serviceName
     * @param string $taskGuid
     * @param string $serviceName
     * @param array $ignoreAssocs: ignore languageresources task assocs
     */
    public function loadAssocByServiceName($taskGuid,$serviceName,$ignoreAssocs=array()){
        $s = $this->db->select()
        ->from(array("assocs" => "LEK_languageresources_taskassoc"), array("assocs.*"))
        ->setIntegrityCheck(false)
        ->join(array("lr" => "LEK_languageresources"),"assocs.languageResourceId = lr.id","")
        ->where('assocs.taskGuid=?', $taskGuid)
        ->where('lr.serviceName=?', $serviceName);
        if(!empty($ignoreAssocs)){
            $s->where('assocs.id NOT IN(?)', $ignoreAssocs);
        }
        return $this->db->fetchAll($s)->toArray();
    }
    
    /**
     * Returns join between taskassoc table and task table for languageResource's id list
     * @param array $languageResourceids
     */
    public function getTaskInfoForLanguageResources($languageResourceids){
        if(empty($languageResourceids)) {
            return [];
        }
        $s = $this->db->select()
        ->from(['assocs' => 'LEK_languageresources_taskassoc'], ['assocs.id','assocs.taskGuid','task.id as taskId', 'task.projectId', 'task.taskName','task.state','task.lockingUser','task.taskNr','assocs.languageResourceId'])
        ->setIntegrityCheck(false)
        ->join(['task' => 'LEK_task'],'assocs.taskGuid = task.taskGuid', '')
        ->where('assocs.languageResourceId in (?)', $languageResourceids)
        ->group('assocs.id');
        return $this->db->fetchAll($s)->toArray();
    }
    
    /***
     * Get all available tmms for the language combination as in the provided task.
     * (Uses loadByAssociatedTaskAndLanguage() which is meant to be called only by rest call!)
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
        
        foreach($result as &$languageresource) {
            $resource =$getResource($languageresource['serviceType'], $languageresource['resourceId']);
            if(!empty($resource)) {
                $languageresource = array_merge($languageresource, $resource->getMetaData());
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
     * Get all tasks that are assigned to the provided languageResourceId.
     * @param int $languageResourceId
     * @return array
     */
    public function getAssocTasksByLanguageResourceId($languageResourceId){
        $s = $this->db->select()
        ->where('languageResourceId=?',$languageResourceId);
        return $this->db->fetchAll($s)->toArray();
    }

    /***
     * Check if given resource is assigned to a task
     * @param int $resourceId
     * @param string $taskGuid
     * @return bool
     */
    public function isAssigned(int $resourceId, string $taskGuid): bool
    {
        $s = $this->db->select()
            ->where('taskGuid = ?',$taskGuid)
            ->where('languageResourceId = ?',$resourceId);
        return empty($this->db->getAdapter()->fetchAll($s)) === false;
    }
}