<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2025 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a paid plug-in for translate5.

 The translate5 core software and its freely downloadable plug-ins are licensed under an AGPLv3 open-source license
 (https://www.gnu.org/licenses/agpl-3.0.en.html).
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 Paid translate5 plugins can deviate from standard AGPLv3 licensing and therefore constitute an
 exception. As such, translate5 plug-ins can be licensed under either AGPLv3 or GPLv3 (see below for details).

 Briefly summarized, a GPLv3 license dictates the same conditions as its AGPLv3 variant, except that it
 does not require the program (plug-in, in this case) to direct users toward its download location if it is
 only being used via the web in a browser.
 This enables developers to write custom plug-ins for translate5 and keep them private, granted they
 meet the GPLv3 licensing conditions stated above.
 As the source code of this paid plug-in is under open source GPLv3 license, everyone who did obtain
 the source code could pass it on for free or paid to other companies or even put it on the web for
 free download for everyone.

 As this would undermine completely the financial base of translate5s development and the translate5
 community, we at MittagQI would not longer support a company or supply it with updates for translate5,
 that would pass on the source code to third parties.

 Of course as long as the code stays within the company who obtained it, you are free to do
 everything you want with the source code (within the GPLv3 boundaries), like extending it or installing
 it multiple times.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5. This plug-in exception allows using GPLv3 for translate5 plug-ins,
 although translate5 core is licensed under AGPLv3.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/
declare(strict_types=1);

namespace MittagQI\Translate5\Segment\SyncStatus;

use editor_Models_Segment;
use editor_Models_Segment_AutoStates;
use editor_Segment_FieldTags;
use editor_Segment_Internal_TagComparision;
use Exception;
use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\Repository\SegmentRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\Segment\Event\SegmentProcessedEvent;
use MittagQI\Translate5\Segment\Operation\DTO\ContextDto;
use MittagQI\Translate5\Segment\Operation\DTO\DurationsDto;
use MittagQI\Translate5\Segment\Operation\DTO\UpdateSegmentDto;
use MittagQI\Translate5\Segment\Operation\UpdateFlow;
use MittagQI\Translate5\Segment\Operation\UpdateSegmentOperation;
use MittagQI\Translate5\Segment\QueuedBatchUpdateWorker;
use MittagQI\Translate5\Segment\SyncStatus\DTO\SyncDto;
use MittagQI\Translate5\Segment\SyncStatus\Event\SyncStatusProcessingFailedEvent;
use MittagQI\Translate5\Segment\SyncStatus\Event\SyncStatusProcessingRequestedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Throwable;
use Zend_Registry;
use ZfExtended_Logger;

class SyncStatusService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly SearchService $searchService,
        private readonly UpdateSegmentOperation $updateSegmentOperation,
        private readonly SegmentRepository $segmentRepository,
        private readonly ZfExtended_Logger $logger,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UserRepository $userRepository,
        private readonly editor_Models_Segment_AutoStates $autoStates,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            TaskRepository::create(),
            SearchService::create(),
            UpdateSegmentOperation::create(),
            SegmentRepository::create(),
            Zend_Registry::get('logger')->cloneMe('editor.segment.syncStatus'),
            EventDispatcher::create(),
            new UserRepository(),
            new editor_Models_Segment_AutoStates()
        );
    }

    public function queueSyncAll(SyncDto $syncDto): void
    {
        $worker = new QueuedBatchUpdateWorker();

        if ($worker->init(
            $syncDto->taskGuid,
            [
                'dto' => $syncDto,
            ]
        )) {
            $worker->queue();
        }
    }

    public function syncAll(SyncDto $dto): void
    {
        $segments = $this->searchService->search($dto);
        if ($segments instanceof \Iterator && ! $segments->valid()) {
            return;
        }

        $task = $this->taskRepository->getByGuid($dto->taskGuid);
        if (! $task->getLockLocked()) {
            return;
        }

        $toDraft = ($dto->autoStateId === editor_Models_Segment_AutoStates::DRAFT);
        // always true for drafts
        $userCanIgnoreTagValidation = ($toDraft
            || $task->getConfig()->runtimeOptions->segments?->userCanIgnoreTagValidation);
        $tagValidationFailed = false;

        $segmentIds = [];
        /* @var $segment editor_Models_Segment */
        foreach ($segments as $segment) {
            if (! $this->autoStates->isLocked((int) $segment->getAutoStateId())
                && ! $this->autoStates->isBlocked((int) $segment->getAutoStateId())
                && ! $segment->meta()->getLocked()
                && $segment->isTargetTranslated()) {
                if (! $userCanIgnoreTagValidation) {
                    $comparison = new editor_Segment_Internal_TagComparision(
                        $segment->getFieldTags($task, 'target'),
                        new editor_Segment_FieldTags(
                            $task,
                            (int) $segment->getId(),
                            $segment->getSource(),
                            'source',
                            'original'
                        )
                    );

                    if (! $comparison->hasValidInternalTags()) {
                        $tagValidationFailed = true;

                        continue;
                    }
                }
                $segmentIds[] = (int) $segment->getId();
            }
        }

        $user = $this->userRepository->getByGuid($dto->actorGuid);

        if (! $userCanIgnoreTagValidation) {
            $tagValidationTracking = new TagValidationTracking($task, (int) $user->getId());
            $tagValidationTracking->update($tagValidationFailed ? 'WARN' : 'OK');
            //FIXME testen mit invaliden Tags!
        }

        if (empty($segmentIds)) {
            return;
        }

        $this->eventDispatcher->dispatch(
            new SyncStatusProcessingRequestedEvent(
                $dto->taskGuid,
                $segmentIds
            )
        );

        try {
            $this->executeSyncAll($dto, $segmentIds);
            $task->logger('editor.workflow')->info(
                'E1012',
                'Segments were set to ' . ($toDraft ? 'draft' : 'final processing') . ' status',
                [
                    'segmentIds' => $segmentIds,
                    'userGuid' => $user->getUserGuid(),
                    'user' => $user->getUsernameLong(),
                ]
            );
        } catch (Throwable) {
            $this->eventDispatcher->dispatch(
                new SyncStatusProcessingFailedEvent(
                    $dto->taskGuid,
                    $segmentIds
                )
            );
        }
    }

    private function executeSyncAll(SyncDto $dto, array $segmentIds): void
    {
        $task = $this->taskRepository->getByGuid($dto->taskGuid);
        $actor = $this->userRepository->getByGuid($dto->actorGuid);
        $updateSegmentDto = new UpdateSegmentDto(
            [],
            new DurationsDto(
                durations: (object) [],
                divisor: 1
            ),
            autoStateId: $dto->autoStateId,
        );

        /* @var $segment editor_Models_Segment */
        foreach ($segmentIds as $segmentId) {
            $segment = $this->segmentRepository->get($segmentId);

            try {
                // update segment status
                $this->updateSegmentOperation->update(
                    $segment,
                    $updateSegmentDto,
                    new ContextDto(UpdateFlow::SynchronizeStatus),
                    $actor
                );
            } catch (Exception $e) {
                /**
                 * Any exception on saving a segment should not break the whole loop.
                 * But the problem should be logged, and also the user should be informed in the GUI
                 */
                $this->logger->exception($e, [
                    'level' => ZfExtended_Logger::LEVEL_WARN,
                    'extra' => [
                        'task' => $task,
                        'loadedSegment' => $segment->toArray(),
                    ],
                ]);
            } finally {
                // always dispatch the event, even if the segment was not saved
                $this->eventDispatcher->dispatch(
                    new SegmentProcessedEvent(
                        $task->getTaskGuid(),
                        (int) $segment->getId(),
                    )
                );
            }
        }
    }
}
