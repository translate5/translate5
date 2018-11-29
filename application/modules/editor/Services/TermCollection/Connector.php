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
class editor_Services_TermCollection_Connector extends editor_Services_Connector_FilebasedAbstract {

    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::connectTo()
     */
    public function connectTo(editor_Models_LanguageResources_LanguageResource $languageResource,$sourceLang=null,$targetLang=null) {
        parent::connectTo($languageResource,$sourceLang,$targetLang);
        //the translations from the term collections are with high priority, that is why 104 (this is the highest matchrate in translate5)
        $this->defaultMatchRate = self::TERMCOLLECTION_MATCH_VALUE;
    }
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::addTm()
     */
    public function addTm(array $fileinfo = null,array $params=null) {
        if(empty($fileinfo)){
            $this->handleError("LanguageResources - termcollection import file does not exisit LanguageResource: \n");
            return false;
        }
        
        $import=ZfExtended_Factory::get('editor_Models_Import_TermListParser_Tbx');
        /* @var $import editor_Models_Import_TermListParser_Tbx */
        
        $import->mergeTerms=isset($params['mergeTerms']) ? filter_var($params['mergeTerms'], FILTER_VALIDATE_BOOLEAN) : false;
        
        //import the term collection
        if(!$import->parseTbxFile([$fileinfo],$this->languageResource->getId())){
            $this->handleError("LanguageResources - Error on termcollection import \n");
            return false;
        }
        
        //delete collection term entries older then the parameter date
        if(isset($params['deleteEntriesModifiedOlderThan']) && !empty($params['deleteEntriesModifiedOlderThan'])){
            
            $validator = new Zend_Validate_Date();
            $validator->setFormat(NOW_ISO);
            if(!$validator->isValid($params['deleteEntriesModifiedOlderThan'])){
                $params['deleteEntriesModifiedOlderThan'] = date(NOW_ISO, strtotime($params['deleteEntriesModifiedOlderThan']));
            }
            
            $termModel=ZfExtended_Factory::get('editor_Models_Term');
            /* @var $termModel editor_Models_Term */
            
            $termModel->removeOldTerms([$this->languageResource->getId()], $params['deleteEntriesModifiedOlderThan']);
            
            //remove all empty term entries from the same term collection
            $termEntry=ZfExtended_Factory::get('editor_Models_TermCollection_TermEntry');
            /* @var $termEntry editor_Models_TermCollection_TermEntry */
            
            $termEntry->removeEmptyFromCollection([$this->languageResource->getId()]);
        }
        
        //delete termcollection entries older then current import date
        if(isset($params['deleteEntriesOlderThanCurrentImport']) && filter_var($params['deleteEntriesOlderThanCurrentImport'], FILTER_VALIDATE_BOOLEAN)){
            $termEntry=ZfExtended_Factory::get('editor_Models_TermCollection_TermEntry');
            /* @var $termEntry editor_Models_TermCollection_TermEntry */
            $termEntry->removeOlderThan($this->languageResource->getId(), NOW_ISO);
        }
        
        return true;
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
        
        //query sdlcloud without tags
        $queryString = $segment->stripTags($queryString);
        
        return $this->queryCollectionResults($queryString);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::search()
     */
    public function search(string $searchString, $field = 'source', $offset = null) {
        return $this->queryCollectionResults($searchString);
    }
    
    /***
     * Search the resource for available translation. Where the source text is in resource source language and the received results
     * are in the resource target language
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::translate()
     */
    public function translate(string $searchString){
        return $this->queryCollectionResults($searchString);
    }
    
    /***
     * Search the terms in the term collection with the given query string
     * @param string $queryString
     * @return editor_Services_ServiceResult
     */
    protected function queryCollectionResults($queryString){
        $entity=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $entity editor_Models_TermCollection_TermCollection */
        $entity->load($this->languageResource->getId());
        
        $results=$entity->searchCollection($queryString,$this->sourceLang,$this->targetLang);
        
        $groupids=array_column($results, 'termEntryId');
        $groupids=array_unique($groupids);
        
        $term=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $term editor_Models_Term */
        $definitions=$term->getDeffinitionsByEntryIds($groupids);
        
        $term=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $term editor_Models_Term */
        $groups=$term->sortTerms([$results]);
        foreach ($groups as $group){
            foreach ($group as $res){
                //add all available definitions from the term termEntry
                if(isset($definitions[$res['termEntryId']])){
                    $res['definitions']=$definitions[$res['termEntryId']];
                }
                //convert back to array
                $this->resultList->addResult($res['term'],$this->defaultMatchRate,$res);
            }
        }
        
        return $this->resultList;
    }

    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::getStatus()
     */
    public function getStatus(& $moreInfo){
        return self::STATUS_AVAILABLE;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::getValidFiletypes()
     */
    public function getValidFiletypes() {
        return [
            'TBX' => 'application/xml',
        ];
    }
    
    /***
     * Add/parce tbx file to the exsisting termcollection
     * 
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::addAdditionalTm()
     */
    public function addAdditionalTm(array $fileinfo = null,array $params=null){
        return $this->addTm($fileinfo,$params);
    }

    public function getTm($mime){
        
    }
    
    /**
     * This method generates an 400 error
     *   which shows additional error information in the frontend
     *
     * @param string $logMsg
     */
    protected function handleError($logMsg) {
        $messages = Zend_Registry::get('rest_messages');
        /* @var $messages ZfExtended_Models_Messages */
        $msg = 'Von Termcollection gemeldeter Fehler';
        $messages->addError($msg, 'core', null);
        
        $log = ZfExtended_Factory::get('ZfExtended_Log');
        /* @var $log ZfExtended_Log */
        $data  = print_r($this->languageResource->getDataObject(),1);
        $log->logError($logMsg, $data);
    }
}