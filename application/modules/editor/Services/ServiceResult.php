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
 * Container class for one single service result
 * Main Intention of this class, provide a unified response format for the different services.
 */
class editor_Services_ServiceResult {
    const STATUS_LOADED = 'loaded';
    const STATUS_SERVERERROR = 'servererror';
    
    protected $defaultSource = '';
    protected $defaultMatchrate;
    
    protected $results = [];
    protected $lastAdded;
    
    /**
     * @var editor_Models_LanguageResources_LanguageResource
     */
    protected $languageResource;
    
    /**
     * next offset with found data, needed for paging
     * @var mixed
     */
    protected $nextOffset = null;
    
    /**
     * Total results, needed for paging
     * @var editor_Models_Segment_InternalTag
     */
    protected $internalTag;
    
    /**
     * A default source text for the results and a defaultMatchrate can be set
     * The default values are the used as initial value for new added result sets
     * @param string $defaultSource
     * @param int $defaultMatchrate
     */
    public function __construct($defaultSource = '', $defaultMatchrate = 0) {
        $this->internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        $this->defaultMatchrate = (int) $defaultMatchrate;
        $this->defaultSource = $defaultSource;
    }
    
    /**
     * Optional, sets a default source text to be used foreach added result
     * @param string $defaultSource
     */
    public function setDefaultSource(string $defaultSource) {
        $this->defaultSource = $defaultSource;
    }
    
    /**
     * Set the source field for the last added result
     * @param string $source
     */
    public function setSource($source) {
        $this->lastAdded->source = $source;
    }
    
    /**
     * sets the resultlist count total which should be send to the server
     * How the total is calculated, depends on the service.
     * @param int $total
     */
    public function setNextOffset($offset) {
        $this->nextOffset = $offset;
    }
    
    /**
     * Set the source field for the last added result
     * @param string $source
     */
    public function setAttributes($attributes) {
        $this->lastAdded->attributes = $attributes;
    }
    
    /**
     * Adds a new result set to the result list. Only target and $matchrate are mandatory.
     * All additonal data can be provided by 
     * 
     * @param string $target
     * @param int $matchrate
     * @param array $metaData metadata container
     * 
     * @return stdClass the last added result
     */
    public function addResult($target, $matchrate = 0, array $metaData = null) {
        $result = new stdClass();
        
        $result->target = $target;
        $result->matchrate = (int) $matchrate;
        $result->source = $this->defaultSource;
        $result->languageResourceid = $this->languageResource->getId();
        $result->languageResourceType = $this->languageResource->getResourceType();
        
        $result->state = self::STATUS_LOADED;
        
        $result->metaData = $metaData;
        
        $this->results[] = $result;
        $this->lastAdded = $result;
        return $result;
    }
    
    /**
     * returns the found next offset of the search
     * @return mixed
     */
    public function getNextOffset() {
        return $this->nextOffset;
    }
    
    /**
     * returns a plain array of result objects
     * @return [stdClass]
     */
    public function getResult() {
        return $this->results;
    }
    
    public function setResults($results){
        $this->results = $results;
    }
    
    public function resetResult(){
        $this->results = [];
    }
    
    /**
     * @param editor_Models_LanguageResources_LanguageResource $languageResource
     */
    public function setLanguageResource(editor_Models_LanguageResources_LanguageResource $languageResource){
        $this->languageResource = $languageResource;
    }
    
    /***
     * Get meta value by meta name from meta data object
     * @param array $metaData
     * @param string $fieldName
     * @return NULL|string
     */
    public function getMetaValue($metaData,$fieldName){
        if(empty($metaData)){
            return null;
        }
        foreach ($metaData as $data) {
            if($data->name == $fieldName){
                return $data->value;
            }
        }
        return null;
    }
    
    /***
     * Check if the current result set contains result with matchrate >=100
     * @return boolean
     */
    public function has100PercentMatch() {
        if(empty($this->getResult())){
            return false;
        }
        foreach ($this->getResult() as $res){
            if(isset($res->matchrate) && $res->matchrate>=100){
                return true;
            }
        }
        return false;
    }

    public function setRawContent(string $source, string $target): void
    {
        $this->lastAdded->rawSource = $source;
        $this->lastAdded->rawTarget = $target;
    }
}
