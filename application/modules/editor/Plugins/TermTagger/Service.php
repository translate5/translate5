<?php

 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */
/**
 * Service Class of Plugin "TermTagger"
 */
class editor_Plugins_TermTagger_Service {
    
    /**
     * @var ZfExtended_Log
     */
    protected $log;
    
    /**
     * contains the HTTP status of the last request
     * @var integer
     */
    protected $lastStatus;
    
    
    public function __construct() {
        $this->log = ZfExtended_Factory::get('ZfExtended_Log');
    }
    
    /**
     * returns the HTTP Status of the last request 
     * @return integer
     */
    public function getLastStatus() {
        return (int) $this->lastStatus;
    }
    
    /**
     * returns true if the last request was HTTP state 2**
     * @return boolean
     */
    public function wasSuccessfull() {
        $stat = $this->getLastStatus();
        return $stat >= 200 && $stat < 300;
    }
    
    /**
     * Checks if there is a TermTagger-server behind $url.
     * 
     * @param url $url url of the TermTagger-Server
     * 
     * @return boolean true if there is a TermTagger-Server behind $url 
     */
    public function testServerUrl(string $url) {
        $httpClient = $this->getHttpClient($url.'/termTagger');
        $response = $this->sendRequest($httpClient, $httpClient::GET);
        
        // $url is OK if status == 200 AND string 'TermTagger Server' is in the response-body
        return $response && $this->wasSuccessfull() && strpos($response->getBody(), 'TermTagger Server') !== false;
    }
    
    /**
     * If no $tbxHash given, checks if the TermTagger-Sever behind $url is alive.
     * If $tbxHash is given, check if Server has loaded the tbx-file with the id $tbxHash.
     * 
     * @param string $url url of the TermTagger-Server
     * @param string tbxHash unic id for a tbx-file
     * 
     * @return boolean True if ping was succesfull
     */
    public function ping(string $url, $tbxHash = null) {
        $httpClient = $this->getHttpClient($url.'/termTagger/tbxFile/'.$tbxHash);
        $response = $this->sendRequest($httpClient, $httpClient::HEAD);
        return ($response && ($tbxHash && $this->wasSuccessfull() || !$tbxHash && $this->getLastStatus() == 404));
    }
    
    
    /**
     * Load a tbx-file $tbxFilePath to the TermTagger-server behind $url where $tbxHash is a unic id for this tbx-file
     *  
     * @param string $url url of the TermTagger-Server
     * @param string $tbxHash TBX hash
     * @param string $tbxData TBX data 
     * 
     * @return Zend_Http_Response|false
     */
    public function open(string $url, string $tbxHash, string $tbxData) {
        return $this->_open($url, $tbxHash, $tbxData);
    }
    
    /**
     * Load a tbx-file $tbxFilePath to the TermTagger-server behind $url where $tbxHash is a unic id for this tbx-file.
     * In addition to the function $this->open() this function returns the tbx-files with ids added to the xml-structur
     *  
     * @param string $url url of the TermTagger-Server
     * @param string $tbxHash TBX hash
     * @param string $tbxData TBX data 
     * 
     * @return Zend_Http_Response|false
     */
    public function openFetchIds(string $url, string $tbxHash, string $tbxData) {
        return $this->_open($url, $tbxHash, $tbxData, array('addIds' => true));
    }
    
    /**
     * sends an open request to the termtagger
     * @param string $url
     * @param string $tbxHash
     * @param string $tbxData
     * @param array $moreParams
     * @return boolean|Zend_Http_Response
     */
    private function _open($url, $tbxHash, $tbxData, $moreParams = array()) {
        // get default- and additional- (if any) -options for server-communication
        $serverCommunication = new stdClass();
        $serverCommunication->tbxFile = $tbxHash;
        foreach ($moreParams as $key => $value) {
            $serverCommunication->$key = $value;
        }
        $serverCommunication->tbxdata = $tbxData;
        
        // send request to TermTagger-server
        $httpClient = $this->getHttpClient($url.'/termTagger/tbxFile/');
        $httpClient->setRawData(json_encode($serverCommunication), 'application/json');
        $response = $this->sendRequest($httpClient, $httpClient::POST);
        if(!$response) {
            //was exception => already logged
            return false;
        }
        if(!$this->wasSuccessfull()) {
            $msg = 'TermTagger HTTP Status was: '.$this->getLastStatus();
            $msg .= "\n URL: ".$httpClient->getUri(true)."\n\nRequested Data: ";
            $msg .= print_r($serverCommunication,true)."\n\nPlain Server Response: ";
            $msg .= print_r($response,true);
            $this->log->logError('Result of opening a TBX was not OK!', $msg);
            return false;
        }
        
        return $response;
    }
    
