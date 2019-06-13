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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */
/**
 * Term Instance
 * TODO refactor this class, so that code to deal with the term mark up will be moved in editor_Models_Segment_TermTag
 * 
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method string getTerm() getTerm()
 * @method void setTerm() setTerm(string $term)
 * @method string getMid() getMid()
 * @method void setMid() setMid(string $mid)
 * @method string getStatus() getStatus()
 * @method void setStatus() setStatus(string $status)
 * @method string getProcessStatus() getProcessStatus()
 * @method void setProcessStatus() setProcessStatus(string $processStatus)
 * @method string getDefinition() getDefinition()
 * @method void setDefinition() setDefinition(string $definition)
 * @method string getGroupId() getGroupId()
 * @method void setGroupId() setGroupId(string $groupId)
 * @method integer getLanguage() getLanguage()
 * @method void setLanguage() setLanguage(integer $languageId)
 * @method integer getCollectionId() getCollectionId()
 * @method void setCollectionId() setCollectionId(integer $id)
 * @method integer getTermEntryId() getTermEntryId()
 * @method void setTermEntryId() setTermEntryId(integer $id)
 * @method string getCreated() getCreated()
 * @method void setCreated() setCreated(string $created)
 * @method string getUpdated() getUpdated()
 * @method void setUpdated() setUpdated(string $updated)
 * @method string getUserGuid() getUserGuid()
 * @method void setUserGuid() setUserGuid(string $userGuid)
 * @method string getUserName() getUserName()
 * @method void setUserName() setUserName(string $userName)
 */
class editor_Models_Term extends ZfExtended_Models_Entity_Abstract {
    const PROCESS_STATUS_UNPROCESSED = 'unprocessed';
    const PROCESS_STATUS_PROV_PROCESSED = 'provisionallyProcessed';
    const PROCESS_STATUS_FINALIZED = 'finalized';
    
    const STAT_PREFERRED = 'preferredTerm';
    const STAT_ADMITTED = 'admittedTerm';
    const STAT_LEGAL = 'legalTerm';
    const STAT_REGULATED = 'regulatedTerm';
    const STAT_STANDARDIZED = 'standardizedTerm';
    const STAT_DEPRECATED = 'deprecatedTerm';
    const STAT_SUPERSEDED = 'supersededTerm';
    const STAT_NOT_FOUND = 'STAT_NOT_FOUND'; //Dieser Status ist nicht im Konzept definiert, sondern wird nur intern verwendet!

    const TRANSSTAT_FOUND = 'transFound';
    const TRANSSTAT_NOT_FOUND = 'transNotFound';
    const TRANSSTAT_NOT_DEFINED ='transNotDefined';
    
    const CSS_TERM_IDENTIFIER = 'term';
    
    /**
     * The above constants are needed in the application as list, since reflection usage is expensive we cache them here:
     * @var array
     */
    protected static $statusCache = [];
    
    protected $validatorInstanceClass = 'editor_Models_Validator_Term_Term';
    
    protected $statOrder = array(
        self::STAT_PREFERRED => 1,
        self::STAT_ADMITTED => 2,
        self::STAT_LEGAL => 2,
        self::STAT_REGULATED => 2,
        self::STAT_STANDARDIZED => 2,
        self::STAT_DEPRECATED => 3,
        self::STAT_SUPERSEDED => 3,
        self::STAT_NOT_FOUND => 99,
    );

    protected $dbInstanceClass = 'editor_Models_Db_Terms';
    
    protected static $groupIdCache = array();

    /**
     * @var editor_Models_Segment_TermTag
     */
    protected $tagHelper;
    
    public function __construct() {
        parent::__construct();
        $this->tagHelper = ZfExtended_Factory::get('editor_Models_Segment_TermTag');
    }
    
    /**
     * creates a new, unsaved term history entity
     * @return editor_Models_Term_History
     */
    public function getNewHistoryEntity() {
        $history = ZfExtended_Factory::get('editor_Models_Term_History');
        /* @var $history editor_Models_Term_History */
        $history->setTermId($this->getId());
        $history->setHistoryCreated(NOW_ISO);
        
        $fields = $history->getFieldsToUpdate();
        foreach ($fields as $field) {
            $history->__call('set' . ucfirst($field), array($this->get($field)));
        }
        return $history;
    }
    
