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
 * OpenTM2 HTTP Connection API
 */
class editor_Services_OpenTM2_HttpApi {
    /**
     * @var editor_Models_LanguageResources_LanguageResource
     */
    protected $languageResource;
    
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
    
    public function __construct(editor_Models_LanguageResources_LanguageResource $languageResource) {
        $this->languageResource = $languageResource;
    }
    
    /**
     * This method creates a new memory.
     */
    public function createEmptyMemory($memory, $sourceLanguage) {
        $data = new stdClass();
        $data->name = $memory;
        $data->sourceLang = $sourceLanguage;
        
        $http = $this->getHttp('POST');
        $http->setRawData(json_encode($data), 'application/json; charset=utf-8');
        $res = $this->request($http);
        return $this->processResponse($res);
    }
    
    /**
     * This method creates a new memory with TM file
     */
    public function createMemory($memory, $sourceLanguage, $tmData) {
        $data = new stdClass();
        $data->name = $memory;
        $data->sourceLang = $sourceLanguage;
        $data->data = base64_encode($tmData);
        
        $http = $this->getHttp('POST');
        $http->setConfig(['timeout' => 1200]);
        $http->setRawData(json_encode($data), 'application/json; charset=utf-8');
        $res = $this->request($http);
        return $this->processResponse($res);
    }
    
    /**
     * This method imports a memory from a TMX file.
     */
    public function importMemory($tmData) {
        //In:{ "Method":"import", "Memory":"MyTestMemory", "TMXFile":"C:/FileArea/MyTstMemory.TMX" } 
        //Out: { "ReturnValue":0, "ErrorMsg":"" } 
        
        $data = new stdClass();
	    $tmData = str_replace('xml:lang="mn"','xml:lang="ru"',$tmData);
        $tmData = str_replace('xml:lang="hi"','xml:lang="ar"',$tmData);
        $tmData = str_replace('xml:lang="mn-MN"','xml:lang="ru-RU"',$tmData);
        $tmData = str_replace('xml:lang="hi-IN"','xml:lang="ar"',$tmData);
        $data->tmxData = base64_encode($tmData);

        $http = $this->getHttpWithMemory('POST', '/import');
        $http->setConfig(['timeout' => 1200]);
        $http->setRawData(json_encode($data), 'application/json; charset=utf-8');
        
        $res = $this->request($http);
        return $this->processResponse($res);
    }
    
    /**
     * prepares a Zend_Http_Client, prefilled with the configured URL + the given REST URL Parts (ID + verbs)
     * @param string $httpMethod
     * @param string $urlSuffix
     * @return Zend_Http_Client
     */
    protected function getHttp($method, $urlSuffix = '') {
        //TODO: here we need only the resource. No lr is required.
        $url = rtrim($this->languageResource->getResource()->getUrl(), '/');
        $urlSuffix = ltrim($urlSuffix, '/');
        $this->http = ZfExtended_Factory::get('Zend_Http_Client');
        /* @var $http Zend_Http_Client */
        $this->http->setUri($url.'/'.$urlSuffix);
        $this->http->setMethod($method);
        $this->httpMethod = $method;
        $this->http->setHeaders('Accept-charset', 'UTF-8');
        $this->http->setHeaders('Accept', 'application/json; charset=utf-8');
        return $this->http;
    }
    
    /**
     * prepares a Zend_Http_Client, prefilled with the configured URL + the Memory Name + additional URL parts
     * @param string $httpMethod
     * @param string $urlSuffix
     * @return Zend_Http_Client
     */
    protected function getHttpWithMemory($method, $urlSuffix = '') {
        return $this->getHttp($method, urlencode($this->languageResource->getSpecificData('fileName')).'/'.ltrim($urlSuffix, '/'));
    }
    