    /**
     * send request method with unified logging
     * @param Zend_Http_Client $client
     * @param string $method
     * @return Zend_Http_Response | false on error
     */
    protected function sendRequest(Zend_Http_Client $client, $method) {
        $this->lastStatus = false;
        try {
            $result = $client->request($method);
            $this->lastStatus = $result->getStatus();
            return $result;
        } catch(Exception $httpException) {
            //logging the send data is irrelevant here, since we are logging communication errors, not termtagger server errors!
            $msg = 'Method: '.$method.'; URL was: '.$client->getUri(true);
            $this->log->logError('Exception in communication with termtagger in '.__CLASS__, $msg);
            $this->log->logException($httpException);
        }
        return false;
    }
    
    /**
     * instances a Zend_Http_Client Object, sets the desired URI and returns it
     * @param string $uri
     * @return Zend_Http_Client
     */
    protected function getHttpClient($uri) {
        $client = new Zend_Http_Client();
        $client->setUri($uri);
        return $client;
    }
    
    /**
     * TermTaggs segment-text(s) in $data on TermTagger-server $url 
     * 
     * @param unknown $url
     * @param editor_Plugins_TermTagger_Service_ServerCommunication $data
     * 
     * @return array((obj) segments) list of segment-objects of FALSE on error
     */
    public function tagterms($url, editor_Plugins_TermTagger_Service_ServerCommunication $data) {
        try {
            $httpClient = new Zend_Http_Client();
            $httpClient->setUri($url.'/termTagger/termTag/');
            $httpClient->setRawData(json_encode($data), 'application/json');
            $httpClient->setConfig(array('timeout' => 60));
            $response = $httpClient->request('POST');
        } catch(Exception $httpException) {
            $this->log->logError('Exception in communication with termtagger in '.__CLASS__.'::'.__FUNCTION__);
            $this->log->logException($httpException);
            return false;
        }
        /* @var $response Zend_Http_Response */
        //error_log(__CLASS__.'->'.__FUNCTION__.'; TERMTAG-REQUEST  $httpClient->getUri(): '.$httpClient->getUri()."\n".'$httpClient->getLastRequest(): '.$httpClient->getLastRequest());
        //error_log(__CLASS__.'->'.__FUNCTION__.'; TERMTAG-RESPONSE  $httpClient->getUri(): '.$httpClient->getUri()."\n".'$response: '.$response);
        
        if ($response->getStatus() != "200") {
            return false;
        }
        
        $responseDecoded = json_decode($response->getBody());
        $segments = $responseDecoded->segments;
        
        return $segments;
    }
    
    
    
