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

class editor_Plugins_MatchAnalysis_Worker extends editor_Models_Task_AbstractWorker
{

    /***
     *
     * @var ZfExtended_Logger
     */
    protected $log;
    /***
     *
     * @var editor_Plugins_MatchAnalysis_Analysis
     */
    protected $analysis;
    /**
     * 
     * @var string
     */
    private $taskInitialState;

    public function __construct()
    {
        parent::__construct();
        $this->log = Zend_Registry::get('logger')->cloneMe('plugin.matchanalysis');
    }

    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array())
    {
        $neededEntries = ['internalFuzzy', 'pretranslateMatchrate', 'pretranslateTmAndTerm', 'pretranslateMt', 'isTaskImport', 'pretranslate', 'taskInitialState', 'taskNextState'];
        $foundEntries = array_keys($parameters);
        if(array_key_exists('taskInitialState', $parameters)){
            $this->taskInitialState = $parameters['taskInitialState'];
        }
        $keyDiff = array_diff($neededEntries, $foundEntries);
        //if there is not keyDiff all needed were found
        return empty($keyDiff);
    }

    /**
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work()
    {
        try {
            
            return $this->doWork();

        } catch (Throwable $e) {

            if (isset($this->analysis)) {
                //clean after analysis exception
                $this->analysis->clean();
            }

            //when error happens, revoke the task old state, and unlock the task
            $this->task->setState($this->taskInitialState);
            $this->task->save();
            $this->task->unlock();
            $this->log->error('E1100', 'MatchAnalysis Plug-In: analysis and pre-translation cannot be run. See additional errors for more Information.', [
                'task' => $this->task,
            ]);
            $this->log->exception($e, [
                'extra' => [
                    'task' => $this->task
                ],
            ]);
            return false;
        }
    }


    /**
     * @return boolean
     */
    protected function doWork()
    {
        $params = $this->workerModel->getParameters();
        $stateNeedsToBeReset = false;

        // lock the task dedicated for analysis
        if ($this->task->lock(NOW_ISO, editor_Plugins_MatchAnalysis_Models_MatchAnalysis::TASK_STATE_ANALYSIS)) {
            // lock the task while match analysis are running
            $stateNeedsToBeReset = true;
            $this->task->setState(editor_Plugins_MatchAnalysis_Models_MatchAnalysis::TASK_STATE_ANALYSIS);
            $this->task->save();
            // else check if we are in import, then no separate lock is needed. Therefore if we are not in import this is an error
        } else if ($this->task->getState() != editor_Models_Task::STATE_IMPORT) {
            $this->log->error('E1167', 'MatchAnalysis Plug-In: task can not be locked for analysis and pre-translation.', [
                'task' => $this->task
            ]);
            $this->task->setState($this->taskInitialState);
            $this->task->save();
            return false;
        }
        $analysisAssoc = ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_TaskAssoc');
        /* @var $analysisAssoc editor_Plugins_MatchAnalysis_Models_TaskAssoc */
        $analysisAssoc->setTaskGuid($this->task->getTaskGuid());

        //set flag for internal fuzzy usage
        $analysisAssoc->setInternalFuzzy($params['internalFuzzy']);
        //set pretranslation matchrate used for the anlysis
        $analysisAssoc->setPretranslateMatchrate($params['pretranslateMatchrate']);
        $analysisAssoc->setUuid(ZfExtended_Utils::uuid());

        $analysisId = $analysisAssoc->save();

        $this->analysis = ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Analysis', [$this->task, $analysisId, $this->taskInitialState]);

        $this->analysis->setPretranslate($params['pretranslate']);
        $this->analysis->setInternalFuzzy($params['internalFuzzy']);
        $this->analysis->setUserGuid($params['userGuid']);
        $this->analysis->setUserName($params['userName']);
        $this->analysis->setPretranslateMatchrate($params['pretranslateMatchrate']);
        $this->analysis->setPretranslateMt($params['pretranslateMt']);
        $this->analysis->setPretranslateTmAndTerm($params['pretranslateTmAndTerm']);
        $this->analysis->setBatchQuery($params['batchQuery']);


        $updateCounter = 0;
        $lastProgress = 0;
        $return = $this->analysis->calculateMatchrate(function ($progress) use (&$updateCounter, &$lastProgress) {
            $updateCounter++;
            $lastProgress = $progress;
            //update the progress on each 10 segments (to prevent from possible deadlocks in worker table).
            if ($updateCounter % 10 == 0) {
                $this->updateProgress($progress);
            }
        });

        if (!empty($lastProgress)) {
            $this->updateProgress($lastProgress);
        }
        // set the state to the next worker following the analysis or the initial state in case of an import
        if($stateNeedsToBeReset && $this->task->getState() != $params['taskNextState']){
            $this->task->setState($params['taskNextState']);
            $this->task->save();
        }

        //setting null takes the current date from DB
        $analysisAssoc->finishNow();

        $this->task->unlock();
        return $return;
    }

    /***
     * Match analysis and pretranslation takes 92 % of the import time
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::getWeight()
     */
    public function getWeight()
    {
        return 92;
    }
}
