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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
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
 * Fileparsing for import of IBM-XLIFF files
 */
class editor_Models_Import_FileParser_Xlf extends editor_Models_Import_FileParser {
    private $wordCount = 0;
    private $segmentCount = 1;
    
    /**
     * Helper to call namespace specfic parsing stuff 
     * @var editor_Models_Import_FileParser_Xlf_Namespaces
     */
    protected $namespaces;
    
    /**
     * Stack of the group translate information
     * @var array
     */
    protected $groupTranslate = [];
    
    /**
     * true if the current segment should be processed
     * false if not
     * @var boolean
     */
    protected $processSegment = true;
    
    /**
     * @var editor_Models_Import_FileParser_XmlParser
     */
    protected $xmlparser;
    
    /**
     * Container for the source segments found in the current transunit
     * @var array
     */
    protected $currentSource = [];
    
    /**
     * Container for the target segments found in the current transunit
     * @var array
     */
    protected $currentTarget = [];
    
    /**
     * Contains the source keys in the order how they should be imported!
     * @var array
     */
    protected $sourceProcessOrder = [];
    
    /**
     * Pointer to the real <source> tags of the current transunit,
     * needed for injection of missing target, mrk and our placeholder tags
     * @var array
     */
    protected $currentPlainSource = null;
    
    /**
     * Pointer to the real <target> tags of the current transunit,
     * needed for injection of missing target, mrk and our placeholder tags
     * @var array
     */
    protected $currentPlainTarget = null;
    
    /**
     * @var editor_Models_Import_FileParser_Xlf_ContentConverter
     */
    protected $contentConverter = null;
    
    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $internalTag;
    
    /**
     * contains the info from where current the source contet originates:
     * plain <source>, plain <seg-source> or <seg-source><mrk type="seg">
     * This info is important for preparing empty mrk tags with placeholders
     * @var integer
     */
    protected $sourceOrigin;
    
    /**
     * Defines the importance of the tags containing possible source content
     * @var array
     */
    protected $sourceOriginImportance = [
        'sub' => 0, //→ no importance, means also no change in the importance
        'source' => 1,
        'seg-source' => 2,
        'mrk' => 3,
    ];
    /**
     * @var ZfExtended_Log 
     */
    protected $log;
    
