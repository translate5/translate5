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

/** #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */

/**
 * Parsing of transit-files for the import
 * 
 * Difference to other importers: We do not use placeholders in skeleton-files
 * due to the way beo transit-classes work (they always generate the whole file
 * from a DOM on save)
 *
 *
 */
class editor_Models_Import_FileParser_Transit extends editor_Models_Import_FileParser{
    
    use editor_Plugins_Transit_TraitParse;
    /**
     *
     * @var string
     */
    protected $sourcePath;
    /**
     *
     * @var string
     */
    protected $sourceExtension;
    /**
     *
     * @var string
     */
    protected $targetExtension;
    /**
     *
     * @var string
     */
    protected $origSourceFile;
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
     *
     * @var ZfExtended_Log 
     */
    protected $log;
    
    /**
     * used for parsing of a segment
     * @var array 
     */
    protected $segmentParts = array();
    
    /**
     * used for parsing of a segment
     * @var integer
     */
    protected $segmentPartsCount = 0;
    /**
     * used for parsing of a segment; is the Nr. a tag is marked with in the Javascript view
     * @var integer
     */
    protected $shortTagIdent = 1;
    /**
     * used for parsing of a segment; endTags contains information about endTags found in a segment
     * @var array 
     */
    protected $endTags = array();
    /**
     *
     * @var editor_Models_Segment_Meta
     */
    protected $meta;

    /**
     * 
     * @param string $path
     * @param string $fileName
     * @param integer $fileId
     * @param boolean $edit100PercentMatches
     * @param editor_Models_Languages $sourceLang
     * @param editor_Models_Languages $targetLang
     * @param editor_Models_Task $task
     */
    public function __construct(string $path, string $fileName, integer $fileId, boolean $edit100PercentMatches, editor_Models_Languages $sourceLang, editor_Models_Languages $targetLang, editor_Models_Task $task){
        parent::__construct($path, $fileName, $fileId, $edit100PercentMatches, $sourceLang, $targetLang, $task);
        $this->meta = ZfExtended_Factory::get('editor_Models_Segment_Meta');
        $this->meta->addMeta('transitLockedForRefMat', editor_Models_Segment_Meta::META_TYPE_BOOLEAN, 0, 'defines, if segment is marked in transitFile as locked for translation memory use');
        $transitLangInfo = Zend_Registry::get('transitLangInfo');
        $this->sourceExtension = $transitLangInfo['source'];
        $this->targetExtension = $transitLangInfo['target'];
        $this->sourcePath = preg_replace('"'.$this->targetExtension.'\.transit$"', $this->sourceExtension, $path);
        $this->origSourceFile = file_get_contents($this->sourcePath);
        $this->sourceDOM = ZfExtended_Factory::get('editor_Plugins_Transit_File');
        $this->sourceDOM->open($this->origSourceFile,$this->sourcePath);
        $this->targetDOM = ZfExtended_Factory::get('editor_Plugins_Transit_File');
        $this->targetDOM->open($this->_origFile,$this->_path);
        $this->log = ZfExtended_Factory::get('ZfExtended_Log');
    }
    