    /**
     * returns for a termId the associated termentries by group 
     * @param array $collectionIds associated collections to the task
     * @param string $termId
     * @param int $langId
     * @return array
     */
    public function getTermGroupEntries(array $collectionIds, $termId,$langId) {
        $s1 = $this->db->getAdapter()->select()
        ->from(array('t1' => 'LEK_terms'),
                array('t1.groupId'))
        ->where('t1.id = ?', $termId)
        ->where('t1.collectionId IN(?)', $collectionIds);
        $s2 = $this->db->getAdapter()->select()
        ->from(array('t2' => 'LEK_terms'))
        ->where('t2.collectionId IN(?)', $collectionIds)
        ->where('t2.language = ? and t2.groupId = ('.$s1->assemble().')', $langId);
        return $this->db->getAdapter()->fetchAll($s2);
    }
    
    /**
     * returns an array with groupId and term to a given mid
     * @param string $mid
     * @param array $collectionIds
     * @return array
     */
    public function getTermAndGroupIdToMid($mid, $collectionIds) {
        if(!empty(self::$groupIdCache[$mid])) {
            return self::$groupIdCache[$mid];
        }
        $select = $this->db->select()
        ->from($this->db, array('groupId', 'term'))
        ->where('collectionId IN(?)', $collectionIds)
        ->where('mid = ?', $mid);
        $res = $this->db->fetchRow($select);
        if(empty($res)) {
            return $res;
        }
        self::$groupIdCache[$mid] = $res;
        return $res->toArray();
    }
    
    
    /**
     * Returns term-informations for $segmentId in $taskGuid.
     * Includes assoziated terms corresponding to the tagged terms
     * 
     * @param string $taskGuid
     * @param int $segmentId
     * @return array
     */
    public function getByTaskGuidAndSegment(string $taskGuid, int $segmentId) {
        if(empty($taskGuid) || empty($segmentId)) {
            return array();
        }
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        
        if (!$task->getTerminologie()) {
            return array();
        }
        
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $segment->load($segmentId);
        
        $termIds = $this->getTermMidsFromTaskSegment($task, $segment);
        
        if(empty($termIds)) {
            return array();
        }
        
        $assoc=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $assoc editor_Models_TermCollection_TermCollection */
        $collections=$assoc->getCollectionsForTask($task->getTaskGuid());
        if(empty($collections)) {
            return array();
        }
        $result = $this->getSortedTermGroups($collections, $termIds,$task->getSourceLang(),$task->getTargetLang());
        
        if(empty($result)) {
            return array();
        }
        return $this->sortTerms($result);
    }
    
    /**
     * Returns term-informations for $segmentId in termCollection.
     * Includes assoziated terms corresponding to the tagged terms
     * 
     * @param array $collectionIds
     * @param string $mid
     * @param array $languageIds 1-dim array with languageIds|default empty array; 
     *          if passed only terms with the passed languageIds are returned
     * @return 2-dim array (get term of first row like return[0]['term'])
     */
    public function getAllTermsOfGroupByMid(array $collectionIds, string $mid, $languageIds = array()) {
        $db = $this->db;
        $s = $db->select()
            ->from(array('t1' => $db->info($db::NAME)))
            ->join(array('t2' => $db->info($db::NAME)), 't1.groupId = t2.groupId', '')
            ->where('t1.collectionId IN(?)', $collectionIds)
            ->where('t2.collectionId IN(?)', $collectionIds)
            ->where('t2.mid = ?', $mid);
        $s->setIntegrityCheck(false);
        if(!empty($languageIds)) {
            $s->where('t1.language in (?)', $languageIds);
        }
        return $db->fetchAll($s)->toArray();
    }
    
    /**
     * Returns term-informations for a given group id
     * 
     * @param array $collectionIds
     * @param string $groupid
     * @param array $languageIds 1-dim array with languageIds|default empty array; 
     *          if passed only terms with the passed languageIds are returned
     * @return 2-dim array (get term of first row like return[0]['term'])
     */
    public function getAllTermsOfGroup(array $collectionIds, string $groupid, $languageIds = array()) {
        $db = $this->db;
        $s = $db->select()
            ->where('collectionId IN(?)', $collectionIds)
            ->where('groupId = ?', $groupid);
        if(!empty($languageIds)) {
            $s->where('language in (?)', $languageIds);
        }
        return $db->fetchAll($s)->toArray();
    }
    
