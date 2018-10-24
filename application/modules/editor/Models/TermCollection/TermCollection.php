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
        $import->mergeTerms=isset($params['mergeTerms']) ? $params['mergeTerms'] : false;
        
        //import source (filesystem or crosspai)
        $import->importSource=isset($params['importSource']) ? $params['importSource'] : "";
        
        $import->customerIds=$params['customerIds'];
        return $import->parseTbxFile($filePath,$params['collectionId']);
    }
    
    /***
     * Create new term collection and return the id.
     * The autoCreatedOnImport flag is set to true.
     * @param string $name
     * @param array $customers
     * @return int
     */
    public function create($name,$customers){
        $this->setAutoCreatedOnImport(1);
        $this->setName($name);
        
        $service=ZfExtended_Factory::get('editor_Services_TermCollection_Service');
        /* @var $service editor_Services_TermCollection_Service */
        $nsp=$service->getServiceNamespace();
        $this->setResourceId($nsp);
        $this->setServiceType($nsp);
        $this->setServiceName($service->getName());
        $this->setColor($service::DEFAULT_COLOR);
        $this->setResourceType(editor_Models_Segment_MatchRateType::TYPE_TERM_COLLECTION);
        $resourceId=$this->save();
        
        if($customers){
            $customerAssoc=ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
            /* @var $customerAssoc editor_Models_LanguageResources_CustomerAssoc */
            $customerAssoc->addAssocs($customers, $resourceId);
        }
        return $resourceId;
    }
    
    /***
     * Search from term matches the current term collections with the given query string
     * 
     * @param string $queryString
     * @return array
     */
    public function searchCollection($queryString,$sourceLang,$targetLang){
        $sql=' SELECT * FROM LEK_terms '.
              'WHERE groupId IN ( '.
              'SELECT `t`.`groupId` FROM `LEK_terms` AS `t` '.
              'WHERE lower(term) like lower(?) COLLATE utf8_bin '.
              'AND (t.collectionId=?) AND (t.language=?) GROUP BY `t`.`groupId`) '.
              'AND language=? AND collectionId=?';
        return $this->db->getAdapter()->query($sql, array($queryString,$this->getId(),$sourceLang,$targetLang,$this->getId()))->fetchAll();
    }
    
    /***
     * Get all collection associated with the task
     * 
     * @param guid $taskGuid
     * @return array
     */
    public function getCollectionsForTask($taskGuid){
        $s=$this->db->select()
            ->setIntegrityCheck(false)
            ->from('LEK_term_collection_taskassoc')
            ->where('taskGuid=?',$taskGuid);
        $rows=$this->db->fetchAll($s)->toArray();
        if(!empty($rows)){
            $ids = array_column($rows, 'collectionId');
            return $ids;
        }
        return [];
    }
    
    /***
     * Get all collections ids assigned to the given customers.
     * 
     * @param array $customerIds
     */
    public function getCollectionsIdsForCustomer($customerIds){
        $s=$this->db->select()
        ->setIntegrityCheck(false)
        ->from(array('lr'=>'LEK_languageresources'))
        ->join(array('ca'=>'LEK_languageresources_customerassoc'), 'ca.languageResourceId=lr.id',array('ca.customerId as customerId'))
        ->where('ca.customerId IN(?)',$customerIds);
        $rows=$this->db->fetchAll($s)->toArray();
        if(!empty($rows)){
            $ids = array_column($rows, 'id');
            return $ids;
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
     * @param integer $collectionId
     * @return array
     */
    public function getAttributesCountForCollection($collectionId){
        $s=$this->db->select()
        ->setIntegrityCheck(false)
        ->from(array('tc' => 'LEK_languageresources'), array('id'))
        ->join(array('t' => 'LEK_terms'),'tc.id=t.collectionId', array('count(DISTINCT t.id) as termsCount'))
        ->join(array('ta' => 'LEK_term_attributes'),'tc.id=ta.collectionId', array('count(DISTINCT ta.id) as termsAtributeCount'))
        ->join(array('tea' => 'LEK_term_entry_attributes'),'tc.id=tea.collectionId', array('count(DISTINCT tea.id) as termsEntryAtributeCount'))
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
        $sql='INSERT INTO LEK_term_collection_taskassoc (collectionId,taskGuid) '.
              'VALUES(?,?);';
        $this->db->getAdapter()->query($sql,[$collectionId,$taskGuid]);
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
        ->join('LEK_term_collection_taskassoc', 'LEK_term_collection_taskassoc.collectionId = LEK_languageresources.id',array('LEK_term_collection_taskassoc.collectionId','LEK_term_collection_taskassoc.taskGuid'))
        ->where('LEK_term_collection_taskassoc.taskGuid=?',$taskGuid)
        ->where('LEK_languageresources.autoCreatedOnImport=?',1);
        $rows=$this->db->fetchAll($s)->toArray();
        
        if(empty($rows)){
            return false;
        }
        $collectionId=$rows[0]['id'];
        
        //remove the collection
        $this->load($collectionId);
        $this->delete();
        
        //remove the task from the assoc table
        $taskassoc = ZfExtended_Factory::get('editor_Models_Db_TermCollection_TaskAssoc');
        $taskassoc->delete(array('taskGuid = ?' => $taskGuid));
        
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
     * Remove term collection from the disk
     * @param integer $collectionId
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