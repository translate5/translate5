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

/**
 */
class editor_Plugins_TermImport_Services_Import {
    
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
     * Key from the crossapi config file for the across api url
     * @var string
     */
    const IMPORT_ACOSS_API_URL="crossAPIurl";
    

    /***
     * Key for the merge terms flag used by the tbx import parser
     * @var string
     */
    const IMPORT_MERGE_TERMS_KEY="mergeTerms";
    
    /***
     *  Key from the crossapi config file for the across api user
     * @var string
     */
    const IMPORT_ACOSS_API_USER="apiUsername";
    
    /***
     *  Key from the crossapi config file for the across api password
     * @var string
     */
    const IMPORT_ACOSS_API_PWD="apiPassword";
    
    /***
     * Key from the crossapi config file for the across export files directory
     * @var string
     */
    const CROSS_EXPORT_FILES_DIR="crossExportFilesDir";
    
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
    
    
    /***
     * Tmp file name for the file from the across api
     */
    const CROSS_API_TMP_FILENAME="Apiresponsetmpfilename.tbx";
    
    /***
     * Deletes all termEntries in all listed termCollections, that have a modification date older than the listed one.
     * Since every entry that exists in a TBX gets a new updated date on TBX-import, even if it is not changed: Simply set this date to yesterday to delete all terms, that are not part of the current import
     * The updated date is a date internal to translate5 and different from the modified date of the term, that is shown in the interface
     * @var string
     */
    const DELETE_ENTRIES_KEY="deleteEntriesModifiedOlderThan";
    
    
    /***
     * Config key for deleting entries older than current import date.
     * @var string
     */
    CONST DELETE_OLDER_IMPORT_ENTRIES_KEY="deleteEntriesOlderThanCurrentImport";
    
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
    
    
    /***
     * File system import handler.
     */
    public function handleFileSystemImport(){
        
        if(empty($this->filesystemMap)){
            $this->loadFilesystemConfig();
        }
        //tbx files import folder
        $importDir=$this->filesystemMap[self::IMPORT_DIR_ARRAY_KEY];
        
        
        if (!is_dir($importDir)) {
            mkdir($importDir, 0777, true);
        }
        
        if($this->isFolderEmpty($importDir)){
            return ["The configured import dir is empty"];
        }
        
        
        //check if delete old entries is configured in the config file
        if(isset($this->filesystemMap[self::DELETE_OLDER_IMPORT_ENTRIES_KEY]) && !empty($this->filesystemMap[self::DELETE_OLDER_IMPORT_ENTRIES_KEY])){
            //remove old term entries and terms
            $this->removeEntriesOlderThenImport(date('Y-m-d H:i:s'));
        }
        
        $returnMessage=[];
        
        //get all files from the import direcotry
        $it = new FilesystemIterator($importDir, FilesystemIterator::SKIP_DOTS);
        foreach ($it as $fileinfo) {
            $file=$fileinfo->getFilename();
            
            $params=$this->handleCollectionForFile($file, $this->filesystemMap);
            
            if(!$params){
                continue;
            }
            
            if(is_string($params)){
                $returnMessage[]=$params;
                continue;
            }
                
            $model=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
            /* @var $model editor_Models_TermCollection_TermCollection */
            
            //define the import source, used for storing the file in the disk in the needed location
            $params['importSource']="filesystem";
            
            
            if(isset($this->filesystemMap[self::IMPORT_MERGE_TERMS_KEY])){
                $params['mergeTerms']=$this->filesystemMap[self::IMPORT_MERGE_TERMS_KEY] ==="true" || $this->filesystemMap[self::IMPORT_MERGE_TERMS_KEY] ==="1";
            }
            
            if($model->importTbx([$importDir.$file], $params)){
                $msg="File:".$file.' was imported in the collection:'.$params['collectionName'];
                $returnMessage[]=$msg;
                error_log($msg);
            }else{
                $msg="Unable to import the file:".$file." into the collection";
                $returnMessage[]=$msg;
                error_log("Unable to import the file:".$file." into the collection");
            }
        }
        if(empty($returnMessage)){
            $returnMessage[]="No files where imported";
        }

        //check if delete old tasks is configured in the config file
        if(isset($this->filesystemMap[self::DELETE_ENTRIES_KEY]) && !empty($this->filesystemMap[self::DELETE_ENTRIES_KEY])){
            $olderThan=$this->filesystemMap[self::DELETE_ENTRIES_KEY];
            //remove old term entries and terms
            $this->removeOldTermsAndEntries($olderThan);
        }
        
        return $returnMessage;
    }
    
