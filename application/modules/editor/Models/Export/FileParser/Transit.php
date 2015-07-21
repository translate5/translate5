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

/** #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */

/**
 * @see readme.md in the transit plugin directory!
 * 
 * Parsed mit editor_Models_Import_FileParser_Transit geparste Dateien für den Export
 */
class editor_Models_Export_FileParser_Transit extends editor_Models_Export_FileParser {
    use editor_Plugins_Transit_TraitParse;
    
    /**
     * @var string Klassenname des Difftaggers
     */
    protected $_classNameDifftagger = 'editor_Models_Export_DiffTagger_Csv';
    /**
     *
     * @var string  (is local encoded)
     */
    protected $sourcePath;
    /**
     *
     * @var string (is local encoded)
     */
    protected $sourceFileName;
    /**
     *
     * @var string (is local encoded)
     */
    protected $targetFileName;
    /**
     *
     * @var DOMDocument 
     */
    protected $sourceDOM;
    /**
     *
     * @var DOMDocument 
     */
    protected $targetDOM;
    /**
     * current id as used by getSegment
     * @var integer
     */
    protected $currentId;

    public function __construct(integer $fileId, boolean $diff,editor_Models_Task $task,string $path) {
        parent::__construct($fileId, $diff, $task, $path);
        $this->targetFileName = basename($path);
        //stand: herausfinden von source-namen anhand des path und speichern der source-Datei im exportfolder
    }
    
    /**
     * sets $this->_skeletonFile
     */
    protected function getSkeleton() {
        parent::getSkeleton();
        $extractDir = $this->extractSkeletonZip();
        $this->setSkeletonFiles($extractDir);
        $recursivedircleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper('Recursivedircleaner');
        $recursivedircleaner->delete($extractDir);
    }
    /**
     * sets $this->_skeletonFile to the contents of the target instead of the zip, which is in DB
     * moves the sourcefile to the exportDir
     * @param type $extractDir
     * @throws Zend_Exception
     */
    protected function setSkeletonFiles($extractDir) {
        $iterator = ZfExtended_Factory::get('DirectoryIterator',array($extractDir));
        /* @var $fileinfo DirectoryIterator */
        foreach ($iterator as $fileinfo) {
            $extension = strtolower($fileinfo->getExtension());
            $pathname = $fileinfo->getPathname();
            if($extension !== 'zip' && $extension !== 'transit' && !is_dir($pathname)) {
                $this->sourceFileName = $fileinfo->getFilename();
                $sourceFile = file_get_contents($pathname);
                break;
            }
        }
        $this->sourcePath = dirname($this->path).DIRECTORY_SEPARATOR.$this->sourceFileName;
        
        $this->sourceDOM = ZfExtended_Factory::get('editor_Plugins_Transit_File');
        $this->sourceDOM->open($sourceFile,  $this->sourcePath);
        
        $this->_skeletonFile = file_get_contents($extractDir.DIRECTORY_SEPARATOR.$this->targetFileName);
        $this->targetDOM = ZfExtended_Factory::get('editor_Plugins_Transit_File');
        $this->targetDOM->open($this->_skeletonFile,  $this->path);
    }
    /**
     * complete override of parent::parse, because we do not use placeholders with transit-files
     * but instead use beo-transit-classes to write changed segments
     */
    protected function parse(){
        if(!$this->isEvenLanguagePair()){
            $this->_exportFile = $this->targetDOM->getAsString();
            return;
        }
        $sourceSegs = $this->sourceDOM->getSegments();
        $targetSegs = $this->targetDOM->getSegments();
        $exportOnlyEditable = $this->config->runtimeOptions->plugins->transit->exportOnlyEditable;
        foreach ($targetSegs as $segId => &$targetSeg) {
            $sourceSeg = $sourceSegs[$segId];
            $sourceOrigText = $sourceSeg->getText();
            $targetOrigText = $targetSeg->getText();
            //skip segments, which contain only tags
            if($this->containsOnlyTagsOrEmpty($sourceOrigText)&&$this->containsOnlyTagsOrEmpty($targetOrigText)){
                continue;
            }
          
            $sourceText = $this->getSegmentContent($segId, editor_Models_SegmentField::TYPE_SOURCE);
            
            if($exportOnlyEditable && !$this->_segmentEntity->isEditable()) {
                $this->setSegmentInfoField($targetSeg, $segId);
                continue;
            }
            
            if(!empty($sourceText)){
                $sourceSeg->setText($sourceText);
            }

            $targetText = $this->getSegmentContent($segId, editor_Models_SegmentField::TYPE_TARGET);
            $targetSeg->setText($targetText);
            
            $this->setSegmentInfoField($targetSeg, $segId);
        }
        $this->sourceDOM->save();
        $this->_exportFile = $this->targetDOM->getAsString();
        //@TODO: setzen der Zielterme des Infofelds, ; prüfen ob im Transit Änderungen und Tags und Infofeld korrekt drin sind - Infofeld auch mit Umlauten und Termen
    }
    /**
     * 
     * @param editor_Plugins_Transit_Segment $transitSegment
     * @param integer $segId segId from transit-file - identical with mid from db
     */
    protected function setSegmentInfoField(editor_Plugins_Transit_Segment &$transitSegment,integer $segId) {
        if((int)$this->config->runtimeOptions->plugins->transit->writeInfoField->enabled !== 1){
            return;
        }
        $infoFieldContent = $this->translate->_('###RefMat-Update ');
        $segment = $this->getSegment($segId);
        
        //fill only edited (and therefore editable) segments
        if(! $segment->getEditable()) {
            return;
        }
        $infoFieldContent = $this->infoFieldAddDate($infoFieldContent);
        $infoFieldContent = $this->infoFieldAddStatus($infoFieldContent,$segment);
        $infoFieldContent = $this->infoFieldAddTerms($infoFieldContent,$segment);
        $transitSegment->setInfo($infoFieldContent);
    }
    
