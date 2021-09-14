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
 * @method integer getId() getId()
 * @method void setId() setId(int $id)
 * @method integer getSourceLang() getSourceLang()
 * @method void setSourceLang() setSourceLang(int $id)
 * @method string getSourceLangCode() getSourceLangCode()
 * @method void setSourceLangCode() setSourceLangCode(string $lang)
 * @method integer getTargetLang() getTargetLang()
 * @method void setTargetLang() setTargetLang(int $id)
 * @method string getTargetLangCode() getTargetLangCode()
 * @method void setTargetLangCode() setTargetLangCode(string $lang)
 * @method integer getLanguageResourceId getLanguageResourceId()
 * @method void setLanguageResourceId() setLanguageResourceId(int $languageResourceId)
 *
 */
class editor_Models_LanguageResources_Languages extends ZfExtended_Models_Entity_Abstract {
    
    protected $dbInstanceClass = 'editor_Models_Db_LanguageResources_Languages';
    protected $validatorInstanceClass = 'editor_Models_Validator_LanguageResources_Languages';
    
    /***
     * Save the languages with Rfc5646 as language code for the resource id
     * @param int $source
     * @param int $target
     * @param int $languageResourceId
     */
    public function saveLanguagesWithRfcCode($source,$target,$languageResourceId){

        if($source){
            $sourceLang = ZfExtended_Factory::get('editor_Models_Languages');
            /* @var $sourceLang editor_Models_Languages */
            $sourceLang->load($source);
            $this->setSourceLang($sourceLang->getId());
            $this->setSourceLangCode($sourceLang->getRfc5646());
        }
        
        if($target){
            $targetLang = ZfExtended_Factory::get('editor_Models_Languages');
            /* @var $targetLang editor_Models_Languages */
            $targetLang->load($target);
            $this->setTargetLang($targetLang->getId());
            $this->setTargetLangCode($targetLang->getRfc5646());
        }

        //when both lanugages are nod defined do not save db entry
        if(!$source && !$target){
            return;
        }
        
        $this->setLanguageResourceId($languageResourceId);
        $this->save();
    }
    
    /***
     * @param int $languageResourceId
     * @return array
     */
    public function loadByLanguageResourceId($languageResourceId=null){
        $s=$this->db->select();
        if($languageResourceId){
            $s->where('languageResourceId=?',$languageResourceId);
        }
        return $this->db->fetchAll($s)->toArray();
    }
    
    /***
     * Load assocs by source language ids
     * @param array $sourceLangs
     * @return array
     */
    public function loadBySourceLangIds($sourceLangs=array()) {
        return $this->loadByFieldAndValue('sourceLang', $sourceLangs);
    }
    
    /***
     * Load assocs by target language ids
     * @param array $targetLangs
     * @return array
     */
    public function loadByTargetLangIds($targetLangs=array()) {
        return $this->loadByFieldAndValue('targetLang', $targetLangs);
    }
    
    /***
     * Load assocs by given assoc field and values
     * @param string $field
     * @param array $value
     * @return array
     */
    public function loadByFieldAndValue($field,array $value){
        $s=$this->db->select()
        ->where($field.' IN(?)',$value);
        return $this->db->fetchAll($s)->toArray();
    }
    
    /**
     * Check if language for $field exist for the $languageResourceId
     * @param array $language array of language ids or codes (depending on $field)
     * @param string $field : sourceLang, targetLang, sourceLangCode, targetLangCode
     * @param int $languageResourceId
     * @return boolean
     */
    public function isInCollection(array $languages, string $field, $languageResourceId): bool{
        $field = $this->db->getAdapter()->quoteIdentifier($field);
        $s = $this->db->select()
            ->where($field.' in (?)', $languages)
            ->where('languageResourceId = ?', $languageResourceId);

        return !empty($this->db->fetchAll($s)->toArray());
    }
    
    /***
     * @return array[]
     */
    public function loadResourceIdsGrouped() {
        $langs=$this->loadByLanguageResourceId();
        $retval=[];
        foreach ($langs as $lang){
            if(!isset($retval[$lang['languageResourceId']])){
                $retval[$lang['languageResourceId']]['sourceLang']=[];
                $retval[$lang['languageResourceId']]['targetLang']=[];
            }
            
            if(!empty($lang['sourceLang']) && !in_array($lang['sourceLang'], $retval[$lang['languageResourceId']]['sourceLang'])){
                array_push($retval[$lang['languageResourceId']]['sourceLang'],$lang['sourceLang']);
            }
            
            if(!empty($lang['targetLang']) && !in_array($lang['targetLang'], $retval[$lang['languageResourceId']]['targetLang'])){
                array_push($retval[$lang['languageResourceId']]['targetLang'],$lang['targetLang']);
            }
        }
        return $retval;
    }
    
