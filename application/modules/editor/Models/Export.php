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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
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
     * @param editor_Models_Task $task
     * @param boolean $diff
     */
    public function setTaskToExport(editor_Models_Task $task, bool $diff) {
        $this->task = $task;
        $this->taskGuid = $task->getTaskGuid();
        Zend_Registry::set('affected_taskGuid', $this->taskGuid); //for TRANSLATE-600 only
        $this->optionDiff = $diff;
    }

    /**
     * exports a task
     * @param string $exportRootFolder
     */
    public function exportToFolder(string $exportRootFolder) {
        $this->_exportToFolder($exportRootFolder);
    }
    
    /**
     * internal method to export a task to a folder
     * @param string $exportRootFolder
     */
    protected function _exportToFolder(string $exportRootFolder) {
        umask(0); // needed for samba access
        if(is_dir($exportRootFolder)) {
            $this->cleaner($exportRootFolder);
        }
        
        if(!file_exists($exportRootFolder) && !@mkdir($exportRootFolder, 0777, true)){
            throw new Zend_Exception(sprintf('Temporary Export Folder could not be created! Task: %s Path: %s', $this->taskGuid, $exportRootFolder));
        }
        
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
    public function exportToZip(string $zipFile) {
        $taskRoot = $this->task->getAbsoluteTaskDataPath();
        $exportRoot = $taskRoot.DIRECTORY_SEPARATOR.$this->taskGuid;
        
        $this->_exportToFolder($exportRoot,false);
        $filter = ZfExtended_Factory::get('Zend_Filter_Compress',array(
            array(
                    'adapter' => 'Zip',
                    'options' => array(
                        'archive' => $zipFile
                    )
                )
            )
        );
        /* @var $filter Zend_Filter_Compress */
        if(!$filter->filter($exportRoot)){
            throw new Zend_Exception('Could not create export-zip of task '.$this->taskGuid.'.');
        }
        $this->cleaner($exportRoot);
    }
    
    protected function cleaner($directory) {
        $recursivedircleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
            'Recursivedircleaner'
        );
        $recursivedircleaner->delete($directory);
    }
}