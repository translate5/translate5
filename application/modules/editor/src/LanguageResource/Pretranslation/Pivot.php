<?php

namespace MittagQI\Translate5\LanguageResource\Pretranslation;

use Closure;
use editor_Models_LanguageResources_LanguageResource;
use editor_Models_Segment;
use editor_Models_Segment_InternalTag;
use editor_Models_Segment_Iterator;
use editor_Models_Segment_MatchRateType;
use editor_Models_Segment_RepetitionHash;
use editor_Models_SegmentField;
use editor_Models_Task;
use editor_Models_Terminology_Models_TermModel;
use editor_Services_Connector;
use editor_Services_Connector_Abstract;
use editor_Services_Connector_FilebasedAbstract;
use editor_Services_Manager;
use editor_Services_ServiceResult;
use Exception;
use MittagQI\Translate5\LanguageResource\TaskPivotAssociation;
use stdClass;
use Zend_Db_Statement_Exception;
use Zend_Registry;
use ZfExtended_ErrorCodeException;
use ZfExtended_Factory;
use ZfExtended_Logger_DebugTrait;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Utils;

class Pivot
{
    use ZfExtended_Logger_DebugTrait;

    const MAX_ERROR_PER_CONNECTOR = 2;

    private $connectorErrorCount = [];

    private array $connectors = [];

    private array $mtConnectors = [];

    private array $resources = [];

    private bool $batchQuery;

    private bool $usePretranslateTMAndTerm = true;

    /***
     * Pretranslate with mt priority only when the tm pretranslation matchrate is not over the $pretranslateMatchrate
     * @var boolean
     */
    protected $usePretranslateMT = true;

    /***
     * Minimum language resources result match-rate for segment pre-translation
     * @var integer
     */
    protected $pretranslateMatchrate = 100;

    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $internalTag;

    /***
     *
     * @var string
     */
    protected string $userGuid;

    /***
     *
     * @var string
     */
    protected string $userName;

    public function __construct(private editor_Models_Task $task)
    {
        $this->initLogger('E1100', 'languageresources.pretranslation', '', 'Pivot pre-translation: ');
        $this->batchQuery = (boolean) Zend_Registry::get('config')->runtimeOptions->LanguageResources->Pretranslation->enableBatchQuery;
        $this->internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');

        $taskConfig = $this->task->getConfig();
        $this->usePretranslateMT = (boolean) $taskConfig->runtimeOptions->LanguageResources->Pretranslation->pivot->pretranslateMtDefault;
    }

