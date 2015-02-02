<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */
/**
 * Term Instanz
 */
class editor_Models_Term extends ZfExtended_Models_Entity_Abstract {
    const STAT_PREFERRED = 'preferredTerm';
    const STAT_ADMITTED = 'admittedTerm';
    const STAT_LEGAL = 'legalTerm';
    const STAT_REGULATED = 'regulatedTerm';
    const STAT_STANDARDIZED = 'standardizedTerm';
    const STAT_DEPRECATED = 'deprecatedTerm';
    const STAT_SUPERSEDED = 'supersededTerm';
    const STAT_NOT_FOUND = 'STAT_NOT_FOUND'; //Dieser Status ist nicht im Konzept definiert, sondern wird nur intern verwendet!

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

    /**
     * Gibt für eine übergebene termId die mit dem zugehörigen Termentry assozierten Terme zurück. 
     * @param string $taskGuid
     * @param string $termId
     * @param int $langId
     * @return array
     */
    public function getTermEntryTermsByTaskGuidTermIdLangId($taskGuid, $termId,$langId) {
        $s1 = $this->db->getAdapter()->select()
        ->from(array('t1' => 'LEK_terms'),
                array('t1.groupId'))
        ->where('t1.id = ?', $termId)
        ->where('t1.taskGuid = ?', $taskGuid);
        $s2 = $this->db->getAdapter()->select()
        ->from(array('t2' => 'LEK_terms'))
        ->where('t2.taskGuid = ?', $taskGuid)
        ->where('t2.language = ? and t2.groupId = ('.$s1->assemble().')', $langId);
        return $this->db->getAdapter()->fetchAll($s2);
    }
    
    
    /**
     * Returns term-informations for $segmentId in $taskGuid.
     * Includes assoziated terms corresponding to the tagged terms
     * 
     * @param string $taskGuid
     * @param int $segmentId
     * @return array
     */
    public function getByTaskGuidAndSegment(string $taskGuid, integer $segmentId) {
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
        
        $result = $this->getSortedTermGroups($task->getTaskGuid(), $termIds, $task->getSourceLang());
        
        if(empty($result)) {
            return array();
        }
        return $this->sortTerms($result);
    }
    
    /**
     * Returns term-informations for $segmentId in $taskGuid.
     * Includes assoziated terms corresponding to the tagged terms
     * 
     * @param string $mid
     * @param array $languageIds 1-dim array with languageIds|default empty array; 
     *          if passed only terms with the passed languageIds are returned
     * @return 2-dim array (get term of first row like return[0]['term'])
     */
    public function getAllTermsOfGroupByMid(string $taskGuid, string $mid, $languageIds = array()) {
        $sub = "select groupId from LEK_terms where mid = ?";
        $sub = $this->db->getAdapter()->quoteInto($sub, $mid).'and taskGuid = ?';
        $sub = $this->db->getAdapter()->quoteInto($sub, $taskGuid);
        $query = "select * from LEK_terms WHERE groupId = (".$sub.")";
        if(count($languageIds)>0){
             $or = 'language = ?';
             $orArr = array();
             foreach ($languageIds as $id) {
                $orArr[] = $this->db->getAdapter()->quoteInto($or, $id);
             }
             $query .= ' and ('.  implode(' or ', $orArr).')';
         }
        
        return $this->db->getAdapter()->fetchAll($query);
    }
    
    protected function getTermMidsFromTaskSegment(editor_Models_Task $task, editor_Models_Segment $segment) {
        
        $fieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        /* @var $fieldManager editor_Models_SegmentFieldManager */
        $fieldManager->initFields($task->getTaskGuid());
        
        $sourceFieldName = $fieldManager->getFirstSourceName();
        $sourceText = $segment->get($sourceFieldName);
        
        if ($task->getEnableSourceEditing()) {
            $sourceFieldName = $fieldManager->getEditIndex($fieldManager->getFirstSourceName());
            $sourceText = $segment->get($sourceFieldName);
        }
        
        $targetFieldName = $fieldManager->getEditIndex($fieldManager->getFirstTargetName());
        $targetText = $segment->get($targetFieldName);
        
        $getTermIdRegEx = '/<div.*?data-tbxid="(term_.*?)".*?>/';
        preg_match_all($getTermIdRegEx, $sourceText, $sourceMatches, PREG_SET_ORDER);
        preg_match_all($getTermIdRegEx, $targetText, $targetMatches, PREG_SET_ORDER);
        
        if (empty($sourceMatches) && empty($targetMatches)) {
            return array();
        }
        
        $termIds = array('source' => $sourceMatches, 'target' => $targetMatches);
        
        return $termIds;
    }
    
