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

use MittagQI\Translate5\Service\T5Memory;

/**
 * OpenTM2 HTTP Connection API
 */
class editor_Services_OpenTM2_HttpApi extends editor_Services_Connector_HttpApiAbstract
{
    private const DATE_FORMAT = 'Ymd\THis\Z';

    const MAX_STR_LENGTH = 2048;

    /**
     * @var editor_Models_LanguageResources_LanguageResource
     */
    protected $languageResource;

    /**
     * @var editor_Services_OpenTM2_FixLanguageCodes
     */
    protected $fixLanguages;

    /**
     * @var bool true if the used resource is T5Memory, false for OpenTM2
     */
    protected bool $isT5Memory = false;

    public function __construct()
    {
        $this->fixLanguages = ZfExtended_Factory::get('editor_Services_OpenTM2_FixLanguageCodes');
    }

    /**
     * This method creates a new memory.
     * @throws Zend_Exception
     */
    public function createEmptyMemory($memory, $sourceLanguage): ?string
    {
        $data = new stdClass();
        $data->name = $this->addTmPrefix($memory);
        $data->sourceLang = $this->fixLanguages->key($sourceLanguage);

        $http = $this->getHttp('POST');
        $http->setRawData($this->jsonEncode($data), 'application/json; charset=utf-8');

        if ($this->processResponse($http->request())) {
            return $data->name;
        }

        return null;
    }

    /**
     * This method creates a new memory with TM file
     * @throws Zend_Exception
     */
    public function createMemory($memory, $sourceLanguage, $tmData): ?string
    {
        $data = new stdClass();
        $data->name = $this->addTmPrefix($memory);
        $data->sourceLang = $this->fixLanguages->key($sourceLanguage);
        $data->data = base64_encode($tmData);

        $http = $this->getHttp('POST');
        $http->setConfig(['timeout' => $this->createTimeout(1200)]);
        $http->setRawData($this->jsonEncode($data), 'application/json; charset=utf-8');

        if ($this->processResponse($http->request())) {
            return $data->name;
        }

        return null;
    }

    /**
     * This method imports a memory from a TMX file.
     */
    public function importMemory($tmData, string $tmName)
    {
        //In:{ "Method":"import", "Memory":"MyTestMemory", "TMXFile":"C:/FileArea/MyTstMemory.TMX" }
        //Out: { "ReturnValue":0, "ErrorMsg":"" }

        $data = new stdClass();

        // TODO T5MEMORY: remove when OpenTM2 is out of production
        if ($this->isOpenTM2()) {
            $tmData = $this->fixLanguages->tmxOnUpload($tmData);
            /* @var $tmxRepairer editor_Services_OpenTM2_FixImportParser */
            $tmxRepairer = ZfExtended_Factory::get('editor_Services_OpenTM2_FixImportParser');
            $tmData = $tmxRepairer->convert($tmData);
        }
        $data->tmxData = base64_encode($tmData);

        $http = $this->getHttpWithMemory('POST', $tmName, '/import');
        $http->setConfig(['timeout' => $this->createTimeout(1200)]);
        $http->setRawData($this->jsonEncode($data), 'application/json; charset=utf-8');

        return $this->processResponse($http->request());
    }

    /**
     * This method clones memory
     *
     * @throws Zend_Exception
     */
    public function cloneMemory(string $targetMemory, string $tmName): bool
    {
        $data = [];
        $data['newName'] = $this->addTmPrefix($targetMemory);

        $http = $this->getHttpWithMemory('POST', $tmName, 'clone');
        $http->setConfig(['timeout' => $this->createTimeout(1200)]);
        $http->setRawData($this->jsonEncode($data), 'application/json; charset=utf-8');

        return $this->processResponse($http->request());
    }

    /**
     * prepares a Zend_Http_Client, prefilled with the configured URL + the given REST URL Parts (ID + verbs)
     * @param string $httpMethod
     * @param string $urlSuffix
     * @return Zend_Http_Client
     */
    protected function getHttp($method, $urlSuffix = '')
    {
        $url = rtrim($this->resource->getUrl(), '/');
        $urlSuffix = ltrim($urlSuffix, '/');
        $this->http = ZfExtended_Factory::get('Zend_Http_Client');
        /* @var $http Zend_Http_Client */
        $this->http->setUri($url . '/' . $urlSuffix);
        $this->http->setMethod($method);
        $this->httpMethod = $method;
        $this->http->setHeaders('Accept-charset', 'UTF-8');
        $this->http->setHeaders('Accept', 'application/json; charset=utf-8');
        $this->http->setConfig(['timeout' => $this->createTimeout(30)]);

        return $this->http;
    }