    /***
     * check if the term with the same termEntry,collection but different termId exist
     * 
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getRestTermsOfGroup($groupId, $mid, $collectionId){
        $s = $this->db->select()
        ->where('groupId = ?', $groupId)
        ->where('mid != ?', $mid)
        ->where('collectionId = ?',$collectionId);
        return $this->db->fetchAll($s);
    }
    
    /**
     * returns all term mids of the given segment in a multidimensional array.
     * First level contains source or target (the fieldname)
     * Second level contains a list of arrays with the found mids and div tags,
     *   the div tag is needed for transfound check 
     * @param editor_Models_Task $task
     * @param editor_Models_Segment $segment
     * @return array
     */
    protected function getTermMidsFromTaskSegment(editor_Models_Task $task, editor_Models_Segment $segment) {
        
        $fieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        /* @var $fieldManager editor_Models_SegmentFieldManager */
        $fieldManager->initFields($task->getTaskGuid());
        
        //Currently only terminology is shown in the first fields see also TRANSLATE-461
        if ($task->getEnableSourceEditing()) {
            $sourceFieldName = $fieldManager->getEditIndex($fieldManager->getFirstSourceName());
            $sourceText = $segment->get($sourceFieldName);
        }
        else {
            $sourceFieldName = $fieldManager->getFirstSourceName();
            $sourceText = $segment->get($sourceFieldName);
        }
        
        $targetFieldName = $fieldManager->getEditIndex($fieldManager->getFirstTargetName());
        $targetText = $segment->get($targetFieldName);
        
        //tbxid should be sufficient as distinct identifier of term tags
        $getTermIdRegEx = '/<div[^>]+data-tbxid="([^"]*)"[^>]*>/';
        preg_match_all($getTermIdRegEx, $sourceText, $sourceMatches, PREG_SET_ORDER);
        preg_match_all($getTermIdRegEx, $targetText, $targetMatches, PREG_SET_ORDER);
        
        if (empty($sourceMatches) && empty($targetMatches)) {
            return array();
        }
        
        return array('source' => $sourceMatches, 'target' => $targetMatches);
    }
    
    /**
     * returns all term mids from given segment content (allows and returns also duplicated mids)
     * @param string $seg
     * @return array values are the mids of the terms in the string
     */
    public function getTermMidsFromSegment(string $seg) {
        return array_map(function($item) {
            return $item['mid'];
        }, $this->getTermInfosFromSegment($seg));
    }
    
    /**
     * returns mids and term flags (css classes) found in a string
     * @param string $seg
     * @return array 2D Array, first level are found terms, second level has key mid and key classes
     */
    public function getTermInfosFromSegment(string $seg) {
        return $this->tagHelper->getInfos($seg);
    }
    
    /**
     * Returns a multidimensional array.
     * 1. level: keys: groupId, values: array of terms grouped by groupId
     * 2. level: terms of group groupId
     * 
     * !! TODO: Sortierung der Gruppen in der Reihenfolge wie sie im Segment auftauchen (order by seg2term.id sollte hinreichend sein)
     * 
     * @param array $collectionIds term collections associated to the task
     * @param array $termIds as 2-dimensional array('source' => array(), 'target' => array())
     * @param $sourceLang
     * @param $sourceLang
     * 
     * @return array
     */
    protected function getSortedTermGroups(array $collectionIds, array $termIds, $sourceLang,$targetLang) {
        $sourceIds = array();
        $targetIds = array();
        $transFoundSearch = array();
        foreach ($termIds['source'] as $termId) {
            $sourceIds[] = $termId[1];
            $transFoundSearch[$termId[1]] = $termId[0];
        }
        foreach ($termIds['target'] as $termId) {
            $targetIds[] = $termId[1];
            $transFoundSearch[$termId[1]] = $termId[0];
        }
        
        $allIds = array_merge($sourceIds, $targetIds);
        $serialIds = '"'.implode('", "', $allIds).'"';
        
        $sql = $this->db->getAdapter()->select()
                ->from(array('t1' =>'LEK_terms'), array('t2.*'))
                ->distinct()
                ->joinLeft(array('t2' =>'LEK_terms'), 't1.groupId = t2.groupId', null)
                ->join(array('l' =>'LEK_languages'), 't2.language = l.id', 'rtl')
                ->where('t1.collectionId IN(?)', $collectionIds)
                ->where('t2.collectionId IN(?)', $collectionIds)
                ->where('t1.mid IN('.$serialIds.')')
                ->where('t1.language IN (?)',array($sourceLang,$targetLang))
                ->where('t2.language IN (?)',array($sourceLang,$targetLang));
       
        $terms = $this->db->getAdapter()->fetchAll($sql);
        
        $termGroups = array();
        foreach($terms as $term) {
            $term = (object) $term;
            
            settype($termGroups[$term->groupId], 'array');
            
            $term->used = in_array($term->mid, $allIds);
            $term->isSource = in_array($term->language, array($sourceLang));
            $term->transFound = false;
            if ($term->used) {
                $term->transFound = preg_match('/class="[^"]*transFound[^"]*"/', $transFoundSearch[$term->mid]);
            }
            
            $termGroups[$term->groupId][] = $term;
        }
        
        return $termGroups;
    }
    
