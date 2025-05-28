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
declare(strict_types=1);

namespace MittagQI\Translate5\File\Filter;

use editor_Models_File_Filter;
use editor_Models_Import_Configuration;
use editor_Models_Task;
use ReflectionException;
use Zend_Db_Statement_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;

/**
 * Manage file filters for pre/post-processing files on im-/export
 */
class Manager
{
    protected editor_Models_Task $task;

    /**
     * currently loaded filter definitions per fileId
     */
    protected array $filters;

    private FilterConfig $config;

    public function __construct()
    {
        $this->config = new FilterConfig();
    }

    /**
     * loads all file filters for a given task
     * @throws ReflectionException
     */
    public function initImport(editor_Models_Task $task, editor_Models_Import_Configuration $importConfig): void
    {
        $this->config->importConfig = $importConfig;
        $this->config->parentWorkerId = $importConfig->workerId;
        $this->loadFilters($task, Type::Import);
    }

    /**
     * loads all file filters for a given task
     * @throws ReflectionException
     */
    public function initReImport(editor_Models_Task $task, string $context): void
    {
        $this->config->context = $context;
        $this->loadFilters($task, Type::Import);
    }

    /**
     * loads all file filters for a given task
     * @throws ReflectionException
     */
    public function initExport(editor_Models_Task $task, int $workerId, string $context): void
    {
        $this->config->context = $context;
        $this->config->parentWorkerId = $workerId;
        $this->loadFilters($task, Type::Export);
    }

    /**
     * loads all file filters for a given task
     * @throws ReflectionException
     */
    protected function loadFilters(editor_Models_Task $task, Type $type): void
    {
        $this->task = $task;
        $filter = ZfExtended_Factory::get(editor_Models_File_Filter::class);
        $filters = $filter->loadForTask($task->getTaskGuid(), $type->value);
        $this->filters = [];
        foreach ($filters as $filter) {
            if (isset($filter->fileId)) {
                settype($this->filters[$filter->fileId], 'array');
                $this->filters[$filter->fileId][] = $filter;
            }
        }
    }

    /**
     * returns the filename of the affected file (could be changed by the filters due conversion)
     * @throws FilterException
     * @throws ReflectionException
     */
    public function applyImportFilters(string $path, int $fileId): string
    {
        return $this->applyFilters(Type::Import, $path, $fileId);
    }

    /**
     * @throws FilterException
     * @throws ReflectionException
     */
    public function applyExportFilters(string $path, int $fileId): string
    {
        return $this->applyFilters(Type::Export, $path, $fileId);
    }

    /**
     * @throws FilterException
     * @throws ReflectionException
     */
    protected function applyFilters(Type $type, string $path, int $fileId): string
    {
        if (empty($this->filters[$fileId])) {
            return $path;
        }
        $filters = $this->filters[$fileId];
        foreach ($filters as $filter) {
            $filterInstance = ZfExtended_Factory::get($filter->filter);
            /** @var FileFilterInterface $filterInstance */
            $filterInstance->initFilter($this, $this->config);
            if ($type === Type::Export) {
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
     * @param class-string<FileFilterInterface> $filterClass
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function addFilter(
        Type $type,
        string $taskGuid,
        int $fileId,
        string $filterClass,
        string $params = null,
    ): void {
        $filter = ZfExtended_Factory::get(editor_Models_File_Filter::class);
        $filter->setFileId($fileId);
        $filter->setTaskGuid($taskGuid);
        $filter->setFilter($filterClass);
        $filter->setType($type->value);
        $filter->setParameters($params);
        $filter->setWeight($filterClass::getWeight());
        $filter->save();
    }

    /**
     * returns true if the file have at least one filter (or if $type given a for the specific type)
     * @throws ReflectionException
     */
    public function hasFilter(int $fileId, Type $type = null): bool
    {
        $checkTypes = [
            Type::Export,
            Type::Import,
        ];
        if (! empty($type)) {
            $checkTypes = [$type];
        }
        $filter = ZfExtended_Factory::get(editor_Models_File_Filter::class);
        foreach ($checkTypes as $type) {
            $rowset = $filter->loadForFile($fileId, $type->value);
            if ($rowset->count() > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint|ReflectionException
     */
    public function addByConfig(
        string $taskGuid,
        editor_Models_Import_Configuration $importConfig,
        array $fileList,
    ): void {
        foreach ($importConfig->fileFilters as $fileFilter) {
            if (! is_subclass_of($fileFilter, FileFilterByConfigInterface::class)) {
                continue;
            }
            foreach ($fileList as $fileId => $filePath) {
                $types = $fileFilter::getTypesForFile($filePath);
                foreach ($types as $type) {
                    $this->addFilter(
                        $type,
                        $taskGuid,
                        $fileId,
                        $fileFilter
                    );
                }
            }
        }
    }
}
