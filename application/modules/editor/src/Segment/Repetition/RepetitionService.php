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

declare(strict_types=1);

namespace MittagQI\Translate5\Segment\Repetition;

use editor_Models_Segment as Segment;
use editor_Models_Segment_AutoStates;
use editor_Models_Segment_MatchRateType as MatchRateType;
use editor_Models_Segment_RepetitionHash as RepetitionHash;
use editor_Models_SegmentField as SegmentField;
use editor_Models_SegmentFieldManager;
use editor_Models_Task as Task;
use editor_Models_TaskProgress;
use editor_Models_TaskUserAssoc as UserJob;
use editor_Segment_Alike_Qualities as AlikeQualities;
use editor_Segment_Processing;
use editor_Segment_Quality_Manager;
use Exception;
use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\Integration\FileBasedInterface;
use MittagQI\Translate5\Repository\SegmentHistoryRepository;
use MittagQI\Translate5\Repository\SegmentRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Segment\Event\BeforeSaveAlikeEvent;
use MittagQI\Translate5\Segment\Event\SegmentProcessedEvent;
use MittagQI\Translate5\Segment\QueuedBatchUpdateWorker;
use MittagQI\Translate5\Segment\Repetition\DTO\ReplaceDto;
use MittagQI\Translate5\Segment\Repetition\Event\RepetitionProcessingFailedEvent;
use MittagQI\Translate5\Segment\Repetition\Event\RepetitionReplacementRequestedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use stdClass;
use Throwable;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Logger;

class RepetitionService
{
    public function __construct(
        private readonly SegmentRepository $segmentRepository,
        private readonly SegmentHistoryRepository $segmentHistoryRepository,
        private readonly RepetitionUpdater $repetitionUpdater,
        private readonly editor_Models_Segment_AutoStates $autoStates,
        private readonly EventDispatcher $events,
        private readonly ZfExtended_Logger $logger,
        private readonly editor_Segment_Quality_Manager $qualityManager,
        private readonly TaskRepository $taskRepository,
        private readonly editor_Models_TaskProgress $taskProgress,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UserJobRepository $jobRepository,
    ) {
    }

    public static function create(): self
    {
        return new self(
            SegmentRepository::create(),
            SegmentHistoryRepository::create(),
            RepetitionUpdater::create(),
            new editor_Models_Segment_AutoStates(),
            EventDispatcher::create(),
            Zend_Registry::get('logger')->cloneMe('editor.segment.repetition'),
            editor_Segment_Quality_Manager::instance(),
            TaskRepository::create(),
            new editor_Models_TaskProgress(),
            EventDispatcher::create(),
            UserJobRepository::create(),
        );
    }

    public function queueReplaceBatch(ReplaceDto $replaceDto): void
    {
        $worker = new QueuedBatchUpdateWorker();

        if ($worker->init(
            $replaceDto->taskGuid,
            [
                'dto' => $replaceDto,
            ]
        )) {
            $worker->queue();
        }
    }

    public function replaceBatch(ReplaceDto $replaceDto): void
    {
        $this->eventDispatcher->dispatch(
            new RepetitionReplacementRequestedEvent(
                userJobId: $replaceDto->userJobId,
                taskGuid: $replaceDto->taskGuid,
                masterId: $replaceDto->masterId,
                repetitionIds: $replaceDto->repetitionIds,
            )
        );

        try {
            $this->executeReplaceBatch($replaceDto);
        } catch (Throwable) {
            $this->eventDispatcher->dispatch(new RepetitionProcessingFailedEvent(
                $replaceDto->masterId,
                $replaceDto->repetitionIds,
            ));
        }
    }

