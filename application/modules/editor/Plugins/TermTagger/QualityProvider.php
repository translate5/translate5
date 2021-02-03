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

class editor_Plugins_TermTagger_QualityProvider extends editor_Segment_Quality_Provider {
    
    /**
     * The central UNIQUE amongst quality providersKey to identify termtagger-related stuff. Must match editor_Plugins_TermTagger_InternalTag::$type
     * @var string
     */
    protected static $type = 'term';
    
    public function hasImportWorker() : bool {
        return true;
    }
    
    public function addImportWorker(editor_Models_Task $task, int $parentWorkerId) {
        
        // if the import is not switched on we return
        $config = Zend_Registry::get('config');
        $importSwitchedOn = $config->runtimeOptions->termTagger->switchOn->import;
        if((boolean)$importSwitchedOn === false) {
            return;
        }
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
        
        $this->lockOversizedSegments($task, $meta, $config);
        
        // init worker and queue it
        $params = ['resourcePool' => 'import', 'processSegmentsDirectly' => true];
        if (!$worker->init($task->getTaskGuid(), $params)) {
            $this->log->error('E1128', 'TermTaggerImport Worker can not be initialized!', [
                'parameters' => $params,
            ]);
            return;
        }
        $worker->queue($parentWorkerId);
    }
    
    public function processSegment(editor_Models_Task $task, editor_Segment_Tags $tags, bool $forImport) : editor_Segment_Tags {
        
        // this API does not process the import
        if($forImport){
            return $tags;
        }
        // do not process if not configured
        $config = Zend_Registry::get('config');
        $guiSwitchedOn = $config->runtimeOptions->termTagger->switchOn->GUI;
        if((boolean)$guiSwitchedOn === false) {
            return $tags;
        }
        $segment = $tags->getSegment();
        // no need to process if task has no terminologie or is not modified
        if (!$task->getTerminologie() || !$segment->isDataModified()) {
            return $tags;
        }
        $messages = Zend_Registry::get('rest_messages');
        /* @var $messages ZfExtended_Models_Messages */
        
        if($segment->meta()->getTermtagState() == editor_Plugins_TermTagger_Configuration::SEGMENT_STATE_OVERSIZE) {
            $messages->addError('Termini des zuletzt bearbeiteten Segments konnten nicht ausgezeichnet werden: Das Segment ist zu lang.');
            return $tags;
        }
        
        $worker = ZfExtended_Factory::get('editor_Plugins_TermTagger_Worker_TermTaggerImport');
        /* @var $worker editor_Plugins_TermTagger_Worker_TermTaggerImport */
        
        $params = ['resourcePool' => 'gui', 'processSegmentsDirectly' => true];
        if (!$worker->init($task->getTaskGuid(), $params)) {
            $logger = Zend_Registry::get('logger')->cloneMe('editor.terminology');
            $logger->error('E1128', 'TermTaggerImport Worker can not be initialized!', ['parameters' => $params]);
            return false;
        }
        if($worker->segmentTagsEdited($tags)){
            // TODO FIXME: this should be a reference to the tags above, so this assignment should be obsolete. Can the case be, segmentTagsEdited returns true but no tags are available ??
            $tags = $worker->getProcessedTags();
            if($tags != null){
                return $tags;
            }
        }
        $messages->addError('Termini des zuletzt bearbeiteten Segments konnten nicht ausgezeichnet werden.');
        return $tags;
    }

    public function isInternalTag(string $type, string $nodeName, array $classNames, array $attributes) : bool {
        // if the data says it's a term-tag or the class is 'term'
        return (($type == static::$type || in_array(static::$type, $classNames)) && editor_Plugins_TermTagger_InternalTag::hasNodeName($nodeName));
    }

    public function createInternalTag(int $startIndex, int $endIndex, string $nodeName=NULL) : editor_Segment_InternalTag {
        return new editor_Plugins_TermTagger_InternalTag($startIndex, $endIndex);
    }
    /**
     * Find oversized segments and mark them as oversized
     *
     * @param editor_Models_Task $task
     * @param editor_Models_Segment_Meta $meta
     * @param Zend_Config $config
     */
    private function lockOversizedSegments(editor_Models_Task $task, editor_Models_Segment_Meta $meta, Zend_Config $config) {
        $maxWordCount = $config->runtimeOptions->termTagger->maxSegmentWordCount ?? 150;
        $meta->db->update([
            'termtagState' => editor_Plugins_TermTagger_Configuration::SEGMENT_STATE_OVERSIZE
        ], [
            'taskGuid = ?' => $task->getTaskGuid(),
            'sourceWordCount >= ?' => $maxWordCount,
        ]);
    }
}