    /**
     * Add the changed terminology to the notice string, for Details see TRANSLATE-477
     * @param string $infoFieldContent
     * @param editor_Models_Segment $segment
     * @return string
     */
    protected function infoFieldAddTerms(string $infoFieldContent, editor_Models_Segment $segment) {
        if((int)$this->config->runtimeOptions->plugins->transit->writeInfoField->termsWithoutTranslation !== 1){
            return $infoFieldContent;
        }
        $taskGuid = $this->_task->getTaskGuid();
        $targetLang = $this->_task->getTargetLang();
        $termModel = ZfExtended_Factory::get('editor_Models_Term');
        /* @var $termModel editor_Models_Term */
        //<div class="term admittedTerm" id="term_193_es-ES_1-6" title="Spanischer Beschreibungstexttest">tamiz de cepillos rotativos</div>

        $sourceOrigText = $segment->getFieldOriginal(editor_Models_SegmentField::TYPE_SOURCE);
        //fetch all terms found in source
        $sourceTermsUsed = $termModel->getTermInfosFromSegment($sourceOrigText);
        $sourceTermMids = array();
        foreach($sourceTermsUsed as $termInfo) {
            $sourceTermMids[$termInfo['mid']] = $termInfo['classes'];
        }
        
        $targetTerms = array();
        
        //fetch terms from target orig
        $targetOrig = $segment->getFieldOriginal(editor_Models_SegmentField::TYPE_TARGET);
        $targetOrigTermMids = $termModel->getTermMidsFromSegment($targetOrig);
        
        //fetch terms from target edited
        $targetEdited = $segment->getFieldEdited(editor_Models_SegmentField::TYPE_TARGET);
        $targetEditedTermMids = $termModel->getTermMidsFromSegment($targetEdited);
        
        $sourceTermsToTrack = array();
        $targetTermsToTrack = array();
        
        //we have to select only terms, which were transNotFound at import time
        //and are now transFound at export time, that means: the associated targetTermMids 
        //of one term in source is not found in $targetOrigTermMids (transNotFound at import),
        //but is found in $targetEditedTermMids (transFound at export)
        foreach ($sourceTermMids as $mid => $termFlags) {
            //$mid => 1
            $targetTerms = $this->getTermGroupEntries($termModel, $mid, $taskGuid, $targetLang);
            if(empty($targetTerms)) {
                //no target terms found
                continue;
            }
            $foundAtImport = array();
            $foundAtExport = array();
            $foundAtExportMids = array();
            foreach($targetTerms as $targetTerm) {
                //capture mids found at import time
                if(in_array($targetTerm['mid'], $targetOrigTermMids)) {
                    $foundAtImport[] = $targetTerm;
                }
                //capture mids found at export time
                if(in_array($targetTerm['mid'], $targetEditedTermMids)) {
                    $foundAtExport[] = $targetTerm;
                    $foundAtExportMids[] = $targetTerm['mid'];
                }
            }
            //if source term changed from transNotFound at import
            //to transFound on export, this segments has to be tracked:
            if(empty($foundAtImport) && !empty($foundAtExport)) {
                $sourceTermsToTrack[] = $termModel->getTerm();
                //track only the target term which exists in the target:
                foreach($targetTerms as $targetTerm) {
                    if(in_array($targetTerm['mid'], $foundAtExportMids)) {
                        $targetTermsToTrack[] = $targetTerm['term'];
                    }
                }
            }
            $this->logFoundMismatch($segment, $mid, $termFlags, $targetTerms, $foundAtExport);
        }
        if(!empty($sourceTermsToTrack) || !empty($targetTermsToTrack)) {
            $infoFieldContent .= '; '.$this->translate->_('QuellTerme').': '.  join(', ', $sourceTermsToTrack).'; '.$this->translate->_('ZielTerme').': '.  join(', ', $targetTermsToTrack).';';
        }
        return $infoFieldContent;
    }
    
