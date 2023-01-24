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
     * Buffer size in KB or false to disable the size calculation
     * @var bool|integer
     */
    protected $batchQueryBufferSize = false;
    
    /**
     * container for collected exceptions
     * @var array
     */
    protected $batchExceptions = [];

    /***
     * Where to check if the segment field has content (only possible target and pivot).
     * This is MT only relevant because for segments where the $contentField contains data (ex: target field has
     * content) no query should be done.
     * @var string
     */
    private string $contentField = editor_Models_SegmentField::TYPE_TARGET;

    private editor_Models_Segment $lastDefaultSegmentSet;

    /**
     * returns the collected batchExceptions or an empty array
     * @return array
     */
    public function getBatchExceptions(): array
    {
        return $this->batchExceptions;
    }
    
    /***
     * Query the resource with multiple segments at once, and save the results in the database.
     * @param string $taskGuid
     */
    public function batchQuery(string $taskGuid, Closure $progressCallback = null){
        $segments = ZfExtended_Factory::get('editor_Models_Segment_Iterator', [$taskGuid]);
        /* @var $segments editor_Models_Segment_Iterator */
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        
        //number of temporary cached segments
        $tmpBuffer = 0;
        //holds the query strings for batch request
        $batchQuery = [];
        $this->batchExceptions = [];
        
        $segmentCounter = 0;
        $progress = 0;
        $bufferSize = 0;
        foreach ($segments as $segment){
            
            $segmentCounter++;
            
            //progress to update
            $progress = $segmentCounter / $task->getSegmentCount();
            
            //For pre-translation only those segments should be send to the MT, that have an empty target.-> https://jira.translate5.net/browse/TRANSLATE-2335
            //For analysis, the mt matchrate will always be the same.So it make no difference here if it is pretranslation
            //or analysis, the empty target segments for mt resources should not be send to batch processor
            //TODO: in future, when the matchrate is provided/calculated for mt, this should be changed



            $contentField = $segment->get($this->getContentField());

            // check if the contentField already has translations/data
            if(strlen($contentField) > 0 && $this->languageResource->isMt()){
                continue;
            }
            $querySegment = $this->tagHandler->prepareQuery($this->getQueryString($segment), $segment->getId());
            $batchQuery[] = [
                //set the query string to segment map. Later it will be used to reapply the tags
                'segment' => clone $segment,
                //collect the source text
                'query' => $querySegment,
                'tagMap' => $this->tagHandler->getTagMap(),
            ];

            // collect the segment size in bytes in temporary variable
            $bufferSize += $this->getQuerySegmentSize($querySegment);
            // is the collected buffer size above the allowed limit (if the buffer size limit is not allowed for the resource, this will return true)
            $allowByContent = $this->isAllowedByContentSize($bufferSize);

            if(++$tmpBuffer != $this->batchQueryBuffer && $allowByContent){
                continue;
            }

            // if the content is above the allowed buffer, remove the last segment from the batchQuery, and save it for the next loop
            if($allowByContent === false){
                // get the last query segment
                $resetBuffer = $batchQuery[count($batchQuery)-1];
                // remove the last query segment from the array (since the size is over the allowed limit)
                array_pop($batchQuery);
                //send batch query request, and save the results to the batch cache
                $this->handleBatchQuerys($batchQuery);
                $progressCallback && $progressCallback($progress);
                $batchQuery = [];
                $batchQuery[] = $resetBuffer;

                // set the current buffer size to the last segment size
                $bufferSize = $this->getQuerySegmentSize($querySegment);
            }else{
                //send batch query request, and save the results to the batch cache
                $this->handleBatchQuerys($batchQuery);

                $progressCallback && $progressCallback($progress);

                $batchQuery = [];
                $bufferSize = 0;
            }
            $tmpBuffer = 0;
        }
        
        //query the rest, if there are any:
        if(!empty($batchQuery)) {
            $this->handleBatchQuerys($batchQuery);
            $progressCallback && $progressCallback($progress);
        }
    }

    /***
     * Check if calculate content size exceeds the allowed limit
     * @param int $totalContentSize
     * @return bool
     */
    protected function isAllowedByContentSize(int $totalContentSize): bool
    {
        if(is_numeric($this->batchQueryBufferSize) === false){
            return true;
        }
        return $totalContentSize < $this->batchQueryBufferSize;
    }

    /***
     * Return the queried segment size in KB
     * @param string $querySegment
     * @return float|bool|int
     */
    protected function getQuerySegmentSize(string $querySegment): float|bool|int
    {
        if(is_numeric($this->batchQueryBufferSize) === false){
            return 0;
        }
        return strlen(urlencode($querySegment)) / 1024;
    }
    
    /**
     * Batch query request for $queryStrings and saving the results for each translation.
     * This is only template function. Override this in each connector if the connector supports batch
     * query requests.
     * @param array $batchQuery
     */
    protected function handleBatchQuerys(array $batchQuery) {
        $sourceLang = $this->getSourceLanguageCode();
        $targetLang = $this->getTargetLanguageCode();
        $this->resultList->resetResult();
        
        //we handle only our own exceptions, since the connector should only throw such
        try {
            if(!$this->batchSearch(array_column($batchQuery, 'query'), $sourceLang, $targetLang)) {
                return;
            }
        } catch(ZfExtended_ErrorCodeException $e) {
            //we collect the exceptions for further processing
            $this->batchExceptions[] = $e;
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
            $segmentId = $query['segment']->getId();
            $this->getQueryStringAndSetAsDefault($query['segment']);
            $this->tagHandler->setTagMap($query['tagMap']);
            $this->processBatchResult($segmentResults);
            
            $this->logForSegment($query['segment']);
            
            $this->saveBatchResults($segmentId);

            //log the adapter usage for the batch query segment
            $this->logAdapterUsage($query['segment']);
            
            $this->resultList->resetResult();
        }
    }

    /**
     * get query string from segment and set it as result default source
     * @param editor_Models_Segment $segment
     * @return string
     */
    protected function getQueryStringAndSetAsDefault(editor_Models_Segment $segment): string
    {
        $this->lastDefaultSegmentSet = $segment;
        return parent::getQueryStringAndSetAsDefault($segment);
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
        Zend_Db_Table::getDefaultAdapter()->insert('LEK_languageresources_batchresults', [
            'languageResource' =>$this->languageResource->getId(),
            'segmentId'=>$segmentId,
            'result'=>serialize($this->resultList)
        ]);
    }
    
    /***
     * If the batch query buffer is set for more then 1 segment, then this connector should support batch query requests
     * @return boolean
     */
    public function isBatchQuery(): bool {
        return $this->batchQueryBuffer > 1;
    }

    /**
     * @return string
     */
    public function getContentField(): string
    {
        return $this->contentField;
    }

    /**
     * @param string $contentField
     */
    public function setContentField(string $contentField): void
    {
        $this->contentField = $contentField;
    }
}
