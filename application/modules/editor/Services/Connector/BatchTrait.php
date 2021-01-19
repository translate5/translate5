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
 * Provides reusable batch functionality for LanguageResource Service Connectors
 * @see editor_Services_Connector_Abstract
 * @property editor_Services_Connector_TagHandler_Abstract $tagHandler
 * @property editor_Models_LanguageResources_LanguageResource $languageResource
 *
 */
trait editor_Services_Connector_BatchTrait {
    
    /***
     * Number of segments which the batch query sends at once
     * @var integer
     */
    protected $batchQueryBuffer = 1;
    
    /***
     * Query the resource with multiple segments at once, and save the results in the database.
     * @param string $taskGuid
     */
    public function batchQuery(string $taskGuid){
        $segments = ZfExtended_Factory::get('editor_Models_Segment_Iterator', [$taskGuid]);
        /* @var $segments editor_Models_Segment_Iterator */
        
        //number of temporary cached segments
        $tmpBuffer = 0;
        //holds the query strings for batch request
        $batchQuery = [];
        foreach ($segments as $segment){
            
            //For pre-translation only those segments should be send to the MT, that have an empty target.-> https://jira.translate5.net/browse/TRANSLATE-2335
            //For analysis, the mt matchrate will always be the same.So it make no difference here if it is pretranslation
            //or analysis, the empty target segments for mt resources should not be send to batch processor
            //TODO: in future, when the matchrate is provided/calculated for mt, this should be changed
            if(!empty($segment->getTarget()) && $this->languageResource->isMt()){
                continue;
            }
            $batchQuery[] = [
                //set the query string to segment map. Later it will be used to reapply the taks
                'segment' => clone $segment,
                //collect the source text
                'query' => $this->tagHandler->prepareQuery($this->getQueryString($segment)),
                'tagMap' => $this->tagHandler->getTagMap(),
            ];
            
            if(++$tmpBuffer != $this->batchQueryBuffer){
                continue;
            }
            
            $tmpBuffer=0;
            
            //send batch query request, and save the results to the batch cache
            $this->handleBatchQuerys($batchQuery);
            $batchQuery = [];
        }
        
        //query the rest, if there are any:
        if(!empty($batchQuery)) {
            $this->handleBatchQuerys($batchQuery);
        }
    }
    
    /**
     * Batch query request for $queryStrings and saving the results for each translation.
     * This is only template function. Override this in each connector if the connector supports batch
     * query requests.
     * @param array $batchQuery
     */
    protected function handleBatchQuerys(array $batchQuery) {
        $sourceLang = $this->languageResource->getSourceLangCode();
        $targetLang = $this->languageResource->getTargetLangCode();
        $this->resultList->resetResult();
        
        if(!$this->batchSearch(array_column($batchQuery, 'query'), $sourceLang, $targetLang)) {
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
            $query = array_shift($batchQuery);
            /* @var $segment editor_Models_Segment */
            
            $this->getQueryStringAndSetAsDefault($query['segment']);
            $this->tagHandler->setTagMap($query['tagMap']);
            $this->processBatchResult($segmentResults);
            
            $this->logForSegment($query['segment']);
            
            $this->saveBatchResults($query['segment']->getId());
            $this->resultList->resetResult();
        }
    }

    /**
     * Sends the prepared queryStrings as a batch to the language resource
     * @param array $queryStrings
     * @param string $sourceLang
     * @param string $targetLang
     * @return bool
     */
    abstract protected function batchSearch(array $queryStrings, string $sourceLang, string $targetLang): bool;
    
    /**
     * process (add to the result list and decode) results from the language resource
     * @param mixed $segmentResults
     */
    abstract protected function processBatchResult($segmentResults);
    
    /**
     * @param int $segmentId
     */
    protected function saveBatchResults(int $segmentId) {
        $model = ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_BatchResult');
        /* @var $model editor_Plugins_MatchAnalysis_Models_BatchResult */
        $model->setLanguageResource($this->languageResource->getId());
        $model->setSegmentId($segmentId);
        $model->setResult(serialize($this->resultList));
        $model->save();
    }
    
    /***
     * If the batch query buffer is set for more then 1 segment, then this connector should support batch query requests
     * @return boolean
     */
    public function isBatchQuery(): bool {
        return $this->batchQueryBuffer > 1;
    }
}
