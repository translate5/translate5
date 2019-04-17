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
 * editor_Plugins_MtComparEval_Worker Class
 */
class editor_Plugins_MtComparEval_CheckStateWorker extends ZfExtended_Worker_Abstract {
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        return true;
    } 
    
    protected function log($msg) {
        if(ZfExtended_Debug::hasLevel('plugin', 'MtComparEval')){
            error_log($msg);
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        $plugin = Zend_Registry::get('PluginManager')->get(__CLASS__);
        /* @var $plugin editor_Plugins_MtComparEval_Bootstrap */
        $meta = ZfExtended_Factory::get('editor_Models_Task_Meta');
        /* @var $meta editor_Models_Task_Meta */
        $importing = $meta->loadBy('mtCompareEvalState', $plugin::STATE_IMPORTING);
        
        if(empty($importing)){
            return true;
        }
        
        $importRemaining = false;
        foreach($importing as $oneImport) {
            //is running more than a day, I think we can cancel it.
            if($this->cancelLongRunningImport($oneImport, $meta)) {
                continue;
            }
            
            if($this->getExperimentStatus($oneImport['mtCompareEvalId'], $plugin)) {
                $meta->updateMutexed('mtCompareEvalState', $plugin::STATE_IMPORTED, $oneImport['id'], 'id');
            } else {
                $importRemaining = true;
            }
        }
        
        if($importRemaining) {
            $this->callMySelfAgain();
        }
        
        return true; 
    }
    
    /**
     * Gets the experiment status from MT-ComparEval
     * @param int $experimentId
     * @return boolean returns true if experiment is ready
     */
    protected function getExperimentStatus($experimentId, editor_Plugins_MtComparEval_Bootstrap $plugin) {
        $http = new Zend_Http_Client();
        $http->setUri($plugin->getMtUri('/api/experiments/status/'.$experimentId));
        $request = $http->request('GET');
        $result = json_decode($request->getBody());
        return $request->getStatus() == '200' && !empty($result) && $result->experiment_imported && $result->all_tasks_imported;
    }
    
    /**
     * Helper Method to mark a task as not importing to MT-ComparEval
     * @param array $oneImport
     * @param editor_Models_Task_Meta $meta
     * @return boolean returns true if experiment export has to be cancelled in translate5
     */
    protected function cancelLongRunningImport(array $oneImport, editor_Models_Task_Meta $meta) {
        $startTime = new DateTime($oneImport['mtCompareEvalStart']);
        $now = new DateTime(NOW_ISO);
        if($now->diff($startTime)->format("%a") <= 0) {
            return false;
        }
        
        $meta->load($oneImport['id']);
        $meta->setMtCompareEvalState($plugin::STATE_NOTSET);
        $meta->setMtCompareEvalStart(NULL);
        $meta->setMtCompareEvalId(NULL);
        $meta->save();
        $log = ZfExtended_Factory::get('ZfExtended_Log');
        /* @var $log ZfExtended_Log */
        $log->logError('Export to MT-ComparEval for task '.$oneImport['taskGuid'].' was longer as one day in state importing. It was cancelled therefore.');
        return true;
    }
    
    protected function callMySelfAgain() {
        sleep(30);//FIXME this should be better done by some kind of time scheduled workers
        $worker = ZfExtended_Factory::get('editor_Plugins_MtComparEval_CheckStateWorker');
        /* @var $worker editor_Plugins_MtComparEval_CheckStateWorker */
        $worker->init(null);
        $worker->queue();
    }
}