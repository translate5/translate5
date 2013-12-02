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
 *

/**
 * Stellt Methoden zur Verarbeitung der vom Parser ermittelteten Segment Daten bereit
 * - speichert die ermittelten Segment Daten als Segmente in die DB
 * - speichert die gefundenen Terme
 */
class editor_Models_Import_SegmentProcessor_ProofRead extends editor_Models_Import_SegmentProcessor {
    /**
     * @var string GUID
     */
    protected $userGuid = NULL;
    /**
     * @var string
     */
    protected $userName = NULL;
    /**
     * @var Zend_Db_Adapter_Mysqli
     */
    protected $db = NULL;
    
    /**
     * @var Zend_Config
     */
    protected $taskConf;
    /**
     * @var int
     */
    protected $segmentNrInTask = 0;
    
    /**
     * @param editor_Models_Languages $sourceLang
     * @param editor_Models_Languages $targetLang
     * @param editor_Models_Task $task
     * @param string $userGuid
     * @param string $userName
     */
    public function __construct(editor_Models_Languages $sourceLang, editor_Models_Languages $targetLang, editor_Models_Task $task, $userGuid, $userName){
        parent::__construct($sourceLang, $targetLang, $task);
        $this->userGuid = $userGuid;
        $this->userName = $userName;
        $this->db = Zend_Registry::get('db');
        $this->taskConf = $this->task->getAsConfig();
    }
    
    public function process(editor_Models_Import_FileParser $parser){
        $seg = ZfExtended_Factory::get('ZfExtended_Models_Entity',array('editor_Models_Db_Segments',array()));
        //statische, Task spezifische Daten zum Segment
        $seg->setUserGuid($this->userGuid);
        $seg->setUserName($this->userName);
        $seg->setTaskGuid($this->taskGuid);
        
        //Segment Spezifische Daten
        $seg->setMid($parser->getMid()); 
        $seg->setFileId($this->fileId);
        $seg->setMatchRate($parser->getMatchRate());
        $seg->setEditable($parser->getEditable());
        $seg->setAutoStateId($parser->getAutoStateId());
        $seg->setPretrans($parser->getPretrans());
        
        $srcToSort = $this->truncateSegmentsToSort($parser->getSource());
        $seg->setSource($parser->getSource()); 
        $seg->setSourceToSort($srcToSort);
        $seg->setSourceMd5(md5($parser->getSourceOrig()));
        
        $this->segmentNrInTask++;
        $seg->setSegmentNrInTask($this->segmentNrInTask);
        
        if($this->taskConf->enableSourceEditing) {
            $seg->setSourceEdited($parser->getSource()); 
            $seg->setSourceEditedToSort($srcToSort);
        }
        
        $seg->setTarget($parser->getTarget());
        $seg->setTargetMd5(md5($parser->getTargetOrig()));
        
        $targetToSort = $this->truncateSegmentsToSort($parser->getTarget());
        $seg->setTargetToSort($targetToSort);
        $seg->setEdited($parser->getTarget());
        $seg->setEditedToSort($targetToSort);
        $segmentId = $seg->save();
        $this->saveTerms2Db($parser->getAndCleanTerms(), $segmentId);
        return $segmentId; 
    }
    
    /**
     * Überschriebener Post Parse Handler, erstellt in diesem Fall das Skeleton File
     * @override
     * @param editor_Models_Import_FileParser $parser
     */
    public function postParseHandler(editor_Models_Import_FileParser $parser) {
    	$skel = ZfExtended_Factory::get('editor_Models_Skeletonfile');
    	$skel->setfileName($this->fileName);
    	$skel->setfileId($this->fileId);
    	$skel->setFile($parser->getSkeletonFile()); // wird in Sdlxliff.php befüllt
    	$skel->save();
    }
    
