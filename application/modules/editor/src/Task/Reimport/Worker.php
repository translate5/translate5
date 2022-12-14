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

use editor_Models_Import_FileParser;
use editor_Models_Loaders_Taskuserassoc;
use editor_Models_SegmentFieldManager;
use editor_Models_Task;
use editor_Models_TaskUserAssoc;
use MittagQI\Translate5\Task\Import\FileParser\Factory;
use MittagQI\Translate5\Task\Lock;
use MittagQI\Translate5\Task\Reimport\SegmentProcessor\Reimport;
use Zend_Acl_Exception;
use ZfExtended_Acl;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_User;
use ZfExtended_Worker_Abstract;

/**
 * Contains the Excel Reimport Worker
 */
class Worker extends ZfExtended_Worker_Abstract {

    /***
     * @var Reimport
     */
    private Reimport $segmentProcessor;

    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        $neededEntries = ['fileId', 'file', 'userGuid','segmentTimestamp'];
        $foundEntries = array_keys($parameters);
        $keyDiff = array_diff($neededEntries, $foundEntries);
        //if there is not keyDiff all needed were found
        return empty($keyDiff);
    }

    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {

        $params = $this->workerModel->getParameters();

        /** @var editor_Models_Task $task */
        $task = ZfExtended_Factory::get('editor_Models_Task');
        $task->loadByTaskGuid($this->taskGuid);

        /** @var ZfExtended_Models_User $user */
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        $user->loadByGuid($params['userGuid']);


        //contains the TUA which is used to alter the segments
        $tua = $this->prepareTaskUserAssociation($task,$user);

        try {

            Lock::taskLock($task,$task::STATE_REIMPORT);

            /** @var editor_Models_SegmentFieldManager $segmentFieldManager */
            $segmentFieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
            $segmentFieldManager->initFields($task->getTaskGuid());

            /** @var Factory $parserHelper */
            $parserHelper = ZfExtended_Factory::get(Factory::class,[
                $task,
                $segmentFieldManager
            ]);

            // get the parser dynamically even of only xliff is supported
            $parser = $parserHelper->getFileParser($params['fileId'],$params['file']);
            /* @var editor_Models_Import_FileParser $parser */

            if( is_null($parser)){
                throw new \MittagQI\Translate5\Task\Reimport\Exception('E1433',[
                    'file' => $params['fileId'],
                    'task' => $task
                ]);
            }

            $parser->setIsReimport();

            $this->segmentProcessor = ZfExtended_Factory::get(Reimport::class,[ $task , $segmentFieldManager,$user]);
            $this->segmentProcessor->setSegmentFile($params['fileId'], $parser->getFileName());
            $this->segmentProcessor->setSaveTimestamp($params['segmentTimestamp']);

            $parser->addSegmentProcessor($this->segmentProcessor);
            $parser->parseFile();
        } finally {
            //if it was a PM override, delete it again
            if($tua->getIsPmOverride()) {
                $tua->delete();
            }
            $this->segmentProcessor->log();

            $this->archiveImportedData($task);

            Lock::taskUnlock($task);
        }

        return true;
    }


    /**
     * prepares the isPmOverride taskUserAssoc if needed!
     * @param editor_Models_Task $task
     * @param ZfExtended_Models_User $user
     * @return editor_Models_TaskUserAssoc
     * @throws Zend_Acl_Exception
     */
    protected function prepareTaskUserAssociation(editor_Models_Task $task,ZfExtended_Models_User $user): editor_Models_TaskUserAssoc {

        $userTaskAssoc = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var editor_Models_TaskUserAssoc $userTaskAssoc */

        try {

            $acl=ZfExtended_Acl::getInstance();

            $isUserPm=$task->getPmGuid()==$user->getUserGuid();
            $isEditAllAllowed=$acl->isInAllowedRoles($user->getRoles(), 'backend', 'editAllTasks');
            $isEditAllTasks = $isEditAllAllowed || $isUserPm;

            //if the user is allowed to load all, use the default loader
            if($isEditAllTasks){
                $userTaskAssoc = editor_Models_Loaders_Taskuserassoc::loadByTaskForceWorkflowRole($user->getUserGuid(), $task);
            }else{
                $userTaskAssoc = editor_Models_Loaders_Taskuserassoc::loadByTask($user->getUserGuid(), $task);
            }

            $userTaskAssoc->getIsPmOverride();
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e) {

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
     * @param editor_Models_Task $task
     * @return void
     * @throws Exception
     * @throws \editor_Models_Import_DataProvider_Exception
     */
    private function archiveImportedData(editor_Models_Task $task){
        /** @var DataProvider $dp */
        $dp = ZfExtended_Factory::get(DataProvider::class,[
            $this->workerModel->getParameters()['fileId']
        ]);
        $dp->setTaskPaths($task);
        $dp->archiveImportedData();
    }

}