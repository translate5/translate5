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
 * Encapsulates all task types and task type tests
 */
class editor_Task_Type {
    /**
     * Singleton instance
     * @var editor_Task_Type $instance
     */
    protected static self $instance;

    /**
     * the registered task types, prefilled with the default ones
     * @var string[]|editor_Task_Type[]
     */
    protected static array $registeredTypes = [
        'editor_Task_Type_Default',
        'editor_Task_Type_Project',
        'editor_Task_Type_ProjectTask',
    ];

    /**
     * The calculated initial taskType for projects
     * @var string
     */
    protected $importTypeProject = editor_Task_Type_Default::ID;

    /**
     * The calculated initial taskType for tasks
     * @var string
     */
    protected $importTypeTask = editor_Task_Type_Default::ID;

    /**
     * Returns the singleton of the task type manager
     * @return editor_Task_Type
     */
    public static function getInstance(): self {
        return self::$instance ?? self::$instance = new self();
    }

    protected function __construct() {
        $types = [];
        foreach(self::$registeredTypes as $cls) {
            /** @var editor_Task_Type_Abstract $type */
            $type = new $cls;
            $types[$type->id()] = $type;
        }
        //reset the internal static list of class names to map of type instances
        self::$registeredTypes = $types;
    }

    /**
     * Adds over overwrites a given task type with the mapped class name
     * @param string $type
     * @param string $className
     */
    public function registerType(string $className) {
        /** @var editor_Task_Type_Abstract $type */
        $type = new $className;
        self::$registeredTypes[$type->id()] = $type;
    }

    /**
     * Return available tasktype keys
     * @return string[]
     */
    public function getValidTypes(): array {
        return array_keys(self::$registeredTypes);
    }

    /**
     * gets all type IDs for which the usage should be exported
     */
    public function getUsageExportTypes(): array {
        return $this->filterTypes(function(string $id, editor_Task_Type_Abstract $type) {
            return $type->isExportUsage();
        });
    }

    /**
     * gets all type IDs which are considered to be a project, since there are tasks being a task and a project, we have to exclude the tasks optionally
     */
    public function getProjectTypes(bool $excludeTasks = false): array {
        return $this->filterTypes(function(string $id, editor_Task_Type_Abstract $type) use ($excludeTasks){
            return $type->isProject() && !($excludeTasks && $type->isTask());
        });
    }

    /**
     * gets all type IDs which are considered to be a task
     */
    public function getTaskTypes(): array {
        return $this->filterTypes(function(string $id, editor_Task_Type_Abstract $type) {
            return $type->isTask();
        });
    }

    /**
     * gets all type IDs which are considered to be a task and which are non internal
     */
    public function getNonInternalTaskTypes(): array {
        return $this->filterTypes(function(string $id, editor_Task_Type_Abstract $type) {
            return $type->isTask() && !$type->isInternalTask();
        });
    }

    /**
     * Internal filter function for getting filtered type IDs
     * @param Closure $callback
     * @return array
     */
    protected function filterTypes(Closure $callback): array {
        $result = [];
        /** @var editor_Task_Type_Abstract $type */
        foreach(self::$registeredTypes as $id => $type) {
            if($callback($id, $type)) {
                $result[] = $id;
            }
        }
        return $result;
    }

    /**
     * returns the task type instance to a given task type ID
     * @param string $type
     * @return editor_Task_Type_Abstract
     */
    public function getType(string $type): editor_Task_Type_Abstract
    {
        return self::$registeredTypes[$type] ?? self::$registeredTypes[editor_Task_Type_Default::ID];
    }

    /**
     * Calculates the to be used task and project types, based on the currently only relevant information
     * @param bool $multiTarget
     * @param string $initialType
     */
    public function calculateImportTypes(bool $multiTarget, string $initialType = editor_Task_Type_Default::ID) {
        $type = $this->getType($initialType);
        $type->calculateImportTypes($multiTarget, $this->importTypeProject, $this->importTypeTask);
    }

    /**
     * returns the task type to be used on import
     * @return string
     */
    public function getImportTaskType() {
        return $this->importTypeTask;
    }

    /**
     * returns the project type to be used on import
     * @return string
     */
    public function getImportProjectType() {
        return $this->importTypeProject;
    }
}
