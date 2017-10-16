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
 * Upload/download file to okapi server, and converting it to xlf
 * One Connector Instance can contain one Okapi Project
 */
class editor_Plugins_Okapi_Connector {
    
    /***
     *
     * The url for connecting the Okapi api
     * 
     * @var string
     */
    private $apiUrl;
    
    /***
     * The url for the current  active project
     * @var string
     */
    private $projectUrl;

    /***
    * Zf config for Okapi
    */
    private $okapiConfig;
    /* @var $config Zend_Config */
    
    
    /***
     * The filepath of the import bconfig file
     * @var string
     */
    private $bconfFilePath;
    
    /***
     * The file which need to be converted
     * @var string
     */
    private $inputFile;
    
    /***
     * Request timeout for the api
     * 
     * @var integer
     */
    const REQUEST_TIMEOUT_SECONDS = 360;
    
    /***
     * The file extenssion of the converted file
     *  
     * @var string
     */
    const OUTPUT_FILE_EXTENSION='.xlf';
    
    /***
     * The temporary used okapi extension, so we make difference between import files generated by okapi
     * @var string
     */
    const OKAPI_FILE_EXTENSION='.okapi';
    
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
            throw new ZfExtended_BadGateway("HTTP Status was not 200/201/401 body: ".$response->getBody(), 500);
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
        $this->processResponse($response);
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
        $response= $http->request('DELETE');
        $this->processResponse($response);
    }
    
    /***
     * Upload the bconf file
     */
    public function uploadOkapiConfig(){
        if(empty($this->getBconfFilePath())){
            return;
        }
        $bconfFilePath=$this->getBconfFilePath();
        $url=$this->projectUrl.'/batchConfiguration';
        $http = $this->getHttpClient($url);
        $http->setFileUpload($bconfFilePath[0], 'batchConfiguration');
        $response = $http->request('POST');
        $this->processResponse($response);
    }
    
    /***
     * Upload the source file(the file which will be converted)
     */
    public function uploadSourceFile(){
        //PUT http://{host}/okapi-longhorn/projects/1/inputFiles/help.html
        //Ex.: Uploads a file that will have the name 'help.html'
        
        $file=$this->getInputFile();
        $name=$file['fileName'];
        $filePath=$file['filePath'];
        
        //we encode the filename, because the okapi api does not support whitespace in files
        $url=$this->projectUrl.'/inputFiles/'.urlencode($name);
        $http = $this->getHttpClient($url);
        $http->setFileUpload($filePath,'inputFile');
        $response = $http->request('PUT');
        $this->processResponse($response);
            
    }
    
    /***
     * Run the file conversion. For each uploaded files converted file will be created
     */
    public function executeTask(){
        $url=$this->projectUrl.'/tasks/execute';
        $http = $this->getHttpClient($url);
        $response = $http->request('POST');
        $this->processResponse($response);
    }
    
    
    /***
     * Download the converted file from okapi, and save the file on the disk.
     */
    public function downloadFile(){
        $file=$this->getInputFile();
        //we encode the filename, because the okapi api does not support whitespace in files
        $url=$this->projectUrl.'/outputFiles/pack1/work/'.urlencode($file['fileName']).self::OUTPUT_FILE_EXTENSION;
        $http = $this->getHttpClient($url);
        $response = $http->request('GET');
        $responseFile=$this->processResponse($response);
        //create the file in the proffRead folder, with the okapi extension so we know who generate this file
        file_put_contents($file['filePath'].self::OUTPUT_FILE_EXTENSION.self::OKAPI_FILE_EXTENSION, $responseFile);
    }
    
    public function setBconfFilePath($confPath){
        $this->bconfFilePath=$confPath;
    }
    
    public function getBconfFilePath(){
        return $this->bconfFilePath;
    }
    
    public function setInputFile($file){
        $this->inputFile=$file;
    }
    
    public function getInputFile(){
        return $this->inputFile;
    }
    
}