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
 * Kapselt den Import Mechanismus
 */
class editor_Models_Import {
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
     * @var ZfExtended_EventManager
     */
    protected $events = false;
    
    /**
     * @var editor_Models_Import_FileList
     */
    protected $filelist;

    /**
     * 
     * @var editor_Models_Import_Configuration
     */
    protected $importConfig;
    
    /**
     * Konstruktor
     */
    public function __construct(){
        $this->gh = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper('General');
        $this->_localEncoded = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper('LocalEncoded');
        $this->segmentFieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(get_class($this)));
        
        $this->importConfig = ZfExtended_Factory::get('editor_Models_Import_Configuration');
    }
    
    /**
     * sets the Importer to check mode: additional debug output on import
     * does not effect pre import checks
     * @param boolean $check optional, per default true 
     */
    public function setCheck($check = true){
        $this->importConfig->isCheckRun = $check;
    }
    
    /**
     * führt den Import aller Dateien eines Task durch
     * @param string $importFolderPath
     */
    public function import(editor_Models_Import_DataProvider_Abstract $dataProvider) {
        if(empty($this->task)){
            throw new Zend_Exception('taskGuid not set - please set using $this->setTask/$this->createTask');
        }
        Zend_Registry::set('affected_taskGuid', $this->task->getTaskGuid()); //for TRANSLATE-600 only
        
        //pre import methods:
        $dataProvider->setTask($this->task);
        $dataProvider->checkAndPrepare();
        $this->importConfig->importFolder = $dataProvider->getAbsImportPath();
        
        //FIXME taskGuid validation needed there?
        $this->importConfig->isValid($this->task->getTaskGuid());
        
        if(! $this->importConfig->hasRelaisLanguage()) {
            //@todo in new rest api and / or new importwizard show ereror, if no relaislang is set, but relais data is given or viceversa (see translate5 featurelist)
            
            //reset given relais language value if no relais data is provided / feature is off
            $this->task->setRelaisLang(0); 
        }
        
        $this->task->save(); //Task erst Speichern wenn die obigen validates und checks durch sind.
        $this->task->lock(NOW_ISO, true); //locks the task
        
//FIXME errors until here should result in a error for the GUI

//HERE code from down of here goes completly into the worker. 
// importConfig is filled and validated above. 
// The worker receives a serialized version of importConfig, 
// perhaps we have to make magic methods to destory and load the language instances
// also bring up the termtagger on the laptop to test the import with termtagging
// also intersting will be the question how the new import will interact with worker dependencies

        $this->filelist = ZfExtended_Factory::get('editor_Models_Import_FileList', array($this->importConfig, $this->task));
        
        //down from here should start the import worker
        //in the worker again:
        Zend_Registry::set('affected_taskGuid', $this->task->getTaskGuid()); //for TRANSLATE-600 only
        
        $this->segmentFieldManager->initFields($this->task->getTaskGuid());
        
        //call import Methods:
        $this->importWithCollectableErrors();
        
        //saving task twice is the simplest way to do this. has meta data is only available after import.
        $this->task->save();
        
        //call post import Methods:
        $dataProvider->postImportHandler();
        //we should use __CLASS__ here, if not we loose bound handlers to base class in using subclasses
        $eventManager = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
        $eventManager->trigger('afterImport', $this, array('task' => $this->task));
        
        $worker = ZfExtended_Factory::get('editor_Models_Import_Worker_SetTaskToOpen');
        /* @var $worker editor_Models_Import_Worker_SetTaskToOpen */
        $worker->init($this->task->getTaskGuid());
        $worker->queue();
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
     * Handler of Import Exceptions
     * We delete the task from database, the import directory remains on the disk,
     * if runtimeOptions.import.keepFilesOnError is set to true (for developing mainly)
     * @param Exception $e
     * @param editor_Models_Import_DataProvider_Abstract $dataProvider
     */
    public function handleImportException(Exception $e, editor_Models_Import_DataProvider_Abstract $dataProvider) {
        $config = Zend_Registry::get('config');
        //delete task but keep taskfolder if configured, on checkRun never keep files
        $deleteFiles = $this->importConfig->isCheckRun || !$config->runtimeOptions->import->keepFilesOnError;
        
        $log = ZfExtended_Factory::get('ZfExtended_Log');
        /* @var $log ZfExtended_Log */
        $msg = "\nImport Exception: ".$e."\n";
        if(!$deleteFiles) {
            $msg .= "\n".'The imported data is kept in '.$config->runtimeOptions->dir->taskData;
        }
        $log->logError('Exception while importing task '.$this->task->getTaskGuid(), $msg);
        
        $remover = ZfExtended_Factory::get('editor_Models_Task_Remover', array($this->task));
        /* @var $remover editor_Models_Task_Remover */
        $remover->removeForced($deleteFiles);
        if($deleteFiles) {
            $dataProvider->handleImportException($e);
        }
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

    /**
     * sets the info/data to the user
     * @param string $userguid
     * @param string $username
     */
    public function setUserInfos(string $userguid, string $username) {
        $this->importConfig->userName = $username;
        $this->importConfig->userGuid = $userguid;
    }

    /**
     * sets a optional taskname and options of the imported task
     * returns the created task
     * Current Options: 
     *   enableSourceEditing => boolean
     * @param stdClass $params
     * @return editor_Models_Task
     */
    public function createTask(stdClass $params) {
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->setTaskName($params->taskName);
        $task->setTaskGuid($params->taskGuid);
        $task->setPmGuid($params->pmGuid);
        $task->setEdit100PercentMatch((int)$params->editFullMatch);
        $task->setLockLocked((int)$params->lockLocked);
        
        $pm = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $pm ZfExtended_Models_User */
        try {
            $pm->loadByGuid($params->pmGuid);
            $task->setPmName($pm->getUsernameLong());
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e){
            $task->setPmName('- not found -');
        }
        
        $task->setTaskNr($params->taskNr);
        
        $sourceId = empty($this->importConfig->sourceLang) ? 0 : $this->importConfig->sourceLang->getId();
        $task->setSourceLang($sourceId);
        $targetId = empty($this->importConfig->targetLang) ? 0 : $this->importConfig->targetLang->getId();
        $task->setTargetLang($targetId);
        $relaisId = empty($this->importConfig->relaisLang) ? 0 : $this->importConfig->relaisLang->getId();
        $task->setRelaisLang($relaisId);
        
        $task->setWorkflow($params->workflow);
        $task->setWordCount($params->wordCount);
        $task->setTargetDeliveryDate($params->targetDeliveryDate);
        $task->setOrderdate($params->orderDate);
        $config = Zend_Registry::get('config');
        //Task based Source Editing can only be enabled if its allowed in the whole editor instance 
        $enableSourceEditing = (bool) $config->runtimeOptions->import->enableSourceEditing;
        $task->setEnableSourceEditing(! empty($params->enableSourceEditing) && $enableSourceEditing);
        $task->validate();
        $this->setTask($task);
        return $task;
    }
    
    /**
     * sets the internal needed Task, inits the Task Directory
     * @param editor_Models_Task $task
     */
    public function setTask(editor_Models_Task $task) {
        $this->task = $task;
        $this->task->initTaskDataDirectory();
    }

    /**
     * Setzt die zu importierende Quell und Zielsprache, das Format der Sprach IDs wird über den Parameter $type festgelegt
     * @param mixed $source
     * @param mixed $target
     * @param mixed $relais Relaissprache, kann null/leer sein wenn es keine Relaissprache gibt
     * @param string $type
     */
    public function setLanguages($source, $target, $relais, $type = editor_Models_Languages::LANG_TYPE_RFC5646) {
        $this->importConfig->setLanguages($source, $target, $relais, $type);
    }
}
