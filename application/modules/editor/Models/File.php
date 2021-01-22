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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Foldertree Object Instanz wie in der Applikation benötigt
 *
 * @method void setId() setId(int $id)
 * @method void setTaskGuid() setTaskGuid(string $guid)
 * @method void setFileName() setFileName(string $name)
 * @method void setFileParser() setFileParser(string $parser)
 * @method void setSourceLang() setSourceLang(int $source)
 * @method void setTargetLang() setTargetLang(int $target)
 * @method void setRelaisLang() setRelaisLang(int $target)
 * @method void setFileOrder() setFileOrder(int $order)
 * @method integer getId() getId()
 * @method string getTaskGuid() getTaskGuid()
 * @method string getFileName() getFileName()
 * @method string getFileParser() getFileParser()
 * @method integer getSourceLang() getSourceLang()
 * @method integer getTargetLang() getTargetLang()
 * @method integer getRelaisLang() getRelaisLang()
 * @method integer getFileOrder() getFileOrder()
 */
class editor_Models_File extends ZfExtended_Models_Entity_Abstract {
    const SKELETON_DIR_NAME = 'skeletonfiles';
    const SKELETON_PATH = '/skeletonfiles/file_%d.zlib';
    protected $dbInstanceClass = 'editor_Models_Db_Files';
    
    /**
     * remove dummy directory entries
     * @param array $idList
     */
    public function cleanupDirectoryIncrements(array $idList) {
        $this->db->delete('`id` in ('.join(',', $idList).')');
    }
    
    /**
     * @param array $taskGuids
     * @return array taskGuid => cnt
     */
    public function getFileCountPerTasks(array $taskGuids) {
        if(empty($taskGuids)) {
            return array();
        }
        
        $s = $this->db->select()
        ->from($this->db, array('cnt' => 'count(id)', 'taskGuid'))
        ->where('taskGuid in (?)', $taskGuids)
        ->group('taskGuid');
        
        $keys = array();
        $values = array_map(function($item) use (&$keys){
            $keys[] = $item['taskGuid'];
            return $item['cnt'];
        }, $this->db->fetchAll($s)->toArray());
        return array_combine($keys, $values);
    }
    /**
     * Saves the skeleton data for the current file to the disk
     * @param string $data Skeletonfile data
     * @param editor_Models_Task $task optional, task entity to get the data path from. If not given load one by the internal stored taskGuid
     */
    public function saveSkeletonToDisk($data, editor_Models_Task $task = null) {
        $filePath = $this->getSkeletonPath($task);
        $skelDir = dirname($filePath);
        if(!file_exists($skelDir)) {
            @mkdir($skelDir);
        }
        if(!is_writable($skelDir)) {
            throw new ZfExtended_Exception('Skeleton directory is not writeable! Directory: '.$skelDir);
        }
        file_put_contents($filePath, gzcompress($data));
    }
    
    /**
     * Loads the skeleton data for the current file from the disk and returns it
     * @param editor_Models_Task $task optional, task entity to get the data path from. If not given load one by the internal stored taskGuid
     */
    public function loadSkeletonFromDisk(editor_Models_Task $task = null) {
        $filePath = $this->getSkeletonPath($task);
        if(!file_exists($filePath)) {
            throw new ZfExtended_Exception('Skeleton file does not exist or not readable! File: '.$filePath);
        }
        return gzuncompress(file_get_contents($filePath));
    }
    
    /**
     * Returns the given task or loads one by the internal stored taskGuid
     * @param editor_Models_Task $task
     * @return editor_Models_Task
     */
    protected function getSkeletonPath(editor_Models_Task $task = null) {
        if(empty($task)) {
            $task = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            $task->loadByTaskGuid($this->getTaskGuid());
        }
        return $task->getAbsoluteTaskDataPath().sprintf(self::SKELETON_PATH, $this->getId());
    }
}