    /***
     * Init the languageResource connectors
     *
     * @return array
     * @throws \ZfExtended_Models_Entity_NotFoundException|\editor_Models_ConfigException
     */
    protected function initConnectors(): array
    {
        /** @var TaskPivotAssociation $assoc */
        $assoc = ZfExtended_Factory::get('MittagQI\Translate5\LanguageResource\TaskPivotAssociation');
        $assocs = $assoc->loadTaskAssociated($this->task->getTaskGuid());

        $availableConnectorStatus = [
            editor_Services_Connector_Abstract::STATUS_AVAILABLE,
            //NOT_LOADED must be also considered as AVAILABLE, since OpenTM2 Tms are basically not loaded and therefore we can not decide if they are usable or not
            editor_Services_Connector_Abstract::STATUS_NOT_LOADED
        ];

        if (empty($assocs)) {
            return [];
        }

        $manager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $manager editor_Services_Manager */

        foreach ($assocs as $assoc) {

            $languageresource = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
            /* @var editor_Models_LanguageResources_LanguageResource $languageresource */

            $languageresource->load($assoc['languageResourceId']);


            $resource = $manager->getResource($languageresource);

            $connector = null;
            try {
                $connector = $manager->getConnector($languageresource, $this->task->getSourceLang(), $this->task->getTargetLang(), $this->task->getConfig());

                // set the analysis running user to the connector
                $connector->setWorkerUserGuid($this->userGuid);

                //throw a warning if the language resource is not available
                $status = $connector->getStatus($resource);
                if (!in_array($status, $availableConnectorStatus)) {
                    $this->log->warn('E1239', 'Language resource "{name}" has status "{status}" and is not available for pivot pre-translations.', [
                        'task' => $this->task,
                        'name' => $languageresource->getName(),
                        'status' => $status,
                        'moreInfo' => $connector->getLastStatusInfo(),
                        'languageResource' => $languageresource,
                    ]);
                    continue;
                }


                // for batch query supported resources, set the content field to relais. Basedo on the content field,
                // we check if the field is empty. Pretranslation is posible only for empty content fields
                $connector->setAdapterBatchContentField(editor_Models_SegmentField::TYPE_RELAIS);

                //collect the mt resource, so it can be used for pretranslations if needed
                if ($resource->getType() === editor_Models_Segment_MatchRateType::TYPE_MT) {
                    $this->mtConnectors[] = $connector;
                }
                //store the languageResource
                $this->resources[$languageresource->getId()] = $languageresource;
            } catch (Exception $e) {

                //FIXME this try catch should not be needed anymore, after refactoring of December 2020

                $errors = [];
                //if the exception is of type ZfExtended_ErrorCodeException, get the additional exception info, and log it
                if ($e instanceof ZfExtended_ErrorCodeException) {
                    $errors = $e->getErrors() ?? [];
                }
                $this->log->warn('E1102', 'Unable to use connector from Language Resource "{name}". Error was: "{msg}".', array_merge([
                    'task' => $this->task,
                    'name' => $languageresource->getName(),
                    'msg' => $e->getMessage(),
                    'languageResource' => $languageresource,
                ], $errors));
                $this->log->exception($e, [
                    'task' => $this->task,
                    'level' => $this->log::LEVEL_WARN,
                    'domain' => $this->log->getDomain(),
                ]);
                continue;
            }

            $this->connectors[$assoc['languageResourceId']] = $connector;
        }
        return $this->connectors;
    }

    /**
     *
     * @param Closure|null $progressCallback : call to update the workerModel progress. It expects progress as argument (progress = 100 / task segment count)
     * @return boolean
     * @throws \ZfExtended_Models_Entity_NotFoundException|\editor_Models_ConfigException
     */
    public function pretranslate(Closure $progressCallback = null): bool
    {
        // create a segment-iterator to get all segments of this task as a list of editor_Models_Segment objects
        $segments = ZfExtended_Factory::get('editor_Models_Segment_Iterator', [$this->task->getTaskGuid()]);
        /* @var $segments editor_Models_Segment_Iterator */

        $this->initConnectors();

        if (empty($this->connectors)) {
            return false;
        }

        $segmentCounter = 0;

        foreach ($segments as $segment) {
            /* @var $segment editor_Models_Segment */

            $segmentCounter++;

            //progress to update
            $progress = $segmentCounter / $this->task->getSegmentCount();

            // ignore the segments with relais content
            if(!empty($segment->get('relais'))){
                //report progress update
                if(null !== $progressCallback){
                    call_user_func($progressCallback,[$progress]);
                }

                continue;
            }

            //get the best match rate, respecting repetitions
            $bestMatchRateResult = $this->calculateMatchrate($segment);

            $useMt = empty($bestMatchRateResult) || $bestMatchRateResult->matchrate < $this->pretranslateMatchrate;

            $mtUsed = $this->usePretranslateMT && $useMt;
            if ($mtUsed) {

                // when mt is used, the request to the resource is not done before, we do it now
                $bestMatchRateResult = $this->getMtResult($segment);

                if (empty($bestMatchRateResult)) {
                    //ensure that falsy values are converted to null
                    $bestMatchRateResult = null;
                }
            }
            //if no mt is used but the matchrate is lower than the pretranslateMatchrate (match lower than pretranslateMatchrate comming from the TM)
            if (!$mtUsed && !empty($bestMatchRateResult) && $bestMatchRateResult->matchrate < $this->pretranslateMatchrate) {
                $bestMatchRateResult = null;
            }

            //if best matchrate results are found
            if (!empty($bestMatchRateResult)) {
                $this->updateSegment($segment, $bestMatchRateResult);
            }

            //report progress update
            if(null !== $progressCallback){
                call_user_func($progressCallback,[$progress]);
            }
        }

        $this->clean();

        return true;
    }

