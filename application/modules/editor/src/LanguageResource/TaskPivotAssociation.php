<?php

namespace MittagQI\Translate5\LanguageResource;

use editor_Models_Languages;
use Zend_Db_Expr;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Abstract;

/***
 * @method integer getId() getId()
 * @method void setId() setId(int $id)
 * @method integer getLanguageResourceId() getLanguageResourceId()
 * @method void setLanguageResourceId() setLanguageResourceId(int $languageResourceid)
 * @method string getTaskGuid() getTaskGuid()
 * @method void setTaskGuid() setTaskGuid(string $taskGuid)
 */
class TaskPivotAssociation extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'MittagQI\Translate5\LanguageResource\Db\TaskPivotAssociation';
    protected $validatorInstanceClass = 'MittagQI\Translate5\LanguageResource\Validator\TaskPivotAssociation';

    /***
     * Load a list of all language resources available for pivot pre-translation for given taskGuid.
     * @param string $taskGuid
     * @return array
     */
    public function loadAllAvailableForTask(string $taskGuid): array
    {

        /** @var \editor_Models_Task $task */
        $task = ZfExtended_Factory::get('\editor_Models_Task');
        $task->loadByTaskGuid($taskGuid);

        if($task->isProject()){
            //get all project tasks and get the resources for each task
            $projectGuids=array_column($task->loadProjectTasks($task->getProjectId(),true), 'taskGuid');
            $result=[];
            foreach ($projectGuids as $pg){
                $result=array_merge($result,$this->loadAllAvailableForTask($pg));
            }
            return array_filter(array_values($result));
        }

        $db = $this->db;
        $adapter = $db->getAdapter();

        $languageModel=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $languageModel editor_Models_Languages */

        if(empty($task->getSourceLang()) || empty($task->getRelaisLang())){
            return [];
        }
        //get source and relais language fuzzy
        $sourceLangs=$languageModel->getFuzzyLanguages($task->getSourceLang(),'id',true);
        $relaisLangs=$languageModel->getFuzzyLanguages($task->getRelaisLang(),'id',true);

        if(empty($sourceLangs) || empty($relaisLangs)){
            return [];
        }

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
                    "ta.id AS associd"))
            ->join(array("la"=>"LEK_languageresources_languages"), 'languageResource.id=la.languageResourceId',array('la.sourceLang AS sourceLang','la.targetlang AS targetLang'))
            ->where('la.sourceLang IN(?)',$sourceLangs)
            ->where('la.targetLang IN(?)',$relaisLangs);
        $s->joinLeft(["ta"=>"LEK_languageresources_taskpivotassoc"],
            $adapter->quoteInto('ta.languageResourceId = languageResource.id AND ta.taskGuid = ?', $taskGuid),
            [
            //checked is true when an assoc entry was found
            "checked" => $adapter->quoteInto('IF(ta.taskGuid = ?,\'true\',\'false\')', $taskGuid)
            ]
        );

        // filter only for task customer resources
        $s->join(array("cu"=>"LEK_languageresources_customerassoc"), 'languageResource.id=cu.languageResourceId',array('cu.customerId AS customerId'))
            ->where('cu.customerId=?',$task->getCustomerId());
        $s->group('languageResource.id');
        return $this->loadFilterdCustom($s);
    }

    /***
     * @param string $taskGuid
     * @return array|null
     */
    public function loadTaskAssociated(string $taskGuid): ?array
    {
        $s = $this->db->select()
            ->where('taskGuid = ?',$taskGuid);
        return $this->db->getAdapter()->fetchAll($s);
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

    /***
     * Delete all associations for given taskGuid
     *
     * @param string $taskGuid
     * @return bool
     */
    public function deleteAllForTask(string $taskGuid): bool
    {
        return $this->db->delete(['taskGuid = ?' => $taskGuid]) > 0;
    }
}