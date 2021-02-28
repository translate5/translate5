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

/* * #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */

/**
 * TODO AUTOQA Move to Quality Model
 * Methods for Management of QM-Subsegments
 */
class editor_Models_Qmsubsegments extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Qmsubsegments';
    
     /** 
      * parses qm-subsegment-img-Tags in segment and saves new once in the database and sets their id correctly in returned segment
      * 
      * - example for img-Tag: <img data-seq="ext-123123" data-comment="Kommentar" class="minor qmflag ownttip open qmflag-2" src="/modules/editor/images/imageTags/qmsubsegment-2-left.png" />
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
      * @param int $segmentId
      * @param string $field edited Segmentfield (must be target or source)  
      * @return string $segment
      */
    public function updateQmSubSegments(string $segment, int $segmentId, string $field) {
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
     * Extracts information from the tag, needed from $this->_areTagsOverlapped()
     * 
     * @param string $tag
     * @return string[]
     */
    protected function _getImageTagInfo($tag) {
    	$data = [];
    	$data['id'] = $this->getIdFromImg($tag);
    	$classes = $this->getClsFromImg($tag);
    	$classes = explode(' ', $classes);
    	$data['type'] = (in_array('open', $classes) !== false ? 'open' : 'close');
    	return $data;
    }
    
    /**
     * Checks while the string is image tag 
     * 
     * @param string $tag
     * @return boolean
     */
    protected function _isImageTag($tag) {
    	if (substr($tag, 0, 5) == '<img ') {
    		return true;
    	}
    	return false;
    }
    
    /**
     * Check while the two tags are overlapped without any contents between them
     * 
     * @param string $tag1
     * @param string $between
     * @param string $tag2
     * @return boolean
     */
    protected function _areTagsOverlapped($tag1, $between, $tag2) {
    	// at least one of the tags is not an image tag
    	if (!$this->_isImageTag($tag1) || !$this->_isImageTag($tag2)) {
    		return false;
    	}
    	if ($between != '') { // there is some contents between tags
    		return false;
    	}
    	$tag1_info = $this->_getImageTagInfo($tag1);
    	$tag2_info = $this->_getImageTagInfo($tag2);
    	if (($tag1_info['type'] == 'open') &&
    		($tag2_info['type'] == 'close') &&
    		($tag1_info['id'] != $tag2_info['id'])) {
    	
    		return true;
    	}
    	return false;
    }

    /**
     * Corrects overlapped image tags between which there is no text node
     *
     * @param string $segment
     * @return string $segment_corrected
     */
    public function correctQmSubSegmentsOverlappedTags(string $segment) {
    	$sArrSrc = $this->splitSegment($segment);
    	$count = count($sArrSrc);
    	    	
    	for ($i = 0; $i < $count; $i++) {
            if ((($i + 2) < $count) && $this->_areTagsOverlapped($sArrSrc[$i], $sArrSrc[$i+1], $sArrSrc[$i+2])) {
            	// swap overlapped tags
            	$tag1_save = $sArrSrc[$i];
            	$sArrSrc[$i] = $sArrSrc[$i+2];
            	$sArrSrc[$i+2] = $tag1_save;
            }
        }
        $segment_corrected = implode('', $sArrSrc);
        return $segment_corrected;
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
    public function deleteUnused(int $segmentId,array $qmIds, string $fieldedited) {
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
     * @param int $segmentId
     * @return array as returned by $this->db->getAdapter()->fetchAll
     */
    public function getQmSubSegsBySegmentId(int $segmentId) {
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
                $issue->qmtype = $issue->id;
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
                    $issue->children = array_values($issue->children);//ensure that we have a numerical array for json-conversion (otherwhise we will not get a json-array, but a json-object)
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
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $translate ZfExtended_Zendoverwrites_Translate */;
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

    /***
     * Check if the given task has segments with mqm
     * 
     * @param string $taskGuid
     * @return boolean
     */
    public function hasTaskMqm(string $taskGuid){
        $q = $this->db->getAdapter()->select()
                ->from(array('q' => 'LEK_qmsubsegments'), array('id'))
                ->where('taskGuid = ?', $taskGuid);
        
        return !empty($this->db->getAdapter()->fetchAll($q));
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
    
    /**
     * creates the mqm tag ready for HTML output
     * the format of the tag is used in the editor
     */
    public function createTag($open = true) {
        //one time lazy creation of the strings
        if(empty($this->_tagSkel)) {
            $s = new Zend_Session_Namespace();
            $this->_tagSkel = '<img class="%1$s qmflag ownttip %2$s qmflag-%3$d" data-seq="%4$d" data-comment="%5$s" src="%6$s" />';
            $this->_tagUrl = APPLICATION_RUNDIR.'/'.$s->runtimeOptions->dir->tagImagesBasePath.'/qmsubsegment-%d-%s.png';
        }
        $side = $open ? 'open' : 'close';
        $tagSide = $open ? 'left' : 'right';
        $url = sprintf($this->_tagUrl, $this->getQmtype(), $tagSide);
        //return Editor.data.segments.subSegment.tagPath+'qmsubsegment-'+qmid+'-'+(open ? 'left' : 'right')+'.png';
        return sprintf($this->_tagSkel, $this->getSeverity(), $side, $this->getQmtype(), $this->getId(), $this->getComment(), $url);
    }
    
    /**
     * set the given segmentId for the given MQM Ids in the second parameter. 
     * @param int $segmentId
     * @param array $ids
     */
    public function updateSegmentId($segmentId, array $ids) {
        $ids = array_map(function($id) {
            return (int) $id;
        }, $ids);
        $this->db->update(array('segmentId' => $segmentId), array('id in (?)' => $ids));
    }
}
