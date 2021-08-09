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
     * Deletes all terms in all listed termCollections, that have a modification date older than the listed one.
     * Since every term that exists in a TBX gets a new updated date on TBX-import, even if it is not changed: Simply set this date to yesterday to delete all terms, that are not part of the current import
     * The updated date is a date internal to translate5 and different from the modified date of the term, that is shown in the interface
     * @var string
     */
    const DELETE_ENTRIES_KEY="deleteTermsLastTouchedOlderThan";


    /***
     * Config key for deleting terms older than current import date.
     * @var string
     */
    CONST DELETE_TERMS_OLDER_THAN_IMPORT_KEY="deleteTermsOlderThanCurrentImport";


    /***
     * Config key for deletes all proposals older then deleteProposalsLastTouchedOlderThan date.
     *
     * @var string
     */
    CONST DELETE_PROPOSALS_OLDER_THAN_KEY="deleteProposalsLastTouchedOlderThan";


    /***
     * Config key form delete all proposals older than the NOW_ISO
     *
     * @var string
     */
    CONST DELETE_PROPOSALS_OLDER_THAN_IMPORT_KEY="deleteProposalsOlderThanCurrentImport";


    /***
     * Data from the filesystem or cross api config file
     * @var array
     */
    public $configMap=array();

    /**
     * @var float contains the start time of the last profiling call
     */
    protected $profilingStart = null;

    /**
     * messages return to caller
     * @var array
     */
    protected $returnMessage = [];

    public function __construct() {
        //init profiling
        $this->logProfiling();
    }

    /***
     * File system import handler.
     */
    public function handleFileSystemImport(){
        if(empty($this->configMap)){
            $this->loadConfig(self::FILESYSTEM_CONFIG_NAME);
        }
        //tbx files import folder
        $importDir = $this->configMap[self::IMPORT_DIR_ARRAY_KEY];

        try {
            if(!file_exists($importDir) && !@mkdir($importDir, 0777, true)){
                return ["Unable to create the TBX Import dir or the TBX import directory is missing. Path: ".$importDir];
            }
        } catch (Exception $e) {
            return ["Unable to create the TBX Import dir or the TBX import directory is missing. Path: ".$importDir];
        }

        if($this->isFolderEmpty($importDir)){
            return ["The configured import dir is empty"];
        }

        $this->returnMessage=[];

        $this->logProfiling('Init FileSystemImport');
        //get all files from the import direcotry
        $it = new FilesystemIterator($importDir, FilesystemIterator::SKIP_DOTS);
        $affectedCollections = [];
        foreach ($it as $fileinfo) {
            $file=$fileinfo->getFilename();

            $params=$this->handleCollectionForFile($file);

            if(!$params){
                continue;
            }

            if(is_string($params)){
                $this->returnMessage[]=$params;
                continue;
            }
            $affectedCollections[] = $params['collectionId'];
            $this->logProfiling('Prepared collection '.$params['collectionName'].'('.$params['collectionId'].')');

            //define the import source, used for storing the file in the disk in the needed location
            $params['importSource']="filesystem";

            $this->importTbx($file, $importDir.$file, $params);

            //remove old term entries and terms
            $this->removeTermsOlderThenImport($params['collectionId']);

            //remove term proposals
            $this->removeProposalsOlderThan($params['collectionId']);

        }
        if(empty($this->returnMessage)){
            $this->returnMessage[]="No files where imported";
        }

        if(empty($affectedCollections)){
            return $this->returnMessage;
        }

        //remove old terms
        $this->removeOldTerms($affectedCollections);

        //remove proposals older than current import
        $this->removeProposalsOlderThenImport($affectedCollections);

        //clean the empty term entries
        $this->removeEmptyTermEntries($affectedCollections);

        return $this->returnMessage;
    }

    /***
     * Import the tbx files into the term collection from the across via the across api.
     * The files will be imported in the configured collection in the crossapi config file
     *
     * @return string[]
     */
    public function handleAccrossApiImport(){
        if(empty($this->configMap)){
            $this->loadConfig(self::CROSSAPI_CONFIG_NAME);
        }

        $this->returnMessage=[];

        //tbx files import folder
        $apiUrl=$this->configMap[self::IMPORT_ACOSS_API_URL];
        $apiUser=$this->configMap[self::IMPORT_ACOSS_API_USER];
        $apiPwd=$this->configMap[self::IMPORT_ACOSS_API_PWD];

        if(empty($apiUrl)){
            $this->returnMessage[]="Across api url is not defined in the config file";
            return $this->returnMessage;
        }

        if(empty($apiUser) || empty($apiPwd)){
            $this->returnMessage[]="Authentication parameters are missing";
            return $this->returnMessage;
        }

        //tbx files import folder
        $exportFilesDir=$this->configMap[self::CROSS_EXPORT_FILES_DIR];

        try {
            if(!file_exists($exportFilesDir) && !@mkdir($exportFilesDir, 0777, true)){
                return ["Unable to create the TBX Import dir or the TBX import directory is missing. Path: ".$exportFilesDir];
            }
        } catch (Exception $e) {
            return ["Unable to create the TBX Import dir or the TBX import directory is missing. Path: ".$exportFilesDir];
        }

        if($this->isFolderEmpty($exportFilesDir)){
            $this->returnMessage[]="Across api export files are not defined";
            return $this->returnMessage;
        }

        //FIXME: split the php file into classes ?
        require_once('AcrossTbxExport.php');

        //get all across export files from the dir
        $it = new FilesystemIterator($exportFilesDir, FilesystemIterator::SKIP_DOTS);
        $affectedCollections = [];
        $this->logProfiling('Init FileAcrossApiImport');
        foreach ($it as $fileinfo) {
            $file=$fileinfo->getFilename();

            $connector=new TbxAcrossSoapConnector($apiUrl,$apiUser,$apiPwd);
            /* @var $connector TbxAcrossSoapConnector */

            $params=$this->handleCollectionForFile($file);

            if(!$params){
                continue;
            }
            //if it is a string, set the error message
            if(is_string($params)){
                $this->returnMessage[]=$params;
                continue;
            }

            if(!$params || !isset($params['collectionId'])){
                continue;
            }

            //absolute file path
            $file=$exportFilesDir.$file;


            $affectedCollections[] = $params['collectionId'];

            $respTbxl=$connector->getTbx($file);

            if(empty($respTbxl)){
                $this->returnMessage[]="Empty tbx file for across config file:".$file;
                continue;
            }

            $tmpFile=$exportFilesDir.self::CROSS_API_TMP_FILENAME;

            //save the tmp file to the disc
            file_put_contents($tmpFile, $respTbxl);

            //define the import source, used for storing the file in the disk in the needed location
            $params['importSource']="crossapi";

            $this->importTbx($file, $tmpFile, $params);

            //remove old term entries and terms
            $this->removeTermsOlderThenImport($params['collectionId']);

            //remove term proposals
            $this->removeProposalsOlderThan($params['collectionId']);

            //remove the tmp file
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
        if(empty($this->returnMessage)){
            $this->returnMessage[]="No files where imported";
        }

        if(empty($affectedCollections)){
            return $this->returnMessage;
        }

        //remove old terms
        $this->removeOldTerms($affectedCollections);

        //remove proposals older than current import
        $this->removeProposalsOlderThenImport($affectedCollections);

        //clean the empty term entries
        $this->removeEmptyTermEntries($affectedCollections);

        return $this->returnMessage;
    }

    protected function importTbx($file, $absFile, $params) {
        $this->logProfiling();
        $model = ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $model editor_Models_TermCollection_TermCollection */
        if(isset($this->configMap[self::IMPORT_MERGE_TERMS_KEY])){
            $params['mergeTerms']=$this->configMap[self::IMPORT_MERGE_TERMS_KEY] ==="true" || $this->configMap[self::IMPORT_MERGE_TERMS_KEY] ==="1";
        }

        if($model->importTbx([$absFile], $params)){
            $msg="File: ".$file.' was imported in the collection: '.$params['collectionName'];
            $this->returnMessage[]=$msg;
            error_log($msg);
        }else{
            $msg="Unable to import the file: ".$file." into the collection";
            $this->returnMessage[]=$msg;
            error_log("Unable to import the file: ".$file." into the collection");
        }
        $this->logProfiling('Imported TBX');
    }

    /***
     * Check if for the current file there is config for the termcollection to tbx file association
     * and termcollection to customer number association
     * @param string $file: file to check
     * @return NULL|string|array
     */
    private function handleCollectionForFile($file){
        if(!isset($this->configMap[self::FILE_MAPPING_GROUP]) || !isset($this->configMap[self::FILE_MAPPING_GROUP][$file])){
            return null;
        }

        $collectionName=$this->configMap[self::FILE_MAPPING_GROUP][$file];

        if(!isset($this->configMap[self::COLLECTION_MAPPING_GROUP][$collectionName])){
            return "No customer is assigned to the collection:".$collectionName;
        }

        $customerNumber=$this->configMap[self::COLLECTION_MAPPING_GROUP][$collectionName];

        $customer = ZfExtended_Factory::get('editor_Models_Customer');
        /* @var $customer editor_Models_Customer */
        $customer->loadByNumber($customerNumber);
        $customerId = $customer->getId();

        $model=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $model editor_Models_TermCollection_TermCollection */

        $tc=$model->loadByName($collectionName);

        //if the term collection exist, return the config import array array
        if(!empty($tc)){
            $customerAssoc=ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
            /* @var $customerAssoc editor_Models_LanguageResources_CustomerAssoc */
            $customers=$customerAssoc->loadByLanguageResourceId($tc['id']);
            $customers=array_column($customers, 'customerId');

            //check if the customer exist in the assoc table
            if(!in_array($customerId, $customers)){
                $customers[]=$customerId;
                //add the customer to the assoc table for the term collection
                $customerAssoc->addAssocs($tc['id'], [$customerId]);
            }
            return ['collectionId'=>$tc['id'],'customerIds'=>$customers,'mergeTerms'=>true,'collectionName'=>$tc['name']];
        }

        //create new term collection/language resource
        $collection=$model->create($collectionName, [$customerId]);

        return ['collectionId'=>$collection->getId(),'customerIds'=>[$customerId],'mergeTerms'=>true,'collectionName'=>$collectionName];
    }

    private function loadConfig($configName){
        $path=$this->getPluginConfigFolderPath();
        $this->initConfigFile($path.$configName);
    }

    /***
     * Init the config array
     *
     * @param string $filePath : absolute path to the config file
     *
     * @throws ZfExtended_ValidateException
     */
    private function initConfigFile($filePath){
        if(!file_exists($filePath)){
            throw new ZfExtended_ValidateException("Configuration file is missing:".$filePath);
        }
        $file=file_get_contents($filePath);
        if(empty($file)){
            throw new ZfExtended_ValidateException("The configuration file:".$filePath.' is empty.');
        }

        $this->configMap = parse_ini_file($filePath,true);
        if(empty($this->configMap)){
            throw new ZfExtended_ValidateException("Wrong file structure in :".$filePath);
        }
    }

    /***
     * Remove terms older than the configured date in the config file.
     * @param array $collectionIds
     * @param string $olderThan
     */
    private function removeOldTerms(array $collectionIds){
        //check if delete old tasks is configured in the config file
        if(empty($this->configMap[self::DELETE_ENTRIES_KEY])){
            return;
        }
        $olderThan=$this->configMap[self::DELETE_ENTRIES_KEY];

        $termModel=ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $termModel editor_Models_Terminology_Models_TermModel */
        $termModel->removeOldTerms($collectionIds, $olderThan);
        //clean the old tbx files from the disc
        $this->removeCollectionTbxFromDisc($collectionIds, strtotime($olderThan));
        $this->logProfiling('removeOldTerms for collections '.join(',', $collectionIds));
    }

    /***
     * Remove terms older than current date: NOW_ISO
     *
     * @param int $collectionId
     */
    private function removeTermsOlderThenImport($collectionId){
        //check if delete old terms is configured in the config file
        if(empty($this->configMap[self::DELETE_TERMS_OLDER_THAN_IMPORT_KEY])){
            return;
        }

        $termModel=ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $termModel editor_Models_Terminology_Models_TermModel */
        $termModel->removeOldTerms([$collectionId], NOW_ISO);
        //clean the old tbx files from the disc
        $this->removeCollectionTbxFromDisc([$collectionId], strtotime(NOW_ISO));
        $this->logProfiling('removeTermsOlderThenImport for collection '.$collectionId);
    }

    /***
     * Delete all proposals older than deleteProposalsLastTouchedOlderThan date.
     *
     * @param int $collectionId
     */
    private function removeProposalsOlderThan($collectionId){
        //check if delete old entries is configured in the config file
        if(empty($this->configMap[self::DELETE_PROPOSALS_OLDER_THAN_KEY])){
            return;
        }
        $olderThan=$this->configMap[self::DELETE_PROPOSALS_OLDER_THAN_KEY];;
        $this->removeOldProposals([$collectionId], $olderThan);
        $this->logProfiling('removeProposalsOlderThan for collection '.$collectionId);
    }

    /***
     * Remove proposals older than curent import
     * @param array $collectionIds
     */
    private function removeProposalsOlderThenImport(array $collectionIds){
        //check if delete old terms is configured in the config file
        if(empty($this->configMap[self::DELETE_TERMS_OLDER_THAN_IMPORT_KEY])){
            return;
        }
        $this->removeOldProposals($collectionIds, NOW_ISO);
        $this->logProfiling('removeProposalsOlderThenImport for collection '.print_r($collectionIds,1));
    }

    /***
     * Remove empty term entries (term entries without any term in it).
     * Only the empty term entries from the same term collection will be removed.
     *
     * @param array $collectionIds
     */
    protected function removeEmptyTermEntries(array $collectionIds)
    {
        //remove all empty term entries from the same term collection
        $termEntry = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');
        /* @var $termEntry editor_Models_Terminology_Models_TermEntryModel */
        $termEntry->removeEmptyFromCollection($collectionIds);
        $this->logProfiling('removeEmptyTermEntries for collections '.join(',', $collectionIds));
    }

    /***
     * Remove proposals for given collection and where the last change for the proposal is older than $olderThan date
     *
     * @param array $collectionIds
     * @param string $olderThan
     */
    protected function removeOldProposals(array $collectionIds,string $olderThan){

        // Remove term proposals
        $term = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $term editor_Models_Terminology_Models_TermModel */
        $term->removeProposalsOlderThan($collectionIds,$olderThan);

        // Remove attribute proposals
        $attribute = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
        /* @var $attribute editor_Models_Terminology_Models_AttributeModel */
        $attribute->removeProposalsOlderThan($collectionIds,$olderThan);
    }


    /****
     * Remove term collection tbx files from the tbx-import directory older than the given timestamp
     * @param array $collections
     * @param int $olderThan
     */
    protected function removeCollectionTbxFromDisc(array $collections,int $olderThan){
        $collection=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $collection editor_Models_TermCollection_TermCollection */
        foreach ($collections as $c) {
            $collection->removeOldCollectionTbxFiles($c, $olderThan);
        }
        $this->logProfiling('removeCollectionTbxFromDisc for collections '.join(',', $collections));
    }

    /***
     * Get the plugin config folder absolute path
     * @return string
     */
    private function getPluginConfigFolderPath(){
        return APPLICATION_PATH.'/modules/editor/Plugins/TermImport/config/';
    }


    /**
     * Check if the folder contains file
     * @param string $dir
     * @return boolean
     */
    private function isFolderEmpty($dir) {
        return (($files = @scandir($dir)) && count($files) <= 2);
    }

    /**
     * logs a message to the error log and prints the duration needed
     * @param string $msg if empty just reset start timer and log nothing
     */
    protected function logProfiling($msg = null) {
        if(!empty($msg)) {
            $duration = microtime(true) - $this->profilingStart;
            error_log('Profiling TermPortal Import - '.$msg.": \n  Duration (seconds): ".$duration);
        }
        $this->profilingStart = microtime(true);
    }
}
