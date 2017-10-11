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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
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
 */
class editor_Models_File_FilterManager {
    const TYPE_IMPORT = 'import';
    const TYPE_EXPORT = 'export';
    
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * currently loaded filter definitions per fileId
     * @var array
     */
    protected $filters;
    
    /**
     * loads all file filters for a given task
     * @param editor_Models_Task $task
     * @param unknown $type
     */
    public function init(editor_Models_Task $task, $type) {
        $this->task = $task;
        $filter = ZfExtended_Factory::get('editor_Models_File_Filter');
        /* @var $filter editor_Models_File_Filter */
        $filters = $filter->loadForTask($task->getTaskGuid(), $type);
        $this->filters = [];
        foreach($filters as $filter) {
            settype($this->filters[$filter->fileId], 'array');
            $this->filters[$filter->fileId][] = $filter;
        }
    }
    
    /**
     * @param string $path
     * @param integer $fileId
     */
    public function applyImportFilters($path, $fileId) {
        $this->applyFilters(self::TYPE_IMPORT, $path, $fileId);
    }
    
    /**
     * @param string $path
     * @param integer $fileId
     */
    public function applyExportFilters($path, $fileId) {
        $this->applyFilters(self::TYPE_EXPORT, $path, $fileId);
    }
    
    /**
     * @param array $params
     */
    protected function applyFilters($type, string $path, integer $fileId) {
        if(empty($this->filters[$fileId])) {
            return;
        }
        $filters = $this->filters[$fileId];
        foreach($filters as $filter) {
            $filterInstance = ZfExtended_Factory::get($filter->filter);
            /* @var $filterInstance editor_Models_File_IFilter */
            $filterInstance->setFilterManager($this);
            if($type == self::TYPE_EXPORT) {
                $filterInstance->applyExportFilter($this->task, $fileId, $path, $filter->parameters);
            }
            else {
                $filterInstance->applyImportFilter($this->task, $fileId, $path, $filter->parameters);
            }
        }
    }
    
    /**
     * Adds the given file filter for the given file
     * @param string $type
     * @param string $taskGuid
     * @param integer $fileId
     * @param string $filterClass
     */
    public function addFilter($type,$taskGuid, $fileId, $filterClass) {
        $filter = ZfExtended_Factory::get('editor_Models_File_Filter');
        /* @var $filter editor_Models_File_Filter */
        $filter->setFileId($fileId);
        $filter->setTaskGuid($taskGuid);
        $filter->setFilter($filterClass);
        $filter->setType($type);
        $filter->save();
        //$this->setParameters($parameters); FIXME!
    }
}