    /**
     * prepares a Zend_Http_Client, prefilled with the configured URL + the Memory Name + additional URL parts
     *
     * @return Zend_Http_Client
     */
    protected function getHttpWithMemory(string $method, string $tmName, string $urlSuffix = ''): Zend_Http_Client
    {
        $url = $this->addTmPrefix(urlencode($tmName)) . '/' . ltrim($urlSuffix, '/');

        return $this->getHttp($method, $url);
    }

    /**
     * adds the internal TM prefix to the given TM name
     * @param string $tmName
     * @return string
     * @throws Zend_Exception
     */
    protected function addTmPrefix(string $tmName): string
    {
        //CRUCIAL: the prefix (if any) must be added on usage, and may not be stored in the specificName
        // that is relevant for security on a multi hosting environment
        $prefix = Zend_Registry::get('config')->runtimeOptions->LanguageResources->opentm2->tmprefix;
        if (!empty($prefix) && !str_starts_with($tmName, $prefix.'-')) {
            $tmName = $prefix . '-' . $tmName;
        }
        return $tmName;
    }

    /**
     * retrieves the TM as TM file
     * @param string|array $mime
     * @return boolean
     */
    public function get($mime, string $tmName)
    {
        if (is_array($mime)) {
            $mime = implode(',', $mime);
        }
        $http = $this->getHttpWithMemory('GET', $tmName);
        $http->setConfig(['timeout' => $this->createTimeout(1200)]);
        $http->setHeaders('Accept', $mime);
        $response = $http->request();
        if ($response->getStatus() === 200) {
            $this->result = $response->getBody();
            if ($mime == "application/xml") {
                $targetLang = $this->languageResource->getTargetLangCode();
                $sourceLang = $this->languageResource->getSourceLangCode();
                $this->result = $this->fixInvalidOpenTM2XML($this->fixLanguages->tmxOnDownload($sourceLang, $targetLang, $this->result));
            }
            return true;
        }

        return $this->processResponse($response);
    }

    /**
     * repairs the TMX from OpenTM2 regarding encoded entities and newlines
     * @param string $tmxData
     * @return string
     * @throws Zend_Exception
     */
    protected function fixInvalidOpenTM2XML(string $tmxData): string
    {
        if ($this->isT5Memory) {
            return $tmxData;
        }

        /** @var editor_Services_OpenTM2_FixExport $fix */
        $fix = ZfExtended_Factory::get('editor_Services_OpenTM2_FixExport');
        $result = $fix->convert($tmxData);
        if ($fix->getChangeCount() > 0 || $fix->getNewLineCount() > 0) {
            $logger = Zend_Registry::get('logger');
            $logger->warn('E1554', 'TMX Export: Entities in {changeCount} text parts repaired (see raw php error log), {newLineCount} new line tags restored.', [
                'languageResource' => $this->languageResource,
                'changeCount' => $fix->getChangeCount(),
                'newLineCount' => $fix->getNewLineCount(),
            ]);
        }
        return $result;
    }

    /**
     * checks the status of a language resource (if set), or just of the server (if no concrete language resource is given)
     * @return boolean
     */
    public function status(?string $tmName): bool
    {
        if (empty($this->languageResource) || null === $tmName) {
            $this->getHttp('GET', '/');
        } else {
            $this->getHttpWithMemory('GET', $tmName, '/status');
        }

        $this->http->setConfig(['timeout' => $this->createTimeout(3)]);

        try {
            //OpenTM2 returns invalid JSON on calling "/", so we have to fix this here by catching the invalid JSON Exception.
            // Also this would send a list of all TMs to all error receivers, which must also prevented
            return $this->processResponse($this->http->request());
        } catch (editor_Services_Exceptions_InvalidResponse $e) {
            if ((empty($this->languageResource) || null === $tmName) && $e->getErrorCode() == 'E1315') {
                return true;
            }
            throw $e;
        }
    }

    /**
     * This method deletes a memory.
     */
    public function delete(string $tmName): bool
    {
        $this->getHttpWithMemory('DELETE', $tmName);

        return $this->processResponse($this->http->request());
    }

