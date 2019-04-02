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

class editor_Services_Microsoft_HttpApi {
    /**
     * @var stdClass
     */
    protected $result;
    
    
    protected $error = array();
    
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
    
    
    /***
     * Id dictonary lookup search request
     * @var string
     */
    protected $isDictionaryLookup=false;
    
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
        
        $this->apiKey = isset($config->runtimeOptions->LanguageResources->microsoft->apiKey) ?$config->runtimeOptions->LanguageResources->microsoft->apiKey:null ;
        if(empty($this->apiKey)){
            throw new ZfExtended_Exception("Microsoft translator api key is not defined");
        }
        
        $this->apiUrl=isset($config->runtimeOptions->LanguageResources->microsoft->apiUrl) ?$config->runtimeOptions->LanguageResources->microsoft->apiUrl:null ;
        if(empty($this->apiUrl)){
            throw new ZfExtended_Exception("Microsoft translator api url is not defined");
        }
    }
    
    /**
     * Search the api for given source/target language by domainCode
     * 
     * @param string $text
     * @param string $sourceLang
     * @param string $targetLang
     * @return boolean
     */
    public function search($text,$sourceLang,$targetLang) {
        try {
            
            $requestBody = array (
                array (
                    'Text' => $text,
                ),
            );
            $content = json_encode($requestBody);
            
            $guidHelper = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
                'Guid'
                );
            $guid=$guidHelper->create(true);
            
            $headers = "Content-type: application/json\r\n" .
                "Content-length: " . strlen($content) . "\r\n" .
                "Ocp-Apim-Subscription-Key:". $this->apiKey."\r\n" .
                "X-ClientTraceId: " . $guid . "\r\n";
            
            // NOTE: Use the key 'http' even if you are making an HTTPS request. See:
            // http://php.net/manual/en/function.stream-context-create.php
            $options = array (
                'http' => array (
                    'header' => $headers,
                    'method' => 'POST',
                    'content' => $content
                )
            );
            $context  = stream_context_create ($options);
            
            //set the default mode, only translation
            $path="/translate?api-version=3.0";
            
            //if it is dictonary lookup, change the path
            if($this->isDictionaryLookup){
                $path="/dictionary/lookup?api-version=3.0";
            }
                
            $url=$this->apiUrl.$path."&from=".$sourceLang."&to=".$targetLang;
            $result = file_get_contents ($url, false, $context);
            
            return $this->processResponse($result);
            
        } catch (Exception $e) {
            $this->badGateway($e);
            return false;
        }
    }
    
    /** Check the api status
     * @return boolean
     */
    public function getStatus(){
        return true;
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
    
    protected function badGateway(Exception $e) {
        $badGateway = new ZfExtended_BadGateway('Die angefragte Microsoft Instanz ist nicht erreichbar', 0, $e);
        $badGateway->setDomain('Microsoft Api');
        
        $error = new stdClass();
        $error->type = 'HTTP';
        $error->error = $e->getMessage();
        $error->method ='GET';
        
        $badGateway->setErrors([$error]);
        throw $badGateway;
    }
    
    /**
     * Set the response result
     * @return boolean
     */
    protected function processResponse($response) {
        $result=json_decode($response,true);
        $translation=isset($result[0]['translations']) ? $result[0]['translations'] : [];
        if(empty($translation)){
            return empty($this->error);
        }
        
        $collection=[];
        foreach ($translation as $single) {
            //the response layout contains only text, when no dictonary lookup is used
            if(isset($single['text'])){
                $collection[]=[
                    'text'=>$single['text']
                ];
            }else{
                //the request is triggered for dictonary lookup, collect the additinal translations
                $collection[]=[
                    'text'=>isset($single['displayTarget']) ? $single['displayTarget'] : '',
                    'metaData'=>$single
                ];
            }
        }
        $this->result=$collection;
        return empty($this->error);
    }
    
    public function setIsDictionaryLookup(boolean $value){
        $this->isDictionaryLookup=$value;
    }
}