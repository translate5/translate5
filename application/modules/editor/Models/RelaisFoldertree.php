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
 */
/**
 * Erweitert Foldertree Klasse um die Methoden welche für den Relais Dateien Import benötigt werden
 */
class editor_Models_RelaisFoldertree extends editor_Models_Foldertree {
    const RELAIS_NOT_FOUND = 0; //keine Relais Datei gefunden
    const RELAIS_NOT_IMPORTED = 1; //gefunden aber noch importiert
    const RELAIS_IMPORTED = 2; //Relais Datei bereits importiert
    
    /**
     * Assoc Array, fileId => RelaisFileStatus
     * @var array
     */
    protected $relaisFilesStati = array();
   
    /**
     * Pfad zu den Relais Dateien
     * @var string
     */
    protected $relaisRootPath;
    
    /**
     * Schalter um spezfische Logik fürs Relais Dateien Checking an und abzuschalten
     * @var boolean
     */
    protected $checkCall = false;
    
    /**
     * Wenn true werden fehlende Relais Datein als Fehler geloggt
     * @var boolean
     */
    protected $enableLogMissingFiles = null;
    
    /**
     * Collected files which have no corresponding relais file
     * @var array
     */
    protected $collectedMissingFiles = [];
    
    /***
     * 
     * @var editor_Models_Import_Configuration
     */
    protected $importConfig;

    public function __construct(){
        parent::__construct();
        $config = Zend_Registry::get('config');
        $this->enableLogMissingFiles = $config->runtimeOptions->import->reportOnNoRelaisFile;
        $this->setPathPrefix($config->runtimeOptions->import->relaisDirectory);
    }
    
    /**
     * Durchsucht das angegebene Relais Verzeichnis nach den im internen Baum gespeicherten Dateien
     * und setzt bei Funden entsprechend den Relais File Status im internen Baum
     * @param string $importFolder
     * @return array
     */
    public function checkAndGetRelaisFiles(string $importFolder){
        $this->checkCall = true;
        $this->relaisRootPath = $importFolder.DIRECTORY_SEPARATOR.$this->_pathPrefix;
    	$this->getFilePathsNodeVisitor($this->objectTree);
    	$this->logMissingFile();
        $this->checkCall = false;
        return array_filter($this->_paths, function($path) {
            return $this->isFileToImport($path);
        });
    }
    
    /**
     * Wird im getFilePathsNodeVisitor für jede Datei aufgerufen
     * @param stdClass $child
     * @param string $path
     */
    protected function handleFile(stdClass $child, $path) {
    	if(! $this->checkCall){
        	parent::handleFile($child, $path);
    	    return;
    	}
    	$filepath = $path.$child->filename;
    	$fullpath = $this->relaisRootPath.DIRECTORY_SEPARATOR.ZfExtended_Utils::filesystemEncode($filepath);
        if(empty($child->relaisFileStatus)){
            $child->relaisFileStatus = file_exists($fullpath) ? self::RELAIS_NOT_IMPORTED : self::RELAIS_NOT_FOUND;
        }

        //here can invoke import filters to manipulate the file information, needed for example if the filename changes and therefore the filename based relais file matching would fail.
        $this->events->trigger("customHandleFile", $this, array(
            'path' => $path,
            'fileChild' => $child,
            'fullPath' => $fullpath,
            'filePath' => $filepath,
            'taskGuid' => $this->getTaskGuid(),
            'importConfig' => $this->importConfig//INFO:(TRANSLATE-1596)Afte we remove the depricate support for proofRead this can be removed
        ));
        $filepath = $path.$child->filename;
        
        //stores the handled file in the internal path array (which is returned filtered later)
        parent::handleFile($child, $path);
        $this->collectMissingFile($path, $child);
        $this->relaisFilesStati[$this->_pathPrefix.DIRECTORY_SEPARATOR.$filepath] = $child->relaisFileStatus;
    }
    
    /**
     * Loggt bei Bedarf die Relais Datei als fehlend
     * @param string $path
     * @param stdClass $child
     */
    protected function collectMissingFile($path, stdClass $child) {
        if($child->relaisFileStatus !== self::RELAIS_NOT_FOUND
             || !$this->enableLogMissingFiles
             || empty($child->isFile)){
            return;
        }
        $this->collectedMissingFiles[$child->id] = $path.$child->filename;
    }
    
    /**
     * Logs the missing relais files.
     */
    protected function logMissingFile() {
        if(empty($this->collectedMissingFiles)) {
            return;
        }
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($this->getTaskGuid());
        
        $logger = Zend_Registry::get('logger')->cloneMe('editor.import.relais');
        /* @var $logger ZfExtended_Logger */
        $logger->warn('E1112', 'Task was configured with relais language, but some relais file were not found. See Details.',[
            'filesWithoutRelais' => join("\n", $this->collectedMissingFiles),
            'task' => $task,
        ]);
    }
    
    
    /**
     * @param string $path
     * @return boolean
     */
    protected function isFileToImport(string $path) {
        return isset($this->relaisFilesStati[$path]) &&
            $this->relaisFilesStati[$path] == self::RELAIS_NOT_IMPORTED;
    }
    
    public function setImportConfig(editor_Models_Import_Configuration $config) {
        $this->importConfig=$config;
    }
}