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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Abstract Base Connector
 */
abstract class editor_Services_Connector_Abstract {
    
    //FIXME this is just a temporary solution until TagTrait is refactored into smaller reusable classes, see TRANSLATE-1509 
    use editor_Models_Import_FileParser_TagTrait;
    
    const STATUS_NOTCHECKED = 'notchecked';
    const STATUS_ERROR = 'error';
    const STATUS_AVAILABLE = 'available';
    const STATUS_UNKNOWN = 'unknown';
    const STATUS_NOCONNECTION = 'noconnection';
    const STATUS_NOVALIDLICENSE = 'novalidlicense';
    const STATUS_NOT_LOADED = 'notloaded';
    
    const FUZZY_SUFFIX = '-fuzzy-';
    
    /***
     * Source languages array key for the languages result.
     * In some of the resources the supported "from-to" languages are not the same.
     * That is why the languages grouping is required in some of them. 
     * @var string
     */
    const SOURCE_LANGUAGES_KEY = 'sourceLanguages';
    
    /***
     * Target languages array key for the languages result.
     * In some of the resources the supported "from-to" languages are not the same.
     * That is why the languages grouping is required in some of them.
     * @var string
     */
    const TARGET_LANGUAGES_KEY = 'targetLanguages';
    
    /*** 
     * Default resource matchrate
     * @var integer
     */
    protected $defaultMatchRate=0;
    
    /**
     * @var editor_Models_LanguageResources_LanguageResource
     */
    protected $languageResource;
    
    /**
     * Container for the connector results
     * @var editor_Services_ServiceResult
     */
    protected $resultList;
    

    /***
     * connector source language
     * @var integer
     */
    protected $sourceLang;
    
    
    /***
     * connector target language
     * @var integer
     */
    protected $targetLang;
    

    /***
     * Flag for if the current connector supports internal fuzzy calculations
     * @var boolean
     */
    protected $isInternalFuzzy = false;
    
    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $internalTag;
    
    /**
     * @var editor_Models_Segment_Whitespace
     */
    protected $whitespaceHelper;
    
    /**
     * @var editor_Models_Segment_TrackChangeTag
     */
    protected $trackChange;
    
    /**
     * Internal flag which stores the info if tags where stripped by the query call or not
     * @var string
     */
    protected $tagsWereStripped = true;
    
    /***
     * @var editor_Models_LanguageResources_Resource
     */
    protected $resource;
    
    /**
     * initialises the internal result list
     */
    public function __construct() {
        $this->resultList = ZfExtended_Factory::get('editor_Services_ServiceResult');
        $this->internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        $this->trackChange = ZfExtended_Factory::get('editor_Models_Segment_TrackChangeTag');
        
        $this->initHelper();
            //$this->whitespaceHelper = ZfExtended_Factory::get('editor_Models_Segment_Whitespace');
        $this->initImageTags();
    }
    
    /***
     * Check the resource connection. This will return true conection with the resource can
     * be established
     * @param editor_Models_LanguageResources_Resource $resource
     * @return boolean
     */
    public function ping(editor_Models_LanguageResources_Resource $resource){
        $this->resource = $resource;
        $moreInfo = "";
        //the valid api response statuses
        $isValidFor = [self::STATUS_AVAILABLE,self::STATUS_NOT_LOADED];
        return in_array($this->getStatus($moreInfo), $isValidFor);
    }
    
    /**
     * Just for logging the called methods
     * @param string $msg
     */
    protected function log($method, $msg = '') {
        //error_log($method." LanguageResource ".$this->languageResource->getName().' - '.$this->languageResource->getServiceName().$msg);
    }
    
    /**
     * Link this Connector Instance to the given LanguageResource and its resource, in the given language combination
     * @param editor_Models_LanguageResources_LanguageResource $languageResource
     * @param int $sourceLang language id 
     * @param int $targetLang language id 
     */
    public function connectTo(editor_Models_LanguageResources_LanguageResource $languageResource, $sourceLang, $targetLang) {
        $this->sourceLang = $sourceLang;
        $this->targetLang = $targetLang;
        $this->resource = $languageResource->getResource();
        $this->languageResource = $languageResource;
        $this->resultList->setLanguageResource($languageResource);
        if($languageResource->getId()!=null){
            $this->languageResource->sourceLangCode=$this->languageResource->getSourceLangCode();
            $this->languageResource->targetLangCode=$this->languageResource->getTargetLangCode();
        }
    }
    
    /**
     * Updates translations in the connected service
     * for returning error messages to the GUI use rest_messages
     * @param editor_Models_Segment $segment
     */
    public function update(editor_Models_Segment $segment) {
        //to be implemented if needed
        $this->log(__METHOD__, ' segment '.$segment->getId());
    }
    
    /***
     * Reset the tm result list data
     */
    public function resetResultList(){
        $this->resultList->resetResult();
    }
    
    /***
     * Get the connector language ressource
     * @return editor_Models_LanguageResources_LanguageResource
     */
    public function getLanguageResource(){
        return $this->languageResource;
    }
    
    /***
     * Return the connectors default matchrate.(this should be configured in the zf config)
     * @return number
     */
    public function getDefaultMatchRate(){
        return $this->defaultMatchRate;
    }

    /**
     * makes a tm / mt / file query to find a match / translation
     * returns an array with stdObjects, each stdObject contains the fields: 
     * 
     * @param editor_Models_Segment $segment
     * @return editor_Services_ServiceResult
     */
    abstract public function query(editor_Models_Segment $segment);

    /**
     * returns the original or edited source content to be queried, depending on source edit
     * @param editor_Models_Segment $segment
     * @return string
     */
    public function getQueryString(editor_Models_Segment $segment) {
        return $this->getQueryStringByName($segment, editor_Models_SegmentField::TYPE_SOURCE);
    }
    
