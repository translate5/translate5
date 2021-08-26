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
 * Connector to Globalese
 * One Connector Instance can contain one Globalese Project
 */
class editor_Plugins_GlobalesePreTranslation_Connector {
    
    /***
     *
     * Globalese file statuses
     */
    const GLOBALESE_FILESTATUS_OK = 'ok';
    const GLOBALESE_FILESTATUS_TRANSLATED='translated';
    const GLOBALESE_FILESTATUS_IN_TRANSLATION= 'in_translation';
    const GLOBALESE_FILESTATUS_ERROR= 'error';
    
    /***
     * Request timeout for the api
     *
     * @var integer
     */
    const REQUEST_TIMEOUT_SECONDS = 360;
    
    /***
     * 
     * @var $config Zend_Config
     */
    private $globaleseConfig;
    
    /***
     * 
     * @var string
     */
    private $apiUrl;
    
    /***
     *
     * @var string
     */
    private $username;
    
    /***
     *
     * @var string
     */
    private $apiKey;
    
    /***
     * 
     * @var int
     */
    private $globaleseProjectId;
    
    /***
     * 
     * @var array
     */
    private $globaleseFileIds=[];
    
    /***
     * Globalese project user-group
     * 
     * @var int
     */
    private $group;
    
    /***
     * Globalese project engine
     * 
     * @var int
     */
    private $engine;
    
    /***
     * Source language as Rfc5646 value 
     * @var string
     */
    private $sourceLang;
    
    /***
     *  Target language as Rfc5646 value 
     * @var string
     */
    private $targetLang;
    
    /***
     * Globalese files with errors
     */
    private $filesWithErrors = [];
    
    /***
     * Instance of the current task
     * 
     * @var editor_Models_Task
     */
    private $m_task;
    
    
    /***
     * Ignore error messages from globalese
     * @var array
     */
    private $ignoreErrorMessages=[
        'Error uploading file. The uploaded file contains no untranslated segments.'
    ];
    
    
    public function __construct() {
        $this->globaleseConfig= Zend_Registry::get('config')->runtimeOptions->plugins->GlobalesePreTranslation;
        /* @var $config Zend_Config */
        
        $this->apiUrl=$this->globaleseConfig->api->url;
    }
    
    /***
     * Create the http object, set the authentication and set the url
     * 
     * @param string $url
     * @return Zend_Http_Client
     */
    private function getHttpClient($url){
        $http = ZfExtended_Factory::get('Zend_Http_Client');
        /* @var $http Zend_Http_Client */
        
        $http->setAuth($this->username,$this->apiKey);
        $http->setUri($this->apiUrl.$url);
        $http->setConfig(array('timeout'=>self::REQUEST_TIMEOUT_SECONDS));
        return $http;
    }
    
    /***
     * Check for the status of the response. If the status is different than 200 or 201,
     * ZfExtended_BadGateway exception is thrown.
     * Also the function checks for the invalid decoded json.
     * 
     * @param Zend_Http_Response $response
     * @param bool $responseAsXlif true if we expect xlif file as response
     * @throws ZfExtended_BadGateway
     * @throws ZfExtended_Exception
     * @return stdClass|string
     */
    private function processResponse(Zend_Http_Response $response,$responseAsXlif=false){
        $validStates = [200,201,401];
        
        //check for HTTP State (REST errors)
        if(!in_array($response->getStatus(), $validStates)) {
            throw new ZfExtended_BadGateway($response->getBody(), 500);
        }
        
        //if the user is unauthorized
        if($response->getStatus() == 401){
            throw new ZfExtended_NotAuthenticatedException($response->getBody(),401);
        }
            
        if($responseAsXlif){
            return $response->getBody();
        }
        
        $body = trim($response->getBody());
        
        //do not decode valid response with empty content
        if(empty($body)){
            return null;
        }
        
        $result = json_decode($body);
        
        //is valid response with 
        if(is_array($result) && count($result) < 1){
            return $response->getBody();
        }
        
        //check for JSON errors
        if(json_last_error() > 0){
            throw new ZfExtended_Exception("Error on JSON decode: ".json_last_error_msg(), 500);
        }
        
        return $result;
    }
    
