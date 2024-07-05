<?php
/*
 START LICENSE AND COPYRIGHT

  This file is part of translate5

  Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use MittagQI\Translate5\LanguageResource\Adapter\Exception\SegmentUpdateException;
use MittagQI\Translate5\LanguageResource\Status;

/**
 * After importing a task a match analysis will be created based on the assigned TM based MatchRessources.
 * To get the analysis results, each segment is send to the assigned MatchRessources. For each queried Sprachressource
 * the received best match rate is stored in a separate DB table. Out of this table all desired analysis are
 * calculated.
 */
class editor_Plugins_MatchAnalysis_Analysis extends editor_Plugins_MatchAnalysis_Pretranslation
{
    public const MAX_ERROR_PER_CONNECTOR = 2;

    /***
     * Analysis id
     *
     * @var mixed
     */
    protected $analysisId;

    /***
     * Flag if pretranslations is active
     * @var string
     */
    protected $pretranslate = false;

    /***
     * Flag if internal fuzzy will be calculated
     * @var string
     */
    protected $internalFuzzy = false;

    protected $connectorErrorCount = [];

    /**
     * Contains an array of segment IDs which have at least one repetition
     * @var array
     */
    protected $segmentIdsWithRepetitions = [];

    /**
     * Contains the bestMatchResult to a segment source hash
     * @var array
     */
    protected $repetitionByHash = [];

    /**
     * Contains the master segment to a segment source hash
     * @var array
     */
    protected $repetitionMasterSegments = [];

    /**
     * Holds the repetition updater
     * @var editor_Models_Segment_RepetitionUpdater
     */
    protected $repetitionUpdater;

    private editor_Services_Manager $manager;

    /**
     * @param integer $analysisId
     */
    public function __construct(editor_Models_Task $task, $analysisId)
    {
        $this->task = $task;
        $this->analysisId = $analysisId;
        $this->sfm = editor_Models_SegmentFieldManager::getForTaskGuid($task->getTaskGuid());
        $this->manager = ZfExtended_Factory::get(editor_Services_Manager::class);

        parent::__construct($analysisId);
    }

