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
    
    
    public function __construct() {
        $this->log = ZfExtended_Factory::get('ZfExtended_Log');
    }
    
    
    /**
     * Checks if there is a TermTagger-server behind $url.
     * 
     * @param url $url url of the TermTagger-Server
     * 
     * @return boolean true if there is a TermTagger-Server behind $url 
     */
    public function testServerUrl(string $url) {
        try {
            $httpClient = new Zend_Http_Client();
            $httpClient->setUri($url);
            $response = $httpClient->request('GET');
            /* @var $response Zend_Http_Response */
        }
        catch(Exception $requestException) {
            $this->log->logError('Exception in processing '.__CLASS__.'->'.__FUNCTION__.'; TermTagger-Server not available under $url: '.$url);
            throw $requestException;
            return false;
        }
        
        // $url is OK if status == 200 AND string 'TermTagger Server' is in the response-body
        if ($response->getStatus() == '200' && strpos($response->getBody(), 'TermTagger Server')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * If no $tbxId given, checks if the TermTagger-Sever behind $url is alive. The returned status "404" in this case means "Server is alive".
     * If $tbxId is given, check if Server has loaded the tbx-file with the id $tbxId.
     * 
     * @param string $url url of the TermTagger-Server
     * @param string $tbxId unic id for a tbx-file
     * 
     * @return number http-status of server-response (eg. 200 or 404)
     */
    public function ping(string $url, $tbxId = null) {
        $httpClient = new Zend_Http_Client();
        $httpClient->setUri($url.'/tbxFile/'.$tbxId);
        $response = $httpClient->request('HEAD');
        /* @var $response Zend_Http_Response */
        //error_log(__CLASS__.'->'.__FUNCTION__.'; PING  $httpClient->getUri(): '.$url."\n".'$httpClient->getLastRequest(): '.$httpClient->getLastRequest());
        //error_log(__CLASS__.'->'.__FUNCTION__.'; PING  $httpClient->getUri(): '.$httpClient->getUri()."\n".'$response: '.$response);
        
        //error_log(__CLASS__.'->'.__FUNCTION__.'; PING  $httpClient->getUri(): '.$httpClient->getUri()
        //                                        ."\n".'$response->getStatus() / $response->getMessage(): '.$response->getStatus().' / '.$response->getMessage()
        //                                        ."\n".'$response->getHeaders(): '.print_r($response->getHeaders(), true)
        //                                        ."\n".'$response->getBody(): '.$response->getBody());
        return $response->getStatus();
    }
    
    
    /**
     * Load a tbx-file $tbxFilePath to the TermTagger-server behind $url where $tbxId is a unic id for this tbx-file
     *  
     * @param string $url url of the TermTagger-Server
     * @param string $tbxId unic id for this tbx-file
     * @param string $tbxFilePath path to the tbx-file 
     * 
     * @return number Http-response-status of the server. If everything is OK "200" else "404"
     */
    public function open(string $url, string $tbxId, string $tbxFilePath) {
        $response = $this->_open($url, $tbxId, $tbxFilePath);
        /* @var $response Zend_Http_Response */
        return $response->getStatus();
    }
    
    /**
     * Load a tbx-file $tbxFilePath to the TermTagger-server behind $url where $tbxId is a unic id for this tbx-file.
     * In addition to the function $this->open() this function returns the tbx-files with ids added to the xml-structur
     *  
     * @param string $url url of the TermTagger-Server
     * @param string $tbxId unic id for this tbx-file
     * @param string $tbxFilePath path to the tbx-file 
     * 
     * @return string json-decoded tbx-file
     */
    public function openFetchIds(string $url, string $tbxId, string $tbxFilePath) {
        $response = $this->_open($url, $tbxId, $tbxFilePath, array('addIds' => true));
        /* @var $response Zend_Http_Response */
        return $response->getBody();
    }
    
    private function _open($url, $tbxId, $tbxFilePath, $moreParams = array()) {
        // set default- and additional- (if any) -options for server-communication
        $serverCommunication = new stdClass();
        $serverCommunication->tbxFile = (string) $tbxId;
        $serverCommunication->tbxdata = file_get_contents($tbxFilePath);
        foreach ($moreParams as $key => $value) {
            $serverCommunication->$key = $value;
        }
        
        // send request to TermTagger-server
        $httpClient = new Zend_Http_Client();
        $httpClient->setUri($url.'/tbxFile/');
        $httpClient->setRawData(json_encode($serverCommunication), 'application/json');
        $response = $httpClient->request('POST');
        /* @var $response Zend_Http_Response */
        //error_log(__CLASS__.'->'.__FUNCTION__.'; $httpClient->getUri(): '.$url."\n".'$httpClient->getLastRequest(): '.$httpClient->getLastRequest());
        //error_log(__CLASS__.'->'.__FUNCTION__.'; $httpClient->getUri(): '.$httpClient->getUri()."\n".'$response: '.$response);
        
        return $response;
    }
    
    
    /**
     * 
     * @param unknown $url
     * @param editor_Plugins_TermTagger_Service_ServerCommunication $data
     */
    public function tagterms($url, editor_Plugins_TermTagger_Service_ServerCommunication $data) {
        $httpClient = new Zend_Http_Client();
        $httpClient->setUri($url.'/termTag/');
        $httpClient->setRawData(json_encode($data), 'application/json');
        $response = $httpClient->request('POST');
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
        $url .= '/tbxFile/';
        
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
        $this->test();
        
        $config = Zend_Registry::get('config');
        $defaultServers = $config->runtimeOptions->termTagger->url->default->toArray();
        $url = $defaultServers[array_rand($defaultServers)];
        $tbxId = 'a300e1140d20e0ac18672d6790e69e0b';
        $url .= '/termTag/';
        
        $serverCommunication = ZfExtended_Factory::get('editor_Plugins_TermTagger_Service_ServerCommunication');
        /*@var $serverCommunication editor_Plugins_TermTagger_Service_ServerCommunication */
        
        $serverCommunication->tbxFile = $tbxId;
        $serverCommunication->sourceLang = 'de';
        $serverCommunication->targetLang = 'en';
        
        $serverCommunication->addSegment(123, 'target', 'Source-Text', 'Target-Text');
        $serverCommunication->addSegment(456, 'target', 'Source-Text', 'Target-Text');
        
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
        $serverCommunication->sourceLang = 'de';
        $serverCommunication->targetLang = 'en';
        
        $serverCommunication->addSegment(123, 'target', 'Source-Text', 'Target-Text');
        $serverCommunication->addSegment(456, 'target', 'Source-Text', 'Target-Text');
        
        // finally call $this-
        $response = $this->tagterms($url, $serverCommunication);
        error_log(__CLASS__.'->'.__FUNCTION__.'; $response: '.print_r($response, true));
    }
}
