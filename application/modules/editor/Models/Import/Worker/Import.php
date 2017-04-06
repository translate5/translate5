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

/**
 * Encapsulates the part of the import logic which is intended to be run in a worker
 */
class editor_Models_Import_Worker_Import {
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * @var ZfExtended_Controller_Helper_LocalEncoded
     */
    protected $_localEncoded;

    /**
     * @var ZfExtended_Controller_Helper_General
     */
    protected $gh;

    /**
     * @var editor_Models_Import_MetaData
     */
    protected $metaDataImporter;
    
    /**
     * shared instance over all parse objects of the segment field manager
     * @var editor_Models_SegmentFieldManager
     */
    protected $segmentFieldManager;
    
    /**
     * Counter for number of imported words
     * if set to "false" word-counting will be disabled
     * @var (int) / boolean
     */
    private $wordCount = 0;
    
    /**
     * @var editor_Models_Import_FileList
     */
    protected $filelist;
    
    /**
     * @var ZfExtended_EventManager
     */
    protected $events;

    
    public function __construct() {
        $this->gh = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper('General');
        $this->_localEncoded = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper('LocalEncoded');
        $this->segmentFieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        //we should use __CLASS__ here, if not we loose bound handlers to base class in using subclasses
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
    }
    
    /**
     * starts the main part of the file import which is intended to run in a worker
     * @param string $taskGuid
     * @param editor_Models_Import_Configuration $importConfig
     */
    public function import(editor_Models_Task $task, editor_Models_Import_Configuration $importConfig) {
        $this->task = $task;
        $this->importConfig = $importConfig;
        
        $importConfig->isValid($task->getTaskGuid());
        $this->filelist = ZfExtended_Factory::get('editor_Models_Import_FileList', array($this->importConfig, $this->task));
        
        //down from here should start the import worker
        //in the worker again:
        Zend_Registry::set('affected_taskGuid', $this->task->getTaskGuid()); //for TRANSLATE-600 only
        
        $this->segmentFieldManager->initFields($this->task->getTaskGuid());

        //call import Methods:
        $this->importWithCollectableErrors();
        
        //saving task twice is the simplest way to do this. has meta data is only available after import.
        $this->task->save();
        
        //init default user prefs
        $workflowManager = ZfExtended_Factory::get('editor_Workflow_Manager');
        /* @var $workflowManager editor_Workflow_Manager */
        $workflowManager->initDefaultUserPrefs($this->task);
        
        $this->events->trigger('importCleanup', $this, array('task' => $task));
    }
    
    /**
     * The errors of the import methods called in here, will be collected in check mode
     */
    protected function importWithCollectableErrors() {
        //should errors stop the import, or should they be logged:
        Zend_Registry::set('errorCollect', $this->importConfig->isCheckRun);
        
        $this->importMetaData();
        $this->events->trigger("beforeDirectoryParsing", $this,array('importFolder'=>$this->importConfig->importFolder));
        $this->importFiles();
        $this->syncFileOrder();
        $this->removeMetaDataTmpFiles();
        $this->importRelaisFiles();
        $this->updateSegmentFieldViews();
        
        //disable errorCollecting for post processing
        Zend_Registry::set('errorCollect', false);
    }
    
    /**
     * refreshes / creates the database views for this task
     */
    protected function updateSegmentFieldViews() {
        if(! $this->importConfig->isCheckRun) {
            $this->task->createMaterializedView();
        }
    }
    
    /**
     * Methode zum Anstoßen verschiedener Meta Daten Imports zum Laufenende Import
     */
    protected function importMetaData() {
        $this->metaDataImporter = ZfExtended_Factory::get('editor_Models_Import_MetaData', array($this->importConfig));
        /* @var $this->metaDataImporter editor_Models_Import_MetaData */
        $this->metaDataImporter->import($this->task);
    }

    /**
     * Löscht temporär während des Imports erzeugte Metadaten
     */
    protected function removeMetaDataTmpFiles() {
        $this->metaDataImporter->cleanup();
    }