    /***
     * Import the tbx files into the term collection from the across via the across api.
     * The files will be imported in the configured collection in the crossapi config file
     * 
     * @return string[]
     */
    public function handleAccrossApiImport(){
        if(empty($this->crossapiMap)){
            $this->loadAccrossConfg();
        }
        
        $returnMessage=[];
        
        //tbx files import folder
        $apiUrl=$this->crossapiMap[self::IMPORT_ACOSS_API_URL];
        $apiUser=$this->crossapiMap[self::IMPORT_ACOSS_API_USER];
        $apiPwd=$this->crossapiMap[self::IMPORT_ACOSS_API_PWD];
        
        if(empty($apiUrl)){
            $returnMessage[]="Across api url is not defined in the config file";
            return $returnMessage;
        }
        
        if(empty($apiUser) || empty($apiPwd)){
            $returnMessage[]="Authentication parameters are missing";
            return $returnMessage;
        }
        
        //tbx files import folder
        $exportFilesDir=$this->crossapiMap[self::CROSS_EXPORT_FILES_DIR];
        
        if (!is_dir($exportFilesDir)) {
            mkdir($exportFilesDir, 0777, true);
        }
        
        if($this->isFolderEmpty($exportFilesDir)){
            $returnMessage[]="Across api export files are not defined";
            return $returnMessage;
        }
        
        $returnMessage=[];
        
        //check if delete old entries is configured in the config file
        if(isset($this->filesystemMap[self::DELETE_OLDER_IMPORT_ENTRIES_KEY]) && !empty($this->filesystemMap[self::DELETE_OLDER_IMPORT_ENTRIES_KEY])){
            //remove old term entries and terms
            $this->removeEntriesOlderThenImport(date('Y-m-d H:i:s'));
        }
        
        //FIXME: split the php file into classes ?
        require_once('AcrossTbxExport.php');

        //get all across export files from the dir
        $it = new FilesystemIterator($importDir, FilesystemIterator::SKIP_DOTS);
        foreach ($it as $fileinfo) {
            $file=$fileinfo->getFilename();
            
            $connector=new TbxAcrossSoapConnector($apiUrl,$apiUser,$apiPwd);
            /* @var $connector TbxAcrossSoapConnector */
            
            $params=$this->handleCollectionForFile($file, $this->crossapiMap);
            
            if(!$params){
                continue;
            }
            //if it is a string, set the error message
            if(is_string($params)){
                $returnMessage[]=$params;
                continue;
            }
            
            //absolute file path
            $file=$exportFilesDir.$file;
            
            if(!$params || !isset($params['collectionId'])){
                continue;
            }
            
            $respTbxl=$connector->getTbx($file);
            
            if(empty($respTbxl)){
                $returnMessage[]="Empty tbx file for across config file:".$file;
                continue;
            }
            
            $tmpFile=$exportFilesDir.self::CROSS_API_TMP_FILENAME;
            
            //save the tmp file to the disc
            file_put_contents($tmpFile, $respTbxl);
            
            $model=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
            /* @var $model editor_Models_TermCollection_TermCollection */
            
            //define the import source, used for storing the file in the disk in the needed location
            $params['importSource']="crossapi";
            
            
            if(isset($this->crossapiMap[self::IMPORT_MERGE_TERMS_KEY])){
                $params['mergeTerms']=$this->crossapiMap[self::IMPORT_MERGE_TERMS_KEY] ==="true" || $this->crossapiMap[self::IMPORT_MERGE_TERMS_KEY] ==="1";;
            }
            
            if($model->importTbx([$tmpFile], $params)){
                $msg="File:".$file.' was imported in the collection:'.$params['collectionName'];
                $returnMessage[]=$msg;
                error_log($msg);
            }else{
                $msg="Unable to import the file:".$file." into the collection";
                $returnMessage[]=$msg;
                error_log("Unable to import the file:".$file." into the collection");
            }
            
            //remove the tmp file
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
        if(empty($returnMessage)){
            $returnMessage[]="No files where imported";
        }
        
        //check if delete old tasks is configured in the config file
        if(isset($this->crossapiMap[self::DELETE_ENTRIES_KEY]) && !empty($this->crossapiMap[self::DELETE_ENTRIES_KEY])){
            $olderThan=$this->crossapiMap[self::DELETE_ENTRIES_KEY];
            //remove old term entries and terms
            $this->removeOldTermsAndEntries($olderThan);
        }
        
        return $returnMessage;
    }
    
    /***
     * Check if for the current file there is config for the termcollection to tbx file association
     * and termcollection to customer number association
     * @param filepath $file: file to check
     * @param unknown $configFile: config file where the associated data is placed
     * @return NULL|string|array
     */
    private function handleCollectionForFile($file,$configFile){
        if(!isset($configFile[self::FILE_MAPPING_GROUP]) || !isset($configFile[self::FILE_MAPPING_GROUP][$file])){
            return null;
        }
        
        $collectionName=$configFile[self::FILE_MAPPING_GROUP][$file];
        
        if(!isset($configFile[self::COLLECTION_MAPPING_GROUP][$collectionName])){
            return "No customer is assigned to the collection:".$collectionName;
        }
            
        $customerNumber=$configFile[self::COLLECTION_MAPPING_GROUP][$collectionName];
        
        $cm=ZfExtended_Factory::get('editor_Models_Customer');
        /* @var $cm editor_Models_Customer */
        $customer=$cm->findCustomerByNumber($customerNumber);
        
        if(!$customer){
            return "Customer with number:".$customerNumber.' does not exist.';
        }
        
        $customerId=$customer['id'];
        
        $model=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $model editor_Models_TermCollection_TermCollection */
        
        $tc=$model->loadByName($collectionName);
        
        //if the term collection does not exist, create a new one
        if(!empty($tc)){
            return ['collectionId'=>$tc['id'],'customerId'=>$tc['customerId'],'mergeTerms'=>true,'collectionName'=>$tc['name']];
        }
            
        $model->setName($collectionName);
        $model->setCustomerId($customerId);
        
        $model->save();
        
        return ['collectionId'=>$model->getId(),'customerId'=>$customerId,'mergeTerms'=>true,'collectionName'=>$collectionName];
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
    
    private function loadFilesystemConfig(){
        $path=$this->getPluginConfigFolderPath();
        $fileSystemFile=$path.self::FILESYSTEM_CONFIG_NAME;
        
        $this->initConfigFile($fileSystemFile, $this->filesystemMap);
    }
    
    private function loadAccrossConfg(){
        $path=$this->getPluginConfigFolderPath();
        $crossApiFile=$path.self::CROSSAPI_CONFIG_NAME;
        
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
     * Remove terms older than the configured date in the config file.
     * Also remove the enpty term entries in the same term collection 
     */
    private function removeOldTermsAndEntries($olderThan){
        $termModel=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $termModel editor_Models_Term */
        
        $termModel->removeOldTerms($olderThan);
        
        //remove all empty term entries from the same term collection
        $termEntry=ZfExtended_Factory::get('editor_Models_TermCollection_TermEntry');
        /* @var $termEntry editor_Models_TermCollection_TermEntry */
        $termEntry->removeEmptyFromCollection();
    }
    
    /***
     * Remove term entries older than $importDate
     */
    private function removeEntriesOlderThenImport($impotDate){
        $termEntry=ZfExtended_Factory::get('editor_Models_TermCollection_TermEntry');
        /* @var $termEntry editor_Models_TermCollection_TermEntry */
        $termEntry->removeOlderThan($impotDate);
    }
    
    /***
     * Get the plugin config folder absolute path
     * @return string
     */
    private function getPluginConfigFolderPath(){
        $ds=DIRECTORY_SEPARATOR;
        return APPLICATION_PATH.$ds."modules".$ds."editor".$ds."Plugins".$ds."TermImport".$ds."config".$ds;
    }
    
    
    /**
     * Check if the folder contains file
     * @param string $dir
     * @return boolean
     */
    private function isFolderEmpty($dir) {
        return (($files = @scandir($dir)) && count($files) <= 2);
    }
}
