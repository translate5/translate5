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

use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\Repository\SegmentRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Segment\Repetition\Event\RepetitionProcessingFailedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use ZfExtended_Worker_Abstract;

class RepetitionWorker extends ZfExtended_Worker_Abstract
{
    /**
     * @var int[]
     */
    private array $repetitionIds;

    private int $duration;

    private int $masterId;

    private int $userJobId;

    private string $columnToEdit;

    private readonly RepetitionService $repetitionService;

    private readonly SegmentRepository $segmentRepository;

    private readonly UserJobRepository $userJobRepository;

    private readonly EventDispatcherInterface $eventDispatcher;

    public function __construct()
    {
        parent::__construct();
        $this->log = \Zend_Registry::get('logger')->cloneMe('editor.repetition.worker');
        $this->repetitionService = RepetitionService::create();
        $this->segmentRepository = SegmentRepository::create();
        $this->userJobRepository = UserJobRepository::create();
        $this->eventDispatcher = EventDispatcher::create();
    }

    protected function validateParameters(array $parameters): bool
    {
        if (! array_key_exists('repetitionIds', $parameters)) {
            return false;
        }

        $this->repetitionIds = array_map('intval', (array) $parameters['repetitionIds']);

        if (! array_key_exists('masterId', $parameters)) {
            return false;
        }

        $this->masterId = (int) $parameters['masterId'];

        if (! array_key_exists('duration', $parameters)) {
            return false;
        }

        $this->duration = (int) $parameters['duration'];

        if (! array_key_exists('userJobId', $parameters)) {
            return false;
        }

        $this->userJobId = (int) $parameters['userJobId'];

        $this->columnToEdit = $parameters['columnToEdit'] ?? 'targetEdit';

        return true;
    }

    protected function handleWorkerException(\Throwable $workException): void
    {
        $this->eventDispatcher->dispatch(new RepetitionProcessingFailedEvent(
            $this->masterId,
            $this->repetitionIds,
        ));

        parent::handleWorkerException($workException);
    }

    protected function work(): bool
    {
        $master = $this->segmentRepository->get($this->masterId);

        $this->repetitionService->replaceBatch(
            $master,
            $this->userJobRepository->get($this->userJobId),
            $this->repetitionIds,
            $this->duration,
            $this->columnToEdit,
        );

        return true;
    }
}