    /**
     * Importiert die Dateien und erzeugt die Taggrafiken
     */
    protected function importFiles(){
        $filelist = $this->filelist->processProofreadAndReferenceFiles($this->importConfig->getProofReadDir());
        
        $mqmProc = ZfExtended_Factory::get('editor_Models_Import_SegmentProcessor_MqmParser', array($this->task, $this->segmentFieldManager));
        $repHash = ZfExtended_Factory::get('editor_Models_Import_SegmentProcessor_RepetitionHash', array($this->task, $this->segmentFieldManager));
        $segProc = ZfExtended_Factory::get('editor_Models_Import_SegmentProcessor_ProofRead', array($this->task, $this->importConfig));
        /* @var $segProc editor_Models_Import_SegmentProcessor_ProofRead */
        foreach ($filelist as $fileId => $path) {
            if($this->importConfig->isCheckRun){
                trigger_error('Check of File: '.$this->importConfig->importFolder.DIRECTORY_SEPARATOR.$path);
            }
            $params = $this->getFileparserParams($path, $fileId);
            $parser = $this->getFileParser($path, $params);
            /* @var $parser editor_Models_Import_FileParser */
            $segProc->setSegmentFile($fileId, $params[1]); //$params[1] => filename
            $parser->addSegmentProcessor($mqmProc);
            $parser->addSegmentProcessor($repHash);
            $parser->addSegmentProcessor($segProc);
            $parser->parseFile();
            $this->countWords($parser->getWordCount());
        }
        if ($this->task->getWordCount() == 0) {
            $this->task->setWordCount($this->wordCount);
        }
        $mqmProc->handleErrors();
        
        $this->task->setReferenceFiles($this->filelist->hasReferenceFiles());
    }
    
    /**
     * Adds up the number of words of the imported files
     * and saves this into the private variable $this->wordCount
     * 
     * If this function is once called with "false", the addup-process will be canceled for the whole import-process
     * 
     * @param int or boolean false $count
     */
    private function countWords($count)
    {
        if ($count === false) {
            $this->wordCount = false;
        }
        
        if ($this->wordCount !== false) {
            $this->wordCount += $count;
        }
    }
    /**
     * decide regarding to the fileextension, which FileParser should be loaded and return it
     *
     * @param string $path
     * @return editor_Models_Import_FileParser
     * @throws Zend_Exception
     */
    protected function getFileParser(string $path,array $params){
        $ext = preg_replace('".*\.([^.]*)$"i', '\\1', $path);
        try {
            $class = 'editor_Models_Import_FileParser_'.  ucfirst(strtolower($ext));
            $parser = ZfExtended_Factory::get($class,$params);
            /* var $parser editor_Models_Import_FileParser */
            $parser->setSegmentFieldManager($this->segmentFieldManager);
            return $parser;
        } catch (ReflectionException $e) {
            if(strpos($e->getMessage(), 'Class '.$class.' does not exist') !== false){
                throw new Zend_Exception('For the fileextension '.$ext. ' no parser is registered. (Class '.$class.' not found).',0,$e);
            }
            throw $e;
        }
    }
    
    /**
     * Importiert die Relais Dateien
     * @param editor_Models_RelaisFoldertree $tree
     */
    protected function importRelaisFiles(){
        if(! $this->importConfig->hasRelaisLanguage()){ 
            return;
        }
        
        $relayFiles = $this->filelist->processRelaisFiles();
        
        $mqmProc = ZfExtended_Factory::get('editor_Models_Import_SegmentProcessor_MqmParser', array($this->task, $this->segmentFieldManager));
        $repHash = ZfExtended_Factory::get('editor_Models_Import_SegmentProcessor_RepetitionHash', array($this->task, $this->segmentFieldManager));
        $segProc = ZfExtended_Factory::get('editor_Models_Import_SegmentProcessor_Relais', array($this->task, $this->segmentFieldManager));
        /* @var $segProc editor_Models_Import_SegmentProcessor_Relais */
        foreach ($relayFiles as $fileId => $path) {
            if($this->importConfig->isCheckRun){
                    trigger_error('Check of Relais File: '.$this->importConfig->importFolder.DIRECTORY_SEPARATOR.$path);
            }
            $params = $this->getFileparserParams($path, $fileId);
            $parser = $this->getFileParser($path, $params);
            /* @var $parser editor_Models_Import_FileParser */
            $segProc->setSegmentFile($fileId, $params[1]);  //$params[1] => filename
            $parser->addSegmentProcessor($mqmProc);
            $parser->addSegmentProcessor($repHash);
            $parser->addSegmentProcessor($segProc);
            $parser->parseFile();
    	}
        $mqmProc->handleErrors();
    }
    
    /**
     * Erzeugt die Parameter für den Fileparser Konstruktor als Array
     * @return array
     */
    protected function getFileparserParams($path, $fileId) {
        return array(
            $this->importConfig->importFolder.DIRECTORY_SEPARATOR.$this->_localEncoded->encode($path),
            $this->gh->basenameLocaleIndependent($path),
            $fileId, 
            $this->task,
        );
    }
    
    protected function syncFileOrder() {
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        //dont update view here, since it is not existing yet!
        $segment->syncFileOrderFromFiles($this->task->getTaskGuid(), true); 
    }
}