    /**
     * searches for matches in the TM
     */
    public function lookup(editor_Models_Segment $segment, string $queryString, string $filename, string $tmName): bool
    {
        $json = new stdClass();

        $json->sourceLang = $this->fixLanguages->key($this->languageResource->getSourceLangCode());
        $json->targetLang = $this->fixLanguages->key($this->languageResource->getTargetLangCode());
        
        if ($this->isToLong($queryString)) {
            $this->result = json_decode('{"ReturnValue":0,"ErrorMsg":"","NumOfFoundProposals":0}');

            return true;
        }

//         $queryString = 'Start the <bpt i="1" mid="1" /><ph mid="2"/><ex mid="3" i="1"/> and wait until the LED is continuous green.';
//         $queryString = 'Start the <it type="struct"/> and wait until the LED is continuous green.';
//         $queryString = 'Start the <x mid="2"/> and wait until the LED is continuous green.';
//         $queryString = 'Start the <bx mid="1" rid="1"/><x mid="2"/><ex mid="3" rid="1"/> and wait until the LED is continuous green.';

        $json->source = $queryString;
        // In general OpenTM2 can deal with whole paths, not only with filenames.
        // But we hold the filepaths in the FileTree JSON, so this value is not easily accessible,
        // so we take only the single filename at the moment
        $json->documentName = $filename;

        $json->markupTable = 'OTMXUXLF'; //NEEDED otherwise t5memory crashes
        $json->context = $segment->getMid(); // here MID (context was designed for dialog keys/numbers on translateable strings software)

        $http = $this->getHttpWithMemory('POST', $tmName, 'fuzzysearch');

        // TODO T5MEMORY: remove when OpenTM2 is out of production
        if ($this->isOpenTM2() && strtolower($json->targetLang) === 'en-gb') {
            // TODO REMOVE THIS WHOLE IF AFTER ABOLISHING OPENTM2
            //between 06.2022 v5.7.4 and 9.2022 v5.7.10 all en-GB segments were stored as en-UK into OpenTM2
            // with v5.7.10 this was fixed, but now all en-UK results must be fetched separately
            // and merged into the result
            // - this is only relevant for targetLanguages = en-GB, source language is not compared on search
            //   on export this will lead to empty xml:lang fields, which is fixed in FixLanguagesCodes
            // - only on saving en-UK as source, the source lang is empty string on export and ?? in answer,
            //   the export is fixed in FixLanguagesCodes, the latter one does not hurt

            //en-UK request first
            $jsonEnUk = clone $json;
            $jsonEnUk->targetLang = 'en-UK';
            $http->setRawData($this->jsonEncode($jsonEnUk), 'application/json; charset=utf-8');
            $resultsUK = [];
            $resultUK = $this->processResponse($http->request());
            if ($resultUK) {
                $resultsUK = clone $this->result;
                if (!empty($resultsUK->results)) {
                    foreach ($resultsUK->results as $oneResult) {
                        $oneResult->targetLang = 'en-GB'; //en-UK is stored as ?? and must be changed
                    }
                }
            }

            //en-GB request
            $http->setRawData($this->jsonEncode($json), 'application/json; charset=utf-8');
            $resultGB = $this->processResponse($http->request());

            if ($resultUK && $resultsUK->NumOfFoundProposals > 0) {
                //if no GB results found or there was an error, we use just the UK entries
                if (!$resultGB || $this->result->NumOfFoundProposals === 0) {
                    $this->result = $resultsUK;
                } //merge the results
                else {
                    $this->result->NumOfFoundProposals += $resultsUK->NumOfFoundProposals;
                    $this->result->results = array_merge($this->result->results, $resultsUK->results);
                }
            }

            return $resultGB || $resultUK;
        }

        $http->setRawData($this->jsonEncode($json), 'application/json; charset=utf-8');
        return $this->processResponse($http->request());
    }

    /**
     * This method searches the given search string in the proposals contained in a memory (concordance search).
     * The function returns one proposal per request.
     * The caller has to provide the search position returned by a previous call or an empty search
     * position to start the search at the begin of the memory.
     * Note: Provide the returned search position NewSearchPosition as SearchPosition on
     * subsequenet calls to do a sequential search of the memory.
     */
    public function search(
        string $queryString,
        string $tmName,
        string $field,
        int $searchPosition = null,
        int $numResults = 20
    ): bool {
        if ($this->isToLong($queryString)) {
            $this->result = json_decode('{"results":[]}');

            return true;
        }

        $data = new stdClass();
        $data->searchString = $queryString;
        $data->searchType = $field;
        $data->searchPosition = $searchPosition;
        $data->numResults = $numResults;
        $data->msSearchAfterNumResults = 250;
        $http = $this->getHttpWithMemory('POST', $tmName, 'concordancesearch');
        $http->setRawData($this->jsonEncode($data), 'application/json; charset=utf-8');

        return $this->processResponse($http->request());
    }

