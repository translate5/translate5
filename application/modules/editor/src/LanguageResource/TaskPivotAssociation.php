<?php

namespace MittagQI\Translate5\LanguageResource;

use editor_Models_Languages;
use Zend_Db_Expr;
use ZfExtended_Factory;

/***
 * @method string getId()
 * @method void setId(int $id)
 * @method string getLanguageResourceId()
 * @method void setLanguageResourceId(int $languageResourceId)
 * @method string getTaskGuid()
 * @method void setTaskGuid(string $taskGuid)
 * @method string getAutoCreatedOnImport()
 * @method void setAutoCreatedOnImport(int $autoCreatedOnImport)
 */
class TaskPivotAssociation extends AssociationAbstract
{
    protected $dbInstanceClass = 'MittagQI\Translate5\LanguageResource\Db\TaskPivotAssociation';

    protected $validatorInstanceClass = 'MittagQI\Translate5\LanguageResource\Validator\TaskPivotAssociation';

    /***
     * Load a list of all language resources available for pivot pre-translation for given taskGuid.
     * @param string $taskGuid
     * @return array
     */
    public function loadAllAvailableForTask(string $taskGuid, \editor_Services_Manager $manager): array
    {
        /** @var \editor_Models_Task $task */
        $task = ZfExtended_Factory::get('\editor_Models_Task');
        $task->loadByTaskGuid($taskGuid);

        if ($task->isProject()) {
            //get all project tasks and get the resources for each task
            $projectGuids = array_column($task->loadProjectTasks((int) $task->getProjectId(), true), 'taskGuid');
            $result = [];
            foreach ($projectGuids as $pg) {
                $result = array_merge($result, $this->loadAllAvailableForTask($pg, $manager));
            }

            return array_filter(array_values($result));
        }

        $db = $this->db;
        $adapter = $db->getAdapter();

        $languageModel = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $languageModel editor_Models_Languages */

        if (empty($task->getSourceLang()) || empty($task->getRelaisLang())) {
            return [];
        }
        //get source and relais language fuzzy
        $sourceLangs = $languageModel->getFuzzyLanguages((int) $task->getSourceLang(), 'id', true);
        $relaisLangs = $languageModel->getFuzzyLanguages((int) $task->getRelaisLang(), 'id', true);

        if (empty($sourceLangs) || empty($relaisLangs)) {
            return [];
        }

        $this->filter->addTableForField('taskGuid', 'ta');
        $s = $db->select()
            ->setIntegrityCheck(false)
            ->from(
                [
                    "languageResource" => "LEK_languageresources",
                ],
                [
                    new Zend_Db_Expr($adapter->quote($task->getTaskName()) . ' as taskName'),
                    new Zend_Db_Expr($adapter->quote($taskGuid) . ' as taskGuid'),
                    "languageResource.id AS languageResourceId", "languageResource.langResUuid", "languageResource.name",
                    "languageResource.color", "languageResource.resourceId", "languageResource.serviceType",
                    "languageResource.serviceName", "languageResource.specificData", "languageResource.timestamp",
                    "languageResource.resourceType", "languageResource.writeSource",
                    "ta.id AS associd"]
            )
            ->join([
                "la" => "LEK_languageresources_languages",
            ], 'languageResource.id=la.languageResourceId', ['la.sourceLang AS sourceLang', 'la.targetlang AS targetLang'])
            ->where('la.sourceLang IN(?)', $sourceLangs)
            ->where('la.targetLang IN(?)', $relaisLangs);
        $s->joinLeft(
            [
                "ta" => "LEK_languageresources_taskpivotassoc",
            ],
            $adapter->quoteInto('ta.languageResourceId = languageResource.id AND ta.taskGuid = ?', $taskGuid),
            [
                //checked is true when an assoc entry was found
                "checked" => $adapter->quoteInto('IF(ta.taskGuid = ?,\'true\',\'false\')', $taskGuid),
            ]
        );

        // filter only for task customer resources
        $s->join([
            "cu" => "LEK_languageresources_customerassoc",
        ], 'languageResource.id=cu.languageResourceId', ['cu.customerId AS customerId'])
            ->where('cu.customerId=?', $task->getCustomerId());
        $s->group('languageResource.id');

        $result = $this->loadFilterdCustom($s);

        foreach ($result as &$row) {
            $row['serviceName'] = $manager->getUiNameByType($row['serviceType']);
        }

        return $result;
    }
}
