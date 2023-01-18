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

class editor_Models_TermCollection_TermCollection extends editor_Models_LanguageResources_LanguageResource {
    /**
     * Import the tbx files in the term collection
     * @param array $filePath
     * @param array $params
     * @return bool|null
     */
    public function importTbx(array $filePath, array $params): ?bool
    {
        $import = ZfExtended_Factory::get('editor_Models_Import_TermListParser_Tbx');
        /* @var $import editor_Models_Import_TermListParser_Tbx */
        $import->mergeTerms = $params['mergeTerms'] ?? false;
        //import source (filesystem or crossApi)
        $import->importSource = $params['importSource'] ?? "";
        if (is_string($params['customerIds'])) {
            $params['customerIds'] = explode(',',$params['customerIds']);
        }
        $import->customerIds = $params['customerIds'];
        $import->loadUser($this->getUserGuid($params));
        return $import->parseTbxFile($filePath, $params['collectionId']);
    }

    private function getUserGuid(array $params): string
    {
        if (array_key_exists('userGuid', $params) && !empty($params['userGuid'])) {
            return $params['userGuid'];
        }

        if (ZfExtended_Authentication::getInstance()->isAuthenticated()) {
            return ZfExtended_Authentication::getInstance()->getUser()->getUserGuid();
        }

        return ZfExtended_Models_User::SYSTEM_GUID;
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
     * Result will be listed only if there is matching term in the opposite language:
     * Example if there is a match for term in source(de), and in the same term entry, there is term in the opposite language(en), than this
     * will be listed as result
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
            ->where('languageId IN(?)',$field === 'source' ? $sourceLangs : $targetLangs)
            ->group('termEntryTbxId');
        $rows = $this->db->fetchAll($s)->toArray();

        if (empty($rows)) {
            return [];
        }

        $termEntryTbxId = [];
        $termEntryTbxIdSearch = [];
        foreach ($rows as $res) {
            $termEntryTbxId[] = $res['termEntryTbxId'];
            //collect the searched terms, so thy are merged with the results
            if (!isset($termEntryTbxIdSearch[$res['termEntryTbxId']])) {
                $termEntryTbxIdSearch[$res['termEntryTbxId']] = [];
            }
            array_push($termEntryTbxIdSearch[$res['termEntryTbxId']], $res['term']);
        }

        // fill all terms in the opposite field of the matched term results
        $s = $this->db->select()
            ->setIntegrityCheck(false)
            ->from(['t' => 'terms_term'])
            ->joinLeft(['ta' => 'terms_attributes'], 'ta.termId = t.id AND ta.type = "processStatus"', ['ta.type AS processStatusAttribute', 'ta.value AS processStatusAttributeValue'])
            ->where('t.termEntryTbxId IN(?)', $termEntryTbxId)
            ->where('t.languageId IN(?)',$field === 'source' ? $targetLangs : $sourceLangs)
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
    public function getCollectionsForTask(string $taskGuid, bool $idsOnly = true): array
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

        if (empty($rows)) {
            return [];
        }
        if($idsOnly) {
            return array_column($rows, 'id');
        }
        return $rows;
    }

