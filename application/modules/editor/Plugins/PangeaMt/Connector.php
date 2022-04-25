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
 * TODO: most of the following code is the same for each language-resource...
 */
class editor_Plugins_PangeaMt_Connector extends editor_Services_Connector_Abstract {
    use editor_Services_Connector_BatchTrait;
    
    /**
     * @var editor_Plugins_PangeaMt_HttpApi
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
        $this->api = ZfExtended_Factory::get('editor_Plugins_PangeaMt_HttpApi');
        $this->defaultMatchRate = $this->config->runtimeOptions->plugins->PangeaMt->matchrate;
        $this->batchQueryBuffer=30;
    }
    
    /***
     *
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::query()
     */
    public function query(editor_Models_Segment $segment) {
        $qs = $this->getQueryStringAndSetAsDefault($segment);
        if(empty($qs) && $qs !== '0') {
            return $this->resultList;
        }
        $results = null;
        $sourceLang = $this->languageResource->getSourceLangCode(); // = e.g. "de", TODO: validate against $this->sourceLang (e.g. 4)
        $targetLang = $this->languageResource->getTargetLangCode();// = e.g. "en"; TODO: validate against $this->targetLang (e.g. 5)
        $engineId = $this->languageResource->getSpecificData('engineId');
        
        if($this->api->search($this->tagHandler->prepareQuery($qs), $sourceLang, $targetLang, $engineId)){
            $results = $this->api->getResult();
            if(empty($results)) {
                return $this->resultList;
            }
            
            foreach ($results as $result) {
                $result = $result[0];
                $target = $result->tgt ?? "";
                $source=$result->src ?? "";
                $this->resultList->addResult($this->tagHandler->restoreInResult($target), $this->defaultMatchRate);
                $this->resultList->setSource($this->tagHandler->restoreInResult($source));
            }
        }
        return $this->resultList;
    }
    
    /***
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::translate()
     */
    public function translate(string $searchString){
        if(empty($searchString)) {
            return $this->resultList;
        }
        $allResults = null;
        $sourceLang = $this->languageResource->getSourceLangCode(); // = e.g. "de", TODO: validate against $this->sourceLang (e.g. 4)
        $targetLang = $this->languageResource->getTargetLangCode();// = e.g. "en"; TODO: validate against $this->targetLang (e.g. 5)
        $engineId = $this->languageResource->getSpecificData('engineId');
        if($this->api->search($searchString, $sourceLang, $targetLang, $engineId)){
            $allResults = $this->api->getResult();
        }
        
        if(empty($allResults)) {
            return $this->resultList;
        }
        
        foreach ($allResults as $result) {
            $result = $result[0];
            $translation = $result->tgt ?? "";
            $this->resultList->addResult($translation,$this->defaultMatchRate);
        }
        return $this->resultList;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_BatchTrait::batchSearch()
     */
    protected function batchSearch(array $queryStrings, string $sourceLang, string $targetLang): bool {
        $engineId = $this->languageResource->getSpecificData('engineId');
        return $this->api->search($queryStrings, $sourceLang, $targetLang, $engineId);
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_BatchTrait::processBatchResult()
     */
    protected function processBatchResult($segmentResults, int $segmentId=-1) {
        foreach ($segmentResults as $result) {
            $target = $result->tgt ?? "";
            $source=$result->src ?? "";
            $this->resultList->addResult($this->tagHandler->restoreInResult($target, $segmentId), $this->defaultMatchRate);
            $this->resultList->setSource($this->tagHandler->restoreInResult($source, $segmentId));
        }
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::getStatus()
     */
    public function getStatus(editor_Models_LanguageResources_Resource $resource){
        $this->lastStatusInfo = '';
        if($this->api->getStatus()){
            return self::STATUS_AVAILABLE;
        }
        return self::STATUS_NOCONNECTION;
    }
}
