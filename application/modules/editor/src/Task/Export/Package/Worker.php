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

use editor_Models_Export;
use editor_Models_Export_Exception;
use editor_Models_Export_Worker;
use editor_Models_Task;
use editor_Services_TermCollection_Service;
use Throwable;
use Zend_EventManager_StaticEventManager;
use Zend_Registry;
use ZfExtended_Authentication;
use ZfExtended_Factory;
use ZfExtended_Logger;
use ZfExtended_Zendoverwrites_Controller_Action_HelperBroker;

/**
 *
 */
class Worker extends editor_Models_Export_Worker {

    private editor_Models_Task $task;

    /***
     * @param $parameters
     * @return bool
     */
    protected function validateParameters($parameters = array())
    {
        if ( !parent::validateParameters($parameters)){
            return false;
        }
        return !empty($parameters['userGuid']);
    }

    /**
     *
     * @param editor_Models_Task $task
     * @param bool $diff
     * @return string
     */
    public function initExport(editor_Models_Task $task, bool $diff): string
    {
        $this->task = $task;

        /* @var FileStructure $structure */
        $structure = ZfExtended_Factory::get(FileStructure::class,[
            $task
        ]);
        $root = $structure->initFileStructure();

        $this->events->trigger('initPackageFileStructure',$this,[
            'rootFolder' => $root,
            'task' => $task
        ]);

        return $this->initFolderExport($task, $diff, $root);
    }

    /**
     * inits a export to a given directory
     * @param string $taskGuid
     * @param bool $diff
     * @param string $exportFolder
     * @return string the folder which receives the exported data
     */
    public function initFolderExport(editor_Models_Task $task, bool $diff, string $exportFolder) {
        $parameter = [
            'diff' => $diff,
            self::PARAM_EXPORT_FOLDER => $exportFolder,
            'userGuid' => ZfExtended_Authentication::getInstance()->getUser()->getUserGuid()
        ];
        $this->init($task->getTaskGuid(), $parameter);
        return $exportFolder;
    }

    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work(): bool
    {
        //also containing an instance of the initial dataprovider.
        // The Dataprovider can itself hook on to several import events
        $parameters = $this->workerModel->getParameters();

        if( empty($this->task)){
            $this->task = ZfExtended_Factory::get('editor_Models_Task');
            $this->task->loadByTaskGuid($this->taskGuid);
        }

        if(!$this->validateParameters($parameters)) {
            //no separate logging here, missing diff is not possible,
            // directory problems are loggeed above
            return false;
        }

        try {
            $this->exportTask($parameters);
            //$this->exportCollection($parameters);
        }catch (Throwable $throwable){
            $logger = Zend_Registry::get('logger');
            /* @var $logger ZfExtended_Logger */
            $logger->exception($throwable);
            return false;
        }


        // TODO: check the event context. Is this event required for my case ? Are any other events in export ?
        //we should use __CLASS__ here, if not we loose bound handlers to base class in using subclasses
        //$eventManager = ZfExtended_Factory::get('ZfExtended_EventManager', array($exportClass));
        //$eventManager->trigger('afterExport', $this, array('task' => $task, 'parentWorkerId' => $this->workerModel->getId()));
        return true;
    }

    private function exportTask(array $workerParams){

        /* @var FileStructure $structure */
        $structure = ZfExtended_Factory::get(FileStructure::class,[
            $this->task
        ]);

        if(!is_dir($structure->getFilesFolder()) || !is_writable($structure->getFilesFolder())){
            //The task export folder does not exist or is not writeable, no export ZIP file can be created.
            throw new editor_Models_Export_Exception('E1147', [
                'task' => $this->task,
                'exportFolder' => $structure->getFilesFolder(),
            ]);
        }

        // TODO: validate if all all of the files inside the export are allowed

        $export = ZfExtended_Factory::get('editor_Models_Export');
        /* @var editor_Models_Export $export */
        $export->setTaskToExport($this->task, $workerParams['diff']);
        $export->export($structure->getFilesFolder(), $this->workerModel->getId());

    }

    public function exportCollection(array $workerParams){

        /* @var FileStructure $structure */
        $structure = ZfExtended_Factory::get(FileStructure::class,[
            $this->task
        ]);

        $service=ZfExtended_Factory::get('editor_Services_TermCollection_Service');
        /* @var editor_Services_TermCollection_Service $service */
        /** @var TaskAssociation $assoc */
        $assoc = ZfExtended_Factory::get(TaskAssociation::class);
        
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        $user->loadByGuid($workerParams['userGuid']);

        $assocs = $assoc->loadAssocByServiceName($this->taskGuid,$service->getName());

        $export = ZfExtended_Factory::get('editor_Models_Export_Terminology_Tbx');
        $export->setExportAsFile(true);

        $localEncoded = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
            'LocalEncoded'
        );

        $path = $structure->getCollectionFolder();

        foreach ($assocs as $item) {
            $filePath = $localEncoded->encode($path.DIRECTORY_SEPARATOR.$item['languageResourceId'].'.tbx');
            $export->setFile($filePath);
            $export->exportCollectionById($item['languageResourceId'],$user->getUserName());
        }
    }
}