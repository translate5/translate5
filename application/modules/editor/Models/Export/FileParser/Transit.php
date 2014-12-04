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

  /**
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
        parent::__construct($fileId, $diff,$task,$path);
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
        foreach ($targetSegs as $segId => &$targetSeg) {
            $sourceSeg = $sourceSegs[$segId];
            $sourceOrigText = $sourceSeg->getText();
            $targetOrigText = $targetSeg->getText();
            //skip segments, which contain only tags
            if($this->containsOnlyTagsOrEmpty($sourceOrigText)&&$this->containsOnlyTagsOrEmpty($targetOrigText)){
                continue;
            }
          
            $sourceText = $this->getSegmentContent($segId, editor_Models_SegmentField::TYPE_SOURCE);
            $sourceSeg->setText($sourceText);

            $targetText = $this->getSegmentContent($segId, editor_Models_SegmentField::TYPE_TARGET);
            $targetSeg->setText($targetText);
            
            $this->setSegmentInfoField($targetSeg, $segId);
            
        }
        $this->sourceDOM->save();
        $this->_exportFile = $this->targetDOM->getAsString();
        //@TODO: setzen des Infofelds, ; prüfen ob im Transit Änderungen und Tags und Infofeld korrekt drin sind - Infofeld auch mit Umlauten und Termen
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
        $segmentModel = $this->getSegment($segId);
        
        if((int)$this->config->runtimeOptions->plugins->transit->writeInfoField->exportDate === 1){
            $session = new Zend_Session_Namespace();
            if(preg_match('"^de"i', $session->locale === 1)){
                $infoFieldContent .= date("d.m.Y").':';
            }
            else{
                $infoFieldContent .= date("Y-m-d").':';
            }
        }
        if((int)$this->config->runtimeOptions->plugins->transit->writeInfoField->manualStatus === 1){
            $termModel = ZfExtended_Factory::get('editor_Models_Term');
            /* @var $termModel editor_Models_Term */
            $stateId = $segmentModel->getStateId();
            $state = $this->config->runtimeOptions->segments->stateFlags->$stateId;
            $infoFieldContent .= ' '.$state;
        }
       if((int)$this->config->runtimeOptions->plugins->transit->writeInfoField->termsWithoutTranslation === 1){
           //<div class="term admittedTerm" id="term_193_es-ES_1-6" title="Spanischer Beschreibungstexttest">tamiz de cepillos rotativos</div>
            $getTransNotFoundTermIdRegEx = '#<div.*?(class=".*?((term.*?transNotFound)|(transNotFound.*?term)).*?".*?data-tbxid="(.*?)".*?>)|(data-tbxid="(.*?)".*?class=".*?((term.*?transNotFound)|(transNotFound.*?term)).*?".*?>)#';

            $sourceOrigText = $segmentModel->getFieldOriginal(editor_Models_SegmentField::TYPE_SOURCE);
            preg_match_all($getTransNotFoundTermIdRegEx, $sourceOrigText, $matches);
            $sourceTermMids = $matches[5];
            $sourceTerms = array();
            foreach ($sourceTermMids as $mid) {
                $termModel->loadByMid($mid);
                $sourceTerms[] = $termModel->getTerm();
            }
            $infoFieldContent .= '; '.$this->translate->_('QuellTerme').': '.  join(', ', $sourceTerms).'; '.$this->translate->_('ZielTerme').';';
       }
       $transitSegment->setInfo($infoFieldContent);
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
        return parent::parseSegment($segment);
    }
}