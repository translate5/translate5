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
 * TODO: implement me
 */
class editor_Services_SDLLanguageCloud_Connector extends editor_Services_Connector_Abstract {

    /**
     * @var editor_Services_SDLLanguageCloud_HttpApi
     */
    protected $api;
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::connectTo()
     */
    public function connectTo(editor_Models_LanguageResources_LanguageResource $languageResource,$sourceLang=null,$targetLang=null) {
        parent::connectTo($languageResource,$sourceLang,$targetLang);
        $class = 'editor_Services_SDLLanguageCloud_HttpApi';
        $this->api = ZfExtended_Factory::get($class, [$languageResource]);
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */
        $this->DEFAULT_MATCHRATE = $config->runtimeOptions->LanguageResources->sdllanguagecloud->matchrate;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::open()
     */
    public function open() {
        //This call is not necessary, since this resource is opened automatically.
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::open()
     */
    public function close() {
        //This call is not necessary, since this resource is closed automatically.
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::query()
     */
    public function query(editor_Models_Segment $segment) {
        $queryString = $this->getQueryString($segment);
        //return empty result when no query string exisit
        if(empty($queryString)) {
            return $this->resultList;
        }
        
        $this->resultList->setDefaultSource($queryString);
        
        $internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        /* @var $internalTag editor_Models_Segment_InternalTag */
        
        $queryString = $internalTag->toXliffPaired($queryString, true);
        return $this->querySdlApi($queryString);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::search()
     */
    public function search(string $searchString, $field = 'source', $offset = null) {
        return $this->querySdlApi($searchString);
    }
    
    /***
     * Search the resource for available translation. Where the source text is in resource source language and the received results
     * are in the resource target language
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::translate()
     */
    public function translate(string $searchString){
        return $this->querySdlApi($searchString);
    }
    
    
    /***
     * Query the sdl cloud api and get the available results as editor_Services_ServiceResult
     * @param string $searchString
     * @return editor_Services_ServiceResult
     */
    protected function querySdlApi($searchString){
        if(empty($searchString)) {
            return $this->resultList;
        }
        
        $this->resultList->setDefaultSource($searchString);
        
        //load all languages (sdl api use iso6393 langage shortcuts)
        $langModel=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $langModel editor_Models_Languages */
        $lngs=$langModel->loadAllKeyValueCustom('id','iso6393');
        
        $result=null;
        $params=[
            'domainCode'=>$this->languageResource->getSpecificDataByProperty('domainCode'),
            'text'=>$searchString,
            'from'=>$lngs[$this->sourceLang],
            'to'=>$lngs[$this->targetLang]
        ];
        if($this->api->search($params)){
            $result=$this->api->getResult();
        }
        $this->resultList->addResult(isset($result->translation) ? $result->translation : "",$this->DEFAULT_MATCHRATE);
        return $this->resultList;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::getStatus()
     */
    public function getStatus(& $moreInfo){
        try {
            $this->api->getStatus();
        }catch (ZfExtended_BadGateway $e){
            $moreInfo = $e->getMessage();
            $log = ZfExtended_Factory::get('ZfExtended_Log');
            /* @var $log ZfExtended_Log */
            $log->logError($moreInfo, $this->languageResource->getResource()->getUrl());
            return self::STATUS_NOCONNECTION;
        }
        
        if($this->api->getResponse()->getStatus()==200) {
            return self::STATUS_AVAILABLE;
        }
        
        //a 404 response from the status call means: 
        // - OpenTM2 is online
        // - the requested TM is currently not loaded, so there is no info about the existence
        // - So we display the STATUS_NOT_LOADED instead
        if($this->api->getResponse()->getStatus() == 404) {
            $moreInfo = 'Die Ressource ist generell verfügbar, stellt aber keine Informationen über das angefragte TM bereit, da dies nicht geladen ist.';
            return self::STATUS_NOT_LOADED;
        }
        
        $moreInfo = join("<br/>\n", array_map(function($item) {
            return $item->type.': '.$item->error;
        }, $this->api->getErrors()));
            
        return self::STATUS_NOCONNECTION;
    }
}