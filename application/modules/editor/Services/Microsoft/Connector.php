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
class editor_Services_Microsoft_Connector extends editor_Services_Connector_Abstract {

    /***
     * Every string lower than this value will be translated using the dictonary lookup api.
     * This is only the case when using the translate call
     * @var integer
     */
    const DICTONARY_SEARCH_CHARACTERS_BORDER=30;
    /**
     * @var editor_Services_Microsoft_HttpApi
     */
    protected $api;

    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::__construct()
     */
    public function __construct() {
        parent::__construct();
        $this->api = ZfExtended_Factory::get('editor_Services_Microsoft_HttpApi');
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */
        $this->defaultMatchRate = $config->runtimeOptions->LanguageResources->microsoft->matchrate;
        $this->batchQueryBuffer = 30;
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
        //This call is not necessary, since TMs are closed automatically.
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
        $metaData=[];
        $translation="";
        foreach ($result as $res) {
            if(empty($translation)&& $translation !== "0"){
                $translation=$res['text'];
            }
            if(isset($res['metaData'])){
                $metaData[]=$res['metaData'];
            }
        }
        $this->resultList->addResult($this->prepareTranslatedText($translation), $this->defaultMatchRate,['alternativeTranslations'=>$metaData]);
        return $this->resultList;
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::search()
     */
    public function search(string $searchString, $field = 'source', $offset = null) {
        throw new BadMethodCallException("The Microsoft Translation Connector does not support search requests");
    }
    
    /***
     * Search the resource for available translation. Where the source text is in resource source language and the received results
     * are in the resource target language 
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::translate()
     */
    public function translate(string $searchString){
        //the dictonary lookup translation is active only for less than or equal to DICTONARY_SEARCH_CHARACTERS_BORDER
        $this->api->setIsDictionaryLookup(mb_strlen($searchString)<=self::DICTONARY_SEARCH_CHARACTERS_BORDER);
        return $this->queryMicrosoftApi($searchString);
    }
    
    
    public function languages(){
        return array_keys($this->api->getLanguages());
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
        if(!$this->api->search($queryStrings, $sourceLang, $targetLang)){
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
     * Query the microsoft api for the search string
     * @param string $searchString
     * @param boolean $reimportWhitespace optional, if true converts whitespace into translate5 capable internal tag
     * @return editor_Services_ServiceResult
     */
    protected function queryMicrosoftApi($searchString, $reimportWhitespace = false){
        if(empty($searchString)&&$searchString!=="0") {
            return $this->resultList;
        }
        
        $result=null;
        if($this->api->search($searchString,$this->languageResource->getSourceLangCode(),$this->languageResource->getTargetLangCode())){
            $result=$this->api->getResult();
        }
        
        if(empty($result)){
            return $this->resultList;
        }
        $metaData=[];
        $translation="";
        foreach ($result as $res) {
            if(empty($translation)&& $translation !== "0"){
                $translation=$res['text'];
                if($reimportWhitespace) {
                    $translation = $this->importWhitespaceFromTagLessQuery($translation);
                }
            }
            if(isset($res['metaData'])){
                $metaData[]=$res['metaData'];
            }
        }
        $this->resultList->addResult($translation, $this->defaultMatchRate,['alternativeTranslations'=>$metaData]);
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
            return self::STATUS_NOCONNECTION;
        }catch (ZfExtended_ErrorCodeException $e){
            $moreInfo = $e->getMessage();
            $logger = Zend_Registry::get('logger')->cloneMe('editor.languageresource.service.connector');
            /* @var $logger ZfExtended_Logger */
            $logger->warn('E1282','Language resource communication error.',
                array_merge($e->getErrors(),[
                    'languageResource'=>$this->languageResource,
                    'message'=>$e->getMessage()
                ]));
            return self::STATUS_NOCONNECTION;
        }
    }
}
