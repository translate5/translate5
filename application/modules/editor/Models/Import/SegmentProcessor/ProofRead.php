<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

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
     * @var editor_Models_Languages Entity Instanz der Sprache
     */
    protected $sourceLang = null;
    
    /**
     * @var editor_Models_Languages Entity Instanz der Sprache
     */
    protected $targetLang = null;
    
    /**
     * @param editor_Models_Task $task
     * @param editor_Models_Languages $sourceLang
     * @param editor_Models_Languages $targetLang,
     * @param string $userGuid
     * @param string $userName
     */
    public function __construct(editor_Models_Task $task, editor_Models_Languages $sourceLang, editor_Models_Languages $targetLang, $userGuid, $userName){
        parent::__construct($task);
        $this->sourceLang = $sourceLang;
        $this->targetLang = $targetLang;
        $this->userGuid = $userGuid;
        $this->userName = $userName;
        $this->db = Zend_Registry::get('db');
        $this->taskConf = $this->task->getAsConfig();
    }

    public function process(editor_Models_Import_FileParser $parser){
        $seg = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $seg editor_Models_Segment */
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
        
        $this->segmentNrInTask++;
        $seg->setSegmentNrInTask($this->segmentNrInTask);
        $seg->setFieldContents($parser->getSegmentFieldManager(), $parser->getFieldContents());
        
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
        $this->saveFieldWidth($parser);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_SegmentProcessor::postProcessHandler()
     */
    public function postProcessHandler(editor_Models_Import_FileParser $parser, $segmentId) {
        $this->calculateFieldWidth($parser);
    }
    
    /**
     * befüllt für alle Terms eines Segments die Tabellen LEK_terminstances
     * und LEK_segment2terms auf Basis der im parser gesammelten Terme
     * 
     * @todo sofern die Term Speicher Geschichte wiederverwendet werden soll (z.B. in den Relais), diese auch auslagern (see TRANSLATE-22)
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
        if(empty($terms2save)) {
            return;
        }
        foreach($terms2save as $term){
            $data = array(
                        'NULL',
                        (int)$segmentId,
                        (int)$term->id,
                        $this->db->quote((string)$term->term),
                        (int)$term->projectTerminstanceId,
                    );
            $sql[]= ' ('.join(',',$data).')';
        }
        $sql = 'INSERT INTO `LEK_terminstances` (`id` ,`segmentId` ,`termId` ,`term` ,`projectTerminstanceId`) VALUES'. join(',', $sql);
        $this->db->query($sql);
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
            $relTerms = $termModel->getTermGroupEntries($this->taskGuid, $term->id,$lang->getId());

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
            $relTerms = $termModel->getTermGroupEntries($this->taskGuid,$term->id,$lang->getId());
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