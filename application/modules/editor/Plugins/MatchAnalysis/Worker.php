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

class editor_Plugins_MatchAnalysis_Worker extends editor_Models_Import_Worker_Abstract {
    
    /***
     * Task old state before the match analysis were started
     * @var string
     */
    private $taskOldState=null;
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        $neededEntries = ['internalFuzzy', 'pretranslateMatchrate', 'pretranslateTmAndTerm', 'pretranslateMt', 'termtaggerSegment', 'isTaskImport', 'pretranslate'];
        $foundEntries = array_keys($parameters);
        $keyDiff = array_diff($neededEntries, $foundEntries);
        //if there is not keyDiff all needed were found
        return empty($keyDiff);
    } 
    
    /**
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        try {
            $params = $this->workerModel->getParameters();
            $ret=$this->doWork();
            
            //run the term tagger when the termtagger flag is set, it is pretranslation and no terminologie worker is queued
            if($params['termtaggerSegment'] && $params['pretranslate'] && !$params['isTaskImport']){
                $this->queueTermtagger($this->taskGuid,$this->workerModel->getParentId());
            }
        } catch (Exception $e) {
            //when error happens, revoke the task old state, and unlock the task
            $this->task->setState($this->taskOldState);
            $this->task->save();
            $this->task->unlock();
           
            $logger = Zend_Registry::get('logger')->cloneMe('plugin.matchanalysis');
            /* @var $logger ZfExtended_Logger */
            $logger->exception($e);
            return false;
        }
        return $ret;
    }
    
    
    /**
     * @return boolean
     */
    protected function doWork() {
        $params = $this->workerModel->getParameters();

        $newState=null;
        $this->taskOldState = $this->task->getState();
        
        //lock the task dedicated for analysis
        if($this->task->lock(NOW_ISO, editor_Plugins_MatchAnalysis_Models_MatchAnalysis::TASK_STATE_ANALYSIS)) {
            //lock the task while match analysis are running
            $newState=editor_Plugins_MatchAnalysis_Models_MatchAnalysis::TASK_STATE_ANALYSIS;
            $this->task->setState(editor_Plugins_MatchAnalysis_Models_MatchAnalysis::TASK_STATE_ANALYSIS);
            $this->task->save();
        //else check if we are in import, then no separate lock is needed. Therefore if we are not in import this is an error
        } elseif($this->task->getState() != editor_Models_Task::STATE_IMPORT) {
            $this->log->error('E1167', 'MatchAnalysis Plug-In: analysis and pre-translation cannot be run.', [
                'task' => $this->task
            ]);
            return;
        }
        $analysisAssoc=ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_TaskAssoc');
        /* @var $analysisAssoc editor_Plugins_MatchAnalysis_Models_TaskAssoc */
        $analysisAssoc->setTaskGuid($this->task->getTaskGuid());
        
        //set flag for internal fuzzy usage
        $analysisAssoc->setInternalFuzzy($params['internalFuzzy']);
        //set pretranslation matchrate used for the anlysis
        $analysisAssoc->setPretranslateMatchrate($params['pretranslateMatchrate']);
        
        $analysisId=$analysisAssoc->save();
        
        $analysis = ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Analysis', [$this->task, $analysisId, $this->taskOldState]);
        /* @var $analysis editor_Plugins_MatchAnalysis_Analysis */
        
        $analysis->setPretranslate($params['pretranslate']);
        $analysis->setInternalFuzzy($params['internalFuzzy']);
        $analysis->setUserGuid($params['userGuid']);
        $analysis->setUserName($params['userName']);
        $analysis->setPretranslateMatchrate($params['pretranslateMatchrate']);
        $analysis->setPretranslateMt($params['pretranslateMt']);
        $analysis->setPretranslateTmAndTerm($params['pretranslateTmAndTerm']);
        $return=$analysis->calculateMatchrate();
        
        //unlock the state
        if(!empty($newState)){
            $this->task->setState($this->taskOldState);
            $this->task->save();
        }
        $this->task->unlock();
        return $return;
    }
    
    /**
     * Queue the termtagger worker
     * @param string $taskGuid
     * @param string $workerId
     * @return boolean
     */
    protected function queueTermtagger($taskGuid,$workerId){
        $worker = ZfExtended_Factory::get('editor_Plugins_TermTagger_Worker_TermTaggerImport');
        /* @var $worker editor_Plugins_TermTagger_Worker_TermTaggerImport */
        
        // Create segments_meta-field 'termtagState' if not exists
        $meta = ZfExtended_Factory::get('editor_Models_Segment_Meta');
        /* @var $meta editor_Models_Segment_Meta */
        $meta->addMeta('termtagState', $meta::META_TYPE_STRING, $worker::SEGMENT_STATE_UNTAGGED, 'Contains the TermTagger-state for this segment while importing', 36);
        
        // init worker and queue it
        if (!$worker->init($taskGuid, array('resourcePool' => 'import'))) {
            $this->log->error('E1168', 'MatchAnalysis Plug-In: TermTagger worker for pre-translation can not be initialized.');
            return false;
        }
        $worker->queue($workerId);
        return true;
    }
}