    /**
     * Query the language resource service for each segment, calculate the best match rate, and save the match analysis
     * model
     *
     * @param Closure|null $progressCallback : call to update the workerModel progress. It expects progress as argument
     *     (progress = 100 / task segment count)
     * @return boolean
     */
    public function analyseAndPretranslate(Closure $progressCallback = null): bool
    {
        // create a segment-iterator to get all segments of this task as a list of editor_Models_Segment objects
        $segments = ZfExtended_Factory::get(editor_Models_Segment_Iterator::class, [
            $this->task->getTaskGuid(),
        ]);
        $segments->setIgnoreBlockedSegments(true);

        $this->initConnectors();

        if (empty($this->hasConnectors())) {
            return false;
        }
        $this->initRepetitions();

        $segmentCounter = 0;

        foreach ($segments as $segment) {
            /* @var $segment editor_Models_Segment */

            $segmentCounter++;

            //progress to update
            $progress = $segmentCounter / $this->task->getSegmentCount();

            //get the best match rate, respecting repetitions
            $bestMatchRateResult = $this->calculateMatchrate($segment);

            if (! $this->pretranslate) {
                //report progress update
                $progressCallback && $progressCallback($progress);

                continue;
            }
            //if TM and Term pretranslation should not be used, we set it null here to trigger MT (if enabled)
            if (! $this->usePretranslateTMAndTerm) {
                $bestMatchRateResult = null;
            }
            $useMt = empty($bestMatchRateResult) || $bestMatchRateResult->matchrate < $this->pretranslateMatchrate;
            $mtUsed = $this->usePretranslateMT && $useMt;
            if ($mtUsed) {
                $hasRepetitions = in_array($segment->getId(), $this->segmentIdsWithRepetitions);

                //if have already a MT result, since it is a repetition, then use that, instead of fetching again
                if ($this->repetitionByHash[$segment->getSourceMd5()]?->isMT ?? false) {
                    $bestMatchRateResult = $this->repetitionByHash[$segment->getSourceMd5()];
                } else {
                    $bestMatchRateResult = $this->getMtResult($segment);
                }

                if (empty($bestMatchRateResult)) {
                    //ensure that falsy values are converted to null
                    $bestMatchRateResult = null;
                } else {
                    //store the result for the repetitions, but only if there is not already a repeated result found
                    if ($hasRepetitions) {
                        //if we are a repetition and no master was found before, then we set it
                        if (empty($this->repetitionMasterSegments[$segment->getSourceMd5()])) {
                            $this->repetitionMasterSegments[$segment->getSourceMd5()] = clone $segment;
                        }
                        //if there was no repetition result found at all or it was no MT, then we reset it
                        $rep = $this->repetitionByHash[$segment->getSourceMd5()] ?? null;
                        if (empty($rep) || ! ($rep->isMT ?? false)) {
                            // if tags could not be applied, then getMtResult should be called again
                            $this->repetitionByHash[$segment->getSourceMd5()] = $bestMatchRateResult;
                        }
                        $master = $this->repetitionMasterSegments[$segment->getSourceMd5()] ?? null;
                        //if we are processing a repetition, we have to fix the tags:
                        if ($rep && $master && $master->getId() !== $segment->getId()) {
                            $bestMatchRateResult = $this->updateTargetOfRepetition($segment, $rep) ?? $this->getMtResult($segment);
                        }
                    }
                }
            }
            //if no mt is used but the matchrate is lower than the pretranslateMatchrate (match lower than pretranslateMatchrate comming from the TM)
            if (! $mtUsed && ! empty($bestMatchRateResult) && $bestMatchRateResult->matchrate < $this->pretranslateMatchrate) {
                $bestMatchRateResult = null;
            }

            /*
            ** TODO on refactoring:
            ** - currently all information is packed into the $bestMatchRateResult, but not in an additive way, but in an overwritten way
             *   that means for example for repetitions: it contains the original match data, but the matchrate is overwritten by 102%
             * - above the information is calculated: Should the segment updated yes or no, and this info is stored by  having a bestMatchRate or not
             *   better would it be to always have a result object, containing all needed information, including the calculated info update me yes or no
             * - Fix the class structure here and break code in smaller pieces
             * - Probably it will be the best to:
             *   - Loop over all segments, on repetitions consider only the master segments and ignore the repetitions
             *   - Loop over all repeated segments (where the masters also are contained) then duplicate the content if possible/and wanted into the repetitions
             * - introduce a defined class for the result instead of juggling with stdClass
            */

            //if best matchrate results are found
            if (! empty($bestMatchRateResult)) {
                //DIRTY but this is the only place where we know if a master of a repetition should be finally updated or not
                // if yes, then the repetitions should also be updated, if the master is not updated (due what ever) then the repetitions should also not be updated
                $master = $this->repetitionMasterSegments[$segment->getSourceMd5()] ?? null;
                $rep = $this->repetitionByHash[$segment->getSourceMd5()] ?? null;
                $isMaster = $rep && $master && $master->getId() === $segment->getId();
                $isRepetition = $rep && $master && $master->getId() !== $segment->getId();
                if ($isMaster) {
                    //only update repetitions if the master was updated too, which is here the case
                    // set the updateMe in the shared repetition result for all repetitions
                    $rep->updateMe = true;
                }
                //update the segment only if, it was no repetition, or the master of the repetition was updated too
                if (empty($rep) || ($rep->updateMe ?? false)) {
                    $this->updateSegment($segment, $bestMatchRateResult, $isRepetition);
                }
            }
            //report progress update
            $progressCallback && $progressCallback($progress);
        }

        if (! empty($segment)) {
            $segment->syncRepetitions($this->task->getTaskGuid());
        }

        $this->clean();

        return true;
    }