    private function executeReplaceBatch(ReplaceDto $replaceDto): void
    {
        $task = $this->taskRepository->getByGuid($replaceDto->taskGuid);

        $alikeQualities = new AlikeQualities($replaceDto->masterId);

        // no direct instantiation of the hasher, since it overwritten in one of plugins
        $hasher = ZfExtended_Factory::get(RepetitionHash::class, [$task]);

        $sfm = editor_Models_SegmentFieldManager::getForTaskGuid($task->getTaskGuid());
        $sourceMeta = $sfm->getByName(SegmentField::TYPE_SOURCE);
        // @phpstan-ignore-next-line
        $isSourceEditable = $sourceMeta !== false && (int) $sourceMeta->editable == 1;

        $durationDto = new stdClass();

        $this->updateDuration(SegmentField::TYPE_TARGET, $durationDto, $replaceDto->duration);
        if ($isSourceEditable) {
            $this->updateDuration(SegmentField::TYPE_SOURCE, $durationDto, $replaceDto->duration);
        }

        $repeatedIncludingEdited = $replaceDto->repetitionIds;
        $repeatedIncludingEdited[] = $replaceDto->masterId;

        sort($repeatedIncludingEdited, SORT_NUMERIC);

        $firstRepetitionId = $repeatedIncludingEdited[0];

        // the update target method tries to update the repetition target by transforming the
        // desired tags (all tags from source on translation or targetOriginal on review)
        // into the targetEdit of the master segment and take the result then.
        // if that fails, the segment can not be processed automatically and must remain for a manual review by the user
        $useSourceForReference = $task->getConfig()
            ->runtimeOptions
            ->editor
            ->frontend
            ->reviewTask
            ->useSourceForReference
        ;

        $count = count($replaceDto->repetitionIds);

        // Do preparations for cases when we need full list of task's segments to be analysed for quality detection
        // Currently it is used only for consistency-check to detect consistency qualities BEFORE segment is saved,
        // so that it would be possible to do the same AFTER segment is saved, calculate the difference and insert/delete
        // qualities on segments where needed
        $this->qualityManager->preProcessTask($task, editor_Segment_Processing::ALIKE);

        $master = $this->segmentRepository->get($replaceDto->masterId);
        $userJob = $this->jobRepository->get($replaceDto->userJobId);

        $validTargetMd5 = $this->getValidTargetMd5($master);

        foreach ($replaceDto->repetitionIds as $segmentId) {
            $repetition = $this->segmentRepository->get($segmentId);

            $this->replaceAlike(
                $master,
                $repetition,
                $task,
                $userJob,
                $alikeQualities,
                $hasher,
                $durationDto,
                $validTargetMd5,
                $firstRepetitionId,
                $isSourceEditable,
                $useSourceForReference,
                $count,
            );
        }

        // finally, we have to update the master segment
        $this->eventDispatcher->dispatch(new SegmentProcessedEvent(
            $task->getTaskGuid(),
            (int) $master->getId(),
        ));

        // Update qualities for cases when we need full list of task's segments to be analysed for quality detection
        $this->qualityManager->postProcessTask($task, editor_Segment_Processing::ALIKE);

        $this->taskProgress->refreshProgress($task, $userJob->getUserGuid(), fireEvent: true);
    }

    /**
     * @param string[] $validTargetMd5
     */
    private function replaceAlike(
        Segment $master,
        Segment $repetition,
        Task $task,
        UserJob $userJob,
        AlikeQualities $alikeQualities,
        RepetitionHash $hasher,
        stdClass $duration,
        array $validTargetMd5,
        int $firstRepetitionId,
        bool $isSourceEditable,
        bool $useSourceForReference,
        int $alikeCount,
    ): void {
        $history = null;

        try {
            $oldHash = $repetition->getTargetMd5();

            // if neither source nor target hashes are matching,
            // then the segment is not alike of the edited segment => we ignore and log it
            if (! $this->isValidSegment($repetition, $master, $validTargetMd5)) {
                error_log(
                    sprintf(
                        'Incorrect segments processed: MasterSegment: %s, Repetition: %s',
                        $master->getId(),
                        $repetition->getId()
                    )
                );

                return;
            }

            $history = $repetition->getNewHistoryEntity();
            $repetition->setTimeTrackData($duration, $alikeCount);

            if ($repetition->getId() === $master->getId()) {
                return;
            }

            if ($repetition->getTaskGuid() !== $master->getTaskGuid() || ! $repetition->isEditable()) {
                return;
            }

            // updateSegmentContent does replace the masters tags with the original repetition ones
            // if there was an error in taking over the segment content into the repetition (returning false) the segment must be ignored

            $sourceSuccess = true;
            $isSourceRepetition = $master->getSourceMd5() === $repetition->getSourceMd5();
            //  if isSourceEditable, then update also the source field
            // if $isSourceRepetition, then update also the source field to overtake changed terms in the source
            if ($isSourceEditable || $isSourceRepetition) {
                $sourceSuccess = $this->repetitionUpdater->updateSource($master, $repetition, $isSourceEditable);
            }

            $useSourceTags = $useSourceForReference
                || empty($master->getTarget())
                || 0 !== (int) $master->getPretrans();

            if (! $sourceSuccess || ! $this->repetitionUpdater->updateTarget($master, $repetition, $useSourceTags)) {
                //the segment has to be ignored!
                return;
            }

            if ($master->getStateId() !== null) {
                $repetition->setStateId((int) $master->getStateId());
            }
            $repetition->setUserName($master->getUserName());
            $repetition->setUserGuid($master->getUserGuid());
            $repetition->setWorkflowStep($master->getWorkflowStep());
            $repetition->setWorkflowStepNr((int) $master->getWorkflowStepNr());

            $newMatchRate = $task->isTranslation()
                ? FileBasedInterface::REPETITION_MATCH_VALUE
                : $master->getMatchRate();

            // First occurrence should always keep its initial match rate
            // All the other occurrences should get 102% or stay higher
            if ((int) $repetition->getId() !== $firstRepetitionId) {
                $repetition->setMatchRate(max(
                    $newMatchRate,
                    FileBasedInterface::REPETITION_MATCH_VALUE
                ));
            }
            $repetition->setMatchRateType($master->getMatchRateType());

            $repetition->setAutoStateId($this->autoStates->calculateAlikeState($repetition, $userJob));

            $matchRateType = new MatchRateType();
            $matchRateType->init($repetition->getMatchRateType());

            if ($matchRateType->isEdited()) {
                $matchRateType->add(MatchRateType::TYPE_AUTO_PROPAGATED);
                $repetition->setMatchRateType((string) $matchRateType);
            }

            // is called before save the alike to the DB,
            // after doing all alike data handling (include recalc of the autostate)
            $this->events->dispatch(new BeforeSaveAlikeEvent($task, $master, $repetition));

            // validate the segment after the repitition updater did it's work and states are set
            $repetition->validate();

            // Quality processing / AutoQA: must be done after validation to not overwrite invalid contents
            if ($isSourceEditable || $isSourceRepetition) {
                //the source was updated by the repetition updater, process them as alike qualities
                $this->qualityManager->processAlikeSegment($repetition, $task, $alikeQualities);
            } else {
                // since the source was not processed
                // we have to trigger here the quality processing as it was a sole segment
                // (this also triggers retagging via termtagger)
                $this->qualityManager->processSegment($repetition, $task, editor_Segment_Processing::EDIT);
            }

            //must be called after validation, since validation does not allow original and originalMd5 updates
            $this->updateTargetHashAndOriginal($repetition, $hasher);

            $history->save();
            $repetition->setTimestamp(NOW_ISO); //see TRANSLATE-922
            $repetition->save();
            $repetition->updateIsTargetRepeated($repetition->getTargetMd5(), $oldHash);
        } catch (Exception $e) {
            /**
             * Any error in connection with the saving process can be ignored by the application,
             * the segment may only not appear in the return to the browser. This means that the
             * segment appears to the user as unproofed and can then be proofread by hand if necessary.
             * It is logged for debugging. (if debugs are active)
             */
            $data = [
                'level' => ZfExtended_Logger::LEVEL_WARN,
                'extra' => [
                    'loadedSegmentMaster' => $master->getDataObject(),
                ],
            ];
            $data['extra']['preparedRepetition'] = $repetition->getDataObject();
            $data['extra']['preparedRepetitionHistory'] = $history?->getDataObject();

            $this->logger->exception($e, $data);
        } finally {
            // always dispatch the event, even if the segment was not saved
            $this->eventDispatcher->dispatch(new SegmentProcessedEvent(
                $task->getTaskGuid(),
                (int) $repetition->getId(),
            ));
        }
    }

