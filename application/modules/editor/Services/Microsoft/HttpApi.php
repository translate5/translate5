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

class editor_Services_Microsoft_HttpApi extends editor_Services_Connector_HttpApiAbstract {
    public function __construct(editor_Services_Microsoft_Resource $resource) {
        $this->resource = $resource;
    }
    
    /**
     * Search the api for given source/target language by domainCode
     *
     * @param string $text
     * @param string $sourceLang
     * @param string $targetLang
     * @return boolean
     */
    public function search($text, $sourceLang, $targetLang, $useDictionary = false) {

        $useDictionary = $useDictionary && $this->isValidDictionaryLookup($sourceLang, $targetLang);
        
        $path = $useDictionary ? '/dictionary/lookup' : '/translate';
        $this->getHttp('POST', $path);

        if(!is_array($text)){
            $text = [$text];
        }
        $requestBody  = [];
        foreach ($text as $t) {
            $requestBody[] = ['Text' => $t];
        }

        $this->http->setRawData(json_encode($requestBody));
        
        $this->http->setParameterGet([
            'from' => $sourceLang,
            'to' => $targetLang,
        ]);

        return $this->processResponse($this->http->request());
    }

    /***
     * Check if it is valid direcory lookup for the given language combination.
     * The microsoft translator supports only from en or to en directory lookup.
     * More info: https://docs.microsoft.com/en-us/azure/cognitive-services/Translator/language-support
     * @param string $sourceLang
     * @param string $targetLang
     * @return boolean
     */
    protected function isValidDictionaryLookup($sourceLang,$targetLang){
        //FIXME compare against dictionary language list??? not cached, must be loaded again...
        return (mb_substr(strtolower($sourceLang), 0,2)=='en' || mb_substr(strtolower($targetLang), 0,2)=='en');
    }

    /**
     * Check the api status
     * @return boolean
     */
    public function getStatus(){
        //TODO does that produce costs? There is no other way to check the API authentication (the languages call does not check authentication)
        $this->getHttp('POST', '/dictionary/lookup');
        $this->http->setConfig(['timeout'=>5]);
        $this->http->setRawData(json_encode([['Text' => '']]));
        $this->http->setParameterGet([
            'from' => 'de',
            'to' => 'en',
        ]);
        
        return $this->processResponse($this->http->request());
    }

    /**
     * prepares a Zend_Http_Client, prefilled with the configured URL + the given REST URL Parts
     * @param string $method
     * @param string $endpointPath
     * @return Zend_Http_Client
     */
    protected function getHttp($method, $endpointPath = '') {
        parent::getHttp($method, '/'.ltrim($endpointPath, '/'));
        $this->http->setParameterGet('api-version', '3.0');
        $this->http->setConfig(['timeout'=>30]);
        $this->http->setHeaders('Content-type', 'application/json');
        $this->http->setHeaders('Ocp-Apim-Subscription-Key', $this->resource->getAuthenticationKey());
        $location = $this->resource->getLocation();
        if(!empty($location)) {
            $this->http->setHeaders('Ocp-Apim-Subscription-Region', $location);
        }
        $this->http->setHeaders('X-ClientTraceId', ZfExtended_Utils::uuid());
        return $this->http;
    }

    /***
     * Gets from API the set of languages currently supported for translation, return bool if the request was successfull
     * @return array|null
     */
    public function getLanguages(): ?array {
        $this->getHttp('GET', '/languages');
        $this->http->setParameterGet('scope', 'translation');
        if($this->processResponse($this->http->request())) {
            // we consider only the translation languages
            return array_keys(get_object_vars($this->result->translation));
        }
        return null;
    }
}
