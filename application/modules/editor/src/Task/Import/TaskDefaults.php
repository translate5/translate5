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

namespace MittagQI\Translate5\Task\Import;

use editor_Models_ConfigException;
use editor_Models_Customer_Customer;
use editor_Models_LanguageResources_CustomerAssoc;
use editor_Models_LanguageResources_Languages;
use editor_Models_Languages;
use editor_Models_Task;
use editor_Models_TaskConfig;
use editor_Models_TaskUserAssoc;
use editor_Models_UserAssocDefault;
use editor_Utils;
use editor_Workflow_Manager;
use MittagQI\Translate5\LanguageResource\TaskAssociation;
use MittagQI\Translate5\LanguageResource\TaskPivotAssociation;
use Throwable;
use Zend_Cache_Exception;
use Zend_Config;
use Zend_Db_Statement_Exception;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;

class TaskDefaults
{
    private Zend_Config $config;

    public function __construct()
    {
        $this->config = Zend_Registry::get('config');
    }

    /***
     * Check and set the default pivot langauge based on customer specific config.
     * If the pivot field is not provided on task post and for the current task customer
     * there is configured defaultPivotLanguage, the configured pivot language will be set as task pivot
     */
    public function setDefaultPivotForProject(
        editor_Models_Task $project,
        ?editor_Models_Customer_Customer $customer = null
    ): void {
        $config = null === $customer ? $this->config : $customer->getConfig();

        if (!empty($config->runtimeOptions->project->defaultPivotLanguage)) {
            // get default pivot language value from the config
            $defaultPivot = $config->runtimeOptions->project->defaultPivotLanguage;
            try {
                /** @var editor_Models_Languages $language */
                $language = ZfExtended_Factory::get(editor_Models_Languages::class);
                $language->loadByRfc5646($defaultPivot);

                $project->setRelaisLang($language->getId());
            }catch (Throwable) {
                // in case of wrong configured variable and the load language fails, do nothing
            }
        }
    }

    /***
     * Sets task defaults for given task (default languageResources, default userAssocs)
     * @param editor_Models_Task $task
     * @param bool $importWizardUsed
     * @throws Zend_Cache_Exception
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws editor_Models_ConfigException
     */
    public function setTaskDefaults(editor_Models_Task $task, bool $importWizardUsed = false): void
    {
        $this->addDefaultLanguageResources($task);
        $this->addDefaultPivotResources($task);
        $this->addDefaultUserAssoc($task);
        if ($importWizardUsed) {
            $this->handlePivotAutostart($task);
        }
    }

    /**
     * Assign language resources by default that are set as useAsDefault for the task's client
     * (but only if the language combination matches).
     *
     * @param editor_Models_Task $task
     * @throws Zend_Cache_Exception
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    private function addDefaultLanguageResources(editor_Models_Task $task): void
    {
        $customerAssoc = ZfExtended_Factory::get(editor_Models_LanguageResources_CustomerAssoc::class);

        $data = $customerAssoc->loadByCustomerIdsUseAsDefault([$task->getCustomerId()]);

        if (empty($data)) {
            return;
        }

        $taskGuid = $task->getTaskGuid();

        $this->findMatchingAssocData(
            (int)$task->getSourceLang(),
            (int)$task->getTargetLang(),
            $data,
            function ($assocRow) use ($taskGuid) {
                $taskAssoc = ZfExtended_Factory::get(TaskAssociation::class);
                $taskAssoc->setLanguageResourceId($assocRow['languageResourceId']);
                $taskAssoc->setTaskGuid($taskGuid);
                if (!empty($assocRow['writeAsDefault'])) {
                    $taskAssoc->setSegmentsUpdateable(true);
                }
                $taskAssoc->save();
            }
        );
        $task->updateIsTerminologieFlag($task->getTaskGuid());
    }

    /**
     * Associate default pivot resources for given task
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws Zend_Cache_Exception
     */
    public function addDefaultPivotResources(editor_Models_Task $task): void
    {
        $customerAssoc = ZfExtended_Factory::get(editor_Models_LanguageResources_CustomerAssoc::class);
        $data = $customerAssoc->loadByCustomerIdsPivotAsDefault([$task->getCustomerId()]);

        if (empty($data)) {
            return;
        }

        $taskGuid = $task->getTaskGuid();

        $this->findMatchingAssocData(
            (int)$task->getSourceLang(),
            (int)$task->getRelaisLang(),
            $data,
            function ($assocRow) use ($taskGuid) {
                /** @var TaskPivotAssociation $pivotAssoc */
                $pivotAssoc = ZfExtended_Factory::get(TaskPivotAssociation::class);
                $pivotAssoc->setLanguageResourceId($assocRow['languageResourceId']);
                $pivotAssoc->setTaskGuid($taskGuid);
                $pivotAssoc->save();
            }
        );
    }

