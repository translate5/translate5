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

class editor_Services_Microsoft_Resource extends editor_Models_LanguageResources_Resource {

    /**
     * Microsoft Azure location configuration (for example northeurope etc.pp.)
     * @var string
     */
    protected $location;
    
    protected $validLanguages = null;
    
    public function __construct(string $id, string $name) {
        $this->id = $id;
        $this->name = $name;
        $this->filebased = false; //forced to be no filebased
        $this->searchable = false; //forced to be non searchable (concordance search)
        $this->writable = false; //forced to be non writeable
        $this->analysable = true;//is used by match analysis
        $this->type = editor_Models_Segment_MatchRateType::TYPE_MT;
        
        $config = Zend_Registry::get('config');
        
        $this->authKey = $config->runtimeOptions->LanguageResources->microsoft->apiKey ?? null ;
        $this->url = $config->runtimeOptions->LanguageResources->microsoft->apiUrl ?? null ;
        $this->location = $config->runtimeOptions->LanguageResources->microsoft->apiLocation ?? null ;
    }
    
    /**
     * returns the configured azure location
     */
    public function getLocation(): string {
        return (string) $this->location;
    }
    
    /**
     * Check if the valid resource language is valid for the api.
     * {@inheritDoc}
     * @see editor_Models_LanguageResources_Resource::hasSourceLang()
     */
    public function hasSourceLang(editor_Models_Languages $sourceLang) {
        return $this->isValidLanguage($sourceLang->getRfc5646());
    }
    
    /**
     * Check if the valid resource language is valid for the api
     * {@inheritDoc}
     * @see editor_Models_LanguageResources_Resource::hasTargetLang()
     */
    public function hasTargetLang(editor_Models_Languages $targetLang) {
        return $this->isValidLanguage($targetLang->getRfc5646());
    }
    
    /**
     * checks if the given language RFC5646 value is valid for this resource
     * @param string $rfc5646
     * @return boolean
     */
    protected function isValidLanguage(string $rfc5646): bool {
        if(is_null($this->validLanguages)) {
            $api = ZfExtended_Factory::get('editor_Services_Microsoft_HttpApi',[$this]);
            /* @var $api editor_Services_Microsoft_HttpApi */
            $this->validLanguages = $api->getLanguages() ?? [];
        }
        return in_array($rfc5646, $this->validLanguages);
    }
}