    /**
     * 
     * @param string $mid
     * @param array $collectionIds
     * @return Zend_Db_Table_Row_Abstract | null
     */
    public function loadByMid(string $mid,array $collectionIds) {
        $s = $this->db->select(false);
        $s->from($this->db);
        $s->where('collectionId IN(?)', $collectionIds)->where('mid = ?', $mid);
        
        
        $this->row = $this->db->fetchRow($s);
        if(empty($this->row)){
            $this->notFound('#select', $s->assemble());
        }
        return $this->row;
    }
    
    /**
     * Sortiert die Terme innerhalb der Termgruppen:
     * @param array $termGroups
     * @return array
     */
    public function sortTerms(array $termGroups) {
        foreach($termGroups as $groupId => $group) {
            usort($group, array($this, 'compareTerms'));
            $termGroups[$groupId] = $group;
        }
        return $termGroups;
    }

    /**
     * Bewertet die Terme nach den folgenden Kriterien (siehe auch http://php.net/usort/)
     *  -- 1. Kriterium: Vorzugsbenennung vor erlaubter Benennung vor verbotener Benennung
     *  -- 2. Kriterium: In Quelle vorhanden
     *  -- 3. Kriterium: In Ziel vorhanden (damit ist die Original-Übersetzung gemeint, nicht die editierte Variante)
     *  -- 4. Kriterium: Alphanumerische Sortierung
     *  Zusammenhang Parameter und Return Values siehe usort $cmp_function
     *
     *  @param array $term1
     *  @param array $term2
     *  @return integer
     */
    protected function compareTerms($term1, $term2) {
        // return > 0 => t1 > t2
        // return = 0 => t1 = t2
        // return < 0 => t1 < t2
        $term1=is_array($term1) ? (object)$term1 : $term1;
        $term2=is_array($term2) ? (object)$term2 : $term2;
        $status = $this->compareTermStatus($term1->status, $term2->status);
        if($status !== 0) {
            return $status;
        }
        
        $isSource=0;
        if(isset($term1->isSource)){
            $isSource = $this->compareTermLangUsage($term1->isSource, $term2->isSource);
        }
        
        if($isSource !== 0) {
            return $isSource;
        }
        
        //Kriterium 4 - alphanumerische Sortierung:
        return strcmp(mb_strtolower($term1->term), mb_strtolower($term2->term));
    }

    /**
     * Vergleicht die Term Status
     * @param string $status1
     * @param string $status2
     * @return integer
     */
    protected function compareTermStatus($status1, $status2) {
        //wenn beide stati gleich, dann wird kein weiterer Vergleich benötigt
        if($status1 === $status2) {
            return 0;
        }
        if(empty($this->statOrder[$status1])){
            $status1 = self::STAT_NOT_FOUND;
        }
        if(empty($this->statOrder[$status2])){
            $status2 = self::STAT_NOT_FOUND;
        }

        //je kleiner der statOrder, desto höherwertiger ist der Status!
        //Da Höherwertig aber bedeutet, dass es in der Sortierung weiter oben erscheinen soll,
        //ist der Höherwertige Status im numerischen Wert kleiner!
        if($this->statOrder[$status1] < $this->statOrder[$status2]) {
            return -1; //status1 ist höherwertiger, da der statOrdner kleiner ist
        }
        return 1; //status2 ist höherwertiger
    }

    /**
     * Vergleicht die Term auf Verwendung in Quell oder Zielspalte
     * @param string $isSource1
     * @param string $isSource2
     * @return integer
     */
    protected function compareTermLangUsage($isSource1, $isSource2) {
        //Verwendung in Quelle ist höherwertiger als in Ziel (Kriterium 2 und 3)
        if($isSource1 === $isSource2) {
            return 0;
        }
        if($isSource1) {
            return 1;
        }
        return -1;
    }
    
    /**
     * @param editor_Models_Task $task
    //FIXME editor_Models_Export_Tbx durch entsprechendes Interface ersetzen
     * @param editor_Models_Export_Terminology_Tbx $exporteur
     */
    public function export(editor_Models_Task $task, editor_Models_Export_Terminology_Tbx $exporteur) {
        $languageModel=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $languageModel editor_Models_Languages */
        
        $assoc=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $assoc editor_Models_TermCollection_TermCollection */
        $collectionIds=$assoc->getCollectionsForTask($task->getTaskGuid());
        
        if(empty($collectionIds)){
            return null;
        }
        
        //get source and target language fuzzies
        $langs=[];
        $langs=array_merge($langs,$languageModel->getFuzzyLanguages($task->getSourceLang()));
        $langs=array_merge($langs,$languageModel->getFuzzyLanguages($task->getTargetLang()));
        if($task->getRelaisLang() > 0) {
            $langs=array_merge($langs,$languageModel->getFuzzyLanguages($task->getRelaisLang()));
        }
        $langs=array_unique($langs);
        
        $data=$this->loadSortedByCollectionAndLanguages($collectionIds, $langs);
        if(!$data) {
            return null;
        }
        $exporteur->setData($data);
        return $exporteur->export();
    }
    