    /**
     * calculates the segments matchrate, respecting repetitions and fuzzy matched and handles them if needed
     * @throws editor_Models_ConfigException
     */
    protected function calculateMatchrate(editor_Models_Segment $segment): ?stdClass
    {
        //calculate and set segment hash
        $segmentHash = $segment->getSourceMd5();

        //lazy init, we need only instance, the here given $segment will be overwritten wuth the updateRepetition call
        if (empty($this->repetitionUpdater)) {
            $this->repetitionUpdater = ZfExtended_Factory::get('editor_Models_Segment_RepetitionUpdater', [$segment, $this->task->getConfig()]);
        }

        //check if the segment source hash exist in the repetition array
        //segment exist in the repetition array -> it is repetition, save it as 102 (repetition) and 0 languageResource
        //segment does not exist in repetition array -> query the tm save the best match rate per tm
        $hasRepetitions = in_array($segment->getId(), $this->segmentIdsWithRepetitions);
        $isRepetition = $hasRepetitions && array_key_exists($segmentHash, $this->repetitionMasterSegments);
        if (! $isRepetition) {
            $bestResult = $this->getBestResult($segment, true);
            if (! $hasRepetitions) {
                // if the segment has no repetitions at all we just return the found result
                return $bestResult;
            }
            //the first segment of multiple repetitions is always stored as master
            $this->repetitionMasterSegments[$segmentHash] = clone $segment;

            //store the found match for repetition reusage
            return $this->repetitionByHash[$segmentHash] = $bestResult;
        }
        $masterHasResult = ! empty($this->repetitionByHash[$segmentHash]);

        // DESCRIPTION BEHAVIOUR FOR REPETITIONS
        // for the analysis, a repetition is always counted as 102% match!
        // what is taken over for pre-translation is however defined differently:
        // - if master segment matchrate < configured matchrate for pre-translation:
        //      then count the repetition as 102 in the analysis but do not touch the repeated segment, so matchrate = 0, no target, no matchtype
        // - if master segment matchrate >= configured matchrate for pre-translation:
        //      then count the repetition as 102 in the analysis AND set the repeated segment to the matchrate, target content and matchtype of the master segment
        //      the segment is also marked as pre-translated and it should be editable if a fuzzy match (which is no problem anymore since the fuzzy matchrate is taken over)

        //get the best match rate for the repetition segment, basically 102%, but:
        // it can be context match (103%) which is better as the above defined 102% repetition one
        // or the one stored for the repetition could be from a MT. So recalc here always.
        $bestResult = $this->getBestResult($segment, false);
        // we take only 102% if the master was lesser
        $repetitionRate = max(($bestResult->matchrate ?? 0), editor_Services_Connector_FilebasedAbstract::REPETITION_MATCH_VALUE);
        //save the repetition analysis with either 102% or 103% matchrate
        $this->saveAnalysis($segment, $repetitionRate, 0);

        //if there is no match we can not update the target below, this means returning null
        if (! $masterHasResult || $this->isInternalFuzzy($this->repetitionByHash[$segmentHash]->target ?? '')) {
            return null; //if the master of the repetition had no result, the repetition has no content either
        }

        $masterResult = $this->repetitionByHash[$segmentHash];
        $masterHasFullMatch = $masterResult->matchrate >= 100;

        if ($masterHasFullMatch) {
            //bestResult is fallback if tags could not be applied
            return $this->updateTargetOfRepetition($segment, $masterResult) ?? $bestResult;
        }

        //if the master was a fuzzy or the full match repetition could not be set (above updateTargetOfRepetition) properly, we keep the found matchrate and translation
        return $bestResult;
    }

    /**
     * When taking over a repetition, the content (tags) must be prepared properly before usage
     * returns null if the tags could not be applied
     */
    protected function updateTargetOfRepetition(editor_Models_Segment $segment, ?stdClass $masterResult, ?int $repetitionRate = null): ?stdClass
    {
        $segmentHash = $segment->getSourceMd5();
        if (! is_null($masterResult) && $this->repetitionUpdater->updateTargetOfRepetition($this->repetitionMasterSegments[$segmentHash], $segment)) {
            //the returning result must be the one from the first of the repetition group.
            // to get the correct content for the repetition we get the value from $segment, which was updated by the repetition updater
            // we may not update the repetitionHash, this would interfer with the other repetitions
            $bestRepeatedResult = clone $masterResult;
            $bestRepeatedResult->target = $segment->getTargetEdit();
            if (! is_null($repetitionRate)) {
                $bestRepeatedResult->matchrate = $repetitionRate; //in the case of masterHasFullMatch we use also that matchrate for the segment
            }

            return $bestRepeatedResult;
        }

        return null;
    }

