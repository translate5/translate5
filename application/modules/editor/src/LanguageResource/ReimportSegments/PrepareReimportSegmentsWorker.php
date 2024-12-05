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

use editor_Models_Task_AbstractWorker;
use MittagQI\Translate5\LanguageResource\TaskAssociation;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use Throwable;
use ZfExtended_ErrorCodeException;
use ZfExtended_Exception;
use ZfExtended_Factory;

class PrepareReimportSegmentsWorker extends editor_Models_Task_AbstractWorker
{
    private LanguageResourceRepository $languageResourceRepository;

    private ReimportSegmentsLoggerProvider $loggerProvider;

    public function __construct()
    {
        parent::__construct();

        $this->languageResourceRepository = new LanguageResourceRepository();
        $this->loggerProvider = new ReimportSegmentsLoggerProvider();
    }

    protected function validateParameters(array $parameters): bool
    {
        return ! empty($parameters['languageResourceId']);
    }

    protected function work(): bool
    {
        $params = $this->workerModel->getParameters();
        $languageResourceId = (int) $params['languageResourceId'];

        if (! $this->isWritableLanguageResourceForTask($this->task->getTaskGuid(), $languageResourceId)) {
            return true;
        }

        $runId = bin2hex(random_bytes(16));

        ReimportSegmentsSnapshot::create()->createSnapshot(
            $this->task,
            $runId,
            $languageResourceId,
            $params[ReimportSegmentsOptions::FILTER_TIMESTAMP] ?? null,
            $params[ReimportSegmentsOptions::FILTER_ONLY_EDITED] ?? false,
            $params[ReimportSegmentsOptions::USE_SEGMENT_TIMESTAMP] ?? false
        );

        $reimportWorker = new ReimportSegmentsWorker();

        $options = [
            'languageResourceId' => $languageResourceId,
            'runId' => $runId,
        ];

        $success = $reimportWorker->init($this->task->getTaskGuid(), $options);

        if (! $success) {
            throw new ZfExtended_Exception('LanguageResource ReImport Error on worker init()');
        }

        $reimportWorker->queue();

        return true;
    }

    private function isWritableLanguageResourceForTask(string $taskGuid, int $languageResourceId): bool
    {
        $assoc = ZfExtended_Factory::get(TaskAssociation::class);
        $assoc->loadByTaskGuidAndTm($taskGuid, $languageResourceId);

        return ! empty($assoc->getSegmentsUpdateable());
    }

    protected function handleWorkerException(Throwable $workException): void
    {
        $params = $this->workerModel->getParameters();
        $languageResourceId = (int) $params['languageResourceId'];
        $languageResource = $this->languageResourceRepository->get($languageResourceId);

        $this->loggerProvider->getLogger()->error(
            'E1169',
            'Creating a snapshot for reimport in TM failed - please check log for reason and restart!',
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