    /**
     * returns the term group of one mid and one language
     * @param editor_Models_Term $termModel
     * @param string $mid
     * @param string $taskGuid
     * @param int $targetLang
     * @return array empty array if nothing found
     */
    protected function getTermGroupEntries($termModel, $mid, $taskGuid, $targetLang) {
        try {
            $termModel->loadByMid($mid, $taskGuid);
            return $termModel->getTermGroupEntries($taskGuid, $termModel->getId(), $targetLang);
        } catch (ZfExtended_Models_Entity_NotFoundException $exc) {
            $log = ZfExtended_Factory::get('ZfExtended_Log');
            /* @var $log ZfExtended_Log */
            $msg = 'term has not been found in Database, which should be there. TaskGuid: '.$taskGuid;
            $msg .= '; Mid: '.$mid.'; Export continues. '.__FILE__.': '.__LINE__;
            $log->logError($msg);
        }
        return array();
    }
    
    /**
     * This methods logs the case if a source term is tagged as termNotFound,
     *   but an associated target term exists and vice versa 
     * @param editor_Models_Segment $segment
     * @param boolean $foundAtExport
     * @param array $termFlags
     * @param array $targetTerms
     * @param array $foundAtExport
     */
    protected function logFoundMismatch(editor_Models_Segment $segment, $mid, array $termFlags, array $targetTerms, array $foundAtExport) {
        $transFound = in_array('transFound', $termFlags);
        $transNotFound = in_array('transNotFound', $termFlags);
        $targetTerms = array_map(function($item){return $item['mid'];}, $targetTerms);
        $foundAtExport = !empty($foundAtExport);//if array not empty, we have found something
        if($foundAtExport && $transNotFound || !$foundAtExport && $transFound) {
            $log = ZfExtended_Factory::get('ZfExtended_Log');
            /* @var $log ZfExtended_Log */
            $msg = 'Source Term with mid '.$mid.' was marked as '.($foundAtExport?'transNotFound':'transFound');
            $msg .= ' but should be '.($foundAtExport?'transFound':'transNotFound').' Targets: '.join(', ',$targetTerms);
            $msg .= ' Segment: '.print_r($segment->getDataObject(),1)."\n in ".__FILE__.': '.__LINE__;
            $log->logError($msg);
        }
    }
    
    protected function infoFieldAddStatus(string $infoFieldContent,editor_Models_Segment $segment) {
        if((int)$this->config->runtimeOptions->plugins->transit->writeInfoField->manualStatus === 1){
            $stateId = $segment->getStateId();
            if(empty($stateId)){
                $state = 'NO_QUALITY_STATE_SET_BY_USER';
            }
            else{
                $state = $this->config->runtimeOptions->segments->stateFlags->$stateId;
            }
            $infoFieldContent .= ' '.$state;
        }
        return $infoFieldContent;
    }
    