    /**
     * creates a zipped file which contains source and target
     * and sets the skeletonfile for transit to this file
     */
    protected function setSkeletonfile() {
        $config = Zend_Registry::get('config');
        $tmpDir = $config->runtimeOptions->dir->tmp;
        $zipFilePath = $tmpDir.DIRECTORY_SEPARATOR.$this->_fileName.'.zip';
        
        $zip = new ZipArchive();
        $res = $zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($res !== true) {
            throw new Zend_Exception('Creation of zipfile for import failed. Return of zip-opening had been: '.$res);
        }
                
        $res = $zip->addFile($this->sourcePath, basename($this->sourcePath));
        if ($res !== true) {
            trigger_error('Could not add sourcePath to zip: '.$this->sourcePath.' - reported problem had been: '.$res);
        }
        
        $res = $zip->addFile($this->_path, basename($this->_path));
        if ($res !== true) {
            trigger_error('Could not add targetPath to zip: '.$this->_path.' - reported problem had been: '.$res);
        }
        $zip->close();
        $this->_skeletonFile = file_get_contents($zipFilePath);
        unlink($zipFilePath);
    }
    /**
     * übernimmt das eigentliche FileParsing
     *
     * - ruft untergeordnete Methoden für das Fileparsing auf, wie extractSegment, setSegmentAttribs
     */
    protected function parse(){
        $counterTrans = 0;
        $this->setSkeletonfile();
        if(!$this->isEvenLanguagePair()){
            return;
        }
        $sourceSegs = $this->sourceDOM->getSegments();
        $targetSegs = $this->targetDOM->getSegments();
        foreach ($targetSegs as $segId => $seg) {
            $source = $sourceSegs[$segId]->getText();
            $target = $seg->getText();
            //skip segments, which contain only tags
            if($this->containsOnlyTagsOrEmpty($source)&&$this->containsOnlyTagsOrEmpty($target)){
                continue;
            }
            $this->setMid($segId);
            $this->setSegmentAttribs($seg);
            $transUnit = array('source'=>$source,'target'=>$target);
            
            $this->extractSegment($transUnit);
            $segmentId = (int)$this->setAndSaveSegmentValues();
            $this->setSegmentsMeta($seg,$target,$segmentId);//pass target instead of getting it inside of setSegmentsMeta to save performance
            $counterTrans++;
        }
        
        if ($counterTrans === 0) {
            $this->log->logError('Die Datei ' . $this->_fileName . ' enthielt keine übersetzungsrelevanten Segmente!');
        }
        
        //TODO: prüfen, ob lockedForRefMat und notTranslated (sowohl mit Status in transit als auch durch leeres Zielsegment, aber nicht Quellsegment) korrekt gesperrt werden; Export: schreiben von Quellsegmenten prüfen; prüfen, was sonst noch geprüft werden muss
        
        //@TODO: test isEvenLanguagePair, whitespace, mehrere Dateien, überall bei trigger_error E_USER_ERROR setzen
    }
    
    /**
     * @param array $transUnit array('source' => DOM_DOCUMENT,'target' => DOM_DOCUMENT)
     */
    protected function extractSegment($transUnit){
        $this->segmentData = array();
        $sourceName = $this->segmentFieldManager->getFirstSourceName();
        $targetName = $this->segmentFieldManager->getFirstTargetName();
        
        $this->segmentData[$sourceName] = array(
            'original' => $this->parseSegment($transUnit['source'], true),
            'originalMd5' => md5($transUnit['source'])
        );
        
        $this->segmentData[$targetName] = array(
            'original' => $this->parseSegment($transUnit['target'], false),
            'originalMd5' => md5($transUnit['target'])
        );
    }
    /**
     * @param array $transUnit array('source' => DOM_DOCUMENT,'target' => DOM_DOCUMENT)
     * @param string $targetText
     * @param integer $segmentId 
     */
    protected function setSegmentsMeta(editor_Plugins_Transit_Segment $targetseg,string $targetText,integer $segmentId) {
        $save = false;
        if($targetseg->getAccessStatus()===editor_Plugins_Transit_Segment::ACCESS_NO_REFMAT){
            $save = true;
            $this->meta->setTransitLockedForRefMat(1);
        }
        if($targetseg->getStatus()===editor_Plugins_Transit_Segment::STATUS_NOT_TRANSLATED || $targetText === ''){
            //@todo: enable setting of $this->meta->setNotTranslated(1); on empty target for other import formats
            $save = true;
            $this->meta->setNotTranslated(1);
        }
        if($save){
            $this->meta->setSegmentId($segmentId);
            $this->meta->setTaskGuid($this->task->getTaskGuid());
            $this->meta->save();
        }
    }
    
    /**
     * Sets $this->_matchRateSegment and $this->_autopropagated
     * for the segment currently worked on
     *
     * @param editor_Plugins_Transit_Segment $segment
     */
    protected function setSegmentAttribs($segment){
        //segment-id of transit is used as mid and thus used here
        $this->_matchRateSegment[$segment->getId()] = (int)$segment->getMatchValue();
        $this->_autopropagated[$segment->getId()] = false;
    }
    
