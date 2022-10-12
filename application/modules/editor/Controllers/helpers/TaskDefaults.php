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

use MittagQI\Translate5\LanguageResource\TaskAssociation;

/**
 */
class Editor_Controller_Helper_TaskDefaults extends Zend_Controller_Action_Helper_Abstract {

    /***
     * Assign language resources by default that are set as useAsDefault for the task's client
     * (but only if the language combination matches).
     *
     * @param editor_Models_Task $task
     * @throws Zend_Cache_Exception
     */
    public function addDefaultLanguageResources(editor_Models_Task $task): void {
        $customerAssoc = ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
        /* @var $customerAssoc editor_Models_LanguageResources_CustomerAssoc */

        $data = $customerAssoc->loadByCustomerIdsUseAsDefault([$task->getCustomerId()]);

        if(empty($data)) {
            return;
        }

        $taskGuid = $task->getTaskGuid();

        $this->findMatchingAssocData($task->getSourceLang(),$task->getTargetLang(),$data,function ($assocRow) use ($taskGuid){
            $taskAssoc = ZfExtended_Factory::get('MittagQI\Translate5\LanguageResource\TaskAssociation');
            /* @var $taskAssoc MittagQI\Translate5\LanguageResource\TaskAssociation */
            $taskAssoc->setLanguageResourceId($assocRow['languageResourceId']);
            $taskAssoc->setTaskGuid($taskGuid);
            if(!empty($assocRow['writeAsDefault'])){
                $taskAssoc->setSegmentsUpdateable(1);
            }
            $taskAssoc->save();
        });
    }

    /***
     * Associate default pivot resources for given task
     * @param editor_Models_Task $task
     *
     * @return void
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws Zend_Cache_Exception
     */
    public function addDefaultPivotResources(editor_Models_Task $task): void
    {
        $customerAssoc = ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
        /* @var $customerAssoc editor_Models_LanguageResources_CustomerAssoc */

        $data = $customerAssoc->loadByCustomerIdsPivotAsDefault([$task->getCustomerId()]);

        if(empty($data)) {
            return;
        }

        $taskGuid = $task->getTaskGuid();

        $this->findMatchingAssocData($task->getSourceLang(),$task->getRelaisLang(),$data,function ($assocRow) use ($taskGuid){
            /** @var \MittagQI\Translate5\LanguageResource\TaskPivotAssociation $pivotAssoc */
            $pivotAssoc = ZfExtended_Factory::get('\MittagQI\Translate5\LanguageResource\TaskPivotAssociation');
            $pivotAssoc->setLanguageResourceId($assocRow['languageResourceId']);
            $pivotAssoc->setTaskGuid($taskGuid);
            $pivotAssoc->save();
        });
    }

    /***
     * Find matching language resources by task languages and call the callback for saving
     * @param int $sourceLang
     * @param int $targetLang
     * @param array $defaultData
     * @param callable $saveCallback
     * @return void
     * @throws Zend_Cache_Exception
     */
    private function findMatchingAssocData(int $sourceLang, int $targetLang, array $defaultData,callable $saveCallback): void
    {

        if(empty($sourceLang) || empty($targetLang)){
            return;
        }
        $languages = ZfExtended_Factory::get('editor_Models_LanguageResources_Languages');
        /* @var $languages editor_Models_LanguageResources_Languages */
        $language = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $language ZfExtended_Languages */

        $sourceLanguages = $language->getFuzzyLanguages($sourceLang,'id',true);
        $targetLanguages = $language->getFuzzyLanguages($targetLang,'id',true);

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
     */
    public function addDefaultUserAssoc(editor_Models_Task $task){
        $defaults = ZfExtended_Factory::get('editor_Models_UserAssocDefault');
        /* @var $defaults editor_Models_UserAssocDefault */
        $defaults = $defaults->loadDefaultsForTask($task);
        if(empty($defaults)){
            return;
        }

        /* @var $taskConfig editor_Models_TaskConfig */
        $taskConfig = ZfExtended_Factory::get('editor_Models_TaskConfig');

        foreach ($defaults as $assoc){
            
            $manager = ZfExtended_Factory::get('editor_Workflow_Manager');
            /* @var $manager editor_Workflow_Manager */

            $workflow = $manager->getCached($assoc['workflow']);

            $role = $workflow->getRoleOfStep($assoc['workflowStepName']);

            $model = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
            /* @var $model editor_Models_TaskUserAssoc */
            $model->setWorkflow($assoc['workflow']);

            $model->setWorkflowStepName($assoc['workflowStepName']);
            $model->setRole($role);

            $model->setTaskGuid($task->getTaskGuid());
            $model->setUserGuid($assoc['userGuid']);

            // if there is default deadline date default config, insert it as task specific config.
            if($assoc['deadlineDate'] !== null && $assoc['deadlineDate'] > 0){
                $name = ['runtimeOptions','workflow', $model->getWorkflow(), $model->getWorkflowStepName(), 'defaultDeadlineDate'];
                $taskConfig->updateInsertConfig($task->getTaskGuid(), implode('.', $name), $assoc['deadlineDate']);
            }

            // get deadline date config and set it if exist
            $configValue = $task->getConfig(true)->runtimeOptions->workflow->{$model->getWorkflow()}->{$model->getWorkflowStepName()}->defaultDeadlineDate ?? 0;
            if($configValue > 0){
                $model->setDeadlineDate(editor_Utils::addBusinessDays($task->getOrderdate(), $configValue));
            }
            // processing some trackchanges properties that can't be parted out to the trackchanges-plugin
            $model->setTrackchangesShow($assoc['trackchangesShow']);
            $model->setTrackchangesShowAll($assoc['trackchangesShowAll']);
            $model->setTrackchangesAcceptReject($assoc['trackchangesAcceptReject']);

            $model->save();
        }
    }

    /***
     * Disable the pivot autostart in case the import is done via the translate5 UI. For all api imports, the config
     * runtimeOptions.import.autoStartPivotTranslations will decide if the the pivot pre-translation is auto-queued
     * @param editor_Models_Task $task
     * @return void
     */
    public function handlePivotAutostart(editor_Models_Task $task): void
    {

        $importWizardUsed = $this->getRequest()->getParam('importWizardUsed',false);
        if( $importWizardUsed === false){
            return;
        }
        
        /** @var editor_Models_TaskConfig $config */
        $config = ZfExtended_Factory::get('editor_Models_TaskConfig');
        $config->updateInsertConfig($task->getTaskGuid(),'runtimeOptions.import.autoStartPivotTranslations',false);
    }
}