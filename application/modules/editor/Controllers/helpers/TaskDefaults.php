<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of ZfExtended library
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
https://www.gnu.org/licenses/lgpl-3.0.txt

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
			 https://www.gnu.org/licenses/lgpl-3.0.txt

END LICENSE AND COPYRIGHT
*/

/**
 */
class Editor_Controller_Helper_TaskDefaults extends Zend_Controller_Action_Helper_Abstract {

    /***
     * Assign language resources by default that are set as useAsDefault for the task's client
     * (but only if the language combination matches).
     * @param editor_Models_Task $task
     * @param int $customerId
     */
    public function addDefaultLanguageResources(editor_Models_Task $task, int $customerId) {
        $customerAssoc = ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
        /* @var $customerAssoc editor_Models_LanguageResources_CustomerAssoc */

        //TODO: here write as reference also
        $allUseAsDefaultCustomers = $customerAssoc->loadByCustomerIdsUseAsDefault([$customerId]);

        if(empty($allUseAsDefaultCustomers)) {
            return;
        }

        $taskAssoc = ZfExtended_Factory::get('editor_Models_LanguageResources_Taskassoc');
        /* @var $taskAssoc editor_Models_LanguageResources_Taskassoc */
        $languages = ZfExtended_Factory::get('editor_Models_LanguageResources_Languages');
        /* @var $languages editor_Models_LanguageResources_Languages */
        $language = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $language ZfExtended_Languages */

        $sourceLanguages = $language->getFuzzyLanguages($task->getSourceLang(),'id',true);
        $targetLanguages = $language->getFuzzyLanguages($task->getTargetLang(),'id',true);

        foreach ($allUseAsDefaultCustomers as $defaultCustomer) {
            $languageResourceId = $defaultCustomer['languageResourceId'];
            $sourceLangMatch = $languages->isInCollection($sourceLanguages, 'sourceLang', $languageResourceId);
            $targetLangMatch = $languages->isInCollection($targetLanguages, 'targetLang', $languageResourceId);
            if ($sourceLangMatch && $targetLangMatch) {
                $taskAssoc->init();
                $taskAssoc->setLanguageResourceId($languageResourceId);
                $taskAssoc->setTaskGuid($task->getTaskGuid());
                if(!empty($defaultCustomer['writeAsDefault'])){
                    $taskAssoc->setSegmentsUpdateable(1);
                }
                $taskAssoc->save();
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

            // if there is default dedline date default config, insert it as task specific config.
            if($assoc['deadlineDate']!== null && $assoc['deadlineDate']>0){
                $name = ['runtimeOptions','workflow',$model->getWorkflow(),$model->getWorkflowStepName(),'defaultDeadlineDate'];
                $taskConfig->updateInsertConfig($task->getTaskGuid(),implode('.',$name),$assoc['deadlineDate']);
            }

            // get deadline date config and set it if exist
            $configValue = $task->getConfig()->runtimeOptions->workflow->{$model->getWorkflow()}->{$model->getWorkflowStepName()}->defaultDeadlineDate ?? 0;
            if($configValue > 0){
                $model->setDeadlineDate(editor_Utils::addBusinessDays($task->getOrderdate(),$configValue));
            }

            $model->save();
        }
    }
}