    /**
     * This method updates (or adds) a memory proposal in the memory.
     * Note: This method updates an existing proposal when a proposal with the same key information
     * (source text, language, segment number, and document name) exists.
     *
     * @throws JsonException
     * @throws Zend_Http_Client_Exception
     */
    public function update(
        string $source,
        string $target,
        editor_Models_Segment $segment,
        string $filename,
        string $tmName,
        bool $save2disk = true,
        bool $useSegmentTimestamp = false
    ): bool {
        $this->error = null;

        $http = $this->getHttpWithMemory('POST', $tmName, 'entry');
        $json = $this->getUpdateJson(__FUNCTION__, $source, $target);

        if (null !== $this->error) {
            return false;
        }

        $timestamp = $useSegmentTimestamp
            ? (new DateTimeImmutable($segment->getTimestamp()))->format(self::DATE_FORMAT)
            : $this->nowDate();

        $json->documentName = $filename; // 101 doc match
        $json->author = $segment->getUserName();
        $json->timeStamp = $timestamp;
        $json->context = $segment->getMid(); //INFO: this is segment stuff
        // t5memory does not understand boolean parameters, so we have to convert them to 0/1
        $json->save2disk = $save2disk ? '1' : '0';

        $http->setRawData($this->jsonEncode($json), 'application/json; charset=utf-8');

        return $this->processResponse($http->request());
    }

    /***
     * Update text values ($source/$target) to the current tm memory
     * @throws Zend_Http_Client_Exception
     */
    public function updateText(string $source, string $target, string $tmName): bool
    {
        $this->error = null;

        $http = $this->getHttpWithMemory('POST', $tmName, 'entry');
        $json = $this->getUpdateJson(__FUNCTION__, $source, $target);

        if (null !== $this->error) {
            return false;
        }

        $json->documentName = 'source';
        $json->author = ZfExtended_Authentication::getInstance()->getUser()->getUserName();
        $json->context = '';
        $json->addInfo = $json->documentName;

        $http->setRawData($this->jsonEncode($json), 'application/json; charset=utf-8');

        return $this->processResponse($http->request());
    }

    public function reorganizeTm(string $tmName): bool
    {
        $http = $this->getHttpWithMemory('GET', $tmName, 'reorganize');
        if ($this->processResponse($http->request())) {
            // since Version 0.4.48 we have the number of invalid segments in the result
            // {
            //     "axelloc-ID57-T5Memory 0448 TEST": "reorganized",
            //     "time": "1 sec",
            //     "reorganizedSegmentCount": "2277", -> since 0.4.48
            //     "invalidSegmentCount": "0" -> since 0.4.48
            // }
            if (property_exists($this->result, 'invalidSegmentCount')) {
                $invalid = (int)$this->result->invalidSegmentCount;

                if ($invalid > 0) {
                    $overall = (int)$this->result->reorganizedSegmentCount;
                    $logger = Zend_Registry::get('logger');
                    $logger->warn('E1555', 'Errors during Translation Memory reorganization: {invalid} of {overall} segments invalid in "{tmname}".', [
                        'languageResource' => $this->languageResource,
                        'invalid' => $invalid,
                        'overall' => $overall,
                        'tmname' => $this->languageResource->getName(),
                    ]);
                }
            }
            return true;
        }
        return false;
    }

    public function resources(): bool
    {
        $http = $this->getHttp('GET', '/resources');
        $http->setConfig(['timeout' => $this->createTimeout(3)]);
        $http->setUri(rtrim($this->resource->getUrl(), '/') . '_service/resources');

        return $this->processResponse($http->request());
    }

    /***
     * Get the default update memory json
     */
    private function getUpdateJson(string $function, string $source, string $target): stdClass
    {

        if ($this->isToLong($source) || $this->isToLong($target)) {
            $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
            $this->error = new stdClass();
            $this->error->method = $this->httpMethod;
            $this->error->url = $this->http->getUri(true);
            $this->error->error =
                $translate->_(
                    'Das Segment konnte nur in der Aufgabe, nicht aber ins TM gespeichert werden. Segmente länger als 2048 Bytes sind nicht im TM speicherbar.'
                );
            return new stdClass();
        }

        $json = $this->json($function);
        $json->source = $source;
        $json->target = $target;
        $json->type = "Manual";
        $json->markupTable = "OTMXUXLF"; //fixed markup table for our XLIFF subset
        $json->sourceLang = $this->fixLanguages->key($this->languageResource->getSourceLangCode());
        $json->targetLang = $this->fixLanguages->key($this->languageResource->getTargetLangCode());
        $json->timeStamp = $this->nowDate();

        return $json;
    }

