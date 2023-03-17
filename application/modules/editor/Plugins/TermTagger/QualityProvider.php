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

use MittagQI\Translate5\Plugins\TermTagger\Configuration;
use MittagQI\Translate5\Plugins\TermTagger\Processor\Tagger;
use MittagQI\Translate5\Plugins\TermTagger\Service;
use MittagQI\Translate5\Plugins\TermTagger\Worker;
use MittagQI\Translate5\Segment\Db\Processing;
use MittagQI\Translate5\Segment\Processing\State;

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
    
    public function hasOperationWorker(string $processingMode, Zend_Config $qualityConfig) : bool {
        // we will run with any processing mode
        return true;
    }
    
    public function addWorker(editor_Models_Task $task, int $parentWorkerId, string $processingMode, array $workerParams=[]) {

        // if source & target language is similar, we simply do nothing, since the termtagger would crash in this case, see TRANSLATE-2373
        if($task->isSourceAndTargetLanguageSimilar()){
            return;
        }
        // in case of an import and no terminology is applied, there needs to be no workers, nothing to do ...
        if($processingMode === editor_Segment_Processing::IMPORT && !$task->getTerminologie()){
            return;
        }
        // init worker and queue it
        $worker = ZfExtended_Factory::get(Worker::class);
        // Crucial: add processing-mode to worker params
        $workerParams['processingMode'] = $processingMode;
        $workerParams['resourcePool'] = 'import';

        if (!$worker->init($task->getTaskGuid(), $workerParams)) {
            $this->getLogger($processingMode)->error('E1128', 'TermTagger Worker can not be initialized!', [ 'parameters' => $workerParams ]);
            return;
        }
        // NOTE: this worker (which usually is queued multiple times) will either remove or add the terminology. When removing it needs just to be a single instance as it uses no service and makes no requests
        if(!$task->getTerminologie()){
            $worker->setSingleThreaded();
        }
        $worker->queue($parentWorkerId);
    }

    public function prepareOperation(editor_Models_Task $task, string $processingMode) {

        // disable when source/target language similar, see TRANSLATE-2373
        if($task->isSourceAndTargetLanguageSimilar()){
            return;
        }

        // when we are an import and no terminology is bound, we simply set all states to processed ...
        if(!$task->getTerminologie() && $processingMode === editor_Segment_Processing::IMPORT){
            $processingTable = new Processing();
            $processingTable->setTaskToState($task->getTaskGuid(), Service::SERVICE_ID, State::PROCESSED);
            return;
        }

        // Find oversized segments, non-editable segments and mark them as unprocessable
        $config = Zend_Registry::get('config');
        $metaTable = ZfExtended_Factory::get(editor_Models_Db_SegmentMeta::class);
        $where = $metaTable->select()
            ->from($metaTable->getName(), ['segmentId'])
            ->where('taskGuid = ?', $task->getTaskGuid())
            ->where('sourceWordCount >= ?', $this->getOversizeWordCount($config));
        $rows = $metaTable->fetchAll($where)->toArray();
        $oversizedSegmentIds = array_column($rows, 'segmentId');
        // find noneditable segments, which have to be excluded unless tagging non-editable segments is wanted
        $noneditableSegmentIds = [];
        if(!$config->runtimeOptions->termTagger->tagReadonlySegments){
            $segmentsTable = ZfExtended_Factory::get(editor_Models_Db_Segments::class);
            $noneditableSegmentIds = $segmentsTable->getAllIdsForTask($task->getTaskGuid(), false, ['editable = ?' => 0]);
        }
        if(!empty($oversizedSegmentIds) || !empty($noneditableSegmentIds)){
            $processingTable = new Processing();
            // set the oversized segments to toolong
            $processingTable->setSegmentsToState($oversizedSegmentIds, Service::SERVICE_ID, State::TOOLONG);
            // set the noneditable segments to ignore
            $processingTable->setSegmentsToState($noneditableSegmentIds, Service::SERVICE_ID, State::IGNORED);
        }
    }

    public function finalizeOperation(editor_Models_Task $task, string $processingMode, array $processingResult){

        // disable when source/target language similar, see TRANSLATE-2373, also nothing to report when terminology was removed
        if($task->isSourceAndTargetLanguageSimilar() || !$task->getTerminologie()){
            return;
        }
        // the processing might excluded termtagging, we only add event when a result is available
        if(array_key_exists(Service::SERVICE_ID, $processingResult)){
            $this->getLogger($processingMode)->info('E1364', 'TermTagger overall run done - {segmentCounts}', [
                'task' => $task,
                'segmentCounts' => 'tagged '.$processingResult[Service::SERVICE_ID].' of '.$processingResult['segments'],
            ]);
        }
        // we report any defect segments we found during processing
        Tagger::reportDefectSegments($task, $processingMode);
    }
    
    public function processSegment(editor_Models_Task $task, Zend_Config $qualityConfig, editor_Segment_Tags $tags, string $processingMode) : editor_Segment_Tags {

        // disable when source/target language similar, see TRANSLATE-2373
        if($task->isSourceAndTargetLanguageSimilar()){
            return $tags;
        }

        if($processingMode === editor_Segment_Processing::ALIKE){
            
            // when copying alike tags, we just save the qualities extracted from the tags (if active in config)
            if($task->getConfig()->runtimeOptions->termTagger->enableAutoQA){
                
                Tagger::findAndAddQualitiesInTags($tags);
            }
            
        } else if($processingMode === editor_Segment_Processing::EDIT){

            // processing the segment editing
            $segment = $tags->getSegment();
            // no need to process if task has no terminologie or is not modified
            if (!$task->getTerminologie() || !$segment->isDataModified()) {
                // we need to process the qualities, otherwise the existing wil simply be deleted
                Tagger::findAndAddQualitiesInTags($tags);
                return $tags;
            }
            $config = Zend_Registry::get('config');
            $messages = Zend_Registry::get('rest_messages');
            /* @var $messages ZfExtended_Models_Messages */

            // when the segment is oversized or shall be ignored due to not being editable we do not need to process it and just can return them
            // the editing non-editable segments check is only for completeness here
            if(($segment->meta()->getSourceWordCount() >= $this->getOversizeWordCount($config)) || (!$segment->getEditable() && !$config->runtimeOptions->termTagger->tagReadonlySegments)) {
                // to keep potentially existing tags/qualities we need to process them, otherwise the existing will simply be deleted
                Tagger::findAndAddQualitiesInTags($tags);
                $messages->addError('Termini des zuletzt bearbeiteten Segments konnten nicht ausgezeichnet werden: Das Segment ist zu lang.');
                return $tags;
            }
            // tag the terms in the segment
            $service = editor_Plugins_TermTagger_Bootstrap::createService('termtagger'); /* @var Service $service */
            $serviceUrl = $service->getPooledServiceUrl('gui');
            $processor = new Tagger($task, $service, $processingMode, $serviceUrl, false);
            $processor->process($tags, false);
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
     * Adds Frontend-configurations for the quality types
     * @return array{
     *      field: string,
     *      columnPostfixes: string[]
     * }
     */
    public function getFrontendTypeDefinition() : array {
        return [
            'field' => 'termTagger',
            'columnPostfixes' => ['Column'],
        ];
    }

    /**
     * @param string $processingMode
     * @return ZfExtended_Logger
     * @throws Zend_Exception
     */
    private function getLogger(string $processingMode): ZfExtended_Logger
    {
        return Zend_Registry::get('logger')->cloneMe(Configuration::getLoggerDomain($processingMode));
    }

    /**
     * @param Zend_Config $config
     * @return int
     */
    private function getOversizeWordCount(Zend_Config $config)
    {
        return ($config->runtimeOptions->termTagger->maxSegmentWordCount ?? 150);
    }
}
