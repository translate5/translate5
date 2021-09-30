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

class editor_Services_LucyLT_Resource extends editor_Models_LanguageResources_Resource {
    
    /**
     * This array maps our internal rfc5646 language keys to the language keys defined by lucy
     * 
     * When adding new languages: 
     * Currently only "de" language keys are supported as index key, de-DE will not work! 
     * 
     * @var array
     */
    protected $languageMap = array(
        'de' => 'GERMAN',
        'en' => 'ENGLISH',
    );
    
    /**
     * @var string
     */
    protected $credentials;
    
    public function __construct(string $id, string $name, string $url, string $credentials) {
        parent::__construct($id, $name, $url);
        $this->credentials = $credentials;
        $this->filebased = false; //forced to be no filebased
        $this->searchable = false; //forced to be non searchable
        $this->writable = false; //forced to be non writeable
        $this->analysable=false;//is used by match analysis
        $this->type = editor_Models_Segment_MatchRateType::TYPE_MT;
    }
    
    /**
     * returns the credentials of this configured resource
     * @return string
     */
    public function getCredentials() {
        return $this->credentials;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Models_LanguageResources_Resource::hasSourceLang()
     */
    public function hasSourceLang(editor_Models_Languages $sourceLang) {
        return $this->hasLanguage($sourceLang->getRfc5646());
    }

    /**
     * {@inheritDoc}
     * @see editor_Models_LanguageResources_Resource::hasTargetLang()
     */
    public function hasTargetLang(editor_Models_Languages $targetLang) {
        return $this->hasLanguage($targetLang->getRfc5646());
    }
    
    /**
     * checks if the given language key (RFC5646) is listed in the internal defined languageMap of Lucy Languages
     * @param string $langKey as defined in RFC5646
     * @return boolean
     */
    protected function hasLanguage($langKey) {
        return array_key_exists($this->getRfc5646($langKey), $this->languageMap);
    }
    
    /**
     * returns the first part of the Rfc5646 language name, 
     *  since internally in the Lucy Connector we currently 
     *  can not deal with de-DE and use therefore always de
     *  
     * @param string $langKey as defined in RFC5646
     * @return boolean
     */
    protected function getRfc5646(string $langKey) {
        $key = explode('-', $langKey);
        $key = reset($key);
        return strtolower($key);
    }
    
    /**
     * returns the Lucy Language representation for the given language, null if the language is not defined
     * @param string $langKey as defined in RFC5646
     * @return string|NULL
     */
    public function getMappedLanguage($langKey) {
        if($this->hasLanguage($langKey)){
            return $this->languageMap[$this->getRfc5646($langKey)];
        }
        return null;
    }
}