    /**
     * checks if the chosen segment may be modified
     * if targetMd5 hashes are recalculated on editing, we have to consider also the hashes in the history of the
     * master segment. See TRANSLATE-885 for details!
     *
     * @param string[] $validTargetMd5
     */
    private function isValidSegment(Segment $alikeSegment, Segment $sourceSegment, array $validTargetMd5): bool
    {
        //the source hash must be just equal
        $sourceMatch = $sourceSegment->getSourceMd5() === $alikeSegment->getSourceMd5();

        //the target hash must be one of the previous hashes or the current one:
        $targetMatch = in_array($alikeSegment->getTargetMd5(), $validTargetMd5);

        if (! $sourceMatch && ! $targetMatch) {
            return false;
        }

        if (empty($sourceSegment->meta()->getSegmentDescriptor())) {
            return true;
        }

        return $sourceSegment->meta()->getSegmentDescriptor() === $alikeSegment->meta()->getSegmentDescriptor();
    }

    /**
     * Updates the target hash and targetOriginal value of the repetition, if a hasher instance is given.
     */
    private function updateTargetHashAndOriginal(Segment $segment, RepetitionHash $hasher)
    {
        $segment->setTargetMd5($hasher->rehashTarget($segment));
    }

    /**
     * Applies the given Closure for each editable segment field
     * (currently only source and target! Since ChangeAlikes are deactivated for alternatives)
     * Closure Parameters: $field → 'target' or 'source'
     */
    private function updateDuration(string $field, stdClass $durationDto, int $duration): void
    {
        $editField = $field . editor_Models_SegmentFieldManager::_EDIT_SUFFIX;
        $durationDto->$editField = $duration;
    }

    /**
     * @return string[]
     * @throws \ZfExtended_Models_Entity_NotFoundException
     */
    private function getValidTargetMd5(Segment $sourceSegment): array
    {
        // without a hasher instance no hashes changes, so we don't have to load the history
        // load first target hardcoded only, since repetitions may not work with multiple alternatives
        $historyEntries = $this->segmentHistoryRepository->getHistoryDataBySegmentId(
            (int) $sourceSegment->getId(),
            SegmentField::TYPE_TARGET,
            3
        );
        $validTargetMd5 = array_column($historyEntries, 'originalMd5');

        //the current targetMd5 hash is valid in any case
        $validTargetMd5[] = $sourceSegment->getTargetMd5();

        //remove the empty segment hashes from the valid list, since empty targets are no repetition
        return array_diff(array_unique($validTargetMd5), [$sourceSegment::EMPTY_STRING_HASH]);
    }
}
