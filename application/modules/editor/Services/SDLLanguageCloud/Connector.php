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
 */
class editor_Services_SDLLanguageCloud_Connector extends editor_Services_Connector_Abstract {

    /**
     * @var editor_Services_SDLLanguageCloud_HttpApi
     */
    protected $api;
    
    public function __construct() {
        parent::__construct();
        $this->api = ZfExtended_Factory::get('editor_Services_SDLLanguageCloud_HttpApi');
        $this->defaultMatchRate = $this->config->runtimeOptions->LanguageResources->sdllanguagecloud->matchrate;
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::query()
     */
    public function query(editor_Models_Segment $segment) {
        $qs = $this->getQueryStringAndSetAsDefault($segment);
        $translation = $this->querySdlApi($this->tagHandler->prepareQuery($qs));
        $translation = $this->tagHandler->restoreInResult($translation);
        $this->resultList->addResult($translation, $this->defaultMatchRate);
        return $this->resultList;
    }
    
    /***
     * Search the resource for available translation. Where the source text is in resource source language and the received results
     * are in the resource target language
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::translate()
     */
    public function translate(string $searchString){
        $this->resultList->addResult($this->querySdlApi($searchString), $this->defaultMatchRate);
        return $this->resultList;
    }
    
    
    /***
     * Query the sdl cloud api and get the available results as editor_Services_ServiceResult
     * @param string $searchString
     * @return string
     */
    protected function querySdlApi($searchString): string{
        if(empty($searchString) && $searchString !== "0") {
            return $this->resultList;
        }
        
        $result=null;
        $params=[
            'domainCode'=>$this->languageResource->getSpecificData('domainCode'),
            'text'=>$searchString,
            'from'=>$this->languageResource->getSourceLangCode(),
            'to'=>$this->languageResource->getTargetLangCode(),
        ];
        if($this->api->search($params)){
            $result=$this->api->getResult();
        }
        return $result->translation ?? "";
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::getStatus()
     */
    public function getStatus(editor_Models_LanguageResources_Resource $resource){
        $this->lastStatusInfo = '';
        $this->api->getStatus();
        
        if($this->api->getResponse()->getStatus()==200) {
            return self::STATUS_AVAILABLE;
        }
        
        //a 404 response from the status call means:
        // - OpenTM2 is online
        // - the requested TM is currently not loaded, so there is no info about the existence
        // - So we display the STATUS_NOT_LOADED instead
        if($this->api->getResponse()->getStatus() == 404) {
            $this->lastStatusInfo = 'Die Ressource ist generell verfügbar, stellt aber keine Informationen über das angefragte TM bereit, da dies nicht geladen ist.';
            return self::STATUS_NOT_LOADED;
        }
        
        $this->lastStatusInfo = join("<br/>\n", array_map(function($item) {
            return $item->type.': '.$item->error;
        }, $this->api->getErrors()));
            
        return self::STATUS_NOCONNECTION;
    }
}
