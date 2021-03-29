<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Contains the Export Worker (the scheduling parts)
 * The export process itself is encapsulated in editor_Models_Export
 * The worker nows three parameters:
 *  "diff" boolean en- or disables the export differ
 *  "method" string is either "exportToZip" [default] or "exportToFolder"
 *  "exportToFolder" string valid writable path to the export folder, only needed for method "exportToFolder"
 */
class editor_Models_Export_Worker extends ZfExtended_Worker_Abstract {
    CONST PARAM_EXPORT_FOLDER = 'exportToFolder';
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        if(!isset($parameters['diff']) || !is_bool($parameters['diff'])) {
            return false;
        }
        return true;
    }
    
    /**
     * inits a export to the default directory
     * @param string $taskGuid
     * @param bool $diff
     * @return string the folder which receives the exported data
     */
    public function initExport(editor_Models_Task $task, bool $diff) {
        //if no explicit exportToFolder is given, we use the default (taskGuid)
        $default = $task->getAbsoluteTaskDataPath().DIRECTORY_SEPARATOR.$task->getTaskGuid();
        is_dir($default) || @mkdir($default); //we create it if it does not exist
        return $this->initFolderExport($task, $diff, $default);
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
            self::PARAM_EXPORT_FOLDER => $exportFolder
        ];
        $this->init($task->getTaskGuid(), $parameter);
        return $exportFolder;
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        //also containing an instance of the initial dataprovider.
        // The Dataprovider can itself hook on to several import events
        $parameters = $this->workerModel->getParameters();
        
        if(!$this->validateParameters($parameters)) {
            //no separate logging here, missing diff is not possible,
            // directory problems are loggeed above
            return false;
        }
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($this->taskGuid);
        
        if(!is_dir($parameters[self::PARAM_EXPORT_FOLDER]) || !is_writable($parameters[self::PARAM_EXPORT_FOLDER])){
            //The task export folder does not exist or is not writeable, no export ZIP file can be created.
            throw new editor_Models_Export_Exception('E1147', [
                'task' => $task,
                'exportFolder' => $parameters[self::PARAM_EXPORT_FOLDER],
            ]);
        }
        
        $exportClass = 'editor_Models_Export';
        $export = ZfExtended_Factory::get($exportClass);
        /* @var $export editor_Models_Export */
        $export->setTaskToExport($task, $parameters['diff']);
        $export->export($parameters[self::PARAM_EXPORT_FOLDER], $this->workerModel->getId());
        
        //we should use __CLASS__ here, if not we loose bound handlers to base class in using subclasses
        $eventManager = ZfExtended_Factory::get('ZfExtended_EventManager', array($exportClass));
        $eventManager->trigger('afterExport', $this, array('task' => $task, 'parentWorkerId' => $this->workerModel->getId()));
        return true;
    }
    
    /***
     * The export takes approximately 2% from the import(file pre-translation)
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::getWeight()
     */
    public function getWeight() {
        return 2;
    }
}