<?php
/*
START LICENSE AND COPYRIGHT

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a plug-in for translate5. 
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and 
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the 
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
   
 There is a plugin exception available for use with this release of translate5 for 
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3: 
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/gpl.html
			 http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

class editor_Plugins_TermImport_Init extends ZfExtended_Plugin_Abstract {
    
    /***
     * Import from file system config file
     * @var string
     */
    const FILESYSTEM_CONFIG_NAME="filesystem.config";
    
    /***
     * Import from across api config name
     * @var string
     */
    const CROSSAPI_CONFIG_NAME="crossapi.config";
    
    /***
     * Import dir key from the filesystem config file
     * @var string
     */
    const IMPORT_DIR_ARRAY_KEY="importDir";
    
    /***
     * File mapping group name in the filesystem config
     * 
     * @var string
     */
    const FILE_MAPPING_GROUP="FileMapping";
    
    /***
     * Collection mapping group name in the filesystem config
     * 
     * @var string
     */
    const COLLECTION_MAPPING_GROUP="CollectionMapping";
    
    /**
     * @var array
     */
    protected $frontendControllers = array(
    );
    
    
    /***
     * Data from the filesystem config file
     * @var array
     */
    public $filesystemMap=array();
    
    
    /***
     * Data from the cross api config file
     * @var array
     */
    public $crossapiMap=array();
    
    public function init() {
        $this->initEvents();
    }
    
    protected function initEvents() {
    }
    
    /***
     * File system import handler.
     * //TODO:call this function(cron job ?) so the import is triggered
     */
    public function handleFileSystemImport(){
        
        if(empty($this->filesystemMap)){
            $this->loadConfigFiles();
        }
        
        //tbx files import folder
        $importDir=$this->filesystemMap[self::IMPORT_DIR_ARRAY_KEY];

        //get all files from the import direcotry
        $files = array_slice(scandir($importDir), 2);
        if(empty($files)){
            return;
        }
        
        foreach ($files as $file){
            
            if(!isset($this->filesystemMap[self::FILE_MAPPING_GROUP]) || !isset($this->filesystemMap[self::FILE_MAPPING_GROUP][$file])){
                continue;
            }
            
            $collectionName=$this->filesystemMap[self::FILE_MAPPING_GROUP][$file];
            $customerId=$this->filesystemMap[self::COLLECTION_MAPPING_GROUP][$collectionName];
            
            $model=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
            /* @var $model editor_Models_TermCollection_TermCollection */
            
            $tc=$model->loadByName($collectionName);
            
            //if the term collection does not exist, create a new one
            if(empty($tc)){
                
                $model->setName($collectionName);
                if($customerId){
                    $model->setCustomerId($customerId);
                }
                $model->save();
                $tc=array(
                        'id'=>$model->getId(),
                        'name'=>$model->getName(),
                );
            }
            
            $params=array('collectionId'=>$tc['id'],'customerId'=>$customerId,'mergeTerms'=>true);
            if($model->importTbx([$importDir.$file], $params)){
                error_log("File:".$file.' was imported in the collection:'.$collectionName);
            }else{
                error_log("Unable to import the file:".$file." into the collection");
            }
        }
    }
    
    /**
     * Load the configuration files into the data holders
     */
    private function loadConfigFiles(){
        $path=$this->getPluginConfigFolderPath();
        $fileSystemFile=$path.self::FILESYSTEM_CONFIG_NAME;
        $crossApiFile=$path.self::CROSSAPI_CONFIG_NAME;
        
        $this->initConfigFile($fileSystemFile, $this->filesystemMap);
        $this->initConfigFile($crossApiFile, $this->crossapiMap);
    }
    
    /***
     * Init the config array
     * 
     * @param string $filePath : absolute path to the config file
     * @param arrayy $mapArray : array where the config data will be stored
     * 
     * @throws ZfExtended_ValidateException
     */
    private function initConfigFile($filePath,&$mapArray){
        if(!file_exists($filePath)){
            throw new ZfExtended_ValidateException("Configuration file is missing:".$filePath);
        }
        $file=file_get_contents($filePath);
        if(empty($file)){
            throw new ZfExtended_ValidateException("The configuration file:".$filePath.' is empty.');
        }
        
        $mapArray= parse_ini_file($filePath,true);
        if(empty($mapArray)){
            throw new ZfExtended_ValidateException("Wrong file structure in :".$filePath);
        }
    }

    /***
     * Get the plugin config folder absolute path
     * @return string
     */
    private function getPluginConfigFolderPath(){
        return APPLICATION_PATH.DIRECTORY_SEPARATOR.$this->getPluginPath().DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR;
    }
}