    /**
     * Create the project on globalese server.
     * 
     * @return integer globalese project id
     */
    public function createProject() {
        $projectname = "Translate5-".$this->getTask()->getTaskGuid();
        
        $http = $this->getHttpClient('projects');

        $http->setHeaders('Content-Type: application/json');
        $http->setHeaders('Accept: application/json');
        
        $params = [
                'name'=>$projectname,
                'source-language'=>$this->getSourceLang(),
                'target-language'=>$this->getTargetLang(),
                'group'=>$this->getGroup(),
                'engine'=>$this->getEngine()
        ];
        
        $http->setRawData(json_encode($params), 'application/json');
        $response = $http->request('POST');
        
        $responseDecoded = $this->processResponse($response);

        if(isset($responseDecoded->id)){
            $this->globaleseProjectId = $responseDecoded->id;
            return $responseDecoded->id;
        }
    }
    
    /**
     * Remove the project from globalese server.
     * 
     * @param int $projectId
     */
    public function removeProject() {
        $url='projects/'.$this->globaleseProjectId;
        $http = $this->getHttpClient($url);
        $result = $http->request('DELETE');
    }
    
    /**
     * This function will create the file, also upload the xliff file
     * and start the translation for this file in globalese.
     * 
     * @param string $filename
     * @param string $xliff the xliff content as plain string
     * @return integer the fileid of the generated file
     */
    public function upload($filename, $xliff) {
        //throw an error if internal projectId is empty
        if(!$this->globaleseProjectId){
            throw new ZfExtended_Exception('internal globalese project ID is empty!');
        }
        
        try {
            //creates file in Globalese
            $fileId = $this->createFile($filename);
            //uploads file to Globalese
            $this->uplodFile($fileId, $xliff);
            //starts translation in Globalese
            $this->translateFile($fileId);

            array_push($this->globaleseFileIds, $fileId);
            
            return $fileId;
        } catch (Exception $ex) {
            $this->deleteFile($fileId);
            /* @var $erroLog ZfExtended_Log */
            $message=$ex->getMessage();
            $message=json_decode($message);
            if(in_array($message->error, $this->ignoreErrorMessages)){
                return "";
            }
            $erroLog= ZfExtended_Factory::get('ZfExtended_Log');
            $erroLog->logError("Error occurred during file upload or translation (taskGuid=".$this->getTask()->getTaskGuid()."),(globalese file id = ".$fileId.")".$ex->getMessage());
        }
        
    }
    
    /***
     * Create the file on globalese server
     * 
     * @param string $filename the name of the file
     * @return string the globalese id of the file
     */
    private function createFile($filename){
        $http = $this->getHttpClient('translation-files');
        $http->setHeaders('Content-Type: application/json');
        $http->setHeaders('Accept: application/json');
        
        $params = [
                'project'=>$this->globaleseProjectId,
                'name'=>$filename,
                'source-language'=>$this->getSourceLang(),
                'target-language'=>$this->getTargetLang()
        ];
        
        $http->setRawData(json_encode($params), 'application/json');
        $response = $http->request('POST');
        
        $result=$this->processResponse($response);
        
        if(!$result->id){
            return null;
        }
        return $result->id;
    }
    
    /***
     * Upload the xliff file to globalese server
     * 
     * @param string $fileId
     * @param string $xliff
     */
    private function uplodFile($fileId,$xliff){
        $url='translation-files/'.$fileId;
        $http = $this->getHttpClient($url);
        $http->setRawData($xliff);
        $response = $http->request('POST');
        
        $this->processResponse($response);
    }
    
    /***
     * Start the translation for file with the given fileid
     * @param string $fileId
     */
    private function translateFile($fileId){
        $url='translation-files/'.$fileId.'/translate';
        $http = $this->getHttpClient($url);
        $response = $http->request('POST');
        
        //logging when the request was not successfull
        $this->processResponse($response);
    }
    
    /***
     * Delete the file on globalese server, and removes the file from local stack
     * 
     * @param string $fileId
     */
    private function deleteFile($fileId){
        $url='translation-files/'.$fileId;
        $http = $this->getHttpClient($url);
        $result = $http->request('DELETE');
        
        //remove the file from the local stack
        $key = array_search($fileId, $this->globaleseFileIds);
        unset($this->globaleseFileIds[$key]);
    }
    