    /***
     * Remove assoc by languageResourceId
     * @param array $languageResourceIds
     */
    public function removeByResourceId($languageResourceIds){
        $this->db->delete(array('languageResourceId IN(?)' => $languageResourceIds));
    }
    
    /**
     * Which combinations of sources and targets are available for all languageResources the current user can use?
     * @return object
     */
    public function getLanguageCombinationsForLoggedUser() {
        // TODO: use this instaed of getLocalesAccordingToReference() in Instanttranslate.js
        $targetsForSources = [];
        $sourcesForTargets = [];
        $addTargetsToSources = function($sources,$targets) use (&$targetsForSources) {
            foreach ($sources as $source){
                if(!array_key_exists($source, $targetsForSources)) {
                    $targetsForSources[$source] = [];
                }
                foreach ($targets as $target){
                    if (!in_array($target, $targetsForSources[$source]) && $target != $source) {
                        array_push($targetsForSources[$source], $target);
                    }
                }
            }
        };
        $addSourcesToTargets = function($sources,$targets) use (&$sourcesForTargets) {
            foreach ($targets as $target){
                if(!array_key_exists($target, $sourcesForTargets)) {
                    $sourcesForTargets[$target] = [];
                }
                foreach ($sources as $source){
                    if (!in_array($source, $sourcesForTargets[$target]) && $source != $target) {
                        array_push($sourcesForTargets[$target], $source);
                    }
                }
            }
        };
        // how to handle 'de-DE' vs. 'de'
        $config = Zend_Registry::get('config');
        $showSublanguages = $config->runtimeOptions->InstantTranslate->showSubLanguages;
        $checkSubLanguage = function(&$locale) use ($showSublanguages) {
            if (!$showSublanguages) {
                $localeParts = explode('-',$locale);
                $locale = $localeParts[0];
            }
        };
        
        $languageResources = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /* @var $languageResources editor_Models_LanguageResources_LanguageResource */
        $allAvailableLanguageResources = $languageResources->getAllMergedByAssoc();
        
        foreach ($allAvailableLanguageResources as $languageResource){
            $sources = $languageResource['source'];
            $targets = $languageResource['target'];
            array_walk($sources, $checkSubLanguage);
            array_walk($targets, $checkSubLanguage);
            array_unique($sources);
            array_unique($targets);
            $addTargetsToSources($sources,$targets);
            $addSourcesToTargets($sources,$targets);
        }
        
        //sort alphabetically
        if(!empty($targetsForSources)){
            ksort($targetsForSources);
            foreach ($targetsForSources as &$single){
                sort($single);
            }
        }
        //sort alphabetically
        if(!empty($sourcesForTargets)){
            ksort($sourcesForTargets);
            foreach ($sourcesForTargets as &$single){
                sort($single);
            }
        }
        return (object) [
            'targetsForSources' => $targetsForSources,
            'sourcesForTargets' => $sourcesForTargets,
        ];
    }
    
    /**
     * Get all available target locales for a source locale.
     * The locales are based on available mt language resources.
     *
     * @returns {Array}
     */
    public function getTargetsForSources() {
        $localesAvailable = [];
        $engineModel=ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /* @var $engineModel editor_Models_LanguageResources_LanguageResource */
        
        //load all available mt resources for the customers of a user
        $allLanguageResources= $engineModel->getAllMergedByAssoc(true,editor_Models_Segment_MatchRateType::TYPE_MT);
        $showSubLanguages = Zend_Registry::get('config')->runtimeOptions->InstantTranslate->showSubLanguages ?? true;
        foreach($allLanguageResources as $languageResourceToCheck) {
            
            //foreach sources, find the available targets
            foreach ($languageResourceToCheck['source'] as $source){
                if(!isset($localesAvailable[$source])){
                    $localesAvailable[$source]=[];
                }
                foreach($languageResourceToCheck['target'] as $target){
                    $useLanguage=strpos($target, '-') === false || $showSubLanguages;
                    if(!in_array($target,$localesAvailable[$source]) && $target!=$source && $useLanguage){
                        $localesAvailable[$source][]=$target;
                    }
                }
            }
        }
        return $localesAvailable;
    }
}

