<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Plugins\CotiHotfolder\Factory;

use DateTime;
use editor_Models_Customer_Customer as Customer;
use editor_Models_Task as Task;
use editor_Models_Workflow;
use MittagQI\Translate5\Plugins\CotiHotfolder\DTO\InstructionsDTO;
use MittagQI\Translate5\Plugins\CotiHotfolder\Service\ProjectManagerProvider;
use MittagQI\Translate5\Plugins\CotiHotfolder\Service\T5Logger;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Utils;

class ProjectFactory
{
    public function __construct(
        private readonly T5Logger $logger,
        private readonly ProjectManagerProvider $projectManagerProvider,
    ) {
    }

    public static function create(): self
    {
        return new self(
            T5Logger::create(),
            ProjectManagerProvider::create(),
        );
    }

    public function createProject(
        InstructionsDTO $instructions,
        Customer $customer,
        string $foreignId,
    ): Task {
        $project = new Task();
        $project->createTaskGuidIfNeeded();
        $project->setImportAppVersion(ZfExtended_Utils::getAppVersion());

        $project->setSourceLang($instructions->sourceLang);
        $project->setTaskName($instructions->project->name);
        $project->setForeignId($foreignId);
        $project->setDescription($instructions->project->description);
        $project->setOrderdate((new DateTime())->format('Y-m-d H:i:s'));
        $project->setCustomerId((int) $customer->getId());

        $pmUser = $this->projectManagerProvider->getCustomerOrDefaultPm($customer);

        $project->setPmGuid($pmUser->getUserGuid());
        $project->setPmName($pmUser->getUsernameLong());

        $config = $customer->getConfig();

        $project->setEdit100PercentMatch($config->runtimeOptions->import->edit100PercentMatch);
        $project->setUsageMode($config->runtimeOptions->import->initialTaskUsageMode);
        $project->setWorkflow(
            $this->getWorkflow(
                $instructions->project->workflow,
                $config->runtimeOptions->workflow->initialWorkflow,
                $project->getTaskName()
            )
        );
        $project->setDefaultPivotLanguage($project, $customer);

        return $project;
    }

    private function getWorkflow(?string $name, string $defaultWorkflow, string $taskName): string
    {
        if (null === $name) {
            return $defaultWorkflow;
        }

        try {
            $workflow = new editor_Models_Workflow();
            $workflow->loadByName($name);

            return $name;
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            $this->logger->workflowNotFound($taskName, $name);

            return $defaultWorkflow;
        }
    }
}
