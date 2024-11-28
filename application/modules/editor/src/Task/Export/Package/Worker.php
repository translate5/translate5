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

namespace MittagQI\Translate5\Task\Export\Package;

use editor_Models_Export_Worker;
use editor_Models_Task;
use MittagQI\Translate5\Task\TaskLockService;
use Throwable;
use Zend_Registry;
use ZfExtended_Authentication;
use ZfExtended_Factory;
use ZfExtended_Logger;

class Worker extends editor_Models_Export_Worker
{
    /***
     * @var ExportSource
     */
    private ExportSource $exportSource;

    private TaskLockService $lock;

    public function __construct()
    {
        parent::__construct();

        $this->lock = TaskLockService::create();
    }

    /**
     * @throws Exception
     */
    public function initExport(editor_Models_Task $task, bool $diff): string
    {
        $this->task = $task;

        $this->exportSource = ZfExtended_Factory::get(ExportSource::class, [
            $this->task,
        ]);

        $root = $this->exportSource->initFileStructure();

        try {
            $this->exportSource->validate();
            $this->events->trigger('initPackageFileStructure', $this, [
                'exportSource' => $this->exportSource,
                'task' => $this->task,
            ]);
        } catch (\ZfExtended_ErrorCodeException $throwable) {
            throw new Exception('E1453', [
                'task' => $task,
            ], $throwable);
        }

        return $this->initFolderExport($task, $diff, $root);
    }

    protected function validateParameters(array $parameters): bool
    {
        if (! parent::validateParameters($parameters)) {
            return false;
        }

        return ! empty($parameters['userGuid']);
    }

    /**
     * inits a export to a given directory
     * @return string the folder which receives the exported data
     */
    public function initFolderExport(editor_Models_Task $task, bool $diff, string $exportFolder)
    {
        $parameter = [
            'diff' => $diff,
            self::PARAM_EXPORT_FOLDER => $exportFolder,
            'userGuid' => ZfExtended_Authentication::getInstance()->getUserGuid(),
        ];
        $this->init($task->getTaskGuid(), $parameter);

        return $exportFolder;
    }

    public function work(): bool
    {
        //also containing an instance of the initial dataprovider.
        // The Dataprovider can itself hook on to several import events
        $parameters = $this->workerModel->getParameters();

        if (empty($this->task)) {
            $this->task = ZfExtended_Factory::get('editor_Models_Task');
            $this->task->loadByTaskGuid($this->taskGuid);
        }

        if (! $this->validateParameters($parameters)) {
            return false;
        }

        try {
            $this->lock->lockTask($this->task, editor_Models_Task::STATE_PACKAGE_EXPORT);

            $this->exportSource = ZfExtended_Factory::get(ExportSource::class, [
                $this->task,
            ]);
            $this->exportSource->export($this->workerModel);
        } catch (Throwable $throwable) {
            $logger = Zend_Registry::get('logger');
            /* @var ZfExtended_Logger $logger */
            $logger->exception($throwable, [
                'extra' => [
                    'task' => $this->task,
                ],
            ]);

            $this->lock->unlockTask($this->task);

            throw $throwable;
        }

        return true;
    }
}
