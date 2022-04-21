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

/**
 * The central Quality Manager
 * Orchestrates all Quaities via the editor_Segment_Quality_Provider registry and API
 * All plugins that provide Quality relevant APIs have to register a Quality Provider in the Plugin Init/Bootstrap
 * The first use of the Quality manager instance will lock the registry thus registration of providers in a later phase will lead to exceptions
 *
 */
final class editor_Segment_Quality_Manager {

    /**
     * This can be used to disable the AutoQA processing completely. This does not disable the frontend
     * DO ONLY USE FOR DEVELOPMENT PURPOSES
     */
    const ACTIVE = true;
    /**
     * AutoQA-Operation: Performs a re-evaluation of all qualities for the task, retags all segments
     * This Operation also tag's terms and may needs a while
     * @param editor_Models_Task $task
     */
    public static function autoqaOperation(editor_Models_Task $task){
        
        $parentId = editor_Task_Operation::create(editor_Task_Operation::AUTOQA, $task);
  
        self::instance()->queueOperation(editor_Segment_Processing::RETAG, $task, $parentId);
        
        $workerQueue = ZfExtended_Factory::get('ZfExtended_Worker_Queue');
        /* @var $wq ZfExtended_Worker_Queue */
        $workerQueue->trigger();
    }
    /**
     * @var editor_Segment_Quality_Manager
     */
    private static $_instance = null;
    /**
     * Holds all quality provider classes before instantiation (which locks adding to this array)
     * Here base quality checks that are always present / not being added by plugins are defined initially
     * @var string[]
     */
    private static $_provider = [
        'editor_Segment_Internal_Provider',
        'editor_Segment_MatchRate_Provider',
        'editor_Segment_Mqm_Provider',
        'editor_Segment_Qm_Provider',
        'editor_Segment_Length_QualityProvider',
        'editor_Segment_Empty_QualityProvider',
        'editor_Segment_Consistent_QualityProvider',
        'editor_Segment_Whitespace_QualityProvider',
    ];
    /**
     * @var boolean
     */
    private static $_locked = false;
    /**
     * Adds a Provider to the Quality manager
     * @param string $className
     * @throws ZfExtended_Exception
     */
    public static function registerProvider($className){
        if(self::$_locked){
            throw new ZfExtended_Exception('Adding a Quality Provider after app bootstrapping is not allowed.');
        }
        if(!in_array($className, self::$_provider)){
            self::$_provider[] = $className;
        }
    }
    /**
     *
     * @return editor_Segment_Quality_Manager
     */
    public static function instance(){
        if(self::$_instance == null){
            self::$_instance = new editor_Segment_Quality_Manager();
            self::$_locked = true;
        }
        return self::$_instance;
    }
    /**
     * 
     * @var editor_Segment_Quality_Provider[]
     */
    private $registry;
    /**
     *
     * @var ZfExtended_Zendoverwrites_Translate
     */
    private $translate = null;
    /**
     * To prevent any changes during import
     * @var boolean
     */
    private $locked = false;
    /**
     * Just a cache for the export types which are defined statically via the TagProviderInterface
     * @var array
     */
    private $exportTypes = NULL;
    /**
     * The constructor instantiates all providers and locks the registry
     * @throws ZfExtended_Exception
     */
    private function __construct(){
        $this->registry = [];
        foreach(self::$_provider as $providerClass){
            try {
                $provider = new $providerClass();
                /* @var $provider editor_Segment_Quality_Provider */
                $this->registry[$provider->getType()] = $provider;
            } catch (Exception $e) {
                throw new ZfExtended_Exception('Quality Provider '.$providerClass.' does not exist');
            }
        }
    }
    /**
     * 
     * @param string $type
     * @return boolean
     */
    public function hasProvider(string $type){
        return array_key_exists($type, $this->registry);
    }
    /**
     * 
     * @param string $type
     * @return editor_Segment_Quality_Provider|NULL
     */
    public function getProvider(string $type){
        if(array_key_exists($type, $this->registry)){
            return $this->registry[$type];
        }
        return NULL;
    }
    /**
     * Adds the neccessary import workers
     * @param string $taskGuid
     * @param int $workerParentId
     */
    public function queueImport(editor_Models_Task $task, int $workerParentId=0){
        // add starting worker
        $worker = ZfExtended_Factory::get('editor_Segment_Quality_ImportWorker');
        /* @var $worker editor_Segment_Quality_ImportWorker */
        if($worker->init($task->getTaskGuid())){
            $qualityParentId = $worker->queue($workerParentId, null, false);
            // add finishing worker
            $worker = ZfExtended_Factory::get('editor_Segment_Quality_ImportFinishingWorker');
            /* @var $worker editor_Segment_Quality_ImportFinishingWorker */
            if($worker->init($task->getTaskGuid())) {
                $worker->queue($qualityParentId, null, false);
            }
        }
    }
    /**
     * Adds the neccessary workers for an operation
     * @param string $processingMode: as defined in editor_Segment_Processing
     * @param editor_Models_Task $task
     * @param int $workerParentId: this must be the id of the wrapping operation worker
     */
    public function queueOperation(string $processingMode, editor_Models_Task $task, int $workerParentId){
        // add starting worker
        $workerParams = ['processingMode' => $processingMode ]; // mandatory for any quality processing
        $worker = ZfExtended_Factory::get('editor_Segment_Quality_OperationWorker');
        /* @var $worker editor_Segment_Quality_OperationWorker */
        if($worker->init($task->getTaskGuid(), $workerParams)) {
            $worker->queue($workerParentId, null, false);
            // add finishing worker
            $worker = ZfExtended_Factory::get('editor_Segment_Quality_OperationFinishingWorker');
            /* @var $worker editor_Segment_Quality_ImportFinishingWorker */
            if($worker->init($task->getTaskGuid(), $workerParams)) {
                $worker->queue($workerParentId, null, false);
            }
        }
    }
    /**
     * Prepares Term tagging only
     * @param editor_Models_Task $task
     * @param int $parentWorkerId
     */
    public function prepareTagTerms(editor_Models_Task $task, int $parentWorkerId) {
        if(self::ACTIVE) {
            $this->prepareOperation(editor_Segment_Processing::TAGTERMS, $task, $parentWorkerId);
        }
    }
    /**
     * Prepares the quality workers depending on the context/processing type
     * @param string $processingMode
     * @param editor_Models_Task $task
     * @param int $parentWorkerId
     * @param array $workerParams
     */
    public function prepareOperation(string $processingMode, editor_Models_Task $task, int $parentWorkerId, array $workerParams=[]){
        if(self::ACTIVE) {
            foreach ($this->registry as $type => $provider) {
                /* @var $provider editor_Segment_Quality_Provider */
                if ($provider->hasOperationWorker($processingMode)) {
                    $provider->addWorker($task, $parentWorkerId, $processingMode, $workerParams);
                }
            }
        }
    }
    /**
     * Finishes an operation: processes all non-worker providers & saves the processed tags-model back to the segments
     * @param editor_Models_Task $task
     */
    public function finishOperation(string $processingMode, editor_Models_Task $task){
        if(self::ACTIVE) {
            $qualityConfig = $task->getConfig()->runtimeOptions->autoQA;
            $db = ZfExtended_Factory::get('editor_Models_Db_Segments');
            /* @var $db editor_Models_Db_Segments */
            $db->getAdapter()->beginTransaction();
            $sql = $db->select()
                ->from($db, ['id'])
                ->where('taskGuid = ?', $task->getTaskGuid())
                ->order('id')
                ->forUpdate(true);
            $segmentIds = $db->fetchAll($sql)->toArray();
            $segmentIds = array_column($segmentIds, 'id');
            $segment = ZfExtended_Factory::get('editor_Models_Segment');
            $qualities = [];
            /* @var $segment editor_Models_Segment */
            foreach ($segmentIds as $segmentId) {
                $segment->load($segmentId);
                $tags = editor_Segment_Tags::fromSegment($task, $processingMode, $segment, editor_Segment_Processing::isOperation($processingMode));
                // process all quality providers that do not have an import worker
                foreach ($this->registry as $type => $provider) {
                    /* @var $provider editor_Segment_Quality_Provider */
                    if (!$provider->hasOperationWorker($processingMode)) {
                        $tags = $provider->processSegment($task, $qualityConfig, $tags, $processingMode);
                    }
                }
                // we save all qualities at once to reduce db-strain
                $qualities = array_merge($qualities, $tags->extractNewQualities());
                // flush the segment tags content back to the segment
                $tags->flush();
            }
            // save qualities
            editor_Models_Db_SegmentQuality::saveRows($qualities);

            // Append qualities, that can be detected only after all segments are created
            foreach ($this->registry as $type => $provider) {
                /* @var $provider editor_Segment_Quality_Provider */
                if (!$provider->hasOperationWorker($processingMode)) {
                    $tags = $provider->postProcessTask($task, $qualityConfig, $processingMode);
                }
            }
            $db->getAdapter()->commit();
        }
    }
    /**
     * 
     * @param editor_Models_Segment $segment
     * @param editor_Models_Task $task
     * @param string $processingMode
     */
    public function processSegment(editor_Models_Segment $segment, editor_Models_Task $task, string $processingMode){
        if(self::ACTIVE) {
            $qualityConfig = $task->getConfig()->runtimeOptions->autoQA;
            $tags = editor_Segment_Tags::fromSegment($task, $processingMode, $segment, false);
            foreach ($this->registry as $type => $provider) {
                /* @var $provider editor_Segment_Quality_Provider */
                $tags = $provider->processSegment($task, $qualityConfig, $tags, $processingMode);
            }
            $tags->flush();
        }
    }
    