    /***
     * Load term and attribute proposals yunger as $youngerAs date within the given collection
     * @param string $youngerAs
     * @param array $collectionId
     */
    public function loadProposalExportData(string $youngerAs,array $collectionIds){
        //if no date is set, se to current
        if(empty($youngerAs)){
            $youngerAs=date('Y-m-d H:i:s');
        }
        if(empty($collectionIds)){
            $termCollection=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
            /* @var $termCollection editor_Models_TermCollection_TermCollection */
            $collectionIds=$termCollection->getCollectionForLogedUser();
        }
        $sql="SELECT
                t.termEntryId as 'term-termEntryId',
                t.definition as 'term-definition',
                l.langName as 'term-language',
                t.id as 'term-Id',
                t.term as 'term-term',
                t.processStatus as 'term-processStatus',
                t.userName as 'term-lastEditor',
                t.updated as 'term-lastEditedDate',
                tp.id as 'termproposal-id',
                tp.term as 'termproposal-term',
                tp.created as 'termproposal-lastEditedDate',
                tp.userName as 'termproposal-lastEditor',
                ta.id as 'attribute-id',
                ta.value as 'attribute-value',
                ta.updated as 'attribute-lastEditedDate',
                ta.userName as 'attribute-lastEditor',
                tap.id as 'attributeproposal-id',
                tap.value as 'attributeproposal-value',
                tap.created as 'attributeproposal-lastEditedDate',
                tap.userName as 'attributeproposal-lastEditor'
                    FROM
                    LEK_terms t
					LEFT OUTER JOIN
                    LEK_term_proposal tp ON tp.termId = t.id
                    INNER JOIN LEK_languages l ON t.language=l.id 
                    LEFT OUTER JOIN
                    LEK_term_attributes ta ON ta.termId=t.id AND ta.id IN (
							select attributeId from LEK_term_attribute_proposal
							inner join LEK_term_attributes on LEK_term_attributes.id=LEK_term_attribute_proposal.attributeId
							where LEK_term_attribute_proposal.value is not null or LEK_term_attribute_proposal.value!=''
                            and LEK_term_attributes.termId=t.id
                    )
                    LEFT OUTER JOIN
                    LEK_term_attribute_proposal tap ON tap.attributeId = ta.id
                where 
                t.created > DATE_SUB(?, INTERVAL 5 MINUTE) 
                and t.collectionId IN(?)
                and (tp.term is not null or tap.value is not null)
				order by t.groupId,t.term";
        $resultArray=$this->db->getAdapter()->query($sql,[$youngerAs,implode(',', $collectionIds)])->fetchAll();
        if(empty($resultArray)){
            return [];
        }
        return $this->groupProposalExportData($resultArray);
    }
    
    /***
     * Load terms in given collection and languages. The returned data will be sorted by groupId,language and id
     * 
     * @param array $collectionIds
     * @param array $langs
     * @return NULL|Zend_Db_Table_Rowset_Abstract
     */
    public function loadSortedByCollectionAndLanguages(array $collectionIds,$langs=array()){
        $s = $this->db->select()
        ->where('collectionId IN(?)', $collectionIds);
        if(!empty($langs)){
            $s->where('language in (?)', $langs);
        }
        $s->order('groupId ASC')
        ->order('language ASC')
        ->order('id ASC');
        $data = $this->db->fetchAll($s);
        if($data->count() == 0) {
            return null;
        }
        return $data;
    }
    
    /***
     * Get term by collection, language and term
     * 
     * @param mixed $collectionId
     * @param mixed $languageId
     * @param mixed $termValue
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function loadByCollectionLanguageAndTermValue($collectionId,$languageId,$termValue){
        $s = $this->db->select()
            ->where('collectionId = ?', $collectionId)
            ->where('language = ?', $languageId)
            ->where('term = ?', $termValue);
        return $this->db->fetchAll($s);
    }
    
    /***
     * Check if the given term entry exist in the collection
     * @param mixed $termEntry
     * @param int $collectionId
     * @return boolean
     */
    public function isTermEntryInCollection($termEntry,$collectionId){
        $s = $this->db->select()
        ->where('groupId = ?', $termEntry)
        ->where('collectionId = ?', $collectionId);
        $terms=$this->db->fetchAll($s);
        return $terms->count()>0;
    }
    
