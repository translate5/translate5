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
 * Connector to LanguageTool
 * https://languagetool.org/http-api/swagger-ui/#/default
 */
class editor_Plugins_SpellCheck_LanguageTool_Connector {
    
    /**
     * LanguageTool
     */
    const PATH_LANGUAGES = '/languages';
    const PATH_MATCHES = '/check';
    const METHOD_LANGUAGES = 'GET';
    const METHOD_MATCHES = 'POST';
    
    /**
     * Request timeout for the api
     * @var integer
     */
    const REQUEST_TIMEOUT_SECONDS = 360;
    
    /**
     * @var Zend_Config
     */
    private $languageToolConfig;
    
    /**
     * Base-URL used for LanguagaTool - use the URL of your installed languageTool (without trailing slash!).
     * Taken from Zf_configuration (example: "http://yourlanguagetooldomain:8081/v2")
     * @var string
     */
    private $apiBaseUrl;
    
    /**
     * LanguageTool: supported languages
     * @var array
     */
    private $languages;
    
    /**
     * LanguageTool: matches from spellcheck
     * @var object
     */
    private $matches;
    
    /**
     * 
     */
    public function __construct() {
        $this->languageToolConfig= Zend_Registry::get('config')->runtimeOptions->plugins->SpellCheck;
        /* @var Zend_Config */
        
        $this->apiBaseUrl=$this->languageToolConfig->languagetool->api->baseurl;
    }
    
    /**
     * Create the http object and set the url
     * 
     * @param string $url
     * @return Zend_Http_Client
     */
    private function getHttpClient($path){
        $http = ZfExtended_Factory::get('Zend_Http_Client');
        /* @var $http Zend_Http_Client */
        $http->setUri($this->apiBaseUrl.$path);
        $http->setConfig(array('timeout'=>self::REQUEST_TIMEOUT_SECONDS));
        return $http;
    }
    
    /**
     * Check for the status of the response. If the status is different than 200,
     * ZfExtended_BadGateway exception is thrown.
     * Also the function checks for the invalid decoded json.
     * 
     * @param Zend_Http_Response $response
     * @throws ZfExtended_BadGateway
     * @throws ZfExtended_Exception
     * @return stdClass|string
     */
    private function processResponse(Zend_Http_Response $response){
        $validStates = [200]; // not checked: 
                              // - 201 Created (we don't create any resources)
                              // - 401 Unauthorized (we don't use authentication)
        
        $result = json_decode(trim($response->getBody()));
        
        return $result;
    }
    
    /**
     * Get all languages supported by LanguageTool.
     * @return array
     */
    public function getLanguages(){
        $http = $this->getHttpClient(self::PATH_LANGUAGES);
        $http->setHeaders('Content-Type: application/json');
        $http->setHeaders('Accept: application/json');
        
        $response = $http->request(self::METHOD_LANGUAGES);
        
        return $this->processResponse($response);
    }
    
    /**
     * Get matches from LanguageTool.
     * @param string $text
     * @param string $language
     * @return object
     */
    public function getMatches($text, $language){
        $http = $this->getHttpClient(self::PATH_MATCHES);
        $http->setHeaders('Content-Type: application/json');
        $http->setHeaders('Accept: application/json');
        
        $http->setParameterPost('text',$text);
        $http->setParameterPost('language',$language);
        
        $response = $http->request(self::METHOD_MATCHES);
        
        return $this->processResponse($response);
    }
}