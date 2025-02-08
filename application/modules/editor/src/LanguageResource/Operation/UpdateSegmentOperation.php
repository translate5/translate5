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

namespace MittagQI\Translate5\LanguageResource\Operation;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Segment as Segment;
use editor_Models_Task as Task;
use editor_Services_Connector;
use editor_Services_Connector_Exception;
use editor_Services_Exceptions_NoService;
use editor_Services_Manager;
use MittagQI\Translate5\LanguageResource\Adapter\Exception\SegmentUpdateException;
use MittagQI\Translate5\LanguageResource\Adapter\UpdatableAdapterInterface;
use MittagQI\Translate5\LanguageResource\TaskTm\Repository\TaskTmRepository;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use Zend_Registry;
use ZfExtended_BadGateway;
use ZfExtended_Factory;
use ZfExtended_Logger;

/**
 * Updates a segment in a Task TM (if it is applicable) or in all integrations (Language Resources)
 * assigned to the task segment belongs to
 */
class UpdateSegmentOperation
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly LanguageResourceRepository $languageResourceRepository,
        private readonly TaskTmRepository $taskTmRepository,
        private readonly editor_Services_Manager $serviceManager,
        private readonly ZfExtended_Logger $logger,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            TaskRepository::create(),
            new LanguageResourceRepository(),
            new TaskTmRepository(),
            ZfExtended_Factory::get(editor_Services_Manager::class),
            Zend_Registry::get('logger')->cloneMe('editor.languageresource.service'),
        );
    }

    public function updateSegment(Segment $segment): void
    {
        // segments with empty sources or targets will not be updated
        if ($segment->hasEmptySource() || $segment->hasEmptyTarget()) {
            return;
        }

        $data = $this->languageResourceRepository->getAssociatedToTaskGroupedByType($segment->getTaskGuid());

        /** @var LanguageResource[] $taskTms */
        $taskTms = $this->taskTmRepository->getAllCreatedForTask($segment->getTaskGuid());

        foreach ($taskTms as $taskTm) {
            if (! isset($data[$taskTm->getServiceType()])) {
                continue;
            }

            $this->updateSegmentInTaskTm($segment, $taskTm->getServiceType());

            unset($data[$taskTm->getServiceType()]);
        }

        foreach ($data as $languageResourcesData) {
            $this->updateSegmentInIntegrations($segment, $languageResourcesData);
        }
    }

    private function updateSegmentInTaskTm(
        Segment $segment,
        string $serviceType
    ): void {
        $task = $this->taskRepository->getByGuid($segment->getTaskGuid());

        $taskTmLanguageResources = $this->taskTmRepository->getOfTypeAssociatedToTask(
            $segment->getTaskGuid(),
            $serviceType
        );

        $atLeastOneUpdated = false;
        foreach ($taskTmLanguageResources as $taskTmLanguageResource) {
            $connector = $this->getConnector($taskTmLanguageResource, $task);

            $connector->update(
                $segment,
                [
                    UpdatableAdapterInterface::RECHECK_ON_UPDATE => true,
                    UpdatableAdapterInterface::RESCHEDULE_UPDATE_ON_ERROR => true,
                ]
            );

            $atLeastOneUpdated = true;
        }

        if (! $atLeastOneUpdated) {
            $this->logger->error('E1629', 'Task doesn\'t have assigned task TM', [
                'segment' => $segment,
                'serviceType' => $serviceType,
            ]);
        }
    }

    private function updateSegmentInIntegrations(Segment $segment, array $languageResourcesData): void
    {
        $task = $this->taskRepository->getByGuid($segment->getTaskGuid());

        foreach ($languageResourcesData as $languageResourceData) {
            $languageResource = ZfExtended_Factory::get(LanguageResource::class);
            // TODO $assumeDatabase is skipped here which leads to that we can not manipulate language resourse
            // inside of the connector. Need to check if we can normally load language resource from DB here.
            $languageResource->init($languageResourceData);

            if (empty($languageResourceData['segmentsUpdateable'])) {
                continue;
            }

            try {
                $connector = $this->getConnector($languageResource, $task);

                $connector->update(
                    $segment,
                    [
                        UpdatableAdapterInterface::RECHECK_ON_UPDATE => true,
                        UpdatableAdapterInterface::RESCHEDULE_UPDATE_ON_ERROR => true,
                    ]
                );
            } catch (SegmentUpdateException) {
                // Ignore the error here as it is already logged in the connector so nothing to do here
            } catch (editor_Services_Exceptions_NoService|editor_Services_Connector_Exception|ZfExtended_BadGateway $e) {
                $extraData = [
                    'languageResource' => $languageResource,
                    'task' => $task,
                ];

                $e->addExtraData($extraData);

                $event = $this->logger->exception(
                    $e,
                    [
                        'level' => ZfExtended_Logger::LEVEL_WARN,
                    ],
                    true
                );

                // TODO move to the editor_Models_Segment_Updater::afterSegmentUpdate as it is applicable only there
                editor_Services_Manager::reportTMUpdateError(null, $event?->message, $event?->eventCode);

                continue;
            }
        }
    }

    private function getConnector(
        LanguageResource $languageResource,
        Task $task
    ): UpdatableAdapterInterface|editor_Services_Connector {
        return $this->serviceManager->getConnector(
            $languageResource,
            config: $task->getConfig(),
            customerId: (int) $task->getCustomerId(),
        );
    }
}