    /**
     * Creates a stdClass Object which is later converted to JSON for communication
     * @param string $method a method is always needed in the request JSON
     * @param string $memory optional, if given this is added as memory to the JSON
     * @return stdClass;
     */
    protected function json(string $method)
    {
        $result = new stdClass();
        $result->Method = $method;

        return $result;
    }

    /**
     * parses and processes the response of OpenTM2, and handles the errors
     */
    protected function processResponse(Zend_Http_Response $response): bool
    {
        parent::processResponse($response);

        // Normally the ReturnValue is 0 if there is no error.
        // Also 10010 and 10011 are valid ReturnValue values
        $returnValueError = !empty($this->result->ReturnValue)
            && !in_array((int)$this->result->ReturnValue, [10010, 10011, 0]);

        //For some errors this is not true, then only a ErrorMsg is set, but return value is 0,
        if ($returnValueError || !empty($this->result->ErrorMsg)) {
            $this->error = new stdClass();
            $this->error->method = $this->httpMethod;
            $this->error->url = $this->http->getUri(true);
            $this->error->code = 'Error Nr. ' . ($this->result->ReturnValue ?? '');
            $this->error->error = $this->result->ErrorMsg;
        }

        return empty($this->error);
    }

    /**
     * returns the current time stamp in the expected format for OpenTM2
     */
    protected function nowDate()
    {
        return gmdate(self::DATE_FORMAT);
    }

    /**
     * Sets internally the used language resource (and service resource)
     * @param editor_Models_LanguageResources_LanguageResource $languageResource
     */
    public function setLanguageResource(editor_Models_LanguageResources_LanguageResource $languageResource)
    {
        $this->setResource($languageResource->getResource());
        $this->languageResource = $languageResource;
    }

    public function getLanguageResource()
    {
        return $this->languageResource;
    }

    public function setResource(editor_Models_LanguageResources_Resource $resource)
    {
        parent::setResource($resource);
        $this->isT5Memory = !str_contains($resource->getUrl(), '/otmmemoryservice');
        $this->fixLanguages->setDisabled($this->isT5Memory);
    }

    /**
     * returns true if the target system is OpenTM2, false if isT5Memory
     * @return bool
     * @deprecated check all usages and remove them if OpenTM2 is replaced with t5memory
     * TODO T5MEMORY: remove when OpenTM2 is out of production
     */
    public function isOpenTM2(): bool
    {
        return !$this->isT5Memory;
    }

    /**
     * returns true if string is to long for OpenTM2
     * According some research, it seems that the magic border to crash OpenTM2 is on 2048 characters, but:
     * 1,2 and 3 Byte long characters are counting as 1 character, while 4Byte Characters are counting as 2 Characters.
     * There fore the below special count is needed.
     * @param string $string
     * @return bool
     */
    protected function isToLong(string $string): bool
    {
        $realCharLength = mb_strlen($string);
        if ($realCharLength < (self::MAX_STR_LENGTH / 2)) {
            // we do not have to make the regex stuff,
            // if the real char length is shorter as half of the max count
            return false;
        }
        //since for OpenTM2 4Byte characters seems to count 2 characters,
        // we have to count and add them to get the real count
        $smileyCount = preg_match_all('/[\x{10000}-\x{10FFFF}]/mu', $string);

        return ($realCharLength + $smileyCount) > self::MAX_STR_LENGTH;
    }

    /**
     * @param array|stdClass $data
     *
     * @return string
     *
     * @throws JsonException
     */
    private function jsonEncode($data): string
    {
        $flags = JSON_THROW_ON_ERROR;

        // TODO T5MEMORY: remove when OpenTM2 is out of production
        if (!$this->isOpenTM2()) {
            $flags = JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT;
        }

        // Due to error in proxygen library in t5memory json closing brace should follow a new line symbol (should be "\n}" instead of "}"),
        // otherwise such a json won't be parsed correctly
        return json_encode($data, $flags);
    }

    /**
     * Generates the Timeouts to use for a request
     * TODO T5MEMORY: remove when OpenTM2 is out of production
     * @param int $seconds
     * @return int
     */
    private function createTimeout(int $seconds): int
    {
        if ($this->isT5Memory) {
            return T5Memory::REQUEST_TIMEOUT + $seconds;
        } else {
            return $seconds;
        }
    }
}
