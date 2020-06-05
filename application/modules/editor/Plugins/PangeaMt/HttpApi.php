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
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

class editor_Plugins_PangeaMt_HttpApi {
    
    const BASE_PATH = '/NexRelay/v1';
    
    const ENC_TYPE = 'application/json; charset=utf-8';
    
    /**
     * Api-URL from zf configuration
     * @var string
     */
    protected $apiUrl;
    
    /***
     * Authentication key
     * @var string
     */
    protected $apiKey;
    /**
     * @var stdClass
     */
    protected $result;
    
    
    protected $error = array();
    
    public function __construct() {
        $this->initApi();
    }
    
    /***
     * init api authentication data
     * @throws ZfExtended_ValidateException
     */
    protected function initApi(){
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */
        $logger = Zend_Registry::get('logger');
        $this->apiKey = $config->runtimeOptions->plugins->PangeaMt->apikey ?? null ;
        if(empty($this->apiKey)){
            $logger->error('E1272', 'PangeaMt Plug-In: Apikey is not defined.');
        }
    }
    
    /**
     * Search the api for the engines that PangeaMt offers for us.
     */
    public function getEngines() {
        $method = 'POST';
        $endpointPath = 'corp/engines';
        if($this->pangeaMtRequest($method, $endpointPath)) {
            $this->result = $this->result->engines;
            return true;
        }
        return false;
    }
    
    /**
     * Search the api for given source/target language
     * 
     * @param string $text
     * @param string $sourceLang
     * @param string $targetLang
     * @param boolean $isInstantTranslate (optional; default: false)
     * @return boolean
     */
    public function search($queryString,$sourceLang,$targetLang,$isInstantTranslate = false) {
        // TODO
        return false;
    }
    
    /** Check the api status
     * @return boolean
     */
    public function getStatus(){
        return $this->getEngines(); // test status by requesting the engines that the API provides
    }
    
    // -------------------------------------------------------------------------
    // General handling of the API-requests
    // TODO: most of the following code is the same for each language-resource...
    // -------------------------------------------------------------------------
    
    /**
     * "Lazy load" and return of the configured API's URL.
     * @return string
     */
    protected function getApiUrl(): string {
        if (!$this->apiUrl) {
            $config = Zend_Registry::get('config');
            /* @var $config Zend_Config */
            $urls = $config->runtimeOptions->plugins->PangeaMt->server->toArray();
            if (empty($urls) || empty($urls[0])) {
                $this->throwBadGateway();
            }
            $this->apiUrl = $urls[0];
        }
        return $this->apiUrl;
    }
    
    /**
     * Returns the URL to the service.
     * @param string $endpointPath
     * @return string
     */
    protected function getUrl($endpointPath = '') {
        return $this->getApiUrl().self::BASE_PATH.'/'.ltrim($endpointPath, '/');
    }
    
    /**
     * prepares a Zend_Http_Client, prefilled with the configured URL + the given REST URL Parts
     * @param string $method
     * @param string $endpointPath
     * @return Zend_Http_Client
     */
    protected function getHttp($method, $endpointPath = '') {
        $this->http = ZfExtended_Factory::get('Zend_Http_Client');
        /* @var $http Zend_Http_Client */
        $this->http->setUri($this->getUrl($endpointPath));
        $this->http->setMethod($method);
        $this->httpMethod = $method;
        $this->http->setHeaders('Accept-charset', 'UTF-8');
        $this->http->setHeaders('Accept', self::ENC_TYPE);
        $this->http->setHeaders('Content-Type', self::ENC_TYPE);
        return $this->http;
    }
    
    /**
     * Sends a request to PangeaMt's Api Service.
     * @param string $method
     * @param string $endpointPath
     * @param array $data (optional)
     * @param array $queryParams (optional)
     * @return boolean
     */
    protected function pangeaMtRequest($method, $endpointPath = '', $data = [], $queryParams = []) {
        $http = $this->getHttp($method, $endpointPath);
        $http->setParameterGet($queryParams);
        $data[] = ["apikey" => $this->apiKey];
        if (!empty($data)) {
            $http->setRawData(json_encode($data), self::ENC_TYPE);
        }
        $response = $this->request($http);
        $processResponse = $this->processResponse($response);
        if (!$processResponse) {
            $this->throwBadGateway();
        }
        return $processResponse;
    }
    
    /**
     * wraps the http request call to catch connection exceptions
     * @param Zend_Http_Client $http
     * @return Zend_Http_Response
     */
    protected function request(Zend_Http_Client $http) {
        //exceptions with one of that messages are leading to badgateway exceptions
        $badGatewayMessages = [
            'php_network_getaddresses: getaddrinfo failed: Name or service not known',
            'unable to connect to tcp',
        ];
        
        try {
            return $http->request();
        }
        catch (Zend_Exception $e) {
            foreach ($badGatewayMessages as $msg) {
                if (stripos($e->getMessage(), $msg) === false){
                    //check next message
                    continue;
                }
                $this->throwBadGateway();
            }
            throw $e;
        }
    }
    
    /**
     * Throws a ZfExtended_BadGateway exception containing the underlying errors
     * @throws ZfExtended_BadGateway
     */
    protected function throwBadGateway() {
        $e = new ZfExtended_BadGateway('PangeaMt-Api: The request returned an error.');
        $e->setDomain('PangeaMt-Api');
        $errors = $this->getErrors();
        $errors[] = $this->result; //add real result to error data
        $e->setErrors($errors);
        throw $e;
    }
    
    /**
     * parses and processes the response of PangeaMt-Api, and handles the errors
     * @param Zend_Http_Response $response
     * @return boolean
     */
    protected function processResponse(Zend_Http_Response $response) {
        $this->error = [];
        $this->response = $response;
        $validStates = [200]; // only GET-requests in use
        
        $url = $this->http->getUri(true);
        
        //check for HTTP State (REST errors)
        if (!in_array($response->getStatus(), $validStates)) {
            $error = new stdClass();
            $error->type = 'HTTP';
            $error->error = $response->getStatus();
            $error->url = $url;
            $error->method = $this->httpMethod;
            $this->error[] = $error;
        }
        
        $responseBody = trim($response->getBody());
        $result = (empty($responseBody)) ? '' : json_decode($responseBody);
        
        //check for JSON errors
        if (json_last_error() > 0) {
            $error = new stdClass();
            $error->type = 'JSON';
            $error->error = json_last_error_msg();
            $error->url = $url;
            $error->method = $this->httpMethod;
            $this->error[] = $error;
            return false;
        }
        
        $this->result = $result;
        
        //check for error messages from body
        if (!empty($result->ReturnValue) && $result->ReturnValue > 0) {
            $error = new stdClass();
            $error->type = 'Error: '.$result->ReturnValue;
            $error->error = $result->message;
            $error->url = $url;
            $error->method = $this->httpMethod;
            $this->error[] = $error;
        }
        
        return empty($this->error);
    }
    
    /**
     * returns the found errors
     */
    public function getErrors() {
        return $this->error;
    }
    
    /**
     * returns the decoded JSON result
     */
    public function getResult() {
        return $this->result;
    }
}