    /**
     * Get best result (best matchrate) for the segment. If $saveAnalysis is provided, for each best match rate for the
     * tm, one analysis will be saved
     */
    protected function getBestResult(editor_Models_Segment $segment, bool $saveAnalysis = true): ?stdClass
    {
        $bestMatchRateResult = null;
        $bestMatchRate = null;

        //query the segment for each assigned tm
        foreach ($this->getConnectorsIterator() as $languageResourceid => $connector) {
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
                $isTermCollection = $match->languageResourceType == editor_Models_Segment_MatchRateType::TYPE_TERM_COLLECTION;
                if ($matchRateInternal->matchrate > $match->matchrate) {
                    continue;
                }

                // If the matchrate is the same, we only check for a new best match if it is from a termcollection
                // or if the match has a newer timestamp
                if ($matchRateInternal->matchrate == $match->matchrate && ! $isTermCollection) {
                    continue;
                }

                if ($isTermCollection) {
                    // - preferred terms > permitted terms
                    // - if multiple permitted terms: take the first
                    if (! is_null($bestMatchRateResult) && $bestMatchRateResult->languageResourceType == editor_Models_Segment_MatchRateType::TYPE_TERM_COLLECTION) {
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
                    if (! $matchIsPreferredTerm && ! $matchIsPermittedTerm) {
                        continue;
                    }
                }

                $matchRateInternal = $match;
                //store best match rate results(do not compare agains the mt results)
                // if match of another TM has the same >=100 matchrate, use the newer one
                if (($matchRateInternal->matchrate > $bestMatchRate
                        || $matchRateInternal->matchrate === $bestMatchRate
                        && $bestMatchRate >= 100
                        && $match->timestamp > $bestMatchRateResult->timestamp)
                    && ! $isMtResource
                ) {
                    $bestMatchRateResult = $match;
                    $bestMatchRateResult->internalLanguageResourceid = $languageResourceid;
                }
            }

            //no match rate is found in the languageResource result
            if ($matchRateInternal->matchrate == null) {
                $saveAnalysis && $this->saveAnalysis($segment, 0, $languageResourceid);
                $matches->resetResult();

                continue;
            }

            //$matchRateInternal contains always the highest matchrate from $matchResults
            //update the bestmatchrate if $matchRateInternal contains highest matchrate
            if ($matchRateInternal->matchrate > $bestMatchRate) {
                $bestMatchRate = $matchRateInternal->matchrate;
            }

            //save the match analyses if needed
            $saveAnalysis && $this->saveAnalysis($segment, $matchRateInternal, $languageResourceid);

            //reset the result collection
            $matches->resetResult();

            // Mark the segment as fuzzy match in the TM
            // Checking for matchrate >= 100 is for edge case when segments have the same-same source but different
            // source md5hash so they are not a repetitions. Updating the segment in this case would lead to omitting
            // translation for other segments with the same-same source, but different source md5hash
            if (
                $bestMatchRateResult !== null
                && $bestMatchRateResult->matchrate < 100
                && $this->internalFuzzy
                && $connector->isInternalFuzzy()
            ) {
                $origTarget = $segment->getTargetEdit();
                $dummyTargetText = self::renderDummyTargetText($segment->getTaskGuid());
                $segment->setTargetEdit($dummyTargetText);

                try {
                    $connector->update($segment);
                } catch (SegmentUpdateException) {
                    // Ignore the error here as we don't care about the result
                }

                $segment->setTargetEdit($origTarget);
            }
        }

        return $bestMatchRateResult;
    }

    /**
     * Checks how many errors the connector has produced. If too much, disable it.
     */
    protected function isDisabledDueErrors(editor_Services_Connector $connector, int $id): bool
    {
        //check if the connector itself is disabled
        if ($connector->isDisabled()) {
            return true;
        }

        if (! isset($this->connectorErrorCount[$id]) || $this->connectorErrorCount[$id] <= self::MAX_ERROR_PER_CONNECTOR) {
            return false;
        }

        $connector->disable();

        $this->log->warn(
            'E1101',
            'Disabled Language Resource {name} ({service}) for analysing and pretranslation due too much errors.',
            [
                'task' => $this->task,
                'languageResource' => $this->resources[$id],
                'name' => $this->resources[$id]->getName(),
                'service' => $this->resources[$id]->getServiceName(),
            ]
        );

        return true;
    }

    /**
     * Log and count the connection error
     * @param int $id
     */
    protected function handleConnectionError(Exception $e, $id)
    {
        $this->log->exception($e, [
            'level' => $this->log::LEVEL_WARN,
            'domain' => $this->log->getDomain(),
            'extra' => [
                'task' => $this->task,
            ],
        ]);
        settype($this->connectorErrorCount[$id], 'integer');
        $this->connectorErrorCount[$id]++;
    }

