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

namespace MittagQI\Translate5\Task\Reimport;

use editor_Models_Loaders_Taskuserassoc as JobLoader;
use editor_Models_Task as Task;
use editor_Models_TaskUserAssoc;
use MittagQI\Translate5\Task\Lock;
use MittagQI\Translate5\Task\Reimport\DataProvider\AbstractDataProvider;
use MittagQI\Translate5\Task\Reimport\DataProvider\FileDto;
use Zend_Acl_Exception;
use ZfExtended_Acl;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Conflict;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_User;
use ZfExtended_Worker_Abstract;

/**
 * Contains the Task Reimport Worker
 */
class Worker extends ZfExtended_Worker_Abstract
{

    /**
     * context when applying the filters on the filenames of the already imported files in LEK_files
     */
    const FILEFILTER_CONTEXT_EXISTING = 'REIMPORT_CHECK_EXISTING';

    /**
     * context when applying the filters on uploaded re-import files
     */
    const FILEFILTER_CONTEXT_NEW = 'REIMPORT_CHECK_NEW';

    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array())
    {
        $neededEntries = ['files', 'userGuid', 'segmentTimestamp', 'dataProviderClass'];
        $foundEntries = array_keys($parameters);
        $keyDiff = array_diff($neededEntries, $foundEntries);
        //if there is not keyDiff all needed were found
        return empty($keyDiff);
    }

    /**
     * (non-PHPdoc)
     * @return true
     * @throws Exception
     * @throws Zend_Acl_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws \JsonException
     * @throws ZfExtended_Models_Entity_Conflict
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work()
    {

        $params = $this->workerModel->getParameters();

        /** @var Task $task */
        $task = ZfExtended_Factory::get('editor_Models_Task');
        $task->loadByTaskGuid($this->taskGuid);

        /** @var ZfExtended_Models_User $user */
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        $user->loadByGuid($params['userGuid']);


        //contains the TUA which is used to alter the segments
        $tua = $this->prepareTaskUserAssociation($task, $user);

        try {
            Lock::taskLock($task, $task::STATE_REIMPORT);

            $reimportFile = ZfExtended_Factory::get(ReimportFile::class, [
                $task,
                $user
            ]);

            foreach ($params['files'] as $fileId => $file) {
                /* @var FileDto $file */
                $reimportFile->import($fileId, $file->reimportFile, $params['segmentTimestamp']);
                $reimportFile->getSegmentProcessor()->log();
            }
        } finally {
            //if it was a PM override, delete it again
            if ($tua->getIsPmOverride()) {
                $tua->delete();
            }
            Lock::taskUnlock($task);

            $this->archiveImportedData($task, $params['files']); //FIXME archive is updated also in case of error?
            $this->cleanupImportFolder($params['dataProviderClass'], $task);
        }

        return true;
    }


    /**
     * prepares the isPmOverride taskUserAssoc if needed!
     * @param Task $task
     * @param ZfExtended_Models_User $user
     * @return editor_Models_TaskUserAssoc
     * @throws Zend_Acl_Exception
     */
    protected function prepareTaskUserAssociation(Task $task, ZfExtended_Models_User $user): editor_Models_TaskUserAssoc
    {
        $userTaskAssoc = ZfExtended_Factory::get(editor_Models_TaskUserAssoc::class);

        try {

            $acl = ZfExtended_Acl::getInstance();

            $isUserPm = $task->getPmGuid() == $user->getUserGuid();
            $isEditAllAllowed = $acl->isInAllowedRoles($user->getRoles(), 'backend', 'editAllTasks');
            $isEditAllTasks = $isEditAllAllowed || $isUserPm;

            //if the user is allowed to load all, use the default loader
            if ($isEditAllTasks) {
                $userTaskAssoc = JobLoader::loadByTaskForceWorkflowRole($user->getUserGuid(), $task);
            } else {
                $userTaskAssoc = JobLoader::loadByTask($user->getUserGuid(), $task);
            }

            $userTaskAssoc->getIsPmOverride();
        } catch (ZfExtended_Models_Entity_NotFoundException) {

            $userTaskAssoc->setUserGuid($user->getUserGuid());
            $userTaskAssoc->setTaskGuid($task->getTaskGuid());
            $userTaskAssoc->setRole('');
            $userTaskAssoc->setState('');
            $userTaskAssoc->setWorkflow($task->getWorkflow());
            $userTaskAssoc->setWorkflowStepName('');
            $userTaskAssoc->setIsPmOverride(true);

        }

        $userTaskAssoc->save();

        return $userTaskAssoc;
    }

    /***
     * Create new archive version after the reimport
     * @param Task $task
     * @param FileDto[] $filesToUpdate
     * @return void
     */
    private function archiveImportedData(Task $task, array $filesToUpdate): void
    {
        $archiveUpdater = ZfExtended_Factory::get(TaskArchiveUpdater::class);
        if (! $archiveUpdater->updateFiles($task, $filesToUpdate)) {
            $this->log->warn(
                'E1475',
                'Re-Import: No ImportArchive backup created: Import Archive does not exist or folder is not writeable',
                ['task' => $task]
            );
        }
    }


    /**
     * Clean the temporary folders used for extracting zip archives.
     * @param string $dataProviderClass
     * @param Task $task
     * @return void
     */
    private function cleanupImportFolder(string $dataProviderClass, Task $task): void
    {
        if (is_subclass_of($dataProviderClass, AbstractDataProvider::class)) {
            $dataProviderClass::getForCleanup($task)->cleanup();
        }
    }
}