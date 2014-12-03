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
class editor_Plugins_Transit_Bootstrap {
    
    /**
     * @var Zend_EventManager_StaticEventManager
     */
    protected $staticEvents = false;
    
    /**
     *
     * @var string
     */
    protected $proofReadDirName;
    
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

    public function __construct() {
        $config = Zend_Registry::get('config');
        // event-listeners
        $this->staticEvents = Zend_EventManager_StaticEventManager::getInstance();
        $this->staticEvents->attach('editor_Models_Import', 'beforeDirectoryParsing', array($this, 'handleTransitImportPreparation'));
        $this->staticEvents->attach('editor_Models_Import_DataProvider_Abstract', 'beforeArchiveImportedData', array($this, 'handleTransitImportCleanup'));
        // end of event-listeners
        $this->proofReadDirName = $config->runtimeOptions->import->proofReadDirectory;
    }
    
    
    /**
     * handler for event: editor_Models_Import#beforeDirectoryParsing
     */
    public function handleTransitImportPreparation(Zend_EventManager_Event $event) {
        $params = $event->getParams();
        $this->importFolder = $params['importFolder'];
        $transitConfig = $this->getTransitConfigFile();
        if(is_bool($transitConfig)){
            return;
        }
        $langInfo = $this->setTransitLangInfo($transitConfig);
        $this->renameTargetFiles('preparation');
    }
    
    /**
     * handler for event: editor_Models_Import#afterImport
     */
    public function handleTransitImportCleanup(Zend_EventManager_Event $event) {
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
        Zend_Registry::set('transitLangInfo', $langInfo);
        $this->langInfo = $langInfo;
    }
    
    /**
     * 
     * @param string $job
     */
    protected function renameTargetFiles($job) {
        $iterator = new DirectoryIterator($this->importFolder.DIRECTORY_SEPARATOR.$this->proofReadDirName);
        /* @var $fileinfo DirectoryIterator */
        foreach ($iterator as $fileinfo) {
            if($fileinfo->isFile()) {
                if($fileinfo->getExtension()===$this->langInfo['target'] && $job === 'preparation'){
                    rename($fileinfo->getPathname(),$fileinfo->getPathname().'.transit');
                }
                if($fileinfo->getExtension()==='transit' && $job === 'cleanup'){
                    rename($fileinfo->getPathname(),  preg_replace('"\.transit$"i', '', $fileinfo->getPathname()));
                }
              }
        }
    }
}