    /**
     * retrieves the TM as TM file
     * @param string|array $mime
     * @return boolean
     */
    public function get($mime) {
        if(is_array($mime)) {
            $mime = implode(',', $mime);
        }
        $http = $this->getHttpWithMemory('GET');
        $http->setConfig(['timeout' => 1200]);
        $http->setHeaders('Accept', $mime);
        $response = $this->request($http);
        if($response->getStatus() === 200) {
            $this->result = $response->getBody();
	    if($mime == "application/xml"){
		if($this->languageResource->targetLangRfc5646 == 'mn') $this->result = str_replace('xml:lang="ru"','xml:lang="mn"',$this->result);
		if($this->languageResource->targetLangRfc5646 == 'mn-MN') $this->result = str_replace('xml:lang="ru"','xml:lang="mn-MN"',$this->result);
		if($this->languageResource->targetLangRfc5646 == 'hi') $this->result = str_replace('xml:lang="ar"','xml:lang="hi"',$this->result);
		if($this->languageResource->targetLangRfc5646 == 'hi-IN') $this->result = str_replace('xml:lang="ar"','xml:lang="hi-IN"',$this->result);
		if($this->languageResource->targetLangRfc5646 == 'mn') $this->result = str_replace('<prop type="tmgr:language">RUSSIAN</prop>','<prop type="tmgr:language">MONGOLIAN</prop>',$this->result);
		if($this->languageResource->targetLangRfc5646 == 'mn-MN') $this->result = str_replace('<prop type="tmgr:language">RUSSIAN</prop>','<prop type="tmgr:language">MONGOLIAN</prop>',$this->result);
		if($this->languageResource->targetLangRfc5646 == 'hi') $this->result = str_replace('<prop type="tmgr:language">ARABIC</prop>','<prop type="tmgr:language">HINDI</prop>',$this->result);
		if($this->languageResource->targetLangRfc5646 == 'hi-IN') $this->result = str_replace('<prop type="tmgr:language">ARABIC</prop>','<prop type="tmgr:language">HINDI</prop>',$this->result);
	    }
            return true;
        }
        
        return $this->processResponse($response);
    }
    
    /**
     * retrieves the TM as TM file
     * @return boolean
     */
    public function status() {
        $http = $this->getHttpWithMemory('GET', '/status');
        $http->setConfig(['timeout' => 3]);
        return $this->processResponse($this->request($http));
    }

    /**
     * This method deletes a memory.
     */
    public function delete() {
        $http = $this->getHttpWithMemory('DELETE');
        return $this->processResponse($this->request($http));
    }
    
    /**
     * searches for matches in the TM
     * @param editor_Models_Segment $segment
     * @param string $queryString
     * @param string $filename
     * @return boolean
     */
    public function lookup(editor_Models_Segment $segment, string $queryString, string $filename) {
        $json = new stdClass();
	if($this->languageResource->targetLangRfc5646 == 'mn') $this->languageResource->targetLangRfc5646 = 'ru';
        if($this->languageResource->targetLangRfc5646 == 'mn-MN') $this->languageResource->targetLangRfc5646 = 'ru-RU';
        if($this->languageResource->targetLangRfc5646 == 'hi') $this->languageResource->targetLangRfc5646 = 'ar';
        if($this->languageResource->targetLangRfc5646 == 'hi-IN') $this->languageResource->targetLangRfc5646 = 'ar';
        $json->sourceLang = $this->languageResource->sourceLangRfc5646;
        $json->targetLang = $this->languageResource->targetLangRfc5646;
        $json->source = $queryString;
        //In general OpenTM2 can deal with whole paths, not only with filenames.
        // But we hold the filepaths in the FileTree JSON, so this value is not easily accessible, 
        // so we take only the single filename at the moment
        $json->documentName = $filename; 
        
        $json->segmentNumber = ''; //TODO can be used after implementing TRANSLATE-793
        $json->markupTable = 'OTMXUXLF';
        $json->context = $segment->getMid(); // here MID (context was designed for dialog keys/numbers on translateable strings software)
        
        $http = $this->getHttpWithMemory('POST', 'fuzzysearch');
        $http->setRawData(json_encode($json), 'application/json; charset=utf-8');
//ob_start();
        $response1 =  $this->request($http);
        $response2 =  $this->processResponse($response1);
//var_dump($json,$response1,$response2);
//error_log(ob_get_clean());
         return $response2;
    }
    
