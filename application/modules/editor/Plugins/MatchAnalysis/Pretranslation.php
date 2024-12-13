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

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_Connector as Connector;
use MittagQI\Translate5\Integration\FileBasedInterface;

class editor_Plugins_MatchAnalysis_Pretranslation
{
    use ZfExtended_Logger_DebugTrait;

    /***
     *
     * @var editor_Models_Task
     */
    protected $task;

    /***
     *
     * @var editor_Models_SegmentFieldManager
     */
    protected $sfm;

    /***
     *
     * @var editor_Models_TaskUserAssoc
     */
    protected $userTaskAssoc;

    /***
     *
     * @var string
     */
    protected $userGuid;

    /***
     *
     * @var string
     */
    protected $userName;

    /***
     * @var array<int, LanguageResource>
     */
    protected $resources = [];

    /***
     * Minimum matchrate so the segment is pretransalted
     * @var integer
     */
    protected $pretranslateMatchrate = 100;

    /***
     * Pretranslate with translation memory and term collection priority
     * @var boolean
     */
    protected $usePretranslateTMAndTerm = false;

    /***
     * Pretranslate with mt priority only when the tm pretranslation matchrate is not over the $pretranslateMatchrate
     * @var boolean
     */
    protected $usePretranslateMT = false;

    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $internalTag;

    /**
     * @var editor_Models_Segment_AutoStates
     */
    protected $autoStates;

    /***
     * Collection of assigned resources to the task
     * @var array<int, Connector>
     */
    private $connectors = [];

    /**
     * [Resource ID => [LR ID => Connector]]
     * @var array<string, array<int, Connector>>
     */
    private array $internalFuzzyConnectorMap = [];

    /***
     * Pretranslation mt connectors(the mt resources associated to a task)
     * @var array
     */
    protected $mtConnectors = [];

    /**
     * @var ZfExtended_EventManager
     */
    protected $events = false;

    /***
     * Analysis id
     *
     * @var mixed
     */
    protected $analysisId;

    /***
     * Is the current analysis and pretranslation running with batch query enabled
     * @var boolean
     */
    protected $batchQuery = false;

    public function __construct(int $analysisId)
    {
        $this->initLogger('E1100', 'plugin.matchanalysis', '', 'Plug-In MatchAnalysis: ');
        $this->internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        $this->autoStates = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', ['editor_Plugins_MatchAnalysis_Pretranslation']);
        $this->analysisId = $analysisId;
    }

    /**
     * Use this for internal fuzzy match target that will be ignored.
     */
    public static function renderDummyTargetText($taskGuid)
    {
        return "translate5-unique-id[" . $taskGuid . "]";
    }

    protected function internalFuzzyConnectorSet(LanguageResource $languageResource): bool
    {
        return isset($this->internalFuzzyConnectorMap[$languageResource->getResourceId()]);
    }

    protected function addInternalFuzzyConnector(LanguageResource $lr, Connector $connector): void
    {
        $this->internalFuzzyConnectorMap[$lr->getResourceId()] = [
            (int) $lr->getId() => $connector,
        ];
    }

    /**
     * @return iterable<int, Connector>
     */
    protected function getInternalFuzzyConnectorsIterator(): iterable
    {
        foreach ($this->internalFuzzyConnectorMap as $connectorTuple) {
            foreach ($connectorTuple as $lrId => $connector) {
                yield $lrId => $connector;
            }
        }
    }

    protected function addConnector(int $languageResourceId, Connector $connector)
    {
        $this->connectors[$languageResourceId] = $connector;
    }

    /**
     * @return iterable<int, Connector>
     */
    protected function getConnectorsIterator(): iterable
    {
        foreach ($this->connectors as $languageResourceId => $connector) {
            yield $languageResourceId => $connector;
        }

        foreach ($this->getInternalFuzzyConnectorsIterator() as $languageResourceId => $connector) {
            yield $languageResourceId => $connector;
        }
    }

    protected function hasConnectors(): bool
    {
        return ! empty($this->connectors);
    }

    protected function emptyConnectors(): void
    {
        $this->connectors = [];
    }

    private function getConnector(int $languageResourceId): ?Connector
    {
        return $this->connectors[$languageResourceId] ?? null;
    }

    /**
     * returns true if the given segment content is from a internal fuzzy
     */
    protected function isInternalFuzzy(string $segmentContent): bool
    {
        $dummyTargetText = self::renderDummyTargetText($this->task->getTaskGuid());

        return str_contains($segmentContent, $dummyTargetText);
    }

