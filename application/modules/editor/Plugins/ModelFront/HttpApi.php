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

class editor_Plugins_ModelFront_HttpApi {
    
    const PREDICT_RISK_ROUTE='predict';
    
    /**
     * Api-URL from zf configuration
     * @var string
     */
    protected string $apiUrl;
    
    /**
     * Api token from zf configuration
     * @var string
     */
    protected $apiToken;
    
    /**
     * @var Zend_Http_Response
     */
    protected Zend_Http_Response $response;
    
    protected Zend_Http_Client $http;

    /***
     * rfc language value
     * @var string
     */
    protected string $sourceLangRfc;
    
    /***
     * rfc language value
     * @var string
     */
    protected string $targetLangRfc;
    
    protected $result;

    /***
     * @var Zend_Config
     */
    protected Zend_Config $config;

    /**
     * @throws editor_Plugins_ModelFront_Exception
     * @throws Zend_Exception
     */
    public function __construct() {
        $this->config = Zend_Registry::get('config');
        $this->apiUrl = $this->config->runtimeOptions->plugins->ModelFront->apiUrl ?? null;
        $this->apiToken = $this->config->runtimeOptions->plugins->ModelFront->apiToken ?? null;
        if(empty($this->apiUrl) || empty($this->apiToken)){
            throw new editor_Plugins_ModelFront_Exception('E1266');
        }
        //add backslash if missing 
        $this->apiUrl=rtrim($this->apiUrl, '/') . '/';
    }
    
    
    /***
     * Send predict api call. The $data should be in ModelFront required layout:
     * 'original'=> source text
     * 'translation'=> translated source
     * @param array $data
     * @throws Exception
     * @return boolean
     */
    public function predictRisk(array $data): bool{
        if(empty($this->sourceLangRfc) || empty($this->targetLangRfc)){ 
            throw new editor_Plugins_ModelFront_Exception('E1267');
        }
        $http=$this->getHttp('POST');
        $http->setUri($this->apiUrl.self::PREDICT_RISK_ROUTE);
        $rows=[];
        $rows['rows'][]=$data;
        $http->setRawData(json_encode($rows), 'application/json; charset=utf-8');
        return $this->processResponse($this->request($http));
    }
    
    /**
     * wraps the http request call to catch connection exceptions
     * @param Zend_Http_Client $http
     * @return Zend_Http_Response
     */
    protected function request(Zend_Http_Client $http): Zend_Http_Response{
        
        try {
            return $http->request();
        }
        catch (Zend_Exception $e) {
            throw new editor_Plugins_ModelFront_Exception('E1268',['message'=>$e->getMessage()]);
        }
    }
    
    /**
     * @param Zend_Http_Response $response
     * @return boolean
     */
    protected function processResponse(Zend_Http_Response $response): bool{
        $this->error = [];
        $this->result=[];
        $this->response = $response;
        $validStates = [200, 201];
        
        $url = $this->http->getUri(true);
        
        //check for HTTP State (REST errors)
        if(!in_array($response->getStatus(), $validStates)) {
            $error = new stdClass();
            $error->type = 'HTTP';
            $error->error = $response->getStatus();
            $error->url = $url;
            $error->body=$response->getBody();
            $this->error[] = $error;
        }
        $result = json_decode(trim($response->getBody()),true);
        
        //check for JSON errors
        if(json_last_error() > 0){
            $error = new stdClass();
            $error->type = 'JSON';
            $error->url = $url;
            $error->method = $this->httpMethod;
            $this->error[] = $error;
            return false;
        }
        
        $this->result = $result;
        
        return empty($this->error);
    }
    
    
    /**
     * @param string $method
     * @return Zend_Http_Client
     */
    protected function getHttp($method): Zend_Http_Client{
        $this->http = ZfExtended_Factory::get('Zend_Http_Client');
        /* @var $http Zend_Http_Client */
        $this->http->setMethod($method);
        $this->http->setParameterGet('sl', $this->sourceLangRfc);
        $this->http->setParameterGet('tl',$this->targetLangRfc);
        $this->http->setParameterGet('token', $this->apiToken);
        $this->http->setHeaders('Accept-charset', 'UTF-8');
        $this->http->setHeaders('Accept', 'application/json');
        return $this->http;
    }
    
    public function setSourceLangRfc(string $sourceLangRfc){
        $this->sourceLangRfc=$sourceLangRfc;
    }
    
    public function setTargetLangRfc(string $targetLangRfc){
        $this->targetLangRfc=$targetLangRfc;
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
        return $this->result['rows'] ?? [];
    }
    
}