    /**
     * Init tagmapping
     */
    public function __construct(string $path, string $fileName, integer $fileId, editor_Models_Task $task) {
        parent::__construct($path, $fileName, $fileId, $task);
        $this->protectUnicodeSpecialChars();
        $this->initNamespaces();
        $this->contentConverter = ZfExtended_Factory::get('editor_Models_Import_FileParser_Xlf_ContentConverter', [$this->namespaces, $this->task, $fileName]);
        $this->internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        $this->log = ZfExtended_Factory::get('ZfExtended_Log');
    }
    
    
    /**
     * This function return the number of words of the source-part in the imported xlf-file
     * 
     * @return: (int) number of words
     */
    public function getWordCount()
    {
        return $this->wordCount;
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::parse()
     */
    protected function parse() {
        $this->segmentCount = 0;
        $this->xmlparser = $parser = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        /* @var $parser editor_Models_Import_FileParser_XmlParser */
        
        $this->registerStructural();
        $this->registerMeta();
        $this->registerContent();
        $this->namespaces->registerParserHandler($this->xmlparser);
        
        $parser->parse($this->_origFileUnicodeProtected);
        
        $this->_skeletonFile = (string) $parser;
        
        if ($this->segmentCount === 0) {
            error_log('Die Datei ' . $this->_fileName . ' enthielt keine übersetzungsrelevanten Segmente!');
        }
    }
    
    /**
     * registers handlers for nodes with meta data
     */
    protected function registerMeta() {
        $this->xmlparser->registerElement('trans-unit count', function($tag, $attributes, $key){
            $this->addupSegmentWordCount($attributes);
        });
    }
    
    /**
     * registers handlers for source, seg-source and target nodes to be stored for later processing
     */
    protected function registerContent() {
        $sourceEndHandler = function($tag, $key, $opener){
            $this->handleSourceTag($tag, $key, $opener);
        };
        
        $sourceTag = 'trans-unit > source, trans-unit > seg-source, trans-unit > seg-source > mrk[type=seg]';
        $sourceTag .= ', trans-unit > source sub, trans-unit > seg-source sub';
        
        $this->xmlparser->registerElement($sourceTag, function($tag, $attributes){
            $sourceImportance = $this->compareSourceOrigin($tag);
            //set the source origin where we are currently
            $this->setSourceOrigin($tag);
            
            //source content with lower importance was set before, remove it 
            if($sourceImportance > 0){
                $this->sourceProcessOrder = [];
                $this->currentSource = [];
            }
            if($sourceImportance >= 0){
                //preset the source segment for sorting purposes
                // if we just add the content in the end handler, sub tags are added before the surrounding text content,
                // but it is better if sub content is listed after the content of the corresponding segment
                // for that we just set the source indizes here in the startHandler, here the order is correct
                $this->sourceProcessOrder[] = $this->calculateMid(['tag' => $tag, 'attributes' => $attributes], true);
            }
        }, $sourceEndHandler);
        
        $this->xmlparser->registerElement('trans-unit > target', null, function($tag, $key, $opener){
            $this->currentPlainTarget = $this->getTargetMeta($tag, $key, $opener);
            foreach($this->currentTarget as $target) {
                if($target['tag'] == 'mrk'){
                    //if there is already target content coming from mrk tags inside, 
                    // do nothing at the end of the main target tag
                    return;
                }
            }
            //add the main target tag to the list of processable targets, needed only without mrk tags and if target is not empty
            if(strlen(trim($this->xmlparser->getRange($opener['openerKey']+1, $key - 1, true)))){
                $this->currentTarget[$this->calculateMid($opener, false)] = $this->currentPlainTarget;
            }
        });
        
        //handling sub segment mrks and sub tags
        $this->xmlparser->registerElement('trans-unit > target > mrk[type=seg], trans-unit > target sub', null, function($tag, $key, $opener){
            $this->currentTarget[$this->calculateMid($opener, false)] = $this->getTargetMeta($tag, $key, $opener);
        });
        
        $this->xmlparser->registerElement('*', null, function($tag, $key, $opener){
            //error_log("Unknown in XLF Parser ". $tag);
        });
    }
    
    /**
     * puts the given target chunk in an array with additonal meta data 
     */
    protected function getTargetMeta($tag, $key, $opener) {
        //is initialized with null to check if there is no target tag at all,
        // here in the target handler we have to convert the null to an empty array
        return [
                'tag' => $tag,
                'opener' => $opener['openerKey'],
                'closer' => $key,
                'openerMeta' => $opener,
        ];
    }
    
    /**
     * Stores the "source" content for further processing
     * "source" content is content of the
     *   <source>                       tag, if the <seg-source> does not exist 
     *   <seg-source>                   tag, plain content or
     *   <seg-source> <mrk type="seg">  content of the mrk type=seg tags inside the seg-source
     * @param string $tag
     * @param integer $key
     * @param array $opener
     */
    protected function handleSourceTag($tag, $key, $opener) {
        $source = [
            'tag' => $tag,
            'opener' => $opener['openerKey'],
            'closer' => $key,
            'openerMeta' => $opener,
        ];
        if($tag == 'source'){
            //point to the plain/real source tag, needed for <target> injection
            $this->currentPlainSource = $source;
        }
        $sourceImportance = $this->compareSourceOrigin($tag);
        
        //source content with heigher importance was set before, ignore current content 
        // for the importance see $this->sourceOriginImportance
        if($sourceImportance < 0){
            return;
        }

        //$sourceImportance == 0, no importance change add each found content:
        $this->currentSource[$this->calculateMid($opener, true)] = $source;
    }

    /**
     * calculates the MID for mapping source to target fragment
     * @param array $opener
     * @param boolean $source defines for which column the content is calculated: true if source, false if target  
     * @return string
     */
    protected function calculateMid(array $opener, $source) {
        //if the content was coming from a: 
        // mrk tag, we have to track the mrks mids for target matching
        // sub tag, we have to uses the parent tags id to identify the sub element.
        //   This is important for alignment of the sub tags, if the parent tags have flipped positions in source and target
        $prefix = '';
        if($opener['tag'] == 'sub') {
            $prefix = 'sub-';
            $validParents = ['ph[id]','it[id]','bpt[id]','ept[id]'];
            $parent = false;
            while(!$parent && !empty($validParents)) {
                $parent = $this->xmlparser->getParent(array_shift($validParents));
                if($parent) {
                    return $prefix.$parent['attributes']['id'];
                }
            }
            $id = $this->xmlparser->getParent('trans-unit')['attributes']['id'];
            $msg  = 'SUB tag of '.($source ? 'source' : 'target').' is not unique due missing ID in the parent node and is ignored as separate segment therefore.'."\n";
            $msg .= 'Transunit mid: '.$id.' and TaskGuid: '.$this->task->getTaskGuid();
            $this->log->logError($msg);
            return '';
        }
        if($opener['tag'] == 'mrk') {
            $prefix = 'mrk-';
        }
        if(!($opener['tag'] == 'mrk' && $mid = $this->xmlparser->getAttribute($opener['attributes'], 'mid'))) {
            $toConsider = $source ? $this->currentSource : $this->currentTarget;
            $toConsider = array_filter(array_keys($toConsider), function($item){
                return is_numeric($item);
            });
            if(empty($toConsider)){
                $mid = 0;
            }
            else {
                //instead of using the length of the array  we consider only the numeric keys, take the biggest one and increase it
                $mid = max($toConsider) + 1; 
            }
        }
        return $prefix.$mid;
    }
    
    /**
     * Sets the source origin importance
     * @see compareSourceOrigin
     * @param string $tag
     */
    protected function setSourceOrigin($tag) {
        $origin = $this->sourceOriginImportance[$tag];
        if($origin === 0) {
            return;
        }
        if($origin > $this->sourceOrigin){
            $this->sourceOrigin = $origin;
        }
    }
    
    /**
     * compares the importance of source origin. lowest importance has the content of a source tag, 
     *  more important is seg-source, with the most importance is seg-source>mrk 
     *  The content with the highes importance is used
     * @param string $tag
     * @return integer return <0 if a higher important source was set already, >0 if a more important source is set now, and 0 if the importance was the same (with mrks and subs possible only)
     */
    protected function compareSourceOrigin($tag) {
        $origin = $this->sourceOriginImportance[$tag];
        if($origin === 0) {
            return 0;
        }
        return $origin - $this->sourceOrigin;
    }
    
    /**
     * registers handlers for structural nodes (group, transunit)
     */
    protected function registerStructural() {
        $this->xmlparser->registerElement('group', function($tag, $attributes, $key){
            $this->handleGroup($attributes);
        }, function(){
            array_pop($this->groupTranslate);
        });
        
        $this->xmlparser->registerElement('trans-unit', function($tag, $attributes, $key){
            $this->processSegment = $this->isTranslateable($attributes);
            $this->sourceOrigin = 0;
            $this->currentSource = [];
            $this->currentTarget = [];
            $this->sourceProcessOrder = [];
            $this->currentPlainSource = null;
            // set to null to identify if there is no a target at all
            $this->currentPlainTarget = null;

//From Globalese:
//<trans-unit id="segmentNrInTask">
//<source>Installation and Configuration</source>
//<target state="needs-review-translation" state-qualifier="leveraged-mt" translate5:origin="Globalese">Installation und Konfiguration</target>
//</trans-unit>
        }, function($tag, $key, $opener) {
            $this->extractSegment($opener['attributes']);
            //leaving a transunit means disable segment processing
            $this->processSegment = false;
        });
    }
    
    /**
     * returns true if segment should be translated, considers also surrounding group tags
     * @param array $transunitAttributes
     */
    protected function isTranslateable($transunitAttributes) {
        if(!empty($transunitAttributes['translate'])) {
            return $transunitAttributes['translate'] == 'yes';
        }
        $reverse = array_reverse($this->groupTranslate);
        foreach($reverse as $group) {
            if(is_null($group)) {
                continue; //if the previous group provided no information, loop up 
            }
            return $group;
        }
        return true; //if not info given at all: translateable
    }
    
    protected function initNamespaces() {
        $this->namespaces = ZfExtended_Factory::get("editor_Models_Import_FileParser_Xlf_Namespaces",[$this->_origFileUnicodeProtected]);
    }
    
    /**
     * Handles a group tag
     * @param array $attributes
     */
    protected function handleGroup(array $attributes) {
        if(empty($attributes['translate'])) {
            //we have to add also the groups without an translate attribute 
            // so that array_pop works correct on close node 
            $this->groupTranslate[] = null;
            return;
        }
        $this->groupTranslate[] = (strtolower($attributes['translate']) == 'yes');
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::parseSegmentAttributes()
     */
    protected function parseSegmentAttributes($attributes) {
        settype($attributes['id'], 'integer');
        //build mid from id of segment plus segmentCount, because xlf-file can have more than one file in it with repeatingly the same ids.
        // and one trans-unit (where the id comes from) can contain multiple mrk type seg tags, which are all converted into single segments.
        // instead of using mid from the mrk type seg element, the segmentCount as additional ID part is fine.
        $id = $attributes['id'].'_'.++$this->segmentCount;
        
        $segmentAttributes = $this->createSegmentAttributes($id);

        //process nonxliff attributes
        $this->namespaces->transunitAttributes($attributes, $segmentAttributes);
        $this->setMid($id);
        
        if(!empty($this->currentPlainTarget) && $state = $this->xmlparser->getAttribute($this->currentPlainTarget['openerMeta']['attributes'], 'state')) {
            $segmentAttributes->targetState = $state;
        }
        return $segmentAttributes;
    }
    
    /**
     * sub-method of parse();
     * extract source- and target-segment from a trans-unit element
     * and saves this segments into database
     *
     * @param array $transUnit In this class this are the trans-unit attributes only
     * @return array $transUnit contains replacement-tags <lekSourceSeg id=""/> and <lekTargetSeg id=""/>
     *          instead of the original segment content. attribut id contains the id of db-table LEK_segments
     */
    protected function extractSegment($transUnit) {
        //define the fieldnames where the data should be stored
        $sourceName = $this->segmentFieldManager->getFirstSourceName();
        $targetName = $this->segmentFieldManager->getFirstTargetName();
        
        $placeHolders = [];
        
        foreach($this->sourceProcessOrder as $mid) {
            
            if($mid === '') {
                //if mid was empty string there was an error, ignore the segment, logging was already done
                unset($this->currentTarget[$mid]);
                continue;
            }
            $currentSource = $this->currentSource[$mid];
            
            //parse the source chunks
            $sourceChunks = $this->xmlparser->getRange($currentSource['opener']+1, $currentSource['closer']-1);
            $sourceSegment = $this->contentConverter->convert($sourceChunks);
            
            //if there is no source content, nothing can be done
            if(empty($sourceSegment)){
                unset($this->currentTarget[$mid]);
                continue;
            }
            
            if(!empty($this->currentTarget) && empty($this->currentTarget[$mid])){
                $transUnitMid = $this->xmlparser->getAttribute($transUnit, 'id', '-na-');
                $msg  = 'MRK/SUB tag of source not found in target with Mid: '.$mid."\n";
                $msg .= 'Transunit mid: '.$transUnitMid.' and TaskGuid: '.$this->task->getTaskGuid();
                $this->log->logError($msg);
            }
            if(empty($this->currentTarget) || empty($this->currentTarget[$mid])){
                $targetSegment = '';
            }
            else {
                $currentTarget = $this->currentTarget[$mid];
                unset($this->currentTarget[$mid]);
                //parse the target chunks
                $targetChunks = $this->xmlparser->getRange($currentTarget['opener']+1, $currentTarget['closer']-1);
                $targetSegment = $this->contentConverter->convert($targetChunks);
            }
            $this->segmentData = array();
            $this->segmentData[$sourceName] = array(
                'original' => $sourceSegment
            );
        
            $this->segmentData[$targetName] = array(
                'original' => $targetSegment
            );
            
            //parse attributes for each found segment not only for the whole trans-unit
            $attributes = $this->parseSegmentAttributes($transUnit);
            if(!$this->processSegment) {
                //add also translate="no" segments but readonly
                $attributes->editable = false;
            }
            
            //if target was given and source contains tags only or is empty, then it will be ignored
            if(!empty($targetSegment) && !$this->hasText($sourceSegment)) {
                continue;
            }
            $segmentId = $this->setAndSaveSegmentValues();
            $placeHolders[$mid] = $this->getFieldPlaceholder($segmentId, $targetName);
        }
        
        if(!empty($this->currentTarget)){
            $transUnitMid = $this->xmlparser->getAttribute($transUnit, 'id', '-na-');
            $msg  = 'MRK/SUB tag of target not found in source with Mid(s): '.join(', ', array_keys($this->currentTarget))."\n";
            $msg .= 'Transunit mid: '.$transUnitMid.' and TaskGuid: '.$this->task->getTaskGuid();
            $this->log->logError($msg);
        }
        
        //if we dont find any usable segment, we dont have to place the placeholder
        if(empty($placeHolders)){
            return;
        }
        
        //if the last processed source was a mrk tag, we assume that all content is coming from MRK Tags!
        if($currentSource['tag'] == 'mrk') {
            //put each placeholder in one MRK tag
            $placeHolder = join(array_map(function($mid, $ph){
                return '<mrk type="seg" mid="'.$mid.'">'.$ph.'</mrk>';
            },array_keys($placeHolders), $placeHolders));
        }
        else {
            //without MRK tags $placeHolders should contain only one element
            $placeHolder = join($placeHolders);
        }
        
        //this solves TRANSLATE-879: sdlxliff and XLF import does not work with missing target
        if(is_null($this->currentPlainTarget)){
            $this->xmlparser->replaceChunk($this->currentPlainSource['closer'], "</source>\r\n        <target>".$placeHolder.'</target>');
        }
        else {
            //clean up target content to empty, we store only our placeholder in the skeleton file
            $start = $this->currentPlainTarget['opener'] + 1;
            $length = $this->currentPlainTarget['closer'] - $start;
            $this->xmlparser->replaceChunk($start, '', $length);
            $this->xmlparser->replaceChunk($this->currentPlainTarget['closer'], function($index, $oldChunk) use ($placeHolder) {
                return $placeHolder.$oldChunk;
            });
        }
    }
    
    /**
     * returns false if segment content contains only tags
     * @param string $segmentContent
     * @return boolean
     */
    protected function hasText($segmentContent) {
        $segmentContent = $this->internalTag->replace($segmentContent, '');
        $segmentContent = trim(strip_tags($segmentContent));
        return !empty($segmentContent);
    }
    
    /**
     * detects wordcount in a trans-unit element.
     * sums up wordcount for the whole file in $this->wordCount
     * 
     * Sample of wordcount provided by a trans-unit: <count count-type="word count" unit="word">13</count>
     *
     * @param array $transUnit
     */
    protected function addupSegmentWordCount($attributes) {
        // <count count-type="word count" unit="word">7</count>
        //TODO: this count-type is not xliff 1.2!!! IBM specific? or 1.1?
        if($this->processSegment && !empty($attributes['count-type']) && $attributes['count-type'] == 'word count') {
            $this->wordCount += trim($this->xmlparser->getNextChunk());
        }
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser::parseSegment()
     */
    protected function parseSegment($segment, $isSource) {
        //is abstract so must be defined empty since not used!
    }
}