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

/**
 * Controller for the Plugin SpellCheck
 */
class editor_Plugins_SpellCheck_SpellCheckQueryController extends ZfExtended_RestController {
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::init()
     *
     * copied the init method, parent can not be used, since no real entity is used here
     */
    public function init() {
        $this->initRestControllerSpecific();
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction(){
        throw new ZfExtended_BadMethodCallException(__CLASS__);
    }
    
    /**
     * Get the languages that are supported by the tool we use (currently: LanguageTool).
     */
    public function languagesAction(){
        if($this->getParam('targetLangCode') && $this->getParam('targetLangCode')!=""){
            $targetLangCode = $this->getParam('targetLangCode');
        }
        if (!$targetLangCode){
            $this->view->rows = false;
            return;
        }
        $connector = ZfExtended_Factory::get('editor_Plugins_SpellCheck_LanguageTool_Connector');
        /* @var $connector editor_Plugins_SpellCheck_LanguageTool_Connector */
        $supportedLanguages= $connector->getLanguages();
        $this->view->rows = $this->getSupportedLanguage($supportedLanguages, $targetLangCode);
    }
    
    /**
     * Is the language supported by the LanguageTool?
     * Examples:
     * |----------------------------------------------------------------------------|
     * |---from Editor-----|--see LEK_languages---|--------see LanguageTool---------|
     * |----------------------------------------------------------------------------|
     * | targetLang (=rfc) |  ISO   | sublanguage | longcode | needed result for LT |
     * |----------------------------------------------------------------------------|
     * |      de           |   de   |   de-DE     |   de-DE  |       de-DE          |
     * |     de-DE         |   de   |   de-DE     |   de-DE  |       de-DE          |
     * |     de-AT         |   de   |   de-AT     |   de-AT  |       de-AT          |
     * |      fr           |   fr   |   fr-FR     |     fr   |         fr           |
     * |     fr-FR         |   fr   |   fr-FR     |     fr   |         fr           |
     * |      he           |   il   |   he-IL     |     -    |       false          |
     * |----------------------------------------------------------------------------|
     * @param array $supportedLanguages
     * @param string $targetLangCode
     * @return object|false
     */
    private function getSupportedLanguage($supportedLanguages, $targetLangCode){
        $languagesModel=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $languagesModel editor_Models_Languages */
        $sublanguage = $languagesModel->getSublanguageByRfc5646($targetLangCode);
        $iso = $languagesModel->getIso3166Part1alpha2ByRfc5646($targetLangCode);
	//next 5 lines workaround for TRANSLATE-1630 "Workaround for east asian problems with spellchecker"
	$explode = explode('-',$targetLangCode);	
	$mainLang = $explode[0];
	if($mainLang == 'ko' || $mainLang == 'zh' || $mainLang == 'ja'){
	    return false;
	}
        foreach ($supportedLanguages as $lang) {
            if ($lang->longCode == $sublanguage) {      // priority: longCode (e.g. "de-DE") is the default sublanguage ("de-DE") of the targetLangCode ("de")
                return $lang;
            }
        }
        foreach ($supportedLanguages as $lang) {
            if ($lang->longCode == $iso) {              // fallback: longCode (e.g. "fr") is the ISO ("fr") of the targetLangCode ("fr")
                return $lang;
            }
        }
        return false;
    }
    
    /**
     * The matches that our tool finds (currently: LanguageTool).
     */
    public function matchesAction(){
        $text = $this->getParam('text','');
        $language= $this->getParam('language','');
        if (empty($text) || empty($language) ) {
            error_log("NO text or language.");
            $this->view->rows = "[]";
            return;
        }
        
        $connector = ZfExtended_Factory::get('editor_Plugins_SpellCheck_LanguageTool_Connector');
        $this->view->rows = $connector->getMatches($text,$language);
    }
    
    public function getAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->get');
    }
    
    public function putAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->put');
    }
    
    public function deleteAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->delete');
    }
    
    public function postAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->post');
    }
}
