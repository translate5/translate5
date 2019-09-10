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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * NEC-TM HTTP Connection API
 */
class editor_Plugins_NecTm_HttpApi {
    
    const BASE_PATH = '/api/v1';
    
    /**
     * for JWT-Authorization: MemCache-Id for Access-Token
     * @var string
     */
    const TOKEN_CACHE_KEY = 'NecTmAccessToken';
    
    /**
     * Api-URL from zf configuration
     * @var string
     */
    protected $apiUrl;
    
    /**
     * Username at NEC-TM
     * @var string
     */
    protected $username;
    
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
    
    /**
     * @var ZfExtended_Cache_MySQLMemoryBackend
     */
    protected $memCache;
    
    public function __construct() {
        $this->memCache = Zend_Cache::factory('Core', new ZfExtended_Cache_MySQLMemoryBackend());
    }
    
    /**
     * Set the token to authorize access to NEC-TM's APIs for the configured-user.
     * @return string|false
     */
    protected function setAccessToken() {
        $token = $this->login();
        if (!$token) {
            return false; // no token available
        }
        $this->memCache->save($token, self::TOKEN_CACHE_KEY);
        $test = $this->memCache->load(self::TOKEN_CACHE_KEY);
        return $token;
    }
    
    /**
     * Returns the access-token that was delivered by the API for further authentication.
     * @return string|false
     */
    protected function getAccessToken() {
        $cachedToken = $this->memCache->load(self::TOKEN_CACHE_KEY);
        if(!$cachedToken || empty($cachedToken)) {
            $cachedToken = $this->setAccessToken();
        }
        return $cachedToken;
    }
    
    /**
     * login and return the accessToken
     * @throws Exception
     * @return string accessToken|false
     */
    protected function login() {
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */
        $credentials = $config->runtimeOptions->plugins->NecTm->credentials->toArray();
        $auth = explode(':', $credentials[0]);
        $this->username = $auth[0];
        
        $data = new stdClass();
        $data->username = $this->username;
        $data->password = $auth[1];
        
        $http = $this->getHttp('POST','auth');
        $http->setHeaders('Content-Type', 'application/json');
        $http->setRawData(json_encode($data), 'application/json; charset=utf-8');
        
        if ($this->processResponse($this->request($http))) {
            return $this->result->access_token;
        }
        return false;
    }
    
    /**
     * Returns all available NEC-TM-Tags (for us then: "categories") for the current user for the current NecTm-Service.
     * @return array
     */
    public function getTags() {
        /*
            "tags": [
                {
                    "id": "tag_Location",
                    "type": "public",
                    "name": "Madrid"
                },
                {
                    "id": "tag391",
                    "type": "unspecified",
                    "name": "Health"
                },
                {
                    "id": "Tag by XYZ",
                    "type": "private",
                    "name": "XYZ"
                }
            ]
        */
        $method = 'GET';
        $endpointPath = 'tags';
        $data = [];
        $params = [];
        try {
            $this->necTmRequest($method, $endpointPath, $data, $params);
        }
        catch(editor_Plugins_NecTm_ExceptionToken $e) {
            return [];
        }
        return $this->result->tags;
    }
    
    /**
     * Search the api for given source/target language.
     * @param string $queryString
     * @param string $sourceLang
     * @param string $targetLang
     * @return boolean
     */
    public function search($queryString, $sourceLang, $targetLang) {
        $method = 'GET';
        $endpointPath = 'tm';
        $data = [];
        $params= array('q'           => $queryString,
                       'slang'       => 'de',       // TODO
                       'tlang'       => 'en',       // TODO
                       'aut_trans'   => false,      // TODO
                       'concordance' => false,      // TODO
                       'strip_tags'  => false,      // TODO
                       'tag'         => '');        // TODO
        $this->necTmRequest($method, $endpointPath, $data, $params);
        $results = $this->result->results; // TODO
        $this->result = $results[0]->tu->target_text;
        return true;
    }
    
    
    // -------------------------------------------------------------------------
    // General handling of the API-requests
    // -------------------------------------------------------------------------
    
    /**
     * "Lazy load" and return of the configured API's URL.
     * @param string $endpointPath
     * @return string
     */
    protected function getApiUrl(): string {
        if (!$this->apiUrl) {
            $config = Zend_Registry::get('config');
            /* @var $config Zend_Config */
            $urls = $config->runtimeOptions->plugins->NecTm->server->toArray();
            if(empty($urls) || empty($urls[0])){
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
        $this->http->setHeaders('Accept', 'application/json; charset=utf-8');
        return $this->http;
    }
    
    /**
     * Sends a request to NEC_TM's Api Service.
     * @param string $method
     * @param string $endpointPath
     * @param array $data
     * @param array $params
     */
    protected function necTmRequest($method, $endpointPath = '', $data = [], $params = []) {
        $http = $this->getHttp($method, $endpointPath);
        $http->setHeaders('Authorization', 'JWT ' . $this->getAccessToken());
        if (!empty($data)) {
            $http->setRawData(json_encode($data), 'application/json; charset=utf-8');
        }
        if (!empty($params)) {
            $setParameter = ($method == 'GET') ? 'setParameterGet' : 'setParameterPost';
            $http->$setParameter($params);
        }
        $res = $this->request($http);
        if(!$this->processResponse($res)) {
            $this->throwBadGateway($http);
        }
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
                $t1 = $e->getMessage();
                $test = stripos($e->getMessage(), $msg);
                if(stripos($e->getMessage(), $msg) === false){
                    //check next message
                    continue;
                }
                $this->throwBadGateway($http);
            }
            throw $e;
        }
    }
    
    /**
     * Throws a ZfExtended_BadGateway exception containing the underlying errors
     * @throws ZfExtended_BadGateway
     */
    protected function throwBadGateway($http = '') {
        //FIXME wenn in getErrors ein 403, dann kein Zugriff auf das TM - entweder weil nicht existent (wissen wir aber nicht) oder wegen fehlender credentials??? → den letzten Fall muss ich eh noch evaluieren
        // entpsrechend hier ein aussagekräfigerer Text ausgeben
        // Bei 401 Ebenfalls keine Zugriff Meldung
        $e = new ZfExtended_BadGateway('NEC-TM-Api: The request returned an error.');
        $e->setDomain('NEC-TM-Api');
        $errors = $this->getErrors();
        $errors[] = $this->result; //add real result to error data
        if (!empty($http)) {
            $errors[] = $http;
        }
        $e->setErrors($errors);
        throw $e;
    }
    
    /**
     * parses and processes the response of NEC-TM-Api, and handles the errors
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
            $this->error[] = $error;
        }
        
        $responseBody = trim($response->getBody());
        $result = (empty($responseBody)) ? '' : json_decode($responseBody);
        
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
     * returns the found errors
     */
    public function getErrors() {
        return $this->error;
    }
    /**
     * returns the decoded JSON result
     */
    public function getResult() {
        $test = $this->result;
        return $this->result;
    }
    
}