    /***
     * Get the attribute count for the collection
     * The return array will be in format:
     *  [
     *      'termsCount'=>number,
     *      'termsEntryAttributeCount'=>number,
     *      'languageAtributeCount'=>number,
     *      'termsAtributeCount'=>number,
     *  ]
     * @param int $collectionId
     * @return array
     */
    public function getAttributesCountForCollection(int $collectionId): array
    {

        $query = "SELECT 
                        (SELECT count(DISTINCT id) FROM terms_term WHERE collectionId = ?) AS termsCount,
                        SUM(entry) AS termsEntryAtributeCount, SUM(language) languageAtributeCount, SUM(term) AS termsAtributeCount
                    FROM
                        ((SELECT 
                            SUM(CASE
                                    WHEN
                                        termId IS NULL AND language IS NULL
                                            AND termEntryId IS NOT NULL
                                    THEN
                                        1
                                    ELSE 0
                                END) AS entry,
                                SUM(CASE
                                    WHEN termId IS NOT NULL THEN 1
                                    ELSE 0
                                END) AS term,
                                SUM(CASE
                                    WHEN
                                        termId IS NULL AND language IS NOT NULL
                                            AND termEntryId IS NOT NULL
                                    THEN
                                        1
                                    ELSE 0
                                END) AS language
                        FROM
                            terms_transacgrp
                        WHERE
                            collectionId = ?) UNION ALL (SELECT 
                            SUM(CASE
                                    WHEN
                                        termId IS NULL AND language IS NULL
                                            AND termEntryId IS NOT NULL
                                    THEN
                                        1
                                    ELSE 0
                                END) AS entry,
                                SUM(CASE
                                    WHEN termId IS NOT NULL THEN 1
                                    ELSE 0
                                END) AS term,
                                SUM(CASE
                                    WHEN
                                        termId IS NULL AND language IS NOT NULL
                                            AND termEntryId IS NOT NULL
                                    THEN
                                        1
                                    ELSE 0
                                END) AS language
                        FROM
                            terms_attributes
                        WHERE
                            collectionId = ?)) AS e;";

        return $this->db->getAdapter()->query($query,[$collectionId,$collectionId,$collectionId])->fetchAll()[0] ?? [];
    }


    /***
     * Get all TermCollections ids assigned to the given customers.
     * @param array $customerIds
     * @param bool $dict if true return ids mapped to name, if false array of IDs only
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

        if (!empty($rows)) {
            if($dict) {
                return array_combine(array_column($rows, 'id'), array_column($rows, 'name'));
            }
            return array_column($rows, 'id');
        }

        return [];
    }

    /***
     * Associate termCollection to taskGuid (warning, sets autoCreatedOnImport = true)
     * @param mixed $collectionId
     * @param string $taskGuid
     */
    public function addTermCollectionTaskAssoc($collectionId, string $taskGuid)
    {
        $model=ZfExtended_Factory::get('MittagQI\Translate5\LanguageResource\TaskAssociation');
        /* @var $model MittagQI\Translate5\LanguageResource\TaskAssociation */
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
        ->from('LEK_languageresources_languages', ['LEK_languageresources_languages.sourceLang as id'])
        ->join('LEK_languages', 'LEK_languages.id = LEK_languageresources_languages.sourceLang', ['LEK_languages.rfc5646','LEK_languages.iso3166Part1alpha2','LEK_languages.langName'])
        ->where('LEK_languageresources_languages.languageResourceId IN(?)', $collectionIds)
        ->group('LEK_languageresources_languages.sourceLang');
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
        $taskAssocTable = ZfExtended_Factory::get('MittagQI\Translate5\LanguageResource\Db\TaskAssociation');
        /* @var $taskAssocTable MittagQI\Translate5\LanguageResource\Db\TaskAssociation */
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
        // remove the termcollection tbx files from the disk
        $this->removeCollectionDir($this->getId());
        // clean up the collection images
        $this->removeCollectionImagesDir($this->getId());
        parent::delete();
        //remove all empty term entries from the same term collection
        $termEntry = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermEntryModel');
        /* @var $termEntry editor_Models_Terminology_Models_TermEntryModel */
        $termEntry->removeEmptyFromCollection([$this->getId()]);
    }

