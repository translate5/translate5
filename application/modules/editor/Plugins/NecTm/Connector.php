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
 * NEC-TM Connector
 */
class editor_Plugins_NecTm_Connector extends editor_Services_Connector_FilebasedAbstract {

    /**
     * @var editor_Plugins_NecTm_HttpApi
     */
    protected $api;
    
    /***
     * Filename by file id cache
     * @var array
     */
    public $fileNameCache=array();
    
    
    /**
     * @var editor_Models_Import_FileParser_XmlParser
     */
    protected $xmlparser;
    
    /**
     * The categories for the languageResource: 
     * - those assigned to the languageResource by the user
     * AND
     * - those configured as top-level-categories in ZfConfig
     * @var array
     */
    protected $categories;
    
    /**
     * @var editor_Models_Categories
     */
    protected $categoriesModel;
    
    /**
     * All languages (id => rfc5646)
     * @var array 
     */
    protected $lngs;
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::connectTo()
     */
    public function connectTo(editor_Models_LanguageResources_LanguageResource $languageResource, $sourceLang, $targetLang) {
        parent::connectTo($languageResource, $sourceLang, $targetLang);
        $this->api = ZfExtended_Factory::get('editor_Plugins_NecTm_HttpApi');
        $this->xmlparser= ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        $this->setCategories($languageResource);
        $this->categoriesModel = ZfExtended_Factory::get('editor_Models_Categories');
    }
    
    /**
     * Set the categories for the languageResource: 
     * - those assigned to the languageResource by the user
     * AND
     * - those configured as top-level-categories in ZfConfig
     * @param editor_Models_LanguageResources_LanguageResource $languageResource
     */
    protected function setCategories($languageResource) {
        $categoriesFromResource = $languageResource->getOriginalCategoriesIds();
        $service = ZfExtended_Factory::get('editor_Plugins_NecTm_Service');
        /* @var $service editor_Plugins_NecTm_Service */
        $categoriesFromService = $service->getTopLevelCategoriesIds();
        $this->categories = array_unique(array_merge($categoriesFromResource, $categoriesFromService), SORT_STRING);
    }
    
