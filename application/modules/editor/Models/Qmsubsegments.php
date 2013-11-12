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

/* * #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */

/**
 * Methods for Management of QM-Subsegments
 */
class editor_Models_Qmsubsegments extends ZfExtended_Models_Entity_Abstract {
    const TYPE_SOURCE = 'source';
    const TYPE_TARGET = 'target';
    
    protected $dbInstanceClass = 'editor_Models_Db_Qmsubsegments';
    
     /** 
      * parses qm-subsegment-img-Tags in segment and saves new once in the database and sets their id correctly in returned segment
      * 
      * - example for img-Tag: <img data-seq="ext-123123" data-comment="Kommentar" class="minor qmflag ownttip open qmflag-2" src="/modules/editor/images/imageTags/qmsubsegment-2-left.png">
      * 
      * Warning, in Frontend duplicated IDs are fixed by the editor.
      * That means that existing ids (data-seqs) are wandering forward: 
      * Example:
      *  Before duplicating:
      *   This is the [X 1]testtext[/X 1].
      *  after duplicating and before fixing in the frontend:
      *   This [X 1]is[/X 1] the [X 1]testtext[/X 1].
      *  after fixing:
      *   This [X 1]is[/X 1] the [X 2]testtext[/X 2].
      * 
      * @param string $segment
      * @param integer $segmentId
      * @param string $field edited Segmentfield (must be target or source)  
      * @return string $segment
      */
    public function updateQmSubSegments(string $segment, integer $segmentId, string $field) {
        $segment = str_replace("\xc2\xa0",' ',$segment);
        $sArr = $this->splitSegment($segment);
        $count = count($sArr);
        $openTags = array();
        $qmIdsInSeg = array();
        for ($i = 1; $i < $count;$i=$i+2) {//the odd entries contain the img-tags
            $id = $this->getIdFromImg($sArr[$i]);
            $cls = $this->getClsFromImg($sArr[$i]);
            //skip close tags
            $tagType = (strpos($cls, ' open ') !== false ? 'open' : 'close'); 
            if($tagType === 'close'){
                continue;
            }
            if(strpos($id, 'ext-')!== false || $id == 0){//new subsegment-Tag found, id == 0 occurs on wdhe usage. handling 0 as a new SubSegment fixes the following statistics issues
                $session = new Zend_Session_Namespace();
                $comment = preg_replace('".* data-comment=\"([^\"]*)\".*"s', '\\1', $sArr[$i]);
                if($comment === $sArr[$i]){
                    $this->imgTagParseLog('comment', $sArr[$i]);
                }
                //assuming that first class is severity and last class is qmtype
                $cls = explode(' ', $cls);
                $qmtype = (int)str_replace('qmflag-', '', end($cls));
                if(!is_integer($qmtype) || empty($qmtype)){
                    $this->imgTagParseLog('qmtype', $sArr[$i]);
                }
                $severity = reset($cls);
                if(empty($severity)){ //@todo additional check against stored sev values in task
                    $this->imgTagParseLog('severity', $sArr[$i]);
                }
                
                $this->init();
                $this->setFieldedited($field);
                $this->setQmtype($qmtype);
                $this->setComment($comment);
                $this->setSeverity($severity);
                $this->setTaskGuid($session->taskGuid);
                $this->setSegmentId($segmentId);
                $this->save();
                
                $autoincId = $this->getId();
                $qmIdsInSeg[]=$autoincId;
                $openTags[$id] = $autoincId;
                $sArr[$i] = str_replace('data-seq="'.$id.'"', 'data-seq="'.$autoincId.'"', $sArr[$i]);
            }
            else{
                $id = (int)$id;
                if(!is_int($id) || empty($id)){
                    $this->imgTagParseLog('database-ID', $sArr[$i]);
                }
                $qmIdsInSeg[]=$id;
            }
        }
        for ($i = 1; $i < $count;$i=$i+2) {//the odd entries contain the img-tags
            $id = preg_replace('".* data-seq=\"([^\"]*)\".*"s', '\\1', $sArr[$i]);
            if($id === $sArr[$i]){
            	$this->imgTagParseLog('data-seq', $sArr[$i]);
            }
            if(strpos($id, 'ext-')!== false){//new subsegment-Tag found
                //process close tags only, so ignore open tags
                if(preg_match('/class="[^"]* open [^"]*"/', $sArr[$i])){
                    continue;
                }
                
                $sArr[$i] = str_replace('data-seq="'.$id.'"', 'data-seq="'.$openTags[$id].'"', $sArr[$i]);
            }
        }
        $this->deleteUnused($segmentId,$qmIdsInSeg, $field);
        return implode('', $sArr);
    }
    
