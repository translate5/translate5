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
        
        //TODO: prüfen, ob lockedForRefMat und notTranslated (sowohl mit Status in transit als auch durch leeres Zielsegment, aber nicht Quellsegment) korrekt gesperrt werden; prüfen, was sonst noch geprüft werden muss
        
        //@TODO: test isEvenLanguagePair
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
        
        $this->splitSegment($segment);
        
        $this->parseWsTags();
        
        $this->parseSubSegs();
        
        $this->parseDefinedSingleTags();
        
        $this->parseEndTags('Tag');
        $this->parseBeginTags('Tag');
        
        $this->parseEndTags('FontTag');
        $this->parseBeginTags('FontTag');
        
        $this->checkForUndefinedTags();
        
        $this->parseWhitespace();
        return implode('', $this->segmentParts);
    }
    
    /**
     * protects whitespace inside a segment with a tag
     *
     * @param string $segment
     * @param integer $count optional, variable passed by reference stores the replacement count
     * @return string $segment
     */
    protected function parseSegmentProtectWhitespace($segment, &$count = 0) {
        $segment = parent::parseSegmentProtectWhitespace($segment, $count);
        $res = preg_replace_callback(
                array(
                    '"\x{0009}"u', //Hex UTF-8 bytes or codepoint of horizontal tab
                    '"\x{000B}"u', //Hex UTF-8 bytes or codepoint of vertical tab
                    '"\x{000C}"u', //Hex UTF-8 bytes or codepoint of page feed
                    '"\x{0085}"u', //Hex UTF-8 bytes or codepoint of control sign for next line
                    '"\x{00A0}"u', //Hex UTF-8 bytes or codepoint of protected space
                    '"\x{1680}"u', //Hex UTF-8 bytes or codepoint of Ogam space
                    '"\x{180E}"u', //Hex UTF-8 bytes or codepoint of mongol vocal divider
                    '"\x{2028}"u', //Hex UTF-8 bytes or codepoint of line separator
                    '"\x{202F}"u', //Hex UTF-8 bytes or codepoint of small protected space
                    '"\x{205F}"u', //Hex UTF-8 bytes or codepoint of middle mathematical space
                    '"\x{3000}"u', //Hex UTF-8 bytes or codepoint of ideographic space
                    '"[\x{2000}-\x{200A}]"u', //Hex UTF-8 bytes or codepoint of eleven different small spaces, Haarspatium and em space
                    ), //Hex UTF-8 bytes 	E2 80 9C//von mssql nicht vertragen
                        function ($match) {
                            return '<space ts="' . implode(',', unpack('H*', $match[0])) . '"/>';
                        }, 
            $segment, -1, $replaceCount);
        $count += $replaceCount;
        return $res;
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
    /**
     * single tags parsed by this method: NL, NU and "Tag pos="Point"
     * 
     * new tags should also be added to containsOnlyTagsOrEmpty
     */
    protected function parseDefinedSingleTags(){
        for($i = 1; $i < $this->segmentPartsCount; $i++) {
            $tag = &$this->segmentParts[$i];
           
            if (
                    strpos($tag ,'<Tab')!== false ||
                    strpos($tag ,'<NL')!== false ||
                    strpos($tag ,'<NU')!== false ||
                    strpos($tag ,'<Tag pos="Point"')!== false 
                ){
                if(preg_match('"^<([^ >]*)"s', $tag, $matches)!==1){
                    trigger_error('Tagname not found, something went wrong: '.$tag);
                }
                $tagName = $matches[1];
                $shortTagIdent = $this->shortTagIdent++;
                $tagText = $this->getTagText($tag, $tagName);
                $tagType = '_singleTag';
                $tag = $this->createTag($tag, $shortTagIdent, $tagName, $tagType, $tagText);
            }
            $i++;
        }
    }
    protected function parseSubSegs(){
        $subSegFound = false;
        for($i = 1; $i < $this->segmentPartsCount; $i++) {
            $tag = &$segmentParts[$i];
            if (preg_match('"<SubSeg[^>/]*?>"s',$tag)=== 1){
                $tagName = 'SubSeg';
                $shortTagIdent = $this->shortTagIdent++;
                $tagText = 'SubSeg';
                $tagType = '_leftTag';
                $tag = $this->createTag($tag, $shortTagIdent, $tagName, $tagType, $tagText);
                $subSegFound = $i;
            }
            
            if ($subSegFound && $subSegFound != $i && strpos($tag ,'<SubSeg')!== false){
                trigger_error('we do not support nested SubSegs so far');
            }
            if (strpos($tag,'</SubSeg>')!== false){
                $tagName = 'SubSeg';
                $shortTagIdent = $this->shortTagIdent++;
                $tagText = 'SubSeg';
                $tagType = '_rightTag';
                $tag = $this->createTag($tag, $shortTagIdent, $tagName, $tagType, $tagText);
            }
            $i++;
        }
    }
    
    protected function createTag($tag, $shortTagIdent, $tagName, $tagType, $tagText) {
        $fileNameHash = md5($tagText);
        if(strpos($tagText, '<') !== false ||strpos($tagText, '"') !== false){
            $tagText = htmlspecialchars($tagText, ENT_XML1);
        }
        $p = $this->getTagParams($tag, $shortTagIdent, $tagName, $fileNameHash, $tagText);
        $tag = $this->$tagType->getHtmlTag($p);
        $this->$tagType->createAndSaveIfNotExists($tagText, $fileNameHash);
        $this->_tagCount++;
        return $tag;
    }
    /**
     * new tags should also be added to containsOnlyTagsOrEmpty
     * 
     * @param string $segment
     */
    protected function splitSegment(string $segment) {
        $splitByAndInsert = function ($tagStart,$regex,$i){
            if (strpos($this->segmentParts[$i] ,$tagStart)!== false){//this is to save performance
                $parts = preg_split($regex, $this->segmentParts[$i], NULL, PREG_SPLIT_DELIM_CAPTURE);
                if(count($parts)>1){
                    array_splice($this->segmentParts, $i, 1, $parts);
                    $this->segmentPartsCount = count($this->segmentParts);
                    return;
                }
                trigger_error('If tagName is present, parts should always be bigger than 1');
            }
        };

        $this->segmentParts = array($segment);
        $this->segmentPartsCount = 1;
        $this->segmentParts = str_replace(array('~','</Tag>'), array('__TranSiT_TRANSTiLde__','~'), $this->segmentParts);
        
        for($i = 0; $i < $this->segmentPartsCount; $i++) {
            $splitByAndInsert('<SubSeg>','"(<Tag [^>]*?>[^~]*?<SubSeg[^>/]*?>)"s',$i);
            $splitByAndInsert('</SubSeg>','"(</SubSeg>[^~]*?~)"s',$i);
            $splitByAndInsert('<Tag','"(<Tag [^>]*?>[^~]*?~)"s',$i);
            $splitByAndInsert('<FontTag','"(<FontTag [^>]*?>.*?</FontTag>)"s',$i);
            $splitByAndInsert('<WS','"(<WS [^>]*?/>)"s',$i);
            $splitByAndInsert('<NL','"(<NL[^>]*?>.*?</NL>)"s',$i);
            $splitByAndInsert('<NU','"(<NU[^>]*?>.*?</NU>)"s',$i);
            $splitByAndInsert('<Tab','"(<Tab[^>]*?>.*?</Tab>)"s',$i);
            $i++;
        }
        $this->segmentParts = str_replace( array('~','__TranSiT_TRANSTiLde__'),array('</Tag>','~'), $this->segmentParts);
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
                '#<hardReturn/>#',
                '#<softReturn/>#',
                '#<macReturn/>#',
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
     * checks if there are any tags not covered by splitSegment or "Tag"-Tags or
     * "FontTag"-Tags not covered by their methods. Thus has to be placed before
     * parseWhitespace() and after all other tag-parsing methods 
     */
    protected function checkForUndefinedTags(){
        //parse only even array-elements, because in those there should be no tags
        for($i = 0; $i < $this->segmentPartsCount; $i++) {
            //check for undefined tags
            $part = &$this->segmentParts[$i];
            if (strpos($part ,'<')!== false){
                if(strpos(str_replace('<space ', '', $part), '<') !== false){//our whitespace-tags are still allowed
                    trigger_error('In the segmentPart '.$part.' a tag has been found.');
                }
            }
            $i++;
            $part = &$this->segmentParts[$i];
            if (strpos($part ,'<Tag')!== false){
                trigger_error('In the segmentPart '.$part.' a "Tag"-tag has been found, which not has been covered so far.');
            }
            if (strpos($part ,'<FontTag')!== false){
                trigger_error('In the segmentPart '.$part.' a "FontTag"-tag has been found, which not has been covered so far.');
            }
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
                $tagType = '_rightTag';
                $tagNr = $this->getTransitTagNr($tag);
                if(!$tagNr){
                    $tagType = '_singleTag';
                }
                else{
                    $this->endTags[$this->shortTagIdent] = $tagNr;
                }
                $tagText = $this->getTagText($tag, $tagName);
                $tag = $this->createTag($tag, $this->shortTagIdent++, $tagName, $tagType, $tagText);
            }
            $i++;
        }
    }
    
    protected function parseWsTags(){
        for($i = 1; $i < $this->segmentPartsCount; $i++) {
            $tag = &$this->segmentParts[$i];
            if (strpos($tag ,'<WS')!== false){
                $shortTagIdent = $this->shortTagIdent++;
                $tagName = 'WS';
                $tagText = 'WS';
                $tagType = '_singleTag';
                $tag = $this->createTag($tag, $shortTagIdent, $tagName, $tagType, $tagText);
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
                $tag = $this->createTag($tag, $shortTagIdent, $tagName, $tagType, $tagText);
            }
            $i++;
        }
    }
}
