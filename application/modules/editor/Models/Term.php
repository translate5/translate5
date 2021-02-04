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
    protected function getSortedTermGroups(array $collectionIds, array $termIds, $sourceLang, $targetLang) {
        $lang = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $lang editor_Models_Languages */
        $sourceLanguages = $lang->getFuzzyLanguages($sourceLang);
        $targetLanguages = $lang->getFuzzyLanguages($targetLang);
        $allLanguages = array_unique(array_merge($sourceLanguages, $targetLanguages));
        $sourceIds = array_column($termIds['source'], 1);
        $targetIds = array_column($termIds['target'], 1);
        $transFoundSearch = array_column($termIds['source'], 0, 1) + array_column($termIds['target'], 0, 1);
        $allIds = array_merge($sourceIds, $targetIds);
        
        $sql = $this->db->getAdapter()->select()
                ->from(array('t1' =>'LEK_terms'), array('t2.*'))
                ->distinct()
                ->joinLeft(array('t2' =>'LEK_terms'), 't1.termEntryId = t2.termEntryId AND t1.collectionId = t2.collectionId', null)
                ->join(array('l' =>'LEK_languages'), 't2.language = l.id', 'rtl')
                ->where('t1.collectionId IN(?)', $collectionIds)
                //->where('t2.collectionId IN(?)', $collectionIds)
                ->where('t1.mid IN(?)', $allIds)
                ->where('t1.language IN (?)', $allLanguages)
                ->where('t2.language IN (?)', $allLanguages);
        
        $terms = $this->db->getAdapter()->fetchAll($sql);
        
        $termGroups = array();
        foreach($terms as $term) {
            $term = (object) $term;
            
            settype($termGroups[$term->groupId], 'array');
            
            $term->used = in_array($term->mid, $allIds);
            $term->isSource = in_array($term->language, $sourceLanguages);
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
    public function loadByMid(string $mid, array $collectionIds) {
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
     * exports all terms of all termCollections associated to the task in the task's languages.
     * @param editor_Models_Task $task
     */
    public function exportForTagging(editor_Models_Task $task) {
        $languageModel=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $languageModel editor_Models_Languages */
        
        $assoc=ZfExtended_Factory::get('editor_Models_TermCollection_TermCollection');
        /* @var $assoc editor_Models_TermCollection_TermCollection */
        $collectionIds=$assoc->getCollectionsForTask($task->getTaskGuid());
        
        if(empty($collectionIds)) {
            //No term collection assigned to task although tasks terminology flag is true.
            // This is normally not possible, since the terminology flag in the task is maintained on TC task assoc changes via API
            throw new editor_Models_Term_TbxCreationException('E1113', [
                'task' => $task
            ]);
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
            //The associated collections don't contain terms in the languages of the task.
            // Should not be, should be checked already on assignment of collection to task.
            // Colud happen when all terms of a language are removed from a TermCollection via term import after associating that term collection to a task.
            throw new editor_Models_Term_TbxCreationException('E1114', [
                'task' => $task,
                'collectionIds' => $collectionIds,
                'languageIds' => $langs,
            ]);
        }
        
        $exporteur = ZfExtended_Factory::get('editor_Models_Export_Terminology_Tbx');
        /* @var $exporteur editor_Models_Export_Terminology_Tbx */
        $exporteur->setData($data);
        $result = $exporteur->export();
        if(empty($result)) {
            //collected terms could not be converted to XML.
            throw new editor_Models_Term_TbxCreationException('E1115', [
                'task' => $task,
                'collectionIds' => $collectionIds,
                'languageIds' => $langs,
            ]);
        }
        return $result;
    }
    
    /***
     * Export term and term attribute proposals in excel file.
     * When no path is provided, redirect the output to a client's web browser (Excel)
     *
     * @param array $rows
     * @param string $path: the path where the excel document will be saved
     */
    public function exportProposals(array $rows,string $path=null){
        $excel = ZfExtended_Factory::get('ZfExtended_Models_Entity_ExcelExport');
        /* @var $excel ZfExtended_Models_Entity_ExcelExport */
        
        // set property for export-filename
        $excel->setProperty('filename', 'Term and term attributes proposals');
        
        $t = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $t ZfExtended_Zendoverwrites_Translate */
        
        // sample label-translations
        $excel->setLabel('termEntryId', $t->_('Eintrag'));
        $excel->setLabel('definition', $t->_('Definition'));
        $excel->setLabel('language', $t->_('Sprache'));
        $excel->setLabel('termId', $t->_('Term-Id'));
        $excel->setLabel('term', $t->_('Term'));
        $excel->setLabel('termProposal', $t->_('Änderung zu bestehendem Term'));
        $excel->setLabel('processStatus', $t->_('Prozess-Status'));
        $excel->setLabel('attributeName', $t->_('Attributs-Schlüssel'));
        $excel->setLabel('attribute', $t->_('Attributs-Wert'));
        $excel->setLabel('attributeProposal', $t->_('Änderung zu bestehendem Attributs-Wert'));
        $excel->setLabel('lastEditor', $t->_('Letzter Bearbeiter'));
        $excel->setLabel('lastEditedDate', $t->_('Bearbeitungsdatum'));
        
        
        $autosizeCells=function($phpExcel) use ($excel){
            foreach ($phpExcel->getWorksheetIterator() as $worksheet) {
                
                $phpExcel->setActiveSheetIndex($phpExcel->getIndex($worksheet));
                
                $sheet = $phpExcel->getActiveSheet();
                
                //the highes column based on the current row columns
                $highestColumn='M';
                foreach(range('A',$highestColumn) as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
                
                
                $highestColumnIndex = $excel->columnIndexFromString($highestColumn);
                
                // expects same number of row records for all columns
                $highestRow = $worksheet->getHighestRow();
                
                for($col = 0; $col < $highestColumnIndex; $col++)
                {
                    // if you do not expect same number of row records for all columns
                    // get highest row index for each column
                    // $highestRow = $worksheet->getHighestRow();
                    
                    for ($row = 1; $row <= $highestRow; $row++)
                    {
                        $cell = $worksheet->getCellByColumnAndRow($col, $row);
                        if(strpos($cell->getValue(), '<changemycolortag>') !== false){
                            $cell->setValue(str_replace('<changemycolortag>','',$cell->getValue()));
                            $sheet->getStyle($cell->getCoordinate())->getFill()->setFillType('solid')->getStartColor()->setRGB('f9f25c');
                        }
                    }
                }
            }
        };
        
        //if the path is provided, save the excel into the given path location
        if(!empty($path)){
            $excel->loadArrayData($rows);
            $autosizeCells($excel->getSpreadsheet());
            $excel->saveToDisc($path);
            return;
        }
        
        //send the excel to browser download
        $excel->simpleArrayToExcel($rows,$autosizeCells);
    }
    
    /***
     * Load all term and attribute proposals, or if second parameter is given load only proposals younger as $youngerAs date within the given collection(s)
     * @param array $collectionId
     * @param string $youngerAs optional, if omitted all proposals are loaded
     */
    public function loadProposalExportData(array $collectionIds, string $youngerAs = ''){
        $adapter=$this->db->getAdapter();
        $bindParams = [];
        $termYoungerSql = $attrYoungerSql = '';
        if(!empty($youngerAs)) {
            $bindParams[] = $youngerAs;
            $bindParams[] = $youngerAs;
            $termYoungerSql = ' and (t.created >=? || tp.created >= ?)';
            $attrYoungerSql = ' and (ta.created >=? || tap.created >=?)';
        }
        //Info: why collection ids is not in bindParams
        //binding multiple values to single param is not posible with $adapter->query . For more info see PDOStatement::execute
        $termSql="SELECT
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
                    null as 'attribute-id',
                    null as 'attribute-name',
                    null as 'attribute-value',
                    null as 'attribute-lastEditedDate',
                    null as 'attribute-lastEditor',
                    null as 'attributeproposal-id',
                    null as 'attributeproposal-value',
                    null as 'attributeproposal-lastEditedDate',
                    null as 'attributeproposal-lastEditor'
                    FROM LEK_terms t
                    LEFT OUTER JOIN LEK_term_proposal tp ON tp.termId = t.id
                    INNER JOIN LEK_languages l ON t.language=l.id
                WHERE ".$adapter->quoteInto('t.collectionId IN(?)',$collectionIds)
                .$termYoungerSql."
                AND (tp.term is not null or t.processStatus='unprocessed')
                ORDER BY t.groupId,t.term";
        
        $termResult=$adapter->query($termSql,$bindParams)->fetchAll();
        
        $attributeSql="SELECT
                        ta.id as 'attribute-id',
                        ta.termId as 'term-Id',
                        ta.termEntryId as 'attribute-termEntryId',
                        ta.name as 'attribute-name',
                        ta.value as 'attribute-value',
                        ta.updated as 'attribute-lastEditedDate',
                        ta.userName as 'attribute-lastEditor',
                        ta.processStatus as 'attribute-processStatus',
                        l.langName as 'term-language',
                        tap.id as 'attributeproposal-id',
                        tap.value as 'attributeproposal-value',
                        tap.created as 'attributeproposal-lastEditedDate',
                        tap.userName as 'attributeproposal-lastEditor',
                        t.termEntryId as 'term-termEntryId',
                        t.definition as 'term-definition',
                        t.id as 'term-Id',
                        t.term as 'term-term',
                        t.processStatus as 'term-processStatus',
                        t.userName as 'term-lastEditor',
                        t.updated as 'term-lastEditedDate',
                        tp.id as 'termproposal-id',
                        tp.term as 'termproposal-term',
                        tp.created as 'termproposal-lastEditedDate',
                        tp.userName as 'termproposal-lastEditor'
                    FROM LEK_term_attributes ta
                        LEFT OUTER JOIN LEK_term_attribute_proposal tap ON tap.attributeId = ta.id
                        LEFT OUTER JOIN LEK_terms t on ta.termId=t.id
                        LEFT OUTER JOIN LEK_term_proposal tp on tp.termId=t.id
                        LEFT OUTER JOIN LEK_languages l ON t.language=l.id
                    WHERE ".$adapter->quoteInto('ta.collectionId IN(?)',$collectionIds).
                    $attrYoungerSql."
                    AND (tap.value is not null or ta.processStatus='unprocessed')
                    ORDER BY ta.termEntryId,ta.termId";
        
        $attributeResult=$adapter->query($attributeSql,$bindParams)->fetchAll();
        
        //merge term proposals with term attributes and term entry attributes proposals
        $resultArray=array_merge($termResult,$attributeResult);
        
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
     * Returns all terms of the given $searchTerms that don't exist in
     * any of the given collections.
     * @param array $searchTerms with objects {'text':'abc', 'id':123}
     * @param array $collectionIds
     * @param array $language
     * @return array $nonExistingTerms with objects {'text':'abc', 'id':123}
     */
    public function getNonExistingTermsInAnyCollection(array $searchTerms,array $collectionIds,array $language){
        $nonExistingTerms = [];
        if(empty($searchTerms) || empty($collectionIds) || empty($language)){
            return $nonExistingTerms;
        }
        foreach ($searchTerms as $term) {
            $s = $this->db->select()
            ->where('term = ?', $term->text)
            ->where('collectionId IN(?)', $collectionIds)
            ->where('language IN (?)',$language);
            $terms = $this->db->fetchAll($s);
            if ($terms->count() == 0) {
                $nonExistingTerms[] = $term;
            }
        }
        return $nonExistingTerms;
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
        
        $isProposableAllowed=$this->isProposableAllowed();
        
        //remove the unprocessed status if the user is not allowed for proposals
        if(!$isProposableAllowed){
            $processStats = array_diff($processStats,[self::PROCESS_STATUS_UNPROCESSED]);
        }
        
        $tableTerm = $this->db->info($this->db::NAME);
        $tableProposal = (new editor_Models_Db_Term_Proposal())->info($this->db::NAME);
        $s = $this->db->select()
        ->setIntegrityCheck(false)
        ->from($tableTerm, ['term as label','id as value','term as desc','definition','groupId','collectionId','termEntryId','language'])
        ->where('lower(`'.$tableTerm.'`.term) like lower(?) COLLATE utf8mb4_bin',$queryString)
        ->where('`'.$tableTerm.'`.language IN(?)',explode(',', $languages))
        ->where('`'.$tableTerm.'`.collectionId IN(?)',$collectionIds)
        ->where('`'.$tableTerm.'`.processStatus IN(?)',$processStats);
        $s->order($tableTerm.'.term asc');
        if($limit){
            $s->limit($limit);
        }
        
        if(!$isProposableAllowed || !in_array(self::PROCESS_STATUS_UNPROCESSED, $processStats)){
            return $this->db->fetchAll($s)->toArray();
        }
        
        //if proposal is allowed, search also in the proposal table for results
        $tableProposal = (new editor_Models_Db_Term_Proposal())->info($this->db::NAME);
        $sp = $this->db->select()
        ->setIntegrityCheck(false)
        ->from($tableProposal, ['term as label','termId as value','term as desc'])
        ->joinInner($tableTerm, '`'.$tableTerm.'`.`id` = `'.$tableProposal.'`.`termId`', ['definition','groupId','collectionId', 'termEntryId','language'])
        ->where('lower(`'.$tableProposal.'`.term) like lower(?) COLLATE utf8mb4_bin',$queryString)
        ->where('`'.$tableTerm.'`.language IN(?)',explode(',', $languages))
        ->where('`'.$tableTerm.'`.collectionId IN(?)',$collectionIds)
        ->order($tableTerm.'.term asc');
        if($limit){
            $sp->limit($limit);
        }
        $sql='('.$s->assemble().') UNION ('.$sp->assemble().')';
        return $this->db->getAdapter()->query($sql)->fetchAll();
    }
    
    /***
     * Find term attributes in the given term entry (lek_terms groupId)
     *
     * @param string $termEntryId
     * @param array $collectionIds
     * @return array
     */
    //TODO: update the references
    public function searchTermAttributesInTermentry($termEntryId,$collectionIds){
        $s=$this->getSearchTermSelect();
        $s->where('LEK_terms.termEntryId=?',$termEntryId)
        ->where('LEK_terms.collectionId IN(?)',$collectionIds)
        ->order('LEK_languages.rfc5646')
        ->order('LEK_terms.term')
        ->order('LEK_terms.id');
        return $this->db->fetchAll($s)->toArray();
    }
    
    /***
     * Find the term and the term attributes by given term id
     * @param int $termId
     * @return array
     */
    public function findTermAndAttributes(int $termId){
        $s=$this->getSearchTermSelect();
        $s->where('LEK_terms.id=?',$termId)
        ->order('LEK_languages.rfc5646')
        ->order('LEK_terms.term');
        return $this->db->fetchAll($s)->toArray();
    }
    
    /***
     * Remove terms where the updated date is older than the given one.
     *
     * @param array $collectionIds
     * @param string $olderThan
     * @return boolean
     */
    public function removeOldTerms(array $collectionIds, $olderThan){
        //get all terms in the collection older than the date
        $s = $this->db->select()
        ->setIntegrityCheck(false)
        ->from(['t'=>'LEK_terms'],['t.id'])
        ->joinLeft(['p'=>'LEK_term_proposal'],'p.termId=t.id ',['p.term','p.created','p.userGuid','p.userName'])
        ->where('t.updated < ?', $olderThan)
        ->where('t.collectionId in (?)',$collectionIds)
        ->where('t.processStatus NOT IN (?)',self::PROCESS_STATUS_UNPROCESSED);
        $result=$this->db->fetchAll($s)->toArray();
        
        if(empty($result)){
            return false;
        }
        $term=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $term editor_Models_Term */
        $attribute=ZfExtended_Factory::get('editor_Models_Term_Attribute');
        /* @var $attribute editor_Models_Term_Attribute */
        
        $deleteProposals=[];
        //for each of the terms with the proposals, use the proposal value as the
        //new term value in the original term, after the original term is updated, remove
        //the proposal
        foreach ($result as $key=>$res){
            if(empty($res['term'])){
                continue;
            }
            $proposal=ZfExtended_Factory::get('editor_Models_Term_Proposal');
            /* @var $proposal editor_Models_Term_Proposal */
            $proposal->init([
                'created'=>$res['created'],
                'userGuid'=>$res['userGuid'],
                'userName'=>$res['userName'],
            ]);
            
            $term->load($res['id']);
            $term->setTerm($res['term']);
            $term->setCreated($res['created']);
            $term->setUpdated(NOW_ISO);
            $term->setUserGuid($res['userGuid']);
            $term->setUserName($res['userName']);
            $term->setProcessStatus(self::PROCESS_STATUS_UNPROCESSED);
            //TODO: with the next termportal step(add new attribute and so)
            //update/merge those new proposal attributes to
            //now only the transac group should be modefied
            $attribute->updateTermTransacGroupFromProposal($term,$proposal);
            $attribute->updateTermProcessStatus($term, $term::PROCESS_STATUS_UNPROCESSED);
            $term->save();
            $deleteProposals[]=$res['id'];
            unset($result[$key]);
        }
        //remove the collected proposals
        if(!empty($deleteProposals)){
            $proposal=ZfExtended_Factory::get('editor_Models_Term_Proposal');
            /* @var $proposal editor_Models_Term_Proposal */
            $proposal->db->delete([
                'termId IN(?)' => $deleteProposals
            ]);
        }
        
        $result=array_column($result,'id');
        if(empty($result)){
            return false;
        }
        //delete the collected old terms
        return $this->db->delete(['id IN(?)'=>$result])>0;
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
                    $model->setSourceLangCode($x['rfc5646']);
                    
                    $model->setTargetLang($y['language']);
                    $model->setTargetLangCode($y['rfc5646']);
                    
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
     * Get loaded data as object with term attributes included
     * @return stdClass
     */
    public function getDataObjectWithAttributes() {
        $result=$this->getDataObject();
        //load all attributes for the term
        $rows=$this->groupTermsAndAttributes($this->findTermAndAttributes($result->id));
        $result->attributes=[];
        if(!empty($rows) && !empty($rows[0]['attributes'])){
            $result->attributes =$rows[0]['attributes'];
        }
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
        return $user->hasRole('termProposer');
    }
    
    /***
     * Check if the term modification attribute date is after $date
     * @param int $termId
     * @param mixed $date
     */
    public function isModifiedAfter(int $termId,$date){
        $sql='SELECT id FROM LEK_term_attributes WHERE parentId IN(
            SELECT ta.id FROM LEK_term_attributes ta
            INNER JOIN LEK_terms t ON t.id=ta.termId
            WHERE ta.name="transac" AND ta.attrType="modification"
            AND t.id=?)
            AND name="date"
            AND FROM_UNIXTIME(value)>?;';
        $result=$this->db->getAdapter()->query($sql,[$termId,$date])->fetchAll();
        return !empty($result);
    }
    
    /***
     * Group the term and attribute proposal data for the export
     * @param array $data
     * @return array
     */
    protected function groupProposalExportData(array $data){
        
        usort($data, function($a, $b) {
            $retval = $a['term-Id'] <=> $b['term-Id'];
            if ($retval == 0) {
                $retval = $b['term-term'] <=> $a['term-term'];
            }
            return $retval;
        });
        
        $returnResult=[];
        $tmpTerm=[];
        
        //clange cell color by value on the excel export callback
        $changeMyCollorTag='<changemycolortag>';
        foreach ($data as $row) {
            
            $tmpTerm['termEntryId']=$row['term-termEntryId'];
            //if it is empty it is termEntryAttribute
            if(empty($tmpTerm['termEntryId']) && !empty($row['attribute-termEntryId'])){
                $tmpTerm['termEntryId']=$row['attribute-termEntryId'];
            }
            $tmpTerm['definition']=$row['term-definition'];
            $tmpTerm['language']=$row['term-language'];
            $tmpTerm['termId']=$row['term-Id'];
            $tmpTerm['term']=$changeMyCollorTag.$row['term-term'];
            $tmpTerm['termProposal']='';
            $tmpTerm['processStatus']=$row['term-processStatus'];
            $tmpTerm['attributeName']=$row['attribute-name'];
            $tmpTerm['attribute']=$row['attribute-value'];
            $tmpTerm['attributeProposal']='';
            $tmpTerm['lastEditor']=$changeMyCollorTag.$row['term-lastEditor'];
            $tmpTerm['lastEditedDate']=$changeMyCollorTag.$row['term-lastEditedDate'];
            
            //if the proposal exist, set the change color and last editor for the proposal
            if(!empty($row['termproposal-term'])){
                $tmpTerm['term']=str_replace($changeMyCollorTag,'',$row['term-term']);
                $tmpTerm['termProposal']=$changeMyCollorTag.$row['termproposal-term'];
                $tmpTerm['lastEditor']=$changeMyCollorTag.$row['termproposal-lastEditor'];
                $tmpTerm['lastEditedDate']=$changeMyCollorTag.$row['termproposal-lastEditedDate'];
            }
            
            if(isset($row['attribute-processStatus']) && $row['attribute-processStatus']==self::PROCESS_STATUS_UNPROCESSED){
                $tmpTerm['attribute']=$changeMyCollorTag.$row['attribute-value'];
                $tmpTerm['lastEditor']=$changeMyCollorTag.$row['attribute-lastEditor'];
                $tmpTerm['lastEditedDate']=$changeMyCollorTag.$row['attribute-lastEditedDate'];
                $tmpTerm['term']=str_replace($changeMyCollorTag,'',$row['term-term']);
                $tmpTerm['termProposal']=str_replace($changeMyCollorTag,'',$row['termproposal-term']);
            }
            
            //if the attribute proposal is set, set the change color and last editor for the attribute proposal
            if(!empty($row['attributeproposal-value'])){
                $tmpTerm['term']=str_replace($changeMyCollorTag,'',$row['term-term']);
                $tmpTerm['termProposal']=str_replace($changeMyCollorTag,'',$row['termproposal-term']);
                $tmpTerm['attribute']=$row['attribute-value'];
                $tmpTerm['attributeProposal']=$changeMyCollorTag.$row['attributeproposal-value'];
                $tmpTerm['lastEditor']=$changeMyCollorTag.$row['attributeproposal-lastEditor'];
                $tmpTerm['lastEditedDate']=$changeMyCollorTag.$row['attributeproposal-lastEditedDate'];
            }
            $returnResult[]=$tmpTerm;
            $tmpTerm=[];
        }
        return $returnResult;
    }
    
    /***
     * Get term search select. It the user is proposal allowed, the term and attribute proposals will be joined.
     *
     * @return Zend_Db_Select
     */
    protected function getSearchTermSelect(){
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
            'termEntryId',
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
                'LEK_term_proposal.id as proposalId',
                'LEK_term_proposal.created as proposalCreated',
                'LEK_term_proposal.userName as proposalUserName'
            ]);
        }
        
        $attribute=ZfExtended_Factory::get('editor_Models_Term_Attribute');
        /* @var $attribute editor_Models_Term_Attribute */
        
        if($attribute->isProposableAllowed()){
            $s->joinLeft('LEK_term_attribute_proposal', 'LEK_term_attribute_proposal.attributeId = LEK_term_attributes.id',[
                'LEK_term_attribute_proposal.value as proposalAttributeValue',
                'LEK_term_attribute_proposal.id as proposalAttributelId',
            ]);
        }else{
            //exclude the proposals
            $s->where('LEK_terms.processStatus!=?',self::PROCESS_STATUS_UNPROCESSED)
            ->where('LEK_term_attributes.processStatus!=?',self::PROCESS_STATUS_UNPROCESSED);
        }
        return $s;
    }
    
    /***
     * Group term and term attributes data by term. Each row will represent one term and its attributes in attributes array.
     * The term attributes itself will be grouped in parent-child structure
     * @param array $data
     * @return array
     */
    public function groupTermsAndAttributes(array $data){
        if(empty($data)){
            return $data;
        }
        $map=[];
        $termColumns=[
            'definition',
            'groupId',
            'label',
            'value',
            'desc',
            'termStatus',
            'processStatus',
            'termId',
            'termEntryId',
            'collectionId',
            'languageId',
            'term'
        ];
        //available term proposal columns
        $termProposalColumns=[
            'proposalTerm',
            'proposalId',
            'proposalCreated',
            'proposalUserName'
        ];
        //maping between database name and term proposal table real name
        $termProposalColumnsNameMap=[
            'proposalTerm'=>'term',
            'proposalId'=>'id',
            'proposalCreated'=>'created',
            'proposalUserName'=>'userName'
        ];
        
        //available attribute proposal columns
        $attributeProposalColumns=[
            'proposalAttributeValue',
            'proposalAttributelId'
        ];
        
        //maping between database name and attribute proposal table real name
        $attributeProposalColumnsNameMap=[
            'proposalAttributeValue'=>'value',
            'proposalAttributelId'=>'id'
        ];
        
        $attribute=ZfExtended_Factory::get('editor_Models_Term_Attribute');
        /* @var $attribute editor_Models_Term_Attribute */
        
        //Group term-termattribute data by term. For each grouped attributes field will be created
        $oldKey='';
        $groupOldKey=false;
        $termProposalData=[];
        
        $termModel=ZfExtended_Factory::get('editor_Models_Term');
        /* @var $termModel editor_Models_Term */
        $isTermProposalAllowed=$termModel->isProposableAllowed();
        
        $attributeModel=ZfExtended_Factory::get('editor_Models_Term_Attribute');
        /* @var $attributeModel editor_Models_Term_Attribute */
        $isAttributeProposalAllowed=$attributeModel->isProposableAllowed();
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        
        //map the term id to array index (this is used because the jquery json decode changes the array sorting based on the termId)
        $keyMap=[];
        $indexKeyMap=function($termId) use (&$keyMap){
            if(!isset($keyMap[$termId])){
                $keyMap[$termId]=count($keyMap);
                return $keyMap[$termId];
            }
            return $keyMap[$termId];
        };
        
        foreach ($data as $tmp){
            $termKey=$indexKeyMap($tmp['termId']);
            
            if(!isset($map[$termKey])){
                $termKey=$indexKeyMap($tmp['termId']);
                $map[$termKey]=[];
                $map[$termKey]['attributes']=[];
                
                if(isset($oldKey) && isset($map[$oldKey])){
                    $map[$oldKey]['attributes']=$attribute->createChildTree($map[$oldKey]['attributes']);
                    $groupOldKey=true;
                    
                    $map[$oldKey]['proposable']=$isTermProposalAllowed;
                    //collect the term proposal data if the user is allowed to
                    if($isTermProposalAllowed){
                        $map[$oldKey]['proposal']=!empty($termProposalData['term']) ? $termProposalData : null;
                        $map[$oldKey]['attributes']=$attribute->updateModificationGroupDate($map[$oldKey]['attributes'],isset($map[$oldKey]['proposal'])?$map[$oldKey]['proposal']:[]);
                        $termProposalData=[];
                    }
                }
            }
            
            //split the term fields and term attributes
            $atr=[];
            $attProposal=[];
            foreach ($tmp as $key=>$value){
                //check if it is term specific data
                if(in_array($key,$termColumns)){
                    $map[$termKey][$key]=$value;
                    continue;
                }
                //is term attribute proposal specific data
                if(in_array($key,$attributeProposalColumns)){
                    $attProposal[$attributeProposalColumnsNameMap[$key]]=$value;
                    continue;
                }
                //is term proposal specific columnt
                if(in_array($key,$termProposalColumns)){
                    $termProposalData[$termProposalColumnsNameMap[$key]]=$value;
                    continue;
                }
                
                if($key=='headerText'){
                    $value=$translate->_($value);
                }
                //it is attribute column
                $atr[$key]=$value;
            }
            
            //is attribute proposable (is user attribute proposal allowed and the attribute is proposal whitelisted)
            $atr['proposable'] =$isAttributeProposalAllowed && $attribute->isProposable($atr['name'],$atr['attrType']);
            if($isAttributeProposalAllowed){
                $atr['proposal']=!empty($attProposal['id']) ? $attProposal : null;
                $attProposal=[];
            }
            
            array_push($map[$termKey]['attributes'],$atr);
            $oldKey = $indexKeyMap($tmp['termId']);
            $groupOldKey=false;
        }
        //if not grouped after foreach, group the last result
        if(!$groupOldKey){
            $map[$oldKey]['proposable']=$isTermProposalAllowed;
            $map[$oldKey]['attributes']=$attribute->createChildTree($map[$oldKey]['attributes']);
            
            //collect the term proposal data if the user is allowed to
            if($isTermProposalAllowed){
                $map[$oldKey]['proposal']=!empty($termProposalData['term']) ? $termProposalData : null;
                $map[$oldKey]['attributes']=$attribute->updateModificationGroupDate($map[$oldKey]['attributes'],isset($map[$oldKey]['proposal'])?$map[$oldKey]['proposal']:[]);
            }
        }
        
        if(empty($map)){
            return null;
        }
        return $map;
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
    
    /**
     * Returns the configured mapping of term-statuses
     * (= which statuses are allowed etc).
     * @return array
     */
    static public function getTermStatusMap() {
        $config = Zend_Registry::get('config');
        return $config->runtimeOptions->tbx->termLabelMap->toArray();
    }
    
    /**
     * Is the term a "preferred" term according to the given status?
     * @param string $termstatus
     * @return boolean
     */
    static public function isPreferredTerm($termstatus) {
        $termStatusMap = self::getTermStatusMap();
        if(!array_key_exists($termstatus, $termStatusMap)) {
            return false;
        }
        return $termStatusMap[$termstatus] == 'preferred';
    }
    
    /**
     * Is the term a "permitted" term according to the given status?
     * @param string $termstatus
     * @return boolean
     */
    static public function isPermittedTerm($termstatus) {
        $termStatusMap = self::getTermStatusMap();
        if(!array_key_exists($termstatus, $termStatusMap)) {
            return false;
        }
        return $termStatusMap[$termstatus] == 'permitted';
    }
}