    /**
     * Adds a date string to the infoFieldContent String, only if enabled
     * @param string $infoFieldContent
     * @return string
     */
    protected function infoFieldAddDate(string $infoFieldContent) {
        //if no transit plugin config exists, exit
        if(!isset($this->config->runtimeOptions->plugins->transit)) {
            return $infoFieldContent;
        }
        $transitConfig = $this->config->runtimeOptions->plugins->transit;
        
        //if config is disabled, exit
        if((int)$transitConfig->writeInfoField->exportDate !== 1){
            return $infoFieldContent;
        }
        
        //use configured value or if empty now()
        if(empty($transitConfig->writeInfoField->exportDateValue)){
            $date = time();
        }
        else {
            $date = strtotime($transitConfig->writeInfoField->exportDateValue);
        }
        $session = new Zend_Session_Namespace();
        if(preg_match('"^de"i', $session->locale) === 1){
            $infoFieldContent .= date("d.m.Y", $date).':';
        }
        else{
            $infoFieldContent .= date("Y-m-d", $date).':';
        }
        return $infoFieldContent;
    }
    
    public function saveFile() {
        file_put_contents(preg_replace('"\.transit$"i', '', $this->path), $this->getFile());
    }
    
    /**
     * 
     * @return string $extractDir path to dir to which the zip had been extracted
     * @throws Zend_Exception
     */
    protected function extractSkeletonZip(){
        $extractDir = $this->config->runtimeOptions->dir->tmp.DIRECTORY_SEPARATOR.'transitExportZip_'.md5($this->path);
        $exportZip = $extractDir.DIRECTORY_SEPARATOR.'export.zip';
        if(!mkdir($extractDir)){
            throw new Zend_Exception('The tmp-folder transitExportZip could not be created.');
        }
        if(!file_put_contents($exportZip, $this->_skeletonFile)){
            throw new Zend_Exception('The export.zip could not be written to tmp dir.');
        }
        $zip = ZfExtended_Factory::get('ZipArchive');
        if (!$zip->open($exportZip)) {
                throw new Zend_Exception('The file' . $exportZip . ' could not be opened!');
        }
        if(!$zip->extractTo($extractDir)){
                throw new Zend_Exception('The file ' . $exportZip . ' could not be extracted!');
        }
        $zip->close();
        return $extractDir;
    }
    
    /**
     * loads the segment to the given mid, caches a limited count of segments internally 
     * to prevent loading again while switching between fields
     * 
     * overrides parent, because parent needs id of segment and transit-parser only knows mid
     * @param integer $segId as found in untouch transit file - has to be identical with mid of found segment
     * @return editor_Models_Segment
     */
    protected function getSegment($segId){
        if(isset($this->segmentCache[$segId])) {
            return $this->segmentCache[$segId];
        }
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        $taskGuid = $this->_task->getTaskGuid();
        /* @var $segment editor_Models_Segment */
        if(is_null($this->currentId)){
            $seg = $segment->loadFirst($taskGuid);
            $this->currentId = $seg->getId();
            $this->segmentCache[$segId] = $seg;
        }
        else{
            $seg = $segment->loadNext($taskGuid,$this->currentId);
            $this->currentId = $seg->getId();
            $this->segmentCache[$segId] = $seg;
        }
        if($seg->getMid() != $segId){
            throw new Zend_Exception('segId had been not identical with mid of found segment - which has to be. segId: '.$segId.' mid: '.$seg->getMid());
        }
        //we keep a max of 5 segments, this should be enough
        if(count($this->segmentCache) > 5) {
            reset($this->segmentCache);
            $firstKey = key($this->segmentCache);
            unset($this->segmentCache[$firstKey]);
        }
        return $segment;
    }

    protected function parseSegment($segment) {
        //the following line is only necessary, since transit does not support MQM-tags. It can be removed, if this changes. Same is true for the comment in tasks.phtml 
        $segment = preg_replace('"<img[^>]*>"','', $segment);
        $segment = parent::parseSegment($segment);
        //at this moment there should be no other div-tags any more
        if($this->shouldTermTaggingBeRemoved()){
            $segment = str_replace('</div>', '', $segment);
            $segment = preg_replace('"<div .*?>"', '', $segment);
        }
        return $segment;
    }
    /**
     * overwrite, because recreation makes no sense. parseSegment simply keeps them, if necessary
     * @param string $segment
     * @param boolean $removeTermTags, default = true
     * @return string $segment
     */
    protected function recreateTermTags($segment, $removeTermTags=true) {
        return $segment;
    }
}