    /**
     * Get the available collections for the currently authenticated user
     *
     * @param bool $dict if true return ids mapped to name, if false array of IDs only
     * @param array $clientIds if given, intersect the loaded collection IDs with the ones given as parameter
     * @param bool $termportal defines if we are in termportal or not
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getCollectionForAuthenticatedUser(bool $dict = false, array $clientIds = [], $termportal = false): array
    {
        $user = ZfExtended_Authentication::getInstance()->getUser();

        if ($termportal && in_array('termPM_allClients', $user->getRoles())) {
            $customers = Zend_Db_Table_Abstract::getDefaultAdapter()->query('SELECT `id` FROM `LEK_customer`')->fetchAll(PDO::FETCH_COLUMN);
        }
        else {
            $customers = $user->getCustomersArray();
        }


        if (!empty($clientIds)) {
            $customers = array_intersect($customers, $clientIds);
        }

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
        ->where('serviceType = ?',$serviceType);

        return $this->db->fetchAll($s)->toArray();
    }


    /***
     * Get term translations (source/target) for given term collection and source/target language.
     * @param int $collection
     * @param int $source
     * @param int $target
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getTermTranslationsForLanguageCombo(int $collection, int $source, int $target): array
    {
        /** @var editor_Models_Languages $fuzzyModel */
        $fuzzyModel = ZfExtended_Factory::get('editor_Models_Languages');

        $sourceLanguages = $fuzzyModel->getFuzzyLanguages($source,includeMajor: true);
        $targetLanguages = $fuzzyModel->getFuzzyLanguages($target,includeMajor: true);


        // get the configured term processing statuses which can be used
        $config = Zend_Registry::get('config');
        $processStatus = $config->runtimeOptions->terminology->usedTermProcessStatus->toArray();
        if( empty($processStatus)){
            $processStatus =  [editor_Models_Terminology_Models_TermModel::PROCESS_STATUS_FINALIZED];
        }
        $processStatus = implode('","',$processStatus);
        
