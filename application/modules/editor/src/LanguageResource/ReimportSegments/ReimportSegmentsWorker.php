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

namespace MittagQI\Translate5\LanguageResource\ReimportSegments;

use DateTimeImmutable;
use editor_Models_Task_AbstractWorker;
use MittagQI\Translate5\LanguageResource\ReimportSegments\Action\ReimportSnapshot;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use Throwable;
use ZfExtended_ErrorCodeException;

class ReimportSegmentsWorker extends editor_Models_Task_AbstractWorker
{
    private const MAX_RETRIES = 5;

    private LanguageResourceRepository $languageResourceRepository;

    private ReimportSegmentsLoggerProvider $loggerProvider;

    private ReimportSegmentsQueue $reimportSegmentsQueue;

    public function __construct()
    {
        parent::__construct();

        $this->languageResourceRepository = new LanguageResourceRepository();
        $this->loggerProvider = new ReimportSegmentsLoggerProvider();
        $this->reimportSegmentsQueue = new ReimportSegmentsQueue();
    }

    protected function validateParameters(array $parameters): bool
    {
        return ! empty($parameters['languageResourceId']) && ! empty($parameters['runId']);
    }

    protected function work(): bool
    {
        $params = $this->workerModel->getParameters();
        $runId = $params['runId'];
        $languageResourceId = (int) $params['languageResourceId'];
        $currentRun = $params['currentRun'] ?? 0;
        $segmentsToImport = $params['reimportOnlyIds'] ?? [];

        $result = ReimportSnapshot::create()->reimport($this->task, $runId, $languageResourceId, $segmentsToImport);

        if (! empty($result->failedSegmentIds) && $currentRun < self::MAX_RETRIES) {
            $this->reimportSegmentsQueue->queueReimportDelayed(
                $this->task->getTaskGuid(),
                $languageResourceId,
                $runId,
                ++$currentRun,
                new DateTimeImmutable('+ 15 minutes'),
                $result->failedSegmentIds,
            );
        }

        return true;
    }

    protected function handleWorkerException(Throwable $workException): void
    {
        $params = $this->workerModel->getParameters();
        $languageResourceId = (int) $params['languageResourceId'];
        $languageResource = $this->languageResourceRepository->get($languageResourceId);

        $this->loggerProvider->getLogger()->error(
            'E1169',
            'Task reimport in TM failed - please check log for reason and restart!',
            [
                'task' => $this->task,
                'languageResource' => $languageResource,
            ]
        );

        if ($workException instanceof ZfExtended_ErrorCodeException) {
            $workException->addExtraData([
                'languageResource' => $languageResource,
            ]);
        }

        parent::handleWorkerException($workException);
    }
}
