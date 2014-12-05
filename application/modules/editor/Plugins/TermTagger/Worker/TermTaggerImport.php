<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */
/**
 * editor_Plugins_TermTagger_Worker_TermTaggerImport Class
 */
class editor_Plugins_TermTagger_Worker_TermTaggerImport extends ZfExtended_Worker_Abstract {
    
    use editor_Plugins_TermTagger_Worker_TermTaggerTrait;
    
    
    /**
     * Special Paramters:
     * 
     * $parameters['resourcePool']
     * sets the resourcePool for slot-calculation depending on the context.
     * Possible values are all values out of $this->allowedResourcePool
     * 
     * 
     * On very first init:
     * seperate data from parameters which are needed while processing queued-worker.
     * All informations which are only relevant in 'normal processing (not queued)'
     * are not needed to be saved in DB worker-table (aka not send to parent::init as $parameters)
     * 
     * ATTENTION:
     * for queued-operating $parameters saved in parent::init MUST have all necessary paramters
     * to call this init function again on instanceByModel
     * 
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::init()
     */
    public function init($taskGuid = NULL, $parameters = array()) {
        $parametersToSave = array();
        
        if (isset($parameters['resourcePool'])) {
            if (in_array($parameters['resourcePool'], self::$allowedResourcePools)) {
                $this->resourcePool = $parameters['resourcePool'];
                $parametersToSave['resourcePool'] = $this->resourcePool;
            }
        }
        
        return parent::init($taskGuid, $parametersToSave);
    }
    
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        return true;
    } 
    
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::run()
     */
    public function run() {
        return parent::run();
    }
    
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        error_log(__CLASS__.' -> '.__FUNCTION__);
        
        $segmentIds = $this->loadUntaggedSegmentIds($this->workerModel->getTaskGuid());
        
        if (empty($segmentIds)) {
            return false;
        }
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_task */
        $task->loadByTaskGuid($this->workerModel->getTaskGuid());
        
        $serverCommunication = ZfExtended_Factory::get('editor_Plugins_TermTagger_Service_ServerCommunication', array($task));
        /* @var $serverCommunication editor_Plugins_TermTagger_Service_ServerCommunication */
        
        $fieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        /* @var $fieldManager editor_Models_SegmentFieldManager */
        $fieldManager->initFields($this->workerModel->getTaskGuid());
        $segmentFields = $fieldManager->getFieldList();
        $sourceFieldName = $fieldManager->getFirstSourceName();
        
        foreach ($segmentIds as $segmentId) {
            $segment = ZfExtended_Factory::get('editor_Models_Segment');
            /* @var $segment editor_Models_Segment */
            $segment->load($segmentId);
            $segment->meta()->setTermtagState($this::$SEGMENT_STATE_INPROGRESS);
            $segment->meta()->save();
            
            $sourceText = $segment->get($sourceFieldName);
            
            foreach ($segmentFields as $field) {
                if($field->type != editor_Models_SegmentField::TYPE_TARGET || !$field->editable) {
                    continue;
                }
                
                $serverCommunication->addSegment($segment->getId(), $field->name, $sourceText, $segment->getTargetEdit());
            }
        }
                
        $termTagger = ZfExtended_Factory::get('editor_Plugins_TermTagger_Service');
        /* @var $termTagger editor_Plugins_TermTagger_Service */
        if (!$this->checkTermTaggerTbx($this->workerModel->getSlot(), $serverCommunication->tbxFile)) {
            return false;
        }
            
        $response = $termTagger->tagterms($this->workerModel->getSlot(), $serverCommunication);
        $result = $this->decodeServiceResult($response);
        // on error return false and store original untagged data
        if (empty($result)) {
            return false;
        }
        
        $responses = $this->groupResponseById($result->segments);
        
        foreach ($responses as $segmentId => $responseGroup) {
            
            $segment = ZfExtended_Factory::get('editor_Models_Segment');
            /* @var $segment editor_Models_Segment */
            $segment->load($segmentId);
            
            $segment->set($sourceFieldName, $responseGroup[0]->source);
            if ($task->getEnableSourceEditing()) {
                $segment->set($fieldManager->getEditIndex($sourceFieldName), $responseGroup[0]->source);
            }
            
            foreach ($responseGroup as $response) {
                $segment->set($response->field, $response->target);
                $segment->set($fieldManager->getEditIndex($response->field), $response->target);
            }
            
            $segment->save();
            
            $segment->meta()->setTermtagState($this::$SEGMENT_STATE_TAGGED);
            $segment->meta()->save();
        }
        
        // initialize an new worker-queue-entry
        $worker = ZfExtended_Factory::get('editor_Plugins_TermTagger_Worker_TermTaggerImport');
        /* @var $worker editor_Plugins_TermTagger_Worker_TermTaggerImport */
        if (!$worker->init($this->workerModel->getTaskGuid(), array('resourcePool' => 'import'))) {
            $this->log('TermTaggerImport-Error on worker init()', __CLASS__.' -> '.__FUNCTION__.'; Worker could not be initialized');
            return false;
        }
        $worker->queue();
        
        return true;
    }
    
    /**
     * Loads a list of segmentIds where terms are not tagged yet.
     * Limit for this list is $config->runtimeOptions->termTagger->segmentsPerCall
     * 
     * @param string $taskGuid
     */
    private function loadUntaggedSegmentIds($taskGuid) {
        $config = Zend_Registry::get('config');
        $limit = $config->runtimeOptions->termTagger->segmentsPerCall;
        
        // get list of untagged segments
        $db = ZfExtended_Factory::get('editor_Models_Db_Segments');
        $dbName = $db->info($db::NAME);
        /* @var $db editor_Models_Db_Segments */
        $sql = $db->select()
                    ->from(array('segment' => $dbName), 'segment.id')
                    ->joinLeft(array('meta' => $dbName.'_meta'), 'segment.id = meta.segmentId', array())
                    ->where('segment.taskGuid = ?', $taskGuid)
                    ->where('meta.termtagState IS NULL OR meta.termtagState NOT IN (?)',
                            array($this::$SEGMENT_STATE_TAGGED, $this::$SEGMENT_STATE_INPROGRESS)) // later there may will be a state 'targetnotfound'
                    ->order('segment.id')
                    ->limit($limit);
        //error_log(__CLASS__.' -> '.__FUNCTION__.'; $sql: '.$sql);
        $segmentIds = $db->fetchAll($sql)->toArray();
        //error_log(__CLASS__.' -> '.__FUNCTION__.'; $segmentIds: '.print_r($segmentIds, true));
        
        return $segmentIds;
    }
    
    /**
     * In case of multiple target-fields in one segment, there are multiple responses for the same segment.
     * This function groups this different responses under the same segmentId
     * 
     * @param array $responses
     * @return array grouped
     */
    private function groupResponseById($responses) {
        $return = array();
        
        foreach ($responses as $response) {
            $return[$response->id][] = $response;
        }
        
        return $return;
    }
    
}