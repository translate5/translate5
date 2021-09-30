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
 * Parses Mqm Types and severities which are defined in the task's data model
 * This class holds an instance for each task in case of multi task usage
 */
final class editor_Segment_Mqm_Configuration {
    
    /**
     * @var editor_Segment_Mqm_Configuration[]
     */
    private static $_instances = [];
    /**
     *
     * @param editor_Models_Task $task
     * @return editor_Segment_Mqm_Configuration
     */
    public static function instance(editor_Models_Task $task){
        if(!array_key_exists($task->getId(), self::$_instances)){
            self::$_instances[$task->getId()] = new editor_Segment_Mqm_Configuration($task);
        }
        return self::$_instances[$task->getId()];
    }
    /**
     *
     * @var string[]
     */
    private $mqmTypes = [];
    /**
     *
     * @var int[]
     */
    private $mqmTypeIds = [];
    /**
     *
     * @var string[]
     */
    private $mqmSeverities = [];
    /**
     *
     * @param editor_Models_Task $task
     */
    private function __construct(editor_Models_Task $task){
        try {
            $this->mqmTypes = $task->getMqmTypesFlat();
            $this->mqmTypeIds = array_keys($this->mqmTypes);
            $this->mqmSeverities = array_keys(get_object_vars($task->getMqmSeverities()));
        } catch (Exception $e) {
        }
    }
    /**
     * Retrieves if there are MQMs configured
     * @return bool
     */
    public function isEmpty() : bool {
        return (count($this->mqmTypeIds) == 0);
    }
    /**
     * Find a severity in an array of css classes
     * @param string[] $cssClasses
     * @param string $default
     * @return string
     */
    public function findMqmSeverity(array $cssClasses, string $default=null) : ?string {
        foreach($this->mqmSeverities as $severity){
            if(in_array($severity, $this->mqmSeverities)){
                return $severity;
            }
        }
        return $default;
    }
    /**
     * Find a MQM type id in an array of css classes
     * @param int[] $ids
     * @param int $default
     * @return string
     */
    public function findMqmTypeId(array $ids, int $default=-1) : int {
        foreach($this->mqmTypeIds as $id){
            if(in_array($id, $this->mqmTypeIds)){
                return $id;
            }
        }
        return $default;
    }
    /**
     *
     * @param int $id
     * @return string|NULL
     */
    public function getMqmTypeForId(int $id) : ?string {
        if(array_key_exists($id, $this->mqmTypes)){
            return $this->mqmTypes[$id];
        }
        return NULL;
    }
    /**
     * Returns all possible mqm type indices/ids
     * @return number[]
     */
    public function getAllIds(){
        return $this->mqmTypeIds;
    }
}