    /***
     * @param editor_Models_Segment $segment
     * @return stdClass|null
     */
    protected function calculateMatchrate(editor_Models_Segment $segment): ?stdClass
    {
        return $this->getBestResult($segment);
    }

    /**
     * Get best result (best matchrate) for the segment. If $saveAnalysis is provided, for each best match rate for the tm,
     * one analysis will be saved
     *
     * @param editor_Models_Segment $segment
     * @return NULL|stdClass
     */
    protected function getBestResult(editor_Models_Segment $segment): ?stdClass
    {
        $bestMatchRateResult = null;
        $bestMatchRate = null;

        //query the segment for each assigned tm
        foreach ($this->connectors as $languageResourceid => $connector) {
            /* @var $connector editor_Services_Connector */

            if ($this->isDisabledDueErrors($connector, $languageResourceid)) {
                continue;
            }

            //if the current connector supports batch query, enable the batch query for this connector
            if ($connector->isBatchQuery() && $this->batchQuery) {
                $connector->enableBatch();
            }

            $connector->resetResultList();
            $isMtResource = $this->resources[$languageResourceid]->getResourceType() == editor_Models_Segment_MatchRateType::TYPE_MT;

            try {
                $matches = $this->getMatches($connector, $segment, $isMtResource);
            } catch (Exception $e) {
                $this->handleConnectionError($e, $languageResourceid);
                // in case of an error we produce an empty result container for that query and log the error so that the analysis can proceed
                $matches = ZfExtended_Factory::get('editor_Services_ServiceResult');
            }

            $matchResults = $matches->getResult();

            $matchRateInternal = new stdClass();
            $matchRateInternal->matchrate = null;
            //for each match, find the best match rate, and save it
            foreach ($matchResults as $match) {
                if ($matchRateInternal->matchrate > $match->matchrate) {
                    continue;
                }

                // If the matchrate is the same, we only check for a new best match if it is from a termcollection
                if ($matchRateInternal->matchrate == $match->matchrate && $match->languageResourceType != editor_Models_Segment_MatchRateType::TYPE_TERM_COLLECTION) {
                    continue;
                }

                if ($match->languageResourceType == editor_Models_Segment_MatchRateType::TYPE_TERM_COLLECTION) {
                    // - preferred terms > permitted terms
                    // - if multiple permitted terms: take the first
                    if (!is_null($bestMatchRateResult) && $bestMatchRateResult->languageResourceType == editor_Models_Segment_MatchRateType::TYPE_TERM_COLLECTION) {
                        $bestMatchMetaData = $bestMatchRateResult->metaData;
                        $bestMatchIsPreferredTerm = editor_Models_Terminology_Models_TermModel::isPreferredTerm($bestMatchMetaData['status']);
                        if ($bestMatchIsPreferredTerm) {
                            continue;
                        }
                    }
                    // - only allow preferred and permitted terms for best matches
                    $metaData = $match->metaData;
                    $matchIsPreferredTerm = editor_Models_Terminology_Models_TermModel::isPreferredTerm($metaData['status']);
                    $matchIsPermittedTerm = editor_Models_Terminology_Models_TermModel::isPermittedTerm($metaData['status']);
                    if (!$matchIsPreferredTerm && !$matchIsPermittedTerm) {
                        continue;
                    }
                }

                $matchRateInternal = $match;
                //store best match rate results(do not compare agains the mt results)
                if ($matchRateInternal->matchrate > $bestMatchRate && !$isMtResource) {
                    $bestMatchRateResult = $match;
                    $bestMatchRateResult->internalLanguageResourceId = $languageResourceid;
                }
            }

            //no match rate is found in the languageResource result
            if ($matchRateInternal->matchrate == null) {
                $matches->resetResult();
                continue;
            }

            //$matchRateInternal contains always the highest matchrate from $matchResults
            //update the bestmatchrate if $matchRateInternal contains highest matchrate
            if ($matchRateInternal->matchrate > $bestMatchRate) {
                $bestMatchRate = $matchRateInternal->matchrate;
            }

            //reset the result collection
            $matches->resetResult();
        }

        return $bestMatchRateResult;
    }

