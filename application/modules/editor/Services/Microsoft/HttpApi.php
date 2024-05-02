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

class editor_Services_Microsoft_HttpApi extends editor_Services_Connector_HttpApiAbstract
{
    public function __construct(editor_Services_Microsoft_Resource $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Search the api for given source/target language by domainCode
     *
     * @return boolean
     * @throws Zend_Http_Client_Exception
     * @throws editor_Services_Exceptions_InvalidResponse
     */
    public function search(mixed $text, string $sourceLang, string $targetLang, bool $useDictionary = false): bool
    {
        $path = $useDictionary ? '/dictionary/lookup' : '/translate';
        $this->getHttp('POST', $path);

        if (! is_array($text)) {
            $text = [$text];
        }
        $requestBody = [];
        foreach ($text as $t) {
            $requestBody[] = [
                'Text' => $t,
            ];
        }

        $this->http->setRawData(json_encode($requestBody));

        $this->http->setParameterGet([
            'from' => $sourceLang,
            'to' => $targetLang,
        ]);

        return $this->processResponse($this->http->request());
    }

    /**
     * Check if it is valid dictionary lookup for the given language combination.
     * The microsoft translator supports only from en or to en directory lookup.
     * More info: https://docs.microsoft.com/en-us/azure/cognitive-services/Translator/language-support
     * @return boolean
     */
    public function isValidDictionaryLookup(string $sourceLang, string $targetLang)
    {
        $languages = $this->getLanguages(editor_Services_Microsoft_LanguageScope::DICTIONARY);
        if (isset($languages[$sourceLang]) && in_array($targetLang, $languages[$sourceLang])) {
            return true;
        }

        return false;
    }

    /**
     * Check the api status
     * @return boolean
     */
    public function getStatus()
    {
        //TODO does that produce costs? There is no other way to check the API authentication (the languages call does not check authentication)
        $this->getHttp('POST', '/dictionary/lookup');
        $this->http->setConfig([
            'timeout' => 5,
        ]);
        $this->http->setRawData(json_encode([[
            'Text' => '',
        ]]));
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
    protected function getHttp($method, $endpointPath = '')
    {
        parent::getHttp($method, '/' . ltrim($endpointPath, '/'));
        $this->http->setParameterGet('api-version', '3.0');
        $this->http->setConfig([
            'timeout' => 30,
        ]);
        $this->http->setHeaders('Content-type', 'application/json');
        $this->http->setHeaders('Ocp-Apim-Subscription-Key', $this->resource->getAuthenticationKey());
        $location = $this->resource->getLocation();
        if (! empty($location)) {
            $this->http->setHeaders('Ocp-Apim-Subscription-Region', $location);
        }
        $this->http->setHeaders('X-ClientTraceId', ZfExtended_Utils::uuid());

        return $this->http;
    }

    /**
     * Gets from API the set of languages currently supported for translation
     */
    public function getLanguages(string $scope = editor_Services_Microsoft_LanguageScope::TRANSLATION): mixed
    {
        $memCache = Zend_Registry::get('cache');
        $result = $memCache->load('editor_Services_Microsoft_HttpApi_getLanguages');
        if ($result !== false) {
            // INFO: currently only one scope is need, so we do not make it more complex
            return $result[$scope] ?? null;
        }

        $this->getHttp('GET', '/languages');
        // currently we will cache only the required scopes to save memory
        $this->http->setParameterGet('scope', implode(',', [
            editor_Services_Microsoft_LanguageScope::TRANSLATION, editor_Services_Microsoft_LanguageScope::DICTIONARY,
        ]));

        // to return results as array
        $this->setAssociativeResult(true);

        if ($this->processResponse($this->http->request())) {
            $result = $this->sanitizeLanguages($this->result);
            if ($memCache->save($result, 'editor_Services_Microsoft_HttpApi_getLanguages')) {
                return $this->getLanguages($scope);
            }
        }

        return null;
    }

    /**
     * Filter out not needed data from the langauges result data.
     */
    private function sanitizeLanguages(mixed $result): array
    {
        if (! empty($result[editor_Services_Microsoft_LanguageScope::TRANSLATION])) {
            $result[editor_Services_Microsoft_LanguageScope::TRANSLATION] = array_keys(
                $result[editor_Services_Microsoft_LanguageScope::TRANSLATION]
            );
        }

        if (! empty($result[editor_Services_Microsoft_LanguageScope::DICTIONARY])) {
            foreach ($result[editor_Services_Microsoft_LanguageScope::DICTIONARY] as $key => $item) {
                $key = strtolower($key);
                $translations = [];
                if (isset($item['translations']) && is_array($item['translations'])) {
                    foreach ($item['translations'] as $translation) {
                        $translations[] = strtolower($translation['code']);
                    }
                }
                $result[editor_Services_Microsoft_LanguageScope::DICTIONARY][$key] = $translations;
            }
        }

        return $result;
    }
}
