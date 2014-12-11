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
 * Kapselt den Import Mechanismus
 */
class editor_Models_Import {
    /**
     * @var string GUID
     */
    protected $_taskGuid = NULL;
    /**
     * @var editor_Models_Task
     */
    protected $task;
    /**
     * @var string GUID
     */
    protected $_userGuid = NULL;
    /**
     * @var string
     */
    protected $_userName = NULL;
    /**
     * @var array array(fileId => 'filePath',...)
     */
    protected $_filePaths = array();
    /**
     * @var editor_Models_Languages Entity Instanz der Sprache
     */
    protected $_sourceLang = NULL;
    /**
     * @var editor_Models_Languages Entity Instanz der Sprache
     */
    protected $_targetLang = NULL;
    /**
     * @var editor_Models_Languages Entity Instanz der Sprache
     */
    protected $_relaisLang = NULL;
    /**
     * konkreter angeforderte Quell Sprache (Für Ausgabe bei einem Fehler)
     * @var mixed
     */
    protected $_sourceLangValue = NULL;
    /**
     * konkreter angeforderte Ziel Sprache (Für Ausgabe bei einem Fehler)
     * @var mixed
     */
    protected $_targetLangValue = NULL;
    /**
     * konkreter angeforderte Relais Sprache (Für Ausgabe bei einem Fehler)
     * @var mixed
     */
    protected $_relaisLangValue = NULL;

    /**
     * @var boolean legt für die aktuelle Fileparser-Instanz fest, ob 100-Matches
     *              editiert werden dürfen (true) oder nicht (false)
     *              Übergabe in URL: false wird bei Übergabe von 0 oder leer-String gesetzt, sonst true
     */
    public $_edit100PercentMatches = false;
    /**
     * @var string import folder, under which the to be imported folder and file hierarchy resides
     */
    protected $_importFolder = NULL;
    /**
     * @var array enthält alle images, die mit dem aktuellen Controllerdurchlauf erzeugt wurden als Values
     */
    protected $_imagesInTask = array();
    /**
     * @var ZfExtended_Controller_Helper_LocalEncoded
     */
    protected $_localEncoded = array();

    protected $_langErrors = array(
        'source' => 'Die übergebene Quellsprache %s ist ist ungültig.',
        'target' => 'Die übergebene Zielsprache %s ist ist ungültig.',
        'relais' => 'Die übergebene Relaissprache %s ist ist ungültig.',
    );
    
    /**
     * @var ZfExtended_Controller_Helper_General
     */
    protected $gh;

    /**
     * @var string Definiert, welcher Pfadtrenner bei java-Aufruf auf der command-line innerhalb des Parameters -cp gesetzt wird (Linux ":" Windows ";")
     */
    protected $javaPathSep = ':';
    
    /**
     * 
     * @var boolean
     */
    protected $isCheckRun = false;

    /**
     * @var editor_Models_Import_MetaData
     */
    protected $metaDataImporter;
    