    /**
     * Special API for qualities which can only be evaluated by processing all segments of a task
     * This method is called BEFORE saving the segments and it's repetitions
     * Operations like Import or Analyze will only have ::postProcessTask being called since there are no differences to be detected
     *
     * @param editor_Models_Task $task
     * @param string $processingMode
     */
    public function preProcessTask(editor_Models_Task $task, string $processingMode) {
        if(self::ACTIVE) {
            $qualityConfig = $task->getConfig()->runtimeOptions->autoQA;
            foreach ($this->registry as $type => $provider) {
                /* @var $provider editor_Segment_Quality_Provider */
                $provider->preProcessTask($task, $qualityConfig, $processingMode);
            }
        }
    }
    
    /**
     * Special API for qualities which can only be evaluated by processing all segments of a task
     * This method is called AFTER saving the segments and it's repetitions
     *
     * @param editor_Models_Task $task
     * @param string $processingMode
     */
    public function postProcessTask(editor_Models_Task $task, string $processingMode) {
        if(self::ACTIVE){
            $qualityConfig = $task->getConfig()->runtimeOptions->autoQA;
            foreach ($this->registry as $type => $provider) {
                /* @var $provider editor_Segment_Quality_Provider */
                $provider->postProcessTask($task, $qualityConfig, $processingMode);
            }
        }
    }
    