    /***
     * Use the given TM analyse (or MT if analyse was empty) result to update the segment
     * Update the segment only if it is not TRANSLATED
     *
     * @param editor_Models_Segment $segment
     * @param stdClass $result - match resources result
     * @param bool $isRepetition
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    protected function updateSegment(editor_Models_Segment $segment, stdClass $result, bool $isRepetition)
    {
        // Check whether match rate and/or penalties changed
        $penaltyChanged = array_sum([
            'penaltyGeneral' => ((int) $segment->getPenaltyGeneral() !== (int) $result->penaltyGeneral) ? 1 : 0,
            'penaltySublang' => ((int) $segment->getPenaltySublang() !== (int) $result->penaltySublang) ? 1 : 0,
        ]);

        // Do not pretranslate if either conditions are in place:
        // 1. Segment is locked, as pretranslation is only for editable segments
        // 2. Segment is not untranslated (e.g. is already pre-translated by previous match-analysis/pretranslation run)
        //    Except case when:
        //      Penalty was changed. In that case we need to update the penalties for that segments at least, and need
        //      to update the best match and it's rate at most, because when penalty changed this may make previous best
        //      match to be not the best anymore, and if so - this means we now have new best match with it's own rate,
        //      which should be reflected in the segment data
        if ($segment->meta()->getLocked()
            || ($segment->getAutoStateId() != editor_Models_Segment_AutoStates::NOT_TRANSLATED && ! $penaltyChanged)) {
            return;
        }

        //the internalLanguageResourceid is set when the segment bestmatchrate is found(see analysis getbestmatchrate function)
        $languageResourceid = $result->internalLanguageResourceid;

        $history = $segment->getNewHistoryEntity();

        $segmentField = $this->sfm->getFirstTargetName();
        $segmentFieldEdit = $segmentField . 'Edit';

        $targetResult = $result->target;

        $matchrateType = ZfExtended_Factory::get('editor_Models_Segment_MatchRateType');
        /* @var $matchrateType editor_Models_Segment_MatchRateType */

        //set the type
        $languageResource = $this->resources[$languageResourceid];
        /* @var $languageResource LanguageResource */

        //just to display the TM name too, we add it here to the type
        $type = $languageResource->getServiceName() . ' - ' . $languageResource->getName();

        //ignore internal fuzzy match target
        if ($this->isInternalFuzzy($targetResult)) {
            //set the internal fuzzy available matchrate type
            $matchrateType->initPretranslated(editor_Models_Segment_MatchRateType::TYPE_INTERNAL_FUZZY_AVAILABLE, $type);
            $segment->setMatchRateType((string) $matchrateType);

            //save the segment and history
            $this->saveSegmentAndHistory($segment, $history);

            return;
        }

        $matchType = [];
        $hasText = $this->internalTag->hasText($segment->getSource());
        if ($hasText) {
            //if the result language resource is termcollection, set the target result first character to uppercase
            if ($this->isTermCollection($languageResourceid)) {
                $targetResult = ZfExtended_Utils::mb_ucfirst($targetResult);
            }
            $targetResult = $this->internalTag->removeIgnoredTags($targetResult);
            $segment->setMatchRate($result->matchrate);
            $segment->setPenaltyGeneral($result->penaltyGeneral);
            $segment->setPenaltySublang($result->penaltySublang);
            $matchType[] = $languageResource->getResourceType();
            $matchType[] = $type;
            if ($isRepetition) {
                $matchType[] = $matchrateType::TYPE_AUTO_PROPAGATED;
            }

            //negated explanation is easier: lock the pretranslations if 100 matches in the task are not editable,
            $segment->setEditable($result->matchrate < 100 || $this->task->getEdit100PercentMatch());
        } else {
            //if the source contains no text but tags only, we set the target to the source directly
            // and the segment is not editable
            $targetResult = $segment->getSource();
            $segment->setMatchRate(FileBasedInterface::CONTEXT_MATCH_VALUE);
            $matchType[] = $matchrateType::TYPE_SOURCE;
            $segment->setEditable(false);
        }
        $matchrateType->initPretranslated(...$matchType);

        $segment->setMatchRateType((string) $matchrateType);

        $segment->setAutoStateId($this->autoStates->calculatePretranslationState($segment->isEditable()));
        //a segment is only pre-translated if it contains content
        $segment->setPretrans($hasText ? $segment::PRETRANS_INITIAL : $segment::PRETRANS_NOTDONE);

        //check if the result is valid for log
        if ($this->isResourceLogValid($languageResource, (int) $segment->getMatchRate())) {
            $this->getConnector($languageResourceid)?->logAdapterUsage($segment, $isRepetition);
        }

        $segment->set($segmentField, $targetResult); //use sfm->getFirstTargetName here
        $segment->set($segmentFieldEdit, $targetResult); //use sfm->getFirstTargetName here

        $segment->updateToSort($segmentField);
        $segment->updateToSort($segmentFieldEdit);

        $segment->setUserGuid($this->userGuid); //to the authenticated userGuid
        $segment->setUserName($this->userName); //to the authenticated userName