    /**
     * befüllt für alle Terms eines Segments die Tabellen LEK_terminstances
     * und LEK_segment2terms auf Basis der im parser gesammelten Terme
     * 
     * @todo sofern die Term Speicher Geschichte wiederverwendet werden soll (z.B. in den Relais), diese auch auslagern
     * 
     * @param int $segmentId
     */
    protected function saveTerms2Db(array $terms2save, $segmentId) {
    	$this->saveTermInstances($terms2save, $segmentId);
    	$this->saveSegment2Terms($terms2save, $segmentId);
    }
    
    /**
     * fügt für jeden Term die zu ihm gehörenden Terme aus dem selben termEntry hinzu
     * @param array $terms2save
     * @param integer $segmentId
     */
    protected function saveTermInstances(array $terms2save, $segmentId) {
        $sql = array();
    	foreach($terms2save as $term){
            $sql[]= " (NULL , ".
               (int)$segmentId.
               ", ".(int)$term->id.
                    ", ".$this->db->quote((string)$term->term).
               ", ".(int)$term->projectTerminstanceId.
               ")";
    	}
        if(count($sql)>0){
            $this->db->query(
                'INSERT INTO `LEK_terminstances` (`id` ,`segmentId` ,`termId` ,`term` ,`projectTerminstanceId`) VALUES'. implode(',', $sql));
        }
    }
    
    /**
     * Speichert die Segment Term Assozationen
     * @param array $terms2save
     * @param integer $segmentId
     */
    protected function saveSegment2Terms(array $terms2save, $segmentId) {
    	$termModel = ZfExtended_Factory::get('editor_Models_Term');
        /* @var $termModel editor_Models_Term */
    	$saved = array();
         $sql = array();
    	foreach($terms2save as $term){
    		$lang = ($term->isSource)?$this->sourceLang:$this->targetLang;
    		$relTerms = $termModel->getTermEntryTermsByTaskGuidTermIdLangId($this->taskGuid, $term->id,$lang->getId());
                
    		foreach ($relTerms as $relTerm) {
                    if(isset($saved[$relTerm['id']])){
                        if($term->id == $relTerm['id']){
                            $sql[$relTerm['id']]['used'] = true;
                        }
    			continue;
                    }
                    $saved[$relTerm['id']] = true;
                    $sql[$relTerm['id']]['segmentId'] = (int)$segmentId;
                    $sql[$relTerm['id']]['isSource'] = (int)$term->isSource;
                    $sql[$relTerm['id']]['used'] = $term->id == $relTerm['id'];
                    $sql[$relTerm['id']]['termId'] = (int)$relTerm['id'];
                    $sql[$relTerm['id']]['transFound'] = (int)$term->transFound;
    		}
                //and now the corresponding lang (source or target) to get also target-terms not existent in the target segment and vice versa
    		$lang = ($term->isSource)?$this->targetLang:$this->sourceLang;
    		$relTerms = $termModel->getTermEntryTermsByTaskGuidTermIdLangId($this->taskGuid,$term->id,$lang->getId());
    		foreach ($relTerms as $relTerm) {
                    if(isset($saved[$relTerm['id']])){
                        if($term->id == $relTerm['id']){
                            $sql[$relTerm['id']]['used'] = true;
                        }
    			continue;
                    }
                    $saved[$relTerm['id']] = true;
                    $sql[$relTerm['id']]['segmentId'] = (int)$segmentId;
                    $sql[$relTerm['id']]['isSource'] = $term->isSource?0:1;
                    $sql[$relTerm['id']]['used'] = $term->id == $relTerm['id'];
                    $sql[$relTerm['id']]['termId'] = (int)$relTerm['id'];
                    $sql[$relTerm['id']]['transFound'] = (int)$term->transFound;
    		}
    		$saved[$term->id] = true;
    	}
        if(count($sql)>0){
            foreach ($sql as &$value) {
                $value = " (NULL , ".
                    $value['segmentId'].
                    ", '".$value['isSource']."', '".
                    $value['used'].
                    "', '".$value['termId']."', '".$value['transFound']."')";
            }
            $this->db->query(
                'INSERT INTO `LEK_segments2terms` (`id` ,`segmentId` ,`isSource` ,`used` ,`termId` ,`transFound`) VALUES'. implode(',', $sql));
        }
    }
}