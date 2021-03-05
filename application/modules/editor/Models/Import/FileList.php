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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

/**
 * Import class to encapsulated all file / directory related stuff. 
 * This is: 
 * - reading the review tree and save it to the DB 
 * - reading the reference files tree and save it to the DB 
 * - reading the relais files tree 
 * - return a list of review and relais files for the importer
 */
class editor_Models_Import_FileList {
    
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * @var editor_Models_Import_Configuration
     */
    protected $importConfig;
    
    /**
     * @var editor_Models_Foldertree
     */
    protected $treeDb;
    
    public function __construct(editor_Models_Import_Configuration $importConfig, editor_Models_Task $task) {
        $this->importConfig = $importConfig;
        $this->task = $task;
    }
    
    /**
     * Find all review files and stores them as foldertree, 
     * syncs the review files then as plain file entities
     * returns a file list with files to be imported
     */
    public function processReviewedFiles() {
        $parser = ZfExtended_Factory::get('editor_Models_Import_DirectoryParser_WorkingFiles', [$this->importConfig->checkFileType, $this->importConfig->ignoredUncheckedExtensions]);
        /* @var $parser editor_Models_Import_DirectoryParser_WorkingFiles */
        $tree = $parser->parse($this->importConfig->getReviewDir(), $this->task);
        $notImportedFiles = $parser->getNotImportedFiles();
        if(!empty($notImportedFiles)) {
            $logger = Zend_Registry::get('logger');
            /* @var $logger ZfExtended_Logger */
            $logger->warn('E1136', 'Some files could not be imported, since there is no parser available. For affected files see log details.', [
                'files' => $notImportedFiles,
            ]);
        }
        
        $this->treeDb = ZfExtended_Factory::get('editor_Models_Foldertree');
        /* @var $treeDb editor_Models_Foldertree */
        $this->treeDb->setTree($tree);
        $this->treeDb->setTaskGuid($this->task->getTaskGuid());
        $this->saveAndSyncFileTree();
        
        return $this->treeDb->getPaths($this->task->getTaskGuid(),'file');
    }
    
    /**
     * Find all reference files and stores them as foldertree, 
     */
    public function processReferenceFiles() {
        if(!$this->hasReferenceFiles()){
            return;
        }
        $this->treeDb = ZfExtended_Factory::get('editor_Models_Foldertree');
        $this->treeDb->loadByTaskGuid($this->task->getTaskGuid());
        $this->treeDb->setReferenceFileTree($this->getReferenceFileTree());
        $this->treeDb->save();
    }
    
    /**
     * saves the file tree and syncs the filetree to the file table
     */
    public function saveAndSyncFileTree() {
        $config = $this->importConfig;
        $params = array($this->treeDb, $config->getLanguageId('source'), $config->getLanguageId('target'), $config->getLanguageId('relais'));
        $sync = ZfExtended_Factory::get('editor_Models_Foldertree_SyncToFiles', $params);
        /* @var $sync editor_Models_Foldertree_SyncToFiles */
        $sync->recursiveSync();
        $this->treeDb->save();
    }
    
    /**
     * Saves the reference files, and generates a file tree out of the reference files folder
     * returns the Tree as JSON string
     * @return string
     */
    protected function getReferenceFileTree() {
        $config = Zend_Registry::get('config');
        $refTarget = $this->getAbsReferencePath();
        $refDir = $config->runtimeOptions->import->referenceDirectory;
        $refAbsDir = $this->importConfig->importFolder.DIRECTORY_SEPARATOR.$refDir;
        ZfExtended_Utils::recursiveCopy($refAbsDir, $refTarget);
    
        $parser = ZfExtended_Factory::get('editor_Models_Import_DirectoryParser_ReferenceFiles');
        /* @var $parser editor_Models_Import_DirectoryParser_ReferenceFiles */
        return $parser->parse($refTarget, $this->task);
    }
    
    /**
     * returns the absolute path to the tasks folder for reference files
     */
    protected function getAbsReferencePath() {
        $config = Zend_Registry::get('config');
        return $this->task->getAbsoluteTaskDataPath().DIRECTORY_SEPARATOR.$config->runtimeOptions->import->referenceDirectory;
    }
    
    public function processRelaisFiles() {
        $tree = ZfExtended_Factory::get('editor_Models_RelaisFoldertree');
        /* @var $tree editor_Models_RelaisFoldertree */
        $tree->setImportConfig($this->importConfig);
        $tree->getPaths($this->task->getTaskGuid(),'file'); //Aufruf nötig, er initialisiert den Baum
        $relaisFiles = $tree->checkAndGetRelaisFiles($this->importConfig->importFolder);
        $tree->save();
        return $relaisFiles;
    }
    
    /**
     * returns if reference files has to be imported
     * @return boolean
     */
    public function hasReferenceFiles() {
        $config = Zend_Registry::get('config');
        //If no review directory is set, the reference files must be ignored  
        $workfilesDirectory = $this->importConfig->getFilesDirectory();
        $refDir = $config->runtimeOptions->import->referenceDirectory;
        return !empty($workfilesDirectory) && is_dir($this->importConfig->importFolder.DIRECTORY_SEPARATOR.$refDir);
    }
}