    /**
     * 
     * @param string $seg
     * @return type array values are the mids of the terms in the string
     */
    public function getTermMidsFromSegment(string $seg) {
        $getTermIdRegEx = '/<div.*?data-tbxid="(term_.*?)".*?>/';
        preg_match_all($getTermIdRegEx, $seg, $matches);
        return $matches[1];
    }
    /**
     * Returns a multidimensional array.
     * 1. level: keys: groupId, values: array of terms grouped by groupId
     * 2. level: terms of group groupId
     * 
     * !! TODO: Sortierung der Gruppen in der Reihenfolge wie sie im Segment auftauchen (order by seg2term.id sollte hinreichend sein)
     * 
     * @param string $taskGuid unic id of current task
     * @param array $termIds as 2-dimensional array('source' => array(), 'target' => array())
     * @parma $sourceLang
     * 
     * @return array
     */
    protected function getSortedTermGroups($taskGuid, array $termIds,  $sourceLang) {
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
                ->distinct()
                ->from(array('t1' =>'LEK_terms'), array('t2.*'))
                ->joinLeft(array('t2' =>'LEK_terms'), 't1.groupId = t2.groupId')
                ->where('t1.taskGuid = ?', $taskGuid)
                ->where('t2.taskGuid = ?', $taskGuid)
                ->where('t1.mid IN('.$serialIds.')');
        $terms = $this->db->getAdapter()->fetchAll($sql);
        
        $termGroups = array();
        foreach($terms as $term) {
            $term = (object) $term;
            
            settype($termGroups[$term->groupId], 'array');
            
            $term->used = in_array($term->mid, $allIds);
            $term->isSource = in_array($term->language, array($sourceLang));
            $term->transFound = false;
            if ($term->used) {
                $term->transFound = preg_match('/class=".*?transFound.*?"/', $transFoundSearch[$term->mid]);
            }
            
            $termGroups[$term->groupId][] = $term;
        }
        
        return $termGroups;
    }
    
    /**
     * 
     * @param string $mid
     * @param string $taskGuid
     * @return Zend_Db_Table_Row_Abstract | null
     */
    public function loadByMid(string $mid,string $taskGuid) {
        $s = $this->db->select(false);
        $db = $this->db;
        $s->from($this->db);
        $s->where('taskGuid = ?', $taskGuid)->where('mid = ?', $mid);
        
        
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
    protected function sortTerms(array $termGroups) {
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
        $status = $this->compareTermStatus($term1->status, $term2->status);
        if($status !== 0) {
            return $status;
        }

        $isSource = $this->compareTermLangUsage($term1->isSource, $term2->isSource);
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
     * @param editor_Models_Export_Tbx $exporteur
     */
    public function export(editor_Models_Task $task, editor_Models_Export_Terminology_Tbx $exporteur) {
        $langs = array($task->getSourceLang(), $task->getTargetLang());
        if($task->getRelaisLang() > 0) {
            $langs[] = $task->getRelaisLang();
        }
        $s = $this->db->select()
        ->where('taskGuid = ?', $task->getTaskGuid())
        ->where('language in (?)', $langs)
        ->order('groupId ASC')
        ->order('language ASC')
        ->order('id ASC');
        $data = $this->db->fetchAll($s);
        if($data->count() == 0) {
            return null;
        }
        $exporteur->setData($data);
        return $exporteur->export();
    }
}