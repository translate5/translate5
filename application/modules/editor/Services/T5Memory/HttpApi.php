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
use MittagQI\Translate5\T5Memory\DTO\SearchDTO;
use MittagQI\Translate5\T5Memory\DTO\UpdateOptions;
use MittagQI\Translate5\T5Memory\PersistenceService;

/**
 * T5Memory HTTP Connection API
 */
class editor_Services_T5Memory_HttpApi extends editor_Services_Connector_HttpApiAbstract
{
    private const DATE_FORMAT = 'Ymd\THis\Z';

    private const MARKUP_TABLE = 'OTMXUXLF';

    private const REQUEST_ENCTYPE = 'application/json; charset=utf-8';

    public const MAX_STR_LENGTH = 2048;

    /**
     * @var editor_Models_LanguageResources_LanguageResource
     */
    protected $languageResource;

    private PersistenceService $persistenceService;

    public function __construct()
    {
        $this->persistenceService = new PersistenceService(Zend_Registry::get('config'));
    }

    /**
     * This method creates a new memory.
     * @throws Zend_Exception
     */
    public function createEmptyMemory($memory, $sourceLanguage): ?string
    {
        $data = new stdClass();
        $data->name = $this->persistenceService->addTmPrefix($memory);
        $data->sourceLang = $sourceLanguage;

        $http = $this->getHttp('POST');
        $http->setRawData($this->jsonEncode($data), self::REQUEST_ENCTYPE);

        if ($this->processResponse($http->request())) {
            return $data->name;
        }

        return null;
    }

    /**
     * prepares a Zend_Http_Client, prefilled with the configured URL + the given REST URL Parts (ID + verbs)
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
        $this->http->setHeaders('Accept', self::REQUEST_ENCTYPE);
        $this->http->setConfig([
            'timeout' => $this->createTimeout(30),
        ]);

        return $this->http;
    }

    /**
     * prepares a Zend_Http_Client, prefilled with the configured URL + the Memory Name + additional URL parts
     */
    protected function getHttpWithMemory(string $method, string $tmName, string $urlSuffix = ''): Zend_Http_Client
    {
        $url = urlencode($this->persistenceService->addTmPrefix($tmName)) . '/' . ltrim($urlSuffix, '/');

        return $this->getHttp($method, $url);
    }

