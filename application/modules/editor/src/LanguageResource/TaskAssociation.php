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

use editor_Models_LanguageResources_CustomerAssoc;
use editor_Models_Languages as Languages;
use editor_Models_Segment_MatchRateType;
use editor_Models_Task as Task;
use editor_Services_Manager;
use ReflectionException;
use Zend_Cache_Exception;
use Zend_Db_Expr;
use Zend_Db_Table_Row_Abstract;
use ZfExtended_Factory as Factory;
use ZfExtended_Models_Entity_NotFoundException;

/**
 * LanguageResource TaskAssoc Entity Object
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method int getLanguageResourceId()
 * @method void setLanguageResourceId(int $languageResourceid)
 * @method string getTaskGuid()
 * @method void setTaskGuid(string $taskGuid)
 * @method bool getSegmentsUpdateable()
 * @method void setSegmentsUpdateable(bool $updateable)
 * @method int getAutoCreatedOnImport()
 * @method void setAutoCreatedOnImport(int $autoCreatedOnImport)
 * @method int getPenaltyGeneral()
 * @method void setPenaltyGeneral(int $penaltyGeneral)
 * @method int getPenaltySublang()
 * @method void setPenaltySublang(int $penaltySublang)
 */
class TaskAssociation extends AssociationAbstract
{
    protected $dbInstanceClass = 'MittagQI\Translate5\LanguageResource\Db\TaskAssociation';

    protected $validatorInstanceClass = 'MittagQI\Translate5\LanguageResource\Validator\TaskAssociation'; //→ here the new validator class

