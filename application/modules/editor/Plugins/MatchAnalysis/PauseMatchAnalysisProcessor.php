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

namespace MittagQI\Translate5\Plugins\MatchAnalysis;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_Manager as Manager;
use editor_Services_OpenTM2_Connector as OpenTm2Connector;
use editor_Services_Connector_FilebasedAbstract as AbstractConnector;
use Exception;
use MittagQI\Translate5\Models\Import\PauseImportWorker;
use MittagQI\Translate5\Models\Import\PauseWorkerProcessorInterface;
use editor_Models_Task as Task;
use ZfExtended_Factory;

class PauseMatchAnalysisProcessor implements PauseWorkerProcessorInterface
{
    private $config;

    public function __construct()
    {
        $this->config = \Zend_Registry::get('config');
    }

    public function shouldWait(Task $task): bool
    {
        $manager = ZfExtended_Factory::get(Manager::class);
        $languageResourcesData = ZfExtended_Factory::get(LanguageResource::class)
            ->loadByAssociatedTaskGuidListAndServiceTypes([$task->getTaskGuid()], [Manager::SERVICE_OPENTM2]);

        foreach ($languageResourcesData as $languageResourceData) {
            $languageResource = ZfExtended_Factory::get(LanguageResource::class);
            $languageResource->load($languageResourceData['id']);

            $resource = $manager->getResource($languageResource);

            try {
                /** @var OpenTm2Connector $connector */
                $connector = $manager->getConnector($languageResource, (int)$task->getSourceLang(), (int)$task->getTargetLang(), $task->getConfig());

                return AbstractConnector::STATUS_IMPORT === $connector->getStatus($resource);

            } catch (Exception $exception) {
            }
        }

        return false;
    }

    public function getMaxWaitTime(): int
    {
        return $this->config->runtimeOptions->worker->{PauseMatchAnalysisWorker::class}->maxPauseTime;
    }

    public function getSleepTime(): int
    {
        // Config?
        return 5;
    }
}
