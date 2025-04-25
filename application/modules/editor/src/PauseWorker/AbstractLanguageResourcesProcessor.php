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

namespace MittagQI\Translate5\PauseWorker;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Task as Task;
use editor_Services_Manager as Manager;
use Exception;
use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;
use Throwable;
use ZfExtended_Factory;

abstract class AbstractLanguageResourcesProcessor
{
    private Manager $manager;

    public function __construct()
    {
        $this->manager = ZfExtended_Factory::get(Manager::class);
    }

    protected function getWaitingStrategyByStatus(Task $task, int ...$languageResourceIds): WaitingStrategy
    {
        foreach ($languageResourceIds as $languageResourceId) {
            $languageResource = ZfExtended_Factory::get(LanguageResource::class);

            try {
                $languageResource->load($languageResourceId);
            } catch (Throwable $e) {
                // We don't care here in case language resource can not be loaded, it will be processed further
                continue;
            }

            $resource = $this->manager->getResource($languageResource);

            try {
                $connector = $this->manager->getConnector(
                    $languageResource,
                    $task->getSourceLang(),
                    $task->getTargetLang(),
                    $task->getConfig(),
                    (int) $task->getCustomerId(),
                );
                $status = $connector->getStatus($resource, $languageResource);

                if (in_array($status, $this->getStatusesRequireWaiting(), true)) {
                    return in_array($status, $this->getStatusesForWaitWithoutTimeout(), true)
                        ? WaitingStrategy::WaitWithoutTimeout
                        : WaitingStrategy::WaitWithTimeout;
                }
            } catch (Exception) {
                // Do nothing here to make worker decide what to do with such language resource further
            }
        }

        return WaitingStrategy::DontWait;
    }

    private function getStatusesRequireWaiting(): array
    {
        return [
            LanguageResourceStatus::IMPORT,
            LanguageResourceStatus::REORGANIZE_IN_PROGRESS,
            LanguageResourceStatus::WAITING_FOR_LOADING,
            LanguageResourceStatus::LOADING,
            LanguageResourceStatus::CONVERTING,
        ];
    }

    private function getStatusesForWaitWithoutTimeout(): array
    {
        return [
            LanguageResourceStatus::CONVERTING,
        ];
    }
}
