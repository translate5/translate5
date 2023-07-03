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
 * Manage file filters for pre/post-processing files on im-/export
 */
class editor_Models_File_FilterManager
{
    const TYPE_IMPORT = 'import';
    const TYPE_EXPORT = 'export';
    
    /**
     * @var editor_Models_Task
     */
    protected editor_Models_Task $task;
    
    /**
     * currently loaded filter definitions per fileId
     * @var array
     */
    protected array $filters;

    private editor_Models_File_FilterConfig $config;

    public function __construct()
    {
        $this->config = new editor_Models_File_FilterConfig();
    }

    /**
     * loads all file filters for a given task
     * @param editor_Models_Task $task
     * @param editor_Models_Import_Configuration $importConfig
     */
    public function initImport(editor_Models_Task $task, editor_Models_Import_Configuration $importConfig): void
    {
        //FIXME can we get the context from importConfig?
        $this->config->importConfig = $importConfig;
        $this->config->parentWorkerId = $importConfig->workerId;
        $this->loadFilters($task, self::TYPE_IMPORT);
    }

    /**
     * loads all file filters for a given task
     * @param editor_Models_Task $task
     * @param string $context
     */
    public function initReImport(editor_Models_Task $task, string $context): void
    {
        $this->config->context = $context;
        $this->loadFilters($task, self::TYPE_IMPORT);
    }

    /**
     * loads all file filters for a given task
     * @param editor_Models_Task $task
     * @param int $workerId
     * @param string $context
     */
    public function initExport(editor_Models_Task $task, int $workerId, string $context): void
    {
        $this->config->context = $context;
        $this->config->parentWorkerId = $workerId;
        $this->loadFilters($task, self::TYPE_EXPORT);
    }
    
    /**
     * loads all file filters for a given task
     * @param editor_Models_Task $task
     * @param string $type
     */
    protected function loadFilters(editor_Models_Task $task, string $type): void
    {
        $this->task = $task;
        $filter = ZfExtended_Factory::get('editor_Models_File_Filter');
        /* @var $filter editor_Models_File_Filter */
        $filters = $filter->loadForTask($task->getTaskGuid(), $type);
        $this->filters = [];
        foreach ($filters as $filter) {
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
    public function applyImportFilters(string $path, int $fileId): string
    {
        return $this->applyFilters(self::TYPE_IMPORT, $path, $fileId);
    }

    /**
     * @param string $path
     * @param int $fileId
     * @return string
     */
    public function applyExportFilters(string $path, int $fileId): string
    {
        return $this->applyFilters(self::TYPE_EXPORT, $path, $fileId);
    }

    /**
     * @param $type
     * @param string $path
     * @param int $fileId
     * @return string
     */
    protected function applyFilters($type, string $path, int $fileId): string
    {
        if (empty($this->filters[$fileId])) {
            return $path;
        }
        $filters = $this->filters[$fileId];
        foreach ($filters as $filter) {
            $filterInstance = ZfExtended_Factory::get($filter->filter);
            /* @var $filterInstance editor_Models_File_IFilter */
            $filterInstance->initFilter($this, $this->config);
            if ($type == self::TYPE_EXPORT) {
                $path = $filterInstance->applyExportFilter($this->task, $fileId, $path, $filter->parameters);
            } else {
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
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function addFilter(string $type, string $taskGuid, int $fileId, string $filterClass): void
    {
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
     * @param string|null $type
     * @return bool
     */
    public function hasFilter(int $fileId, string $type = null): bool
    {
        $checkTypes = [
            self::TYPE_EXPORT,
            self::TYPE_IMPORT,
        ];
        if (!empty($type)) {
            $checkTypes = [$type];
        }
        $filter = ZfExtended_Factory::get('editor_Models_File_Filter');
        /* @var $filter editor_Models_File_Filter */
        foreach ($checkTypes as $type) {
            $rowset = $filter->loadForFile($fileId, $type);
            if ($rowset->count() > 0) {
                return true;
            }
        }
        return false;
    }
}