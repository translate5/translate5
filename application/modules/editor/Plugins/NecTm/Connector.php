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
    
    protected $sourceLangForNecTm;
    protected $targetLangForNecTm;
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::connectTo()
     */
    public function connectTo(editor_Models_LanguageResources_LanguageResource $languageResource, $sourceLang, $targetLang) {
        parent::connectTo($languageResource, $sourceLang, $targetLang);
        $this->api = ZfExtended_Factory::get('editor_Plugins_NecTm_HttpApi');
        $this->xmlparser= ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        // The NEC-TM-Api uses "Tags"; we handle them via categories:
        $this->setCategories($languageResource);
        $this->categoriesModel = ZfExtended_Factory::get('editor_Models_Categories');
        // For exporting files, the NEC-TM-Api needs to know the source and target language. Usually they are given when
        // calling the methods from the segment, but nor for getTm() - hence we store them right away:
        $this->setLanguagesForNecTm($languageResource);
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
     * Store the source- and target-Language from the LanguageResource as needed for the NEC-TM-Api.
     * @param editor_Models_LanguageResources_LanguageResource $languageResource
     */
    protected function setLanguagesForNecTm($languageResource) {
        $this->sourceLangForNecTm = $this->getLangCodeForNecTm($languageResource->sourceLangRfc5646);
        $this->targetLangForNecTm = $this->getLangCodeForNecTm($languageResource->targetLangRfc5646);
    }
    
    /**
     * NEC-TM-Api: "Both slang and tlang parameters are expected to be ISO639-1 codes without the locale."
     * @param string $langRfc5646 (= e.g. 'en-US', 'en')
     * @return string (= the generic language, e.g. 'en')
     */
    protected function getLangCodeForNecTm($langRfc5646) {
        if (!is_string($langRfc5646)) { // e.g. Array[0] when we just delete a NEC-TM-LanguageResource
            return null;
        }
        return explode("-", $langRfc5646)[0] ?? $langRfc5646;
    }
    
    /**
     * Return the rfc5646-Language for the given langId.
     * @param integer $langId (= the id in our DB)
     * @return string (= rfc5646)
     */
    protected function getRfcLang($langId) {
        if (!$this->lngs) {
            // "lazy" load: all languages
            $langModel = ZfExtended_Factory::get('editor_Models_Languages');
            /* @var $langModel editor_Models_Languages */
            $this->lngs = $langModel->loadAllKeyValueCustom('id','rfc5646');
        }
        return $this->lngs[$langId];
    }
    
    /**
     * Is everything ok with the languages that are set already and given now?
     * @param integer $sourceLangId
     * @param integer $targetLangId
     */
    protected function validateLanguages($sourceLangId, $targetLangId) {
        $sourceLangRfc = $this->getRfcLang($sourceLangId);
        $targetLangRfc = $this->getRfcLang($targetLangId);
        if ($this->getLangCodeForNecTm($sourceLangRfc) !== $this->sourceLangForNecTm
            || $this->getLangCodeForNecTm($targetLangRfc) !== $this->targetLangForNecTm) {
                // If the languages we already stored for the Connector from the LanguageResource differ
                // from the languages given by the segment now, we really have a problem.
            error_log('validateLanguages: NOT OK'); // TODO: throw error
        }
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
    public function addTm(array $fileinfo = null, array $params=null) {
        $validFileTypes = $this->getValidFiletypes();
        if (empty($validFileTypes['TMX'])) {
            throw new ZfExtended_NotFoundException('NEC-TM: Cannot addTm for TMX-file; valid file types are missing.');
        }
        $noFile = empty($fileinfo);
        $tmxUpload = !$noFile && in_array($fileinfo['type'], $validFileTypes['TMX']) && preg_match('/\.tmx$/', $fileinfo['name']);
        if ($tmxUpload) {
            if ($this->api->importTMXfile($fileinfo['tmp_name'], $this->sourceLangForNecTm, $this->targetLangForNecTm, $this->categories)){
                return true;
            }
            $this->handleNecTmError('LanguageResources - could not add TMX to NEC-TM'." LanguageResource: \n");
            return false;
        }
        // NEC-TM-Api does not need a file; LanguageResource works anyway.
        return true;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::addAdditionalTm()
     */
    public function addAdditionalTm(array $fileinfo = null,array $params=null){
        return $this->addTm($fileinfo, $params);
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::getValidFiletypes()
     */
    public function getValidFiletypes() {
        return [
            'TMX' => ['application/xml','text/xml'],
        ];
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::getValidFiletypeForExport()
     */
    public function getValidExportTypes() {
        return [
            'TMX' => 'application/xml',
        ];
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_FilebasedAbstract::getTm()
     */
    public function getTm($mime) {
        $languageResource = $this->getLanguageResource();
        if($this->api->get($mime, $this->sourceLangForNecTm, $this->targetLangForNecTm, $this->categories)) {
            return $this->api->getResult();
        }
        $this->throwBadGateway();
        return false;
        // TODO: If we return false, the editor_LanguageresourceinstanceController continues anyway and shows details as xml in a new tab.
        // (For testing you can set the $this->api::JOB_STATUS_TIMETOWAIT to 2 or so.)
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::update()
     */
    public function update(editor_Models_Segment $segment) {
        $messages = Zend_Registry::get('rest_messages');
        /* @var $messages ZfExtended_Models_Messages */
        $source = $this->prepareSegmentContent($this->getQueryString($segment));
        $target = $this->prepareSegmentContent($segment->getTargetEdit());
        $filename = $this->languageResource->getSpecificData('fileName');  //  (= if file was imported for LanguageResource on creation)
        if($this->api->addTMUnit($source, $target, $this->sourceLangForNecTm, $this->targetLangForNecTm, $this->categories, $filename)) {
            return;
        }
        
        $errors = $this->api->getErrors();
        //$messages = Zend_Registry::get('rest_messages');
        /* @var $messages ZfExtended_Models_Messages */
        
        $msg = 'Das Segment konnte nicht ins TM gespeichert werden! Bitte kontaktieren Sie Ihren Administrator! <br />Gemeldete Fehler:';
        $messages->addError($msg, 'core', null, $errors);
        $log = ZfExtended_Factory::get('ZfExtended_Log');
        /* @var $log ZfExtended_Log */
        $msg = 'LanguageResources - could not save segment to TM'." LanguageResource: \n";
        $data  = print_r($this->languageResource->getDataObject(),1);
        $data .= " \nSegment\n".print_r($segment->getDataObject(),1);
        $data .= " \nError\n".print_r($errors,1);
        $log->logError($msg, $data);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_FilebasedAbstract::query()
     */
    public function query(editor_Models_Segment $segment) {
        return $this->queryNecTmApi($this->prepareDefaultQueryString($segment), true);
    }
    
    /***
     * Query the NEC-TM-api for the search string
     * @param string $searchString
     * @param bool $reimportWhitespace optional, if true converts whitespace into translate5 capable internal tag
     * @return editor_Services_ServiceResult
     */
    protected function queryNecTmApi($searchString, $reimportWhitespace = false){
        if(empty($searchString)) {
            return $this->resultList;
        }
        $result = null;
        $this->validateLanguages($this->sourceLang, $this->targetLang);
        if($this->api->search($searchString, $this->sourceLangForNecTm, $this->targetLangForNecTm, $this->categories)){
            $result = $this->api->getResult();
        }
        
        if(empty($result)) {
            return $this->resultList;
        }
        
        $translation = $result->tu->target_text ?? "";
        if($reimportWhitespace) {
            $translation = $this->importWhitespaceFromTagLessQuery($translation);
        }
        
        // NEC-TM seems to try to insert internal tags "at the same place" in the found translation.
        // Example:
        // - "source_text": "translate5 <div class="open 672069643d22393222 internal-tag ownttip">ist Open Source</div>:"
        // - "target_text": "translate5 <div class="open 672069643d22393222 internal-tag ownttip">is Open Source</div>:"
        
        $this->resultList->addResult($translation, $result->match, $this->getMetaData($result));
        return $this->resultList;
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
                switch ($name) {
                    case 'file_name':
                        $item->value = $this->languageResource->getSpecificData('fileName');
                        break;
                    case 'tag':
                        $tagIds = $item->value;
                        foreach ($tagIds as $tagId) {
                            $this->categoriesModel->loadByOriginalCategoryId($tagId);
                            $label = $this->categoriesModel->getLabel();
                            $type = $this->categoriesModel->getSpecificData('type');
                            $tags[] = $label.' ('.$tagId.', '.$type.')';
                        }
                        $item->value = $tags;
                        break;
                    case 'update_date':
                        $item->value = date('Y-m-d H:i:s T', strtotime($item->value));
                        break;
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
        $this->validateLanguages($this->sourceLang, $this->targetLang);
        if($this->api->concordanceSearch($searchString, $field, $this->sourceLangForNecTm,$this->targetLangForNecTm, $this->categories)){
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
        // TODO: check for additional handling when implementing TRANSLATE-1252
        return $this->queryNecTmApi($searchString);
    }
    
    /**
     * Throws a ZfExtended_BadGateway exception containing the underlying errors
     * @throws ZfExtended_BadGateway
     */
    protected function throwBadGateway() {
        $e = new ZfExtended_BadGateway('Die angefragte NEC-TM Instanz meldete folgenden Fehler:');
        $e->setDomain('LanguageResources');
        $e->setErrors($this->api->getErrors());
        throw $e;
    }
    
    /**
     * In difference to $this->throwBadGateway this method generates an 400 error 
     *   which shows additional error information in the frontend
     *   
     * @param string $logMsg
     */
    protected function handleNecTmError($logMsg) {
        $errors = $this->api->getErrors();
        
        $messages = Zend_Registry::get('rest_messages');
        /* @var $messages ZfExtended_Models_Messages */
        $msg = 'Von NEC-TM gemeldeter Fehler';
        $messages->addError($msg, 'core', null, $errors);
        
        $log = ZfExtended_Factory::get('ZfExtended_Log');
        /* @var $log ZfExtended_Log */
        $data  = print_r($this->languageResource->getDataObject(),1);
        $data .= " \nError\n".print_r($errors,1);
        $log->logError($logMsg, $data);
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::getStatus()
     */
    public function getStatus(& $moreInfo){
        if($this->api->getStatus()){
            return self::STATUS_AVAILABLE;
        }
        return self::STATUS_NOCONNECTION;
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
        // TODO (context-matches not supported by NEC-TM-Api so far)
    }
    
    /***
     * 
     * THIS IS NOT IMPLEMENTED SO FAR.
     * Clone the existing tm (= NEC-TM: tags) with "fuzzy" name. The new fuzzy connector will be returned.
     * @param int $analysisId
     * @return editor_Plugins_NecTm_Connector
     */
    public function initForFuzzyAnalysis($analysisId) {
        return $this;
        // TODO (wait for NEC-TM-APi to delete tags WITH deleting their contents)
        
        $this->isInternalFuzzy = true;
        $fuzzyLanguageResource = clone $this->languageResource;
        /* @var $fuzzyLanguageResource editor_Models_LanguageResources_LanguageResource  */
        
        // - The fuzzyLanguageResource can use all the NEC-TM-tags that it has cloned. 
        // - For saving internal translations, we introduce an extra tag (our internal translations must save to this extra-tag only!).
        // - After analyzing, this extra tag and all its content must be removed from the NEC-TM.
        
        $fuzzyLanguageResourceName = $this->renderFuzzyLanguageResourceName($this->languageResource->getName(), $analysisId);
        $fuzzyLanguageResource->setName($fuzzyLanguageResourceName);
        $fuzzyLanguageResource->setId(null); // TODO: why do we do this?
        
        $fuzzyTagName = $this->renderFuzzyLanguageResourceName('translate5InternalFuzzyTag', $analysisId);
        // TODO: (1) create extra tag in NEC-TM (2) use this tag when saving new internal translations (3) afterwards: delete this tag and remove all its content
        $fuzzyLanguageResource->addSpecificData('internalFuzzyTag', $fuzzyTagName);
        
        $connector = ZfExtended_Factory::get(get_class($this));
        /* @var $connector editor_Services_Connector */
        $connector->connectTo($fuzzyLanguageResource,$this->languageResource->getSourceLang(),$this->languageResource->getTargetLang());
        $connector->isInternalFuzzy = true;
        return $connector;
    }
    
    /***
     * Get the result list where the >=100 matches with the same target are grouped as 1 match.
     * @return editor_Services_ServiceResult|number
     */
    public function getResultListGrouped() {
        // TODO (check if results can contain multiple 100%-matches)
    }
}