    /**
     * splits up the segment along the img tags
     * @param string $segment
     * @return array
     */
    public function splitSegment(string $segment) {
        return preg_split('"(<img [^>]+>)"s', $segment, NULL, PREG_SPLIT_DELIM_CAPTURE);
    }
    
    /**
     * returnes the stored id in the img tag
     * @param string $img
     * @return string
     */
    public function getIdFromImg(string $img) {
        //get id from data-seq field
        $id = preg_replace('".* data-seq=\"([^\"]*)\".*"s', '\\1', $img);
        if($id === $img){
        	$this->imgTagParseLog('data-seq', $img);
        }
        return $id;
    }
    
    /**
     * returnes the stored css classes as string from the img tag
     * @param string $img
     * @return string
     */
    public function getClsFromImg($img) {
        //get class from tag
        preg_match('/class="([^"]*)"/s', $img, $cls);
        if(! empty($cls[1])) {
        	return $cls[1];
        }
       	$this->imgTagParseLog('class', $img);
       	return '';
    }
    
    /**
     * Throws an exception if given imgTag is invalid
     * @param string $what Identifier of missing / invalid tag attribute / info
     * @param string $imgTag corresponding tag
     * @throws Zend_Exception
     */
    protected function imgTagParseLog($what, $imgTag){
        throw new Zend_Exception('Subsegment img found, but no '.$what.' found in it in segment: '.$imgTag);
    }

    /**
     * deletes all QMSubSegment Entries to given segment which ID was not given in List
     * 
     * @param int $segmentId
     * @param array $qmIds
     * @param string $fieldedited
     */
    public function deleteUnused(integer $segmentId,array $qmIds, string $fieldedited) {
        $rows = $this->getQmSubSegsBySegmentId($segmentId);
        $delete = array();
        foreach ($rows as $row) {
            if(!in_array($row['id'],$qmIds)){
                $delete[] = ' `id` = '.$row['id'];
            }
        }
        if(count($delete)>0){
            $this->db->getAdapter()->query('DELETE FROM `LEK_qmsubsegments` WHERE ('.  implode(' or ', $delete).") AND fieldedited = '".$fieldedited."'");
        }
    }

    /**
     * loads all qmsubsegment entries by a segmentId
     * 
     * @param integer $segmentId
     * @return array as returned by $this->db->getAdapter()->fetchAll
     */
    public function getQmSubSegsBySegmentId(integer $segmentId) {
        $q = $this->db->getAdapter()->select()
                ->from(array('q' => 'LEK_qmsubsegments'))
                ->where('segmentId = ?', $segmentId);
        return $this->db->getAdapter()->fetchAll($q);
    }

    /**
     * loads all qmsubsegment entries by task, grouped and with counts
     * @param string $taskGuid
     * @param string $fieldedited
     * @return array as returned by this->regroupStatistics
     */
    public function loadByTaskGuid(string $taskGuid, string $fieldedited) {
        $data = $this->getStatisticsFromDb($taskGuid, $fieldedited);
        return $this->regroupStatistics($data);
    }

