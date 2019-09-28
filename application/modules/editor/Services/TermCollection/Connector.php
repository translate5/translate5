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
     * If the query for the term had tags, the match rate must be less then 100% so that the user has to fix the tags
     * @var integer
     */
    const TERMCOLLECTION_TAG_MATCH_VALUE = 99;
    
    public function __construct() {
        parent::__construct();
        //the translations from the term collections are with high priority, that is why 104 (this is the highest matchrate in translate5)
        $this->defaultMatchRate = self::TERMCOLLECTION_MATCH_VALUE;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::addTm()
     */
    public function addTm(array $fileinfo = null,array $params=null) {
        if(empty($fileinfo)){
            //empty term collection
            return false;
        }
        
        $import=ZfExtended_Factory::get('editor_Models_Import_TermListParser_Tbx');
        /* @var $import editor_Models_Import_TermListParser_Tbx */
        
        $import->mergeTerms=isset($params['mergeTerms']) ? filter_var($params['mergeTerms'], FILTER_VALIDATE_BOOLEAN) : false;
        
        $sessionUser = new Zend_Session_Namespace('user');
        $userGuid=$params['userGuid'] ?? $sessionUser->data->userGuid;
        $import->loadUser($userGuid);
        
        //import the term collection
        if(!$import->parseTbxFile([$fileinfo],$this->languageResource->getId())){
            $this->handleError("LanguageResources - Error on termcollection import \n");
            return false;
        }
        
        $termModel=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $termModel editor_Models_Term */
        
        $validator = new Zend_Validate_Date();
        $validator->setFormat('Y-m-d H:i:s');
        
        //delete collection term entries older then the parameter date
        if(isset($params['deleteTermsModifiedOlderThan']) && !empty($params['deleteTermsModifiedOlderThan'])){
            
            if(!$validator->isValid($params['deleteTermsModifiedOlderThan'])){
                $params['deleteTermsModifiedOlderThan'] = date('Y-m-d H:i:s', strtotime($params['deleteTermsModifiedOlderThan']));
            }
            $termModel->removeOldTerms([$this->languageResource->getId()], $params['deleteTermsModifiedOlderThan']);
        }
        
        $deleteOlderThanCurrentImport=isset($params['deleteTermsOlderThanCurrentImport']) && filter_var($params['deleteTermsOlderThanCurrentImport'], FILTER_VALIDATE_BOOLEAN);
        //delete termcollection terms older then current import date
        if($deleteOlderThanCurrentImport){
            $termModel->removeOldTerms([$this->languageResource->getId()], NOW_ISO);
        }
        
        //check if the delete proposal older than date is set
        $deleteProposalsDate=null;
        if(!empty($params['deleteProposalsOlderThan']) && !$validator->isValid($params['deleteProposalsOlderThan'])){
            //the date is set but it is not in the required format
            $deleteProposalsDate = date('Y-m-d H:i:s', strtotime($params['deleteProposalsOlderThan']));
        }
        
        //the delet proposals older than the current import is set, use the now_iso as reference date
        $deleteProposalsOlderThanCurrentImport=isset($params['deleteProposalsOlderThanCurrentImport']) && filter_var($params['deleteProposalsOlderThanCurrentImport'], FILTER_VALIDATE_BOOLEAN);
        if(empty($deleteProposalsDate) && $deleteProposalsOlderThanCurrentImport){
            $deleteProposalsDate=NOW_ISO;
        }
        
        //delete term proposals 
        if(!empty($deleteProposalsDate)){
            $proposals=ZfExtended_Factory::get('editor_Models_Term_Proposal');
            /* @var $proposals editor_Models_Term_Proposal */
            //remove the term proposals
            $proposals->removeOlderThan([$this->languageResource->getId()],$deleteProposalsDate);
            $attributeProposals=ZfExtended_Factory::get('editor_Models_Term_AttributeProposal');
            /* @var $attributeProposals editor_Models_Term_AttributeProposal */
            //remove the attirubte proposals
            $attributeProposals->removeOlderThan([$this->languageResource->getId()],$deleteProposalsDate);
        }
        
        //remove all empty term entries from the same term collection
        $termEntry=ZfExtended_Factory::get('editor_Models_TermCollection_TermEntry');
        /* @var $termEntry editor_Models_TermCollection_TermEntry */
        $termEntry->removeEmptyFromCollection([$this->languageResource->getId()]);
        
        return true;
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::query()
     */
    public function query(editor_Models_Segment $segment) {
        return $this->queryCollectionResults($this->prepareDefaultQueryString($segment), true);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Services_Connector_Abstract::search()
     */
    public function search(string $searchString, $field = 'source', $offset = null) {
        $searchString='%'.$searchString.'%';
        return $this->queryCollectionResults($searchString,false,$field);
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
     * @param boolean $reimportWhitespace optional, if true converts whitespace into translate5 capable internal tag
     * @param string $field optional, the field where the search will be performed
     * @return editor_Services_ServiceResult
     */
    protected function queryCollectionResults($queryString, $reimportWhitespace = false,$field='source'){
        if(empty($queryString) && $queryString !== "0") {
            return $this->resultList;
        }
        $entity=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $entity editor_Models_TermCollection_TermCollection */
        $entity->load($this->languageResource->getId());
        
        $results=$entity->searchCollection($queryString,$this->sourceLang,$this->targetLang,$field);
        
        //load all available languages, so we can set the term rfc value to the frontend
        $langModel=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $langModel editor_Models_Languages */
        $lngs=$langModel->loadAllKeyValueCustom('id','rfc5646');
        
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
                $matchRate = $this->tagsWereStripped ? self::TERMCOLLECTION_TAG_MATCH_VALUE : $this->defaultMatchRate;
                if($reimportWhitespace) {
                    $res['term'] = $this->importWhitespaceFromTagLessQuery($res['term']);
                }
                if(isset($res['language'])){
                    $res['languageRfc'] = $lngs[$res['language']] ?? null;
                }
                //set the default source and the result depending of where the search is triggered
                $this->resultList->setDefaultSource($field == 'source' ? $res['default'.$field] : $res['term']);
                $this->resultList->addResult($field == 'source' ? $res['term'] : $res['default'.$field],$matchRate,$res);
            }
        }
        
        return $this->resultList;
    }

    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_Abstract::getStatus()
     */
    public function getStatus(& $moreInfo){
        $status=$this->languageResource->getSpecificData('status');
        if(empty($status)){
            $status=self::STATUS_AVAILABLE;
        }
        return $status;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Services_Connector_FilebasedAbstract::getValidFiletypes()
     */
    public function getValidFiletypes() {
        return [
            'TBX' => ['application/xml','text/xml'],
        ];
    }
    
    /**
     *
     * {@inheritdoc}
     * @see editor_Services_Connector_FilebasedAbstract::getValidExportTypes()
     */
    public function getValidExportTypes()
    {
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