    /**
     * @param bool $isMtResource
     * @return editor_Services_ServiceResult
     */
    protected function getMatches(editor_Services_Connector $connector, editor_Models_Segment $segment, $isMtResource)
    {
        if ($isMtResource) {
            //the resource is of type mt, so we do not need to query the mt for results, since we will receive always the default MT defined matchrate
            //the mt resource only will be searched when pretranslating

            //get the query string from the segment
            $queryString = $connector->getQueryString($segment);

            $internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
            /* @var $internalTag editor_Models_Segment_InternalTag */
            $queryString = $internalTag->toXliffPaired($queryString, true);
            $matches = ZfExtended_Factory::get('editor_Services_ServiceResult', [
                $queryString,
            ]);
            /* @var $dummyResult editor_Services_ServiceResult */
            $matches->setLanguageResource($connector->getLanguageResource());
            $matches->addResult('', $connector->getDefaultMatchRate());

            return $matches;
        }

        // if the current resource type is not MT, query the tm or termcollection
        $matches = $connector->query($segment);

        return $matches;
    }

    /***
     * Save match analysis to the database
     *
     * @param editor_Models_Segment $segment
     * @param mixed $matchRateResult : it can be stdClass (opentm2 match result) or int (only the matchrate)
     * @param int $languageResourceid
     */
    protected function saveAnalysis($segment, $matchRateResult, $languageResourceid)
    {
        $matchAnalysis = ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_MatchAnalysis');
        /* @var $matchAnalysis editor_Plugins_MatchAnalysis_Models_MatchAnalysis */
        $matchAnalysis->setSegmentId($segment->getId());
        $matchAnalysis->setSegmentNrInTask($segment->getSegmentNrInTask());
        $matchAnalysis->setTaskGuid($this->task->getTaskGuid());
        $matchAnalysis->setAnalysisId($this->analysisId);
        $matchAnalysis->setLanguageResourceid($languageResourceid);
        $matchAnalysis->setWordCount($segment->meta()->getSourceWordCount());
        $matchAnalysis->setCharacterCount($segment->meta()->getSourceCharacterCount());
        $matchAnalysis->setMatchRate($matchRateResult->matchrate ?? $matchRateResult);

        if ($languageResourceid === 0) {
            $type = editor_Models_Segment_MatchRateType::TYPE_AUTO_PROPAGATED;
        } elseif (array_key_exists($languageResourceid, $this->resources)) {
            $type = $this->resources[$languageResourceid]->getResourceType();
        } else {
            $type = editor_Models_Segment_MatchRateType::TYPE_UNKNOWN;
        }

        $matchAnalysis->setType($type);

        $isFuzzy = false;
        $dummyTargetText = self::renderDummyTargetText($segment->getTaskGuid());
        if (isset($matchRateResult) && is_object($matchRateResult)) {
            //ignore internal fuzzy match target
            $isFuzzy = strpos($matchRateResult->target, $dummyTargetText) !== false;
        }
        $matchAnalysis->setInternalFuzzy($isFuzzy ? 1 : 0);
        $matchAnalysis->save();
    }

    /**
     * Inits data for repetition handling
     */
    protected function initRepetitions()
    {
        $segmentModel = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segmentModel editor_Models_Segment */
        $results = $segmentModel->getRepetitions($this->task->getTaskGuid());
        $this->segmentIdsWithRepetitions = array_column($results, 'id');
        $this->repetitionByHash = [];
        $this->repetitionMasterSegments = [];
    }

    protected function initConnectors(): void
    {
        $languageResources = ZfExtended_Factory::get(LanguageResource::class);
        $languageResourceIds = array_column(
            $languageResources->loadByAssociatedTaskGuid($this->task->getTaskGuid()),
            'id'
        );

        if (empty($languageResourceIds)) {
            return;
        }

        $availableConnectorStatus = [
            Status::AVAILABLE,
            // NOT_LOADED must be also considered as AVAILABLE,
            // since OpenTM2 Tms are basically not loaded and therefore we can not decide if they are usable or not
            Status::NOT_LOADED,
        ];

        foreach ($languageResourceIds as $languageResourceId) {
            $languageResource = ZfExtended_Factory::get(LanguageResource::class);
            $languageResource->load((int) $languageResourceId);

            $resource = $this->manager->getResource($languageResource);

            //ignore non analysable resources
            if (! $resource->getAnalysable()) {
                continue;
            }

            //store the languageResource
            $this->resources[$languageResource->getId()] = $languageResource;

            try {
                $connector = $this->getConnector($languageResource);

                $status = $connector->getStatus($resource, $languageResource);

                if (! in_array($status, $availableConnectorStatus)) {
                    $this->log->warn(
                        'E1239',
                        'MatchAnalysis Plug-In: Language resource "{name}" has status "{status}" and is not available for match analysis and pre-translations.',
                        [
                            'task' => $this->task,
                            'name' => $languageResource->getName(),
                            'status' => $status,
                            'moreInfo' => $connector->getLastStatusInfo(),
                            'languageResource' => $languageResource,
                        ]
                    );

                    continue;
                }

                // collect the mt resource, so it can be used for pre-translations if needed
                if ($resource->getType() == editor_Models_Segment_MatchRateType::TYPE_MT) {
                    $this->mtConnectors[] = $connector;
                }

                $this->addConnector((int) $languageResource->getId(), $connector);

                if ($this->internalFuzzy) {
                    $this->addInternalFuzzyConnector($languageResource);
                }

            } catch (Exception $e) {
                //FIXME this try catch should not be needed anymore, after refactoring of December 2020

                $this->handleConnectorCreationException($e, $languageResource);
            }
        }
    }