        //NOTE: remove me if to many problems
        //$segment->validate();

        if ($this->task->getWorkflowStep() == 1) {
            //TODO move hasher creation out the segment loop
            $hasher = ZfExtended_Factory::get('editor_Models_Segment_RepetitionHash', [$this->task]);
            /* @var $hasher editor_Models_Segment_RepetitionHash */
            //calculate and set segment hash
            $segmentHash = $hasher->rehashTarget($segment, $targetResult);
            $segment->setTargetMd5($segmentHash);
        }

        //set the used language resource uuid in the segments meta table
        $segment->meta()->setPreTransLangResUuid($languageResource->getLangResUuid());
        $segment->meta()->save();

        //save the segment and history
        $this->saveSegmentAndHistory($segment, $history);

        $this->events->trigger('afterAnalysisSegmentPretranslate', $this, [
            'entity' => $segment,
            'analysisId' => $this->analysisId,
            'languageResourceId' => $languageResourceid,
            'result' => $result,
        ]);
    }

    /***
     * Query the segment using the Mt engines assigned to the task.
     * Ony the first mt engine will be used
     * @param editor_Models_Segment $segment
     * @return NULL|[stdClass]
     */
    protected function getMtResult(editor_Models_Segment $segment)
    {
        if (empty($this->mtConnectors)) {
            return null;
        }
        //INFO: use the first connector, since no mt engine priority exist
        $connector = $this->mtConnectors[0];
        /* @var $connector editor_Services_Connector */

        //if the current connector supports batch query, enable the batch query for this connector
        if ($connector->isBatchQuery() && $this->batchQuery) {
            $connector->enableBatch();
        }

        $connector->resetResultList();
        $matches = $connector->query($segment);
        $matchResults = $matches->getResult();
        if (! empty($matchResults)) {
            $result = $matchResults[0];
            $result->internalLanguageResourceid = $connector->getLanguageResource()->getId();
            $result->isMT = true;

            return $result;
        }

        return null;
    }

    /***
     * Check if the given language resource id is a valid termcollection resource
     * @param int $languageResourceId
     * @return boolean
     */
    protected function isTermCollection($languageResourceId)
    {
        if (! isset($this->resources[$languageResourceId])) {
            return false;
        }
        $lr = $this->resources[$languageResourceId];
        /* @var $lr LanguageResource */
        $tcs = ZfExtended_Factory::get('editor_Services_TermCollection_Service');

        /* @var $tcs editor_Services_TermCollection_Service */
        return $lr->getServiceName() == $tcs->getName();
    }

    /***
     * Should the current language resources result with matchrate be logged in the languageresources ussage log table
     *
     * @param LanguageResource $languageResource
     * @param int $matchRate
     * @return boolean
     */
    protected function isResourceLogValid(LanguageResource $languageResource, int $matchRate)
    {
        //check if it is tm or tc, an if the matchrate is >= 100
        return ($languageResource->isTm() || $languageResource->isTc())
            && $matchRate >= FileBasedInterface::EXACT_MATCH_VALUE;
    }

    /***
     * Save the segment(set the duration and the timestamp) and the segmenthistory
     * @param editor_Models_Segment $segment
     * @param editor_Models_SegmentHistory $history
     */
    protected function saveSegmentAndHistory(editor_Models_Segment $segment, editor_Models_SegmentHistory $history)
    {
        $segmentField = $this->sfm->getFirstTargetName();
        $segmentFieldEdit = $segmentField . 'Edit';
        $duration = new stdClass();
        $duration->$segmentField = 0;
        $segment->setTimeTrackData($duration);

        $duration = new stdClass();
        $duration->$segmentFieldEdit = 0;
        $segment->setTimeTrackData($duration);

        $history->save();
        $segment->setTimestamp(NOW_ISO);
        $segment->save();
    }

    public function setUserGuid($userGuid)
    {
        $this->userGuid = $userGuid;
    }

    public function setUserName($userName)
    {
        $this->userName = $userName;
    }

    public function setPretranslateMatchrate($pretranslateMatchrate)
    {
        $this->pretranslateMatchrate = $pretranslateMatchrate;
    }

    /***
     * Set pretranslate from Mt priority flag
     * @param bool $usePretranslateMT
     */
    public function setPretranslateMt($usePretranslateMT)
    {
        $this->usePretranslateMT = $usePretranslateMT;
    }

    /***
     * Set the pretranslate from the Tm and termcollection priority flag. This flag also will run the pretranslations
     * @param bool $usePretranslateTMAndTerm
     */
    public function setPretranslateTmAndTerm($usePretranslateTMAndTerm)
    {
        $this->usePretranslateTMAndTerm = $usePretranslateTMAndTerm;
    }
}