    /**
     * Import Data Provider
     * @var editor_Models_Import_DataProvider_Abstract
     */
    protected $dataProvider;
    
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
     * Konstruktor
     */
    public function __construct(){
        $this->gh = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper('General');
        $this->_localEncoded = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper('LocalEncoded');
        $this->segmentFieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(get_class($this)));
    }
    
    /**
     * sets the Importer to check mode: additional debug output on import
     * does not effect pre import checks
     * @param boolean $check optional, per default true 
     */
    public function setCheck($check = true){
        $this->isCheckRun = $check;
    }
    
    /**
     * führt den Import aller Dateien eines Task durch
     * @param string $importFolderPath
     */
    public function import(editor_Models_Import_DataProvider_Abstract $dataProvider) {
        if(is_null($this->_taskGuid)){
            throw new Zend_Exception('taskGuid not set - please set using $this->setTask');
        }
        
        //pre import methods:
        $this->validateParams();
        $dataProvider->setTask($this->task);
        $dataProvider->checkAndPrepare();
        $this->_importFolder = $dataProvider->getAbsImportPath();
        $this->validateImportFolders();
        if(! $this->hasRelaisLanguage()) {
            //@todo in new rest api and / or new importwizard show ereror, if no relaislang is set, but relais data is given or viceversa (see translate5 featurelist)
            
            //reset given relais language value if no relais data is provided / feature is off
            $this->task->setRelaisLang(0); 
        }
        $this->task->setReferenceFiles($this->hasReferenceFiles());
        $this->task->save(); //Task erst Speichern wenn die obigen validates und checks durch sind.
        
        
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
        
        //we set and configure a general worker to set the open state of the task, after all other work
        $setToOpenWorker = ZfExtended_Factory::get('ZfExtended_Worker_Callback');
        /* @var $setToOpenWorker ZfExtended_Worker_Callback */
        $setToOpenWorker->init($this->task->getTaskGuid(), array(
                'class' => get_class($this), 
                'callback' => 'setTaskToOpen')
        );
        $setToOpenWorker->queue();
    }
    
    /**
     * Callback Method for the $setToOpenWorker
     * @param string $taskGuid
     * @param array $parameters
     * @return boolean
     */
    public function setTaskToOpen($taskGuid, array $parameters) {
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        $task->setState($task::STATE_OPEN);
        $task->save();
        
        $eventManager = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
        $eventManager->trigger('importCompleted', $this, array('task' => $task));
        return true;
    }
    
    /**
     * The errors of the import methods called in here, will be collected in check mode
     */
    protected function importWithCollectableErrors() {
        //should errors stop the import, or should they be logged:
        Zend_Registry::set('errorCollect', $this->isCheckRun);
        
        $this->importMetaData(); //Im MetaData Importer die TMX Geschichte integrieren
        $this->events->trigger("beforeDirectoryParsing", $this,array('importFolder'=>$this->_importFolder));
        $this->saveDirTrees();
        $this->importFiles();
        $this->syncFileOrder();
        $this->removeMetaDataTmpFiles();
        $this->importAndGenerateRelaisFiles();
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
        $deleteFiles = $this->isCheckRun || !$config->runtimeOptions->import->keepFilesOnError;
        
        $log = ZfExtended_Factory::get('ZfExtended_Log');
        /* @var $log ZfExtended_Log */
        $msg = 'More information see in the next exception email!';
        if(!$deleteFiles) {
            $msg = "\n".'The imported data is kept in '.$config->runtimeOptions->dir->taskData;
        }
        $log->logError('Exception while importing task '.$this->task->getTaskGuid(), $msg);
        
        if($deleteFiles) {
            $this->task->delete();
            $dataProvider->handleImportException($e);
        }
        else {
            $this->task->deleteButKeepFiles();
        }
    }
    
    /**
     * Importiert die Relais Dateien eines Tasks, welche noch nicht importiert wurde. 
     * 
     */
    public function importAndGenerateRelaisFiles() {
        if(! $this->hasRelaisLanguage()){ 
            return;
        }
        //Da im Durchlauf für die Relais Dateien Relais => Target ist, werden die Sprachen entsprechend geändert: 
        $this->_targetLang = $this->_relaisLang; 
        
        $tree = ZfExtended_Factory::get('editor_Models_RelaisFoldertree');
        /* @var $tree editor_Models_RelaisFoldertree */
        $tree->getPaths($this->_taskGuid,'file'); //Aufruf nötig, er initialisiert den Baum
        $this->_filePaths = $tree->checkAndGetRelaisFiles($this->_importFolder);
        
        $tree->save();
        
        $this->importRelaisFiles($tree);
    }
    
    /**
     * refreshes / creates the database views for this task
     */
    protected function updateSegmentFieldViews() {
        if(! $this->isCheckRun) {
            $this->task->createMaterializedView();
        }
    }
    
    /**
     * löscht alle Daten des aktuell im Importer geladenen Tasks aus der DB
     */
    public function deleteTask() {
        $this->task->delete();
    }

    /**
     * Methode zum Anstoßen verschiedener Meta Daten Imports zum Laufenende Import
     */
    protected function importMetaData() {
        $this->metaDataImporter = ZfExtended_Factory::get('editor_Models_Import_MetaData', array($this->_sourceLang, $this->_targetLang));
        /* @var $this->metaDataImporter editor_Models_Import_MetaData */
        $this->metaDataImporter->import($this->task, $this->_importFolder);
    }

    /**
     * Löscht temporär während des Imports erzeugte Metadaten
     */
    protected function removeMetaDataTmpFiles() {
        $this->metaDataImporter->cleanup();
    }

    /**
     * Importiert die Dateien und erzeugt die Taggrafiken
     *
     * - befüllt $this->_imagesInTask
     */
    protected function importFiles(){
        $mqmProc = ZfExtended_Factory::get('editor_Models_Import_SegmentProcessor_MqmParser', array($this->task, $this->segmentFieldManager));
        $segProc = ZfExtended_Factory::get('editor_Models_Import_SegmentProcessor_ProofRead', array($this->task, $this->_sourceLang, $this->_targetLang, $this->_userGuid, $this->_userName));
        /* @var $segProc editor_Models_Import_SegmentProcessor_ProofRead */
        foreach ($this->_filePaths as $fileId => $path) {
            if($this->isCheckRun){
                trigger_error('Check of File: '.$this->_importFolder.DIRECTORY_SEPARATOR.$path);
            }
            $params = $this->getFileparserParams($path, $fileId);
            $parser = $this->getFileParser($path, $params);
            /* @var $parser editor_Models_Import_FileParser */
            $segProc->setSegmentFile($fileId, $params[1]); //$params[1] => filename
            $parser->addSegmentProcessor($mqmProc);
            $parser->addSegmentProcessor($segProc);
            $parser->parseFile();
            $this->countWords($parser->getWordCount());
            $this->_imagesInTask = array_merge($this->_imagesInTask,$parser->getTagImageNames());
        }
        if ($this->task->getWordCount() == 0) {
            $this->task->setWordCount($this->wordCount);
        }
        $mqmProc->handleErrors();
    }
    
    /**
     * Adds up the number of words of the inported files
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
    protected function importRelaisFiles(editor_Models_RelaisFoldertree $tree){
        $mqmProc = ZfExtended_Factory::get('editor_Models_Import_SegmentProcessor_MqmParser', array($this->task, $this->segmentFieldManager));
        $segProc = ZfExtended_Factory::get('editor_Models_Import_SegmentProcessor_Relais', array($this->task, $this->segmentFieldManager));
        /* @var $segProc editor_Models_Import_SegmentProcessor_Relais */
        foreach ($this->_filePaths as $fileId => $path) {
            if(!$tree->isFileToImport($path)){
                continue;
            }
            if($this->isCheckRun){
                    trigger_error('Check of Relais File: '.$this->_importFolder.DIRECTORY_SEPARATOR.$path);
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
            $this->_importFolder.DIRECTORY_SEPARATOR.$this->_localEncoded->encode($path),
            $this->gh->basenameLocaleIndependent($path),
            $fileId, 
            $this->_edit100PercentMatches, 
            $this->_sourceLang, 
            $this->_targetLang,
            $this->task,
        );
    }
    
    /**
     * - liest den Directory-Tree aus
     * - speichert ihn in der DB als Objekt (LEK_foldertree) und flach durch Befüllung von LEK_files
     * - befüllt $this->_filePaths
     */
    protected function saveDirTrees(){
        $parser = ZfExtended_Factory::get('editor_Models_Import_DirectoryParser_WorkingFiles');
        /* @var $parser editor_Models_Import_DirectoryParser_WorkingFiles */
        $tree = $parser->parse($this->getProofReadDir());
        
        $treeDb = ZfExtended_Factory::get('editor_Models_Foldertree');
        /* @var $treeDb editor_Models_Foldertree */
        $treeDb->setTree($tree);
        if($this->hasReferenceFiles() && !$this->isCheckRun){
            $treeDb->setReferenceFileTree($this->getReferenceFileTree());
        }
        $treeDb->setTaskGuid($this->_taskGuid);
        $relaisId = $this->hasRelaisLanguage() ? $this->_relaisLang->getId() : 0;
        $sync = ZfExtended_Factory::get('editor_Models_Foldertree_SyncToFiles', array($treeDb,$this->_sourceLang->getId(),$this->_targetLang->getId(),$relaisId));
        /* @var $sync editor_Models_Foldertree_SyncToFiles */
        $sync->recursiveSync();
        
        $treeDb->save();
        $this->_filePaths = $treeDb->getPaths($this->_taskGuid,'file');
    }
    
    /**
     * Gibt den absoluten Pfad (inkl. Import Root) zum Verzeichnis mit den zu lektorierenden Dateien zurück, berücksichtigt die proofRead bzw. Relaissprachen Config
     * @param boolean $rel optional, gibt an ob nur der relative Teil des Proof Read Dirs zum Import Root zurückgegeben werden soll  
     * @return string
     */
    protected function getProofReadDir($rel = false) {
        $config = Zend_Registry::get('config');
        $prefix = $rel ? '' : $this->_importFolder;
        $proofReadDir = $config->runtimeOptions->import->proofReadDirectory;
        return $proofReadDir == '' ? $prefix : $prefix.DIRECTORY_SEPARATOR.$proofReadDir; 
    }
    
    /**
     * Gibt den absoluten Pfad (inkl. Import Root) zum Verzeichnis mit den Relais Dateien zurück, berücksichtigt die Relaissprachen Config
     * @param boolean $rel optional, gibt an ob nur der relative Teil des Proof Read Dirs zum Import Root zurückgegeben werden soll  
     * @return string
     */
    protected function getRelaisDir($rel = false) {
        if(empty($this->_importFolder)){
            throw new Zend_Exception('internal import folder is not yet set.');
        }
        $prefix = $rel ? '' : $this->_importFolder.DIRECTORY_SEPARATOR; 
        $config = Zend_Registry::get('config');
        return $prefix.$config->runtimeOptions->import->relaisDirectory;
    }

    /**
     * returns if reference files has to be imported
     * @return boolean
     */
    protected function hasReferenceFiles() {
        $config = Zend_Registry::get('config');
        //If no ProofRead directory is set, the reference files must be ignored  
        $proofDir = $config->runtimeOptions->import->proofReadDirectory;
        $refDir = $config->runtimeOptions->import->referenceDirectory;
        return !empty($proofDir) && is_dir($this->_importFolder.DIRECTORY_SEPARATOR.$refDir);
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
    	$refAbsDir = $this->_importFolder.DIRECTORY_SEPARATOR.$refDir;
    	$this->recurseCopy($refAbsDir, $refTarget);
    
    	$parser = ZfExtended_Factory::get('editor_Models_Import_DirectoryParser_ReferenceFiles');
    	/* @var $parser editor_Models_Import_DirectoryParser_ReferenceFiles */
        return $parser->parse($refTarget);
    }
    
    /**
     * does a recursive copy of the given directory
     * @param string $src Source Directory
     * @param string $dst Destination Directory
     */
    protected function recurseCopy(string $src, string $dst) {
    	$dir = opendir($src);
    	@mkdir($dst);
    	$SEP = DIRECTORY_SEPARATOR;
    	while(false !== ( $file = readdir($dir)) ) {
    		if ($file == '.' || $file == '..') {
    		    continue;
    		}
			if (is_dir($src.$SEP.$file)) {
				$this->recurseCopy($src.$SEP.$file, $dst.$SEP.$file);
			}
			else {
				copy($src.$SEP.$file, $dst.$SEP.$file);
			}
    	}
    	closedir($dir);
    }
    
    /**
     * returns the absolute path to the tasks folder for reference files
     */
    protected function getAbsReferencePath() {
        $config = Zend_Registry::get('config');
        return $this->task->getAbsoluteTaskDataPath().DIRECTORY_SEPARATOR.$config->runtimeOptions->import->referenceDirectory;
    }
    
    /**
     * validiert / filtert die Get-Werte
     * @throws Zend_Exception
     */
    protected function validateParams(){
        $guidValidator = new ZfExtended_Validate_Guid();
        $validateUsername = new Zend_Validate_Regex('"[A-Za-z0-9 \-]+"');
        if(!$guidValidator->isValid($this->_taskGuid)){
            throw new Zend_Exception('Die übergebene taskGuid '.$this->_taskGuid.' ist keine valide GUID.');
        }
        if(!$guidValidator->isValid($this->_userGuid)){
            throw new Zend_Exception('Die übergebene userGuid '.$this->_userGuid.' ist keine valide GUID.');
        }
        if(!$validateUsername->isValid($this->_userName)){
            throw new Zend_Exception('Der übergebene _userName '.$this->_userName.' ist kein valider Username.');
        }
        if(is_null($this->_sourceLang)){
            throw new Zend_Exception(sprintf($this->_langErrors['source'], $this->_sourceLangValue));
        }
        if(is_null($this->_targetLang)){
            throw new Zend_Exception(sprintf($this->_langErrors['target'], $this->_targetLangValue));
        }
        if(!empty($this->_relaisLangValue) && is_null($this->_relaisLang)){
            throw new Zend_Exception(sprintf($this->_langErrors['relais'], $this->_relaisLangValue));
        }
    }
    
    /**
     * validiert die nötigen Import Verzeichnisse
     * @throws Zend_Exception
     */
    protected function validateImportFolders(){
        if(!is_dir($this->_importFolder)){
        	throw new Zend_Exception('Der übergebene importRootFolder '.$this->_importFolder.' existiert nicht.');
        }
        if(!is_dir($this->getProofReadDir())){
        	throw new Zend_Exception('Der übergebene ProofReadFolder '.$this->getProofReadDir().' existiert nicht.');
        }
    }

    protected function syncFileOrder() {
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        //dont update view here, since it is not existing yet!
        $segment->syncFileOrderFromFiles($this->_taskGuid, true); 
    }

    /**
     * @param boolean $edit
     */
    public function setEdit100PercentMatches(boolean $edit){
        $this->_edit100PercentMatches = $edit;
    }
    /**
     * sets the info/data to the user
     * @param string $userguid
     * @param string $username
     */
    public function setUserInfos(string $userguid, string $username) {
        $this->_userName = $username;
        $this->_userGuid = $userguid;
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
        
        $sourceId = empty($this->_sourceLang) ? 0 : $this->_sourceLang->getId();
        $task->setSourceLang($sourceId);
        $targetId = empty($this->_targetLang) ? 0 : $this->_targetLang->getId();
        $task->setTargetLang($targetId);
        $relaisId = empty($this->_relaisLang) ? 0 : $this->_relaisLang->getId();
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
        $this->_taskGuid = $task->getTaskGuid();
        $this->task->initTaskDataDirectory();
    }

    /**
     * Setzt die zu importierende Quell und Zielsprache, das Format der Sprach IDs wird über den Parameter $type festgelegt
     * @param string $source
     * @param string $target
     * @param string $relais Relaissprache, kann null/leer sein wenn es keine Relaissprache gibt
     * @param string $type
     */
    public function setLanguages(string $source, string $target, $relais, $type = editor_Models_Languages::LANG_TYPE_RFC5646) {
        $this->_sourceLangValue = $source;
        $this->_targetLangValue = $target;
        $this->_relaisLangValue = $relais;
        $langFields = array('_sourceLang' => $source, '_targetLang' => $target, '_relaisLang' => $relais);
        
        foreach($langFields as $key => $lang) {
            $langInst = ZfExtended_Factory::get('editor_Models_Languages');
            if(empty($lang) || !$langInst->loadLang($lang, $type)) {
                //null setzen wenn Sprache nicht gefunden. Das triggert einen Fehler in der validateParams dieser Klasse
                $langInst = null;
            }
            $this->{$key} = $langInst;
        }
    }
    
    /**
     * Gibt an ob eine Relaissprache verwendet werden soll (Anhand des Import Parameters)
     * @return boolean
     */
    protected function hasRelaisLanguage() {
        return !empty($this->_relaisLang) && is_dir($this->getRelaisDir());
    }
}
