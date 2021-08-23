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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

/**
 * entrypoint for the export
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
     * @var ZfExtended_EventManager
     */
    protected $events;
    
    public function __construct() {
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
    }
    
    /**
     * @param editor_Models_Task $task
     * @param bool $diff
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
     * @param int $workerId
     */
    public function export(string $exportRootFolder, $workerId) {
        umask(0); // needed for samba access
        if(is_dir($exportRootFolder)) {
            $recursivedircleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
                'Recursivedircleaner'
            );
            $recursivedircleaner->delete($exportRootFolder);
        }
        
        if(!file_exists($exportRootFolder) && !@mkdir($exportRootFolder, 0777, true)){
            throw new Zend_Exception(sprintf('Temporary Export Folder could not be created! Task: %s Path: %s', $this->taskGuid, $exportRootFolder));
        }
        
        $treeDb = ZfExtended_Factory::get('editor_Models_Foldertree');
        /* @var $treeDb editor_Models_Foldertree */
        $treeDb->setPathPrefix('');
        $dirPaths = $treeDb->getPaths($this->taskGuid,'dir');
        $filePaths = $treeDb->getPaths($this->taskGuid,'file');
        $localEncoded = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
            'LocalEncoded'
        );
        
        $fileFilter = ZfExtended_Factory::get('editor_Models_File_FilterManager');
        /* @var $fileFilter editor_Models_File_FilterManager */
        $fileFilter->initExport($this->task, $workerId);
        
        sort($dirPaths);
        foreach ($dirPaths as $path) {
            $path = $localEncoded->encode($path);
            $path = $exportRootFolder.DIRECTORY_SEPARATOR.$path;
            mkdir($path);
        }
        $log = Zend_Registry::get('logger')->cloneMe('editor.export');
        $segmentErrors = [];
        foreach ($filePaths as $fileId => $relPath) {
            $path = $localEncoded->encode($relPath);
            $path = $exportRootFolder.DIRECTORY_SEPARATOR.$path;
            $parser = $this->getFileParser((int)$fileId, $path);
            /* @var $parser editor_Models_Export_FileParser */
            if(empty($parser)) {
                $log = Zend_Registry::get('logger')->cloneMe('editor.export');
                $log->warn('E1157', 'Export: the file "{file}" could not be exported, since it had possibly already errors on import.', [
                    'task' => $this->task,
                    'file' => $relPath,
                ]);
                continue;
            }
            $parser->saveFile();
            $errors = $parser->getSegmentTagErrors();
            if(!empty($errors)) {
                $segmentErrors[$fileId.' # '.$relPath] = $errors;
            }
            
            $fileFilter->applyExportFilters($path, $fileId);
        }
        
        if(empty($segmentErrors)) {
            return;
        }
        $log = Zend_Registry::get('logger')->cloneMe('editor.export');
        $log->warn('E1149', 'Export: Some segments contain tag errors [Task '.$this->task->getTaskGuid().' "'.$this->task->getTaskName().'"].', [
            'task' => $this->task,
            'segments' => $segmentErrors,
        ]);
    }
    
    /**
     * decide regarding to the fileextension, which FileParser should be loaded and return it.
     *  Returns null if no fileparser was stored to the file. This can happen on errors in preprocessing of files without a native file parser.
     *
     * @param int $fileId
     * @param string $path
     * @return editor_Models_Import_FileParser|null
     */
    protected function getFileParser(int $fileId, string $path){
        $file = ZfExtended_Factory::get('editor_Models_File');
        /* @var $file editor_Models_File */
        $file->load($fileId);
        $exportParser = str_replace('_Import_', '_Export_', $file->getFileParser());
        if(empty($exportParser) || !class_exists($exportParser)) {
            return null;
        }
        
        //put the fileparser config into an object, so that it can be manipulated in the event handlers
        $fpConfig = new stdClass();
        $fpConfig->path = $path;
        $fpConfig->options = ['diff' => $this->optionDiff];
        $fpConfig->exportParser = $exportParser;
        
        $this->events->trigger('exportFileParserConfiguration', $this, [
            'task' => $this->task,
            'file' => $file,
            'config' => $fpConfig,
        ]);
        
        return ZfExtended_Factory::get($fpConfig->exportParser, [
            $this->task, $fileId,
            $fpConfig->path,
            $fpConfig->options
        ]);
    }
}