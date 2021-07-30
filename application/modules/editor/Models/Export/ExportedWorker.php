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
 * This class provides a general Worker which can be configured with a callback method which.
 * This class is designed for simple workers which dont need a own full blown worker class.
 *
 * The following parameters are needed:
 * class → the class which should be instanced on work. Classes with Constructor Parameters are currently not supported!
 * method → the class method which is called on work. The method receives the taskguid and the whole parameters array.
 *
 * Be careful: This class can not be used in worker_dependencies !
 */
class editor_Models_Export_ExportedWorker extends ZfExtended_Worker_Abstract {
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        $logger = Zend_Registry::get('logger')->cloneMe('editor.export');
        /* @var $logger ZfExtended_Logger */
        if(empty($parameters['folderToBeZipped'])){
            $logger->error('E1144', 'ExportedWorker: No Parameter "folderToBeZipped" given for worker.');
            return false;
        }
        //if no zipFile given and no waitOnly throw an error
        if(empty($parameters['zipFile']) && empty($parameters['waitOnly'])){
            $logger->error('E1143', 'ExportedWorker: No Parameter "zipFile" given for worker.');
            return false;
        }
        return true;
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        $parameters = $this->workerModel->getParameters();

        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($this->taskGuid);
        
        if(empty($parameters['waitOnly'])) {
            //creates an export.zip if necessary
            $this->exportToZip($task);
        }
        
        $eventManager = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
        $eventManager->trigger('exportCompleted', $this, array('task' => $task, 'parameters' => $parameters));
        return true;
    }
    
    /**
     * Inits the worker in a way to create an export.zip, returns the temp zip name
     * @param string $taskGuid
     * @param string $exportFolder the folder which contains the data to be zipped
     * @return string returns the temp name of the target zip file
     */
    public function initZip($taskGuid, $exportFolder) {
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        $zipFile = tempnam($task->getAbsoluteTaskDataPath(), 'taskExport_');
        $this->init($taskGuid, [
                'folderToBeZipped' => $exportFolder,
                'zipFile' => $zipFile,
        ]);
        return $zipFile;
    }
    
    public function initWaitOnly($taskGuid, $exportFolder) {
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        $this->init($taskGuid, [
                'folderToBeZipped' => $exportFolder,
                'waitOnly' => true,
        ]);
    }
    
    /**
     * exports the task as zipfile export.zip in the taskData if configured
     * @param editor_Models_Task $task
     * @throws editor_Models_Export_Exception
     */
    protected function exportToZip(editor_Models_Task $task): void {
        $params = $this->workerModel->getParameters();
        if(empty($params['folderToBeZipped'])){
            return;
        }
        
        if(!is_dir($params['folderToBeZipped'])) {
            //The task export folder does not exist, no export ZIP file can be created.
            throw new editor_Models_Export_Exception('E1146', [
                'task' => $task,
                'exportFolder' => $params['folderToBeZipped'],
            ]);
        }
        
        
        $filter = ZfExtended_Factory::get('Zend_Filter_Compress',array(
            array(
                    'adapter' => 'Zip',
                    'options' => array(
                        'archive' => $params['zipFile']
                    )
                )
            )
        );
        /* @var $filter Zend_Filter_Compress */
        if(!$filter->filter($params['folderToBeZipped'])){
            //Could not create export-zip.
            throw new editor_Models_Export_Exception('E1145', [
                'task' => $task,
                'exportFolder' => $params['folderToBeZipped'],
            ]);
        }
        
        $recursivedircleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
            'Recursivedircleaner'
        );
        $recursivedircleaner->delete($params['folderToBeZipped']);
    }
}