    /**
     * Alike Segments have a special processing as they clone some qualities from their original segment
     * @param editor_Models_Segment $segment
     * @param editor_Models_Task $task
     * @param editor_Segment_Alike_Qualities $alikeQualities
     */
    public function processAlikeSegment(editor_Models_Segment $segment, editor_Models_Task $task, editor_Segment_Alike_Qualities $alikeQualities){
        if(self::ACTIVE) {
            $processingMode = editor_Segment_Processing::ALIKE;
            $qualityConfig = $task->getConfig()->runtimeOptions->autoQA;
            $tags = editor_Segment_Tags::fromSegment($task, $processingMode, $segment, false);
            $tags->initAlikeQualities($alikeQualities);
            foreach ($this->registry as $type => $provider) {
                /* @var $provider editor_Segment_Quality_Provider */
                $tags = $provider->processSegment($task, $qualityConfig, $tags, $processingMode);
            }
            $tags->flush();
        }
    }
    /**
     * The central API to identify the needed Tag class by classnames and attributes
     * @param string $tagType
     * @param string $nodeName
     * @param string[] $classNames
     * @param string[] $attributes
     * @param int $startIndex
     * @param int $endIndex
     * @return editor_Segment_Tag | NULL
     */
    public function evaluateInternalTag(string $tagType, string $nodeName, array $classNames, array $attributes, int $startIndex, int $endIndex){
        if(!empty($tagType) && array_key_exists($tagType, $this->registry)){
            if($this->registry[$tagType]->isSegmentTag($tagType, $nodeName, $classNames, $attributes)){
                return $this->registry[$tagType]->createSegmentTag($startIndex, $endIndex, $nodeName, $classNames);
            }            
            return NULL;
        }
        foreach($this->registry as $type => $provider){
            /* @var $provider editor_Segment_Quality_Provider */
            if($provider->isSegmentTag($tagType, $nodeName, $classNames, $attributes)){
                return $provider->createSegmentTag($startIndex, $endIndex, $nodeName, $classNames);
            }
        }
        return NULL;
    }
    /**
     * Translates a Segment Quality Type
     * @param string $type
     * @throws ZfExtended_Exception
     * @return string
     */
    public function translateQualityType(string $type) : string {
        if($this->hasProvider($type)){
            $translation = $this->getProvider($type)->translateType($this->getTranslate());
            if($translation === NULL){
                throw new ZfExtended_Exception('editor_Segment_Quality_Manager::translateQualityType: provider of type "'.$type.'" has no translation for the type".');
            }
            return $translation;
        }
        throw new ZfExtended_Exception('editor_Segment_Quality_Manager::translateQualityType: provider of type "'.$type.'" not present.');
        return '';
    }
    /**
     * Translates a Segment Quality Type tooltip
     * @param string $type
     * @throws ZfExtended_Exception
     * @return string
     */
    public function translateQualityTypeTooltip(string $type) : string {
        if ($this->hasProvider($type)) {
            return $this->getProvider($type)->translateTypeTooltip($this->getTranslate());
        }
        throw new ZfExtended_Exception('editor_Segment_Quality_Manager::translateQualityTypeTooltip: provider of type "'.$type.'" not present.');
        return '';
    }
    /**
     * Translates a Segment Quality Code that is referenced in LEK_segment_quality category in conjunction with type
     * @param string $type
     * @param string $category
     * @param editor_Models_Task $task
     * @throws ZfExtended_Exception
     * @return string
     */
    public function translateQualityCategory(string $type, string $category, editor_Models_Task $task) : string {
        if($this->hasProvider($type)){
            $translation = $this->getProvider($type)->translateCategory($this->getTranslate(), $category, $task);
            if($translation === NULL){
                throw new ZfExtended_Exception('editor_Segment_Quality_Manager::translateQualityCategory: provider of type "'.$type.'" has no translation of category "'.$category.'".');
            }
            return $translation;
        }
        throw new ZfExtended_Exception('editor_Segment_Quality_Manager::translateQualityCategory: provider of type "'.$type.'" not present.');
        return '';
    }
    /**
     * Evaluates, if the quality of the given type has categories
     * @param string $type
     * @return bool
     */
    public function hasCategories(string $type) : bool {
        if($this->hasProvider($type)) {
            return $this->getProvider($type)->hasCategories();
        }
        return false;
    }
    /**
     * Evaluates, if a quality of the given type renders tags in the tags texts
     * @param string $type
     * @return bool
     */
    public function hasSegmentTags(string $type) : bool {
        if($this->hasProvider($type)) {
            return $this->getProvider($type)->hasSegmentTags();
        }
        return false;
    }
    /**
     * Evaluates, if a quality of the given type is a type that generally should show up in the filter panel and in the task properties
     * @param string $type
     * @return bool
     */
    public function isFilterableType(string $type) : bool {
        if($this->hasProvider($type)) {
            return $this->getProvider($type)->isFilterableType();
        }
        return false;
    }
    /**
     * Evaluates, if a quality of the given type and category can be false positive
     * @param string $type
     * @param string $category
     * @return bool
     */
    public function canBeFalsePositiveCategory(string $type, string $category) : bool {
        if($this->hasProvider($type)) {
            return $this->getProvider($type)->canBeFalsePositiveCategory($category);
        }
        return false;
    }
    /**
     * Evaluates, if the quality of the given type is fully configured/checked
     * @param string $type
     * @param Zend_Config $taskConfig
     * @return bool
     */
    public function isFullyCheckedType(string $type, Zend_Config $taskConfig) : bool {
        if($this->hasProvider($type)) {
            return $this->getProvider($type)->isFullyChecked($taskConfig->runtimeOptions->autoQA, $taskConfig);
        }
    }
    /**
     * Retrieves all types of qualities we have. By default only those, that should show up in the filter-panel
     * @param bool $includeNonFilterableTypes
     * @return string[]
     */
    public function getAllFilterableTypes(editor_Models_Task $task){
        $types = [];
        $taskConfig = $task->getConfig();
        $qualityConfig = $taskConfig->runtimeOptions->autoQA; 
        foreach($this->registry as $type => $provider){
            /* @var $provider editor_Segment_Quality_Provider */
            if($provider->isActive($qualityConfig, $taskConfig) && $provider->isFilterableType()){
                $types[] = $provider->getType();
            }
        }
        return $types;
    }
    /**
     * Retrieves the types of qualities that should not show up in the quality panel & quality task views
     * @return string[]
     */
    public function getFilterTypeBlacklist(){
        $blacklist = [];
        foreach($this->registry as $type => $provider){
            /* @var $provider editor_Segment_Quality_Provider */
            if(!$provider->isFilterableType()){
                $blacklist[] = $provider->getType();
            }
        }
        return $blacklist;
    }
    /**
     * Retrieves all types that will be exported (with further processing)
     * @return array
     */
    public function getAllExportedTypes(){
        if($this->exportTypes === NULL){
            $this->exportTypes = [];
            foreach($this->registry as $type => $provider){
                /* @var $provider editor_Segment_Quality_Provider */
                if($provider->isExportedTag()){
                    $this->exportTypes[] = $provider->getType();
                }
            }
        }
        return $this->exportTypes;
    }
    /**
     * 
     * @return ZfExtended_Zendoverwrites_Translate
     */
    public function getTranslate(){
        if($this->translate == null){
            $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        }
        return $this->translate;
    }
}
