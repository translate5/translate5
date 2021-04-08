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
 * this assumes, that there are no nested internal tags in transit
 *
 */
class editor_Models_Import_FileParser_Transit extends editor_Models_Import_FileParser{
    use editor_Plugins_Transit_TraitParse;
    use editor_Models_Import_FileParser_TagTrait;
    
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
     * used for assigning beginning tags to ending tags
     * @var array
     */
    protected $beginINumbers = array();
    /**
     * used for assigning beginning tags to ending tags
     * @var array
     */
    protected $endINumbers = array();
        
    /**
     * used for parsing of a segment; endTags contains information about endTags found in a segment
     * @var array
     */
    protected $endTags = array();

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::getFileExtensions()
     */
    public static function getFileExtensions() {
        return ['transit'];
    }
    
    /**
     *
     * @param string $path
     * @param string $fileName
     * @param int $fileId
     * @param editor_Models_Task $task
     */
    public function __construct(string $path, string $fileName, int $fileId, editor_Models_Task $task){
        parent::__construct($path, $fileName, $fileId, $task);
        $this->initImageTags();
        $meta = ZfExtended_Factory::get('editor_Models_Segment_Meta');
        /* @var $meta editor_Models_Segment_Meta */
        $meta->addMeta('transitLockedForRefMat', editor_Models_Segment_Meta::META_TYPE_BOOLEAN, 0, 'defines, if segment is marked in transitFile as locked for translation memory use');
        
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
        $tmpDir = $this->config->runtimeOptions->dir->tmp;
        $zipFilePath = $tmpDir.DIRECTORY_SEPARATOR.$this->_fileName.'.zip';
        
        $zip = new ZipArchive();
        $res = $zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($res !== true) {
            trigger_error('Creation of zipfile for import failed. Return of zip-opening had been: '.$res);
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
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::parse()
     */
    protected function parse(){
        $counterTrans = 0;
        $this->setSkeletonfile();
        if(!$this->isEvenLanguagePair($this->_taskGuid, $this->sourcePath, $this->_path)){
            trigger_error("The number of segments of source- and target-files are not identical");
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
            //segment-id of transit is used as mid and thus used here
            $attributes = $this->createSegmentAttributes($seg->getId());
            //from transit we support only the matchRate at the moment, rest is default
            $attributes->matchRate = (int)$seg->getMatchValue();
            $transUnit = array('source'=>$source,'target'=>$target);
            
            $this->extractSegment($transUnit);
            $this->addCustomSegmentsMeta($attributes, $seg, $target);//pass target instead of getting it inside of updateSegmentsMeta to save performance
            $this->setAndSaveSegmentValues();
            $counterTrans++;
        }
        
        if ($counterTrans === 0) {
            $this->log->logError('Die Datei ' . $this->_fileName . ' enthielt keine übersetzungsrelevanten Segmente!');
        }
        
        //TODO: prüfen, ob lockedForRefMat und notTranslated (sowohl mit Status in transit als auch durch leeres Zielsegment, aber nicht Quellsegment) korrekt gesperrt werden; prüfen, was sonst noch geprüft werden muss
    }
    
    /**
     * @param array $transUnit array('source' => DOM_DOCUMENT,'target' => DOM_DOCUMENT)
     */
    protected function extractSegment($transUnit){
        $this->segmentData = array();
        $sourceName = $this->segmentFieldManager->getFirstSourceName();
        $targetName = $this->segmentFieldManager->getFirstTargetName();
        
        $this->segmentData[$sourceName] = array(
            'original' => $this->parseSegment($transUnit['source'], true)
        );
        
        $this->segmentData[$targetName] = array(
            'original' => $this->parseSegment($transUnit['target'], false)
        );
    }
    /**
     * @param array $transUnit array('source' => DOM_DOCUMENT,'target' => DOM_DOCUMENT)
     * @param string $targetText
     * @param int $segmentId
     */
    protected function addCustomSegmentsMeta(editor_Models_Import_FileParser_SegmentAttributes $attributes, editor_Plugins_Transit_Segment $targetseg,string $targetText) {
        if($targetseg->getAccessStatus()===editor_Plugins_Transit_Segment::ACCESS_NO_REFMAT){
            $attributes->customMetaAttributes['transitLockedForRefMat'] = 1;
        }
        if($targetseg->getStatus()===editor_Plugins_Transit_Segment::STATUS_NOT_TRANSLATED || $targetText === ''){
            //@todo: enable setting of $this->meta->setNotTranslated(1); on empty target for other import formats
            $attributes->customMetaAttributes['notTranslated'] = 1;
        }
    }
    
    /**
     * Konvertiert in einem Segment (bereits ohne umschließende Tags) die PH-Tags für ExtJs
     *
     * @param string $segment
     * @param bool $isSource
     * @return string $segment enthält anstelle der Tags die vom JS benötigten Replacement-Tags
     *         wobei die id die ID des Segments in der Tabelle Segments darstellt
     */
    protected function parseSegment($segment, $isSource){
        $segment = editor_Models_Segment_Utility::foreachSegmentTextNode($segment, function($text){
            return $this->utilities->whitespace->protectWhitespace($text);
        });
        if (strpos($segment, '<')=== false) {
            return $segment;
        }
        $this->endTags = array();
        $this->shortTagIdent = 1;
        
        $segment = $this->parseTags($segment);
        
        $segment = $this->replacePlaceholderTags($segment);
        $this->checkForUndefinedTags($segment);

        return $segment;
    }
    
    /**
     *
     * @param string $tag
     * @return integer $tagNr | false if not found
     */
    protected function getTransitTagNr(string $tag) {
        $matches = array();
        preg_match('"^.*? i=\"(\d+)\".*?$"', $tag,$matches);
        if(!isset($matches[1])){
            return false;
        }
        return (int)$matches[1];
    }
    
    protected function parseSubSegs($tagString){
        $protect = function($string) {
            return str_replace(array('~','</SubSeg>','µ','<SubSeg'), array('__TranSiT_TRANSTiLde__','~','__TranSiT_TRANSPiI__','µ'), $string);
        };
        $unprotect = function($string) {
            return str_replace( array('~','__TranSiT_TRANSTiLde__','µ','__TranSiT_TRANSPiI__'),array('</SubSeg>','~','<SubSeg','µ'), $string);
        };
        
        $tagString = $protect($tagString);
        $parts = preg_split('"([^~]*?µ[^>/]*?>)([^~]*?)(~[^~µ]*)"s', $tagString, NULL, PREG_SPLIT_DELIM_CAPTURE);
        $count = count($parts);
        $shortTagIdentOld = false;
        for($i = 0; $i < $count; $i++) {
            $tag = &$parts[$i];
            if (preg_match('"µ[^>/]*?>"s',$tag)=== 1){
                $tagName = 'SubSeg';
                $shortTagIdent = $this->shortTagIdent++;
                $tagText = 'SubSeg';
                $tagType = '_leftTag';
                $tag = $this->createTag($unprotect($tag), $shortTagIdent, $tagName, $tagType, $tagText);
            }
            elseif (strpos($tag, '~')!==false){
                if(!isset($shortTagIdent)||$shortTagIdent === $shortTagIdentOld){
                    trigger_error('In the file '.$this->sourcePath.' a closing SubSeg has been found before a corresponding opening SubSeg');
                }
                $shortTagIdentOld = $shortTagIdent;
                $tagName = 'SubSeg';
                $tagText = 'SubSeg';
                $tagType = '_rightTag';
                $tag = $this->createTag($unprotect($tag), $shortTagIdent, $tagName, $tagType, $tagText);
            }
            else{
                $tag = $protect($this->parseTags($unprotect($tag)));
            }
        }
        $tagString = implode('', $parts);
        return $unprotect($tagString);
    }
    
    protected function createTag($tag, $shortTagIdent, $tagName, $tagType, $tagText) {
        if(strpos($tagText, '<') !== false ||strpos($tagText, '"') !== false){
            $tagText = htmlspecialchars($tagText, ENT_QUOTES | ENT_XML1);
        }
        $p = $this->getTagParams($tag, $shortTagIdent, $tagName, $tagText);
        $tag = $this->$tagType->getHtmlTag($p);
        return $tag;
    }
    /**
     * new tags should also be added to containsOnlyTagsOrEmpty
     * this assumes, that there are no nested internal tags in transit
     *
     * @param string $segment
     */
    protected function parseTags(string $segment) {
        $qp = qp('<root>'.$segment.'</root>', ':root',array('format_output'=> false, 'encoding'=>'UTF-8','use_parser'=>'xml'));
        /* @var $qp \QueryPath\DOMQuery */
        $this->parsePairedTag($qp, 'Tag');
        $this->parsePairedTag($qp, 'FontTag');
        $this->parseSingleTag($qp, 'NL');
        $this->parseSingleTag($qp, 'NU');
        $this->parseSingleTag($qp, 'Tab');
        $this->parseSingleTag($qp, 'WS');
        $this->parseSingleTag($qp, 'UC');
        $this->parseSingleTag($qp, 'SegHiB');
        $this->parseSingleTag($qp, 'SegHiE');
        
        return $qp->innerXML();
    }
    
    /**
     *
     * @param \QueryPath\DOMQuery $qp
     * @param string $tagName
     */
    protected function parseSingleTag(\QueryPath\DOMQuery $qp, string $tagName) {
        $tagTags = $qp->find('root > '.$tagName);
        foreach ($tagTags->get(NULL, TRUE) as $tagTag) {
            $this->createSingleTag($tagTag,$tagName);
        }
    }
    
    protected function createSingleTag(DOMElement $tag,string $tagName) {
        $tagString = $tag->ownerDocument->saveXML($tag);
        $tagText = $this->getTagText($tagString, $tagName);
        $tagType = '_singleTag';
        $tagString = $this->createTag($tagString, $this->shortTagIdent++, $tagName, $tagType, $tagText);
        $this->replaceDOMElementWithXML($tagString, $tag);
    }
    
    protected function replaceDOMElementWithXML(string $xml,  DOMElement $element) {
        $dom = $element->ownerDocument;
        $f = $dom->createDocumentFragment();
        $f->appendXML($xml);
        $dom->documentElement->replaceChild($f, $element);
    }
    
    /**
     *
     * @param \QueryPath\DOMQuery $qp
     * @param string $tagName
     */
    protected function parsePairedTag(\QueryPath\DOMQuery $qp, string $tagName) {
        $tagTags = $qp->find('root > '.$tagName);
        foreach ($tagTags->get(NULL, TRUE) as $tagTag) {
            /* @var $tagTag DOMElement */
            $pos = $tagTag->getAttribute('pos');
            if($tagTag->hasAttribute('i')){
                if($pos === 'Begin'){
                    $this->beginINumbers[$tagTag->getAttribute('i')] = false;
                }
                if($pos === 'End'){
                    $this->endINumbers[$tagTag->getAttribute('i')] = false;
                }
            }
        }
        foreach ($tagTags->get(NULL, TRUE) as $tagTag) {
            /* @var $tagTag DOMElement */
            $pos = $tagTag->getAttribute('pos');
            $tagString = $tagTag->ownerDocument->saveXML($tagTag);
            $i = false;
            if($tagTag->hasAttribute('i')){
                $i = $tagTag->getAttribute('i');
            }
            if(strpos($tagString, '<SubSeg')!==false && strpos($tagString, '</SubSeg>')!==false){//the strpos insures, that <SubSeg/>-tags (without content) are not handled as SubSeg
                $tagString = $this->parseSubSegs($tagString);
                if($tagTag->hasAttribute('i')){
                    unset($this->beginINumbers[$i]);
                    unset($this->endINumbers[$i]);
                }
            }
            elseif ($pos === 'Point') {
                $this->createSingleTag($tagTag,$tagName);
                continue;
            }
            elseif($pos ==='Begin'){
                $tagString = $this->parseBeginTags($tagName, $tagString,$i);
            }
            elseif($pos ==='End'){
                $tagString = $this->parseEndTags($tagName, $tagString,$i);
            }
            $this->replaceDOMElementWithXML($tagString, $tagTag);
        }
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
    
    /**
     * checks if there are any tags not covered by parseTags or "Tag"-Tags or
     * "FontTag"-Tags not covered by their methods. Thus has to be placed before
     * whitespaceTagReplacer() and after all other tag-parsing methods
     */
    protected function checkForUndefinedTags(string $segment){
        $segment = preg_replace('/<div[^>]+class="(open|close|single).*?".*?\/div>/is', '', $segment);
        if (strpos($segment ,'<')!== false){
            trigger_error('In the file '.$this->sourcePath.' in the segment '.$segment.' an undefined tag has been found.');
        }
    }
    
    /**
     *
     * @param string $tagName
     */
    protected function parseEndTags(string $tagName, string $tag,$transitTagNr){
        $tagType = '_rightTag';
        if(!$transitTagNr){
            $tagType = '_singleTag';
        }
        $shortTagIdent = $this->getShortTagIdent($transitTagNr);
        $tagText = $this->getTagText($tag, $tagName);
        $tag = $this->createTag($tag, $shortTagIdent, $tagName, $tagType, $tagText);
        return $tag;
    }
    
    protected function getShortTagIdent($transitTagNr) {
        if($transitTagNr && isset($this->beginINumbers[$transitTagNr])
                && isset($this->endINumbers[$transitTagNr])){
            if(!$this->beginINumbers[$transitTagNr]||!$this->endINumbers[$transitTagNr]){
                $this->beginINumbers[$transitTagNr] = $this->shortTagIdent++;
                $this->endINumbers[$transitTagNr] = $this->beginINumbers[$transitTagNr];
            }
            return $this->beginINumbers[$transitTagNr];
        }
        return $this->shortTagIdent++;
//@todo        auch sicherstellen, dass nicht end oder begin-tags mit dem selben i vorkommen
    }
    
    protected function parseBeginTags(string $tagName, string $tag,$transitTagNr) {
        $tagType = '_leftTag';
        if(!$transitTagNr){
            $tagType = '_singleTag';
        }
        $shortTagIdent = $this->getShortTagIdent($transitTagNr);
        $tagText = $this->getTagText($tag, $tagName);
        $tag = $this->createTag($tag, $shortTagIdent, $tagName, $tagType, $tagText);
        return $tag;
    }
}