    /**
     * @param editor_Services_Connector $connector
     * @param editor_Models_Segment $segment
     * @param bool $isMtResource
     * @return editor_Services_ServiceResult
     */
    protected function getMatches(editor_Services_Connector $connector, editor_Models_Segment $segment,bool $isMtResource): editor_Services_ServiceResult
    {
        if ($isMtResource === false) {
            // if the current resource type is not MT, query the tm or termcollection
            return $connector->query($segment);
        }

        //the resource is of type mt, so we do not need to query the mt for results, since we will receive always the default MT defined matchrate
        //the mt resource only will be searched when pretranslating

        //get the query string from the segment
        $queryString = $connector->getQueryString($segment);

        $internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        /* @var $internalTag editor_Models_Segment_InternalTag */
        $queryString = $internalTag->toXliffPaired($queryString, true);
        $matches = ZfExtended_Factory::get('editor_Services_ServiceResult', [
            $queryString
        ]);
        /* @var $dummyResult editor_Services_ServiceResult */
        $matches->setLanguageResource($connector->getLanguageResource());
        $matches->addResult('', $connector->getDefaultMatchRate());
        return $matches;
    }


    /***
     * Query the segment using the Mt engines assigned to the task.
     * Ony the first mt engine will be used
     * @param editor_Models_Segment $segment
     * @return NULL|[stdClass]
     */
    protected function getMtResult(editor_Models_Segment $segment){
        if(empty($this->mtConnectors)){
            return null;
        }
        //INFO: use the first connector, since no mt engine priority exist
        $connector = $this->mtConnectors[0];
        /* @var $connector editor_Services_Connector */

        //if the current connector supports batch query, enable the batch query for this connector
        if($connector->isBatchQuery() && $this->batchQuery){
            $connector->enableBatch();
        }

        $connector->resetResultList();
        $matches = $connector->query($segment);
        $matchResults=$matches->getResult();
        if(!empty($matchResults)){
            $result=$matchResults[0];
            $result->internalLanguageResourceId=$connector->getLanguageResource()->getId();
            $result->isMT = true;
            return $result;
        }
        return null;
    }

    /***
     * Use the given TM analyse (or MT if analyse was empty) result to update the segment
     * Update the segment only if it is not TRANSLATED
     *
     * @param editor_Models_Segment $segment
     * @param stdClass $result - match resources result
     *
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    protected function updateSegment(editor_Models_Segment $segment, stdClass $result): void
    {

        //if the segment target is not empty or best match rate is not found do not pretranslate
        //pretranslation only for editable segments
        if($segment->meta()->getLocked() || !empty($segment->get('relais'))){
            return;
        }

        //the internalLanguageResourceId is set when the segment bestmatchrate is found(see analysis getbestmatchrate function)
        $languageResourceid=$result->internalLanguageResourceId;

        $history = $segment->getNewHistoryEntity();

        $relaisResult=$result->target;

        //set the type
        $languageResource = $this->resources[$languageResourceid];
        /* @var $languageResource editor_Models_LanguageResources_LanguageResource */

        $hasText = $this->internalTag->hasText($segment->getSource());
        if($hasText) {
            //if the result language resource is termcollection, set the target result first character to uppercase
            if($this->isTermCollection($languageResourceid)){
                $relaisResult=ZfExtended_Utils::mb_ucfirst($relaisResult);
            }
            $relaisResult = $this->internalTag->removeIgnoredTags($relaisResult);
        }
        else {
            //if the source contains no text but tags only, we set the target to the source directly
            // and the segment is not editable
            $relaisResult = $segment->getSource();
        }
        //check if the result is valid for log
        if($this->isResourceLogValid($languageResource, $segment->getMatchRate())){
            $this->connectors[$languageResourceid]->logAdapterUsage($segment, false);
        }