    /**
     * checks the status of a language resource (if set), or just of the server (if no concrete language resource is
     * given)
     * @return boolean
     */
    public function status(?string $tmName): bool
    {
        if (empty($this->languageResource) || null === $tmName) {
            $this->getHttp('GET', '/');
        } else {
            $this->getHttpWithMemory('GET', $tmName, '/status');
        }

        $this->http->setConfig([
            'timeout' => isset($tmName) ? $this->createTimeout(3) : 20,
        ]);

        try {
            //T5Memory returns invalid JSON on calling "/", so we have to fix this here by catching the invalid JSON Exception.
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
    public function lookup(string $queryString, string $context, string $filename, string $tmName): bool
    {
        $json = new stdClass();

        $json->sourceLang = $this->languageResource->getSourceLangCode();
        $json->targetLang = $this->languageResource->getTargetLangCode();
        $json->markupTable = self::MARKUP_TABLE;

        if ($this->isToLong($queryString)) {
            $this->result = json_decode('{"ReturnValue":0,"ErrorMsg":"","NumOfFoundProposals":0}');

            return true;
        }

        $json->source = $queryString;
        // In general T5Memory can deal with whole paths, not only with filenames.
        // But we hold the filepaths in the FileTree JSON, so this value is not easily accessible,
        // so we take only the single filename at the moment
        $json->documentName = $filename;

        $json->markupTable = self::MARKUP_TABLE; //NEEDED otherwise t5memory crashes
        $json->context = $context;

        $http = $this->getHttpWithMemory('POST', $tmName, 'fuzzysearch');

        $http->setRawData($this->jsonEncode($json), self::REQUEST_ENCTYPE);

        return $this->processResponse($http->request());
    }

    public $request = null;

    /**
     * This method searches the given search string in the proposals contained in a memory (concordance search).
     * The function returns one proposal per request.
     * The caller has to provide the search position returned by a previous call or an empty search
     * position to start the search at the begin of the memory.
     * Note: Provide the returned search position NewSearchPosition as SearchPosition on
     * subsequenet calls to do a sequential search of the memory.
     */
    public function concordanceSearch(
        string $queryString,
        string $tmName,
        string $field,
        string $searchPosition = null,
        int $numResults = 20,
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
        $http->setRawData($this->jsonEncode($data), self::REQUEST_ENCTYPE);

        $this->request = $this->jsonEncode($data);

        return $this->processResponse($http->request());
    }

    public function search(string $tmName, ?string $searchPosition, ?int $numResults, SearchDTO $searchDTO): bool
    {
        $data = $this->getSearchData($searchDTO, $searchPosition, $numResults);
        $http = $this->getHttpWithMemory('POST', $tmName, '/search');
        $http->setConfig([
            'timeout' => $this->createTimeout(300),
        ]);
        $http->setRawData($this->jsonEncode($data), self::REQUEST_ENCTYPE);

        return $this->processResponse($http->request());
    }

    public function deleteBatch(
        string $tmName,
        SearchDTO $searchDTO,
        bool $saveDifferentTargetsForSameSource,
    ): bool {
        $data = $this->getSearchData($searchDTO);
        $data[UpdateOptions::SAVE_DIFFERENT_TARGETS_FOR_SAME_SOURCE] = $saveDifferentTargetsForSameSource ? '1' : '0';
        $http = $this->getHttpWithMemory('POST', $tmName, '/entriesdelete');
        $http->setRawData($this->jsonEncode($data), self::REQUEST_ENCTYPE);

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
        string $userName,
        string $context,
        string $timestamp,
        string $filename,
        string $tmName,
        bool $saveDifferentTargetsForSameSource,
        bool $save2disk = true,
    ): bool {
        $this->error = null;

        $http = $this->getHttpWithMemory('POST', $tmName, 'entry');
        $json = $this->getUpdateJson(__FUNCTION__, $source, $target);

        if (null !== $this->error) {
            return false;
        }

        $json->documentName = $filename; // 101 doc match
        $json->author = $userName;
        $json->timeStamp = $timestamp;
        $json->context = $context; //INFO: this is segment stuff
        // t5memory does not understand boolean parameters, so we have to convert them to 0/1
        $json->save2disk = $save2disk ? '1' : '0';
        $json->saveDifferentTargetsForSameSource = $saveDifferentTargetsForSameSource ? '1' : '0';

        $http->setRawData($this->jsonEncode($json), self::REQUEST_ENCTYPE);

        return $this->processResponse($http->request());
    }

    /***
     * Update text values ($source/$target) to the current tm memory
     * @throws Zend_Http_Client_Exception
     */
    public function updateText(
        string $source,
        string $target,
        string $tmName,
        bool $saveDifferentTargetsForSameSource,
    ): bool {
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
        $json->saveDifferentTargetsForSameSource = $saveDifferentTargetsForSameSource ? '1' : '0';

        $http->setRawData($this->jsonEncode($json), self::REQUEST_ENCTYPE);

        return $this->processResponse($http->request());
    }

    public function deleteEntry(string $tmName, int $segmentId, int $recordKey, int $targetKey): bool
    {
        $request = [
            'recordKey' => $recordKey,
            'targetKey' => $targetKey,
            'segmentId' => $segmentId,
        ];

        $http = $this->getHttpWithMemory('POST', $tmName, 'entrydelete');
        $http->setRawData($this->jsonEncode($request), self::REQUEST_ENCTYPE);

        return $this->processResponse($http->request());
    }

    public function getEntry(string $tmName, int $recordKey, int $targetKey): bool
    {
        $request = [
            'recordKey' => $recordKey,
            'targetKey' => $targetKey,
        ];

        $http = $this->getHttpWithMemory('POST', $tmName, 'getentry');
        $http->setRawData($this->jsonEncode($request), self::REQUEST_ENCTYPE);

        return $this->processResponse($http->request());
    }

    public function flush(string $tmName): void
    {
        $http = $this->getHttpWithMemory('GET', $tmName, 'flush');
        $http->request();
    }

    public function resources(): bool
    {
        $http = $this->getHttp('GET', '/resources');
        $http->setConfig([
            'timeout' => $this->createTimeout(3),
        ]);
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
        $json->markupTable = self::MARKUP_TABLE; //fixed markup table for our XLIFF subset
        $json->sourceLang = $this->languageResource->getSourceLangCode();
        $json->targetLang = $this->languageResource->getTargetLangCode();
        $json->timeStamp = $this->getNowDate();

        return $json;
    }

    private function getSearchData(SearchDTO $searchDTO, ?string $searchPosition = null, ?int $numResults = null): array
    {
        // Please note that "SENSETIVE" here is a typo in the t5memory API, so please do not change it to "SENSITIVE"
        $caseSensitive = $searchDTO->caseSensitive ? 'CASESENSETIVE' : 'CASEINSENSETIVE';
        $searchOptions = ', ' . $caseSensitive;

        return [
            'source' => $searchDTO->source,
            'sourceSearchMode' => $searchDTO->sourceMode . $searchOptions,
            'target' => $searchDTO->target,
            'targetSearchMode' => $searchDTO->targetMode . $searchOptions,
            'sourceLang' => $searchDTO->sourceLanguage,
            'targetLang' => $searchDTO->targetLanguage,
            'document' => $searchDTO->document,
            'documentSearchMode' => $searchDTO->documentMode . $searchOptions,
            'author' => $searchDTO->author,
            'authorSearchMode' => $searchDTO->authorMode . $searchOptions,
            'addInfo' => $searchDTO->additionalInfo,
            'addInfoSearchMode' => $searchDTO->additionalInfoMode . $searchOptions,
            'context' => $searchDTO->context,
            'contextSearchMode' => $searchDTO->contextMode . $searchOptions,
            'timestampSpanStart' => $this->getDate($searchDTO->creationDateFrom),
            'timestampSpanEnd' => $this->getDate($searchDTO->creationDateTo),
            'onlyCountSegments' => $searchDTO->onlyCount ? '1' : '0',
            'searchPosition' => (string) $searchPosition,
            'numResults' => $numResults,
        ];
    }

    /**
     * Creates a stdClass Object which is later converted to JSON for communication
     * @param string $method a method is always needed in the request JSON
     * @return stdClass;
     */
    protected function json(string $method)
    {
        $result = new stdClass();
        $result->Method = $method;

        return $result;
    }

    /**
     * parses and processes the response of T5Memory, and handles the errors
     */
    protected function processResponse(Zend_Http_Response $response): bool
    {
        parent::processResponse($response);

        // Normally the ReturnValue is 0 if there is no error.
        // Also 10010 and 10011 are valid ReturnValue values
        // TODO remove ReturnValue as it is not supported anymore in T5Memory
        $returnValueError = ! empty($this->result->ReturnValue)
            && ! in_array((int) $this->result->ReturnValue, [10010, 10011, 0]);

        $errorMsg = $this->result->ErrorMsg
            ?? $this->result->importErrorMsg
            ?? $this->result->reorganizeErrorMsg
            ?? null;

        //For some errors this is not true, then only a ErrorMsg is set, but return value is 0,
        if ($returnValueError || ! empty($errorMsg)) {
            $this->error = new stdClass();
            $this->error->method = $this->httpMethod;
            $this->error->url = $this->http->getUri(true);
            $this->error->code = 'Error Nr. ' . ($this->result->ReturnValue ?? '');
            $this->error->error = $errorMsg;
            $this->error->returnValue = (int) ($this->result->ReturnValue ?? 0);
        }

        return empty($this->error);
    }

    /**
     * returns the current time stamp in the expected format for T5Memory
     */
    public function getNowDate(): string
    {
        return gmdate(self::DATE_FORMAT);
    }

    public function getDate(int $timestamp): string
    {
        return gmdate(self::DATE_FORMAT, $timestamp);
    }

    /**
     * Sets internally the used language resource (and service resource)
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

    /**
     * returns true if string is to long for T5Memory
     * According some research, it seems that the magic border to crash T5Memory is on 2048 characters, but:
     * 1,2 and 3 Byte long characters are counting as 1 character, while 4Byte Characters are counting as 2 Characters.
     * There fore the below special count is needed.
     */
    protected function isToLong(string $string): bool
    {
        $realCharLength = mb_strlen($string);
        if ($realCharLength < (self::MAX_STR_LENGTH / 2)) {
            // we do not have to make the regex stuff,
            // if the real char length is shorter as half of the max count
            return false;
        }
        //since for T5Memory 4Byte characters seems to count 2 characters,
        // we have to count and add them to get the real count
        $smileyCount = preg_match_all('/[\x{10000}-\x{10FFFF}]/mu', $string);

        return ($realCharLength + $smileyCount) > self::MAX_STR_LENGTH;
    }

    /**
     * @param array|stdClass $data
     *
     * @throws JsonException
     */
    private function jsonEncode($data): string
    {
        // Due to error in proxygen library in t5memory:
        // json closing brace should follow a new line symbol (should be "\n}" instead of "}"),
        // otherwise such a json won't be parsed correctly
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }

    /**
     * Generates the Timeouts to use for a request
     * TODO T5MEMORY: remove when T5Memory is out of production
     */
    private function createTimeout(int $seconds): int
    {
        return T5Memory::REQUEST_TIMEOUT + $seconds;
    }
}
