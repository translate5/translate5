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
 * Encapsulates the part of the import logic which is intended to be run in a worker
 */
class editor_Models_Import_Worker_Import {
    /***
     *
     * @var string
     */
    const CONFIG_TEMPLATE = 'task-config.ini';
    
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * @var ZfExtended_Controller_Helper_LocalEncoded
     */
    protected $_localEncoded;

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
    
    /**
     * @var editor_Models_Import_SupportedFileTypes
     */
    protected $supportedFiles;

    
    public function __construct() {
        $this->_localEncoded = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper('LocalEncoded');
        $this->segmentFieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        //we should use __CLASS__ here, if not we loose bound handlers to base class in using subclasses
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(__CLASS__));
        
        $this->supportedFiles = ZfExtended_Factory::get('editor_Models_Import_SupportedFileTypes');
    }
    
    /**
     * starts the main part of the file import which is intended to run in a worker
     * @param string $taskGuid
     * @param editor_Models_Import_Configuration $importConfig
     */
    public function import(editor_Models_Task $task, editor_Models_Import_Configuration $importConfig) {
        $this->task = $task;
        $this->importConfig = $importConfig;
        
        $this->loadConfigTemplate();
        
        $importConfig->isValid($task->getTaskGuid());
        $this->filelist = ZfExtended_Factory::get('editor_Models_Import_FileList', array($this->importConfig, $this->task));
        
        //down from here should start the import worker
        //in the worker again:
        Zend_Registry::set('affected_taskGuid', $this->task->getTaskGuid()); //for TRANSLATE-600 only
        
        $this->segmentFieldManager->initFields($this->task->getTaskGuid());

        $this->events->trigger('beforeImportFiles', $this, ['task' => $task, 'importConfig' => $importConfig]);
        
        //call import Methods:
        $this->importFiles();
        $this->syncFileOrder();
        $this->importRelaisFiles();
        $this->task->createMaterializedView();
        $this->calculateMetrics();
        //saving task twice is the simplest way to do this. has meta data is only available after import.
        $this->task->save();
        
        
        //init default user prefs
        $workflowManager = ZfExtended_Factory::get('editor_Workflow_Manager');
        /* @var $workflowManager editor_Workflow_Manager */
        $workflowManager->getByTask($this->task)->doImport($this->task, $importConfig);
        $workflowManager->initDefaultUserPrefs($this->task);
        
        $this->events->trigger('importCleanup', $this, ['task' => $task, 'importConfig' => $importConfig]);
    }
    
    /**
     * Importiert die Dateien und erzeugt die Taggrafiken
     */
    protected function importFiles(){

        $treeDb = ZfExtended_Factory::get('editor_Models_Foldertree');
        /* @var $treeDb editor_Models_Foldertree */
        $treeDb->setPathPrefix($this->importConfig->getFilesDirectory());
        $filelist = $treeDb->getPaths($this->task->getTaskGuid(),'file');
        
        $fileFilter = ZfExtended_Factory::get('editor_Models_File_FilterManager');
        /* @var $fileFilter editor_Models_File_FilterManager */
        $fileFilter->initImport($this->task, $this->importConfig);
            
        $mqmProc = ZfExtended_Factory::get('editor_Models_Import_SegmentProcessor_MqmParser', array($this->task, $this->segmentFieldManager));
        $repHash = ZfExtended_Factory::get('editor_Models_Import_SegmentProcessor_RepetitionHash', array($this->task, $this->segmentFieldManager));
        $segProc = ZfExtended_Factory::get('editor_Models_Import_SegmentProcessor_Review', array($this->task, $this->importConfig));
        /* @var $segProc editor_Models_Import_SegmentProcessor_Review */
        
        $filesProcessedAtAll = 0;
        foreach ($filelist as $fileId => $path) {
            $path = $fileFilter->applyImportFilters($path, $fileId, $filelist);
            $file = new SplFileInfo($this->importConfig->importFolder.'/'.$path);
            $parser = $this->getFileParser($fileId, $file);
            if(!$parser) {
                continue;
            }
            
            /* @var $parser editor_Models_Import_FileParser */
            $segProc->setSegmentFile($fileId, $file->getBasename()); //$params[1] => filename
            $parser->addSegmentProcessor($mqmProc);
            $parser->addSegmentProcessor($repHash);
            $parser->addSegmentProcessor($segProc);
            $parser->parseFile();
            $filesProcessedAtAll++;
            //wordcount provided by import format
            $this->countWords($parser->getWordCount());
        }
        
        if($filesProcessedAtAll === 0) {
            //E1166: Although there were importable files in the task, no files were imported. Investigate the log for preceeding errors.
            throw new editor_Models_Import_FileParser_NoParserException('E1166', [
                'task' => $this->task
            ]);
        }
        
        if ($this->task->getWordCount() == 0) {
            $this->task->setWordCount($this->wordCount);
        }
        $mqmProc->handleErrors();
        
        $this->task->setReferenceFiles($this->filelist->hasReferenceFiles());
    }
    
    /**
     * Calculates and sets the task metrics emptyTargets (bool), wordCount (int) and segmentCount(int)
     */
    protected function calculateMetrics() {
        $taskGuid = $this->task->getTaskGuid();
        
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        
        $meta = ZfExtended_Factory::get('editor_Models_Segment_Meta');
        /* @var $meta editor_Models_Segment_Meta */
        
        /* @var $segment editor_Models_Segment */
        $this->task->setEmptyTargets($segment->hasEmptyTargetsOnly($taskGuid));

        //we may set the tasks wordcount only to our calculated values if there was no count given either by API or by import formats
        if ($this->task->getWordCount() == 0) {
            $this->task->setWordCount($meta->getWordCountSum($taskGuid));
        }
        
        $this->task->setSegmentCount($segment->getTotalSegmentsCount($taskGuid));
    }
    
    /**
     * Adds up the number of words of the imported files
     * and saves this into the private variable $this->wordCount
     *
     * If this function is once called with "false", the addup-process will be canceled for the whole import-process
     *
     * @param int or bool false $count
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
     */
    protected function getFileParser(int $fileId, SplFileInfo $file){
        try {
            $parserClass = $this->lookupFileParserCls($file->getExtension(), $file);
        } catch(editor_Models_Import_FileParser_NoParserException $e) {
            Zend_Registry::get('logger')->exception($e, ['level' => ZfExtended_Logger::LEVEL_WARN]);
            return false;
        }
        
        $parser = ZfExtended_Factory::get($parserClass, [
            $file->getPathname(),
            $file->getBasename(),
            $fileId,
            $this->task
        ]);
        /* var $parser editor_Models_Import_FileParser */
        $parser->setSegmentFieldManager($this->segmentFieldManager);
        return $parser;
    }
    
    /**
     * Looks for a suitable file parser and returns the corresponding file parser cls
     * @param string $extension
     * @param SplFileInfo $file
     * @throws editor_Models_Import_FileParser_NoParserException
     * @return string
     */
    protected function lookupFileParserCls(string $extension, SplFileInfo $file): string {
        $parserClasses = $this->supportedFiles->getParser($extension);
        $errorMsg = '';
        $errorMessages = [];
        
        $fileObject = $file->openFile('r', false);
        $fileHead = $fileObject->fread(512);
        foreach($parserClasses as $parserClass) {
            if($parserClass::isParsable($fileHead, $errorMsg)) {
                // if the first found file parser to that extension may parse it, we use it
                return $parserClass;
            }
            if(!empty($errorMsg)) {
                $errorMessages[$parserClass] = $errorMsg;
            }
        }
        
        //'For the given fileextension no parser is registered.'
        throw new editor_Models_Import_FileParser_NoParserException('E1060', [
            'file' => $file->getPathname(),
            'task' => $this->task,
            'extension' => $extension,
            'errorMessages' => $errorMessages,
            'availableParsers' => $this->supportedFiles->getSupportedExtensions(),
        ]);
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
            $file = new SplFileInfo($this->importConfig->importFolder.'/'.$path);
            $parser = $this->getFileParser($fileId, $file);
            if(!$parser) {
                continue;
            }
            /* @var $parser editor_Models_Import_FileParser */
            $segProc->setSegmentFile($fileId, $file->getBasename());
            $parser->addSegmentProcessor($mqmProc);
            $parser->addSegmentProcessor($repHash);
            $parser->addSegmentProcessor($segProc);
            $parser->parseFile();
    	}
        $mqmProc->handleErrors();
    }
    
    protected function syncFileOrder() {
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        //dont update view here, since it is not existing yet!
        $segment->syncFileOrderFromFiles($this->task->getTaskGuid(), true);
    }
    
    /***
     * Load the config template for the task if it is provided in the import package
     * @throws Exception
     */
    protected function loadConfigTemplate() {
        $template = $this->importConfig->importFolder.'/'.self::CONFIG_TEMPLATE;
        if (!file_exists($template)) {
            return;
        }
        $logData = [
            'filename' => self::CONFIG_TEMPLATE,
            'task' => $this->task,
        ];
        $config = parse_ini_file($template);
        $log = Zend_Registry::get('logger');
        /* @var $log ZfExtended_Logger */
        foreach ($config as $name => $value){
            $taskConfig=ZfExtended_Factory::get('editor_Models_TaskConfig');
            /* @var $taskConfig editor_Models_TaskConfig */
            try {
                $taskConfig->updateInsertConfig($this->task->getTaskGuid(),$name,$value);
            }
            catch (ZfExtended_Models_Entity_Exceptions_IntegrityConstraint $e) {
                $logData['name'] = $name;
                $log->exception(new editor_Models_Import_FileParser_Exception('E1327', $logData), ['level' => $log::LEVEL_WARN]);
            }
            catch (Exception $e) {
                $logData['errorMessage'] = $e->getMessage();
                throw new editor_Models_Import_FileParser_Exception('E1325', $logData);
            }
        }
    }
}
