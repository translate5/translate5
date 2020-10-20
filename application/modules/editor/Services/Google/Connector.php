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

/**
 */
class editor_Services_Google_Connector extends editor_Services_Connector_Abstract {

    /**
     * @var editor_Services_Google_HttpApi
     */
    protected $api;

    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::__construct()
     */
    public function __construct() {
        parent::__construct();
        $this->api = ZfExtended_Factory::get('editor_Services_Google_HttpApi');
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */
        $this->defaultMatchRate = $config->runtimeOptions->LanguageResources->google->matchrate;
        $this->batchQueryBuffer = 50;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::open()
     */
    public function open() {
        //This call is not necessary, since TMs are opened automatically.
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::open()
     */
    public function close() {
    /*
     * This call deactivated, since openTM2 has a access time based garbage collection
     * If we close a TM and another Task still uses this TM this bad for performance,
     *  since the next request to the TM has to reopen it
     */
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::query()
     */
    public function query(editor_Models_Segment $segment) {
        $this->initAndPrepareQueryString($segment);
        if(!$this->api->search($this->searchQueryString,$this->languageResource->getSourceLangCode(),$this->languageResource->getTargetLangCode())){
            return $this->resultList;
        }
        $result=$this->api->getResult();
        $translation = $result['text'] ?? "";
        $this->resultList->addResult($this->prepareTranslatedText($translation), $this->defaultMatchRate);
        return $this->resultList;
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::search()
     */
    public function search(string $searchString, $field = 'source', $offset = null) {
        throw new BadMethodCallException("The Google Translation Connector does not support search requests");
    }
    
    /***
     * Search the resource for available translation. Where the source text is in resource source language and the received results
     * are in the resource target language 
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::translate()
     */
    public function translate(string $searchString){
        return $this->queryGoogleApi($searchString);
    }

    /***
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::languages()
     */
    public function languages(){
        return $this->api->getLanguages();
    }
    
    /***
     * Send batch query request to the remote endpoint. For each segment, save one cache entry in the batch cache database
     * where the result will be serialized editor_Services_ServiceResult.
     * @param array $queryStrings
     * @param array $querySegmentMap
     */
    public function handleBatchQuerys(array $queryStrings,array $querySegmentMap){
        $sourceLang = $this->languageResource->getSourceLangCode();
        $targetLang = $this->languageResource->getTargetLangCode();
        $this->resultList->resetResult();
        if(!$this->api->searchBatch($queryStrings, $sourceLang, $targetLang)){
            return;
        }
        $results = $this->api->getResult();
        if(empty($results)) {
            return;
        }
        
        //for each segment, one result is available
        foreach ($results as $segmentResults) {
            //get the segment from the beginning of the cache
            //we assume that for each requested query string, we get one response back
            $seg =array_shift($querySegmentMap);
            /* @var $seg editor_Models_Segment */
            
            $this->initAndPrepareQueryString($seg);

            $this->resultList->addResult($this->prepareTranslatedText($segmentResults['text']), $this->defaultMatchRate);
            $this->saveBatchResults($seg);
            $this->resultList->resetResult();
        }
    }
    
    /***
     * Query the google cloud api for the search string
     * @param string $searchString
     * @param bool $reimportWhitespace optional, if true converts whitespace into translate5 capable internal tag
     * @return editor_Services_ServiceResult
     */
    protected function queryGoogleApi($searchString, $reimportWhitespace = false){
        if(empty($searchString) && $searchString !== "0") {
            return $this->resultList;
        }
        
        
        $result=null;
        if($this->api->search($searchString,$this->languageResource->getSourceLangCode(),$this->languageResource->getTargetLangCode())){
            $result=$this->api->getResult();
        }
        
        $translation = $result['text'] ?? "";
        if($reimportWhitespace) {
            $translation = $this->importWhitespaceFromTagLessQuery($translation);
        }
        
        $this->resultList->addResult($translation, $this->defaultMatchRate);
        return $this->resultList;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::getStatus()
     */
    public function getStatus(& $moreInfo){
        
        try {
            if($this->api->getStatus()){
                return self::STATUS_AVAILABLE;
            }
        }catch (ZfExtended_BadGateway $e){
            $moreInfo = $e->getMessage();
            $logger = Zend_Registry::get('logger')->cloneMe('editor.languageresource.service.connector');
            /* @var $logger ZfExtended_Logger */
            $logger->warn('E1282','Language resource communication error.',
            array_merge($e->getErrors(),[
                'languageResource'=>$this->languageResource,
                'message'=>$e->getMessage()
            ]));
        }
        return self::STATUS_NOCONNECTION;
    }
}