    // ***********************************************************************
    // all following functions are only for testing while development.... can be deleted
    // ***********************************************************************
    public function test() {
        error_log(__CLASS__.' -> '.__FUNCTION__);
        
        $config = Zend_Registry::get('config');
        $defaultServers = $config->runtimeOptions->termTagger->url->default->toArray();
        $url = $defaultServers[array_rand($defaultServers)];
        $tbxId = 'a300e1140d20e0ac18672d6790e69e0b';
        $url .= '/termTagger/tbxFile/';
        
        $httpClient = new Zend_Http_Client();
        
        // push tbx to TermTaggerServer
        $httpClient->setUri($url);
        $httpClient->setRawData($this->getTestJson($tbxId), 'application/json');
        $response = $httpClient->request('POST');
        /* @var $response Zend_Http_Response */
        //error_log(__CLASS__.'->'.__FUNCTION__.'; $httpClient->getUri(): '.$url."\n".'$httpClient->getLastRequest(): '.$httpClient->getLastRequest());
        error_log(__CLASS__.'->'.__FUNCTION__.'; UPLOAD TBX  $httpClient->getUri(): '.$httpClient->getUri()."\n".'$response: '.$response);
        
        return;
        
        // check tbx on TermTaggerServer
        $httpClient->setUri($url.$tbxId);
        $response = $httpClient->request('HEAD');
        error_log(__CLASS__.'->'.__FUNCTION__.'; CHECK TBX  $httpClient->getUri(): '.$httpClient->getUri()."\n".'$response: '.$response);
        
        // delete tbx on TermTaggerServer
        $httpClient->setUri($url.$tbxId);
        $response = $httpClient->request('DELETE');
        //error_log(__CLASS__.'->'.__FUNCTION__.'; $httpClient->getUri(): '.$url."\n".'$httpClient->getLastRequest(): '.$httpClient->getLastRequest());
        error_log(__CLASS__.'->'.__FUNCTION__.'; DELETE TBX  $httpClient->getUri(): '.$httpClient->getUri()."\n".'$response: '.$response);
        
        // check tbx on TermTaggerServer
        $httpClient->setUri($url.$tbxId);
        $response = $httpClient->request('HEAD');
        error_log(__CLASS__.'->'.__FUNCTION__.'; CHECK TBX AGAIN  $httpClient->getUri(): '.$httpClient->getUri()."\n".'$response: '.$response);
        
    }
    
    
    private function getTestJson($tbxFileId = NULL) {
        //$testJson = file_get_contents('/Users/sb/Desktop/_MittagQI/TRANSLATE-22/TermTagger-Server/json_test_data/tbx_post_request.json');
        //return $testJson;
                
        $tempReturn = array();
        $tempReturn['tbxFile'] = $tbxFileId;
        $tempReturn['addIds'] = true;
        $tempReturn['tbxdata'] = file_get_contents('/Users/sb/Desktop/_MittagQI/TRANSLATE-22/TermTagger-Server/{C1D11C25-45D2-11D0-B0E2-444553540203}.tbx');
        //error_log(__CLASS__.'->'.__FUNCTION__.'; $tempReturn: '.print_r($tempReturn, true));
        
        return json_encode($tempReturn);
    }
    
    
    public function test_2() {
        //$this->test();
        
        $config = Zend_Registry::get('config');
        $defaultServers = $config->runtimeOptions->termTagger->url->default->toArray();
        $url = $defaultServers[array_rand($defaultServers)];
        $tbxId = 'a300e1140d20e0ac18672d6790e69e0b';
        $url .= '/termTagger/termTag/';
        
        $serverCommunication = ZfExtended_Factory::get('editor_Plugins_TermTagger_Service_ServerCommunication');
        /*@var $serverCommunication editor_Plugins_TermTagger_Service_ServerCommunication */
        
        $serverCommunication->tbxFile = $tbxId;
        $serverCommunication->sourceLang = 'de';
        $serverCommunication->targetLang = 'en';
        
        $serverCommunication->addSegment(123, 'target', 'Haus', 'home');
        $serverCommunication->addSegment(456, 'target', 'Apache', 'Apache');
        
        //error_log(__CLASS__.' -> '.__FUNCTION__.'; $serverCommunication: '.print_r($serverCommunication, true));
        //error_log(__CLASS__.' -> '.__FUNCTION__.'; $serverCommunication: '.json_encode($serverCommunication));
        
        $httpClient = new Zend_Http_Client();
        $httpClient->setUri($url);
        $httpClient->setRawData(json_encode($serverCommunication), 'application/json');
        $response = $httpClient->request('POST');
        error_log(__CLASS__.'->'.__FUNCTION__.'; TERMTAG-REQUEST  $httpClient->getUri(): '.$httpClient->getUri()."\n".'$httpClient->getLastRequest(): '.$httpClient->getLastRequest());
        error_log(__CLASS__.'->'.__FUNCTION__.'; TERMTAG-RESPONSE  $httpClient->getUri(): '.$httpClient->getUri()."\n".'$response: '.$response);
    }
    
    public function testTagging() {
        // select a TermTagger-Server and set tbxId
        $config = Zend_Registry::get('config');
        $defaultServers = $config->runtimeOptions->termTagger->url->default->toArray();
        $url = $defaultServers[array_rand($defaultServers)];
        $tbxId = 'a300e1140d20e0ac18672d6790e69e0b';
        
        // config  ServerCommunication-data
        $serverCommunication = ZfExtended_Factory::get('editor_Plugins_TermTagger_Service_ServerCommunication');
        /*@var $serverCommunication editor_Plugins_TermTagger_Service_ServerCommunication */
        
        $serverCommunication->tbxFile = $tbxId;
        $serverCommunication->sourceLang = 'en';
        $serverCommunication->targetLang = 'de';
        
        //$serverCommunication->addSegment(123, 'target', 'macht prefork Haus home', 'macht prefork Haus home');
        $serverCommunication->addSegment(1417, 'target', 'More information about installation options for Apache may be found there.', 'Apache macht prefork Haus home');
                
        // finally call $this-
        $response = $this->tagterms($url, $serverCommunication);
        error_log(__CLASS__.'->'.__FUNCTION__.'; $response: '.print_r($response, true));
    }
}