    /***
     * returns the original or edited $segmentField content to be queried, depending on source edit
     * 
     * @param editor_Models_Segment $segment
     * @param string $segmentField: segmentField (source or target)
     * @return string
     */
    public function getQueryStringByName(editor_Models_Segment $segment,string $segmentField) {
        $sfm = editor_Models_SegmentFieldManager::getForTaskGuid($segment->getTaskGuid());
        $sourceMeta = $sfm->getByName($segmentField);
        $isSourceEdit = ($sourceMeta !== false && $sourceMeta->editable == 1);
        return $isSourceEdit ? $segment->getFieldEdited($segmentField) : $segment->getFieldOriginal($segmentField);
    }
    
    /**
     * prepares and gets the query string in a default manner:
     * - restore whitespace
     * - remove all translate5 tags 
     * the single steps from that function can be reused if needed the query string in a different way 
     * @param editor_Models_Segment $segment
     * @return string 
     */
    protected function prepareDefaultQueryString(editor_Models_Segment $segment) {
        //1. organizational preparation
        $qs = $this->getQueryString($segment);
        $this->resultList->setDefaultSource($qs);
        if(empty($qs) && $qs !== "0") {
            return $qs;
        }
        
        //2. whitespace preparation
        $qs = $this->restoreWhitespaceForQuery($qs);
        
        //3. set flag if tags were removed or not (= if the segment was containing flags)
        $this->tagsWereStripped = $this->internalTag->count($qs) > 0;
        
        //4. strip tags
        return $segment->stripTags($qs);
    }
    
    /**
     * Prepare sources and targets for being handled by the LanguageResource:
     * - removeTrackChanges
     * - restore whitespaces to real characters
     * @param string $contentString
     * @return string $preparedString
     */
    protected function prepareSegmentContent(string $contentString) {
        $preparedString = $contentString;
        // removeTrackChanges
        $preparedString = $this->trackChange->removeTrackChanges($preparedString);
        //restore the whitespaces to real characters
        $preparedString = $this->internalTag->restore($preparedString, true);
        $preparedString = $this->whitespaceHelper->unprotectWhitespace($preparedString);
        $preparedString = $this->internalTag->toXliffPaired($preparedString);
        return $preparedString;
    }
    
    /**
     * restores whitespace in segment content and removes track changes before
     * @param string $queryString
     * @return string
     */
    protected function restoreWhitespaceForQuery($queryString) {
        $qs = $this->trackChange->removeTrackChanges($queryString);
        //restore the whitespaces to real characters
        $qs = $this->internalTag->restore($qs, true);
        return $this->whitespaceHelper->unprotectWhitespace($qs);
    }
    
    /**
     * converts whitespace coming from the connected resource to translate5 usable whitespace tags
     * Warning: text may not contain other tags - they will be destroyed! For more complex solution see OpenTM2
     * 
     * @param string $textNode
     */
    protected function importWhitespaceFromTagLessQuery($textNode) {
        $textNode = $this->whitespaceHelper->protectWhitespace($textNode, false);
        return $this->whitespaceTagReplacer($textNode);
    }
    
    /**
     * (For concordance search:) Highlight the searchString in the found source/target.
     * @param string $searchString
     * @param string $haystack
     * @param bool $doit
     */
    protected function highlight ($searchString, $haystack, $doit) {
        if(!$doit){
            return $haystack;
        }
        return preg_replace('/('.preg_quote($searchString, '/').')/i', '<span class="highlight">\1</span>', $haystack);
    }
    
    /**
     * makes a tm / mt / file concordance search
     * @param string $queryString
     * @param string $field
     * @return editor_Services_ServiceResult
     */
    abstract public function search(string $searchString, $field = 'source', $offset = null);
    
    /**
     * @return editor_Services_ServiceResult the status of the connected resource and additional information if there is some
     */
    abstract public function getStatus(& $moreInfo);
    
    /***
     * Search the resource for available translation. Where the source text is in resource source language and the received results
     * are in the resource target language 
     * 
     * @param string $searchString plain text without tags
     * @return editor_Services_ServiceResult
     */
    abstract public function translate(string $searchString);
    
    /**
     * Opens the with connectTo given TM on the configured Resource (on task open, not on each request)
     * @param editor_Models_LanguageResources_LanguageResource $languageResource
     */
    public function open() {
        //to be implemented if needed
        $this->log(__METHOD__);
    }
    
    /**
     * Closes the connected TM on the configured Resource (on task close, not after each request)
     */
    public function close() {
        //to be implemented if needed
        $this->log(__METHOD__);
    }
    
    /***
     * Return the available language codes for the current resource endpoint(api)
     * Use SOURCE_LANGUAGES_KEY and TARGET_LANGUAGES_KEY as languages grouped results when 
     * the resource does not support same from - to language combinations
     */
    public function languages(){
        $languages = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $languages editor_Models_Languages*/
        $ret=$languages->loadAllKeyValueCustom('id','rfc5646');
        return array_values($ret);
    }
    
    /***
     * Initialize fuzzy connectors. Returns the current instance if not supported.
     * @param int $analysisId
     * @return editor_Services_Connector_Abstract
     */
    public function initForFuzzyAnalysis($analysisId) {
        return $this;
    }
    
    /**
     * The fuzzy languageResource name format is: oldname-fuzzy-AnalysisId
     */
    protected function renderFuzzyLanguageResourceName($name, $analysisId) {
        return $name.self::FUZZY_SUFFIX.$analysisId;
    }
    
    /***
     * Is internal fuzzy connector
     * @return boolean
     */
    public function isInternalFuzzy() {
        return $this->isInternalFuzzy;
    }
}
