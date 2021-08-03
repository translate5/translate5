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
     * @param array $filePath
     * @param array $params
     * @return void|boolean
     */
    public function importTbx(array $filePath, array $params): ?bool
    {
        $import = ZfExtended_Factory::get('editor_Models_Import_TermListParser_Tbx');
        /* @var $import editor_Models_Import_TermListParser_Tbx */
        $import->mergeTerms = $params['mergeTerms'] ?? false;
        //import source (filesystem or crossApi)
        $import->importSource = $params['importSource'] ?? "";
        $import->customerIds = $params['customerIds'];

        $sessionUser = new Zend_Session_Namespace('user');
        $userGuid = $params['userGuid'] ?? $sessionUser->data->userGuid;
        $import->loadUser($userGuid);

        return $import->parseTbxFile($filePath, $params['collectionId']);
    }

    /**
     * Create new term collection and return the id.
     * @param string $name
     * @param array $customers
     * @return editor_Models_TermCollection_TermCollection
     */
    public function create(string $name, array $customers): editor_Models_TermCollection_TermCollection
    {
        $this->setName($name);

        $service = ZfExtended_Factory::get('editor_Services_TermCollection_Service');
        /* @var $service editor_Services_TermCollection_Service */

        //since for termcollections there are no service resources we don't have to deal with them.
        // normally the service resource provides the serviceType, here we use the Namespace as "shortcut"
        $serviceType = $service->getServiceNamespace();
        $this->setResourceId($serviceType);
        $this->setServiceType($serviceType);
        $this->setServiceName($service->getName());
        $this->setColor($service::DEFAULT_COLOR);
        $this->setResourceType(editor_Models_Segment_MatchRateType::TYPE_TERM_COLLECTION);
        $this->createLangResUuid();
        $resourceId=$this->save();
        
        
        if(!empty($customers)){
            $customerAssoc=ZfExtended_Factory::get('editor_Models_LanguageResources_CustomerAssoc');
            /* @var $customerAssoc editor_Models_LanguageResources_CustomerAssoc */
            $customerAssoc->addAssocs($resourceId, $customers);
        }

        return $this;
    }

    /***
     * Search from term matches the current term collections with the given query string.
     * All fuzzy languages will be included in the search.('en' as search language will result with search using 'en','en-US','en-GB' etc)
     * @param string $queryString
     * @param integer $sourceLang
     * @param integer $targetLang
     * @param string $field
     * @return array
     */
    public function searchCollection(string $queryString, int $sourceLang, int $targetLang, string $field): array
    {
        // set the default value for the $field, it can be also passed as null
        if (empty($field)) {
            $field = 'source';
        }
        $languageModel = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $languageModel editor_Models_Languages */

        // get source and target language fuzzies
        $sourceLangs = $languageModel->getFuzzyLanguages($sourceLang,'id',true);
        $targetLangs = $languageModel->getFuzzyLanguages($targetLang,'id',true);

        $s = $this->db->select()
            ->setIntegrityCheck(false)
            ->from('terms_term')
            ->where('lower(term) like lower(?) COLLATE utf8mb4_bin', $queryString)
            ->where('collectionId = ?', $this->getId())
            ->where('language IN(?)',$field === 'source' ? $sourceLangs : $targetLangs)
            ->group('termEntryTbxId');
        $rows = $this->db->fetchAll($s)->toArray();

        if (empty($rows)) {
            return [];
        }

		$termEntryTbxId = [];
		$termEntryTbxIdSearch = [];
		foreach ($rows as $res) {
			$termEntryTbxId[] = $res['termEntryTbxId'];
			//collect the searched terms, so thay are merged with the results
			if (!isset($termEntryTbxIdSearch[$res['termEntryTbxId']])) {
			    $termEntryTbxIdSearch[$res['termEntryTbxId']] = [];
			}
		    array_push($termEntryTbxIdSearch[$res['termEntryTbxId']], $res['term']);
		}

		$s = $this->db->select()
    		->setIntegrityCheck(false)
    		->from(['t' => 'terms_term'])
    		->joinLeft(['ta' => 'terms_attributes'], 'ta.termId = t.id AND ta.attrType = "processStatus"', ['ta.attrType AS processStatusAttribute', 'ta.value AS processStatusAttributeValue'])
    		->where('t.termEntryTbxId IN(?)', $termEntryTbxId)
    		->where('t.language IN(?)',$field === 'source' ? $targetLangs : $sourceLangs)
    		->where('t.collectionId = ?', $this->getId());
		$targetResults = $this->db->fetchAll($s)->toArray();

		//merge the searched terms with the result
		foreach ($targetResults as &$single){
		    $single['default'.$field] = '';
		    if (!empty($termEntryTbxIdSearch[$single['termEntryTbxId']])) {
		        $single['default'.$field] = $termEntryTbxIdSearch[$single['termEntryTbxId']][0];
		    }
		}

		return $targetResults;
    }

    /***
     * Get all collection associated with the task
     * @param string $taskGuid
     * @return array
     */
    public function getCollectionsForTask(string $taskGuid): array
    {
        $service = ZfExtended_Factory::get('editor_Services_TermCollection_Service');
        /* @var $service editor_Services_TermCollection_Service */

        $s = $this->db->select()
            ->setIntegrityCheck(false)
            ->from(['lr'=>'LEK_languageresources'])
            ->join(['ta'=>'LEK_languageresources_taskassoc'], 'ta.languageResourceId=lr.id', ['ta.taskGuid'])
            ->where('ta.taskGuid=?',$taskGuid)
            ->where('lr.serviceName=?',$service->getName());
        $rows = $this->db->fetchAll($s)->toArray();

        if (!empty($rows)) {
            return array_column($rows, 'id');
        }

        return [];
    }

    /***
     * Get all TermCollections ids assigned to the given customers.
     * @param array $customerIds
     * @param bool $dict
     * @return array
     */
    public function getCollectionsIdsForCustomer(array $customerIds, bool $dict = false): array
    {
        $service = ZfExtended_Factory::get('editor_Services_TermCollection_Service');
        /* @var $service editor_Services_TermCollection_Service */
        $serviceType = $service->getServiceNamespace();
        $s = $this->db->select()
        ->setIntegrityCheck(false)
        ->from(['lr' => 'LEK_languageresources'])
        ->join(['ca' => 'LEK_languageresources_customerassoc'], 'ca.languageResourceId = lr.id',['ca.customerId as customerId'])
        ->where('ca.customerId IN(?)',$customerIds)
        ->where('lr.serviceType = ?',$serviceType)
        ->group('lr.id');
        $rows = $this->db->fetchAll($s)->toArray();

        if (!empty($rows)) return $dict
            ? array_combine(array_column($rows, 'id'), array_column($rows, 'name'))
            : array_column($rows, 'id');

        return [];
    }


    /***
     * Get the attribute count for the collection
     * The return array will be in format:
     *  [
     *      'termsCount'=>number,
     *      'termsAttributeCount'=>number,
     *      'termsEntryAttributeCount'=>number,
     *  ]
     * @param int $collectionId
     * @return array
     */
    public function getAttributesCountForCollection(int $collectionId): array
    {
        $s = $this->db->select()
        ->setIntegrityCheck(false)
        ->from(['tc' => 'LEK_languageresources'], ['id'])
        ->join(['t' => 'terms_term'],'tc.id = t.collectionId', ['count(DISTINCT t.id) as termsCount'])
        ->join(['ta' => 'terms_attributes'],'tc.id = ta.collectionId AND not ta.termId is null', ['count(DISTINCT ta.id) as termsAtributeCount'])
        ->join(['tea' => 'terms_transacgrp'],'tc.id = tea.collectionId', ['count(DISTINCT tea.id) as termsEntryAtributeCount'])
        ->where('tc.id =?', $collectionId);

        $result = $this->db->fetchRow($s)->toArray();

        return $result;
    }

    /***
     * Associate termCollection to taskGuid (warning, sets autoCreatedOnImport = true)
     * @param mixed $collectionId
     * @param string $taskGuid
     */
    public function addTermCollectionTaskAssoc($collectionId, string $taskGuid)
    {
        $model=ZfExtended_Factory::get('editor_Models_LanguageResources_Taskassoc');
        /* @var $model editor_Models_LanguageResources_Taskassoc */
        $model->setLanguageResourceId($collectionId);
        $model->setTaskGuid($taskGuid);
        $model->setSegmentsUpdateable(false);
        $model->setAutoCreatedOnImport(true);
        $model->save();
    }

    /***
     * Get all existing languages in the term collections
     * @param array $collectionIds
     * @return array
     */
    public function getLanguagesInTermCollections(array $collectionIds): array
    {
        $s = $this->db->select()
        ->setIntegrityCheck(false)
        ->from('terms_term', ['terms_term.languageId as id'])
        ->join('LEK_languages', 'LEK_languages.id = terms_term.languageId', ['LEK_languages.rfc5646','LEK_languages.iso3166Part1alpha2','LEK_languages.langName'])
        ->where('terms_term.collectionId IN(?)', $collectionIds)
        ->group('terms_term.languageId');
        $rows = $this->db->fetchAll($s)->toArray();

        if (!empty($rows)) {
            return $rows;
        }

        return [];
    }

    /***
     * Get term collection by name
     * @param string $name
     * @return array|Zend_Db_Table_Row_Abstract
     */
    public function loadByName(string $name): ?array
    {
        $s = $this->db->select()
        ->where('name=?',$name)
        ->where('serviceName=?','TermCollection');
        $result=$this->db->fetchRow($s);
        if ($result) {
            return $result->toArray();
        }

        return $result;
    }

    /***
     * Check and remove the term collection if it is imported via task import
     * @param string $taskGuid
     */
    public function checkAndRemoveTaskImported(string $taskGuid)
    {
        //since the reference assoc → langres is not cascade delete, we have to delete them manually
        $taskAssocTable = ZfExtended_Factory::get('editor_Models_Db_Taskassoc');
        /* @var $taskAssocTable editor_Models_Db_Taskassoc */
        $s = $taskAssocTable->select()->where('autoCreatedOnImport = 1 AND taskGuid = ?', $taskGuid);
        $rows = $this->db->fetchAll($s)->toArray();
        $taskAssocTable->delete(['autoCreatedOnImport = 1 AND taskGuid = ?' => $taskGuid]);

        if(empty($rows)){
            return;
        }
        //remove the collection(s), should be normally only one
        foreach($rows as $row) {
            $this->load($row['languageResourceId']);
            try {
                $this->delete();
            }
            catch (ZfExtended_Models_Entity_Exceptions_IntegrityConstraint $e) {
                //do nothing in that case, that means the TermCollection can not be deleted, since it is in use by another task.
                // So we just leave it then
            }
        }
    }

    public function delete()
    {
        //remove the termcollection from the disk
        $this->removeCollectionDir($this->getId());
        parent::delete();
    }

    /***
     * Get the available collections for the currently logged user
     *
     * @param bool $dict
     * @param string|array $clientIds
     * @return array
     */
    public function getCollectionForAuthenticatedUser($dict = false, $clientIds = ''): array
    {
        $userModel = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $userModel ZfExtended_Models_User */
        $customers = $userModel->getUserCustomersFromSession();

        // If $clientIds arg is given - use intersection
        if ($clientIds) $customers = array_intersect($customers,
            is_array($clientIds) ? $clientIds : explode(',', $clientIds)
        );

        if (empty($customers)) {
            return [];
        }

        return $this->getCollectionsIdsForCustomer($customers, $dict);
    }

    /***
     * Load all available termCollections
     * @return array
     */
    public function loadAll(): array
    {
        $service = ZfExtended_Factory::get('editor_Services_TermCollection_Service');
        /* @var $service editor_Services_TermCollection_Service */
        $serviceType = $service->getServiceNamespace();
        $s = $this->db->select()
        ->where('lr.serviceType = ?',$serviceType);

        return $this->db->fetchAll($s)->toArray();
    }

    /***
     * Remove term collection from the disk
     * @param int $collectionId
     */
    public function removeCollectionDir(int $collectionId)
    {
        $collectionPath = editor_Models_Import_TermListParser_Tbx::getFilesystemCollectionDir().'tc_'.$collectionId;
        if (is_dir($collectionPath)) {
            /* @var $recursiveDirCleaner ZfExtended_Controller_Helper_Recursivedircleaner */
            $recursiveDirCleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
                'Recursivedircleaner'
                );
            $recursiveDirCleaner->delete($collectionPath);
        }
    }

    /***
     * Remove collection tbx files from the tbx-import directory where the file modification date is older than the given one
     * @param int $collectionId
     * @param int $olderThan: this is unix timestamp
     */
    public function removeOldCollectionTbxFiles(int $collectionId, int $olderThan)
    {
        $collectionPath = editor_Models_Import_TermListParser_Tbx::getFilesystemCollectionDir().'tc_'.$collectionId;
        if (is_dir($collectionPath)) {
            /* @var $recursiveDirCleaner ZfExtended_Controller_Helper_Recursivedircleaner */
            $recursiveDirCleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
                'Recursivedircleaner'
                );
            $recursiveDirCleaner->deleteOldFiles($collectionPath, $olderThan);
        }
    }
}
