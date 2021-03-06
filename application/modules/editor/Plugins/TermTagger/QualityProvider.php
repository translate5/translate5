<?php
/*
 START LICENSE AND COPYRIGHT
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
 
 This file is part of a plug-in for translate5.
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
 
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
 http://www.gnu.org/licenses/gpl.html
 http://www.translate5.net/plugin-exception.txt
  END LICENSE AND COPYRIGHT
 */

/**
 * 
 * Provides the tagging when Importing Tasks and tagging edited segments which is integrated in the general quality management
 */
class editor_Plugins_TermTagger_QualityProvider extends editor_Segment_Quality_Provider {
    
    /**
     * @var string
     */
    const NOT_FOUND_IN_TARGET = 'not_found_in_target';
    /**
     * @var string
     */
    const NOT_DEFINED_IN_TARGET = 'not_defined_in_target';
    /**
     * @var string
     */
    const FORBIDDEN_IN_TARGET = 'forbidden_in_target';
    /**
     * @var string
     */
    const FORBIDDEN_IN_SOURCE = 'forbidden_in_source';
    
    /**
     * The central UNIQUE amongst quality providersKey to identify termtagger-related stuff.
     * @var string
     */
    protected static $type = editor_Plugins_TermTagger_Tag::TYPE;
    
    protected static $segmentTagClass = 'editor_Plugins_TermTagger_Tag';
    
    public function isActive(Zend_Config $qualityConfig, Zend_Config $taskConfig) : bool {
        return ($taskConfig->runtimeOptions->termTagger->enableAutoQA == 1);
    }
    
    public function isFullyChecked(Zend_Config $qualityConfig, Zend_Config $taskConfig) : bool {
        return ($taskConfig->runtimeOptions->termTagger->enableAutoQA == 1);
    }
    
    public function hasImportWorker() : bool {
        return true;
    }
    
    public function addWorker(editor_Models_Task $task, int $parentWorkerId, string $processingMode) {
        
        // if no terminology is present we return as well
        /* @var $task editor_Models_Task */
        if (!$task->getTerminologie()) {
            return;
        }
        $worker = ZfExtended_Factory::get('editor_Plugins_TermTagger_Worker_TermTaggerImport');
        /* @var $worker editor_Plugins_TermTagger_Worker_TermTaggerImport */
        // Create segments_meta-field 'termtagState' if not exists
        $meta = ZfExtended_Factory::get('editor_Models_Segment_Meta');
        /* @var $meta editor_Models_Segment_Meta */
        $meta->addMeta('termtagState', $meta::META_TYPE_STRING, editor_Plugins_TermTagger_Configuration::SEGMENT_STATE_UNTAGGED, 'Contains the TermTagger-state for this segment while importing', 36);
        
        $this->lockOversizedSegments($task, $meta);
        
        // init worker and queue it
        $params = ['resourcePool' => 'import', 'processingMode' => $processingMode];
        if (!$worker->init($task->getTaskGuid(), $params)) {
            $this->log->error('E1128', 'TermTaggerImport Worker can not be initialized!', [
                'parameters' => $params,
            ]);
            return;
        }
        $worker->queue($parentWorkerId);
    }
    
