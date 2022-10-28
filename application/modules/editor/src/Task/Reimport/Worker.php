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
use MittagQI\Translate5\Task\Import\FileParser\FileParserHelper;
use MittagQI\Translate5\Task\Reimport\SegmentProcessor\Reimport;
use SplFileInfo;
use ZfExtended_Acl;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_User;
use ZfExtended_Worker_Abstract;

/**
 * Contains the Excel Reimport Worker
 */
class Worker extends ZfExtended_Worker_Abstract {


    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        $neededEntries = ['fileId', 'file', 'userGuid'];
        $foundEntries = array_keys($parameters);
        $keyDiff = array_diff($neededEntries, $foundEntries);
        //if there is not keyDiff all needed were found
        return empty($keyDiff);
    }

    /**
     * enable direct runs by inheriting public
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::run()
     */
    public function run() {
        //needed since parent is protected by design
        return parent::run();
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

            /** @var editor_Models_SegmentFieldManager $segmentFieldManager */
            $segmentFieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
            $segmentFieldManager->initFields($task->getTaskGuid());

            /** @var FileParserHelper $parserHelper */
            $parserHelper = ZfExtended_Factory::get(FileParserHelper::class,[
                $task,
                $segmentFieldManager
            ]);

            $fileInfo = new SplFileInfo($params['file']);
            // get the parser dynamically even of only xliff is supported
            $parser = $parserHelper->getFileParser($params['fileId'],$fileInfo);

            /** @var Reimport $processor */
            $processor = ZfExtended_Factory::get(Reimport::class,[ $task , $segmentFieldManager]);
            /* @var editor_Models_Import_FileParser $parser */
            $processor->setSegmentFile($params['fileId'], $fileInfo->getBasename());

            $processor->setUser($user);

            $parser->addSegmentProcessor($processor);
            $parser->parseFile();
        }
        finally {
            //if it was a PM override, delete it again
            if($tua->getIsPmOverride()) {
                $tua->delete();
            }
        }

        return true;
    }


    /**
     * prepares the isPmOveride taskUserAssoc if needed!
     * @return editor_Models_TaskUserAssoc
     */
    protected function prepareTaskUserAssociation(editor_Models_Task $task,ZfExtended_Models_User $user): editor_Models_TaskUserAssoc {
        $userTaskAssoc = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $userTaskAssoc editor_Models_TaskUserAssoc */
        try {
            $acl=ZfExtended_Acl::getInstance();
            $isUserPm=$task->getPmGuid()==$user->getUserGuid();
            $isEditAllAllowed=$acl->isInAllowedRoles($user->getRoles(), 'backend', 'editAllTasks');
            $isEditAllTasks = $isEditAllAllowed || $isUserPm;
            //if the user is allowe to load all, use the default loader
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

}