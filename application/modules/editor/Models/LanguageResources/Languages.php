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
 * @method string getId()
 * @method void setId(int $id)
 * @method string getSourceLang()
 * @method void setSourceLang(int $id)
 * @method string getSourceLangCode()
 * @method void setSourceLangCode(string $lang)
 * @method string getSourceLangName()
 * @method void setSourceLangName(string $langName)
 * @method string getTargetLang()
 * @method void setTargetLang(int $id)
 * @method string getTargetLangCode()
 * @method void setTargetLangCode(string $lang)
 * @method string getTargetLangName()
 * @method void setTargetLangName(string $langName)
 * @method string getLanguageResourceId()
 * @method void setLanguageResourceId(int $languageResourceId)
 *
 */
class editor_Models_LanguageResources_Languages extends ZfExtended_Models_Entity_Abstract {
    
    protected $dbInstanceClass = 'editor_Models_Db_LanguageResources_Languages';
    protected $validatorInstanceClass = 'editor_Models_Validator_LanguageResources_Languages';

    /**
     * Save the languages with Rfc5646 as language code for the resource id
     * @param int $source
     * @param int $target
     * @param int $languageResourceId
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function saveLanguagesWithRfcCode($source, $target, $languageResourceId){

        if($source){
            $sourceLang = ZfExtended_Factory::get(editor_Models_Languages::class);
            $sourceLang->load($source);
            $this->setSourceLang($sourceLang->getId());
            $this->setSourceLangCode($sourceLang->getRfc5646());
            $this->setSourceLangName($sourceLang->getLangName());
        }
        
        if($target){
            $targetLang = ZfExtended_Factory::get(editor_Models_Languages::class);
            $targetLang->load($target);
            $this->setTargetLang($targetLang->getId());
            $this->setTargetLangCode($targetLang->getRfc5646());
            $this->setTargetLangName($targetLang->getLangName());
        }

        //when both lanugages are not defined do not save db entry
        if(!$source && !$target){
            return;
        }
        
        $this->setLanguageResourceId($languageResourceId);
        $this->save();
    }
    
    /**
     * @param int $languageResourceId
     * @return array
     */
    public function loadByLanguageResourceId($languageResourceId = null){
        $s = $this->db->select();
        if($languageResourceId){
            $s->where('languageResourceId = ?',$languageResourceId);
        }
        return $this->db->fetchAll($s)->toArray();
    }

    /**
     * Load langauge pairs for given resource and languages
     * @param int $langageresource
     * @param array $sourceLang
     * @param array $targetLang
     * @return array
     */
    public function loadFilteredPairs(int $langageresource, array $sourceLang, array $targetLang): array
    {
        $s=$this->db->select()
            ->where('languageResourceId=?',$langageresource)
            ->where('sourceLang IN(?)',$sourceLang)
            ->where('targetLang IN(?)',$targetLang);

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
    public function loadResourceIdsGrouped($languageResourceId = null) {
        $langs=$this->loadByLanguageResourceId($languageResourceId);
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

