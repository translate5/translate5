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

class editor_Models_TermCollection_TermCollection extends editor_Models_LanguageResources_LanguageResource {

    /***
     * Import the tbx files in the term collection
     * 
     * @param array $filePath
     * @param array $params
     * @return void|boolean
     */
    public function importTbx(array $filePath,array $params){
        $import=ZfExtended_Factory::get('editor_Models_Import_TermListParser_Tbx');
        /* @var $import editor_Models_Import_TermListParser_Tbx */
        $import->mergeTerms = $params['mergeTerms'] ?? false;
        
        //import source (filesystem or crosspai)
        $import->importSource = $params['importSource'] ?? "";
        
        $import->customerIds=$params['customerIds'];
        return $import->parseTbxFile($filePath,$params['collectionId']);
    }
    
    /***
     * Create new term collection and return the id.
     * The autoCreatedOnImport flag is set to true.
     * @param string $name
     * @param array $customers
     * @param int $autoCreatedOnImport
     * 
     * @return int
     */
    public function create(string $name,array $customers,int $autoCreatedOnImport=1){
        $this->setAutoCreatedOnImport($autoCreatedOnImport);
        $this->setName($name);
        
        $service=ZfExtended_Factory::get('editor_Services_TermCollection_Service');
        /* @var $service editor_Services_TermCollection_Service */

        //since for termcollections there are no service resources we don't have to deal with them.
        // normally the service resource provides the serviceType, here we use the Namespace as "shortcut"
        $serviceType = $service->getServiceNamespace(); 
        $this->setResourceId($serviceType);
        $this->setServiceType($serviceType);
        $this->setServiceName($service->getName());
        $this->setColor($service::DEFAULT_COLOR);
        $this->setResourceType(editor_Models_Segment_MatchRateType::TYPE_TERM_COLLECTION);
        $resourceId=$this->save();
        
        if(!empty($customers)){
            $customerAssoc=ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
            /* @var $customerAssoc editor_Models_LanguageResources_CustomerAssoc */
            $customerAssoc->addAssocs($customers, $resourceId);
        }
        return $resourceId;
    }
    
    /***
     * Search from term matches the current term collections with the given query string.
     * All fuzzy languages will be included in the search.('en' as search language will result with search using 'en','en-US','en-GB' etc)
     * 
     * @param string $queryString
     * @param integer $sourceLang
     * @param integer $targetLang
     * @param string $field
     * @return array
     */
    public function searchCollection($queryString,$sourceLang,$targetLang,$field){
        //set the default value for the $field, it can be also passed as null
        if(empty($field)){
            $field='source';
        }
        $languageModel=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $languageModel editor_Models_Languages */
        
        //get source and target language fuzzies
        $sourceLangs=$languageModel->getFuzzyLanguages($sourceLang);
        $targetLangs=$languageModel->getFuzzyLanguages($targetLang);
        
        $sqlOld_and_very_slow_on_large_data=' SELECT * FROM LEK_terms '.
              'WHERE groupId IN ( '.
              'SELECT `t`.`groupId` FROM `LEK_terms` AS `t` '.
              'WHERE lower(term) like lower(?) COLLATE utf8_bin '.
              'AND (t.collectionId=?) AND (t.language IN(?)) GROUP BY `t`.`groupId`) '.
              'AND language IN(?) AND collectionId=?';
        
        $s=$this->db->select()
            ->setIntegrityCheck(false)
            ->from('LEK_terms')
            ->where('lower(term) like lower(?) COLLATE utf8_bin',$queryString)
            ->where('collectionId=?',$this->getId())
            ->where('language IN(?)',$field=='source' ? $sourceLangs : $targetLangs)
            ->group('groupId');
        $rows=$this->db->fetchAll($s)->toArray();
        if(empty($rows)){
            return array();
        }
        
		$groupIds = array();
		$groupIdSearch=[];
		foreach($rows as $res){
			$groupIds[] = $res['groupId'];
			//collect the searched terms, so thay are merged with the results
			if(!isset($groupIdSearch[$res['groupId']])){
			    $groupIdSearch[$res['groupId']]=[];
			}
		    array_push($groupIdSearch[$res['groupId']], $res['term']);
		}
		$s=$this->db->select()
    		->setIntegrityCheck(false)
    		->from(array('t'=>'LEK_terms'))
    		->joinLeft(array('ta'=>'LEK_term_attributes'), 'ta.termId=t.id AND ta.attrType="processStatus"',array('ta.attrType AS processStatusAttribute','ta.value AS processStatusAttributeValue'))
    		->where('t.groupId IN(?)',$groupIds)
    		->where('t.language IN(?)',$field=='source' ? $targetLangs : $sourceLangs)
    		->where('t.collectionId=?',$this->getId());
		$targetResults=$this->db->fetchAll($s)->toArray();
		
		//merge the searched terms with the result
		foreach ($targetResults as &$single){
		    $single['default'.$field]='';
		    if(!empty($groupIdSearch[$single['groupId']])){
		        $single['default'.$field]=$groupIdSearch[$single['groupId']][0];
		    }
		}
		return $targetResults;
    }
    