        // This query will select translated terms from source to target langauge from each term entry
        // excluding the deprecated terms. The terms with preferredTerm status will have always priority against the
        // other terms. In case the term is not in preferredTerm status, the next most valuable status is admittedTerm.
        // Because sorting inside a grouped rows is needed, this is the way how this is done in mysql 8.
        // What the query is doing is actually it is joining the terms_term table by itself and searching for term
        // pairs with source and target langauges. When multiple matches in one term entry are found, because of the
        // term status order by and termEntryId group by, only one pair per langauge combo will be returned.
        $sql = 'SELECT sourceTable.term AS source,targetTable.term AS target FROM(
                    SELECT
                        sorted_temp_table.*
                    FROM (
                        SELECT
                            ROW_NUMBER() OVER (
                                PARTITION BY termEntryId 
                                ORDER BY status = "'.editor_Models_Terminology_Models_TermModel::STAT_PREFERRED.'" DESC,status ="'.editor_Models_Terminology_Models_TermModel::STAT_ADMITTED.'" DESC,termEntryId ASC,id DESC
                            ) AS virtual_id,
                            term,
                            id,
                            status,
                            termEntryId,
                            languageId,
                            collectionId
                        FROM terms_term
                        WHERE languageId IN('.implode(',',$sourceLanguages).')
                        AND status != "'.editor_Models_Terminology_Models_TermModel::STAT_DEPRECATED.'"
                        AND processStatus IN("'.$processStatus.'")
                    ) AS sorted_temp_table
                    GROUP BY sorted_temp_table.termEntryId) AS sourceTable
                    INNER JOIN (
                    
                    SELECT
                        sorted_temp_table.*
                    FROM (
                        SELECT
                            ROW_NUMBER() OVER (
                                PARTITION BY termEntryId 
                                ORDER BY status = "'.editor_Models_Terminology_Models_TermModel::STAT_PREFERRED.'" DESC,status ="'.editor_Models_Terminology_Models_TermModel::STAT_ADMITTED.'" DESC,termEntryId ASC,id DESC
                            ) AS virtual_id,
                            term,
                            id,
                            status,
                            termEntryId,
                            languageId,
                            collectionId
                        FROM terms_term
                        WHERE languageId IN('.implode(',',$targetLanguages).')
                        AND status != "'.editor_Models_Terminology_Models_TermModel::STAT_DEPRECATED.'"
                        AND processStatus IN("'.$processStatus.'")
                    ) AS sorted_temp_table
                    GROUP BY sorted_temp_table.termEntryId
                    ) as targetTable ON sourceTable.termEntryId = targetTable.termEntryId
                    WHERE sourceTable.collectionId = '.$collection.'
                    AND sourceTable.id != targetTable.id
                    GROUP BY sourceTable.term;';

        return $this->db->getAdapter()->query($sql)->fetchAll();
    }

    /***
     * Remove recursive the given directory path
     *
     * @param string $path
     */
    protected function removeDirectoryRecursive(string $path){
        if (is_dir($path)) {
            ZfExtended_Utils::recursiveDelete($path);
        }
    }

    /***
     * Remove term collection from the disk
     * @param int $collectionId
     */
    public function removeCollectionDir(int $collectionId)
    {
        $collectionPath = editor_Models_Import_TermListParser_Tbx::getFilesystemCollectionDir().'tc_'.$collectionId;
        $this->removeDirectoryRecursive($collectionPath);
    }

    /***
     * Remove term collection images from the disk
     * @param imt $collectionId
     */
    public function removeCollectionImagesDir(int $collectionId)
    {
        /* @var $i editor_Models_Terminology_Models_ImagesModel */
        $i = ZfExtended_Factory::get('editor_Models_Terminology_Models_ImagesModel');
        $this->removeDirectoryRecursive($i->getImagePath($collectionId));
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

    /**
     * Example usage:
     *  ->updateStats($collectionId):
     *      - Update all qties using SELECT COUNT(`id`) FROM corresponsind tables
     *  ->updateStats($collectionId, ['termEntry' => 1, 'term' => -1]):
     *      - Update termEntry and term qties using given diff-values. This example may not happen in real life
     *            it's here just to indicate that qties can be increased or decreased depending on diff > 0 or < 0
     *      - Update attribute qty using SELECT COUNT(`id`) as no diff given
     *
     * @param $collectionId
     * @param mixed $diff
     * @param $diff
     */
    public function updateStats(int $collectionId, $diff = false) {

        // Foreach type
        foreach ([
            'termEntry' => 'terms_term_entry',
            'term'      => 'terms_term',
            'attribute' => 'terms_attributes'
        ] as $type => $table) {

            // If $diff arg is not an array, or is, but having no actual diff specified under $type key
            if (!is_array($diff) || !isset($diff[$type])) {

                // Get qty
                $qty = $this->db->getAdapter()->query('
                    SELECT COUNT(`id`) FROM `' . $table . '` WHERE `collectionId` = ?
                ', $collectionId)->fetchColumn();

            // Else
            } else {

                // Set qty as an expression, that increases/decreases the existing value within json
                $qty = 'IFNULL(JSON_EXTRACT(`specificData`, "$.' . $type . '"), 0) + (' . $diff[$type] . ')';
            }

            // Build "key, value" pair
            $stat[$type] = '"$.' . $type . '", ' . $qty;
        }

        // Update stat
        $this->db->getAdapter()->query($_ = '
            UPDATE `LEK_languageresources` 
            SET `specificData` = JSON_SET(`specificData`, 
              ' . join(",\n", $stat) . '
            )
            WHERE `id` = ?        
        ', $collectionId);

        //i($_, 'a');
    }

    /**
     * @param ZfExtended_Models_User $user
     * @return array
     */
    public function getAccessibleCollectionIds(ZfExtended_Models_User $user): array {
        $s = $this->db->select()
            ->distinct()
            ->from(['lr' => 'LEK_languageresources'], ['id'])
            ->setIntegrityCheck(false)
            ->join(['lr2c' => 'LEK_languageresources_customerassoc'], '`lr2c`.`languageResourceId` = `lr`.`id`', [])
            ->where('`lr`.`resourceType` = ?', editor_Models_Segment_MatchRateType::TYPE_TERM_COLLECTION)
            ->where('customerId IN (?)', $user->getCustomersArray());

        return array_column($this->db->fetchAll($s)->toArray(),'id');
    }
}