    /**
     * Find matching language resources by task languages and call the callback for saving
     * @throws Zend_Cache_Exception
     */
    private function findMatchingAssocData(
        int $sourceLang,
        int $targetLang,
        array $defaultData,
        callable $saveCallback
    ): void {
        if (0 === $sourceLang || 0 === $targetLang) {
            return;
        }

        $languages = ZfExtended_Factory::get(editor_Models_LanguageResources_Languages::class);
        $language = ZfExtended_Factory::get(editor_Models_Languages::class);

        $sourceLanguages = $language->getFuzzyLanguages($sourceLang, 'id', true);
        $targetLanguages = $language->getFuzzyLanguages($targetLang, 'id', true);

        foreach ($defaultData as $data) {
            $languageResourceId = $data['languageResourceId'];
            $sourceLangMatch = $languages->isInCollection($sourceLanguages, 'sourceLang', $languageResourceId);
            $targetLangMatch = $languages->isInCollection($targetLanguages, 'targetLang', $languageResourceId);

            if ($sourceLangMatch && $targetLangMatch) {
                $saveCallback($data);
            }
        }
    }

    /**
     * Add user which should be associated by default on task creation
     * @param editor_Models_Task $task
     * @throws editor_Models_ConfigException
     */
    private function addDefaultUserAssoc(editor_Models_Task $task): void
    {
        $defaults = ZfExtended_Factory::get(editor_Models_UserAssocDefault::class);
        $defaults = $defaults->loadDefaultsForTask($task);
        if (empty($defaults)) {
            return;
        }

        $taskConfig = ZfExtended_Factory::get(editor_Models_TaskConfig::class);

        foreach ($defaults as $assoc) {
            $manager = ZfExtended_Factory::get(editor_Workflow_Manager::class);

            $workflow = $manager->getCached($assoc['workflow']);

            $role = $workflow->getRoleOfStep($assoc['workflowStepName']);

            $model = ZfExtended_Factory::get(editor_Models_TaskUserAssoc::class);
            $model->setWorkflow($assoc['workflow']);

            $model->setWorkflowStepName($assoc['workflowStepName']);
            $model->setRole($role);

            $model->setTaskGuid($task->getTaskGuid());
            $model->setUserGuid($assoc['userGuid']);

            // if there is default deadline date default config, insert it as task specific config.
            if ($assoc['deadlineDate'] !== null && $assoc['deadlineDate'] > 0) {
                $name = [
                    'runtimeOptions',
                    'workflow',
                    $model->getWorkflow(),
                    $model->getWorkflowStepName(),
                    'defaultDeadlineDate'
                ];
                $taskConfig->updateInsertConfig($task->getTaskGuid(), implode('.', $name), $assoc['deadlineDate']);
            }

            // get deadline date config and set it if exist
            $configValue = $task->getConfig(true)
                ->runtimeOptions
                ->workflow
                ->{$model->getWorkflow()}
                ->{$model->getWorkflowStepName()}
                ->defaultDeadlineDate ?? 0;
            if ($configValue > 0) {
                $model->setDeadlineDate(editor_Utils::addBusinessDays((string)$task->getOrderdate(), $configValue));
            }
            // processing some trackchanges properties that can't be parted out to the trackchanges-plugin
            $model->setTrackchangesShow($assoc['trackchangesShow']);
            $model->setTrackchangesShowAll($assoc['trackchangesShowAll']);
            $model->setTrackchangesAcceptReject($assoc['trackchangesAcceptReject']);

            $model->save();
        }
    }

    /**
     * Disable the pivot autostart in case the import is done via the translate5 UI. For all api imports, the config
     * runtimeOptions.import.autoStartPivotTranslations will decide if the pivot pre-translation is auto-queued
     * @param editor_Models_Task $task
     * @return void
     */
    private function handlePivotAutostart(editor_Models_Task $task): void
    {
        $config = ZfExtended_Factory::get(editor_Models_TaskConfig::class);
        $config->updateInsertConfig($task->getTaskGuid(), 'runtimeOptions.import.autoStartPivotTranslations', false);
    }
}