    /**
     * loads one assoc entry, returns the loaded row as array
     *
     * @param int $languageResourceId
     * @return array
     */
    public function loadByTaskGuidAndTm(string $taskGuid, $languageResourceId)
    {
        try {
            $s = $this->db->select()
                ->where('languageResourceId = ?', $languageResourceId)
                ->where('taskGuid = ?', $taskGuid);
            $row = $this->db->fetchRow($s);
        } catch (\Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        if (! $row) {
            $this->notFound(__CLASS__ . '#taskGuid + languageResourceId', $taskGuid . ' + ' . $languageResourceId);
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
    public function loadByTaskGuid($taskGuid)
    {
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
     * @throws ZfExtended_Models_Entity_NotFoundException|ReflectionException|Zend_Cache_Exception
     */
    public function loadByAssociatedTaskAndLanguage(string $taskGuid): mixed
    {
        $task = Factory::get(Task::class);
        $task->loadByTaskGuid($taskGuid);

        if ($task->isProject()) {
            //get all project tasks and get the resources for each task
            $taskGuids = array_column($task->loadProjectTasks((int) $task->getProjectId(), true), 'taskGuid');
            $result = [];
            foreach ($taskGuids as $tg) {
                $result = array_merge($result, $this->loadByAssociatedTaskAndLanguage($tg));
            }

            return array_filter(array_values($result));
        }
        //this ensures that taskGuid does not contain evil content from userland
        $taskGuid = $task->getTaskGuid();

        $db = $this->db;
        $adapter = $db->getAdapter();

        $languageModel = Factory::get(Languages::class);

        // Make sure language resource will be offered for assignments for tasks, even if the sub-languages do not match
        $majorLangId = $languageModel->findMajorLanguageById($task->getSourceLang());
        $sourceLangs = empty($majorLangId) ?
            [$task->getSourceLang()] :
            $languageModel->getFuzzyLanguages($majorLangId, 'id', true);

        $majorLangId = $languageModel->findMajorLanguageById($task->getTargetLang());
        $targetLangs = empty($majorLangId)
            ? [$task->getTargetLang()] :
            $languageModel->getFuzzyLanguages($majorLangId, 'id', true);

        //get all available services
        /* @var $services editor_Services_Manager */
        $services = Factory::get('editor_Services_Manager');
        $allservices = $services->getAll();

        $this->filter?->addTableForField('taskGuid', 'ta');
        $s = $db->select()
            ->setIntegrityCheck(false)
            ->from(
                [
                    "languageResource" => "LEK_languageresources",
                ],
                [
                    new Zend_Db_Expr($adapter->quote($task->getTaskName()) . ' as taskName'),
                    new Zend_Db_Expr($adapter->quote($taskGuid) . ' as taskGuid'),
                    'languageResource.id AS languageResourceId', 'languageResource.langResUuid',
                    'languageResource.name', 'languageResource.color', 'languageResource.resourceId',
                    'languageResource.serviceType', 'languageResource.serviceName', 'languageResource.specificData',
                    'languageResource.timestamp', 'languageResource.resourceType', 'languageResource.writeSource',
                    'ta.id AS taskassocid', 'ta.segmentsUpdateable', 'ta.penaltyGeneral', 'ta.penaltySublang',
                ]
            )
            ->join([
                "la" => "LEK_languageresources_languages",
            ], 'languageResource.id=la.languageResourceId', ['la.sourceLang AS sourceLang', 'la.targetlang AS targetLang'])
            ->where('la.sourceLang IN(?)', $sourceLangs)
            ->where('la.targetLang IN(?)', $targetLangs)
            ->where('languageResource.serviceType IN(?)', $allservices);

        //check filter is set true when editor needs a list of all used TMs/MTs
        if ($this->filter?->hasFilter('checked')) {
            //if checked filter is set, we keep the taskGuid as filter argument,
            // but remove additional checked filter and checked info
            $this->filter->deleteFilter('checked');
            $checkColumns = '';
        } else {
            $this->filter?->deleteFilter('taskGuid');
            $checkColumns = [
                //checked is true when an assoc entry was found
                "checked" => $adapter->quoteInto('IF(ta.taskGuid = ?,\'true\',\'false\')', $taskGuid),
                //segmentsUpdateable is true when an assoc entry was found and the real value was true too
                "segmentsUpdateable" => $adapter->quoteInto('IF(ta.taskGuid = ?,segmentsUpdateable,0)', $taskGuid),
            ];
        }

        $on = $adapter->quoteInto('ta.languageResourceId = languageResource.id AND ta.taskGuid = ?', $taskGuid);
        //must be a left join since we want ALL LangRes with same language pair and client as the given task
        // and not just the ones associated with the task. If someone wants only the associated ones, the taskGuid
        // must be given as filter!
        $s->joinLeft([
            "ta" => "LEK_languageresources_taskassoc",
        ], $on, $checkColumns);

        $s->joinLeft(
            [
                'ttm' => 'LEK_task_tm_task_association',
            ],
            'languageResource.id = ttm.languageResourceId',
            'IF(ISNULL(ttm.id), 0, 1) AS isTaskTm'
        );
        if (
            ! $this->filter?->hasFilter('isTaskTm')
            // By default, we filter out all project TMs that are not associated to the task
            || (false === (bool) $this->filter->getFilter('isTaskTm')->value)
        ) {
            $s->where('ISNULL(ttm.id) OR ta.id IS NOT NULL');
        }
        $this->filter?->deleteFilter('isTaskTm');

        // Only match resources can be associated to a task, that are associated to the same client as the task is.
        $s->join([
            "cu" => "LEK_languageresources_customerassoc",
        ], 'languageResource.id=cu.languageResourceId', [
            'cu.customerId AS customerId',
            'IFNULL(ta.penaltyGeneral, cu.penaltyGeneral) AS penaltyGeneral',
            'IFNULL(ta.penaltySublang, cu.penaltySublang) AS penaltySublang',
        ])->where('cu.customerId=?', $task->getCustomerId());

        $s->group('languageResource.id');

        return $this->loadFilterdCustom($s);
    }

    /***
     * Load the associated language resources to a task by serviceName
     * @param string $taskGuid
     * @param string $serviceName
     * @param array $ignoreAssocs: ignore languageresources task assocs
     */
    public function loadAssocByServiceName($taskGuid, $serviceName, $ignoreAssocs = [])
    {
        $s = $this->db->select()
            ->from([
                "assocs" => "LEK_languageresources_taskassoc",
            ], ["assocs.*"])
            ->setIntegrityCheck(false)
            ->join([
                "lr" => "LEK_languageresources",
            ], "assocs.languageResourceId = lr.id", "")
            ->where('assocs.taskGuid=?', $taskGuid)
            ->where('lr.serviceName=?', $serviceName);
        if (! empty($ignoreAssocs)) {
            $s->where('assocs.id NOT IN(?)', $ignoreAssocs);
        }

        return $this->db->fetchAll($s)->toArray();
    }

    /***
     * Get all assocs by $taskGuids
     * If no $taskGuids are provided, all assoc will be loaded
     * @param array $taskGuids
     * @return array
     */
    public function loadByTaskGuids($taskGuids = [])
    {
        $s = $this->db->select();
        if (! empty($taskGuids)) {
            $s->where('taskGuid IN(?)', $taskGuids);
        }

        return $this->db->fetchAll($s)->toArray();
    }

    /***
     * Get all tasks that are assigned to the provided languageResourceId.
     * @param int $languageResourceId
     * @return array
     */
    public function getAssocTasksByLanguageResourceId($languageResourceId)
    {
        $s = $this->db->select()
            ->where('languageResourceId=?', $languageResourceId);

        return $this->db->fetchAll($s)->toArray();
    }

    /***
     * Get all updatable memories for a given task
     * @param string $taskGuid
     * @return array
     */
    public function getTaskUpdatable(string $taskGuid): array
    {
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid)
            ->where('segmentsUpdateable = 1');

        return $this->db->fetchAll($s)->toArray();
    }

    /**
     * Make sure unmodified penalties - are picked from langres<=>customer assoc
     *
     * @throws ReflectionException
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function onBeforeInsert(): void
    {
        // Prepare global defaults for penalties
        $defaults = [
            'penaltyGeneral' => 0,
            'penaltySublang' => editor_Models_Segment_MatchRateType::MAX_VALUE,
        ];

        // Unset the ones for modified penalty props
        foreach (array_keys($defaults) as $penalty) {
            if ($this->isModified($penalty)) {
                unset($defaults[$penalty]);
            }
        }

        // If both penalties are explicitly set - no need to pick default, so nothing to do here
        if (count($defaults) === 0) {
            return;
        }

        // Get customer id
        $task = Factory::get(Task::class);
        $task->loadByTaskGuid($this->getTaskGuid());
        $customerId = (int) $task->getCustomerId();

        // Get langres<=>customer assoc model
        $customerAssoc = Factory::get(editor_Models_LanguageResources_CustomerAssoc::class);
        $customerAssoc->loadRowByCustomerIdAndResourceId($customerId, $this->getLanguageResourceId());

        // Foreach of unmodified defaults
        foreach ($defaults as $penalty => $value) {
            // Prepare setter and getter method names
            $set = 'set' . ucfirst($penalty);
            $get = 'get' . ucfirst($penalty);

            // Pick task<=>langres penalty from langres<=>customer
            $this->$set($customerAssoc->hasRow() && $customerAssoc->getId() ? $customerAssoc->$get($penalty) : $value);
        }
    }
}
