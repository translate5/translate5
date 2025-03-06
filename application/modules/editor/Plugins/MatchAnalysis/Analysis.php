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
use editor_Models_Segment_MatchRateType as MatchRateType;
use editor_Plugins_MatchAnalysis_Models_MatchAnalysis as MatchAnalysis;
use MittagQI\Translate5\Integration\FileBasedInterface;
use MittagQI\Translate5\LanguageResource\Adapter\Exception\SegmentUpdateException;
use MittagQI\Translate5\LanguageResource\Status;
use MittagQI\Translate5\Penalties\DataProvider\TaskPenaltyDataProvider;
use ZfExtended_Factory as Factory;

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

    /**
     * Flag if internal fuzzy will be calculated
     */
    protected bool $internalFuzzy = false;

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
     * @var array<string, editor_Models_Segment>
     */
    protected $repetitionMasterSegments = [];

    /**
     * Holds the repetition updater
     */
    protected ?editor_Models_Segment_RepetitionUpdater $repetitionUpdater = null;

    private editor_Services_Manager $manager;

    /**
     * Penalties as [languageResourceId => penalty] pairs to be deducted from match rate
     */
    protected array $penalty = [
        'general' => [],
        'sublang' => [],
    ];

    /**
     * @param integer $analysisId
     */
    public function __construct(editor_Models_Task $task, $analysisId)
    {
        $this->task = $task;
        $this->analysisId = $analysisId;
        $this->sfm = editor_Models_SegmentFieldManager::getForTaskGuid($task->getTaskGuid());
        $this->manager = Factory::get(editor_Services_Manager::class);

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
        $segments = Factory::get(editor_Models_Segment_Iterator::class, [
            $this->task->getTaskGuid(),
        ]);
        $segments->setIgnoreBlockedSegments(true);

        $this->initConnectors();

        if (empty($this->hasConnectors())) {
            return false;
        }
        $this->initRepetitions();

        $segmentCounter = 0;

        /** @var editor_Models_Segment $segment */
        foreach ($segments as $segment) {
            $segmentCounter++;

            //progress to update
            $progress = $segmentCounter / $this->task->getSegmentCount();

            //get the best match rate, respecting repetitions
            $bestMatchRateResult = $this->calculateMatchrate($segment);

            $this->saveSegmentToInternalFuzzyTm($segment);

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
                    // Setup penalties and deduct them from match rate
                    $bestMatchRateResult->penaltyGeneral = $this->penalty['general'][$bestMatchRateResult->languageResourceid];
                    $bestMatchRateResult->penaltySublang = $this->penalty['sublang'][$bestMatchRateResult->languageResourceid];
                    $bestMatchRateResult->matchrate = max(
                        0,
                        $bestMatchRateResult->matchrate
                        - $bestMatchRateResult->penaltyGeneral
                        - $bestMatchRateResult->penaltySublang
                    );

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
                $isRepetition = $rep
                    && $master
                    && $master->getId() !== $segment->getId()
                    && $bestMatchRateResult->isRepetition
                ;

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
        // calculate and set segment hash.
        // If segment has descriptor set - it indicates that the segment had res-name on import
        // and such segment should be treated as a unique segment in pair with the descriptor
        $segmentHash = $segment->getSourceMd5() . $segment->meta()->getSegmentDescriptor();

        //lazy init, we need only instance, the here given $segment will be overwritten wuth the updateRepetition call
        if (null === $this->repetitionUpdater) {
            $this->repetitionUpdater = new editor_Models_Segment_RepetitionUpdater($segment, $this->task->getConfig());
        }

        //check if the segment source hash exist in the repetition array
        //segment exist in the repetition array -> it is repetition, save it as 102 (repetition) and 0 languageResource
        //segment does not exist in repetition array -> query the tm save the best match rate per tm
        $hasRepetitions = in_array($segment->getId(), $this->segmentIdsWithRepetitions);
        $isRepetition = $hasRepetitions && array_key_exists($segmentHash, $this->repetitionMasterSegments);

        if (! $isRepetition) {
            $bestResult = $this->getBestResult($segment);
            $calculatedResult = $bestResult ? clone $bestResult : null;

            if ($calculatedResult) {
                $calculatedResult->isRepetition = false;
            }

            if (! $hasRepetitions) {
                // if the segment has no repetitions at all we just return the found result
                return $calculatedResult;
            }

            //the first segment of multiple repetitions is always stored as master
            $this->repetitionMasterSegments[$segmentHash] = clone $segment;
            //store the found match for repetition re-usage
            $this->repetitionByHash[$segmentHash] = $bestResult;

            return $calculatedResult;
        }
        $masterHasResult = ! empty($this->repetitionByHash[$segmentHash]);

        // DESCRIPTION BEHAVIOUR FOR REPETITIONS
        // for the analysis, a repetition is always counted as 102% match!
        // what is taken over for pre-translation is however defined differently:
        // - if master segment matchrate < configured matchrate for pre-translation:
        //      then count the repetition as 102 in the analysis but do not touch the repeated segment,
        //      so matchrate = 0, no target, no matchtype
        // - if master segment matchrate >= configured matchrate for pre-translation:
        //      then count the repetition as 102 in the analysis AND set the repeated segment to the matchrate,
        //      target content and matchtype of the master segment
        //      the segment is also marked as pre-translated and it should be editable if a fuzzy match
        //      (which is no problem anymore since the fuzzy matchrate is taken over)

        // get the best match rate for the repetition segment, basically 102%, but:
        // it can be context match (103%) which is better as the above defined 102% repetition one
        // or the one stored for the repetition could be from a MT. So recalc here always.
        $bestResult = $this->getBestResult($segment, false);

        if (null === $bestResult) {
            if (isset($this->repetitionByHash[$segmentHash])) {
                // save the repetition analysis with 102% match rate
                $this->saveAnalysis($segment, FileBasedInterface::REPETITION_MATCH_VALUE, 0);

                return $this->repetitionByHash[$segmentHash];
            }

            return null;
        }

        $bestMatch = $bestResult->matchrate ?? 0;
        $resultMatchRate = max($bestMatch, FileBasedInterface::REPETITION_MATCH_VALUE);

        // for TM result we take repetition match value only if best match was < 103%
        if (
            $bestResult->languageResourceType == MatchRateType::TYPE_TM
            && $bestMatch === FileBasedInterface::CONTEXT_MATCH_VALUE
            && ! $this->isInternalFuzzy($bestResult->target ?? '')
        ) {
            $resultMatchRate = $bestResult->matchrate;
        }

        $result = clone $bestResult;
        $result->isRepetition = false;

        if (FileBasedInterface::REPETITION_MATCH_VALUE === $resultMatchRate) {
            // save the repetition analysis with 102% match rate
            $this->saveAnalysis($segment, $resultMatchRate, 0);
            $result->isRepetition = true;
            $result->matchrate = 0;
        }

        //if there is no match we can not update the target below, this means returning null
        if (! $masterHasResult || $this->isInternalFuzzy($this->repetitionByHash[$segmentHash]->target ?? '')) {
            // if the master of the repetition had no result, the repetition has no content either
            return null;
        }

        $masterResult = $this->repetitionByHash[$segmentHash];
        $masterHasFullMatch = $masterResult->matchrate >= 100;

        if ($masterHasFullMatch && FileBasedInterface::REPETITION_MATCH_VALUE === $resultMatchRate) {
            //bestResult is fallback if tags could not be applied
            return $this->updateTargetOfRepetition($segment, $masterResult) ?? $result;
        }

        //if the master was a fuzzy or the full match repetition could not be set (above updateTargetOfRepetition) properly, we keep the found matchrate and translation
        return $bestResult;
    }

    /**
     * When taking over a repetition, the content (tags) must be prepared properly before usage
     * returns null if the tags could not be applied
     */
    protected function updateTargetOfRepetition(
        editor_Models_Segment $segment,
        ?stdClass $masterResult,
        ?int $repetitionRate = null
    ): ?stdClass {
        if (null === $masterResult) {
            return null;
        }

        $segmentHash = $segment->getSourceMd5() . $segment->meta()->getSegmentDescriptor();
        $master = $this->repetitionMasterSegments[$segment->getSourceMd5()] // repetition from MT
            ?? $this->repetitionMasterSegments[$segmentHash] // repetition from TM
            ?? null; // no repetition found

        if (null === $master) {
            return null;
        }

        if ($this->repetitionUpdater->updateTargetOfRepetition($master, $segment)) {
            // the returning result must be the one from the first of the repetition group.
            // to get the correct content for the repetition we get the value from $segment, which was updated by the repetition updater
            // we may not update the repetitionHash, this would interfer with the other repetitions
            $bestRepeatedResult = clone $masterResult;
            $bestRepeatedResult->target = $segment->getTargetEdit();
            $bestRepeatedResult->isRepetition = true;

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
        foreach ($this->getConnectorsIterator() as $languageResourceId => $connector) {
            if ($this->isDisabledDueErrors($connector, $languageResourceId)) {
                continue;
            }

            //if the current connector supports batch query, enable the batch query for this connector
            if ($connector->isBatchQuery() && $this->batchQuery) {
                $connector->enableBatch();
            }

            $connector->resetResultList();
            $isMtResource = $this->resources[$languageResourceId]->isMt();

            try {
                $matches = $this->getMatches($connector, $segment, $isMtResource);
            } catch (Exception $e) {
                $this->handleConnectionError($e, $languageResourceId, $connector->isInternalFuzzy());
                // in case of an error we produce an empty result container for that query and log the error so that the analysis can proceed
                $matches = Factory::get('editor_Services_ServiceResult');
            }

            $matchResults = $matches->getResult();

            $bestResultCurrentConnector = new stdClass();
            $bestResultCurrentConnector->matchrate = null;
            //for each match, find the best match rate, and save it
            foreach ($matchResults as $match) {
                // Setup penalties and deduct them from match rate
                if (isset($match->languageResourceid)) {
                    $match->penaltyGeneral = $this->penalty['general'][$match->languageResourceid];
                    $match->penaltySublang = $this->penalty['sublang'][$match->languageResourceid];
                    $match->matchrate -= $match->penaltyGeneral + $match->penaltySublang;
                }

                $isTermCollection = $match->languageResourceType == MatchRateType::TYPE_TERM_COLLECTION;
                if ($bestResultCurrentConnector->matchrate > $match->matchrate) {
                    continue;
                }

                //If the matchrate is the same between matches from one connector,
                // we only check for a new best match if it is from a termcollection
                if ($bestResultCurrentConnector->matchrate == $match->matchrate && ! $isTermCollection) {
                    continue;
                }

                if ($isTermCollection) {
                    // - preferred terms > permitted terms
                    // - if multiple permitted terms: take the first
                    if (! is_null($bestMatchRateResult) && $bestMatchRateResult->languageResourceType == MatchRateType::TYPE_TERM_COLLECTION) {
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

                $bestResultCurrentConnector = $match;

                //store best match rate results
                $currentIsBetter = $bestResultCurrentConnector->matchrate > $bestMatchRate;

                // if match of another TM has the same >=100 matchrate,
                // use the newer one but exclude internal fuzzies, since they are always newer here
                $current100MatchIsNewer = $bestResultCurrentConnector->matchrate === $bestMatchRate
                    && $bestMatchRate >= 100
                    && $match->timestamp > $bestMatchRateResult->timestamp
                    && ! $connector->isInternalFuzzy();

                // if we have the same fuzzy rate, the one from an internal fuzzy is the better one
                $internalFuzzyIsBetter = $bestResultCurrentConnector->matchrate === $bestMatchRate
                    && $bestMatchRate < 100
                    && $connector->isInternalFuzzy();
                //CRUCIAL: very ugly: this is ensured in the analysis grouping due the fact that the fuzzy connector
                // is the last connector used and the result is added to the end,
                // and analysis entries with the same matchrate the later one (higher id) is used.

                // but do not compare agains the mt results
                if (($currentIsBetter || $current100MatchIsNewer || $internalFuzzyIsBetter) && ! $isMtResource
                ) {
                    $bestMatchRateResult = $match;
                    $bestMatchRateResult->internalLanguageResourceid = $languageResourceId;
                }
            }

            //no match rate is found in the languageResource result
            if ($bestResultCurrentConnector->matchrate == null) {
                //store 0 matchrate results only for non-internal fuzzy TMs
                if (! $connector->isInternalFuzzy()) {
                    $saveAnalysis && $this->saveAnalysis($segment, 0, $languageResourceId);
                }
                $matches->resetResult();

                continue;
            }

            //$bestResultCurrentConnector contains always the highest matchrate from $matchResults
            //update the bestmatchrate if $bestResultCurrentConnector contains highest matchrate
            if ($bestResultCurrentConnector->matchrate > $bestMatchRate) {
                $bestMatchRate = $bestResultCurrentConnector->matchrate;
            }

            //save the match analyses if needed
            $saveAnalysis && $this->saveAnalysis($segment, $bestResultCurrentConnector, $languageResourceId);

            //reset the result collection
            $matches->resetResult();
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

        $key = $this->getErrorCountKey($id, $connector->isInternalFuzzy());

        if (! isset($this->connectorErrorCount[$key])
            || $this->connectorErrorCount[$key] <= self::MAX_ERROR_PER_CONNECTOR) {
            return false;
        }

        $connector->disable();

        $langRes = $connector->getLanguageResource();
        $this->log->warn(
            'E1101',
            'Disabled Language Resource {name} ({service}) for analysing and pretranslation due too much errors.',
            [
                'task' => $this->task,
                'languageResource' => $langRes,
                'name' => $langRes->getName(),
                'service' => $langRes->getServiceName(),
            ]
        );

        return true;
    }

    /**
     * Log and count the connection error
     */
    protected function handleConnectionError(Exception $e, int $id, bool $isInternalFuzzy): void
    {
        $this->log->exception($e, [
            'level' => $this->log::LEVEL_WARN,
            'domain' => $this->log->getDomain(),
            'extra' => [
                'isInternalFuzzy' => $isInternalFuzzy,
                'task' => $this->task,
            ],
        ]);
        $key = $this->getErrorCountKey($id, $isInternalFuzzy);
        settype($this->connectorErrorCount[$key], 'integer');
        $this->connectorErrorCount[$key]++;
    }

    private function getErrorCountKey(int $id, bool $isInternalFuzzy): string
    {
        return ($isInternalFuzzy ? 'internalFuzzy' : 'normal') . ':' . $id;
    }

    /**
     * @param bool $isMtResource
     * @return editor_Services_ServiceResult
     */
    protected function getMatches(editor_Services_Connector $connector, editor_Models_Segment $segment, $isMtResource)
    {
        if (! $isMtResource) {
            return $connector->query($segment);
        }

        //the resource is of type mt, so we do not need to query the mt for results, since we will receive always the default MT defined matchrate
        //the mt resource only will be searched when pretranslating

        //get the query string from the segment
        $queryString = $connector->getQueryString($segment);

        $internalTag = new editor_Models_Segment_InternalTag();
        $queryString = $internalTag->toXliffPaired($queryString);

        $matches = new editor_Services_ServiceResult($queryString);
        $matches->setLanguageResource($connector->getLanguageResource());
        $matches->addResult('', $connector->getDefaultMatchRate());

        return $matches;
    }

    private function saveSegmentToInternalFuzzyTm(editor_Models_Segment $segment): void
    {
        if (! $this->internalFuzzy) {
            return;
        }

        foreach ($this->getInternalFuzzyConnectorsIterator() as $internalFuzzyConnector) {
            if ($internalFuzzyConnector->isDisabled()) {
                continue;
            }

            $origTarget = $segment->getTargetEdit();
            $dummyTargetText = self::renderDummyTargetText($segment->getTaskGuid());
            $segment->setTargetEdit($dummyTargetText);

            try {
                $internalFuzzyConnector->update($segment);
            } catch (SegmentUpdateException) {
                // Ignore the error here as we don't care about the result
            }

            $segment->setTargetEdit($origTarget);
        }
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
        /* @var $matchAnalysis MatchAnalysis */
        $matchAnalysis = Factory::get(MatchAnalysis::class);
        $matchAnalysis->setSegmentId($segment->getId());
        $matchAnalysis->setSegmentNrInTask($segment->getSegmentNrInTask());
        $matchAnalysis->setTaskGuid($this->task->getTaskGuid());
        $matchAnalysis->setAnalysisId($this->analysisId);
        $matchAnalysis->setLanguageResourceid($languageResourceid);
        $matchAnalysis->setWordCount($segment->meta()->getSourceWordCount());
        $matchAnalysis->setCharacterCount($segment->meta()->getSourceCharacterCount());
        $matchAnalysis->setMatchRate($matchRateResult->matchrate ?? $matchRateResult);

        if ($languageResourceid === 0) {
            $type = MatchRateType::TYPE_AUTO_PROPAGATED;
        } elseif (array_key_exists($languageResourceid, $this->resources)) {
            $type = $this->resources[$languageResourceid]->getResourceType();
            $matchAnalysis->setPenaltyGeneral($this->penalty['general'][$languageResourceid]);
            $matchAnalysis->setPenaltySublang($this->penalty['sublang'][$languageResourceid]);
        } else {
            $type = MatchRateType::TYPE_UNKNOWN;
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
        $segmentModel = Factory::get('editor_Models_Segment');
        /* @var $segmentModel editor_Models_Segment */
        $results = $segmentModel->getRepetitions($this->task->getTaskGuid());
        $this->segmentIdsWithRepetitions = array_column($results, 'id');
        $this->repetitionByHash = [];
        $this->repetitionMasterSegments = [];
    }

    /**
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws ReflectionException
     */
    protected function initConnectors(): void
    {
        $languageResources = Factory::get(LanguageResource::class);
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

        $taskPenaltyDataProvider = TaskPenaltyDataProvider::create();

        foreach ($languageResourceIds as $languageResourceId) {
            $languageResource = Factory::get(LanguageResource::class);
            $languageResource->load((int) $languageResourceId);

            $resource = $this->manager->getResource($languageResource);

            //ignore non analysable resources
            if (! $resource->getAnalysable()) {
                continue;
            }

            //store the languageResource
            $this->resources[(int) $languageResource->getId()] = $languageResource;

            // Detect penalties to be applied
            $penalties = $taskPenaltyDataProvider->getPenalties($this->task->getTaskGuid(), $languageResourceId);

            // Setup general penalty
            $this->penalty['general'][$languageResourceId] = $penalties['penaltyGeneral'];

            // Setup sublang penalty as 0 until source and/or target sublang mismatch will be detected
            $this->penalty['sublang'][$languageResourceId] = $penalties['penaltySublang'];

            // prepare penalties
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

                $this->addConnector((int) $languageResource->getId(), $connector);

                // collect the mt resource, so it can be used for pre-translations if needed
                if ($languageResource->isMt()) {
                    $this->mtConnectors[] = $connector;
                }

                if (! $resource->supportsInternalFuzzy()) {
                    continue;
                }

                // we need only one fuzzy connector per resource
                if ($this->internalFuzzy && ! $this->internalFuzzyConnectorSet($languageResource)) {
                    $fuzzyConnector = $this->initFuzzyConnector($connector);

                    if ($fuzzyConnector->isDisabled()) {
                        continue;
                    }

                    $this->addInternalFuzzyConnector($languageResource, $fuzzyConnector);
                }
            } catch (Exception $e) {
                //FIXME this try catch should not be needed anymore, after refactoring of December 2020

                $this->handleConnectorCreationException($e, $languageResource);
            }
        }
    }

    private function initFuzzyConnector(editor_Services_Connector $connector): editor_Services_Connector
    {
        try {
            $fuzzyConnector = $connector->initForFuzzyAnalysis($this->analysisId);
        } catch (Exception $e) {
            $fuzzyConnector = clone $connector;
            //the whole connector must be invalidated and the problem must be logged.
            $fuzzyConnector->disable();
            $this->log->exception($e);
            $this->log->error('E1371', 'Internal Fuzzy language resource could not be created. Check log for previous errors.', [
                'task' => $this->task,
                'languageResource' => $connector->getLanguageResource(),
            ]);
        }

        return $fuzzyConnector;
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
        $language = Factory::get(editor_Models_Languages::class);
        $taskMajorSourceLangId = $language->findMajorLanguageById((int) $this->task->getSourceLang());
        $taskMajorTargetLangId = $language->findMajorLanguageById((int) $this->task->getTargetLang());

        $connector = $this->manager->getConnector(
            $languageResource,
            $taskMajorSourceLangId,
            $taskMajorTargetLangId,
            $this->task->getConfig(),
            (int) $this->task->getCustomerId()
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

    public function setInternalFuzzy(bool $internalFuzzy): void
    {
        $this->internalFuzzy = $internalFuzzy;
    }

    public function setBatchQuery(bool $batchQuery)
    {
        $this->batchQuery = $batchQuery;
    }
}
