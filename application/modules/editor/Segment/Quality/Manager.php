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
 * The central Quality Manager
 * Orchestrates all Quaities via the editor_Segment_Quality_Provider registry and API
 * All plugins that provide Quality relevant APIs have to register a Quality Provider in the Plugin Init/Bootstrap
 * The first use of the Quality manager instance will lock the registry thus registration of providers in a later phase will lead to exceptions
 *
 */
final class editor_Segment_Quality_Manager {
   
    /**
     * @var editor_Segment_Quality_Manager
     */
    private static $_instance = null;
    /**
     * @var string[]
     */
    private static $_provider = [];
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
        // Some Base Providers that do not come from Plugins
        // Tag Check
        $provider = new editor_Segment_Internal_Provider();
        $this->registry[$provider->getType()] = $provider;
        // MatchRate
        $provider = new editor_Segment_MatchRate_Provider();
        $this->registry[$provider->getType()] = $provider;
        // MQM
        $provider = new editor_Segment_Mqm_Provider();
        $this->registry[$provider->getType()] = $provider;
        // QM
        $provider = new editor_Segment_Qm_Provider();
        $this->registry[$provider->getType()] = $provider;
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
     * Prepares the import: adds all Import Workers that exist for Quality tags
     * @param editor_Models_Task $task
     * @param int $parentWorkerId
     */
    public function prepareImport(editor_Models_Task $task, int $parentWorkerId){
        
        foreach($this->registry as $type => $provider){
            /* @var $provider editor_Segment_Quality_Provider */
            if($provider->hasImportWorker()){
                $provider->addWorker($task, $parentWorkerId, editor_Segment_Processing::IMPORT);
            }
        }
    }
    /**
     * Finishes the import: processes all non-worker providers & saves the processed tags-model back to the segments
     * @param editor_Models_Task $task
     */
    public function finishImport(editor_Models_Task $task){
        
        $qualityConfig = $task->getConfig()->runtimeOptions->autoQA;
        $processingMode = editor_Segment_Processing::IMPORT;
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
        foreach($segmentIds as $segmentId){
            $segment->load($segmentId);
            $tags = editor_Segment_Tags::fromSegment($task, $processingMode, $segment, true);
            // process all quality providers that do not have an import worker
            foreach($this->registry as $type => $provider){
                /* @var $provider editor_Segment_Quality_Provider */
                if(!$provider->hasImportWorker()){
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
        // remove segment tags model
        editor_Models_Db_SegmentTags::removeByTaskGuid($task->getTaskGuid());
        
        $db->getAdapter()->commit();
    }
    /**
     * 
     * @param editor_Models_Segment $segment
     * @param editor_Models_Task $task
     * @param string $processingMode
     */
    public function processSegment(editor_Models_Segment $segment, editor_Models_Task $task, string $processingMode){
        $qualityConfig = $task->getConfig()->runtimeOptions->autoQA; 
        $tags = editor_Segment_Tags::fromSegment($task, $processingMode, $segment, false);
        foreach($this->registry as $type => $provider){
            /* @var $provider editor_Segment_Quality_Provider */
            $tags = $provider->processSegment($task, $qualityConfig, $tags, $processingMode);
        }
        $tags->flush();
    }
    /**
     * Alike Segments have a special processing as they clone some qualities from their original segment
     * @param editor_Models_Segment $segment
     * @param editor_Models_Task $task
     * @param editor_Segment_Alike_Qualities $alikeQualities
     */
    public function processAlikeSegment(editor_Models_Segment $segment, editor_Models_Task $task, editor_Segment_Alike_Qualities $alikeQualities){
        $processingMode = editor_Segment_Processing::ALIKE;
        $qualityConfig = $task->getConfig()->runtimeOptions->autoQA;
        $tags = editor_Segment_Tags::fromSegment($task, $processingMode, $segment, false);
        $tags->initAlikeQualities($alikeQualities);        
        foreach($this->registry as $type => $provider){
            /* @var $provider editor_Segment_Quality_Provider */
            $tags = $provider->processSegment($task, $qualityConfig, $tags, $processingMode);
        }
        $tags->flush();
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
            if(empty($translation)){
                throw new ZfExtended_Exception('editor_Segment_Quality_Manager::translateQuality: provider of type "'.$type.'" not present.');
            }
            return $translation;
        }
        throw new ZfExtended_Exception('editor_Segment_Quality_Manager::translateQuality: provider of type "'.$type.'" has no translation for the type".');
        return NULL;
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
            if(empty($translation)){
                throw new ZfExtended_Exception('editor_Segment_Quality_Manager::translateQuality: provider of type "'.$type.'" not present.');
            }
            return $translation;
        }        
        throw new ZfExtended_Exception('editor_Segment_Quality_Manager::translateQuality: provider of type "'.$type.'" has no translation of category "'.$category.'".');
        return NULL;
    }
    /**
     * Evaluates, if a quality of the given type renders tags in the tags texts
     * @param string $type
     * @return bool
     */
    public function hasSegmentTags(string $type) : bool {
        return $this->getProvider($type)->hasSegmentTags();
    }
    /**
     * Evaluates, if a quality of the given type is a type that generally should show up in the filter panel and in the task properties
     * @param string $type
     * @return bool
     */
    public function isFilterableType(string $type) : bool {
        return $this->getProvider($type)->isFilterableType();
    }
    /**
     * Evaluates, if a quality of the given type and category can be false positive
     * @param string $type
     * @param string $category
     * @return bool
     */
    public function canBeFalsePositiveCategory(string $type, string $category) : bool {
        return $this->getProvider($type)->canBeFalsePositiveCategory($category);
    }
    /**
     * Evaluates, if the quality of the given type is fully configured/checked
     * @param string $type
     * @param Zend_Config $taskConfig
     * @return bool
     */
    public function isFullyCheckedType(string $type, Zend_Config $taskConfig) : bool {
        return $this->getProvider($type)->isFullyChecked($taskConfig->runtimeOptions->autoQA);
    }
    /**
     * Retrieves all types of qualities we have. By default only those, that should show up in the filter-panel
     * @param bool $includeNonFilterableTypes
     * @return string[]
     */
    public function getAllTypes(bool $includeNonFilterableTypes=false){
        $types = [];
        foreach($this->registry as $type => $provider){
            /* @var $provider editor_Segment_Quality_Provider */
            if($includeNonFilterableTypes || $provider->isFilterableType()){
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
     * Retrieves the types of qualities that have tags in the segment texts
     * @return string[] hashtable type => cssClass
     */
    public function getAllSegmentTagTypeClasses(){
        $data = [];
        foreach($this->registry as $type => $provider){
            /* @var $provider editor_Segment_Quality_Provider */
            if($provider->hasSegmentTags()){
                $data[$provider->getType()] = $provider->getTagIndentificationClass();
            }
        }
        return $data;
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
