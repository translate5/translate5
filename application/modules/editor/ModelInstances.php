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
 * Convenient Container to load and store often used models in one request
 * THIS IS NO REQUEST PERSISTENT CACHE!
 */
class editor_ModelInstances {
    protected static array $instances = [];

    /**
     * Mapping of other fields to the model ID, since the ID is used as instance key.
     * @var array
     */
    protected static array $idMap = [];

    /**
     * returns (and loads if needed) the task by id
     * @param int $id
     * @return editor_Models_Task
     */
    public static function task(int $id): editor_Models_Task {
        /* @var $instance editor_Models_Task */
        $instance = self::getById('editor_Models_Task', $id);
        //we map the taskGuid here, so that the instance may be found by taskGuid also
        self::mapId($instance, 'getTaskGuid');
        return $instance;
    }

    /**
     * returns (and loads if needed) the task by taskGuid
     * @param string $taskGuid
     * @return editor_Models_Task
     */
    public static function taskByGuid(string $taskGuid): editor_Models_Task {
        /* @var $instance editor_Models_Task */
        $instance = self::getMapped('editor_Models_Task', 'getTaskGuid', $taskGuid, function() use ($taskGuid) {
            /* @var $instance editor_Models_Task */
            $instance = self::entityInstance('editor_Models_Task');
            $instance->loadByTaskGuid($taskGuid);
            return $instance;
        });
        return $instance;
    }

    /**
     * returns (and loads if needed) a file by id
     * @param int $id
     * @return editor_Models_File
     */
    public static function file(int $id): editor_Models_File {
        return self::getById('editor_Models_File', $id);
    }


    //TODO consider moving the following code into a ZfExtended abstract

    protected static function getById(string $class, int $id): ZfExtended_Models_Entity_Abstract {
        /* @var $instance ZfExtended_Models_Entity_Abstract */
        $instance = self::$instances[$class][$id] ?? null;
        if(! is_null($instance)) {
            return $instance;
        }
        $instance = self::entityInstance($class);
        $instance->load($id);
        self::store($instance);
        return $instance;
    }

    protected static function getMapped(string $class, string $fieldGetter, string $value, Closure $loader): ZfExtended_Models_Entity_Abstract {
        $id = self::$idMap[self::getMapKey($class, $fieldGetter, $value)] ?? null;
        if(is_null($id)) {
            $instance = $loader();
            self::store($instance);
            self::mapId($instance, $fieldGetter);
            return $instance;
        }
        return self::getById($class, $id);
    }

    protected static function mapId(ZfExtended_Models_Entity_Abstract $instance, $fieldGetter) {
        self::$idMap[self::getMapKey(get_class($instance), $fieldGetter, (string) $instance->$fieldGetter())] = $instance->getId();
    }

    protected static function getMapKey(string $class, string $fieldGetter, string $value): string {
        return $class.'#'.$fieldGetter.'#'.$value;
    }

    protected static function store(ZfExtended_Models_Entity_Abstract $instance): ZfExtended_Models_Entity_Abstract {
        $cls = get_class($instance);
        if(!array_key_exists($cls, self::$instances) || !is_array(self::$instances[$cls])) {
            self::$instances[$cls] = [];
        }
        //we assume that all of our models have a getId function!
        self::$instances[$cls][$instance->getId()] = $instance;
        return $instance;
    }

    protected static function entityInstance($class): ZfExtended_Models_Entity_Abstract {
        /* @var $model ZfExtended_Models_Entity_Abstract */
        return ZfExtended_Factory::get($class);
    }
}