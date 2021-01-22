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

    use editor_Services_Connector_BatchTrait;
    
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
        $this->api = ZfExtended_Factory::get('editor_Services_Microsoft_HttpApi');
        $this->defaultMatchRate = $this->config->runtimeOptions->LanguageResources->microsoft->matchrate;
        $this->batchQueryBuffer = 30;
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::query()
     */
    public function query(editor_Models_Segment $segment) {
        $qs = $this->getQueryStringAndSetAsDefault($segment);
        if(!$this->api->search($this->tagHandler->prepareQuery($qs),$this->languageResource->getSourceLangCode(),$this->languageResource->getTargetLangCode())){
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
        $this->resultList->addResult($this->tagHandler->restoreInResult($translation), $this->defaultMatchRate,['alternativeTranslations'=>$metaData]);
        return $this->resultList;
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
     * @see editor_Services_Connector_Abstract::languages()
     */
    public function languages(): array{
        return array_keys($this->api->getLanguages());
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_BatchTrait::batchSearch()
     */
    protected function batchSearch(array $queryStrings, string $sourceLang, string $targetLang): bool {
        return $this->api->search($queryStrings, $sourceLang, $targetLang);
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_BatchTrait::processBatchResult()
     */
    protected function processBatchResult($segmentResults) {
        $this->resultList->addResult($this->tagHandler->restoreInResult($segmentResults['text']), $this->defaultMatchRate);
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::getStatus()
     */
    public function getStatus(editor_Models_LanguageResources_Resource $resource){
        if($this->api->getStatus()){
            return self::STATUS_AVAILABLE;
        }
        return self::STATUS_NOCONNECTION;
    }
}
