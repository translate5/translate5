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

namespace MittagQI\Translate5\Segment;

use MittagQI\Translate5\Segment\Repetition\DTO\ReplaceDto as ReplaceRepetitionDto;
use MittagQI\Translate5\Segment\Repetition\RepetitionService;
use MittagQI\Translate5\Segment\SearchAndReplace\DTO\ReplaceDto as SearchAndReplaceDto;
use MittagQI\Translate5\Segment\SearchAndReplace\SearchAndReplaceService;
use MittagQI\Translate5\Segment\SyncStatus\DTO\SyncDto;
use MittagQI\Translate5\Segment\SyncStatus\SyncStatusService;

class QueuedBatchUpdateWorker extends \ZfExtended_Worker_Abstract
{
    private readonly RepetitionService $repetitionService;

    private readonly SearchAndReplaceService $searchAndReplaceService;

    private readonly SyncStatusService $syncStatusService;

    public function __construct()
    {
        parent::__construct();
        $this->repetitionService = RepetitionService::create();
        $this->searchAndReplaceService = SearchAndReplaceService::create();
        $this->syncStatusService = SyncStatusService::create();
    }

    protected function validateParameters(array $parameters): bool
    {
        return ! empty($parameters['dto']);
    }

    protected function work(): bool
    {
        $dto = $this->getModel()->getParameters()['dto'];

        match ($dto::class) {
            ReplaceRepetitionDto::class => $this->repetitionService->replaceBatch($dto),
            SearchAndReplaceDto::class => $this->searchAndReplaceService->replaceAll($dto),
            SyncDto::class => $this->syncStatusService->syncAll($dto),
            default => throw new \InvalidArgumentException('Unsupported DTO type: ' . $dto::class),
        };

        return true;
    }
}
