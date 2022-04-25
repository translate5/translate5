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
 */
class editor_Services_Microsoft_Connector extends editor_Services_Connector_Abstract {

    use editor_Services_Connector_BatchTrait;
    
    /***
     * Every string lower than this value will be translated using the dictonary lookup api.
     * This is only the case when using the translate call
     * @var integer
     */
    const DICTONARY_SEARCH_CHARACTERS_BORDER = 30;
    
    /**
     * @var editor_Services_Microsoft_HttpApi
     */
    protected $api;

    /**
     * Using Xliff based tag handler here
     * @var string
     */
    protected $tagHandlerClass = 'editor_Services_Connector_TagHandler_Xliff';
    
    /**
     * Just overwrite the class var hint here
     * @var editor_Services_Connector_TagHandler_Xliff
     */
    protected $tagHandler;
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::__construct()
     */
    public function __construct() {
        parent::__construct();
        $this->defaultMatchRate = $this->config->runtimeOptions->LanguageResources->microsoft->matchrate;
        $this->batchQueryBuffer = 30;
        
        editor_Services_Connector_Exception::addCodes([
            'E1344' => 'Microsoft Translator returns an error: {errorNr} - {message}',
            'E1345' => 'Could not authorize to Microsoft Translator, check your configured credentials.',
            'E1346' => 'Microsoft Translator quota exceeded. A limit has been reached.',
        ]);
        
        ZfExtended_Logger::addDuplicatesByMessage('E1345', 'E1346');
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::connectTo()
     */
    public function connectTo(editor_Models_LanguageResources_LanguageResource $languageResource, $sourceLang, $targetLang) {
        parent::connectTo($languageResource, $sourceLang, $targetLang);
        $this->api = ZfExtended_Factory::get('editor_Services_Microsoft_HttpApi', [$languageResource->getResource()]);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::query()
     */
    public function query(editor_Models_Segment $segment) {
        $queryString = $this->getQueryStringAndSetAsDefault($segment);
        
        if(empty($queryString) && $queryString !== "0") {
            return $this->resultList;
        }
        
        if ($this->queryApi($this->tagHandler->prepareQuery($queryString))) {
            $results = $this->api->getResult();
            foreach ($results as $segmentResults) {
                //a single response is the same as for batch processing:
                $this->processBatchResult($segmentResults, $segment->getId());
            }
            return $this->resultList;
        }
        throw $this->createConnectorException();
    }
    
    /**
     * Query the API for the search string
     * @param string $searchString
     * @param boolean $useDictionary
     * @return boolean
     */
    protected function queryApi($searchString, &$useDictionary = false): bool{
        return $this->api->search($searchString, $this->languageResource->getSourceLangCode(), $this->languageResource->getTargetLangCode(), $useDictionary);
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::translate()
     */
    public function translate(string $searchString){
        if(empty($searchString)&&$searchString!=="0") {
            return $this->resultList;
        }
        
        $results = [];
        //the dictonary lookup translation is active only for less than or equal to DICTONARY_SEARCH_CHARACTERS_BORDER
        $useDictionary = mb_strlen($searchString) <= self::DICTONARY_SEARCH_CHARACTERS_BORDER;
        
        //query either with dictionary or without as fallback
        // $useDictionary may be set to false by the search itself, if the languages do not support a dictionary lookup
        if ($this->queryApi($searchString, $useDictionary)) {
            $results = $this->api->getResult();
            $hasNoDictResults = $useDictionary && (empty($results) || empty($results[0]) || empty($results[0]->translations));
            //if there was no dictionary translation we call it again without dictionary
            if($hasNoDictResults && $this->queryApi($searchString)) {
                $useDictionary = false; //set to false for further processing of the data
            }
        }
        
        if(!is_null($this->api->getError())) {
            throw $this->createConnectorException();
        }
        
        $this->processTranslateResults($useDictionary);
        
        return $this->resultList;
    }
    
    /**
     * process the instant translate results
     * @param bool $useDictionary
     */
    protected function processTranslateResults(bool $useDictionary) {
        $foundText = null;
        $metaData = [];
        
        //loop over all results, if using dictionary we collect also additional meta data
        foreach ($this->api->getResult() as $result) {
            if(empty($result->translations)){
                continue;
            }
            foreach($result->translations as $translation) {
                //use first translation as result:
                if(is_null($foundText)) {
                    //the translation is in a different field, depending if dictionary used or not:
                    $foundText = $useDictionary ? $translation->displayTarget : $translation->text;
                }
                if($useDictionary) {
                    //dictionary usage
                    $metaData[] = $translation;
                }
            }
        }
        
        //return just the emty result list if nothing found
        if(is_null($foundText)) {
            return;
        }
        
        //with dictionary used, use also the found metadata (wrap it in another array first)
        $metaData = $useDictionary ? ['alternativeTranslations' => $metaData] : null;
        
        $this->resultList->addResult($foundText, $this->defaultMatchRate, $metaData);
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::languages()
     */
    public function languages(): array {
        //if empty api wrapper
        if(empty($this->api)) {
            $this->api = ZfExtended_Factory::get('editor_Services_Microsoft_HttpApi', [$this->resource]);
        }
        $languages = $this->api->getLanguages();
        if(is_null($languages)){
            throw $this->createConnectorException();
        }
        return $languages;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_BatchTrait::batchSearch()
     */
    protected function batchSearch(array $queryStrings, string $sourceLang, string $targetLang): bool {
        $result = $this->api->search($queryStrings, $sourceLang, $targetLang);
        if($result) {
            return $result;
        }
        throw $this->createConnectorException();
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_BatchTrait::processBatchResult()
     */
    protected function processBatchResult($segmentResults, int $segmentId=-1) {
        if(!isset($segmentResults->translations) || empty($segmentResults->translations[0])) {
            //if there is no translation we do not process any result
            return;
        }
        //since we translate only to one target language, we will receive only one result in the translations array:
        $result = $segmentResults->translations[0];
        $this->resultList->addResult($this->tagHandler->restoreInResult($result->text, $segmentId), $this->defaultMatchRate);
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::getStatus()
     */
    public function getStatus(editor_Models_LanguageResources_Resource $resource){
        $this->lastStatusInfo = '';
        $this->api = ZfExtended_Factory::get('editor_Services_Microsoft_HttpApi',[$resource]);
        
        if($this->api->getStatus()){
            return self::STATUS_AVAILABLE;
        }
        
        //we also log below errors
        $exception = $this->createConnectorException();
        $this->logger->exception($exception);
        
        $status = $this->api->getResponse()->getStatus();
        if($status == 403 || $status == 401) {
            $this->lastStatusInfo = 'Anmeldung bei Microsoft Translator fehlgeschlagen. Bitte überprüfen Sie die API Einstellungen.';
            return self::STATUS_NOVALIDLICENSE;
        }
        
        if($this->api->getResponse()->getStatus() == 429) {
            $this->lastStatusInfo = 'Ein Nutzungslimit wurde erreicht, prüfen Sie das Systemlog für weitere Informationen.';
            return self::STATUS_QUOTA_EXCEEDED;
        }
        
        $this->lastStatusInfo = $exception->getExtra('message', '');
        return self::STATUS_NOCONNECTION;
    }
    
    /**
     * Creates a service connector exception
     * @return editor_Services_Connector_Exception
     */
    protected function createConnectorException(): editor_Services_Connector_Exception {
        $httpStatus = $this->api->getResponse()->getStatus();
        $error = $this->api->getError();
        $json = null;
        $data = [
            'service' => $this->getResource()->getName(),
            'languageResource' => $this->languageResource ?? '',
        ];
        
        switch ($httpStatus) {
            case 401:
            case 403:
                $ecode = 'E1345'; //Could not authorize to Microsoft Translator, check your configured credentials.
                break;
            case 429:
                $ecode = 'E1346'; //Microsoft Translator quota exceeded. A limit has been reached.
                break;
            default:
                //common language resource error
                $ecode = 'E1344'; //Microsoft Translator returns an error: {errorNr} - {message}
                break;
        }
        
        //if the body contains json with a valid message, we use that as error message, but we still keep the whole body
        if(!empty($error->body)) {
            $json = json_decode($error->body);
            //the strlen check can not be added with && to the above if
            if(isset($json->error) && isset($json->error->code)) {
                $data['errorNr'] = $json->error->code;
            }
            if(isset($json->error) && isset($json->error->message)) {
                $data['message'] = $json->error->message;
            }
        }
        $data['error'] = $error;
        
        return new editor_Services_Connector_Exception($ecode, $data);
    }
}