    /**
     * 
     */
    protected function getRfcLang($lang) {
        if (!$this->lngs) {
            // "lazy" load: all languages
            $langModel = ZfExtended_Factory::get('editor_Models_Languages');
            /* @var $langModel editor_Models_Languages */
            $this->lngs = $langModel->loadAllKeyValueCustom('id','rfc5646');
        }
        return $this->lngs[$lang];
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::open()
     */
    public function open() {
        //This call is not necessary, since this resource is opened automatically.
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::close()
     */
    public function close() {
        //This call is not necessary, since this resource is closed automatically.
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::addTm()
     */
    public function addTm(array $fileinfo = null,array $params=null) {
        // TODO
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::addAdditionalTm()
     */
    public function addAdditionalTm(array $fileinfo = null,array $params=null){
        // TODO
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::getValidFiletypes()
     */
    public function getValidFiletypes() {
        return [
            'TM' => ['application/zip'],
            'TMX' => ['application/xml','text/xml'],
        ];
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::getValidFiletypeForExport()
     */
    public function getValidExportTypes() {
        return [
            'TM' => 'application/zip',
            'TMX' => 'application/xml',
        ];
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_FilebasedAbstract::getTm()
     */
    public function getTm($mime) {
        if($this->api->get($mime)) {
            return $this->api->getResult();
        }
        $this->throwBadGateway();
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_FilebasedAbstract::query()
     */
    public function query(editor_Models_Segment $segment) {
        return $this->queryNecTmApi($this->prepareDefaultQueryString($segment), true);
    }
    
    /***
     * Query the google cloud api for the search string
     * @param string $searchString
     * @param bool $reimportWhitespace optional, if true converts whitespace into translate5 capable internal tag
     * @return editor_Services_ServiceResult
     */
    protected function queryNecTmApi($searchString, $reimportWhitespace = false){
        if(empty($searchString)) {
            return $this->resultList;
        }
        
        $result = null;
        if($this->api->search($searchString,$this->getRfcLang($this->sourceLang),$this->getRfcLang($this->targetLang), $this->categories)){
            $result = $this->api->getResult();
        }
        
        $translation = $result->tu->target_text ?? "";
        if($reimportWhitespace) {
            $translation = $this->importWhitespaceFromTagLessQuery($translation);
        }
        
        $this->resultList->addResult($translation, $result->match, $this->getMetaData($result));
        return $this->resultList;
    }
    
    /**
     * replace additional tags from the TM to internal tags which are ignored in the frontend then
     * @param string $segment
     * @param int $mapCount used as start number for the short tag numbering
     * @return string
     */
    protected function replaceAdditionalTags($segment, $mapCount) {
        // TODO
    }

    /**
     * Checks NEC result on valid segments: <it> ,<ph>,<bpt> and <ept> are invalid since they can not handled by the replaceAdditionalTags method
     * @param string $segmentContent
     */
    protected function validateInternalTags($result, editor_Models_Segment $seg) {
        // TODO
    }
    
    /***
     * Replace the invalid tags with empty content
     * 
     * @param string $content
     * @return string
     */
    protected function replaceInvalidTags($content){
        // TODO
    }
    
    /**
     * Helper function to get the metadata which should be shown in the GUI out of a single result
     * @param stdClass $found
     * @return stdClass
     */
    protected function getMetaData($found) {
        $nameToShow = [
            "file_name",
            "mt",
            "update_date",
            "username",
            "tag"
        ];
        $result = [];
        $tags = [];
        foreach($nameToShow as $name) {
            if(property_exists($found, $name)) {
                $item = new stdClass();
                $item->name = $name;
                $item->value = $found->{$name};
                if($name == 'update_date') {
                    $item->value = date('Y-m-d H:i:s T', strtotime($item->value));
                }
                if($name == 'tag') {
                    $tagIds = $item->value;
                    foreach ($tagIds as $tagId) {
                        $this->categoriesModel->loadByOriginalCategoryId($tagId);
                        $label = $this->categoriesModel->getLabel();
                        $type = $this->categoriesModel->getSpecificData('type');
                        $tags[] = $label.' ('.$type.')';
                    }
                    $item->value = $tags;
                }
                $result[] = $item;
            }
        }
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_FilebasedAbstract::search()
     */
    public function search(string $searchString, $field = 'source', $offset = null) {
        $results = null;
        if($this->api->concordanceSearch($searchString, $field, $this->getRfcLang($this->sourceLang),$this->getRfcLang($this->targetLang), $this->categories)){
            $results = $this->api->getResult();
        }
        
        // NEC-TM's API does not allow for offsets/paging in the search.
        if(empty($results)){
            return $this->resultList;
        }
        
        foreach($results as $result) {
            $this->resultList->addResult($this->highlight($searchString, strip_tags($result['target']), $field == 'target'));
            $this->resultList->setSource($this->highlight($searchString, strip_tags($result['source']), $field == 'source'));
        }
        return $this->resultList;
    }
    
    /***
     * Search the resource for available translation. Where the source text is in resource source language and the received results
     * are in the resource target language
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::translate()
     */
    public function translate(string $searchString){
        // TODO
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_FilebasedAbstract::delete()
     */
    public function delete() {
        // TODO
    }
    
    /**
     * Throws a ZfExtended_BadGateway exception containing the underlying errors
     * @throws ZfExtended_BadGateway
     */
    protected function throwBadGateway() {
        // TODO
    }
    
    /**
     * In difference to $this->throwBadGateway this method generates an 400 error 
     *   which shows additional error information in the frontend
     *   
     * @param string $logMsg
     */
    protected function handleNecTmError($logMsg) {
        // TODO
    }
    
    /**
     * Replaces not allowed characters with "_" in memory names
     * @param string $name
     * @return string
     */
    protected function filterName($name){
        // TODO
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::getStatus()
     */
    public function getStatus(& $moreInfo){
        // TODO
    }
    
    /***
     * Calculate the new matchrate value.
     * Check if the current match is of type context-match or exact-exact match
     * 
     * @param int $matchRate
     * @param array $metaData
     * @param editor_Models_Segment $segment
     * @param string $filename
     * 
     * @return integer
     */
    protected function calculateMatchRate($matchRate,$metaData,$segment,$filename){
        // TODO
    }
    
    /***
     * Download and save the existing tm with "fuzzy" name. The new fuzzy connector will be freturned.
     * The fuzzy languageResource name format is: oldname+Fuzzy-Analysis
     * @param int $analysisId
     * @throws ZfExtended_NotFoundException
     * @return editor_Services_Connector_Abstract
     */
    public function initForFuzzyAnalysis($analysisId) {
        // TODO
    }
    
    /***
     * Get the result list where the >=100 matches with the same target are grouped as 1 match.
     * @return editor_Services_ServiceResult|number
     */
    public function getResultListGrouped() {
        // TODO
    }
    
    /***
     * Reduce the given matchrate to given percent.
     * It is used when unsupported tags are found in the response result, and those tags are removed.
     * @param integer $matchrate
     * @param integer $reducePercent
     * @return number
     */
    protected function reduceMatchrate($matchrate,$reducePercent) {
        // TODO
    }
}