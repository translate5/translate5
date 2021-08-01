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

class editor_Plugins_PangeaMt_Resource extends editor_Models_LanguageResources_Resource {
    
    /**
     * All supported source-languages (language-codes) of the PangeaMT-API.
     * PangeaMT-Engines can also handle "auto" for the source.
     * @var array
     */
    protected $supportedSourceLangs;
    /**
     * All supported target-languages (language-codes) of the PangeaMT-API.
     * @var array
     */
    protected $supportedTargetLangs;
    
    public function __construct(string $id, string $name, string $url) {
        parent::__construct($id, $name, $url);
        $this->filebased = false;  //forced to be no filebased
        $this->searchable = false; //forced to be non searchable (concordance search)
        $this->writable = false;   //forced to be non writeable
        $this->analysable = true;  //is used by match analysis
        $this->type = editor_Models_Segment_MatchRateType::TYPE_MT;
    }
    
    /***
     * {@inheritDoc}
     * @see editor_Models_LanguageResources_Resource::hasSourceLang()
     */
    public function hasSourceLang(editor_Models_Languages $sourceLang) {
        if (!isset($this->supportedSourceLangs)) {
            $this->setSupportedLanguages();
        }
        if (in_array('auto', $this->supportedSourceLangs)) {
            return true;
        }
        return in_array($this->getRfc5646($sourceLang->getRfc5646()), $this->supportedSourceLangs);
    }
    
    /***
     * {@inheritDoc}
     * @see editor_Models_LanguageResources_Resource::hasTargetLang()
     */
    public function hasTargetLang(editor_Models_Languages $targetLang) {
        if (!isset($this->supportedTargetLangs)) {
            $this->setSupportedLanguages();
        }
        return in_array($this->getRfc5646($targetLang->getRfc5646()), $this->supportedTargetLangs);
    }
    
    /**
     * Get the languages that the PangeaMT-API supports and store their language-codes.
     */
    protected function setSupportedLanguages() {
        $api = ZfExtended_Factory::get('editor_Plugins_PangeaMt_HttpApi');
        /* @var $api editor_Plugins_PangeaMt_HttpApi */
        $languages = $api->getLanguages();
        if(empty($languages)){
            return;
        }
        
        $this->supportedSourceLangs = $languages[editor_Services_Connector_Abstract::SOURCE_LANGUAGES_KEY];
        $this->supportedTargetLangs = $languages[editor_Services_Connector_Abstract::TARGET_LANGUAGES_KEY];
    }
    
    /**
     * Returns the first part of the Rfc5646 language name,
     * since PangeaMT only knows "de", but not "de-DE" etc.
     * @param string $langKey as defined in RFC5646
     * @return string
     */
    protected function getRfc5646(string $langKey) {
        $key = explode('-', $langKey);
        return reset($key);
    }
}