    private function addInternalFuzzyConnector(LanguageResource $languageResource): void
    {
        try {
            $this->addConnector(
                (int) $languageResource->getId(),
                $this->getInternalFuzzyConnector($languageResource)
            );
        } catch (editor_Services_Exceptions_NoConnector) {
            // if the internal fuzzy connector not exist,
            // we ignore it and continue with only normal connector
        } catch (Exception $e) {
            $this->log->exception($e);
            $this->log->error(
                'E1371',
                'Internal Fuzzy language resource could not be created. Check log for previous errors.',
                [
                    'task' => $this->task,
                    'languageResource' => $languageResource,
                ]
            );
        }
    }

    private function handleConnectorCreationException(Exception $e, LanguageResource $languageResource): void
    {
        $errors = [];
        //if the exception is of type ZfExtended_ErrorCodeException, get the additional exception info, and log it
        if ($e instanceof ZfExtended_ErrorCodeException) {
            $errors = $e->getErrors() ?? [];
        }

        $this->log->warn(
            'E1102',
            'Unable to use connector from Language Resource "{name}". Error was: "{msg}".',
            array_merge(
                [
                    'task' => $this->task,
                    'name' => $languageResource->getName(),
                    'msg' => $e->getMessage(),
                    'languageResource' => $languageResource,
                ],
                $errors
            )
        );

        $this->log->exception($e, [
            'level' => $this->log::LEVEL_WARN,
            'domain' => $this->log->getDomain(),
            'extra' => [
                'task' => $this->task,
            ],
        ]);
    }

    private function getConnector(LanguageResource $languageResource): editor_Services_Connector
    {
        $connector = $this->manager->getConnector(
            $languageResource,
            $this->task->getSourceLang(),
            $this->task->getTargetLang(),
            $this->task->getConfig()
        );

        // set the analysis running user to the connector
        $connector->setWorkerUserGuid($this->userGuid);

        return $connector;
    }

    /**
     * @throws editor_Services_Exceptions_NoConnector
     */
    private function getInternalFuzzyConnector(LanguageResource $languageResource): editor_Services_Connector
    {
        $connector = $this->manager->getInternalFuzzyConnector(
            $languageResource,
            $this->task->getSourceLang(),
            $this->task->getTargetLang(),
            $this->task->getConfig(),
            $this->analysisId
        );

        // set the analysis running user to the connector
        $connector->setWorkerUserGuid($this->userGuid);

        return $connector;
    }

    /***
     * Remove fuzzy resources from the TMs
     */
    protected function removeFuzzyResources(): void
    {
        if (! $this->hasConnectors()) {
            return;
        }

        foreach ($this->getConnectorsIterator() as $connector) {
            if ($connector->isInternalFuzzy()) {
                $connector->delete();
            }
        }
    }

    /***
     * Remove not required analysis object and data
     */
    public function clean(): void
    {
        $this->removeFuzzyResources();
        $this->emptyConnectors();
    }

    /**
     * returns the error count sum
     */
    public function getErrorCount(): int
    {
        return array_sum($this->connectorErrorCount);
    }

    public function setPretranslate($pretranslate)
    {
        $this->pretranslate = $pretranslate;
    }

    public function setInternalFuzzy($internalFuzzy)
    {
        $this->internalFuzzy = $internalFuzzy;
    }

    public function setBatchQuery(bool $batchQuery)
    {
        $this->batchQuery = $batchQuery;
    }
}
