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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Connector to Okapi
 * One Connector Instance can contain one Okapi Project
 */
class editor_Plugins_Okapi_Connector {
    
    /***
     * 
     * @var string
     */
    private $apiUrl;
    
    /***
     * 
     * @var string
     */
    private $projectUrl;

    /*
    * Zf config for Okapi
    */
    private $okapiConfig;
    /* @var $config Zend_Config */
    
    /***
     * Okapi files with errors
     */
    private $filesWithErrors = [];
    
    /***
     * Instance of the current task
     * 
     * @var editor_Models_Task
     */
    private $m_task;
    
    /***
     * The folder in the disk where the okapi files are
     * @var string
     */
    private $okapiDir;

    /***
     * Request timeout for the api
     * 
     * @var integer
     */
    const REQUEST_TIMEOUT_SECONDS = 360;
    
    const OUTPUT_FILE_EXTENSION='.xlf';
    
    public function __construct() {
        $this->okapiConfig= Zend_Registry::get('config')->runtimeOptions->plugins->Okapi;
        /* @var $config Zend_Config */
        
        $this->apiUrl=$this->okapiConfig->api->url;
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
        
        $http->setUri($url);
        $http->setConfig(array('timeout'=>self::REQUEST_TIMEOUT_SECONDS));
        return $http;
    }
    
    /***
     * Check for the status of the response. If the status is different than 200 or 201,
     * ZfExtended_BadGateway exception is thrown.
     * Also the function checks for the invalid decoded json.
     * 
     * @param Zend_Http_Response $response
     * @throws ZfExtended_BadGateway
     * @throws ZfExtended_Exception
     * @return stdClass|string
     */
    private function processResponse(Zend_Http_Response $response){
        $validStates = [200,201,401];
        
        //check for HTTP State (REST errors)
        if(!in_array($response->getStatus(), $validStates)) {
            throw new ZfExtended_BadGateway($response->getBody(), 500);
        }
        
        return $response->getBody();
    }
    
    /**
     * Create the project on Okapi server.
     * 
     * @return integer Okapi project id
     */
    public function createProject() {
        $http = $this->getHttpClient($this->apiUrl.'projects/new');
        $response = $http->request('POST');
        $url=$response->getHeader('Location');
        $this->projectUrl= $url;
    }
    
    /**
     * Remove the project from Okapi server.
     * 
     * @param integer $projectId
     */
    public function removeProject() {
        $url=$this->projectUrl;
        $http = $this->getHttpClient($url);
        $result = $http->request('DELETE');
        
    }
    
    public function uploadOkapiConfig($bconfFilePath){
        if(empty($bconfFilePath)){
            return;
        }
        
        $url=$this->projectUrl.'/batchConfiguration';
        $http = $this->getHttpClient($url);
        $http->setFileUpload($bconfFilePath[0], 'batchConfiguration');
        $response = $http->request('POST');
        //$this->processResponse($response);
    }
    
    public function uploadSourceFile($file){
        //PUT http://{host}/okapi-longhorn/projects/1/inputFiles/help.html
        //Uploads a file that will have the name 'help.html'
        
        $name=$file['fileName'];
        $filePath=$file['filePath'];
        
        //in this point the tmp imort is deleted
        $url=$this->projectUrl.'/inputFiles/'.$name;
        $http = $this->getHttpClient($url);
        $http->setFileUpload($filePath,'inputFile');
        $response = $http->request('PUT');
            
    }
    
    public function executeTask(){
        $url=$this->projectUrl.'/tasks/execute';
        $http = $this->getHttpClient($url);
        $response = $http->request('POST');
    }
    
    public function downloadFile($file){
        $url=$this->projectUrl.'/outputFiles/pack1/work/'.$file['fileName'].self::OUTPUT_FILE_EXTENSION;
        $http = $this->getHttpClient($url);
        $response = $http->request('GET');
        $responseFile=$this->processResponse($response);
        file_put_contents($file['outputFolder'].DIRECTORY_SEPARATOR.$file['fileName'].self::OUTPUT_FILE_EXTENSION, $responseFile);
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
    
    public function setOkapiDir($okapiDir){
        $this->okapiDir= $okapiDir;
    }
    
    public function getOkapiDir(){
        return $this->okapiDir;
    }
    
}