    /**
     * Konvertiert in einem Segment (bereits ohne umschließende Tags) die PH-Tags für ExtJs
     *
     * @param string $segment
     * @param boolean $isSource
     * @return string $segment enthält anstelle der Tags die vom JS benötigten Replacement-Tags
     *         wobei die id die ID des Segments in der Tabelle Segments darstellt
     */
    protected function parseSegment($segment, $isSource){
        $segment = $this->parseSegmentProtectWhitespace($segment);
        if (strpos($segment, '<')=== false) {
            return $segment;
        }
        $this->endTags = array();
        $this->shortTagIdent = 1;
        
        $this->segmentParts = preg_split('"(<F?o?n?t?Tag [^>/]*>.*?</F?o?n?t?Tag>)"', $segment, NULL, PREG_SPLIT_DELIM_CAPTURE);
        $this->segmentPartsCount = count($this->segmentParts);
        
        $this->checkForUndefinedTags();
        
        $this->parseEndTags('Tag');
        $this->parseBeginTags('Tag');
        
        $this->parseEndTags('FontTag');
        $this->parseBeginTags('FontTag');
        
        $this->parseWhitespace();
        return implode('', $this->segmentParts);
    }
    /**
     * 
     * @param string $tag
     * @return integer $tagNr | false if not found
     */
    protected function getTransitTagNr(string $tag) {
        $matches = array();
        $pregMatch = preg_match('"^.*? i=\"(\d+)\".*?$"', $tag,$matches);
        if($pregMatch !==1 && strpos($tag,' pos="End"')!== false){
            trigger_error('No transit tag-number found in end-tag - but should be present. Tag: '.$tag.' and filePair: '.$this->_path);
        }
        if(!isset($matches[1])){
            return false;
        }
        return (int)$matches[1];

    }
    /**
     * 
     * @param string $tag
     * @param string $tagName
     * @return string
     */
    protected function getTagText(string $tag,string $tagName) {
        return preg_replace('"<'.$tagName.' .*?>(.*?)</'.$tagName.'>"', '\\1', $tag);
    }
    
    protected function parseWhitespace() {
        //@TODO: This should be removed here and in other fileParsers and moved to parent fileParser- antipattern. This must be done after merge in master due to things already done at this part there
        $search = array(
                '#<hardReturn />#',
                '#<softReturn />#',
                '#<macReturn />#',
                '#<space ts="[^"]*"/>#',
        );
        for($i = 0; $i < $this->segmentPartsCount; $i++) {
            //set data needed by $this->whitespaceTagReplacer
            $this->_segment = $this->segmentParts[$i];
            $this->segmentParts[$i] = preg_replace_callback($search, array($this,'whitespaceTagReplacer'), $this->segmentParts[$i]);
            $i++;
        }
    }
    /**
     * 
     */
    protected function checkForUndefinedTags(){
        //parse only even array-elements, because in those there should be no tags
        for($i = 0; $i < $this->segmentPartsCount; $i++) {
            //check for undefined tags
            $part = &$this->segmentParts[$i];
            if (strpos($part ,'<')!== false){
                throw new Zend_Exception('In the segmentPart '.$part.' a tag has been found.');
            }
            $i++;
        }
    }
    /**
     * 
     * @param string $tagName
     */
    protected function parseEndTags(string $tagName){
        //parse only uneven array-elements, because those are the tags
        for($i = 1; $i < $this->segmentPartsCount; $i++) {
            $tag = &$this->segmentParts[$i];
            if (strpos($tag ,'<'.$tagName.' pos="End"')!== false){
                $this->endTags[$this->shortTagIdent] = $this->getTransitTagNr($tag);
                $tagText = $this->getTagText($tag, $tagName);
                $fileNameHash = md5($tagText);

                $p = $this->getTagParams($tag, $this->shortTagIdent++, $tagName, $fileNameHash, $tagText);
                $tag = $this->_rightTag->getHtmlTag($p);
                $this->_rightTag->createAndSaveIfNotExists($tagText, $fileNameHash);

                $this->_tagCount++;
            }
            $i++;
        }
    }
        
    protected function parseBeginTags(string $tagName) {
        //parse nur die ungeraden Arrayelemente, den dies sind die Rückgaben von PREG_SPLIT_DELIM_CAPTURE
        for($i = 1; $i < $this->segmentPartsCount; $i++) {
            $tag = &$this->segmentParts[$i];
            if (strpos($tag ,'<'.$tagName.' pos="Begin"')!== false){
                $tagType = '_leftTag';
                $transitTagNr = $this->getTransitTagNr($tag);
                $shortTagIdent = array_search($transitTagNr, $this->endTags);
                if($shortTagIdent === false){
                    $tagType = '_singleTag';
                    $shortTagIdent = $this->shortTagIdent++;
                }
                $tagText = $this->getTagText($tag, $tagName);
                $fileNameHash = md5($tagText);

                $p = $this->getTagParams($tag, $shortTagIdent, $tagName, $fileNameHash, $tagText);
                $tag = $this->$tagType->getHtmlTag($p);
                $this->$tagType->createAndSaveIfNotExists($tagText, $fileNameHash);

                $this->_tagCount++;
            }
            $i++;
        }
    }
}
