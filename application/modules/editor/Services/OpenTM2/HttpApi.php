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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use MittagQI\Translate5\Service\T5Memory;
use MittagQI\Translate5\T5Memory\DTO\SearchDTO;
use MittagQI\Translate5\T5Memory\Enum\StripFramingTags;

/**
 * OpenTM2 HTTP Connection API
 */
class editor_Services_OpenTM2_HttpApi extends editor_Services_Connector_HttpApiAbstract
{
    private const DATE_FORMAT = 'Ymd\THis\Z';

    private const MARKUP_TABLE = 'OTMXUXLF';

    private const REQUEST_ENCTYPE = 'application/json; charset=utf-8';

    public const MAX_STR_LENGTH = 2048;

    /**
     * @var editor_Models_LanguageResources_LanguageResource
     */
    protected $languageResource;

    protected editor_Services_OpenTM2_FixLanguageCodes $fixLanguages;

    public function __construct()
    {
        $this->fixLanguages = new editor_Services_OpenTM2_FixLanguageCodes();
        $this->fixLanguages->setDisabled(true);
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
        $http->setRawData($this->jsonEncode($data), self::REQUEST_ENCTYPE);

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
        $http->setConfig([
            'timeout' => $this->createTimeout(1200),
        ]);
        $http->setRawData($this->jsonEncode($data), self::REQUEST_ENCTYPE);

        if ($this->processResponse($http->request())) {
            return $data->name;
        }

        return null;
    }

    /**
     * This method imports a memory from a TMX file.
     */
    public function importMemory($tmData, string $tmName, StripFramingTags $stripFramingTags)
    {
        //In:{ "Method":"import", "Memory":"MyTestMemory", "TMXFile":"C:/FileArea/MyTstMemory.TMX" }
        //Out: { "ReturnValue":0, "ErrorMsg":"" }

        $data = new stdClass();

        $data->tmxData = base64_encode($tmData);
        $data->framingTags = $stripFramingTags->value;

        $http = $this->getHttpWithMemory('POST', $tmName, '/import');
        $http->setConfig([
            'timeout' => $this->createTimeout(1200),
        ]);
        $http->setRawData($this->jsonEncode($data), self::REQUEST_ENCTYPE);

        return $this->processResponse($http->request());
    }

    public function createMemoryWithFile(
        string $memory,
        string $sourceLanguage,
        string $filePath,
        StripFramingTags $stripFramingTags
    ): ?string {
        $data = new stdClass();
        $data->name = $this->addTmPrefix($memory);
        $data->sourceLang = $this->fixLanguages->key($sourceLanguage);
        $data->framingTags = $stripFramingTags->value;

        $result = $this->sendStreamRequest(
            rtrim($this->resource->getUrl(), '/') . '/',
            $this->getStreamFromFile($filePath),
            basename($filePath),
            $data
        );

        return $result ? $data->name : null;
    }

    public function importMemoryAsFile(string $filePath, string $tmName, StripFramingTags $stripFramingTags): bool
    {
        return $this->sendStreamRequest(
            rtrim($this->resource->getUrl(), '/') . '/' . $tmName . '/importtmx',
            $this->getStreamFromFile($filePath),
            basename($filePath),
            [
                'framingTags' => $stripFramingTags->value,
            ]
        );
    }

    /**
     * @throws RuntimeException
     * @return resource
     */
    private function getStreamFromFile(string $filePath)
    {
        $stream = fopen($filePath, 'r');

        if (false === $stream) {
            throw new RuntimeException('Could not open file: ' . $filePath);
        }

        // Uncomment when compression is implemented in the API
        //        stream_filter_append($stream, 'zlib.deflate', STREAM_FILTER_READ, [
        //            "window" => 30,
        //        ]);

        return $stream;
    }

    private function sendStreamRequest(string $uri, $stream, string $filename, array|object $data = null): bool
    {
        $client = new Client();
        $multipart = [];

        if (null !== $data) {
            $multipart[] = [
                'name' => 'json_data',
                'contents' => json_encode($data, JSON_PRETTY_PRINT),
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ];
        }

        $multipart[] = [
            'name' => 'file',
            'contents' => $stream,
            'filename' => $filename,
        ];

        try {
            $response = $client->post($uri, [
                'multipart' => $multipart,
            ]);

            // trigger this method to set http (yes! :( ) so that self::processResponse can get uri from it.
            $this->getHttp('POST');

            return $this->processResponse(
                new Zend_Http_Response(
                    $response->getStatusCode(),
                    $response->getHeaders(),
                    $response->getBody()->getContents()
                )
            );
        } catch (RequestException $e) {
            return $this->processPsrRequestException($e);
        }
    }

