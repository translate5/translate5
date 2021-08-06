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
    
    use editor_Services_Connector_BatchTrait;
    
    /**
     * @var editor_Plugins_NecTm_HttpApi
     */
    protected $api;
    
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
     * Using Xliff based tag handler here
     * @var string
     */
    protected $tagHandlerClass = 'editor_Services_Connector_TagHandler_Xliff';
    
    /**
     * Just overwrite the class var hint here
     * @var editor_Services_Connector_TagHandler_Xliff
     */
    protected $tagHandler;
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::__construct()
     */
    public function __construct() {
        parent::__construct();
        $this->api = ZfExtended_Factory::get('editor_Plugins_NecTm_HttpApi');
        $this->batchQueryBuffer = 30;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::connectTo()
     */
    public function connectTo(editor_Models_LanguageResources_LanguageResource $languageResource, $sourceLang, $targetLang) {
        parent::connectTo($languageResource, $sourceLang, $targetLang);
        
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
        $this->sourceLangForNecTm = $this->getLangCodeForNecTm($languageResource->sourceLangCode);
        $this->targetLangForNecTm = $this->getLangCodeForNecTm($languageResource->targetLangCode);
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
                throw new editor_Plugins_NecTm_Exception('E1182', [
                    'sourceLangId' => $sourceLangId,
                    'targetLangId' => $targetLangId,
                    'sourceLangForNecTm' => $this->sourceLangForNecTm,
                    'targetLangForNecTm' => $this->targetLangForNecTm
                ]);
            }
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
            // NEC-TM will use the filename, but the name of the tmp file is useless:
            // - we get: LanguageResources2018-09-website-rewrite-examples.tmxsUh4yU
            // - we want: 2018-09-website-rewrite-examples.tmx
            // Thus, we keep the file unique, but by a unique directory:
            // old: /tmp/LanguageResources2018-09-website-rewrite-examples.tmxsUh4yU
            // new: /tmp/LanguageResourcessUh4yU/2018-09-website-rewrite-examples.tmx
            // TODO: use this procedure in general when importing files for LanguageResources?
            //      (= move it to handleUploadLanguageResourcesFile() in editor_LanguageresourceinstanceController)
            $uniqueDirectory = str_replace($fileinfo['name'], '', $fileinfo['tmp_name']);
            if (!is_dir($uniqueDirectory)) {
                mkdir($uniqueDirectory, 0777, true);
            }
            $newfilename = $uniqueDirectory.'/'.$fileinfo['name']; // TODO: make '/' safe for all systems
            rename($fileinfo['tmp_name'], $newfilename);
            if ($this->api->importTMXfile($newfilename, $this->sourceLangForNecTm, $this->targetLangForNecTm, $this->categories)){
                unlink($newfilename);
                rmdir($uniqueDirectory);
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
            'ZIP' => 'application/zip',
        ];
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_FilebasedAbstract::getTm()
     */
    public function getTm($mime) {
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
        // if categories are empty: NEC-TM-Api responds a 403 with {"message": "Tag is required option"}.
        $service = ZfExtended_Factory::get('editor_Plugins_NecTm_Service');
        /* @var $service editor_Plugins_NecTm_Service */
        $service->validateCategories(implode(',',$this->categories));
        $filename = $this->languageResource->getSpecificData('fileName');  //  (= if file was imported for LanguageResource on creation)
        //if no filename is specified for the language resource, use the current translate5 domain as filename
        if(empty($filename)){
            $config = Zend_Registry::get('config');
            $serverName = $config->runtimeOptions->server->name;
            //replace non-alpha-numeric characters
            $filename = preg_replace('/[^a-z0-9]+/', '_', strtolower($serverName)).'.tmx';
        }
        $source= $this->tagHandler->prepareQuery($this->getQueryString($segment));
        $target= $this->tagHandler->prepareQuery($segment->getTargetEdit());

        if($this->api->addTMUnit($source, $target, $this->sourceLangForNecTm, $this->targetLangForNecTm, $this->categories, $filename)) {
            return;
        }
        
        $errors = $this->api->getErrors();
        
        $messages = Zend_Registry::get('rest_messages');
        /* @var $messages ZfExtended_Models_Messages */
        $msg = 'Das Segment konnte nicht ins TM gespeichert werden! Bitte kontaktieren Sie Ihren Administrator! <br />Gemeldete Fehler:';
        $messages->addError($msg, 'plugin.nectm', null, $errors);
        
        throw new editor_Plugins_NecTm_Exception('E1183', [
            'LanguageResource' => print_r($this->languageResource->getDataObject(),1),
            'Segment' => print_r($segment->getDataObject(),1),
            'Error' => print_r($errors,1)
        ]);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_FilebasedAbstract::query()
     */
    public function query(editor_Models_Segment $segment) {
        $qs = $this->getQueryStringAndSetAsDefault($segment);
        
        $results = null;
        $this->validateLanguages($this->sourceLang, $this->targetLang);
        if($this->api->search($this->tagHandler->prepareQuery($qs), $this->sourceLangForNecTm, $this->targetLangForNecTm, $this->categories)){
            $results = $this->api->getResult();
            if(empty($results)) {
                return $this->resultList;
            }
            foreach($results as $result) {
                $source=$result->tu->source_text ?? "";
                $target = $result->tu->target_text ?? "";
                $this->resultList->addResult($this->tagHandler->restoreInResult($target), $result->match, $this->getMetaData($result));
                $this->resultList->setSource($this->tagHandler->restoreInResult($source));
            }
        }
        return $this->resultList;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_BatchTrait::batchSearch()
     */
    protected function batchSearch(array $queryStrings, string $sourceLang, string $targetLang): bool {
        $engineId = $this->languageResource->getSpecificData('engineId');
        return $this->api->searchBatch($queryStrings, $sourceLang, $targetLang, $engineId, $this->batchQueryBuffer);
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_BatchTrait::processBatchResult()
     */
    protected function processBatchResult($segmentResults) {
        $tmResults = $segmentResults->results ?? [];
        foreach ($tmResults as $tmRes){
            $source=$tmRes->tu->source_text ?? "";
            $target = $tmRes->tu->target_text ?? "";
            $this->resultList->addResult($this->tagHandler->restoreInResult($target), $tmRes->match, $this->getMetaData($tmRes));
            $this->resultList->setSource($this->tagHandler->restoreInResult($source));
        }
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
            $this->resultList->addResult($this->highlight($searchString, $this->tagHandler->restoreInResult($result['target']), $field == 'target'));
            $this->resultList->setSource($this->highlight($searchString, $this->tagHandler->restoreInResult($result['source']), $field == 'source'));
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
        if(empty($searchString)) {
            return $this->resultList;
        }
        $results = null;
        $this->validateLanguages($this->sourceLang, $this->targetLang);
        if($this->api->search($searchString, $this->sourceLangForNecTm, $this->targetLangForNecTm, $this->categories)){
            $results = $this->api->getResult();
            if(empty($results)) {
                return $this->resultList;
            }
            foreach($results as $result) {
                $source=$result->tu->source_text ?? "";
                $target = $result->tu->target_text ?? "";
                $this->resultList->addResult(strip_tags($target), $result->match, $this->getMetaData($result));
                $this->resultList->setSource(strip_tags($source));
            }
        }
        return $this->resultList;
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
        
        throw new editor_Plugins_NecTm_Exception('E1162', [
            'LanguageResource' => print_r($this->languageResource->getDataObject(),1),
            'Error' => print_r($errors,1)
        ]);
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::getStatus()
     */
    public function getStatus(editor_Models_LanguageResources_Resource $resource){
        if($this->api->getStatus()){
            return self::STATUS_AVAILABLE;
        }
        return self::STATUS_NOCONNECTION;
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
        $connector->setConfig($this->getConfig());
        $connector->isInternalFuzzy = true;
        return $connector;
    }
}