    /***
     * Get all collection associated with the task
     * 
     * @param string $taskGuid
     * @return array
     */
    public function getCollectionsForTask($taskGuid){
        $service=ZfExtended_Factory::get('editor_Services_TermCollection_Service');
        /* @var $service editor_Services_TermCollection_Service */
        
        $s=$this->db->select()
            ->setIntegrityCheck(false)
            ->from(array('lr'=>'LEK_languageresources'))
            ->join(array('ta'=>'LEK_languageresources_taskassoc'), 'ta.languageResourceId=lr.id',array('ta.taskGuid'))
            ->where('ta.taskGuid=?',$taskGuid)
            ->where('lr.serviceName=?',$service->getName());
        $rows=$this->db->fetchAll($s)->toArray();
        if(!empty($rows)){
            $ids = array_column($rows, 'id');
            return $ids;
        }
        return [];
    }
    
    /***
     * Get all TermCollections ids assigned to the given customers.
     * 
     * @param array $customerIds
     */
    public function getCollectionsIdsForCustomer($customerIds){
        $service = ZfExtended_Factory::get('editor_Services_TermCollection_Service');
        /* @var $service editor_Services_TermCollection_Service */
        $serviceType = $service->getServiceNamespace(); 
        $s=$this->db->select()
        ->setIntegrityCheck(false)
        ->from(array('lr'=>'LEK_languageresources'))
        ->join(array('ca'=>'LEK_languageresources_customerassoc'), 'ca.languageResourceId=lr.id',array('ca.customerId as customerId'))
        ->where('ca.customerId IN(?)',$customerIds)
        ->where('lr.serviceType = ?',$serviceType)
        ->group('lr.id');
        $rows=$this->db->fetchAll($s)->toArray();
        if(!empty($rows)){
            return array_column($rows, 'id');
        }
        return [];
    }
    
    
    /***
     * Get the attribute count for the collection
     * The return array will be in format: 
     *  [
     *      'termsCount'=>number,
     *      'termsAtributeCount'=>number,
     *      'termsEntryAtributeCount'=>number,
     *  ]
     * @param int $collectionId
     * @return array
     */
    public function getAttributesCountForCollection($collectionId){
        $s=$this->db->select()
        ->setIntegrityCheck(false)
        ->from(array('tc' => 'LEK_languageresources'), array('id'))
        ->join(array('t' => 'LEK_terms'),'tc.id=t.collectionId', array('count(DISTINCT t.id) as termsCount'))
        ->join(array('ta' => 'LEK_term_attributes'),'tc.id=ta.collectionId AND not ta.termId is null', array('count(DISTINCT ta.id) as termsAtributeCount'))
        ->join(array('tea' => 'LEK_term_attributes'),'tc.id=tea.collectionId AND tea.termId is null', array('count(DISTINCT tea.id) as termsEntryAtributeCount'))
        ->where('tc.id =?',$collectionId);
        return $this->db->fetchRow($s)->toArray();
    }
    
    /***
     * Associate termCollection to taskGuid
     * 
     * @param mixed $collectionId
     * @param string $taskGuid
     */
    public function addTermCollectionTaskAssoc($collectionId,$taskGuid){
        $model=ZfExtended_Factory::get('editor_Models_LanguageResources_Taskassoc');
        /* @var $model editor_Models_LanguageResources_Taskassoc */
        $model->setLanguageResourceId($collectionId);
        $model->setTaskGuid($taskGuid);
        $model->setSegmentsUpdateable(false);
        $model->save();
    }
    
