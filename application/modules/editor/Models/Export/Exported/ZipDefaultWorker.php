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
 * Zip the directory where the files were exported
 */
class editor_Models_Export_Exported_ZipDefaultWorker extends editor_Models_Export_Exported_Worker {

    /**
     * @inheritdoc
     */
    protected function validateParameters($parameters = array()) {

        // Get logger
        $logger = Zend_Registry::get('logger')->cloneMe('editor.export');
        /* @var $logger ZfExtended_Logger */

        // If no folderToBeZipped-param given - log error and return false
        if (empty($parameters['folderToBeZipped'])) {
            $logger->error('E1144', 'Exported_Worker: No Parameter "folderToBeZipped" given for worker.');
            return false;
        }

        // If no zipFile-param given - log error and return false
        if (empty($parameters['zipFile'])) {
            $logger->error('E1143', 'Exported_Worker: No Parameter "zipFile" given for worker.');
            return false;
        }

        // Return true
        return true;
    }

    /**
     * Inits the worker in a way to create an export.zip, returns the temp zip name
     * @param string $taskGuid
     * @param array $parameters
     * @return string returns the temp name of the target zip file
     */
    public function setup($taskGuid = null, $parameters = []) {

        /* @var $task editor_Models_Task */
        $task = ZfExtended_Factory::get('editor_Models_Task');

        // Load task
        $task->loadByTaskGuid($taskGuid);

        // Create temporary file for writing zipped contents
        $zipFile = tempnam($task->getAbsoluteTaskDataPath(), 'taskExport_');

        // Call parent
        $this->init($taskGuid, [
            'folderToBeZipped' => $parameters['exportFolder'],
            'zipFile' => $zipFile,
        ]);

        // Return zipFile
        return $zipFile;
    }

    /**
     * Exports the task as zipfile export.zip in the taskData if configured
     *
     * @param editor_Models_Task $task
     * @throws editor_Models_Export_Exception
     */
    protected function doWork(editor_Models_Task $task): void {

        // Get params
        $params = $this->workerModel->getParameters();

        // If folderToBeZipped-param si empty - return
        if (empty($params['folderToBeZipped'])) {
            return;
        }

        // Else if such dir does not exist, no export ZIP file can be created, so throw an exception
        if (!is_dir($params['folderToBeZipped'])) {
            throw new editor_Models_Export_Exception('E1146', [
                'task' => $task,
                'exportFolder' => $params['folderToBeZipped'],
            ]);
        }

        // Create compress-filter instance
        $filter = ZfExtended_Factory::get('Zend_Filter_Compress', [[
            'adapter' => 'Zip',
            'options' => [
                'archive' => $params['zipFile']
            ]
        ]]);

        /* @var $filter Zend_Filter_Compress */
        // Attempt to compress, and if failed - throw and exception
        if (!$filter->filter($params['folderToBeZipped'])) {
            throw new editor_Models_Export_Exception('E1145', [
                'task' => $task,
                'exportFolder' => $params['folderToBeZipped'],
            ]);
        }

        // Drop folderToBeZipped
        ZfExtended_Zendoverwrites_Controller_Action_HelperBroker
            ::getStaticHelper('Recursivedircleaner')
            ->delete($params['folderToBeZipped']);
    }
}