<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * editor_Plugins_MtComparEval_Worker Class
 */
class editor_Plugins_MtComparEval_Worker extends ZfExtended_Worker_Abstract {
    
    protected $data = array();
    protected $fields;
    
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
        $data = ZfExtended_Factory::get('editor_Models_Segment_Iterator', array($this->taskGuid));
        /* @var $data editor_Models_Segment_Iterator */
        if ($data->isEmpty()) {
            return false;
        }
        
        $sfm = $this->initFields();
        
        //walk over segments and fields and get segments data
        foreach($data as $segment) {
            /* @var $segment editor_Models_Segment */
            foreach($this->fields as $field) {
                $fieldName = $field->name;
                if($fieldName != 'source' && $fieldName != 'target') {
                    $fieldName = $sfm->getEditIndex($fieldName);
                }
                $this->data[$field->name][] = strip_tags($segment->getDataObject()->$fieldName);
            }
        }
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($this->taskGuid);
        
        $reimport = $task->meta()->getMtCompareEvalId() > 0;

        $plugin = Zend_Registry::get('PluginManager')->get(__CLASS__);
        /* @var $plugin editor_Plugins_MtComparEval_Bootstrap */
        
        if(!$reimport) {
            $id = $this->addExperiment($task, $plugin);
            if($id === false) {
                return false;
            }
        } else {
            //$this->deleteTasks($id, $task, $plugin);
        }
        $this->addTasks($id, $task, $plugin);
        
        $worker = ZfExtended_Factory::get('editor_Plugins_MtComparEval_CheckStateWorker');
        /* @var $worker editor_Plugins_MtComparEval_CheckStateWorker */
        $worker->init(null);
        $worker->queue();
        
        return true;
    }
    
    protected function initFields() {
        $sfm = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        /* @var $sfm editor_Models_SegmentFieldManager */
        $sfm->initFields($this->taskGuid);
        
        $this->fields = $sfm->getFieldList();
        foreach($this->fields as $field) {
            $this->data[$field->name] = array();
        }
        return $sfm;
    }
    
    /**
     * creates an experiment in MT-ComparEval
     * @param editor_Models_Task $task
     * @param editor_Plugins_MtComparEval_Bootstrap $plugin
     * @return boolean
     */
    protected function addExperiment(editor_Models_Task $task, editor_Plugins_MtComparEval_Bootstrap $plugin) {
        $http = new Zend_Http_Client();
        //curl -X POST -F "name=experiment name" -F "description=description" -F "source=@source.txt" -F "reference=@reference.txt" http://localhost:8080/api/experiments/upload	
        $http->setParameterPost('name', $task->getTaskName().' (ID '.$task->getId().')');
        $http->setParameterPost('description', 'Experiment imported from translate5 (taskGuid: '.$task->getTaskGuid().')');
        $http->setFileUpload('source.txt', 'source', join("\n",$this->data['source']), 'text/plain');
        unset($this->data['source']);
        $http->setFileUpload('reference.txt', 'reference', join("\n",$this->data['target']), 'text/plain');
        unset($this->data['target']);
        
        $http->setUri($plugin->getMtUri('/api/experiments/upload'));
        
        $request = $http->request('POST');
        $this->log(__CLASS__.' request to '.$http->getUri(true).' for task '.$task->getTaskGuid().' was '.$request->getStatus().' with response '.$request->getBody());
        if($request->getStatus() != '200') {
            return false;
        }
        $result = json_decode($request->getBody());
        
        $task->meta()->setMtCompareEvalId($result->experiment_id);
        $task->meta()->setMtCompareEvalStart(NOW_ISO);
        $task->meta()->save();
        
        return $result->experiment_id;
    }
    
    /**
     * creates tasks in MT-ComparEval
     * //curl -X POST -F "name=task name" -F "description=description" -F "experiment_id=1" -F "translation=@translation.txt" http://localhost:8080/api/tasks/upload
     * @param mixed $experimentId
     * @param editor_Models_Task $task
     * @param editor_Plugins_MtComparEval_Bootstrap $plugin
     */
    protected function addTasks($experimentId, editor_Models_Task $task, editor_Plugins_MtComparEval_Bootstrap $plugin) {
        foreach($this->fields as $field) {
            if($field->name == 'source' || $field->name == 'target' || empty($this->data[$field->name])) {
                continue;
            }
            $http = new Zend_Http_Client();
            $http->setParameterPost('name', $field->label.' (ID: '.$field->id.')');
            $http->setParameterPost('description', 'Task imported from translate5 was column '.$field->name);
            $http->setParameterPost('experiment_id', $experimentId);
            $http->setFileUpload('translation.txt', 'translation', join("\n",$this->data[$field->name]), 'text/plain');
            unset($this->data[$field->name]);
            $http->setUri($plugin->getMtUri('/api/tasks/upload'));
            $request = $http->request('POST');
            $this->log(__CLASS__.' request to '.$http->getUri(true).' for field '.$field->name.' was '.$request->getStatus().' with response '.$request->getBody());
        }
    }
}