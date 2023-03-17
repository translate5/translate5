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

use editor_Models_Task;
use Exception;
use MittagQI\Translate5\Task\Export\Exported\PackageWorker;
use MittagQI\Translate5\Task\Lock;
use MittagQI\ZfExtended\Controller\Response\Header;
use Zend_Exception;
use Zend_Registry;
use Zend_Session;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Conflict;
use ZfExtended_Models_Entity_NotFoundException;

/**
 *
 */
class Downloader
{
    public const PACKAGE_EXPORT = 'PackageExport';

    /**
     * @param editor_Models_Task $task
     * @param bool $diff
     * @return int
     */
    public function run(editor_Models_Task $task, bool $diff): int
    {

        // Turn off limitations?
        ignore_user_abort(1);

        set_time_limit(0);

        $worker = ZfExtended_Factory::get(Worker::class);
        $exportFolder = $worker->initExport($task, $diff);

        $workerId = $worker->queue();

        $worker = ZfExtended_Factory::get(PackageWorker::class);

        $worker->init($task->getTaskGuid(), [
            'folderToBeZipped' => $exportFolder,
            'zipFile' => self::getZipFile($task)
        ]);

        $packageWorkerId = $worker->queue($workerId);

        return $packageWorkerId;
    }

    /**
     * Check if the export package is finished by checking if the last worker in the export package chain is finished
     * @param int $workerId
     * @return bool
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function isAvailable(int $workerId): bool
    {
        $worker = ZfExtended_Factory::get('ZfExtended_Models_Worker');
        $worker->load($workerId);

        if($worker->isDefunct()){
            $task = ZfExtended_Factory::get('editor_Models_Task');
            $task->loadByTaskGuid($worker->getTaskGuid());
            throw new \MittagQI\Translate5\Task\Export\Package\Exception('E1501',[
                'task' =>$task
            ]);
        }
        return $worker->isDone();
    }

    /**
     * Download package for given task. In case the file does not exist, this will throw an exception
     * @param editor_Models_Task $task
     * @return void
     * @throws Exception
     */
    public function download(editor_Models_Task $task): void
    {
        $zipFile = $this->getZipFile($task);
        if(is_file($zipFile) === false){
            throw new \MittagQI\Translate5\Task\Export\Package\Exception('E1502',[
                'task' =>$task
            ]);
        }

        Header::sendDownload($task->getTasknameForDownload('_exportPackage.zip'),'application/zip');

        readfile($zipFile);
        unlink($zipFile);
    }

    /**
     * Get the package download link given task and worker id. The worker id is the id of the worker which will zip
     * the exported package(the last worker in the export package chain).
     * @param editor_Models_Task $task
     * @param int $workerId
     * @return string
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function getDownloadLink(editor_Models_Task $task, int $workerId): string
    {
        $restPath = APPLICATION_RUNDIR.'/'.Zend_Registry::get('module').'/';

        $worker = ZfExtended_Factory::get('ZfExtended_Models_Worker');
        $worker->load($workerId);

        return $restPath.'taskid/'.$task->getId().'/task/packagestatus?download=true&workerId='.$worker->getId();
    }

    /**
     * @param editor_Models_Task $task
     * @return string
     */
    protected function getZipFile(editor_Models_Task $task): string
    {
        return $task->getAbsoluteTaskDataPath().DIRECTORY_SEPARATOR.self::PACKAGE_EXPORT;
    }

}