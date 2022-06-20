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
 * OpenTM2 HTTP Connection API
 */
class editor_Services_OpenTM2_HttpApi extends editor_Services_Connector_HttpApiAbstract
{
    private const MARKUP_TABLE = 'OTMXUXLF';
    private const REQUEST_ENCTYPE = 'application/json; charset=utf-8';

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

    public function __construct() {
        $this->fixLanguages = ZfExtended_Factory::get('editor_Services_OpenTM2_FixLanguageCodes');
    }
    
    /**
     * This method creates a new memory.
     */
    public function createEmptyMemory($memory, $sourceLanguage) {
        $data = new stdClass();
        $data->name = $memory;
        $data->sourceLang = $this->fixLanguages->key($sourceLanguage);
        
        $http = $this->getHttp('POST');
        $http->setRawData(json_encode($data), self::REQUEST_ENCTYPE);
        return $this->processResponse($http->request());
    }
    
    /**
     * This method creates a new memory with TM file
     */
    public function createMemory($memory, $sourceLanguage, $tmData) {
        $data = new stdClass();
        $data->name = $memory;
        $data->sourceLang = $this->fixLanguages->key($sourceLanguage);
        $data->data = base64_encode($tmData);
        
        $http = $this->getHttp('POST');
        $http->setConfig(['timeout' => 1200]);
        $http->setRawData(json_encode($data), self::REQUEST_ENCTYPE);
        return $this->processResponse($http->request());
    }
    
    /**
     * This method imports a memory from a TMX file.
     */
    public function importMemory($tmData) {
        //In:{ "Method":"import", "Memory":"MyTestMemory", "TMXFile":"C:/FileArea/MyTstMemory.TMX" }
        //Out: { "ReturnValue":0, "ErrorMsg":"" }
        
        $data = new stdClass();

        if($this->isOpenTM2()) {
            $tmData = $this->fixLanguages->tmxOnUpload($tmData);
            /* @var $tmxRepairer editor_Services_OpenTM2_FixImportParser */
            $tmxRepairer = ZfExtended_Factory::get('editor_Services_OpenTM2_FixImportParser');
            $tmData = $tmxRepairer->convert($tmData);
        }
        $data->tmxData = base64_encode($tmData);

        $http = $this->getHttpWithMemory('POST', '/import');
        $http->setConfig(['timeout' => 1200]);
        $http->setRawData(json_encode($data), self::REQUEST_ENCTYPE);
        
        return $this->processResponse($http->request());
    }
    
    /**
     * prepares a Zend_Http_Client, prefilled with the configured URL + the given REST URL Parts (ID + verbs)
     * @param string $httpMethod
     * @param string $urlSuffix
     * @return Zend_Http_Client
     */
    protected function getHttp($method, $urlSuffix = '') {
        $url = rtrim($this->resource->getUrl(), '/');
        $urlSuffix = ltrim($urlSuffix, '/');
        $this->http = ZfExtended_Factory::get('Zend_Http_Client');
        /* @var $http Zend_Http_Client */
        $this->http->setUri($url.'/'.$urlSuffix);
        $this->http->setMethod($method);
        $this->httpMethod = $method;
        $this->http->setHeaders('Accept-charset', 'UTF-8');
        $this->http->setHeaders('Accept', self::REQUEST_ENCTYPE);
        return $this->http;
    }
    
    /**
     * prepares a Zend_Http_Client, prefilled with the configured URL + the Memory Name + additional URL parts
     * @param string $httpMethod
     * @param string $urlSuffix
     * @return Zend_Http_Client
     */
    protected function getHttpWithMemory($method, $urlSuffix = '') {
        $fileName = $this->languageResource->getSpecificData('fileName') ?? false;
        if(empty($fileName)){
            // if filename would be empty, it would be possible to make a GET / to OpenTM2, which would list all TMs in an invalid JSON
            // this invalid JSON would then be logged to all error receivers, this is something we do not want to!
            // so we ensure that there is a path, although this would lead to an 404
            $fileName = 'i/do/not/exist';
        }
        $url = urlencode($fileName).'/'.ltrim($urlSuffix, '/');
        return $this->getHttp($method, $url);
    }
    