    public function processSegment(editor_Models_Task $task, Zend_Config $qualityConfig, editor_Segment_Tags $tags, string $processingMode) : editor_Segment_Tags {

        if($processingMode == editor_Segment_Processing::ALIKE){
            
            // when copying alike tags, we just save the qualities extracted from the tags (if active in config)
            if($task->getConfig()->runtimeOptions->termTagger->enableAutoQA){
                
                editor_Plugins_TermTagger_SegmentProcessor::findAndAddQualitiesInTags($tags);
            }
            
        } else if($processingMode == editor_Segment_Processing::EDIT){
            
            // editing process uses a worker to manage resource sharing of the termtagger
            
            $segment = $tags->getSegment();
            // no need to process if task has no terminologie or is not modified
            if (!$task->getTerminologie() || !$segment->isDataModified()) {
                // we need to process the qualities, otherwise the existing wil simply be deleted
                editor_Plugins_TermTagger_SegmentProcessor::findAndAddQualitiesInTags($tags);
                return $tags;
            }
            $messages = Zend_Registry::get('rest_messages');
            /* @var $messages ZfExtended_Models_Messages */
            
            if($segment->meta()->getTermtagState() == editor_Plugins_TermTagger_Configuration::SEGMENT_STATE_OVERSIZE) {
                // we need to process the qualities, otherwise the existing wil simply be deleted
                editor_Plugins_TermTagger_SegmentProcessor::findAndAddQualitiesInTags($tags);
                $messages->addError('Termini des zuletzt bearbeiteten Segments konnten nicht ausgezeichnet werden: Das Segment ist zu lang.');
                return $tags;
            }
            
            $worker = ZfExtended_Factory::get('editor_Plugins_TermTagger_Worker_TermTagger');
            /* @var $worker editor_Plugins_TermTagger_Worker_TermTagger */
            
            $params = ['resourcePool' => 'gui', 'processingMode' => $processingMode];
            if (!$worker->init($task->getTaskGuid(), $params)) {
                
                $logger = Zend_Registry::get('logger')->cloneMe('editor.terminology');
                $logger->error('E1128', 'TermTaggerImport Worker can not be initialized!', ['parameters' => $params]);

            } else if(!$worker->runSegmentTagsProcessing($tags)){
                
                $messages->addError('Termini des zuletzt bearbeiteten Segments konnten nicht ausgezeichnet werden.');
            }
        }
        return $tags;
    }
    
    public function translateType(ZfExtended_Zendoverwrites_Translate $translate) : ?string {
        return $translate->_('Terminologie');
    }
    
    public function translateCategory(ZfExtended_Zendoverwrites_Translate $translate, string $category, editor_Models_Task $task) : ?string {
        switch($category){
            case editor_Plugins_TermTagger_QualityProvider::NOT_FOUND_IN_TARGET:
                return $translate->_('Nicht gefunden in Ziel');
                
            case editor_Plugins_TermTagger_QualityProvider::NOT_DEFINED_IN_TARGET:
                return $translate->_('Nicht definiert in zielsprachl. Terminologie');
                
            case editor_Plugins_TermTagger_QualityProvider::FORBIDDEN_IN_TARGET:
                return $translate->_('Verboten in Ziel');
                
            case editor_Plugins_TermTagger_QualityProvider::FORBIDDEN_IN_SOURCE:
                return $translate->_('Verboten in Quelle');
        }
        return NULL;
    }
    
    public function getAllCategories(editor_Models_Task $task) : array {
        return [ editor_Plugins_TermTagger_QualityProvider::NOT_FOUND_IN_TARGET, editor_Plugins_TermTagger_QualityProvider::NOT_DEFINED_IN_TARGET, editor_Plugins_TermTagger_QualityProvider::FORBIDDEN_IN_TARGET, editor_Plugins_TermTagger_QualityProvider::FORBIDDEN_IN_SOURCE ];
    }

    public function isSegmentTag(string $type, string $nodeName, array $classNames, array $attributes) : bool {
        // if the data says it's a term-tag or the class is 'term'
        return (($type == static::$type || in_array(static::$type, $classNames)) && editor_Plugins_TermTagger_Tag::hasNodeName($nodeName));
    }
    /**
     * Find oversized segments and mark them as oversized
     *
     * @param editor_Models_Task $task
     * @param editor_Models_Segment_Meta $meta
     */
    private function lockOversizedSegments(editor_Models_Task $task, editor_Models_Segment_Meta $meta) {
        $config = Zend_Registry::get('config');
        $maxWordCount = $config->runtimeOptions->termTagger->maxSegmentWordCount ?? 150;
        $meta->db->update([
            'termtagState' => editor_Plugins_TermTagger_Configuration::SEGMENT_STATE_OVERSIZE
        ],[
            'taskGuid = ?' => $task->getTaskGuid(),
            'sourceWordCount >= ?' => $maxWordCount,
        ]);
    }
}