    /**
     * returns the first found translated fileid, 
     * @return mixed fileId of found file, null when there are pending files but non finished, false if there are no more files
     */
    public function getFirstTranslated() {
        $filesCount=count($this->globaleseFileIds);
        if($filesCount<1){
            return false;
        }
        foreach($this->globaleseFileIds as $fileId) {
            $url='translation-files/'.$fileId;

            $http = $this->getHttpClient($url);
            
            $response = $http->request('GET');
            
            $decode=$this->processResponse($response);
            
            if($decode->status == self::GLOBALESE_FILESTATUS_ERROR){
                //add the globalese file id for the file that the error occurs
                $this->filesWithErrors[] = $fileId;
                $this->deleteFile($fileId);
                return empty($this->globaleseFileIds) ? false : null;
            }
            
            if($decode->status == self::GLOBALESE_FILESTATUS_TRANSLATED){
                return $fileId;
            }
        }
        return null;
    }
    
    /**
     * Gets the file content to the given fileid 
     * 
     * @param int $fileId
     * @param bool $remove default true, if true removes the fetched file immediatelly from Globalese project
     * @return string (translated file from globalese as string)
     */
    public function getFileContent($fileId, $remove = true) {
        $url='translation-files/'.$fileId.'/download?state='.self::GLOBALESE_FILESTATUS_TRANSLATED;
        $http = $this->getHttpClient($url);
        $response = $http->request('GET');
        try{
            $result = $this->processResponse($response,true);
        } catch (ZfExtended_Exception $ex) {
            $this->deleteFile($fileId);
            /* @var $erroLog ZfExtended_Log */
            $erroLog= ZfExtended_Factory::get('ZfExtended_Log');
            $erroLog->logError("Error occurred during file download (taskGuid=".$this->getTask()->getTaskGuid()."),(globalese file id = ".$fileId.")".$ex->getMessage());
        }
        if($remove){
            $this->deleteFile($fileId);
        }
        return $result;
    }
    
    /***
     * Gets the engines for given source and target language.
     * 
     * @param string $sourceLang
     * @param string $targetLang
     * @return string|mixed
     */
    public function getEngines($sourceLang,$targetLang){
        
        $langModel = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $langModel editor_Models_Languages */
        
        $sourceRfc5646=$langModel->loadLangRfc5646($sourceLang);
        $targetRfc5646=$langModel->loadLangRfc5646($targetLang);
        
        $url='engines/?source='.$sourceRfc5646.'&target='.$targetRfc5646;//.'&status=ok';
        
        $http = $this->getHttpClient($url);
        $http->setHeaders('Content-Type: application/json');
        $http->setHeaders('Accept: application/json');
        $response = $http->request('GET');

        $result = $this->processResponse($response);

        $retVal = [];
        if(empty($result) || is_string($result)){
            return $retVal;
        }
        //return only the engines where the ready property is true 
        foreach ($result as $engine){
            if(isset($engine->ready) && boolval($engine->ready)){
                $retVal[] = $engine;
            }
        }
        
        return $retVal;
    }
    
    /***
     * Get all groups where the auth user belongs to.
     * @return string|mixed
     */
    public function getGroups(){
        $http = $this->getHttpClient('groups');
        $http->setHeaders('Content-Type: application/json');
        $http->setHeaders('Accept: application/json');
        $response = $http->request('GET');
        
        $result = $this->processResponse($response);
        
        return $result;
    }
    
    public function setAuth($username,$apiKey){
        $this->username = $username;
        $this->apiKey = $apiKey;
    }
    
    public function setGroup($group){
        $this->group = $group;
    }
    
    public function getGroup(){
        return $this->group;
    }
    
    public function setEngine($engine){
        $this->engine = $engine;
    }
    
    public function getEngine(){
        return $this->engine;
    }
    
    public function setSourceLang($sourceLang){
        $this->sourceLang=$sourceLang;
    }
    
    public function getSourceLang(){
        return $this->sourceLang;
    }
    
    public function setTargetLang($targetLang){
        $this->targetLang=$targetLang;
    }
    
    public function getTargetLang(){
        return $this->targetLang;
    }
    
    public function getFilesWithErrors(){
        return $this->filesWithErrors;
    }
    
    public function setTask($task){
        $this->m_task=$task;
    }
    
    public function getTask(){
        return $this->m_task;
    }
}