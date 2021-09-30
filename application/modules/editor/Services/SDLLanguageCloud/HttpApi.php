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

class editor_Services_SDLLanguageCloud_HttpApi {
    /**
     * @var Zend_Http_Response
     */
    protected $response;
    
    /**
     * @var stdClass
     */
    protected $result;
    
    protected $error = array();
    
    /**
     * For logging purposes
     * @var Zend_Http_Client
     */
    protected $http;
    
    /**
     * For logging purposes
     * @var string
     */
    protected $httpMethod;
    
    
    /***
     * Api key used for authentcication
     * @var string
     */
    protected $apiKey;
    
    /***
     * 
     * @var string
     */
    protected $apiUrl;
    
    public function __construct() {
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */
        $urls = $config->runtimeOptions->LanguageResources->sdllanguagecloud->server;
        $urls=$urls->toArray();
        if(empty($urls) || empty($urls[0])){
            $exc= new Zend_Exception("Api url is not defined in the zf configuration");
            $this->badGateway($exc, "");
        }
        
        $this->apiUrl=$urls[0];
        $apiKey = $config->runtimeOptions->LanguageResources->sdllanguagecloud->apiKey;
        
        if(empty($apiKey)){
            $exc= new Zend_Exception("Api key is not defined in the zf configuration");
            $this->badGateway($exc, $this->apiUrl);
        }
        $this->apiKey=$apiKey;
    }
    
    
    /**
     * prepares a Zend_Http_Client, prefilled with the configured URL + the given REST URL Parts (ID + verbs)
     * @param string $httpMethod
     * @param string $urlSuffix
     * @return Zend_Http_Client
     */
    protected function getHttp($method, $urlSuffix = '') {
        
        $urlSuffix = ltrim($urlSuffix, '/');
        $this->http = ZfExtended_Factory::get('Zend_Http_Client');
        /* @var $http Zend_Http_Client */
        $this->http->setUri($this->apiUrl.$urlSuffix);
        $this->http->setMethod($method);
        $this->httpMethod = $method;
        $this->http->setHeaders('Accept-charset', 'UTF-8');
        
        //$this->http->setHeaders('Accept', 'application/json; charset=utf-8');
        $this->http->setHeaders('Authorization','LC apiKey='.$this->apiKey);
        return $this->http;
    }
    
    /**
     * Search the api for given source/target language by domainCode
     * @param array $params
     * @return boolean
     */
    public function search($params) {
        $data = new stdClass();
        
        if(!empty($params['domainCode'])){
            $data->domainCode = $params['domainCode'];
        }
        $data->text = $params['text'];
        
        if(!empty($params['from'])){
            $data->from = $params['from'];
        }
        
        if(!empty($params['to'])){
            $data->to = $params['to'];
        }
        
        $http = $this->getHttp('POST', 'translate');
        $http->setRawData(json_encode($data), 'application/json; charset=utf-8');
        return $this->processResponse($this->request($http));
    }
    
    /***
     * Upload a file for translation
     * 
     * @param array $params
     * @return boolean
     */
    public function uploadFile($params){
        $http = $this->getHttp('POST', 'file-translations');

        if(!empty($params['domainCode']) && isset($params['domainCode'])){
            $http->setParameterPost('domainCode',$params['domainCode']);
        }
        if(!empty($params['from']) && isset($params['from'])){
            $http->setParameterPost('from',$params['from']);
        }
        
        if(!empty($params['to']) && isset($params['to'])){
            $http->setParameterPost('to',$params['to']);
        }
        
        //read the uploaded file content
        $fileContent=file_get_contents($params['file']['tmp_name']);
        $fileName=$params['file']['name'].'.'.$params['fileExtension'];
        
        //set the file upload
        $http->setFileUpload($fileName, 'file', $fileContent, $params['file']['type']);
        return $this->processResponse($this->request($http));
    }
    
    /***
     * Get file staus by file translation id (recieved from sdl language cloud)
     * @param string $translationId
     * @return boolean
     */
    public function getFileStatus($translationId){
        $http = $this->getHttp('GET', '/file-translations/'.$translationId);
        return $this->processResponse($this->request($http));
    }
    