    /**
     * builds qm stat tree and translates severities
     * 
     * @param string $taskGuid
     * @param string $type returns only stats of given type (target or source)
     * @return array php-structure as converted to json is expected by ExtJs treeGrid
     */
    public function getQmStatTreeByTaskGuid(string $taskGuid, $type = self::TYPE_TARGET) {
        $storage =  new stdClass();
        $storage->severitySumKeys = array();
        $issues = $this->addRootNodeToQmFlags($taskGuid);
        $storage->statData = $this->loadByTaskGuid($taskGuid, $type);

        $hasChildren = function($checkChilds){
            return isset($checkChilds->children) && is_array($checkChilds->children);
        };
        
        $walk = function($storage,$issues)use(&$walk,$hasChildren){
            foreach ($issues as $keyIssue => &$issue) {
                $hasChilds = $hasChildren($issue);
                settype($issue->totalTotal, 'integer');
                if((isset($storage->statData[$issue->id]) ||$hasChilds)){
                    $issue->expanded = true;
                    $issue->leaf = !$hasChilds;
                    if(isset($storage->statData[$issue->id])){
                        foreach ($storage->statData[$issue->id] as $k => $v) {
                            if($k != 'qmtype' && $k != 'sum'){
                                $k = strtolower($k);
                                $issue->{$k} = (int)$v;
                                $severityKey = 'total'.ucfirst($k);
                                $storage->severitySumKeys[$severityKey] = '';
                                settype($issue->{$severityKey}, 'integer');
                                $issue->{$severityKey} += (int)$v;
                            }
                        }
                        $issue->total = $storage->statData[$issue->id]['sum'];
                        $issue->totalTotal += $storage->statData[$issue->id]['sum'];
                    }
                }
                //useful for debugging:
                //$issue->text .= ' '.$issue->id;
                unset($issue->id);
                
                if($hasChilds){
                    $storage = $walk($storage,$issue->children,$hasChildren);
                    foreach($storage->issues as $k => $childsIssue){
                        foreach($storage->severitySumKeys as $severityKey => $v){
                            if(isset($childsIssue->{$severityKey})){
                                settype($issue->{$severityKey}, 'integer');
                                $issue->{$severityKey} += $childsIssue->{$severityKey};
                            }
                        }
                        $issue->totalTotal += $childsIssue->totalTotal;
                    }
                    $issue->children = $storage->issues;
                    $issue->children = array_values($issue->children);//insure that we have a numerical array for json-conversion (otherwhise we will not get a json-array, but a json-object)
                }
                if($issue->totalTotal == 0){
                    unset($issues[$keyIssue]);
                }
            }
            $storage->issues = $issues;
            return $storage;
        };
        $storage = $walk($storage,$issues,$hasChildren);
        return $storage->issues;
    }

    /**
     * 
     * @param string $taskGuid
     * @return object as returned by $taskModel->getQmSubsegmentIssuesTranslated, but with a toplevel rootnode added
     */
    protected function addRootNodeToQmFlags(string $taskGuid) {
        $translate = Zend_Registry::get('Zend_Translate');
        $taskModel = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $taskModel editor_Models_Task */
        $taskModel->loadByTaskGuid($taskGuid);
        $issues = new stdClass();
        $issues->children = $taskModel->getQmSubsegmentIssuesTranslated(false);;
        $issues->text = $translate->_('Alle Kategorien');
        $issues->id = -1;
        return array(0=>$issues);
    }

    /**
     * @param string $taskGuid
     * @param string $fieldedited
     * @return array statistics grouped by qmtype and severity with counts
     */
    protected function getStatisticsFromDb(string $taskGuid, string $fieldedited) {
        $q = $this->db->getAdapter()->select()
                ->from(array('q' => 'LEK_qmsubsegments'), array(
                    'qmtype',
                    'severity',
                    'count' => 'COUNT(*)'))
                ->group('qmtype')
                ->group('severity')
                ->where('taskGuid = ?', $taskGuid)
                ->where('fieldedited = ?', $fieldedited);
        return $this->db->getAdapter()->fetchAll($q);
    }

    /**
     * 
     * @param array $data
     * @return array array() {[qmtype]=>  array(4) {["qmtype"]=> "asdf", ["severity1"]=> (int)count, ["severity2"]=> (int)count, ... ,["sum"]=>  int()sum of severities      }
     */
    protected function regroupStatistics(array $data) {
        $groupedData = array();
        foreach ($data as $d) {
            $groupedData[$d['qmtype']]['qmtype'] = $d['qmtype'];
            $groupedData[$d['qmtype']][$d['severity']] = $d['count'];
        }
        foreach ($groupedData as &$qmtype) {
            $sum = 0;
            foreach ($qmtype as $key => $value) {
                if ($key !== 'qmtype') {
                    $sum += $value;
                }
            }
            $qmtype['sum'] = $sum;
        }
        return $groupedData;
    }

}
