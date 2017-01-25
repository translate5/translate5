<?php
/*
 START LICENSE AND COPYRIGHT

This file is part of translate5

Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
as published by the Free Software Foundation and appearing in the file agpl3-license.txt
included in the packaging of this file.  Please review the following information
to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
http://www.gnu.org/licenses/agpl.html

There is a plugin exception available for use with this release of translate5 for
open source applications that are distributed under a license other than AGPL:
Please see Open Source License Exception for Development of Plugins for translate5
http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
folder of translate5.

@copyright  Marc Mittag, MittagQI - Quality Informatics
@author     MittagQI - Quality Informatics
@license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

class editor_Plugins_MatchResource_Services_LucyLT_Resource extends editor_Plugins_MatchResource_Models_Resource {
    
    /**
     * FIXME to be extended!
     * This array maps our internal rfc5646 language keys to the language keys defined by lucy
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
     * @see editor_Plugins_MatchResource_Models_Resource::hasSourceLang()
     */
    public function hasSourceLang(editor_Models_Languages $sourceLang) {
        return $this->hasLanguage($sourceLang);
    }

    /**
     * {@inheritDoc}
     * @see editor_Plugins_MatchResource_Models_Resource::hasTargetLang()
     */
    public function hasTargetLang(editor_Models_Languages $targetLang) {
        return $this->hasLanguage($targetLang);
    }
    
    /**
     * checks if the given language is listed in the internal defined languageMap of Lucy Languages
     * @param editor_Models_Languages $lang
     * @return boolean
     */
    protected function hasLanguage(editor_Models_Languages $lang) {
        return array_key_exists($this->getRfc5646($lang), $this->languageMap);
    }
    
    /**
     * checks if the given language is listed in the internal defined languageMap of Lucy Languages
     * @param editor_Models_Languages $lang
     * @return boolean
     */
    protected function getRfc5646(editor_Models_Languages $lang) {
        $key = $lang->getRfc5646();
        $key = explode('-', $key);
        $key = reset($key);
        return strtolower($key);
    }
    
    /**
     * returns the Lucy Language representation for the given language, null if the language is not defined
     * @param editor_Models_Languages $lang
     * @return string|NULL
     */
    public function getMappedLanguage(editor_Models_Languages $lang) {
        if($this->hasLanguage($lang)){
            return $this->languageMap[$this->getRfc5646($lang)];
        }
        return null;
    }
}