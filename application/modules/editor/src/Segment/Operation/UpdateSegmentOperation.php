<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

namespace MittagQI\Translate5\Segment\Operation;

use editor_Models_Segment as Segment;
use editor_Models_Segment_Updater;
use editor_Models_TaskProgress;
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Feasibility\ActionFeasibilityAssertInterface;
use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Segment\ActionAssert\Feasibility\SegmentActionFeasibilityAssert;
use MittagQI\Translate5\Segment\Event\SegmentUpdatedEvent;
use MittagQI\Translate5\Segment\Operation\Contract\UpdateSegmentOperationInterface;
use MittagQI\Translate5\Segment\Operation\DTO\UpdateSegmentDto;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\Workflow\Assert\WriteableWorkflowAssert;
use Psr\EventDispatcher\EventDispatcherInterface;
use ZfExtended_Models_Messages;

class UpdateSegmentOperation implements UpdateSegmentOperationInterface
{
    public function __construct(
        private readonly WriteableWorkflowAssert $writeableWorkflowAssert,
        private readonly ActionFeasibilityAssertInterface $feasibilityAssert,
        private readonly TaskRepository $taskRepository,
        private readonly editor_Models_TaskProgress $taskProgress,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public static function create(): self
    {
        return new self(
            WriteableWorkflowAssert::create(),
            SegmentActionFeasibilityAssert::create(),
            TaskRepository::create(),
            new editor_Models_TaskProgress(),
            EventDispatcher::create(),
        );
    }

    public function update(
        Segment $segment,
        UpdateSegmentDto $updateDto,
        User $actor,
        UpdateSegmentLogger $updateLogger,
        ?ZfExtended_Models_Messages $restMessages = null
    ): void {
        $this->feasibilityAssert->assertAllowed(Action::Update, $segment);

        $this->writeableWorkflowAssert->assert($segment->getTaskGuid(), $actor->getUserGuid());

        $task = $this->taskRepository->getByGuid($segment->getTaskGuid());

        //the history entry must be created before the original entity is modified
        $history = $segment->getNewHistoryEntity();
        //update the segment
        $updater = new editor_Models_Segment_Updater($task, $actor->getUserGuid(), $updateLogger);

        $segment->setTimeTrackData($updateDto->durations->durations, $updateDto->durations->divisor);

        if (null !== $updateDto->stateId) {
            $segment->setStateId($updateDto->stateId);
        }

        if (null !== $updateDto->autoStateId) {
            $segment->setAutoStateId($updateDto->autoStateId);
        }

        if (null !== $updateDto->matchRate) {
            $segment->setMatchRate($updateDto->matchRate);
        }

        if (null !== $updateDto->matchRateType) {
            $segment->setMatchRateType($updateDto->matchRateType);
        }

        $textData = $this->sanitizeEditedContent($updater, $updateDto->textData, $restMessages);

        foreach ($textData as $field => $text) {
            $segment->set($field, $text);
        }

        $segment->setUserGuid($actor->getUserGuid());
        $segment->setUserName($actor->getUserName());

        $updater->update($segment, $history);

        $this->taskProgress->refreshProgress($task, $actor->getUserGuid(), fireEvent: true);

        $this->eventDispatcher->dispatch(new SegmentUpdatedEvent($segment));
    }

    /**
     * Applies the import whitespace replacing to the edited user by the content
     *
     * @param array<string, string> $textData
     */
    protected function sanitizeEditedContent(
        editor_Models_Segment_Updater $updater,
        array $textData,
        ?ZfExtended_Models_Messages $restMessages
    ): array {
        $sanitized = false;
        $result = [];

        foreach ($textData as $field => $text) {
            $sanitized = $updater->sanitizeEditedContent($text, $field) || $sanitized;
            $result[$field] = $text;
        }

        if ($sanitized && null !== $restMessages) {
            $restMessages->addWarning('Aus dem Segment wurden nicht darstellbare Zeichen entfernt (mehrere Leerzeichen, Tabulatoren, Zeilenumbrüche etc.)!');
        }

        return $result;
    }
}