    private function processPsrRequestException(RequestException $e): bool
    {
        if ($e->hasResponse()) {
            $response = $e->getResponse();
            $this->http = ZfExtended_Factory::get('Zend_Http_Client');
            $this->http->setUri((string) $e->getRequest()->getUri());

            return $this->processResponse(
                new Zend_Http_Response(
                    $response->getStatusCode(),
                    $response->getHeaders(),
                    $response->getBody()->getContents()
                )
            );
        }

        throw $e;
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
        $http->setConfig([
            'timeout' => $this->createTimeout(1200),
        ]);
        $http->setRawData($this->jsonEncode($data), 'application/json; charset=utf-8');

        return $this->processResponse($http->request());
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
        $url = $this->addTmPrefix(urlencode($tmName)) . '/' . ltrim($urlSuffix, '/');

        return $this->getHttp($method, $url);
    }

    /**
     * adds the internal TM prefix to the given TM name
     * @throws Zend_Exception
     */
    protected function addTmPrefix(string $tmName): string
    {
        //CRUCIAL: the prefix (if any) must be added on usage, and may not be stored in the specificName
        // that is relevant for security on a multi hosting environment
        $prefix = Zend_Registry::get('config')->runtimeOptions->LanguageResources->opentm2->tmprefix;
        if (! empty($prefix) && ! str_starts_with($tmName, $prefix . '-')) {
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
        $http->setConfig([
            'timeout' => $this->createTimeout(1200),
        ]);
        $http->setHeaders('Accept', $mime);
        $response = $http->request();
        if ($response->getStatus() === 200) {
            $this->result = $response->getBody();
            if ($mime == "application/xml") {
                $targetLang = $this->languageResource->getTargetLangCode();
                $sourceLang = $this->languageResource->getSourceLangCode();
                $this->result = $this->fixLanguages->tmxOnDownload($sourceLang, $targetLang, $this->result);
            }

            return true;
        }

        return $this->processResponse($response);
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
            'timeout' => $this->createTimeout(3),
        ]);

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
        $json->markupTable = self::MARKUP_TABLE;

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

        $json->markupTable = self::MARKUP_TABLE; //NEEDED otherwise t5memory crashes
        $json->context = $segment->getMid(); // here MID (context was designed for dialog keys/numbers on translateable strings software)

        $http = $this->getHttpWithMemory('POST', $tmName, 'fuzzysearch');

        $http->setRawData($this->jsonEncode($json), self::REQUEST_ENCTYPE);

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

        return $this->processResponse($http->request());
    }

    public function search(string $tmName, ?string $searchPosition, ?int $numResults, SearchDTO $searchDTO): bool
    {
        $data = $this->getSearchData($searchDTO, $searchPosition, $numResults);
        $http = $this->getHttpWithMemory('POST', $tmName, '/search');
        $http->setRawData($this->jsonEncode($data), self::REQUEST_ENCTYPE);

        return $this->processResponse($http->request());
    }

    public function deleteBatch(string $tmName, SearchDTO $searchDTO): bool
    {
        $data = $this->getSearchData($searchDTO);
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

        $http->setRawData($this->jsonEncode($json), self::REQUEST_ENCTYPE);

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
                $invalid = (int) $this->result->invalidSegmentCount;

                if ($invalid > 0) {
                    $overall = (int) $this->result->reorganizedSegmentCount;
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
        $json->sourceLang = $this->fixLanguages->key($this->languageResource->getSourceLangCode());
        $json->targetLang = $this->fixLanguages->key($this->languageResource->getTargetLangCode());
        $json->timeStamp = $this->getNowDate();

        return $json;
    }

    private function getSearchData(SearchDTO $searchDTO, ?string $searchPosition = null, ?int $numResults = null): array
    {
        $searchOptions = ', CASEINSENSETIVE';

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
     * parses and processes the response of OpenTM2, and handles the errors
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
        }

        return empty($this->error);
    }

    /**
     * returns the current time stamp in the expected format for OpenTM2
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
     * returns true if string is to long for OpenTM2
     * According some research, it seems that the magic border to crash OpenTM2 is on 2048 characters, but:
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
        //since for OpenTM2 4Byte characters seems to count 2 characters,
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
     * TODO T5MEMORY: remove when OpenTM2 is out of production
     */
    private function createTimeout(int $seconds): int
    {
        return T5Memory::REQUEST_TIMEOUT + $seconds;
    }
}
