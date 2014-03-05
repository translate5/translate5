<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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
     * führt den Export aller Dateien eines Task durch
     * @param string $exportRootFolder
     * @param boolean $unsetExportRunningStamp, default true
     */
    public function exportToFolder(string $exportRootFolder,$unsetExportRunningStamp = true) {
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
            file_put_contents($path,$parser->getFile());
        }
        if($unsetExportRunningStamp)
            $this->unsetExportRunningStamp();
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
            return ZfExtended_Factory::get('editor_Models_Export_FileParser_'.  ucfirst(strtolower($ext)),array($fileId,$this->optionDiff,  $this->task));
            
        } catch (Exception $e) { 
            throw new Zend_Exception('For the fileextension '.$ext. ' no parser is registered.',0,$e);
        }
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
        $this->exportToFolder($exportRoot,false);
        $zipFile = $taskRoot.DIRECTORY_SEPARATOR.'export.zip';
        $filter = new Zend_Filter_Compress(array(
            'adapter' => 'Zip',
            'options' => array(
                'archive' => $zipFile
            ),
        ));
        if(!$filter->filter($exportRoot)){
            throw new Zend_Exception('Could not create export-zip of task '.$this->taskGuid.'.');
        }
        $recursivedircleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
            'Recursivedircleaner'
        );
        $recursivedircleaner->delete($exportRoot);
        $this->unsetExportRunningStamp();
        return $zipFile;
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