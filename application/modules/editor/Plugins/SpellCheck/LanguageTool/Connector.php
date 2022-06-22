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
     * Base-URL used for LanguagaTool - use the URL of your installed languageTool (without trailing slash!).
     * Taken from Zf_configuration (example: "http://yourlanguagetooldomain:8081/v2")
     * @var string
     */
    private $apiBaseUrl;

    /**
     * @var array
     */
    private static $languages = [

        /**
         * Translate5 languages [id => rfc5646] pairs.
         * Lazy-loaded by $this->getSpellCheckLangByTaskTargetLangId() call
         */
        'translate5'   => null,

        /**
         * Array of languages supported by LanguageTool, each represended as
         * stdClass instance having `name`, `code` and `longCode` properties
         * Lazy-loaded by $this->getLanguages() call
         */
        'languageTool' => null,

        /**
         * Combined array of [id => longCode/false] pairs, where `id`-s are from translate5 and
         * `longCode`-s are from LanguageTool if certain language is supported.
         * Those codes need to be passed as 2nd arg of LanguageTool connector's getMatches() call
         * Currently this is used to detect whether spellcheck-quality provider should process segments
         * of a certain task, so if `false` - it means that target language of that task is not supported by LanguageTool,
         * and in that case no segments will be processed by this quality provider
         */
        'argByLangId'  => [],
    ];

    /**
     * LanguageTool: matches from spellcheck
     * @var object
     */
    private $matches;

    /**
     * 
     */
    public function __construct($slot = null) {
        $this->apiBaseUrl = $slot ?? Zend_Registry::get('config')->runtimeOptions->plugins->SpellCheck->languagetool->url->gui;
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

        // Return cached, if already cached
        if (isset(self::$languages['languageTool'])) {
            return self::$languages['languageTool'];
        }

        $http = $this->getHttpClient(self::PATH_LANGUAGES);
        $http->setHeaders('Content-Type: application/json');
        $http->setHeaders('Accept: application/json');
        
        $response = $http->request(self::METHOD_LANGUAGES);
        //class_exists('editor_Utils');
        //i(stack(), 'a');

        return self::$languages['languageTool'] = $this->processResponse($response);
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

    /**
     * Is the language supported by the LanguageTool?
     * Examples:
     * |----------------------------------------------------------------------------|
     * |---from Editor-----|--see LEK_languages---|--------see LanguageTool---------|
     * |----------------------------------------------------------------------------|
     * | targetLang (=rfc) | mainl. | sublanguage | longcode | needed result for LT |
     * |----------------------------------------------------------------------------|
     * |      de           |   de   |   de-DE     |   de-DE  |       de-DE          |
     * |     de-DE         |   de   |   de-DE     |   de-DE  |       de-DE          |
     * |     de-AT         |   de   |   de-AT     |   de-AT  |       de-AT          |
     * |      fr           |   fr   |   fr-FR     |     fr   |         fr           |
     * |     fr-FR         |   fr   |   fr-FR     |     fr   |         fr           |
     * |      he           |   he   |   he-IL     |     he   |         he           |
     * |      cs           |   cs   |   cs-CZ     |     cs   |         cs           |
     * |     cs-CZ         |   cs   |   cs-CZ     |     cs   |         cs           |
     * |----------------------------------------------------------------------------|
     * @param string $targetLangCode
     * @return object|false
     */
    public function getSupportedLanguage($targetLangCode) {

        // Get supported languages
        $supportedLanguages = $this->getLanguages();

        /* @var $languagesModel editor_Models_Languages */
        $languagesModel = ZfExtended_Factory::get('editor_Models_Languages');

        // Get main-language and sub-language
        $mainlanguage = $languagesModel->getMainlanguageByRfc5646($targetLangCode);
        $sublanguage  = $languagesModel->getSublanguageByRfc5646($targetLangCode);

        // Try to find sublanguage among supported languages
        foreach ($supportedLanguages as $lang) {
            if ($lang->longCode == $sublanguage) {      // priority: check if longCode (e.g. "de-DE","cs") is the default sublanguage ("de-DE", "cs-CZ") of the targetLangCode ("de", "cs-CZ")
                return $lang;
            }
        }

        // Try to find mainlanguage among supported languages
        foreach ($supportedLanguages as $lang) {
            if ($lang->longCode == $mainlanguage) {     // fallback: check if longCode (e.g. "fr", "cs") is the mainlanguage ("fr", "cs") of the targetLangCode ("fr", "cs-CZ")
                return $lang;
            }
        }

        // Return false if nothing found
        return false;
    }

    /**
     * Get target lang supported by LanguageTool by task's targetLangId-prop
     *
     * @param $targetLangId
     * @return string|false
     */
    public function getSpellCheckLangByTaskTargetLangId($targetLangId) {

        // If self::$languages['argByLangId'] is non-null this means
        // that we've already checked whether current task's target language is
        // supported by LanguageTool, and this, in it's turn, means that this variable
        // is either `false` (if target lang is not supported), or string-code to be
        // used as 2nd arg for getMatches() call (if target lang is supported)
        if (isset(self::$languages['argByLangId'][$targetLangId])) {
            return self::$languages['argByLangId'][$targetLangId];
        }

        // Load translate5 languages [id => rfc5646] pairs
        self::$languages['translate5'] = self::$languages['translate5']
            ?? ZfExtended_Factory
                ::get('editor_Models_Languages')
                ->loadAllKeyValueCustom('id', 'rfc5646');

        // Get object representing language supported by LanguageTool
        $spellCheckLang = $this->getSupportedLanguage(self::$languages['translate5'][$targetLangId]);

        // Get language code supported by LanguageTool
        return self::$languages['argByLangId'][$targetLangId] = $spellCheckLang ? $spellCheckLang->longCode : false;
    }

    /**
     * Getter for $this->apiBaseUrl
     *
     * @return string|null
     */
    public function getApiBaseUrl() {
        return $this->apiBaseUrl;
    }
}