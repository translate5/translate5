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

use MittagQI\Translate5\Task\Import\ImportService;

/**
 * Contains the Import Worker (the scheduling parts)
 * The import process itself is encapsulated in editor_Models_Import_Worker_Import
 */
class editor_Models_Import_CliImportWorker extends ZfExtended_Worker_Abstract
{
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = []): bool
    {
        return true;
    }

    /**
     * (non-PHPdoc)
     * @throws ReflectionException
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work(): bool
    {
        $importService = new ImportService();

        $parameters = $this->workerModel->getParameters();

        $pm = ZfExtended_Factory::get(ZfExtended_Models_User::class);
        $pm->loadByGuid($parameters['pmGuid']);

        $customer = ZfExtended_Factory::get(editor_Models_Customer_Customer::class);
        $customer->loadByNumber($parameters['customerNumber']);

        $language = ZfExtended_Factory::get(editor_Models_Languages::class);
        if (! is_numeric($parameters['source'])) {
            $language->loadByRfc5646($parameters['source']);
            $parameters['source'] = $language->getId();
        }

        if (! is_null($parameters['pivotLanguage']) && ! is_numeric($parameters['pivotLanguage'])) {
            $language->loadByRfc5646($parameters['pivotLanguage']);
            $parameters['pivotLanguage'] = $language->getId();
        }

        $project = $this->createProject(
            $pm,
            $customer,
            $parameters['source'],
            $parameters['taskName'],
            $parameters['workflow'],
            $parameters['description'],
            $parameters['pivotLanguage'] ?? null
        );

        foreach ($parameters['targets'] as $idx => $lang) {
            if (! is_numeric($lang)) {
                $language->loadByRfc5646($lang);
                $parameters['targets'][$idx] = $language->getId();
            }
        }

        $data = [
            'pmGuid' => $project->getPmGuid(),
            'pmName' => $project->getPmName(),
            'targetLang' => $parameters['targets'],
        ];

        $importService->prepareTaskType(
            $project,
            count($parameters['targets']) > 1,
            editor_Task_Type_ProjectTask::ID
        );

        $dataprovider = ZfExtended_Factory::get(editor_Models_Import_DataProvider_Factory::class);
        $importService->import($project, $dataprovider->createFromPath($parameters['path']), $data, $pm);
        $importService->startWorkers($project);

        return true;
    }

    /**
     * @throws ReflectionException
     */
    private function createProject(
        ZfExtended_Models_User $pm,
        editor_Models_Customer_Customer $customer,
        int $sourceLang,
        string $taskName,
        string $workflow,
        ?string $description,
        ?int $pivotLanguage,
    ): editor_Models_Task {
        $project = ZfExtended_Factory::get(editor_Models_Task::class);
        $project->createTaskGuidIfNeeded();
        $project->setImportAppVersion(ZfExtended_Utils::getAppVersion());

        $project->setSourceLang($sourceLang);
        if (! is_null($pivotLanguage)) {
            $project->setRelaisLang($pivotLanguage);
        }
        $project->setTaskName($taskName);
        $project->setPmGuid($pm->getUserGuid());
        $project->setPmName($pm->getUsernameLong());
        $project->setOrderdate((new DateTime())->format('Y-m-d H:i:s'));

        if ($description) {
            $project->setDescription($description);
        }

        $project->setCustomerId($customer->getId());
        $config = $customer->getConfig();

        $project->setUsageMode($config->runtimeOptions->import->initialTaskUsageMode);
        $project->setWorkflow($workflow);

        return $project;
    }
}
