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

namespace MittagQI\Translate5\LanguageResource\TaskTm\Workflow\Executors;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Task as Task;
use MittagQI\Translate5\LanguageResource\Exception\ReimportQueueException;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsOptions;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsQueue;
use MittagQI\Translate5\LanguageResource\TaskTm\Repository\TaskTmRepository;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use ZfExtended_Factory;
use ZfExtended_Logger;

class ReimportSegmentsActionExecutor
{
    public function __construct(
        private readonly ZfExtended_Logger $logger,
        private readonly ReimportSegmentsQueue $languageResourceReimportQueue,
        private readonly LanguageResourceRepository $languageResourceRepository,
        private readonly TaskTmRepository $taskTmRepository,
    ) {
    }

    public function reimportSegments(Task $task): void
    {
        $data = $this->languageResourceRepository->getAssociatedToTaskGroupedByType($task->getTaskGuid());
        foreach ($data as $serviceType => $languageResourcesData) {
            $taskTmCreatedForTask = $this->taskTmRepository->findOfTypeCreatedForTask(
                $task->getTaskGuid(),
                $serviceType
            );

            if (null === $taskTmCreatedForTask) {
                continue;
            }

            $this->queueReimport($task, $languageResourcesData, $this->getTaskTmIds($task, $serviceType));
        }
    }

    private function queueReimport(Task $task, array $languageResourcesData, array $taskTmIds): void
    {
        foreach ($languageResourcesData as $languageResourceData) {
            if (in_array((int) $languageResourceData['id'], $taskTmIds, true)) {
                continue;
            }

            $languageResource = ZfExtended_Factory::get(LanguageResource::class);
            $languageResource->init($languageResourceData);

            try {
                $this->languageResourceReimportQueue->queueReimport(
                    $task->getTaskGuid(),
                    (int) $languageResource->getId(),
                    [
                        ReimportSegmentsOptions::FILTER_ONLY_EDITED => false,
                        ReimportSegmentsOptions::USE_SEGMENT_TIMESTAMP => true,
                    ]
                );
            } catch (ReimportQueueException) {
                $this->logger->error(
                    'E0000',
                    'Could not init worker for reimporting segments',
                    [
                        'task' => $task,
                        'languageResource' => $languageResource,
                    ]
                );
            }
        }
    }

    private function getTaskTmIds(Task $task, string $serviceType): array
    {
        $taskTmIds = $this->taskTmRepository->getIdsOfTypeAssociatedToTask($task->getTaskGuid(), $serviceType);

        if (empty($taskTmIds)) {
            /** @phpstan-ignore-next-line */
            $this->logger->warning(
                'E1629',
                'Please note task doesn\'t have assigned task TM, however it was created for the task',
                [
                    'task' => $task,
                    'serviceType' => $serviceType,
                ]
            );
        }

        return $taskTmIds;
    }
}
