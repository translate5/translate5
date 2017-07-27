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
 * TODO: 
 * - can not deal with <mrk type="seg"> subsegments!
 * - sub tags are also not supported: idea for implementation: 
 *     instead of adding the sub parser to the ContentConverter add some handlers here and treat the sub content as separate segment
 *     then replace the sub content with a new internal tag so that the users gets a visual reference to the corresponding segment
 *     this subreplacement has to be done before the content is passed to the ContentConverter
 *     
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
     * Init tagmapping
     */
    public function __construct(string $path, string $fileName, integer $fileId, editor_Models_Task $task) {
        parent::__construct($path, $fileName, $fileId, $task);
        $this->protectUnicodeSpecialChars();
        $this->initNamespaces();
        $this->contentConverter = ZfExtended_Factory::get('editor_Models_Import_FileParser_Xlf_ContentConverter', [$this->namespaces, $this->task, $fileName]);
        $this->internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
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
        $sourceHandler = function($tag, $key, $opener){
            $this->handleSourceTag($tag, $key, $opener);
        };
        $sourceTag = 'trans-unit > source, trans-unit > seg-source, trans-unit > seg-source > mrk[type=seg]';
        $this->xmlparser->registerElement($sourceTag, null, $sourceHandler);
        
        $this->xmlparser->registerElement('trans-unit > target', null, function($tag, $key, $opener){
            $this->currentPlainTarget = $this->getTargetMeta($tag, $key, $opener);
            if(!empty($this->currentTarget)) {
                //if there is target content already, this content is coming from mrk tags inside the target, 
                // so do nothing at the end of the target tag
                return;
            }
            $this->currentTarget[] = $this->currentPlainTarget;
        });
        $this->xmlparser->registerElement('trans-unit > target > mrk[type=seg]', null, function($tag, $key, $opener){
            $this->currentTarget[] = $this->getTargetMeta($tag, $key, $opener);
        });
    }
    
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
            //point to the plain/real source tag:
            $this->currentPlainSource = $source;
        }
        //if there is already "source" content:
        if(!empty($this->currentSource)) {
            //we are handling </source> but we have already content from seg-source or seg-source > mrk so do nothing
            if($tag == 'source'){
                return;
            }
            $lastSource = end($this->currentSource);
            //we are handling </mrk type=seg> or </seg-source>
            // if we have already source content from a ordinary source segment, we have to discard it,
            // since we want use the mrk content or the seg-source content (if there are no mrks in it).
            // Looking just at the last currentSource is OK, since if it was a source tag, there is only one entry.
            if(($tag == 'mrk' || $tag = 'seg-source') && $lastSource['tag'] == 'source'){
                $this->currentSource = [];
            }
            //we are handling </seg-source> but we have already processed content from seg-source > mrk so do nothing
            if($tag == 'seg-source' && $lastSource['tag'] == 'mrk'){
                return;
            }
        }
        
        //if the content was coming from a mrk tag, we have to track the mids for target matching
        if($tag == 'mrk' && $mid = $this->xmlparser->getAttribute($opener['attributes'], 'mid')) {
            $this->currentSource[$mid] = $source;
            return;
        }
        $this->currentSource[] = $source;
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
            $this->currentSource = [];
            $this->currentTarget = [];
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
        
        foreach($this->currentSource as $mid => $currentSource) {
            //parse the source chunks
            $sourceChunks = $this->xmlparser->getRange($currentSource['opener']+1, $currentSource['closer']-1);
            $sourceSegment = $this->contentConverter->convert($sourceChunks);
            
            //if there is no source content, nothing can be done
            if(empty($sourceSegment)){
                continue;
            }
            
            if(!empty($this->currentTarget) && empty($this->currentTarget[$mid])){
                $log = ZfExtended_Factory::get('ZfExtended_Log');
                /* @var $log ZfExtended_Log */
                $transUnitMid = $this->xmlparser->getAttribute($transUnit, 'mid', '-na-');
                $msg  = 'MRK tag of source not found in target with Mid: '.$mid."\n";
                $msg .= 'Transunit mid: '.$transUnitMid.' and TaskGuid: '.$this->task->getTaskGuid();
                $log->logError($msg);
                continue;
            }
            if(empty($this->currentTarget)){
                $targetSegment = '';
            }
            else {
                $currentTarget = $this->currentTarget[$mid];
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