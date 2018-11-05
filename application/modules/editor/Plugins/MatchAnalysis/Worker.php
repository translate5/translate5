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
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        return true;
    } 
    
    /**
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        try {
            $ret=$this->doWork();
        } catch (Exception $e) {
            error_log("Error happend on match analysis and pretranslation (taskGuid=".$this->task->getTaskGuid()."). Error was: ".$e->getMessage());
            return false;
        }
        return $ret;
    }
    
    
    /**
     * @return boolean
     */
    protected function doWork() {
        $params = $this->workerModel->getParameters();
        
        $pretranslate=false;
        if(isset($params['pretranslate'])){
            $pretranslate=$params['pretranslate'];
        }
        
        $internalFuzzy=false;
        if(isset($params['internalFuzzy'])){
            $internalFuzzy=$params['internalFuzzy'];
        }
        
        $task=ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($this->taskGuid);
        
        $analysisAssoc=ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_TaskAssoc');
        /* @var $analysisAssoc editor_Plugins_MatchAnalysis_Models_TaskAssoc */
        $analysisAssoc->setTaskGuid($task->getTaskGuid());
        
        //set flag for internal fuzzy usage
        if(isset($params['internalFuzzy'])){
            $analysisAssoc->setInternalFuzzy(filter_var($params['internalFuzzy'], FILTER_VALIDATE_BOOLEAN));
        }
        //set pretranslation matchrate used for the anlysis
        if(isset($params['pretranslateMatchrate'])){
            $analysisAssoc->setPretranslateMatchrate($params['pretranslateMatchrate']);
        }
        
        $analysisId=$analysisAssoc->save();
        
        $analysis=new editor_Plugins_MatchAnalysis_Analysis($task,$analysisId);
        /* @var $analysis editor_Plugins_MatchAnalysis_Analysis */
        $analysis->setPretranslate($pretranslate);
        $analysis->setInternalFuzzy($internalFuzzy);
        if(isset($params['userGuid'])){
            $analysis->setUserGuid($params['userGuid']);
        }
        if(isset($params['userName'])){
            $analysis->setUserName($params['userName']);
        }
        if(isset($params['pretranslateMatchrate'])){
            $analysis->setPretranslateMatchrate($params['pretranslateMatchrate']);
        }
        
        if(isset($params['pretranslateMt'])){
            $analysis->setPretranslateMt($params['pretranslateMt']);
        }
        
        if(isset($params['pretranslateTmAndTerm'])){
            $analysis->setPretranslateTmAndTerm($params['pretranslateTmAndTerm']);
        }
        return $analysis->calculateMatchrate();
    }
}