    /***
     * Download the translated file form the sdl language cloud
     * @param string $url
     * @param string $fileName
     * @return string
     */
    public function downloadFile($url,$fileName){
        $client=new Zend_Http_Client();
        
        $client->setUri($url);
        $client->setHeaders('Authorization','LC apiKey='.$this->apiKey);
        
        $client->setStream(); // will use temp file
        $response = $client->request('GET');
        
        $file=APPLICATION_PATH.'/../data/'.$fileName;
        
        // copy file
        copy($response->getStreamName(), $file);
        // use stream
        $fp = fopen($file, "w");
        stream_copy_to_stream($response->getStream(), $fp);
        // Also can write to known file
        $client->setStream($file)->request('GET');
        
        return $file;
    }
    
    /***
     * Return all available engines in sdllanguagecloud 
     * @param array $domainCode: engine group, can be 'baseline', 'vertical', 'custom'
     * @return boolean
     */
    public function getEngines($domainCode=array()){
        $data = new stdClass();
        if(!empty($domainCode)){
            $data->domainCode =implode("&engineType=", $domainCode);
        }
        $http = $this->getHttp('GET', 'translation-engines');
        $http->setRawData(json_encode($data), 'application/json; charset=utf-8');
        return $this->processResponse($this->request($http));
    }
    
    /***
     * Check the api status
     * @return boolean
     */
    public function getStatus(){
        $http = $this->getHttp('GET', 'languages'); // in order to check if the API is available and running, we request its languages.
        return $this->processResponse($this->request($http));
    }

    
    /**
     * returns the found errors
     */
    public function getErrors() {
        return $this->error;
    }
    
    /**
     * returns the raw response
     */
    public function getResponse() {
        return $this->response;
    }
    
    /**
     * returns the decoded JSON result
     */
    public function getResult() {
        return $this->result;
    }
    
    /**
     * Creates a stdClass Object which is later converted to JSON for communication
     * @param string $method a method is always needed in the request JSON
     * @param string $memory optional, if given this is added as memory to the JSON
     * @return stdClass;
     */
    protected function json(string $method) {
        $result = new stdClass();
        $result->Method = $method;
        return $result;
    }
    
    /**
     * wraps the http request call to catch connection exceptions
     * @param Zend_Http_Client $http
     * @return Zend_Http_Response
     */
    protected function request(Zend_Http_Client $http) {
        //exceptions with one of that messages are leading to badgateway exceptions
        $badGatewayMessages = [
            'stream_socket_client(): php_network_getaddresses: getaddrinfo failed: Name or service not known',
            'stream_socket_client(): unable to connect to tcp',
        ];
        
        try {
            return $http->request();
        }
        catch (Zend_Exception $e) {
            foreach ($badGatewayMessages as $msg) {
                if(strpos($e->getMessage(), $msg) === false){
                    //check next message
                    continue;
                }
                $this->badGateway($e, $http);
            }
            throw $e;
        }
    }
    
    protected function badGateway(Zend_Exception $e, $url) {
        $badGateway = new ZfExtended_BadGateway('Die angefragte SdlLanguageCloud Instanz ist nicht erreichbar', 0, $e);
        $badGateway->setDomain('LanguageResources');
        
        $error = new stdClass();
        $error->type = 'HTTP';
        $error->error = $e->getMessage();
        $error->url = $url;
        $error->method = $this->httpMethod;
        
        $badGateway->setErrors([$error]);
        throw $badGateway;
    }
    
    /**
     * parses and processes the response of OpenTM2, and handles the errors
     * @param Zend_Http_Response $response
     * @return boolean
     */
    protected function processResponse(Zend_Http_Response $response) {
        $this->error = [];
        $this->response = $response;
        $validStates = [200, 201];
        
        $url = $this->http->getUri(true);
        
        //check for HTTP State (REST errors)
        if(!in_array($response->getStatus(), $validStates)) {
            $error = new stdClass();
            $error->type = 'HTTP';
            $error->error = $response->getStatus();
            $error->url = $url;
            $error->method = $this->httpMethod;
            $error->body=$response->getBody();
            $this->error[] = $error;
        }
        $result = json_decode(trim($response->getBody()));
        
        //check for JSON errors
        if(json_last_error() > 0){
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
        if(!empty($result->ReturnValue) && $result->ReturnValue > 0) {
            $error = new stdClass();
            $error->type = 'Error Nr. '.$result->ReturnValue;
            $error->error = $result->ErrorMsg;
            $error->url = $url;
            $error->method = $this->httpMethod;
            $this->error[] = $error;
        }
        
        return empty($this->error);
    }
    
    /**
     * returns the current time stamp in the expected format for OpenTM2
     */
    protected function nowDate() {
        return gmdate('Ymd\THis\Z');
    }
}