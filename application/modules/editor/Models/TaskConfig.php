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

/***
* @method void setId(int $id)
* @method string getId()
* @method void setTaskGuid(guid $taskGuid)
* @method string getTaskGuid()
* @method void setName(string $name)
* @method string getName()
* @method void setValue(string $value)
* @method string getValue()
*/

class editor_Models_TaskConfig extends ZfExtended_Models_Entity_Abstract
{
    protected $dbInstanceClass = editor_Models_Db_TaskConfig::class;

    protected $validatorInstanceClass = editor_Models_Validator_TaskConfig::class;

    /***
     * Internal cache task config cache
     * @var Zend_Config
     */
    protected static $taskCustomerConfig = [];

    /***
     *  Return all task specific configs for the given task guid.
     *  For all configs for which there is not task specific overwrite, the overwrite for the task client will be used as a value.
     *  For all configs for which there is no task customer specific overwrite, the instance-level config value will be used
     *
     * @param string $taskGuid
     * @return Zend_Config
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws editor_Models_ConfigException
     */
    public function getTaskConfig(string $taskGuid)
    {
        if (empty($taskGuid)) {
            throw new editor_Models_ConfigException('E1297');
        }
        if (isset(self::$taskCustomerConfig[$taskGuid])) {
            return self::$taskCustomerConfig[$taskGuid];
        }
        // retrieves all DB Configs for the task, already overwritten by level
        $configModel = $this->getTaskConfigModel($taskGuid);

        $configOperator = ZfExtended_Factory::get(ZfExtended_Resource_DbConfig::class);
        $configOperator->initDbOptionsTree($configModel);

        $taskConfig = new Zend_Config($configOperator->getDbOptionTree());
        $taskConfig->setReadOnly();
        //cache the config for this request
        self::$taskCustomerConfig[$taskGuid] = $taskConfig;

        return self::$taskCustomerConfig[$taskGuid];
    }

    /**
     * Internal API to fetch the raw task's config from DB
     *
     * @return array
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    private function getTaskConfigModel(string $taskGuid)
    {
        $configModel = ZfExtended_Factory::get(editor_Models_Config::class);

        //fetch all config from DB
        $dbConfig = ZfExtended_Factory::get(ZfExtended_Models_Config::class);
        $base = $configModel->mergeIniValues($dbConfig->loadAll());

        //for merge set config name as array key
        $base = $configModel->nameAsKey($base);

        // merge task overwrites with task customer overwrites
        return $configModel->mergeTaskValues($taskGuid, $base);
    }

    /***
     * Load all configs for given taskGuid
     * @param string $taskGuid
     * @return array
     */
    public function loadByTaskGuid(string $taskGuid)
    {
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid);

        return $this->db->getAdapter()->fetchAll($s);
    }

    /***
     * Update or insert new config for given task
     *
     * @param mixed $value
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function updateInsertConfig(string $taskGuid, string $name, $value): Zend_Db_Statement_Interface
    {
        if (is_array($value)) {
            $value = implode('","', $value);
            $value = '["' . $value . '"]';
        }
        $sql = "INSERT INTO LEK_task_config(taskGuid,name,value) " .
            " VALUES (?,?,?) " .
            " ON DUPLICATE KEY UPDATE value = ? ";

        try {
            return $this->db->getAdapter()->query($sql, [$taskGuid, $name, $value, $value]);
        } catch (Zend_Db_Statement_Exception $e) {
            $this->handleIntegrityConstraintException($e);
        }
    }

    /***
     * Copy all task specific config from $odlTaskGuid to $newTaskGuid
     * @param string $oldTaskGuid
     * @param string $newTaskGuid
     */
    public function cloneTaskConfig(string $oldTaskGuid, string $newTaskGuid)
    {
        $adapter = $this->db->getAdapter();
        $sql = "INSERT INTO LEK_task_config (taskGuid, name, value)
        SELECT ?, old.name, old.value
        FROM  LEK_task_config old WHERE taskGuid = ?
        ON DUPLICATE KEY UPDATE value = old.value";
        //duplicates may occur in combination with a task-config.ini, in that case we take over the value from the DB
        $adapter->query($sql, [$newTaskGuid, $oldTaskGuid]);
    }

    /**
     * returns a specific config value for a specific task
     */
    public function getCurrentValue(string $taskGuid, string $name): ?string
    {
        try {
            $s = $this->db->select()
                ->where('taskGuid = ?', $taskGuid)
                ->where('name = ?', $name);
            $row = $this->db->fetchRow($s);
        } catch (Exception $e) {
            return null;
        }
        if (! $row) {
            return null;
        }

        return $row['value'];
    }

    /**
     * Fixes the config for a task/project after Import.
     * This means, that all configs with task-import-level will be inserted to the task-config table and thus never can be changed again in the lifetime of the task after import
     */
    public function fixAfterImport(array $tasks)
    {
        $db = $this->db->getAdapter();
        $values = [];

        /** @var editor_Models_Task $model */
        foreach ($tasks as $task) {
            if (is_array($task)) {
                $model = editor_ModelInstances::task($task['id']);
            } else {
                $model = $task;
            }

            //import workers can only be started for tasks
            if ($model->isProject()) {
                continue;
            }

            // evaluate the configs to fix
            $values = [];
            $taskConfigs = $this->getTaskConfigModel($model->getTaskGuid());
            foreach ($taskConfigs as $config) {
                if ($config['level'] == editor_Models_Config::CONFIG_LEVEL_TASKIMPORT) {
                    $values[] = '(' . $db->quote($model->getTaskGuid()) . ',' . $db->quote($config['name']) . ',' . $db->quote($config['value']) . ')';
                }
            }
        }

        if (count($values) < 1) {
            return;
        }

        // inset all new values, and leave the existing one inside the table.
        $sql = 'INSERT INTO `LEK_task_config` (`taskGuid`, `name`, `value`) VALUES ' . implode(',', $values) . '  ON DUPLICATE KEY UPDATE `value`=`value`';
        $db->query($sql);
    }

    /***
     * Clean the internal config cache variable
     */
    public function cleanConfigCache()
    {
        self::$taskCustomerConfig = [];
    }
}