    /**
     * This method searches the given search string in the proposals contained in a memory (concordance search). 
     * The function returns one proposal per request. 
     * The caller has to provide the search position returned by a previous call or an empty search position to start the search at the begin of the memory.
     * Note: Provide the returned search position NewSearchPosition as SearchPosition on subsequenet calls to do a sequential search of the memory.
     */
    public function search($queryString, $field, $searchPosition = null) {
        $data = new stdClass();
        $data->searchString = $queryString;
        $data->searchType = $field;
        $data->searchPosition = $searchPosition;
        $data->numResults = 20;
        $data->msSearchAfterNumResults = 250;
        $http = $this->getHttpWithMemory('POST', 'concordancesearch');
        $http->setRawData(json_encode($data), 'application/json; charset=utf-8');
        return $this->processResponse($this->request($http));
    }

    /**
     * This method updates (or adds) a memory proposal in the memory.
     * Note: This method updates an existing proposal when a proposal with the same key information (source text, language, segment number, and document name) exists.
     * 
     * @param editor_Models_Segment $segment
     * @return boolean
     */
    public function update(string $source, string $target, editor_Models_Segment $segment, $filename) {
        /* 
         * In:{ "Method":"update", "Memory": "TestMemory", "Proposal": {
         *  "Source": "This is the source text", 
         *  "Target": "This is the translated text", 
         *  "Segment":231,
         *  "DocumentName":"Anothertest.txt", 
         *  "SourceLanguage":"en-US", 
         *  "TargetLanguage":"de-de", 
         *  "Type":"Manual", 
         *  "Author":"A.Nonymous", 
         *  "DateTime":"20161013T152948Z", 
         *  "Markup":"EQFHTML3", 
         *  "Context":"", 
         *  "AddInfo":"" }  } 
         */
        //Out: { "ReturnValue":0, "ErrorMsg":"" }
        $json = $this->json(__FUNCTION__);
        
        $json->source = $source;
        $json->target = $target;

        //$json->segmentNumber = $segment->getSegmentNrInTask(); FIXME TRANSLATE-793 must be implemented first, since this is not segment in task, but segment in file
        $json->documentName = $filename;
        $json->author = $segment->getUserName();
        $json->timeStamp = $this->nowDate();
        $json->context = $segment->getMid();
        
        $json->type = "Manual";
        $json->markupTable = "OTMXUXLF"; //fixed markup table for our XLIFF subset
        
        $json->sourceLang = $this->languageResource->getSourceLangRfc5646();
        $json->targetLang = $this->languageResource->getTargetLangRfc5646();

	if($json->targetLang == 'mn') $json->targetLang = 'ru';
        if($json->targetLang == 'mn-MN') $json->targetLang = 'ru-RU';
        if($json->targetLang == 'hi') $json->targetLang = 'ar';
        if($json->targetLang == 'hi-IN') $json->targetLang = 'ar';
        
        $http = $this->getHttpWithMemory('POST', 'entry');
        $http->setRawData(json_encode($json), 'application/json; charset=utf-8');

        $response = $this->processResponse($this->request($http));
//ob_start();
//var_dump($json,$response);
//error_log(ob_get_clean());
return $response;
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
    
    protected function badGateway(Zend_Exception $e, Zend_Http_Client $http) {
        $badGateway = new ZfExtended_BadGateway('Die angefragte OpenTM2 Instanz ist nicht erreichbar', 0, $e);
        $badGateway->setDomain('LanguageResources');
        
        $error = new stdClass();
        $error->type = 'HTTP';
        $error->error = $e->getMessage();
        $error->url = $http->getUri(true);
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
     * returns the current time stamp in the expected format for OpenTM2
     */
    protected function nowDate() {
        return gmdate('Ymd\THis\Z');
    }
}