        $segment->set('relais',$relaisResult); //use sfm->getFirstTargetName here

        $segment->updateToSort('relais');

        $segment->setUserGuid($this->userGuid);//to the authenticated userGuid
        $segment->setUserName($this->userName);//to the authenticated userName


        if($this->task->getWorkflowStep() == 1){
            $hasher = ZfExtended_Factory::get('editor_Models_Segment_RepetitionHash', [$this->task]);
            /* @var $hasher editor_Models_Segment_RepetitionHash */
            //calculate and set segment hash
            $segmentHash = $hasher->rehashRelais($segment,$relaisResult);
            $segment->setRelaisMd5($segmentHash);
        }

        $duration=new stdClass();
        $duration->relais=0;
        $segment->setTimeTrackData($duration);

        $history->save();
        $segment->setTimestamp(NOW_ISO);
        $segment->save();
    }


    /**
     * Checks how many errors the connector has produced. If too much, disable it.
     *
     * @param editor_Services_Connector $connector
     * @param integer $id
     * @return boolean
     */
    protected function isDisabledDueErrors(editor_Services_Connector $connector,int $id): bool
    {
        //check if the connector itself is disabled
        if ($this->connectors[$id]->isDisabled()) {
            return true;
        }

        if (!isset($this->connectorErrorCount[$id]) || $this->connectorErrorCount[$id] <= self::MAX_ERROR_PER_CONNECTOR) {
            return false;
        }

        $langRes = $connector->getLanguageResource();
        $this->log->warn('E1101', 'Disabled Language Resource {name} ({service}) for pivot pretranslation due too much errors.', [
            'task' => $this->task,
            'languageResource' => $langRes,
            'name' => $langRes->getName(),
            'service' => $langRes->getServiceName(),
        ]);
        $this->connectors[$id]->disable();
        return true;
    }

    /**
     * Log and count the connection error
     * @param Exception $e
     * @param int $id
     */
    protected function handleConnectionError(Exception $e, int $id): void
    {
        $this->log->exception($e, [
            'level' => $this->log::LEVEL_WARN,
            'domain' => $this->log->getDomain(),
            'task' => $this->task,
        ]);
        settype($this->connectorErrorCount[$id], 'integer');
        $this->connectorErrorCount[$id]++;
    }


    /***
     * Check if the given language resource id is a valid termcollection resource
     * @param int $languageResourceId
     * @return boolean
     */
    protected function isTermCollection(int $languageResourceId): bool
    {
        if(!isset($this->resources[$languageResourceId])){
            return false;
        }
        return $this->resources[$languageResourceId]->isTc();
    }

    /***
     * Should the current language resources result with matchrate be logged in the languageresources ussage log table
     *
     * @param editor_Models_LanguageResources_LanguageResource $languageResource
     * @param int $matchRate
     * @return boolean
     */
    protected function isResourceLogValid(editor_Models_LanguageResources_LanguageResource $languageResource, int $matchRate): bool
    {
        //check if it is tm or tc, an if the matchrate is >= 100
        return ($languageResource->isTm() || $languageResource->isTc()) && $matchRate>=editor_Services_Connector_FilebasedAbstract::EXACT_MATCH_VALUE;
    }

    /***
     * Remove not required analysis object and data
     */
    public function clean(): void
    {
        $this->connectors = [];
    }

    /**
     * @return string
     */
    public function getUserGuid(): string
    {
        return $this->userGuid;
    }

    /**
     * @param string $userGuid
     */
    public function setUserGuid(string $userGuid): void
    {
        $this->userGuid = $userGuid;
    }

    /**
     * @return string
     */
    public function getUserName(): string
    {
        return $this->userName;
    }

    /**
     * @param string $userName
     */
    public function setUserName(string $userName): void
    {
        $this->userName = $userName;
    }
}