    /**
     * retrieves the TM as TM file
     * @param string|array $mime
     * @return boolean
     */
    public function get($mime) {
        if(is_array($mime)) {
            $mime = implode(',', $mime);
        }
        $http = $this->getHttpWithMemory('GET');
        $http->setConfig(['timeout' => 1200]);
        $http->setHeaders('Accept', $mime);
        $response = $http->request();
        if($response->getStatus() === 200) {
            $this->result = $response->getBody();
            if($mime == "application/xml"){
                $targetLang = $this->languageResource->targetLangCode;
                $sourceLang = $this->languageResource->sourceLangCode;
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
        if($this->isT5Memory) {
            return $tmxData;
        }

        /** @var editor_Services_OpenTM2_FixExport $fix */
        $fix = ZfExtended_Factory::get('editor_Services_OpenTM2_FixExport');
        $result = $fix->convert($tmxData);
        if($fix->getChangeCount() > 0 || $fix->getNewLineCount() > 0) {
            $logger = Zend_Registry::get('logger');
            $logger->warn('E9999', 'TMX Export: Entities in {changeCount} text parts repaired (see raw php error log), {newLineCount} new line tags restored.', [
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
    public function status() {
        if(empty($this->languageResource)) {
            $this->getHttp('GET', '/');
        }
        else {
            $this->getHttpWithMemory('GET', '/status');
        }
        $this->http->setConfig(['timeout' => 3]);
        try {
            //OpenTM2 returns invalid JSON on calling "/", so we have to fix this here by catching the invalid JSON Exception.
            // Also this would send a list of all TMs to all error receivers, which must also prevented
            return $this->processResponse($this->http->request());
        } catch(editor_Services_Exceptions_InvalidResponse $e) {
            if(empty($this->languageResource) && $e->getErrorCode() == 'E1315') {
                return true;
            }
            throw $e;
        }
    }

    /**
     * This method deletes a memory.
     */
    public function delete() {
        $this->getHttpWithMemory('DELETE');
        return $this->processResponse($this->http->request());
    }
    
    /**
     * searches for matches in the TM
     * @param editor_Models_Segment $segment
     * @param string $queryString
     * @param string $filename
     * @return boolean
     */
    public function lookup(editor_Models_Segment $segment, string $queryString, string $filename) {
        $json = new stdClass();

        $json->sourceLang = $this->fixLanguages->key($this->languageResource->sourceLangCode);
        $json->targetLang = $this->fixLanguages->key($this->languageResource->targetLangCode);
        $json->markupTable = self::MARKUP_TABLE;
        
        if($this->isToLong($queryString)) {
            $this->result = json_decode('{"ReturnValue":0,"ErrorMsg":"","NumOfFoundProposals":0}');
            return true;
        }
        
//         $queryString = 'Start the <bpt i="1" mid="1" /><ph mid="2"/><ex mid="3" i="1"/> and wait until the LED is continuous green.';
//         $queryString = 'Start the <it type="struct"/> and wait until the LED is continuous green.';
//         $queryString = 'Start the <x mid="2"/> and wait until the LED is continuous green.';
//         $queryString = 'Start the <bx mid="1" rid="1"/><x mid="2"/><ex mid="3" rid="1"/> and wait until the LED is continuous green.';
        
        $json->source = $queryString;
        //In general OpenTM2 can deal with whole paths, not only with filenames.
        // But we hold the filepaths in the FileTree JSON, so this value is not easily accessible,
        // so we take only the single filename at the moment
        $json->documentName = $filename;

        $json->markupTable = self::MARKUP_TABLE; //NEEDED otherwise t5memory crashes
        $json->context = $segment->getMid(); // here MID (context was designed for dialog keys/numbers on translateable strings software)
        
        $http = $this->getHttpWithMemory('POST', 'fuzzysearch');
        $http->setRawData(json_encode($json), self::REQUEST_ENCTYPE);
        return $this->processResponse($http->request());
    }
    
    /**
     * This method searches the given search string in the proposals contained in a memory (concordance search).
     * The function returns one proposal per request.
     * The caller has to provide the search position returned by a previous call or an empty search position to start the search at the begin of the memory.
     * Note: Provide the returned search position NewSearchPosition as SearchPosition on subsequenet calls to do a sequential search of the memory.
     */
    public function search($queryString, $field, $searchPosition = null) {
        if($this->isToLong($queryString)) {
            $this->result = json_decode('{"results":[]}');
            return true;
        }
        $data = new stdClass();
        $data->searchString = $queryString;
        $data->searchType = $field;
        $data->searchPosition = $searchPosition;
        $data->numResults = 20;
        $data->msSearchAfterNumResults = 250;
        $http = $this->getHttpWithMemory('POST', 'concordancesearch');
        $http->setRawData(json_encode($data), self::REQUEST_ENCTYPE);
        return $this->processResponse($http->request());
    }

    /**
     * This method updates (or adds) a memory proposal in the memory.
     * Note: This method updates an existing proposal when a proposal with the same key information (source text, language, segment number, and document name) exists.
     *
     * @param string $source
     * @param string $target
     * @param editor_Models_Segment $segment
     * @param $filename
     * @return boolean
     * @throws Zend_Http_Client_Exception
     */
    public function update(string $source, string $target, editor_Models_Segment $segment, $filename): bool
    {
        $http = $this->getHttpWithMemory('POST', 'entry');
        $json = $this->getUpdateJson(__FUNCTION__,$source,$target);
        if(!is_null($this->error)){
            return false;
        }

        $json->documentName = $filename; // 101 doc match
        $json->author = $segment->getUserName();
        $json->timeStamp = $this->nowDate();
        $json->context = $segment->getMid(); //INFO: this is segment stuff

        $http->setRawData(json_encode($json), self::REQUEST_ENCTYPE);
        return $this->processResponse($http->request());
    }

    /***
     * Update text values ($source/$target) to the current tm memory
     * @param string $source
     * @param string $target
     * @return bool
     * @throws Zend_Http_Client_Exception
     */
    public function updateText(string $source, string $target): bool
    {

        $http = $this->getHttpWithMemory('POST', 'entry');
        $json = $this->getUpdateJson(__FUNCTION__,$source,$target);
        if(!is_null($this->error)){
            return false;
        }

        $json->documentName = 'source';
        $userData = editor_User::instance()->getData();
        $json->author = $userData->firstName . ' '. $userData->surName;
        $json->context = '';
        $json->addInfo = $json->documentName;

        $http->setRawData(json_encode($json), self::REQUEST_ENCTYPE);

        return $this->processResponse($http->request());
    }

    public function updateEntry(string $source, string $target): bool
    {
        $request = [
            'sourceLang' => $this->languageResource->getSourceLang(),
            'targetLang' => $this->languageResource->getTargetLang(),
            'source' => $source,
            'target' => $target,
            'markupTable' => self::MARKUP_TABLE,
        ];

        $http = $this->getHttpWithMemory('POST', 'entry');
        $http->setRawData(json_encode($request, JSON_THROW_ON_ERROR), self::REQUEST_ENCTYPE);

        return $this->processResponse($http->request());
    }

    public function deleteEntry(string $source, string $target): bool
    {
        $request = [
            'sourceLang' => $this->languageResource->getSourceLang(),
            'targetLang' => $this->languageResource->getTargetLang(),
            'source' => $source,
            'target' => $target,
            'markupTable' => self::MARKUP_TABLE,
        ];

        $http = $this->getHttpWithMemory('POST', 'entrydelete');
        $http->setRawData(json_encode($request, JSON_THROW_ON_ERROR), self::REQUEST_ENCTYPE);

        return $this->processResponse($http->request());
    }

    /***
     * Get the default update memory json
     * @param string $function
     * @param string $source
     * @param string $target
     * @return stdClass
     */
    private function getUpdateJson(string $function,string $source, string $target): stdClass
    {

        if($this->isToLong($source) || $this->isToLong($target)) {
            $this->error = new stdClass();
            $this->error->method = $this->httpMethod;
            $this->error->url = $this->http->getUri(true);
            $this->error->type = 'TO_LONG';
            $this->error->error = 'The given segment data is to long and would crash OpenTM2 on saving it.';
            return new stdClass();
        }

        $json = $this->json($function);
        $json->source = $source;
        $json->target = $target;
        $json->type = "Manual";
        $json->markupTable = self::MARKUP_TABLE; //fixed markup table for our XLIFF subset
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
    protected function json(string $method) {
        $result = new stdClass();
        $result->Method = $method;
        return $result;
    }
    
    /**
     * parses and processes the response of OpenTM2, and handles the errors
     * @param Zend_Http_Response $response
     * @return boolean
     */
    protected function processResponse(Zend_Http_Response $response): bool {
        parent::processResponse($response);

        //Normally the ReturnValue is 0 if there is no error.
        $returnValueError = !empty($this->result->ReturnValue) && $this->result->ReturnValue > 0;
        
        //For some errors this is not true, then only a ErrorMsg is set, but return value is 0,
        if($returnValueError || !empty($this->result->ErrorMsg)) {
            $this->error = new stdClass();
            $this->error->method = $this->httpMethod;
            $this->error->url = $this->http->getUri(true);
            $this->error->code = 'Error Nr. '.$this->result->ReturnValue;
            $this->error->error = $this->result->ErrorMsg;
        }
        
        return empty($this->error);
    }
    
    /**
     * returns the current time stamp in the expected format for OpenTM2
     */
    protected function nowDate() {
        return gmdate('Ymd\THis\Z');
    }
    
    /**
     * Sets internally the used language resource (and service resource)
     * @param editor_Models_LanguageResources_LanguageResource $languageResource
     */
    public function setLanguageResource(editor_Models_LanguageResources_LanguageResource $languageResource) {
        $this->setResource($languageResource->getResource());
        $this->languageResource = $languageResource;
    }
    
    public function getLanguageResource() {
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
     * @deprecated check all usages and remove them if OpenTM2 is replaced with t5memory
     * @return bool
     */
    public function isOpenTM2(): bool {
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
    protected function isToLong(string $string): bool {
        $realCharLength = mb_strlen($string);
        if($realCharLength < (self::MAX_STR_LENGTH / 2)) {
            // we do not have to make the regex stuff,
            // if the real char length is shorter as half of the max count
            return false;
        }
        //since for OpenTM2 4Byte characters seems to count 2 characters,
        // we have to count and add them to get the real count
        $smileyCount = preg_match_all('/[\x{10000}-\x{10FFFF}]/mu', $string);
        return ($realCharLength + $smileyCount) > self::MAX_STR_LENGTH;
    }
}