    /***
     * Check if the term should be updated for the term collection
     * 
     * @param mixed $termEntry
     * @param mixed $termId
     * @param mixed $collectionId
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function isUpdateTermForCollection($termEntry,$termId,$collectionId){
        $s = $this->db->select()
        ->where('groupId = ?', $termEntry)
        ->where('mid = ?', $termId)
        ->where('collectionId = ?', $collectionId);
        return $this->db->fetchAll($s);
    }
    
    /**
     * Find term in collection by given language and term value
     * @param string $termText
     * @param integer $languageId optional, if omitted use internal value
     * @param integer $termCollection optional, if omitted use internal value
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function findTermInCollection(string $termText, int $languageId = null, int $termCollection = null){
        $s = $this->db->select()
        ->where('term = ?', $termText)
        ->where('language = ?', $languageId ?? $this->getLanguage())
        ->where('collectionId = ?',$termCollection ?? $this->getCollectionId());
        return $this->db->fetchAll($s);
    }
    
    /**
     * Search terms in the term collection with the given search string and languages.
     * 
     * @param string $queryString
     * @param array $languages
     * @param array $collectionIds
     * @param mixed $limit
     * @param array $processStats
     * 
     * @return array
     */
    public function searchTermByLanguage($queryString,$languages,$collectionIds,$limit=null,$processStats){
        //if wildcards are used, adopt them to the mysql needs
        $queryString=str_replace("*","%",$queryString);
        $queryString=str_replace("?","_",$queryString);
        
        //when limit is provided -> autocomplete search
        if($limit){
            $queryString=$queryString.'%';
        }
        
        $tableTerm = $this->db->info($this->db::NAME);
        $tableProposal = (new editor_Models_Db_Term_Proposal())->info($this->db::NAME);
        $s = $this->db->select()
        ->setIntegrityCheck(false)
        ->from($tableTerm, array('definition','groupId', 'term as label','id as value','term as desc', 'collectionId', 'termEntryId'))
        ->joinLeft($tableProposal, '`'.$tableTerm.'`.`id` = `'.$tableProposal.'`.`termId`', ['term', 'id', 'created'])
        ->where('lower(`'.$tableTerm.'`.term) like lower(?) COLLATE utf8_bin',$queryString)
        ->where('`'.$tableTerm.'`.language IN(?)',explode(',', $languages))
        ->where('`'.$tableTerm.'`.collectionId IN(?)',$collectionIds)
        ->where('`'.$tableTerm.'`.processStatus IN(?)',$processStats)
        ->order($tableTerm.'.term asc');
        if($limit){
            $s->limit($limit);
        }
        
        return $this->db->fetchAll($s)->toArray();
    }
    
    /***
     * Find term attributes in the given term entry (lek_terms groupId)
     * 
     * @param string $groupId
     * @param array $collectionIds
     * @return array
     */
    public function searchTermAttributesInTermentry($groupId,$collectionIds){
        $attCols=array(
                'LEK_term_attributes.labelId as labelId',
                'LEK_term_attributes.id AS attributeId',
                'LEK_term_attributes.parentId AS parentId',
                'LEK_term_attributes.internalCount AS internalCount',
                'LEK_term_attributes.name AS name',
                'LEK_term_attributes.attrType AS attrType',
                'LEK_term_attributes.attrTarget AS attrTarget',
                'LEK_term_attributes.attrId AS attrId',
                'LEK_term_attributes.attrLang AS attrLang',
                'LEK_term_attributes.value AS attrValue',
                'LEK_term_attributes.created AS attrCreated',
                'LEK_term_attributes.updated AS attrUpdated',
                'LEK_term_attributes.attrDataType AS attrDataType',
                'LEK_term_attributes.processStatus AS attrProcessStatus',
                new Zend_Db_Expr('"termAttribute" as attributeOriginType')//this is needed as fixed value
        );
        
        $cols=[
            'definition',
            'groupId',
            'term as label',
            'term as term',//for consistency
            'id as value',
            'term as desc',
            'status as termStatus',
            'processStatus as processStatus',
            'id as termId',
            'collectionId',
            'language as languageId'
        ];
        
        
        $s=$this->db->select()
        ->setIntegrityCheck(false)
        ->from($this->db,$cols)
        ->joinLeft('LEK_term_attributes', 'LEK_term_attributes.termId = LEK_terms.id',$attCols)
        ->joinLeft('LEK_term_attributes_label', 'LEK_term_attributes_label.id = LEK_term_attributes.labelId',['LEK_term_attributes_label.labelText as headerText'])
        ->join('LEK_languages', 'LEK_terms.language=LEK_languages.id',['LEK_languages.rfc5646 AS language']);
        
        if($this->isProposableAllowed()){
            $s->joinLeft('LEK_term_proposal', 'LEK_term_proposal.termId = LEK_terms.id',[
                'LEK_term_proposal.term as proposalTerm',
                'LEK_term_proposal.id as proposalId'
            ]);
        }
        
        $attribute=ZfExtended_Factory::get('editor_Models_Term_Attribute');
        /* @var $attribute editor_Models_Term_Attribute */
        
        if($attribute->isProposableAllowed()){
            $s->joinLeft('LEK_term_attribute_proposal', 'LEK_term_attribute_proposal.attributeId = LEK_term_attributes.id',[
                'LEK_term_attribute_proposal.value as proposalAttributeValue',
                'LEK_term_attribute_proposal.id as proposalAttributelId',
            ]);
        }
        
        $s->where('groupId=?',$groupId)
        ->where('LEK_terms.collectionId IN(?)',$collectionIds)
        ->order('label');
        return $this->db->fetchAll($s)->toArray();
    }
    
