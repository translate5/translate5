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
use Google\Cloud\Core\Exception\GoogleException;

/**
 * Wraps the Google Connection and converts the google errors to our internal errors
 */
class editor_Services_Google_ApiWrapper {
    
    /**
     * @var stdClass
     */
    protected $result;
    
    /**
     * @var GoogleException
     */
    protected $error = null;
    
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
    
    /**
     *
     * @var string
     */
    protected $projectId;
    
    /**
     * @var editor_Services_Google_Resource
     */
    protected $resource;
    
    public function __construct(editor_Services_Google_Resource $resource) {
        $this->resource = $resource;
        $this->translateClient = new TranslateClient([
            'projectId' => $resource->getProjectId(),
            'key' => $resource->getAuthenticationKey()
        ]);
    }
    
    /**
     * Search the api for given source/target language by domainCode
     *
     * @param string $text
     * @param string $sourceLang
     * @param string $targetLang
     * @return boolean
     */
    public function translate(string $text, string $sourceLang, string $targetLang) {
        return $this->callWrapped(__FUNCTION__, [$text, [
            'source' => $sourceLang,
            'target' => $targetLang
        ]]);
    }
    
    /**
     * Search the api for given source/target language by domainCode
     *
     * @param array $text
     * @param string $sourceLang
     * @param string $targetLang
     * @return array|null|false
     */
    public function translateBatch(array $text, string $sourceLang, string $targetLang) {
        return $this->callWrapped(__FUNCTION__, [$text, [
            'source' => $sourceLang,
            'target' => $targetLang
        ]]);
    }
    
    /**
     * A list of supported ISO 639-1 language codes.
     * @return array
     */
    public function getLanguages(): array {
        $result = $this->callWrapped('languages');
        if($result === false) {
            return [];
        }
        return $this->getResult();
    }
    
    /***
     * Check if the given language code is valid for the api
     * @param string $languageCode: language code
     * @return boolean
     */
    public function isValidLanguage($languageCode){
        $languages = $this->getLanguages();
        $languages = array_map('strtolower', $languages);
        return in_array($languageCode, $languages);
    }
    
    /**
     * Set the response result
     * @return boolean
     */
    protected function processResponse(array $response) {
        $this->result = $response;
        return empty($this->error);
    }
    
    public function getError(): ?GoogleException {
        return $this->error;
    }
    
    
    /**
     * returns the decoded JSON result
     */
    public function getResult() {
        return $this->result;
    }
    
    /**
     * Wraps the Google API to catch and convert the errors
     * @param string $method
     * @param array $arguments
     * @throws editor_Services_Connector_Exception
     * @return mixed
     */
    protected function callWrapped(string $method, array $arguments = []) {
        $this->error = null;
        try {
            $response = call_user_func_array([$this->translateClient, $method], $arguments);
            return $this->processResponse($response);
        } catch (GoogleException $e) {
            $this->error = $e;
            return false;
        }
    }
}