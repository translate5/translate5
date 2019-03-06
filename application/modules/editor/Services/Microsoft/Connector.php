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
class editor_Services_Microsoft_Connector extends editor_Services_Connector_Abstract {

    /***
     * Every string lower than this value will be translated using the dictonary lookup api.
     * This is only the case when using the translate call
     * @var integer
     */
    const DICTONARY_SEARCH_CHARACTERS_BORDER=30;
    /**
     * @var editor_Services_Microsoft_HttpApi
     */
    protected $api;

    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::__construct()
     */
    public function __construct() {
        parent::__construct();
        $this->api = ZfExtended_Factory::get('editor_Services_Microsoft_HttpApi');
        $config = Zend_Registry::get('config');
        /* @var $config Zend_Config */
        $this->defaultMatchRate = $config->runtimeOptions->LanguageResources->microsoft->matchrate;
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
        return $this->queryMicrosoftApi($this->prepareDefaultQueryString($segment), true);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::search()
     */
    public function search(string $searchString, $field = 'source', $offset = null) {
        throw new BadMethodCallException("The Microsoft Translation Connector does not support search requests");
    }
    
    /***
     * Search the resource for available translation. Where the source text is in resource source language and the received results
     * are in the resource target language 
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::translate()
     */
    public function translate(string $searchString){
        //the dictonary lookup translation is active only for less than or equal to DICTONARY_SEARCH_CHARACTERS_BORDER
        $this->api->setIsDictionaryLookup(strlen($searchString)<=self::DICTONARY_SEARCH_CHARACTERS_BORDER);
        return $this->queryMicrosoftApi($searchString);
    }
    
    /***
     * Query the microsoft api for the search string
     * @param string $searchString
     * @param boolean $reimportWhitespace optional, if true converts whitespace into translate5 capable internal tag
     * @return editor_Services_ServiceResult
     */
    protected function queryMicrosoftApi($searchString, $reimportWhitespace = false){
        if(empty($searchString)) {
            return $this->resultList;
        }
        
        //load all languages (sdl api use iso6393 langage shortcuts)
        $langModel=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $langModel editor_Models_Languages */
        $lngs=$langModel->loadAllKeyValueCustom('id','rfc5646');
        
        $result=null;
        if($this->api->search($searchString,$lngs[$this->sourceLang],$lngs[$this->targetLang])){
            $result=$this->api->getResult();
        }
        
        if(empty($result)){
            return $this->resultList;
        }
        $metaData=[];
        $translation="";
        foreach ($result as $res) {
            if(empty($translation)){
                $translation=$res['text'];
                if($reimportWhitespace) {
                    $translation = $this->importWhitespaceFromTagLessQuery($translation);
                }
            }
            if(isset($res['metaData'])){
                $metaData[]=$res['metaData'];
            }
        }
        $this->resultList->addResult($translation, $this->defaultMatchRate,['alternativeTranslations'=>$metaData]);
        return $this->resultList;
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