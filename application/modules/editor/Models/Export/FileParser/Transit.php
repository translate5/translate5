<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

    /**
     * @param editor_Models_Task $task
     * @param int $fileId
     * @param string $path
     * @param array $options
     */
    public function __construct(editor_Models_Task $task, int $fileId, string $path, array $options = []) {
        parent::__construct($task, $fileId, $path, $options);
        $this->targetFileName = basename($path);
        //stand: herausfinden von source-namen anhand des path und speichern der source-Datei im exportfolder
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Models_Export_FileParser::getSkeleton()
     * Does additional transit specific handling of the skel file
     */
    protected function getSkeleton(editor_Models_File $file) {
        parent::getSkeleton($file);
        $extractDir = $this->extractSkeletonZip();
        $this->setSkeletonFiles($extractDir);
        $recursivedircleaner = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper('Recursivedircleaner');
        $recursivedircleaner->delete($extractDir);
    }
    /**
     * sets $this->_skeletonFile to the contents of the target instead of the zip, which is in DB
     * moves the sourcefile to the exportDir
     * @param string $extractDir
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
        if(!$this->isEvenLanguagePair($this->_task->getTaskGuid(), $this->sourcePath, $this->path)){
            $this->_exportFile = $this->targetDOM->getAsString();
            return;
        }
        $sourceSegs = $this->sourceDOM->getSegments();
        $targetSegs = $this->targetDOM->getSegments();
        $exportOnlyEditable = $this->config->runtimeOptions->plugins->transit->exportOnlyEditable;
        foreach ($targetSegs as $segId => &$targetSeg) {
            /* @var $targetSeg editor_Plugins_Transit_Segment */
            $sourceSeg = $sourceSegs[$segId];
            /* @var $sourceSeg editor_Plugins_Transit_Segment */
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
            
            if(!empty($sourceText) || $sourceText === '0'){
                $sourceSeg->setText($sourceText);
            }

            $targetText = $this->getSegmentContent($segId, editor_Models_SegmentField::TYPE_TARGET);
            $targetSeg->setText($targetText);
            
            $this->setSegmentInfoField($targetSeg, $segId);
            
            $targetSeg->setMatchValue($this->_segmentEntity->getMatchRate());
        }
        $this->sourceDOM->save();
        $this->_exportFile = $this->targetDOM->getAsString();
        //@TODO: setzen der Zielterme des Infofelds, ; prüfen ob im Transit Änderungen und Tags und Infofeld korrekt drin sind - Infofeld auch mit Umlauten und Termen
    }
    /**
     *
     * @param editor_Plugins_Transit_Segment $transitSegment
     * @param int $segId segId from transit-file - identical with mid from db
     */
    protected function setSegmentInfoField(editor_Plugins_Transit_Segment &$transitSegment,int $segId) {
        if((int)$this->config->runtimeOptions->plugins->transit->writeInfoField->enabled !== 1){
            return;
        }
        $infoFieldContent = $this->translate->_('###RefMat-Update ');
        $segment = $this->getSegment($segId);
        
        //fill only edited (and therefore editable) segments
        if(! $segment->getEditable()) {
            return;
        }
        
        $params = array($this->_task, $this->config, $segment, $this->translate);
        $infoFiledHelper = ZfExtended_Factory::get('editor_Models_Export_FileParser_TransitInfoField', $params);
        /* @var $infoFiledHelper editor_Models_Export_FileParser_TransitInfoField */
        
        $transitSegment->setInfo($infoFiledHelper->addInfos($infoFieldContent));
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
     * @param int $segId as found in untouch transit file - has to be identical with mid of found segment
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
            //since FileParser instance is recreated per file, we have to give the fileid
            $seg = $segment->loadFirst($taskGuid, $this->_fileId);
            $this->currentId = $seg->getId();
            $this->segmentCache[$segId] = $seg;
        }
        else{
            //since FileParser instance is recreated per file, we have to give the fileid
            $seg = $segment->loadNext($taskGuid,$this->currentId, $this->_fileId);
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