    /***
     * Remove old terms by given date.
     * The term attributes also will be removed.
     * 
     * @param array $collectionIds
     * @param string $olderThan
     * @return boolean
     */
    public function removeOldTerms(array $collectionIds, $olderThan){
       return $this->db->delete([
           'updated < ?' => $olderThan,
           'collectionId in (?)' => $collectionIds,
       ])>0;
    }

    
    /***
     * Update language assoc for given collections. The langages are merged from exsisting terms per collection.
     * @param array $collectionIds
     */
    public function updateAssocLanguages(array $collectionIds=null){
        $s=$this->db->select()
        ->from(array('t' =>'LEK_terms'), array('t.language','t.collectionId'))
        ->join(array('l' =>'LEK_languages'), 't.language = l.id', 'rfc5646');
        
        if(!empty($collectionIds)){
            $s->where('t.collectionId IN(?)',$collectionIds);
        }
        
        $s->group('t.collectionId')->group('t.language')->setIntegrityCheck(false);
        
        $ret=$this->db->fetchAll($s)->toArray();
        
        $data=[];
        foreach($ret as $lng) {
            if(!isset($data[$lng['collectionId']])){
                $data[$lng['collectionId']]=[];
            }
            array_push($data[$lng['collectionId']], $lng);
        }
        
        foreach($data as $key=>$value) {
            $alreadyProcessed = array();
            //the term collection contains terms with only one language
            $isSingleCombination=count($value)==1;
            foreach ($value as $x) {
                foreach ($value as $y) {
                    //keep track of what is already processed
                    $combination = array($x['language'], $y['language']);
                    
                    //it is not the same number or single language combination and thay are not already processed
                    if (($x['language'] === $y['language'] && !$isSingleCombination) || in_array($combination, $alreadyProcessed)) {
                        continue;
                    }
                    //Add it to the list of what you've already processed
                    $alreadyProcessed[] = $combination;
                    
                    //save the language combination
                    $model=ZfExtended_Factory::get('editor_Models_LanguageResources_Languages');
                    /* @var $model editor_Models_LanguageResources_Languages */
                    
                    $model->setSourceLang($x['language']);
                    $model->setSourceLangRfc5646($x['rfc5646']);
                    
                    $model->setTargetLang($y['language']);
                    $model->setTargetLangRfc5646($y['rfc5646']);
                    
                    $model->setLanguageResourceId($key);
                    $model->save();
                    
                }
            }
        }
    }
    
    /***
     * Get all definitions in the given entryIds. The end results will be grouped by $entryIds as a key. 
     * @param array $entryIds
     * @return array
     */
    public function getDeffinitionsByEntryIds(array $entryIds){
        if(empty($entryIds)){
            return array();
        }
        $s=$this->db->select()
        ->where('termEntryId IN(?)',$entryIds);
        $return=$this->db->fetchAll($s)->toArray();
        
        if(empty($return)){
            return array();
        }

        //group the definitions by termEntryId as a key
        $result=array();
        foreach ($return as $r) {
            if(!isset($result[$r['termEntryId']])){
                $result[$r['termEntryId']]=array();
            }
            
            if(!in_array($r['definition'], $result[$r['termEntryId']]) && !empty($r['definition'])){
                $result[$r['termEntryId']][]=$r['definition'];
            }
        }
        
        return $result;
    }
    
    public function getDataObject() {
        $result=parent::getDataObject();
        $result->termId=$result->id;
        $result->proposable=$this->isProposableAllowed();
        return $result;
    }
    
