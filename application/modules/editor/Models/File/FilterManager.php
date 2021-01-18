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
 */
class editor_Models_File_FilterManager {
    const TYPE_IMPORT = 'import';
    const TYPE_EXPORT = 'export';
    
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * @var editor_Models_Import_Configuration
     */
    protected $importConfig;
    
    /**
     * currently loaded filter definitions per fileId
     * @var array
     */
    protected $filters;
    
    /**
     * contains the worker id of the parent worker
     * @var integer
     */
    protected $parentWorkerId;
    
    /**
     * loads all file filters for a given task
     * @param editor_Models_Task $task
     * @param editor_Models_Import_Configuration $importConfig
     */
    public function initImport(editor_Models_Task $task, editor_Models_Import_Configuration $importConfig) {
        $this->importConfig = $importConfig;
        $this->parentWorkerId = $importConfig->workerId;
        $this->init($task, self::TYPE_IMPORT);
    }
    
    /**
     * loads all file filters for a given task
     * @param editor_Models_Task $task
     * @param int $workerId
     */
    public function initExport(editor_Models_Task $task, $workerId) {
        $this->parentWorkerId = $workerId;
        $this->init($task, self::TYPE_EXPORT);
    }
    
    /**
     * loads all file filters for a given task
     * @param editor_Models_Task $task
     * @param string $type
     */
    protected function init(editor_Models_Task $task, $type) {
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
     * returns the filename of the affected file (could be changed by the filters due conversion)
     * @param string $path
     * @param int $fileId
     * @return string
     */
    public function applyImportFilters($path, $fileId, array &$filelist) {
        return $filelist[$fileId] = $this->applyFilters(self::TYPE_IMPORT, $path, $fileId);
    }
    
    /**
     * @param string $path
     * @param int $fileId
     */
    public function applyExportFilters($path, $fileId) {
        $this->applyFilters(self::TYPE_EXPORT, $path, $fileId);
    }
    
    /**
     * @param array $params
     */
    protected function applyFilters($type, string $path, int $fileId) {
        if(empty($this->filters[$fileId])) {
            return $path;
        }
        $filters = $this->filters[$fileId];
        foreach($filters as $filter) {
            $filterInstance = ZfExtended_Factory::get($filter->filter);
            /* @var $filterInstance editor_Models_File_IFilter */
            $filterInstance->initFilter($this, $this->parentWorkerId, $this->importConfig);
            if($type == self::TYPE_EXPORT) {
                $filterInstance->applyExportFilter($this->task, $fileId, $path, $filter->parameters);
            }
            else {
                //import filters may change the used file path! Dangerous!
                $path = $filterInstance->applyImportFilter($this->task, $fileId, $path, $filter->parameters);
            }
        }
        return $path;
    }
    
    /**
     * Adds the given file filter for the given file
     * @param string $type
     * @param string $taskGuid
     * @param int $fileId
     * @param string $filterClass
     */
    public function addFilter(string $type, string $taskGuid, int $fileId, string $filterClass) {
        $filter = ZfExtended_Factory::get('editor_Models_File_Filter');
        /* @var $filter editor_Models_File_Filter */
        $filter->setFileId($fileId);
        $filter->setTaskGuid($taskGuid);
        $filter->setFilter($filterClass);
        $filter->setType($type);
        $filter->save();
        //$this->setParameters($parameters); FIXME!
    }
    
    /**
     * returns true if the file have at least one filter (or if $type given a for the specific type)
     * @param int $fileId
     * @param string $type
     */
    public function hasFilter(int $fileId, string $type = null) {
        $checkTypes = [
            self::TYPE_EXPORT,
            self::TYPE_IMPORT,
        ];
        if(!empty($type)) {
            $checkTypes = [$type];
        }
        $filter = ZfExtended_Factory::get('editor_Models_File_Filter');
        /* @var $filter editor_Models_File_Filter */
        foreach($checkTypes as $type) {
            $rowset = $filter->loadForFile($fileId, $type);
            if($rowset->count() > 0) {
                return true;
            }
        }
        return false;
    }
}