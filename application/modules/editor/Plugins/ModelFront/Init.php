<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**
 * Info: the ModelFront plugin depends on the MatchAnalysis plugin (MatchAnalysis plugin must be active when ModelFront plugin is active).
 */
class editor_Plugins_ModelFront_Init extends ZfExtended_Plugin_Abstract {


    /***
     * @var ZfExtended_Logger
     */
    protected ZfExtended_Logger $logger;

    /**
     * {@inheritDoc}
     * @see ZfExtended_Plugin_Abstract::init()
     */
    public function init() {
        $this->initEvents();
        $this->logger = Zend_Registry::get('logger')->cloneMe('plugin.modelfront');
    }
    
    /**
     */
    protected function initEvents() {
        //INFO: this is off because of the pretranslation resources problem. See comment in: https://jira.translate5.net/browse/TRANSLATE-1643
        //This should be enabled again when the issue above is implemented.
        //$this->eventManager->attach('editor_TaskController', 'pretranslationOperation', array($this, 'handleOnPretranslationOperation'));
        $this->eventManager->attach('editor_LanguageresourceinstanceController', 'afterQueryAction', array($this, 'handleAfterQueryAction'));
        $this->eventManager->attach('editor_Plugins_MatchAnalysis_Pretranslation', 'afterAnalysisSegmentPretranslate', array($this, 'handleAfterAnalysisSegmentPretranslate'));
    }
    
    /***
     * Queue the ModelFront worker on pretranslation operation
     *
     * @param Zend_EventManager_Event $event
     * @return boolean
     */
    public function handleOnPretranslationOperation(Zend_EventManager_Event $event){
        $model = $event->getParam('entity');
        /* @var $model editor_Models_Task*/
        
        $tasks=[$model];
        if($model->isProject()){
            $taskModel=ZfExtended_Factory::get('editor_Models_Task');
            /* @var $taskModel editor_Models_Task */
            $tasks=$taskModel->loadProjectTasks($model->getProjectId(),true);
        }
        
        foreach ($tasks as $task){
            $this->queueWorker(is_array($task) ? $task['taskGuid'] : $task->getTaskGuid());
        }
    }

    /***
     * After language resource query action event handler
     * @param Zend_EventManager_Event $event
     * @return void
     * @throws Zend_Exception
     */
    public function handleAfterQueryAction(Zend_EventManager_Event $event){
        $view = $event->getParam('view');
        $resourceType=$view->resourceType ?? null;
        
        //modelfront risk calculation is only available for mt resources
        if(empty($resourceType) || $resourceType!=editor_Models_Segment_MatchRateType::TYPE_MT){
            return;
        }
        
        if(!isset($view->rows) || empty($view->rows)){
            return;
        }
        
        //For each mt result, calculate the matchrate using modelfront
        $session = new Zend_Session_Namespace();
        $task=ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid((string) $session->taskGuid);

        $risk = $this->getRiskPredictionInstance($task);
        if(empty($risk)){
            return;
        }

        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $metaData=new stdClass();
        $metaData->name=$translate->_('Matchrate');
        $metaData->value='ModelFront';

        foreach ($view->rows as &$row){
            try {
                //INFO: in mt resources source should be the same as the searched string
                $matchRate=round($risk->riskToMatchrate($row->source, $row->target));
                $row->matchrate=$matchRate < 1 ? 1 : $matchRate;

                if(!is_array($row->metaData)){
                    settype($row->metaData, 'array');
                }
                // add additional info about the matchrate source
                $row->metaData[]=$metaData;
            } catch (editor_Plugins_ModelFront_Exception $e) {
                $this->logger->exception($e, [
                    'level' => $this->logger::LEVEL_WARN
                ]);
                continue;
            }
        }
    }
    
    /***
     * After segment is pretranslated, update the segment matchrate with modelfront results.
     * NOTE: update only for mt pretranslate segments
     * @param Zend_EventManager_Event $event
     * @return void
     */
    public function handleAfterAnalysisSegmentPretranslate(Zend_EventManager_Event $event): void{
        $segment = $event->getParam('entity');
        /* @var $segment editor_Models_Segment */
        $result = $event->getParam('result');
        $analysisId = $event->getParam('analysisId');
        $languageResourceId = $event->getParam('languageResourceId');
        // Starting prefix for segment matchrate when the segment is pre translated with MT resource
        $prefix=editor_Models_Segment_MatchRateType::PREFIX_PRETRANSLATED.';'.editor_Models_Segment_MatchRateType::TYPE_MT;
        if (strpos($segment->getMatchRateType(), $prefix) !== 0 || empty($result) || empty($analysisId) || empty($languageResourceId)) {
            return;
        }
        
        $task=ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($segment->getTaskGuid());

        $risk = $this->getRiskPredictionInstance($task);
        if(empty($task)){
            return;
        }
        try {
            $risk->updateSegmentMatchrate($segment,$analysisId,$languageResourceId);
        } catch (editor_Plugins_ModelFront_Exception $e) {
            $this->logger->exception($e, [
                'level' => $this->logger::LEVEL_WARN
            ]);
        }
    }

    /***
     * Return editor_Plugins_ModelFront_TranslationRiskPrediction instance with exception handling.
     * This will not log an exception if for the ModelFront no api config is provided (The ModelFront should be active by default, which means the auth params most of the time will be empty. That is why we ignore the logging)
     * @param editor_Models_Task $task
     * @return editor_Plugins_ModelFront_TranslationRiskPrediction|null
     */
    protected function getRiskPredictionInstance(editor_Models_Task $task): ?editor_Plugins_ModelFront_TranslationRiskPrediction{
        try{
            return ZfExtended_Factory::get('editor_Plugins_ModelFront_TranslationRiskPrediction',[$task]);
        }catch (editor_Plugins_ModelFront_Exception $e){
            // Log the exception only if the error is not for missing api config parameters
            // The ModelFront should be active by default, which means the auth params most of the time will be empty. That is why we ignore the logging
            // for missing auth params
            if($e->getErrorCode() !== 'E1266'){
                $this->logger->exception($e, [
                    'level' => $this->logger::LEVEL_WARN
                ]);
            }
            return null;
        }
    }
    
    /***
     * Queue ModelFront worker for given $taskGuid
     *
     * @param string $taskGuid
     * @return boolean
     */
    protected function queueWorker(string $taskGuid): bool{
        $worker = ZfExtended_Factory::get('editor_Plugins_ModelFront_Worker');
        /* @var $worker editor_Plugins_ModelFront_Worker */
        // init worker and queue it
        if (!$worker->init($taskGuid, [])) {
            error_log('ModelFront-Error on worker init()', __CLASS__.' -> '.__FUNCTION__.'; Worker could not be initialized');
            return false;
        }
        $parent=ZfExtended_Factory::get('ZfExtended_Models_Worker');
        /* @var $parent ZfExtended_Models_Worker */
        $result = $parent->loadByState("editor_Plugins_MatchAnalysis_Worker", ZfExtended_Models_Worker::STATE_PREPARE,$taskGuid);
        $parentWorkerId = null;
        if(!empty($result)){
            $parentWorkerId = $result[0]['id'];
        }
        
        $worker->queue($parentWorkerId);
    }
}
