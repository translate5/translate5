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
 */
class editor_Services_Google_Connector extends editor_Services_Connector_Abstract {

    /**
     * @var editor_Services_Google_HttpApi
     */
    protected $api;

    /***
     * api class
     * @var string
     */
    protected $apiClass='editor_Services_Google_HttpApi';
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::connectTo()
     */
    public function connectTo(editor_Models_TmMt $tmmt,$sourceLang=null,$targetLang=null) {
        parent::connectTo($tmmt,$sourceLang,$targetLang);
        $this->api = ZfExtended_Factory::get($this->apiClass, [$tmmt]);
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::open()
     */
    public function open() {
        //This call is not necessary, since TMs are opened automatically.
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::open()
     */
    public function close() {
    /*
     * This call deactivated, since openTM2 has a access time based garbage collection
     * If we close a TM and another Task still uses this TM this bad for performance,
     *  since the next request to the TM has to reopen it
     */
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::query()
     */
    public function query(editor_Models_Segment $segment) {
        $queryString = $this->getQueryString($segment);
        
        //if source is empty, OpenTM2 will return an error, therefore we just return an empty list
        if(empty($queryString)) {
            return $this->resultList;
        }
        $this->resultList->setDefaultSource($queryString);
        return $this->queryGoogleApi($queryString);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::search()
     */
    public function search(string $searchString, $field = 'source', $offset = null) {
        return $this->queryGoogleApi($searchString);
    }
    
    /***
     * Query the google cloud api for the search string
     * @param string $searchString
     * @return editor_Services_ServiceResult
     */
    protected function queryGoogleApi($searchString){
        if(empty($searchString)) {
            return $this->resultList;
        }
        
        $this->resultList->setDefaultSource($searchString);
        
        //load all languages (sdl api use iso6393 langage shortcuts)
        $langModel=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $langModel editor_Models_Languages */
        $lngs=$langModel->loadAllKeyValueCustom('id','rfc5646');
        
        $result=null;
        if($this->api->search($searchString,$lngs[$this->sourceLang],$lngs[$this->targetLang])){
            $result=$this->api->getResult();
        }
        $this->resultList->addResult(isset($result['text']) ? $result['text'] : '');
        return $this->resultList;
    }
    
    /**
     * Throws a ZfExtended_BadGateway exception containing the underlying errors
     * @throws ZfExtended_BadGateway
     */
    protected function throwBadGateway() {
        $e = new ZfExtended_BadGateway('Die angefragte OpenTM2 Instanz meldete folgenden Fehler:');
        $e->setOrigin('LanguageResources');
        $e->setErrors($this->api->getErrors());
        throw $e;
    }
    
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::getStatus()
     */
    public function getStatus(& $moreInfo){
        if($this->api->getStatus()){
            return self::STATUS_AVAILABLE;
        }
        return self::STATUS_NOCONNECTION;
    }
}