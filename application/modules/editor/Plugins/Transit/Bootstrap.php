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

/**
 * Initial Class of Plugin "Transit"
 *
 * This plugin brings Transit-Functionality to translate5
 *
 * This plugin changes the filenames of the transit-target-language-files inside of
 * an import folder to be compatible with filename-based import mechanism of translate5
 *
 * It expects a config-File in the top level of an import directory of the format
 * sourceLang-targetLang.transitConfig - where as sourceLang and targetLang are in
 * transit-syntax e. g. DEU-ESP.transitConfig for German source and Spanish target
 *
 * It then changes all targetFileNames to e.g. *.ESP.transit and it stores the
 * languageInfo in ZendRegistry transitLangInf
 *
 */
class editor_Plugins_Transit_Bootstrap extends ZfExtended_Plugin_Abstract {
    protected static $description = 'Provides transit import';
    
    /**
     *
     * @var string
     */
    protected $reviewDirName;
    
    /**
     *
     * @var string
     */
    protected $importFolder;
    /**
     *
     * @var array
     */
    protected $langInfo;

    public function init() {
        // event-listeners
        $this->eventManager->attach('editor_Models_Import_Worker_FileTree', 'beforeDirectoryParsing', array($this, 'handleTransitImportPreparation'));
        $this->eventManager->attach('editor_Models_Import_Worker', 'beforeWork', array($this, 'handleBeforeImport'));
        $this->eventManager->attach('editor_Models_Import_Worker_Import', 'importCleanup', array($this, 'handleTransitImportCleanup'), -10);
        // end of event-listeners

        $this->reviewDirName = editor_Models_Import_Configuration::WORK_FILES_DIRECTORY;
        $meta = ZfExtended_Factory::get('editor_Models_Segment_Meta');
        /* @var $meta editor_Models_Segment_Meta */
        $meta->addMeta('transitLockedForRefMat', $meta::META_TYPE_BOOLEAN, false, 'Is set to true if segment is locked for reference material in transit file');
    }
    
    /**
     * @param Zend_EventManager_Event $event
     */
    public function handleBeforeImport(Zend_EventManager_Event $event) {
        $worker = $event->getParam('worker');
        /* @var $worker editor_Models_Import_Worker */
        $params = $worker->getModel()->getParameters();
        
        $importConfig = $params['config'];
        /* @var $importConfig editor_Models_Import_Configuration */
        $this->importFolder = $importConfig->importFolder;
        $this->reviewDirName = $importConfig->getFilesDirectory();
        
        $this->initTransitConfig();
    }
    
    /**
     * handler for event: editor_Models_Import#beforeDirectoryParsing
     * @param Zend_EventManager_Event $event
     */
    public function handleTransitImportPreparation(Zend_EventManager_Event $event) {
        $params = $event->getParams();
        $this->importFolder = $params['importFolder'];
        $importConfig = $params['importConfig'];
        /* @var $importConfig editor_Models_Import_Configuration */
        $this->reviewDirName = $importConfig->getFilesDirectory();
        if($this->initTransitConfig()) {
            $this->renameTargetFiles('preparation');
        }
    }
    
    /**
     * inits the transit config
     * @return boolean returns false if there is no or an invalid transitConfig
     */
    protected function initTransitConfig() {
        $transitConfig = $this->getTransitConfigFile();
        if(is_bool($transitConfig)){
            return false;
        }
        $this->setTransitLangInfo($transitConfig);
        return true;
    }
    
    /**
     * handler for event: editor_Models_Import#afterImport
     */
    public function handleTransitImportCleanup(Zend_EventManager_Event $event) {
        $params = $event->getParams();
        $importConfig = $params['importConfig'];
        /* @var $importConfig editor_Models_Import_Configuration */
        $this->importFolder = $importConfig->importFolder;
        $this->reviewDirName = $importConfig->getFilesDirectory();
        $this->renameTargetFiles('cleanup');
    }
    
    /**
     *
     * @return \SplFileInfo|boolean
     */
    protected function getTransitConfigFile() {
        $transitFiles = array();
        $iterator = new DirectoryIterator($this->importFolder);
        /* @var $fileinfo DirectoryIterator */
        foreach ($iterator as $fileinfo) {
            if(strtolower($fileinfo->getExtension()) === 'transitconfig') {
                $transitFiles[] = $fileinfo;
                $transitFile = $fileinfo->getFileInfo();
            }
        }
        $transitCount = count($transitFiles);
        if($transitCount>1){
            trigger_error('Only one transitConfig file supported by each task. ImportFolder: '.$this->importFolder);
            return false;
        }
        if($transitCount === 0){
            return true;
        }
        return $transitFile;
    }
    /**
     * sets transitLangInfo in ZendRegistry transitLangInfo
     * sets $this->langInfo to associative array(source=>lang,target=>lang)
     * @param \SplFileInfo
     */
    protected function setTransitLangInfo(\SplFileInfo $transitConfig) {
        $langInfo = explode('-',preg_replace('"\.'.$transitConfig->getExtension().'$"i','',$transitConfig->getBasename()));
        if(count($langInfo)!==2 ||strlen($langInfo[0])!==3||strlen($langInfo[1])!==3){
            trigger_error('transitConfig-file does not contain valid language infos. ImportFolder: '.$this->importFolder);
            return false;
        }
        $langInfo['source'] = $langInfo[0] = strtoupper($langInfo[0]);
        $langInfo['target'] = $langInfo[1] = strtoupper($langInfo[1]);
        
        $supportedTypes = ZfExtended_Factory::get('editor_Models_Import_SupportedFileTypes');
        $supportedTypes->registerIgnored(strtolower($langInfo['source']));
        
        Zend_Registry::set('transitLangInfo', $langInfo);
        $this->langInfo = $langInfo;
    }
    
    /**
     * @param string $job
     */
    protected function renameTargetFiles($job) {
        $iterator = new DirectoryIterator($this->importFolder.DIRECTORY_SEPARATOR.$this->reviewDirName);
        /* @var $fileinfo DirectoryIterator */
        foreach ($iterator as $fileinfo) {
            if(!$fileinfo->isFile()) {
                continue;
            }
            if($fileinfo->getExtension() === ($this->langInfo['target'] ?? '') && $job === 'preparation'){
                rename($fileinfo->getPathname(),$fileinfo->getPathname().'.transit');
            }
            if($fileinfo->getExtension() === 'transit' && $job === 'cleanup'){
                rename($fileinfo->getPathname(),  preg_replace('"\.transit$"i', '', $fileinfo->getPathname()));
            }
        }
    }
}
