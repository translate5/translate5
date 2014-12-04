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
     * Gibt die mit einem bestimmten Segment assozierten Terme sortiert und gruppiert zurück. TaskGuid zur Sicherheit als zusätzlicher Filter.
     * @param string $taskGuid
     * @param int $segmentId
     * @return array
     */
    public function getByTaskGuidAndSegment(string $taskGuid, integer $segmentId) {
        if(empty($taskGuid) || empty($segmentId)) {
            return array();
        }

        $result = $this->getSortedTermGroups($taskGuid, $segmentId);

        if(empty($result)) {
            return array();
        }
        return $this->sortTerms($result);
    }

    /**
     * Gibt ein Multidimensionales Array zurück:
     * erste Ebene: keys: groupId, values: Array mit Termen gruppiert nach GroupId
     * zweite Ebene: Terme der Gruppe
     * Sortierung der Gruppen in der Reihenfolge wie sie im Segment auftauchen (order by seg2term.id sollte hinreichend sein)
     * @param string $taskGuid
     * @param integer $segmentId
     * @return array
     */
    protected function getSortedTermGroups(string $taskGuid, integer $segmentId) {
        $s = $this->db->getAdapter()->select()
        ->from(array('t' => 'LEK_terms'))
        ->join(array('s2t' => 'LEK_segments2terms'), 's2t.termId = t.id')
        ->where('t.taskGuid = ?', $taskGuid)
        ->where('s2t.segmentId = ?', $segmentId)
        ->order('s2t.id');
        $terms = $this->db->getAdapter()->fetchAll($s);
        $termGroups = array();
        foreach($terms as $term) {
            settype($termGroups[$term['groupId']], 'array');
            $termGroups[$term['groupId']][] = (object)$term;
        }
        return $termGroups;
    }

    /**
     * 
     * @param string $mid
     * @return type
     */
    protected function loadByMid(string $mid) {
        $s = $this->db->getAdapter()->select()
        ->from(array('t' => 'LEK_terms'))
        ->where('t.mid = ?', $taskGuid);
        return $this->row = $this->db->getAdapter()->fetchRow($s);
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