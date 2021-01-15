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
    
    const ENC_TYPE = 'application/json; charset=utf-8';
    
    /**
     * The status the NEC-TM-Api returns if a job has been finished sucessfully.
     * @var string
     */
    const JOB_STATUS_SUCCEEDED = "succeded";
    
    /**
     * How long to wait when checking if a job was succesful, e.g when exporting and downloading a tmx from the NEC-TM-Api.
     * @var integer
     */
    const JOB_STATUS_TIMETOWAIT = 20; // = seconds
    
    /**
     * for JWT-Authorization: MemCache-Id for access-token
     * @var string
     */
    const CACHE_TOKEN_KEY = 'NecTmAccessToken';
    
    /**
     * We also cache who uses our access-token.
     * @var string
     */
    const CACHE_USERNAME_KEY = 'NecTmUser';
    
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
    protected function loginAndSetAccessToken() {
        $token = $this->login();
        if (!$token) {
            return false; // no token available
        }
        $this->memCache->save($token, self::CACHE_TOKEN_KEY);
        return $token;
    }
    
    /**
     * Returns the access-token that was delivered by the API for further authentication.
     * @return string|false
     */
    protected function getAccessToken() {
        $cachedToken = $this->memCache->load(self::CACHE_TOKEN_KEY);
        if (!$cachedToken || empty($cachedToken)) {
            $cachedToken = $this->loginAndSetAccessToken();
        }
        return $cachedToken;
    }
    
    /**
     * Returns the username our access-token belongs to.
     * @return string|false
     */
    protected function getUsername() {
        $cachedUsername = $this->memCache->load(self::CACHE_USERNAME_KEY);
        if (!$cachedUsername || empty($cachedUsername)) {
            $this->loginAndSetAccessToken();
        }
        return $this->memCache->load(self::CACHE_USERNAME_KEY);
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
        $this->memCache->save($auth[0], self::CACHE_USERNAME_KEY);
        
        $data = new stdClass();
        $data->username = $this->getUsername();
        $data->password = $auth[1];
        
        $http = $this->getHttp('POST','auth');
        $http->setHeaders('Content-Type', 'application/json');
        $http->setRawData(json_encode($data), self::ENC_TYPE);
        
        if ($this->processResponse($this->request($http))) {
            return $this->result->access_token;
        }
        return false;
    }
    
    /**
     * Returns all available NEC-TM-Tags (for us then: "categories") for the current user for the current NecTm-Service.
     * @return boolean
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
        $queryParams = [];
        $processResponse = $this->necTmRequest($method, $endpointPath, $data, $queryParams);
        $result = $this->result;
        $this->result = $result->tags;
        return $processResponse;
    }
    
    /**
     * Search the api for given source/target language.
     * @param string $queryString
     * @param string $sourceLang
     * @param string $targetLang
     * @param array $tagIds (= assigned categories AND top-level-categories)
     * @return boolean
     */
    public function search($queryString, $sourceLang, $targetLang, $tagIds) {
        $method = 'GET';
        $endpointPath = 'tm';
        $data = [];
        $queryParams = array('q'           => $queryString,
                             'slang'       => $sourceLang,
                             'tlang'       => $targetLang,
                             // 'min_match' => '75'      // "Return only match above or equal to given threshold (0-100)"; default: ​ 75
                             'aut_trans'   => 'false',   // "Apply machine translation if match score is less than a threshold"; default: true
                             // 'concordance' => false,  // "Concordance search mode"; default: false
                             // 'strip_tags'  => false,  // "Strip all XML tags from the query"; default: false
                             'tag'         => $tagIds);
        $processResponse = $this->necTmRequest($method, $endpointPath, $data, $queryParams);
        $this->result = $this->result->results ?? [];
        return $processResponse;
    }
    
    
    /**
     */
    public function searchBatch(array $queryStrings, $sourceLang, $targetLang, $tagIds, $limit) {
        $method = 'POST';
        $endpointPath = 'tm/query_batch';
        $data = [];
        $queryParams = [
            'q'=> $queryStrings,
            'limi'=> $limit,//Limit output to this number of segments. Default value: 10
            'slang'=> $sourceLang,
            'tlang'=> $targetLang,
            'aut_trans'=> 'false',
            'tag'=> $tagIds,
            
        ];
        $processResponse = $this->necTmBatchRequest($method, $endpointPath, $data, $queryParams);
        $this->result = $this->result ?? [];
        return $processResponse;
    }
    
    
    /**
     * Concordance-search; NEC-TM-api requires source- and target language.
     * @param string $searchString
     * @param string $field
     * @param string $sourceLang
     * @param string $targetLang
     * @param array $tagIds (= assigned categories AND top-level-categories)
     * @return boolean
     */
    public function concordanceSearch($searchString, $field, $sourceLang, $targetLang, $tagIds) {
        $method = 'GET';
        $endpointPath = 'tm';
        $data = [];
        $queryParams = array('q'           => $searchString,
                             'slang'       => $sourceLang,
                             'tlang'       => $targetLang,
                             // 'min_match' => '75'      // "Return only match above or equal to given threshold (0-100)"; default: ​ 75
                             'aut_trans'   => 'false',   // "Apply machine translation if match score is less than a threshold"; default: true
                             'concordance' => 'true',    // "Concordance search mode"; default: false
                             // 'strip_tags'  => false,  // "Strip all XML tags from the query"; default: false
                             'tag'         => $tagIds);
        $processResponse = $this->necTmRequest($method, $endpointPath, $data, $queryParams);
        // NEC-TM doesn't offer to search by field; we must filter the results manually:
        $allResults = $this->result->results;
        $results = [];
        foreach ($allResults as $result) {
            switch ($field) {
                case 'source':
                    $resultIsOk = (stripos($result->tu->source_text, $searchString) !== false); // TODO: strpos or stripos?
                    break;
                case 'target':
                    $resultIsOk = (stripos($result->tu->target_text, $searchString) !== false); // TODO: strpos or stripos?
                    break;
            }
            if ($resultIsOk) {
                $results[] = array('source' => $result->tu->source_text,
                                   'target' => $result->tu->target_text);
            }
        }
        $this->result = $results;
        return $processResponse;
    }
    
    /**
     * Import translation memory segments from TMX file.
     * @param string $file
     * @param string $sourceLang
     * @param string $targetLang
     * @param array $tagIds (= assigned categories AND top-level-categories)
     */
    public function importTMXfile($file, $sourceLang, $targetLang, $tagIds) {
        $method = 'PUT';
        $endpointPath = 'tm/import';
        $data = [];
        $queryParams = array('langpair' => $sourceLang.'_'.$targetLang, // "2-letter language codes join by underscore."
                             'tag'      => $tagIds);
        $files = array('file' => $file);
        $processResponse = $this->necTmRequest($method, $endpointPath, $data, $queryParams, $files);
        return $processResponse;
    }
    
    /**
     * Add new translation memory unit.
     * If source and target are the same, NEC-TM updates the data (tags, dates, ...). Otherwise
     * a new unit will be created.
     * @param string $sourceText
     * @param string $targetText
     * @param string $sourceLang
     * @param string $targetLang
     * @param string|null $filename (= if file was imported for LanguageResource on creation)
     * @param array $tagIds (= assigned categories AND top-level-categories)
     * @return boolean
     */
    public function addTMUnit($sourceText, $targetText, $sourceLang, $targetLang, $tagIds, $filename) {
        $method = 'POST';
        $endpointPath = 'tm';
        $data = [];
        $queryParams = array('stext'     => $sourceText,
                             'ttext'     => $targetText,
                             'slang'     => $sourceLang,
                             'tlang'     => $targetLang,
                             'tag'       => $tagIds,
                             'file_name' => $filename);
        $processResponse = $this->necTmRequest($method, $endpointPath, $data, $queryParams);
        return $processResponse;
    }
    
    /**
     * retrieves the TM as TM file
     * @param string|array $mime
     * @param string $sourceLang
     * @param string $targetLang
     * @param array $tagIds (= assigned categories AND top-level-categories)
     * @return boolean
     */
    public function get($mime, $sourceLang, $targetLang, $tagIds) {
        if (is_array($mime)) {
            $mime = implode(',', $mime);
        }
        
        // Step 1: Export translation memory segments to zipped TMX file(s)
        $method = 'POST';
        $endpointPath = 'tm/export';
        $data = [];
        $queryParams = array('slang' => $sourceLang,
                             'tlang' => $targetLang,
                             'tag'   => $tagIds);
        $this->necTmRequest($method, $endpointPath, $data, $queryParams);
        $jobId = $this->result->job_id; // = ID of export task invoked in the background.
        
        // Step 2: wait for the job to be finished
        // TODO: create worker
        $jobIsSucceeded = $this->isSuccessfulJob($jobId);
        if (!$jobIsSucceeded) {
            return false;
        }
        
        // Step 3: Download exported file
        $method = 'GET';
        $endpointPath = 'tm/export/file/'.$jobId;
        $http = $this->getHttp($method, $endpointPath);
        $http->setHeaders('Authorization', 'JWT ' . $this->getAccessToken());
        $http->setConfig(['timeout' => 1200]);
        $http->setHeaders('Accept', $mime);
        $response = $this->request($http);
        if ($response->getStatus() === 200) {
            $this->result = $response->getBody();
            return true;
        }
        return $this->processResponse($response);
    }
    
    /**
     * A job is successful if the NEC-TM-Api returns the corresponding status within
     * a given time. (for the NEC-TM-Api, every import or export is a "Job".)
     * @param string $jobId
     * @return boolean
     */
    protected function isSuccessfulJob($jobId) {
        $jobIsSucceeded = '';
        $starttime = time();
        while ($jobIsSucceeded != self::JOB_STATUS_SUCCEEDED) {
            $timeElapsed = time() - $starttime;
            if ($timeElapsed > self::JOB_STATUS_TIMETOWAIT) {
                break;
            }
            $jobIsSucceeded = $this->getJobStatus($jobId);
        }
        return $jobIsSucceeded == self::JOB_STATUS_SUCCEEDED;
    }
    
    /**
     * Returns the status for the given job-Id.
     * @param string $jobId
     * @return string status
     */
    protected function getJobStatus($jobId){
        $method = 'GET';
        $endpointPath = 'jobs/'.$jobId;
        $data = [];
        $queryParams = [];
        $processResponse = $this->necTmRequest($method, $endpointPath, $data, $queryParams);
        return $this->result->jobs[0]->status;
    }
    
    /**
     * Check the api status.
     * @return boolean
     */
    public function getStatus(){
        return $this->getUserinfos(); // test status by checking our user
        // TODO: NEC-TM-Api also returns 404 if a user doesn't exist. The Api itself might be available!
    }
    
    /**
     * Get user details.
     * NEC-TM-Api returns 404 if user doesn't exist.
     * @return boolean
     */
    protected function getUserinfos(){
        $method = 'GET';
        $endpointPath = 'users/'.$this->getUsername();
        $data = [];
        $queryParams = [];
        $processResponse = $this->necTmRequest($method, $endpointPath, $data, $queryParams);
        return $processResponse;
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
        $this->http->setUri($this->getUrl($endpointPath));
        $this->http->setMethod($method);
        $this->httpMethod = $method;
        $this->http->setHeaders('Accept-charset', 'UTF-8');
        $this->http->setHeaders('Accept', self::ENC_TYPE);
        $this->http->setConfig(['removeArrayIndexInUrlEncode' => true]);
        return $this->http;
    }
    
    /**
     * Sends a request to NEC_TM's Api Service.
     * @param string $method
     * @param string $endpointPath
     * @param array $data
     * @param array $queryParams
     * @param array $files (files[formname] = filename)
     * @return boolean
     */
    protected function necTmRequest($method, $endpointPath = '', $data = [], $queryParams = [], $files = []) {
        // Zend's setParameterGet() would add array-keys to the parameter-name if the value is an array:
        //     ?tag[0]=abc&tag[1]=def
        // But what we need is:
        //     ?tag=abc&tag=def
        $query = '';
        $paramsForQuery = [];
        if (!empty($queryParams)) {
            foreach ($queryParams as $name => $value) {
                if (is_null($value)) {
                    continue;
                }
                if(is_array($value)) {
                    foreach ($value as $valueFromArray) {
                        $paramsForQuery[] = $name.'='.urlencode($valueFromArray);
                    }
                } else {
                    $paramsForQuery[] = $name.'='.urlencode($value);
                }
            }
            $query = '?' . implode("&",$paramsForQuery);
        }
        $http = $this->getHttp($method, $endpointPath.$query);
        $http->setHeaders('Authorization', 'JWT ' . $this->getAccessToken());
        if (!empty($data)) {
            $http->setRawData(json_encode($data), self::ENC_TYPE);
        }
        if (!empty($files)) {
            foreach ($files as $formname => $filename) {
                $http->setFileUpload($filename,$formname);
            }
        }
        $response = $this->request($http);
        $processResponse = $this->processResponse($response);
        if (!$processResponse) {
            $this->throwBadGateway();
        }
        return $processResponse;
    }
    
    
    /**
     */
    protected function necTmBatchRequest($method, $endpointPath = '', $data = [], $queryParams = [], $files = []) {
        $http = $this->getHttp($method, $endpointPath);
        $http->setParameterPost($queryParams);
        $http->setHeaders('Authorization', 'JWT ' . $this->getAccessToken());
        if (!empty($data)) {
            $http->setRawData(json_encode($data), self::ENC_TYPE);
        }
        if (!empty($files)) {
            foreach ($files as $formname => $filename) {
                $http->setFileUpload($filename,$formname);
            }
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
                $ex=new editor_Services_Connector_Exception('E1282');
                $ex->setMessage($e->getMessage());
                throw $ex;
            }
            throw $e;
        }
    }
    
    /**
     * Throws a ZfExtended_BadGateway exception containing the underlying errors
     * @throws ZfExtended_BadGateway
     */
    protected function throwBadGateway() {
        //FIXME wenn in getErrors ein 403, dann kein Zugriff auf das TM - entweder weil nicht existent (wissen wir aber nicht) oder wegen fehlender credentials??? → den letzten Fall muss ich eh noch evaluieren
        // entpsrechend hier ein aussagekräfigerer Text ausgeben
        // Bei 401 Ebenfalls keine Zugriff Meldung (for Confluence-"Description / Solution": check table Zf_memcache also!)
        $e = new ZfExtended_BadGateway('NEC-TM-Api: The request returned an error.');
        $e->setDomain('NEC-TM-Api');
        $errors = $this->getErrors();
        $errors[] = $this->result; //add real result to error data
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