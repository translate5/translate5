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
 * Reusable HTTP Connection API code for HTTP with JSON based APIs
 */
abstract class editor_Services_Connector_HttpApiAbstract {

    const ENC_TYPE = 'application/json; charset=utf-8';

    /**
     * @var editor_Models_LanguageResources_Resource
     */
    protected $resource;
    
    /**
     * @var stdClass
     */
    protected $result;
    
    protected $error = null;
    
    /**
     * @var Zend_Http_Client
     */
    protected $http;
    
    /**
     * @var string
     */
    protected $httpMethod;
    
    /**
     * @var Zend_Http_Response
     */
    protected $response;
    
    /**
     * returns the found errors
     */
    public function getError() {
        return $this->error;
    }
    
    /**
     * returns the decoded JSON result
     */
    public function getResult() {
        return $this->result;
    }
    
    /**
     * Sets internally the resource, if working without a concrete language resource (normally only for ping call)
     * @param editor_Models_LanguageResources_Resource $resource
     */
    public function setResource(editor_Models_LanguageResources_Resource $resource) {
        $this->resource = $resource;
    }
    
    /**
     * returns the raw response
     * @return Zend_Http_Response
     */
    public function getResponse() {
        return $this->response;
    }
    
    /**
     * prepares a Zend_Http_Client, prefilled with the configured URL + the given REST URL Parts (ID + verbs)
     * @param string $httpMethod
     * @param string $urlSuffix
     * @return Zend_Http_Client
     */
    protected function getHttp($method, $apiEndpointPath = '') {
        $url = rtrim($this->resource->getUrl(), '/');
        $apiEndpointPath = ltrim($apiEndpointPath, '/');
        $this->http = ZfExtended_Factory::get('Zend_Http_Client');
        /* @var $http Zend_Http_Client */
        $this->http->setUri($url.'/'.$apiEndpointPath);
        $this->http->setMethod($method);
        $this->httpMethod = $method;
        $this->http->setHeaders('Accept-charset', 'UTF-8');
        $this->http->setHeaders('Accept', self::ENC_TYPE);
        return $this->http;
    }
    
    /**
     * parses and processes the response of OpenTM2, and handles the errors
     * @param Zend_Http_Response $response
     * @return boolean
     */
    protected function processResponse(Zend_Http_Response $response): bool {
        //example how to fake a response
        //$response = new Zend_Http_Response(500, [], '{"ReturnValue":0,"ErrorMsg":"Error: too many open translation memory databases"}');
        $this->error = null;
        $this->response = $response;
        $validStates = [200, 201, 204];
        
        $url = $this->http->getUri(true);
        
        
        //check for HTTP State (REST errors)
        if(!in_array($response->getStatus(), $validStates)) {
            $this->error = new stdClass();
            $this->error->method = $this->httpMethod;
            $this->error->url = $url;
            $this->error->type = 'HTTP '.$response->getStatus();
            $this->error->body = $response->getBody();
            $this->error->error = $response->getStatus(); //is normally overwritten later
        }
        
        $responseBody = trim($response->getBody());

        // for tsv response, return teh content directly, no need for json decode
        if(is_null($this->error) && !empty($responseBody) && $this->isTsvResponse($response)){
            $this->result = $responseBody;
            return empty($this->error);
        }
        
        if(empty($responseBody)) {
            $this->result = '';
        }
        else {
            $errorExtra = [
                'method' => $this->httpMethod,
                'url' => $url,
            ];
            $this->result = json_decode($responseBody);
            
            $lastJsonError = json_last_error();
            
            //if the json string contains unescapd ctrl characters, we escape them and try again the decode:
            if($lastJsonError == JSON_ERROR_CTRL_CHAR) {
                //set the previous responseBody in case of an error
                $errorExtra['rawanswerBeforeCtrlCharFix'] = $responseBody;
                
                //escape control characters with \u notation
                $responseBody = preg_replace_callback('/[[:cntrl:]]/', function($x){
                    return substr(json_encode($x[0]), 1, -1);
                }, $responseBody);
                $this->result = json_decode($responseBody);
                
                //get json error to proceed as usual
                $lastJsonError = json_last_error();
            }
            
            //check for JSON errors
            if($lastJsonError != JSON_ERROR_NONE){
                $errorExtra['errorMsg'] = json_last_error_msg();
                $errorExtra['rawanswer'] = $responseBody;
                throw new editor_Services_Exceptions_InvalidResponse('E1315', $errorExtra);
            }
        }
        
        return empty($this->error);
    }

    /***
     * Check if the given response content type is tsv (tab separated value)
     * @param Zend_Http_Response $response
     * @return bool|void
     */
    protected function isTsvResponse(Zend_Http_Response $response){
        return str_contains($response->getHeader('Content-type'),'tab-separated-values');
    }
}