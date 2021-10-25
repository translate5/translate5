<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Service Class of Plugin "Vendor"
 */
class erp_Models_Tvin_VendorService {
    
    /**
     * @var ZfExtended_Log
     */
    protected $log;
    
    /**
     * contains the HTTP status of the last request
     * @var integer
     */
    protected $lastStatus;
    
    /**
     *
     * @var Zend_Config
     */
    protected $config;

    /*
        source language
     */
    private $sourceLang;

    /*
        target language
    */
    private $targetLang;
    
    /*
        authentication key needed for tvin
    */
    private $apiKey;
    
    /*
        tvin url
    */
    private $url;

    public function __construct() {
        $this->log = ZfExtended_Factory::get('ZfExtended_Log');
        $config = Zend_Registry::get('config');
        $this->config = $config->runtimeOptions->vendor;
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
     * Checks if there is a TVIN-server behind $url.
     * 
     * @param url $url url of the TVIN-Server
     * 
     * @return boolean true if there is a TVIN-Server behind $url 
     */
    public function testServerUrl(string $url, &$version = null) {
        $httpClient = $this->getHttpClient($this->buildUrl());
        $httpClient->setHeaders('accept', 'application/json');
        try {
            $response = $this->sendRequest($httpClient, $httpClient::GET);
        }
        catch(ZfExtended_Exception $e) {
            return false;
        }
        
        return $response && $this->wasSuccessfull();
    }
    
    /**
     * @return Zend_Http_Response
     */
    public function open() {
        if(empty($this->getSourceLang()) || empty($this->getTargetLang())) {
            throw new ZfExtended_Exception('Source or target language is empty');
        }
        return $this->_open();
    }
    
    /**
     * sends an open request to the TVIN
     * @param array $moreParams
     * @throws ZfExtended_Exception
     * @return Zend_Http_Response
     */
    private function _open($moreParams = array()) {
        // get default- and additional- (if any) -options for server-communication
        $serverCommunication = new stdClass();
        $serverCommunication->source=$this->getSourceLang();
        $serverCommunication->target=$this->getTargetLang();
        foreach ($moreParams as $key => $value) {
            $serverCommunication->$key = $value;
        }
        
        // send request to TVIN-server
        $httpClient = $this->getHttpClient($this->buildUrl());

        //$httpClient->setConfig(array('timeout' => (integer)$this->config->timeOut->tbxParsing));
        $httpClient->setRawData(json_encode($serverCommunication), 'application/json');
        $response = $this->sendRequest($httpClient, $httpClient::GET);
        if(!$this->wasSuccessfull()) {
            throw new ZfExtended_Exception("The request was not successfull");
        }
        $response = $this->decodeServiceResult($response);
        if (!$response) {
            throw new ZfExtended_Exception('TVIN HTTP Result could not be decoded!');
        }
        return $response;
    }
    
    /**
     * send request method
     * @param Zend_Http_Client $client
     * @param string $method
     * @throws ZfExtended_Exception
     * @return Zend_Http_Response
     */
    protected function sendRequest(Zend_Http_Client $client, $method) {
        $this->lastStatus = false;
        $client->setConfig(['timeout' => 3000]);
        try {
            $result = $client->request($method);
            $this->lastStatus = $result->getStatus();
            return $result;
        } catch(ZfExtended_Exception $httpException) {
            throw new ZfExtended_Exception($httpException);
        }
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
     * Decodes the TVIN JSON
     * @param Zend_Http_Response $result
     * @return stdClass or null on error
     */
    private function decodeServiceResult(Zend_Http_Response $result = null) {
        if(empty($result)) {
            return null;
        }
    
        $data = json_decode($result->getBody());
        if(!empty($data)) {
            if(!empty($data->error)) {
                $this->log->logError(__CLASS__.' decoded TVIN Result but with following Error from TVIN: ', print_r($data,1));
            }
            return $data;
        }
        $msg = "Original TVIN Result was: \n".$result->getBody()."\n JSON decode error was: ";
        
        $msg .= json_last_error_msg();
        
        $this->log->logError(__CLASS__.' cannot json_decode TVIN Result!', $msg);
        return null;
    }

    private function getApiKey(){
        return $this->config->apiKey;
    }
    
    private function getUrl(){
        return  $this->config->tvinUrl;
    }

    private function buildUrl(){
        $req =$this->getUrl().'/api/Test?source='.$this->getSourceLang().'&target='.$this->getTargetLang().'&apikey='.$this->getApiKey(); 
        //error_log(print_r($req,1));
        return $req;
    }

    public function setSourceLang($sl){
        $this->sourceLang=$sl;
    }
    
    public function setTargetLang($tl){
        $this->targetLang=$tl;
    }

    public function getSourceLang(){
        return $this->sourceLang;
    }

    public function getTargetLang(){
        return $this->targetLang;
    }

}
