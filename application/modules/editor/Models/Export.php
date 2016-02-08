<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

 /**
 * Kapselt den Export Mechanismus
 *
 */

class editor_Models_Export {
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * @var string
     */
    protected $taskGuid;
    
    /**
     * @var boolean
     */
    protected $optionDiff;

    /**
     * 
     * @param editor_Models_Task $task
     * @param boolean $diff
     * @param boolean $setExportRunningStamp, default true
     * @return boolean
     */
    public function setTaskToExport(editor_Models_Task $task, boolean $diff, 
            $setExportRunningStamp = true) {
        $this->task = $task;
        $this->taskGuid = $task->getTaskGuid();
        Zend_Registry::set('affected_taskGuid', $this->taskGuid); //for TRANSLATE-600 only
        $this->optionDiff = $diff;
        return (!$this->exportFolderExists() && (!$setExportRunningStamp || $this->setExportRunningStamp()));
    }
    /**
     * sets a timestamp in LEK_task for the task, if timestamp column is null
     * @return boolean
     * @throws Zend_Exception
     */
    protected function setExportRunningStamp() {
        $rowsUpdated = $this->task->db->update(array('exportRunning'=>  date('Y-m-d H:i:s',time())), 
                array('taskGuid = ? and exportRunning is null'=>$this->taskGuid));
        if($rowsUpdated===0)return false;
        if($rowsUpdated===1)return true;
        throw new Zend_Exception(
                'More then 1 row updated when setExportRunningStamp in LEK_task. Number or rows updated for task '.
            $this->taskGuid.' : '.$rowsUpdated);
    }
    /**
     * unsets a timestamp (sets it to NULL) in LEK_task for the task, if timestamp column is not null
     * @return boolean
     * @throws Zend_Exception
     */
    protected function unsetExportRunningStamp() {
        $rowsUpdated = $this->task->db->update(array('exportRunning'=>  NULL), 
                array('taskGuid = ? and exportRunning is not null'=> $this->taskGuid));
        if($rowsUpdated===0)return false;
        if($rowsUpdated===1)return true;
        throw new Zend_Exception('More then 1 row updated when unsetExportRunningStamp', 
                'Number or rows updated for task '.
            $this->taskGuid.' : '.$rowsUpdated);
    }


    /**
     * exports a task
     * @param string $exportRootFolder
     * @param boolean $unsetExportRunningStamp, default true
     */
    public function exportToFolder(string $exportRootFolder, $unsetExportRunningStamp = true) {
        $this->_exportToFolder($exportRootFolder, $unsetExportRunningStamp);
        $this->startExportedWorker();
    }
    
    /**
     * internal method to export a task to a folder
     * @param string $exportRootFolder
     * @param string $unsetExportRunningStamp
     */
    public function _exportToFolder(string $exportRootFolder, $unsetExportRunningStamp) {
        $this->exportFolderExists(true);
        $session = new Zend_Session_Namespace();
        $treeDb = ZfExtended_Factory::get('editor_Models_Foldertree');
        /* @var $treeDb editor_Models_Foldertree */
        $treeDb->setPathPrefix('');
        $dirPaths = $treeDb->getPaths($this->taskGuid,'dir');
        $filePaths = $treeDb->getPaths($this->taskGuid,'file');
        $localEncoded = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
            'LocalEncoded'
        );
        sort($dirPaths);
        foreach ($dirPaths as $path) {
            $path = $localEncoded->encode($path);
            $path = $exportRootFolder.DIRECTORY_SEPARATOR.$path;
            mkdir($path);
        }
        foreach ($filePaths as $fileId => $path) {
            $path = $localEncoded->encode($path);
            $path = $exportRootFolder.DIRECTORY_SEPARATOR.$path;
            $parser = $this->getFileParser((int)$fileId, $path);
            /* @var $parser editor_Models_Export_FileParser */
            $parser->saveFile();
        }
        if($unsetExportRunningStamp) {
            $this->unsetExportRunningStamp();
        }
        //we should use __CLASS__ here, if not we loose bound handlers to base class in using subclasses
        $eventManager = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
        $eventManager->trigger('afterExport', $this, array('task' => $this->task));
    }
    
    /**
     * decide regarding to the fileextension, which FileParser should be loaded and return it
     *
     * @param int $fileId
     * @param string $path
     * @return editor_Models_Import_FileParser
     * @throws Zend_Exception
     */
    protected function getFileParser(integer $fileId,string $path){
       $ext = preg_replace('".*\.([^.]*)$"i', '\\1', $path);
       
       try {
           return ZfExtended_Factory::get('editor_Models_Export_FileParser_'.ucfirst(strtolower($ext)), array($fileId, $this->optionDiff,  $this->task, $path));
           
        } catch (Exception $e) { 
            throw new Zend_Exception('For the fileextension '.$ext. ' no parser is registered.',0,$e);
        }
    }
    
    /**
     * returns a fileparser for the given task and filename
     * @param editor_Models_Task $task
     * @param string $filename
     * @return editor_Models_Import_FileParser
     */
    public function getFileParserForXmlList(editor_Models_Task $task, $filename) {
        $this->task = $task;
        $this->taskGuid = $task->getTaskGuid();
        $this->optionDiff = false;
        return $this->getFileParser(0, $filename);
    }
    
    /**
     * exports the task as zipfile export.zip in the taskData
     * returns the path to the generated Zip File
     * @return string
     */
    public function exportToZip() {
        $this->exportFolderExists(true);
        $taskRoot = $this->task->getAbsoluteTaskDataPath();
        $exportRoot = $taskRoot.DIRECTORY_SEPARATOR.$this->taskGuid;
        if(!file_exists($exportRoot) && !@mkdir($exportRoot, 0777, true)){
            throw new Zend_Exception(sprintf('Temporary Export Folder could not be created! Task: %s Path: %s', $this->taskGuid, $exportRoot));
        }
        $this->_exportToFolder($exportRoot,false);
        $zipFile = $taskRoot.DIRECTORY_SEPARATOR.'export.zip';
        $filter = ZfExtended_Factory::get('Zend_Filter_Compress',array(
            array(
                    'adapter' => 'Zip',
                    'options' => array(
                        'archive' => $zipFile
                    ),
                )
            )
        );
        if(!$filter->filter($exportRoot)){
            throw new Zend_Exception('Could not create export-zip of task '.$this->taskGuid.'.');
        }
        $recursivedircleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
            'Recursivedircleaner'
        );
        $recursivedircleaner->delete($exportRoot);
        $this->unsetExportRunningStamp();
        
        $this->startExportedWorker();
        
        return $zipFile;
    }
    
    /**
     * Starts the final worker which runs after every export related work
     */
    protected function startExportedWorker() {
        $worker = ZfExtended_Factory::get('editor_Models_Export_ExportedWorker');
        /* @var $worker editor_Models_Export_ExportedWorker */
        $worker->init($this->task->getTaskGuid());
        $worker->queue();
    }
    
    /**
     * 
     * @param boolean $throwException if folder exists
     * @return boolean
     */
    protected function exportFolderExists($throwException = false) {
        $taskRoot = $this->task->getAbsoluteTaskDataPath();
        $exportRoot = $taskRoot.DIRECTORY_SEPARATOR.$this->taskGuid;
        if(file_exists($exportRoot) && count(scandir($exportRoot)) > 2){
            if($throwException){
                throw new Zend_Exception(sprintf('Temporary Export Folder already exists! Task: %s Path: %s', $this->taskGuid, $exportRoot));
            }
            return true;
        }
        return false;
    }
}