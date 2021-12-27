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
 * Base Implementation for all Quality Providers
 *
 */
abstract class editor_Segment_Quality_Provider implements editor_Segment_TagProviderInterface {
    
    /**
     * Retrieves our type
     * @return string
     */
    public static function qualityType(){
        return static::$type;
    }
    /**
     * MUST be set in inheriting classes
     * @var string
     */
    protected static $type = NULL;

    /**
     * @var bool
     */
    public static $hasCategories = true;

    /**
     * MUST be set in inheriting classes if there is a related segment tag
     * @var string
     */
    protected static $segmentTagClass = 'editor_Segment_AnyTag';

    public function __construct(){
        if(static::$type == NULL){
            throw new ZfExtended_Exception(get_class($this).' must have a ::$type property defined');
        }
    }
    /**
     * Retrieves the provider type that is a system-wide unique string to identify the provider
     * @return string
     */
    public function getType() : string {
        return static::$type;
    }
    /**
     * Retrieves if the provider has an own worker for the given processing type
     * @param string $processingMode
     * @return boolean
     */
    public function hasOperationWorker(string $processingMode) : bool {
        return false;
    }
    /**
     * 
     * @param editor_Models_Task $task
     * @param int $parentWorkerId
     * @param string $processingMode
     * @param array $workerParams
     */
    public function addWorker(editor_Models_Task $task, int $parentWorkerId, string $processingMode, array $workerParams=[]) {
  
    }
    /**
     * Processes the Segment and it's tags for the editing (which is unthreaded)
     * Note: the return value is used for further processing so it might even be possible to create a new tags-object though this is highly unwanted
     * Note: When the provider is not active, this must be considered in the implementing code
     * @param editor_Models_Task $task
     * @param Zend_Config $qualityConfig: the quality configuration as defined in runtimeOptions.autoQA.XXX
     * @param editor_Segment_Tags $tags
     * @param string $processingMode: see Modes in editor_Segment_Processing
     * @return editor_Segment_Tags
     */
    public function processSegment(editor_Models_Task $task, Zend_Config $qualityConfig, editor_Segment_Tags $tags, string $processingMode) : editor_Segment_Tags {
        return $tags;
    }
    
    /* *************** Translation API *************** */
    
    /**
     * Returns a translation for the Provider itself
     * @param ZfExtended_Zendoverwrites_Translate $translate
     * @return string
     */
    public function translateType(ZfExtended_Zendoverwrites_Translate $translate) : ?string {
        return NULL;
    }

    /**
     * Returns a translation for the Provider tooltip
     *
     * @param ZfExtended_Zendoverwrites_Translate $translate
     * @return string
     */
    public function translateTypeTooltip(ZfExtended_Zendoverwrites_Translate $translate) : ?string {
        return '';
    }
    /**
     * Returns a translation for a Quality. These Codes are stored in the category column of the LEK_segment_quality model
     * Because MQM translations are task-specific, the task is needed
     * @param ZfExtended_Zendoverwrites_Translate $translate
     * @param string $category
     * @param editor_Models_Task $task
     * @return string
     */
    public function translateCategory(ZfExtended_Zendoverwrites_Translate $translate, string $category, editor_Models_Task $task) : ?string {
        return NULL;
    }
    
    /* *************** REST view API *************** */
    
    /**
     * Retrieves, if the quality is configuered to be active
     * @param Zend_Config $qualityConfig
     * @return bool
     */
    public function isActive(Zend_Config $qualityConfig, Zend_Config $taskConfig) : bool {
        return true;
    }
    /**
     * Retrieves, if the Quality has tags in the segment texts present
     * @return bool
     */
    public function hasSegmentTags() : bool {
        return true;
    }
    /**
     * Retrieves, if the quality type is filterable (will be shown in the filter panel or task panel)
     * @return bool
     */
    public function isFilterableType() : bool {
        return true;
    }
    /**
     * Retrieves, if the given category can be a false positive
     * @param string $category
     * @return bool
     */        
    public function canBeFalsePositiveCategory(string $category) : bool {
        return true;
    }
    /**
     * Retrieves, if the Quality type is properly configured/checked (all configurations active) 
     * @param Zend_Config $qualityConfig
     * @return bool
     */
    public function isFullyChecked(Zend_Config $qualityConfig, Zend_Config $taskConfig) : bool {
        return true;
    }
    /**
     * Retrieves all Categories a quality can have
     * @param editor_Models_Task $task
     * @return string[]
     */
    public function getAllCategories(editor_Models_Task $task) : array {
        return [];
    }
    
    /* *************** Tag provider API *************** */
    
    /**
     * Quality Providers must use the same key to identify the provider & all of it's tags
     * {@inheritDoc}
     * @see editor_Segment_TagProviderInterface::getTagType()
     */
    public function getTagType() : string {
        return static::$type;
    }
    
    public function isSegmentTag(string $type, string $nodeName, array $classNames, array $attributes) : bool {
        return false;
    }
    
    public function isExportedTag() : bool {
        return false;
    }

    public function createSegmentTag(int $startIndex, int $endIndex, string $nodeName, array $classNames) : editor_Segment_Tag {
        $className = static::$segmentTagClass;
        return new $className($startIndex, $endIndex);
    }
    
    public function getTagIndentificationClass() : ?string {
        if($this->hasSegmentTags()){
            return $this->createSegmentTag(0, 0, 'span', [])->getIdentificationClass();
        }
        return NULL;
    }
    
    public function getTagNodeName() : ?string {
        // Quirk: we must pass a node-name & classes here just to fulfill the interface which is meant to identify the class, those props are usually overwritten in the class
        if($this->hasSegmentTags()){
            return $this->createSegmentTag(0, 0, 'span', [])->getName();
        }
        return NULL;
    }
}
