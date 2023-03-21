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

namespace MittagQI\Translate5\Plugins\SpellCheck\LanguageTool;

use editor_Models_Languages;
use Exception;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\DownException;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\MalfunctionException;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\RequestException;
use MittagQI\Translate5\Plugins\SpellCheck\Exception\TimeOutException;
use Zend_Http_Client;
use Zend_Http_Response;
use ZfExtended_BadGateway;
use ZfExtended_Factory;
use ZfExtended_Zendoverwrites_Http_Exception_Down;
use ZfExtended_Zendoverwrites_Http_Exception_NoResponse;
use ZfExtended_Zendoverwrites_Http_Exception_TimeOut;

/**
 * Connector to LanguageTool
 */
final class Adapter {
    
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
    private $serviceUrl;

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
     * Contains the HTTP status of the last request
     *
     * @var integer
     */
    protected $lastStatus;

    /**
     * 
     */
    public function __construct($serviceUrl) {
        $this->serviceUrl = $serviceUrl;
    }

    /**
     * Create the http object and set the url
     *
     * @param string $path
     *
     * @return Zend_Http_Client
     */
    private function getHttpClient($path) {

        $http = ZfExtended_Factory::get(Zend_Http_Client::class);
        $http->setUri($this->serviceUrl . $path);
        $http->setConfig(['timeout' => self::REQUEST_TIMEOUT_SECONDS]);

        // Return http client with pre-configured request uri
        return $http;
    }
    
    /**
     * @param Zend_Http_Response $response
     * @return mixed
     */
    private function processResponse(Zend_Http_Response $response){
        return json_decode(trim($response->getBody()));
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

        return self::$languages['languageTool'] = $this->processResponse($response);
    }

    /**
     * Pick contents of docs/LanguageTool.md and overwrite the list of supported languages there.
     * This method can be call from anywhere using the following:
     *
     * editor_Plugins_SpellCheck_Init::createService('languagetool')
     *      ->getAdapter()
     *      ->renderSupportedLanguagesDoc();
     *
     * @return array
     */
    public function renderSupportedLanguagesDoc()
    {
        // Get all translate5 languages
        $langA = ZfExtended_Factory
            ::get(editor_Models_Languages::class)
            ->loadAllKeyValueCustom('rfc5646', 'langName');

        // Sort by rfc
        ksort($langA);

        // Array of supported languages
        $list = [];

        // Foreach rfc-code
        foreach ($langA as $rfc => $name) {

            // If supported
            if ($supported = $this->getSupportedLanguage($rfc)) {

                // Collect the info for rendering the list further
                $list []= [$name, $rfc, $supported->longCode];
            }
        }

        // Get md file path
        $file = '../docs/LanguageTool.md';

        // Here is the line that will be used to split md file contents into
        // two parts - any contents BEFORE supported languages list, and supported languages list itself
        $line = '| :-------------- |:--------------- | :----------------';

        // Get the first part, including the split-line
        $text = explode($line, file_get_contents($file))[0] . $line . "\r\n";

        // Append lines
        foreach ($list as $item) {
            $text .= '| ' . join(' | ', $item) . "\r\n";
        }

        // Overwrite md file
        file_put_contents($file, $text);

        // Return list of supported languages
        return $list;
    }

    /**
     * Get matches from LanguageTool.
     * @param string $text
     * @param string $language
     * @return object
     */
    public function getMatches($text, $language){

        // Get client
        $http = $this->getHttpClient(self::PATH_MATCHES);

        // Set headers
        $http->setHeaders('Content-Type: application/json');
        $http->setHeaders('Accept: application/json');

        // Set params
        $http->setParameterPost('text', $text);
        $http->setParameterPost('language', $language);

        // Reset $this->lastStatus
        $this->lastStatus = false;

        // Try to
        try {

            // Extra data to be passed to exception
            $extraData = [
                'httpMethod' => self::METHOD_MATCHES,
                'languageToolUrl' => $http->getUri(true),
            ];

            // Make request and get response
            $response = $http->request(self::METHOD_MATCHES);

            // Get status
            $this->lastStatus = $response->getStatus();

            // Return processed response
            return $this->processResponse($response);

        // Catch timeout
        } catch (ZfExtended_Zendoverwrites_Http_Exception_TimeOut $httpException) {
            throw new TimeOutException('E1468', $extraData, $httpException);

        // Catch spot down
        } catch (ZfExtended_Zendoverwrites_Http_Exception_Down $httpException) {
            throw new DownException('E1468', $extraData, $httpException);

        // Catch no response
        } catch (ZfExtended_Zendoverwrites_Http_Exception_NoResponse $httpException) {
            throw new RequestException('E1478', $extraData, $httpException);

        // Others
        } catch (Exception $httpException) {
            throw new RequestException('E1479', $extraData, $httpException);
        }

        // If response http status is not between 200 and 300
        if (!$this->wasSuccessfull()) {

            // Throw malfunction exception
            throw new MalfunctionException('E1477', [
                'httpStatus' => $this->getLastStatus(),
                'languageToolUrl' => $http->getUri(true),
                'plainServerResponse' => print_r($response->getBody(), true),
                'requestedData' => compact('text', 'language'),
            ]);
        }
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
        $languagesModel = ZfExtended_Factory::get(editor_Models_Languages::class);

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
     * @param int $targetLangId
     * @return string|false
     */
    public function getSpellCheckLangByTaskTargetLangId(int $targetLangId) {
        // If self::$languages['argByLangId'] is non-null this means
        // that we've already checked whether current task's target language is
        // supported by LanguageTool, and this, in its turn, means that this variable
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
     * Returns true if the last request HTTP status was 2**
     *
     * @return boolean
     */
    public function wasSuccessfull() {

        // Get last status
        $stat = $this->getLastStatus();

        // Return whether it's 2**
        return $stat >= 200 && $stat < 300;
    }

    /**
     * Returns the HTTP Status of the last request
     *
     * @return integer
     */
    public function getLastStatus() {
        return (int) $this->lastStatus;
    }
}
