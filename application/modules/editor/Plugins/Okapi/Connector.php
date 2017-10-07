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
     * @var int
     */
    private $okapiProjectId;

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
     * Request timeout for the api
     * 
     * @var integer
     */
    const REQUEST_TIMEOUT_SECONDS = 360;
    
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
        
        $fullUrl=$this->apiUrl.$url;
        $http->setUri($fullUrl);
        $http->setConfig(array('timeout'=>self::REQUEST_TIMEOUT_SECONDS));
        return $http;
    }
    
    /***
     * Check for the status of the response. If the status is different than 200 or 201,
     * ZfExtended_BadGateway exception is thrown.
     * Also the function checks for the invalid decoded json.
     * 
     * @param Zend_Http_Response $response
     * @param boolean $responseAsXlif true if we expect xlif file as response
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
        
        $result = json_decode(trim($response->getBody()));
        
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
     * Create the project on Okapi server.
     * 
     * @return integer Okapi project id
     */
    public function createProject() {
        $http = $this->getHttpClient('projects/new');

        //$http->setHeaders('Content-Type: application/json');
        //$http->setHeaders('Accept: application/json');
        
        //$params = [
        //];
        
        //$http->setRawData(json_encode($params), 'application/json');
        $response = $http->request('POST');
        
        $responseDecoded = $this->processResponse($response);

        if(isset($responseDecoded->id)){
            $this->okapiProjectId = $responseDecoded->id;
            return $responseDecoded->id;
        }
    }
    
    /**
     * Remove the project from Okapi server.
     * 
     * @param integer $projectId
     */
    public function removeProject() {
        $url='projects/'.$this->okapiProjectId;
        $http = $this->getHttpClient($url);
        $result = $http->request('DELETE');
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