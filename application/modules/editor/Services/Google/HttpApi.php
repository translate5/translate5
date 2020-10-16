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

use Google\Cloud\Translate\V2\TranslateClient;
use Google\Cloud\Core\Exception\BadRequestException;

class editor_Services_Google_HttpApi {
    /**
     * @var stdClass
     */
    protected $result;
    
    
    protected $error = array();
    
    /**
     *
     * @var Google\Cloud\Translate\TranslateClient
     */
    protected $translateClient;
    
    /***
     * Api key used for authentcication
     * @var string
     */
    protected $apiKey;
    
    /***
     *
     * @var string
     */
    protected $projectId;
    
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
        
        $this->apiKey = $config->runtimeOptions->LanguageResources->google->apiKey ?? null ;
        if(empty($this->apiKey)){
            throw new ZfExtended_BadGateway("Google translate api key is not defined");
        }
        
        $this->projectId = $config->runtimeOptions->LanguageResources->google->projectId ?? null ;
        if(empty($this->projectId)){
            throw new ZfExtended_BadGateway("Google translate project id is not defined");
        }
    }
    
    /**
     * Get the google translate api client.
     *
     * @return \Google\Cloud\Translate\V2\TranslateClient
     */
    protected function getTranslateClient() {
        $this->translateClient=new TranslateClient([
            'projectId' => $this->projectId,
            'key'=>$this->apiKey
        ]);
        return $this->translateClient;
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
            $response=$this->getTranslateClient()->translate($text,[
                'source'=>$sourceLang,
                'target'=>$targetLang
            ]);
            return $this->processResponse($response);
            
        } catch (BadRequestException $e) {
            $this->badGateway($e);
            return false;
        }
    }
    
    /**
     * Search the api for given source/target language by domainCode
     *
     * @param array $text
     * @param string $sourceLang
     * @param string $targetLang
     * @return boolean
     */
    public function searchBatch($text,$sourceLang,$targetLang) {
        try {
            $response=$this->getTranslateClient()->translateBatch($text,[
                'source'=>$sourceLang,
                'target'=>$targetLang
            ]);
            return $this->processResponse($response);
            
        } catch (BadRequestException $e) {
            $this->badGateway($e);
            return false;
        }
    }
    
    /** Check the api status
     * @return boolean
     */
    public function getStatus(){
        try {
            $result=$this->getTranslateClient()->translate('Hi',[
                'source'=>'en',
                'target'=>'de'
            ]);
            return !empty($result);
        } catch (Exception $e) {
            $this->badGateway($e);
            return false;
        }
    }
    
    /***
     * A list of supported ISO 639-1 language codes.
     * @return array
     */
    public function getLanguages() {
        return $this->getTranslateClient()->languages();
    }
    
    /***
     * Check if the given language code is valid for the api
     * @param string $languageCode: language code
     * @return boolean
     */
    public function isValidLanguage($languageCode){
        $client=$this->getTranslateClient();
        try {
            if($languageCode=='en'){
                return true;
            }
            $client->translate("Hello, this is simple language test.",[
               'source'=>'en',
               'target'=>$languageCode
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
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
        $badGateway = new ZfExtended_BadGateway('Die angefragte Google Instanz ist nicht erreichbar', 0, $e);
        $badGateway->setDomain('Google Api');
        
        $error = new stdClass();
        $error->type = 'HTTP';
        try {
            $jsonError = json_decode($e->getMessage());
            $err = $jsonError->error;
            $error->error = $err->message;
            $error->code = $err->code;
            $badGateway->setMessage($err->message);
        } catch (Exception $e) {
            $error->error = $e->getMessage();
        }
        
        $error->method ='GET';
        
        $badGateway->setErrors([$error]);
        throw $badGateway;
    }
    
    /**
     * Set the response result
     * @return boolean
     */
    protected function processResponse(array $response) {
        $this->result = $response;
        return empty($this->error);
    }
    
    /**
     * returns the current time stamp in the expected format for OpenTM2
     */
    protected function nowDate() {
        return gmdate('Ymd\THis\Z');
    }
}