    /***
     * Get all existing languages in the term collections
     * 
     * @param array $collectionIds
     * @return array
     */
    public function getLanguagesInTermCollections(array $collectionIds){
        $s=$this->db->select()
        ->setIntegrityCheck(false)
        ->from('LEK_terms',array('LEK_terms.language as id'))
        ->join('LEK_languages', 'LEK_languages.id = LEK_terms.language',array('LEK_languages.rfc5646','LEK_languages.iso3166Part1alpha2'))
        ->where('LEK_terms.collectionId IN(?)',$collectionIds)
        ->group('LEK_terms.language');
        $rows=$this->db->fetchAll($s)->toArray();
        
        if(!empty($rows)){
            return $rows;
        }
        
        return [];
    }
    
    /***
     * Get term collection by name
     * @param string $name
     * @return array
     */
    public function loadByName($name){
        $s=$this->db->select()
        ->where('name=?',$name);
        $result=$this->db->fetchRow($s);
        if($result){
            return $result->toArray();
        }
        return $result;
    }
    
    /***
     * Check and remove the term collection if it is imported via task import
     * @param array $collectionIds
     */
    public function checkAndRemoveTaskImported($taskGuid){
        $s=$this->db->select()
        ->setIntegrityCheck(false)
        ->from('LEK_languageresources',array('LEK_languageresources.*'))
        ->join('LEK_languageresources_taskassoc', 'LEK_languageresources_taskassoc.languageResourceId = LEK_languageresources.id',array('LEK_languageresources_taskassoc.languageResourceId as collectionId','LEK_languageresources_taskassoc.taskGuid'))
        ->where('LEK_languageresources_taskassoc.taskGuid=?',$taskGuid)
        ->where('LEK_languageresources.autoCreatedOnImport=?',1);
        $rows=$this->db->fetchAll($s)->toArray();
        
        if(empty($rows)){
            return false;
        }
        $collectionId=$rows[0]['id'];
        
        //remove the collection
        $this->load($collectionId);
        $this->delete();
        
        //remove the termcollection from the disk
        $this->removeCollectionDir($collectionId);
    }
    
    /***
     * Add language to the language resources assoc table.
     * When the language does not exisit for the columng(source/target), entry with null as pair value will be inserted.
     * @param int $language
     * @param int $collectionId
     */
    public function addCollectionLanguage($language,$collectionId){
        $lngAssoc=ZfExtended_Factory::get('editor_Models_LanguageResources_Languages');
        /* @var $lngAssoc editor_Models_LanguageResources_Languages */
        
        //if the language does not exist in the assoc as source, add entry with the source as $language and empty as target
        if(!$lngAssoc->isInCollection($language, 'sourceLang', $collectionId)){
            $lngAssoc->saveLanguages($language, null, $collectionId);
        }
        
        //if the language does not exist in the assoc as target, add entry with the target as $language and empty as source
        if(!$lngAssoc->isInCollection($language, 'targetLang', $collectionId)){
            $lngAssoc->saveLanguages(null, $language, $collectionId);
        }
    }
    
    /***
     * Get the available collections for the currently logged user
     *
     * @return array
     */
    public function getCollectionForAuthenticatedUser(){
        $userModel=ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $userModel ZfExtended_Models_User */
        $customers=$userModel->getUserCustomersFromSession();
        if(empty($customers)){
            return [];
        }
        return $this->getCollectionsIdsForCustomer($customers);
    }
    
    /***
     * Remove term collection from the disk
     * @param int $collectionId
     */
    protected function removeCollectionDir($collectionId){
        $collectionPath=editor_Models_Import_TermListParser_Tbx::getFilesystemCollectionDir().'tc_'.$collectionId;
        if(is_dir($collectionPath)){
            /* @var $recursivedircleaner ZfExtended_Controller_Helper_Recursivedircleaner */
            $recursivedircleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
                'Recursivedircleaner'
                );
            $recursivedircleaner->delete($collectionPath);
        }
    }
}