    /***
     * Check if the term is proposable.
     * It is proposable when the term status is not unproccessed and the user is allowed for term proposal operation
     * @param string $status
     * @return boolean
     */
    public function isProposable(string $status=null){
        if(empty($status)){
            $status=$this->getStatus();
        }
        return $status!==self::PROCESS_STATUS_UNPROCESSED && $this->isProposableAllowed();
    }
    
    /***
     * It is proposable when the user is allowed for term proposal operation
     * @return boolean
     */
    public function isProposableAllowed(){
        $user=ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        return $user->isAllowed('editor_term','proposeOperation');
    }
    
    /***
     * Group the term and attribute proposal data for the export
     * @param array $data
     * @return array
     */
    protected function groupProposalExportData(array $data){
        $returnResult=[];
        $termId=null;
        $tmpTerm=[];
        $newTermInsert=true;
        //clange cell color by value on the excel export callback
        $changeMyCollorTag='<changemycolortag>';
        foreach ($data as $row) {
            
            $newTermInsert=$termId != $row['term-Id'] && !empty($row['termproposal-term']);
            
            $tmpTerm['termEntryId']=$row['term-termEntryId'];
            $tmpTerm['definition']=$row['term-definition'];
            $tmpTerm['language']=$row['term-language'];
            $tmpTerm['termId']=$row['term-Id'];
            $tmpTerm['term']=$row['term-term'];
            $tmpTerm['termProposal']='';
            $tmpTerm['processStatus']=$row['term-processStatus'];
            $tmpTerm['attribute']=$row['attribute-value'];
            $tmpTerm['attributeProposal']='';
            $tmpTerm['lastEditor']=$changeMyCollorTag.$row['term-lastEditor'];
            $tmpTerm['lastEditedDate']=$changeMyCollorTag.$row['term-lastEditedDate'];
            
            //if the proposal exist, set the change color and last editor for the proposal
            if(!empty($row['termproposal-term'])){
                $tmpTerm['termProposal']=$changeMyCollorTag.$row['termproposal-term'];
                $tmpTerm['lastEditor']=$changeMyCollorTag.$row['termproposal-lastEditor'];
                $tmpTerm['lastEditedDate']=$changeMyCollorTag.$row['termproposal-lastEditedDate'];
            }
            
            //if the attribute proposal is set, set the change color and last editor for the attribute proposal
            if(!empty($row['attributeproposal-value'])){
                //if also the term proposal exist for new term row, insert the term proposal with change color value and last editor
                if($newTermInsert){
                    $tmpTerm['attribute']='';
                    $tmpTerm['attributeProposal']='';
                    $returnResult[]=$tmpTerm;
                    $newTermInsert=false;
                }
                $tmpTerm['termProposal']=str_replace($changeMyCollorTag,'',$row['termproposal-term']);
                $tmpTerm['attribute']=$row['attribute-value'];
                $tmpTerm['attributeProposal']=$changeMyCollorTag.$row['attributeproposal-value'];
                $tmpTerm['lastEditor']=$changeMyCollorTag.$row['attributeproposal-lastEditor'];
                $tmpTerm['lastEditedDate']=$changeMyCollorTag.$row['attributeproposal-lastEditedDate'];
            }
            $returnResult[]=$tmpTerm;
            $tmpTerm=[];
            
            $termId=$row['term-Id'];
        }
        return $returnResult;
    }
    
    /**
     * returns a map CONSTNAME => value of all term status
     * @return array
     */
    static public function getAllStatus() {
        self::initConstStatus();
        return self::$statusCache['status'];
    }
    
    /**
     * returns a map CONSTNAME => value of all term process-status
     * @return array
     */
    static public function getAllProcessStatus() {
        self::initConstStatus();
        return self::$statusCache['processStatus'];
    }
    
    /**
     * returns a map CONSTNAME => value of all translation status
     * @return array
     */
    static public function getAllTransStatus() {
        self::initConstStatus();
        return self::$statusCache['translation'];
    }
    
    /**
     * creates a internal list of the status constants
     */
    static protected function initConstStatus() {
        if(!empty(self::$statusCache)) {
            return;
        }
        self::$statusCache = [
            'status' => [],
            'translation' => [],
            'processStatus' => [],
        ];
        $refl = new ReflectionClass(__CLASS__);
        $constants = $refl->getConstants();
        foreach($constants as $key => $val) {
            if(strpos($key, 'STAT_') === 0) {
                self::$statusCache['status'][$key] = $val;
            }
            if(strpos($key, 'TRANSSTAT_') === 0) {
                self::$statusCache['translation'][$key] = $val;
            }
            if(strpos($key, 'PROCESS_STATUS_') === 0) {
                self::$statusCache['processStatus'][$key] = $val;
            }
        }
    }
}
