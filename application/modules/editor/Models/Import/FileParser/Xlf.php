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
 * Fileparsing for import of IBM-XLIFF files
 */
class editor_Models_Import_FileParser_Xlf extends editor_Models_Import_FileParser {
    const PREFIX_MRK = 'mrk-';
    const PREFIX_SUB = 'sub-';
    const MISSING_MRK = 'missing-mrk';
    
    private $wordCount = 0;
    private $segmentCount = 1;
    
    private $startShiftCount = 0;
    private $endShiftCount = 0;
    
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
     * Container for plain text content in target tags
     * @var array
     */
    protected $otherContentTarget = [];
    
    /**
     * Container for plain text content in source tags
     * @var array
     */
    protected $otherContentSource = [];
    
    /**
     * Contains the source keys in the order how they should be imported!
     * @var array
     */
    protected $sourceProcessOrder = [];
    
    /**
     * Pointer to the real <source>/<seg-source> tags of the current transunit,
     * needed for injection of missing target tags
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
     * @var editor_Models_Segment
     */
    protected $segmentBareInstance;
    
    /**
     * contains the info from where current the source contet originates:
     * plain <source>, plain <seg-source> or <seg-source><mrk mtype="seg">
     * This info is important for preparing empty mrk tags with placeholders
     * @var integer
     */
    protected $sourceOrigin;
    
    protected $transUnitCnt = 0;
    
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
    
    protected $matchRate = [];
    
    /**
     * Flag if unknown content should be collected or not
     * @var boolean
     */
    protected $checkContentOutsideMrk = false;
    
    /**
     * Flag if current tag is collected as otherContent (outside mrk tags)
     * @var integer|boolean
     */
    protected $trackTagOutsideMrk = false;
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::getFileExtensions()
     */
    public static function getFileExtensions() {
        return ['xlf','xlif','xliff','mxliff','mqxliff'];
    }
    
    /**
     * Init tagmapping
     */
    public function __construct(string $path, string $fileName, integer $fileId, editor_Models_Task $task) {
        parent::__construct($path, $fileName, $fileId, $task);
        $this->initNamespaces();
        $this->contentConverter = ZfExtended_Factory::get('editor_Models_Import_FileParser_Xlf_ContentConverter', [$this->namespaces, $this->task, $fileName]);
        $this->internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        $this->segmentBareInstance = ZfExtended_Factory::get('editor_Models_Segment');
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
        
        $preserveWhitespaceDefault = $this->config->runtimeOptions->import->xlf->preserveWhitespace;
        $this->_skeletonFile = $parser->parse($this->_origFile, $preserveWhitespaceDefault);
        
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
        
        //handler to get content outside of mrk tags
        $otherContentHandler = function($other) {
            $this->otherContentHandler($other);
        };
        
        $sourceTag = 'trans-unit > source, trans-unit > seg-source, trans-unit > seg-source > mrk[mtype=seg]';
        $sourceTag .= ', trans-unit > source sub, trans-unit > seg-source sub';
        
        $this->xmlparser->registerElement($sourceTag, function($tag, $attributes){
            $sourceImportance = $this->compareSourceOrigin($tag);
            //set the source origin where we are currently (mrk or sub or plain source or seg-source)
            $this->setSourceOrigin($tag);
            
            //source content with lower importance was set before, remove it 
            if($sourceImportance > 0){
                $this->sourceProcessOrder = [];
                $this->currentSource = [];
            }
            $mid = $this->calculateMid(['tag' => $tag, 'attributes' => $attributes], true);
            if($sourceImportance >= 0){
                //preset the source segment for sorting purposes
                // if we just add the content in the end handler, sub tags are added before the surrounding text content,
                // but it is better if sub content is listed after the content of the corresponding segment
                // for that we just set the source indizes here in the startHandler, here the order is correct
                $this->sourceProcessOrder[] = $mid;
            }
            if($tag == 'mrk') {
                $this->otherContentSource[$mid] = ''; //add a new container for the content after the current mrk
            }
        }, $sourceEndHandler);
        
        //register to seg-source directly to enable / disable the collection of other content 
        $this->xmlparser->registerElement('xliff trans-unit > seg-source', function() use ($otherContentHandler) {
            //if we have a seg-source we probably have also mrks where no other content is allowed to be outside the mrks 
            $this->checkContentOutsideMrk = true;
            $this->xmlparser->registerOther($otherContentHandler); // register other handler to get and check content between mrk tags
        }, function(){
            $this->xmlparser->registerOther(null); // unregister other handler
        });
        
        $this->xmlparser->registerElement('trans-unit > target', function() use ($otherContentHandler) {
            $this->xmlparser->registerOther($otherContentHandler); // register other handler to get and check content between mrk tags
        }, function($tag, $key, $opener){
            $this->xmlparser->registerOther(null); // unregister other handler
            //if empty targets are given as Single Tags
            $this->currentPlainTarget = $this->getTargetMeta($tag, $key, $opener);
            if($this->isEmptyTarget($opener, $key)) {
                return;
            }
            foreach($this->currentTarget as $target) {
                if($target['tag'] == 'mrk'){
                    //if there is already target content coming from mrk tags inside, 
                    // do nothing at the end of the main target tag
                    return;
                }
            }
            //add the main target tag to the list of processable targets, needed only without mrk tags and if target is not empty
            $this->otherContentTarget = []; //if we use the plainTarget (no mrks), the otherContent is the plainTarget and no further checks are needed
            $this->currentTarget[$this->calculateMid($opener, false)] = $this->currentPlainTarget;
        });
        
        //handling sub segment mrks and sub tags
        $this->xmlparser->registerElement('trans-unit > target > mrk[mtype=seg], trans-unit > target sub', function($tag, $attributes) {
            if($tag == 'mrk') {
                //if we have a mrk we enable the content outside mrk check
                $this->checkContentOutsideMrk = true;
                $this->otherContentTarget[$this->calculateMid(['tag' => $tag, 'attributes' => $attributes], false)] = ''; //add a new container for the content after the current mrk 
            }
        }, function($tag, $key, $opener){
            $this->currentTarget[$this->calculateMid($opener, false)] = $this->getTargetMeta($tag, $key, $opener);
        });
        
        $this->xmlparser->registerElement('trans-unit alt-trans', function($tag, $attributes){
            $mid = $this->xmlparser->getAttribute($attributes, 'mid', 0); //defaulting to 0 for transunits without mrks
            $matchRate = $this->xmlparser->getAttribute($attributes, 'match-quality', false);
            if($matchRate !== false) {
                $this->matchRate[$mid] = (int) trim($matchRate,'% '); //removing the percent sign
            }
        });
        
        /**
         * If we are in target or seg-source we collect all unknown tags and save them as strings into 
         * the otherContent fields. 
         * To prevent that <a><b></b></a> is collected as <a><b></b></a> and <b></b> we store the start key in the trackTagOutsideMrk flag 
         */
        $this->xmlparser->registerElement('*', function($tag, $attributes, $key){
            $inTarget = $this->xmlparser->getParent('trans-unit > target');
            $inSegSource = $this->xmlparser->getParent('seg-source');
            if(empty($inTarget) && empty($inSegSource)) {
                $this->trackTagOutsideMrk = false;
            }
            else {
                $this->trackTagOutsideMrk = $key;
            }
        }, function($tag, $key, $opener){
            if($this->trackTagOutsideMrk === $opener['openerKey']) {
                $this->trackTagOutsideMrk = false;
                $this->otherContentHandler($this->xmlparser->getRange($opener['openerKey'], $key, true));
            }
        });
        //error_log("Unknown in XLF Parser ". $other); //→ $other evaluates to the tag in the wildcard handler
    }
    
    /**
     * The length of other content (outside/between mrk mtype seg tags) is also saved for length calculation
     * Assume the following <target>, where bef, betweenX and aft are assumed as whitespace 
     *  (since other content as whitespace outside of mrks gices an error)
     * <target>bef<mrk>text 1</mrk>between1<mrk>text 2</mrk>between1<mrk>text 3</mrk>aft</target>
     *   the length of "bef" is saved as "additionalUnitLength" to each segment
     *   the length of each whitespace after a closed mrk is saved to that mrk as "additionalMrkLength"
     *   each additionalMrkLength is added automatically to the segments content length in siblingData
     *   the additionalUnitLength instead must be only added once on each length calculation (where siblingData is used)
     * preserveWhitespace influences the otherContent: 
     *   preserveWhitespace = true: length of otherContent is always the real length, 
     *   preserveWhitespace = false: length of otherContent is always length of the padded whitespace between the MRK tags, 
     * source and target MRK padding if MRKs are different in source vs target: 
     *    if $useSourceOtherContent is true, this is no problem since there is no target to compare and add missing MRKs
     *    if its false and targetOtherContent is used: just use the target otherContent since padded target MRKs could 
     *      not be edited and are not added as new MRKs in the target. So no otherContent must be considered here.
     *    This will change with implementing merging and splitting.
     *    
     * @param editor_Models_Import_FileParser_SegmentAttributes $attributes
     * @param boolean $useSourceOtherContent
     * @param boolean $preserveWhitespace
     */
    protected function saveTargetOtherContentLength(editor_Models_Import_FileParser_SegmentAttributes $attributes, $useSourceOtherContent, $preserveWhitespace) {
        $otherContent = $useSourceOtherContent ? $this->otherContentSource : $this->otherContentTarget;
//debug START
/*
        $x = array_map(function($i){
            return '#'.$i.'#'.strlen($i);
        }, $otherContent);
        error_log("\n\n".$attributes->transunitId."\n\n");
        error_log(print_r($x,1));
        error_log(print_r($this->currentTarget,1));
*/
//debug END        

        $text = function($text) {
            //for other content length calculation we have to convert the othercontent to translate5 content (mainly because of the tags)
            // this must be done with preserve whitespace true (otherwise the length of tags would be ignored)
            $preserveWhitespace = true; 
            return $this->xmlparser->join($this->contentConverter->convert($text, true, $preserveWhitespace));
        };
        
        //by definition the first otherContent belongs to the whole transunit - this value is stored in each segment
        // only of preserveWhitespace is true
        if($preserveWhitespace && !empty($otherContent[0])) {
            $attributes->additionalUnitLength = $this->segmentBareInstance->textLength($text($otherContent[0]));
        }
        //the other lengths are stored per affected segment, so if there is none, do nothing
        if(empty($otherContent[$attributes->mrkMid])) {
            return;
        }
        
        if($preserveWhitespace) {
            //with preserve whitespace we use the original content
            $content = $text($otherContent[$attributes->mrkMid]);
        } else {
            //otherwise we prepare the otherContent like in checkAndPrepareOtherContent, but only if we are not
            //in the last MRK: here we may not save any additionalLength, since we consider only the content inbetween MRKs
            //<target>additionalUnitLength ignored<mrk>content</mrk> this length is needed<mrk>content</mrk>this length is ignored again</target>
            $mrkMidKeys = array_keys($otherContent);
            if($attributes->mrkMid != end($mrkMidKeys)) {
                $content = $text($this->prepareMrkInbetweenContent($otherContent[$attributes->mrkMid]));
            }
            else {
                $content = '';
            }
        }
        $attributes->additionalMrkLength = $this->segmentBareInstance->textLength($content);
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
     *   <seg-source> <mrk mtype="seg">  content of the mrk type=seg tags inside the seg-source
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
        //set <source> only if no seg-source was set already, seg-source can always be used, seg-source is more important as source tag
        if($tag == 'source' && empty($this->currentPlainSource) || $tag == 'seg-source'){
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
     * calculates the MID for mapping source to target fragment (is NOT related to the segments MID)
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
            $prefix = self::PREFIX_SUB;
            $validParents = ['ph[id]','it[id]','bpt[id]','ept[id]'];
            $parent = false;
            while(!$parent && !empty($validParents)) {
                $parent = $this->xmlparser->getParent(array_shift($validParents));
                if($parent) {
                    return $prefix.$parent['tag'].'-'.$parent['attributes']['id'];
                }
            }
            $msg  = 'SUB tag of '.($source ? 'source' : 'target').' is not unique due missing ID in the parent node and is ignored as separate segment therefore.'."\n";
            $this->throwSegmentationException($msg);
            return '';
        }
        if($opener['tag'] == 'mrk') {
            $prefix = self::PREFIX_MRK;
            if($this->xmlparser->getAttribute($opener['attributes'], 'mid') === false) {
                $msg  = 'MRK tag of '.($source ? 'source' : 'target').' has no MID attribute.';
                $this->throwSegmentationException($msg);
            }
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
     * @param string $msg
     * @throws ZfExtended_Exception
     */
    protected function throwSegmentationException($msg, $transUnitId = false) {
        if($transUnitId === false) {
            $transUnitId = $this->xmlparser->getParent('trans-unit')['attributes']['id'];
        }
        $msg .= "\n".'Transunit mid: '.$transUnitId.' and TaskGuid: '.$this->task->getTaskGuid();
        throw new ZfExtended_Exception($msg);
    }
    
    /**
     * Sets the source origin importance
     * @see self::compareSourceOrigin
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
        //check for correct xlf version
        $this->xmlparser->registerElement('xliff', function($tag, $attributes, $key){
            $this->checkXliffVersion($attributes, $key);
        });
        
        $this->xmlparser->registerElement('group', function($tag, $attributes, $key){
            $this->handleGroup($attributes);
        }, function(){
            array_pop($this->groupTranslate);
        });
        
        $this->xmlparser->registerElement('trans-unit', function($tag, $attributes, $key){
            $this->processSegment = $this->isTranslateable($attributes);
            $this->transUnitCnt++;
            $this->sourceOrigin = 0;
            $this->matchRate = [];
            $this->currentSource = [];
            $this->currentTarget = [];
            $this->sourceProcessOrder = [];
            $this->currentPlainSource = null;
            // set to null to identify if there is no a target at all
            $this->currentPlainTarget = null;
            $this->otherContentSource = [];//reset otherContent for new source
            $this->otherContentTarget = [];//reset otherContent for new target
            $this->checkContentOutsideMrk = false;
            
//From Globalese:
//<trans-unit id="segmentNrInTask">
//<source>Installation and Configuration</source>
//<target state="needs-review-translation" state-qualifier="leveraged-mt" translate5:origin="Globalese">Installation und Konfiguration</target>
//</trans-unit>
        }, function($tag, $key, $opener) {
            try {
                $this->extractSegment($opener['attributes']);
            }
            catch(Exception $e){
                $e->setMessage($e->getMessage()."\n".'In trans-unit '.print_r($opener['attributes'],1));
                throw $e;
            }
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
    
    /**
     * Checks if the given xliff is in the correct (supported) version
     * @param string $xliffTag
     * @param integer $key
     * @throws ZfExtended_Exception
     */
    protected function checkXliffVersion($attributes, $key) {
        $validVersions = ['1.1', '1.2'];
        $version = $this->xmlparser->getAttribute($attributes, 'version');
        if(! in_array($version, $validVersions)) {
            throw new ZfExtended_Exception('XLF Parser supports only XLIFF Version 1.1 and 1.2, but the following xliff tag was given: '.$this->xmlparser->getChunk($key));
        }
    }
    
    protected function initNamespaces() {
        $this->namespaces = ZfExtended_Factory::get("editor_Models_Import_FileParser_Xlf_Namespaces",[$this->_origFile]);
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
     * parses the TransUnit attributes
     * @param array $attributes transUnit attributes
     * @param integer $mid MRK tag mid or 0 if no mrk mtype seg used
     * @return editor_Models_Import_FileParser_SegmentAttributes
     */
    protected function parseSegmentAttributes($attributes, $mid) {
        //build mid from id of segment plus segmentCount, because xlf-file can have more than one file in it with repeatingly the same ids.
        // and one trans-unit (where the id comes from) can contain multiple mrk type seg tags, which are all converted into single segments.
        // instead of using mid from the mrk type seg element, the segmentCount as additional ID part is fine.
        $transunitId = $this->xmlparser->getAttribute($attributes, 'id', null);
        $id = $transunitId.'_'.++$this->segmentCount;
        
        $segmentAttributes = $this->createSegmentAttributes($id);
        $segmentAttributes->mrkMid = $mid;
        
        $this->calculateMatchRate($segmentAttributes);

        //process nonxliff attributes
        $this->namespaces->transunitAttributes($attributes, $segmentAttributes);
        $this->setMid($id);
        
        if(!empty($this->currentPlainTarget) && $state = $this->xmlparser->getAttribute($this->currentPlainTarget['openerMeta']['attributes'], 'state')) {
            $segmentAttributes->targetState = $state;
        }
        
        if(!$this->processSegment) {
            //add also translate="no" segments but readonly
            $segmentAttributes->editable = false;
        }
        
        // since a transunitId can exist in each file of a translate5 task the fileId must be added for uniqness of the transunitId in the DB
        // also in each XLIFF there can be multiple file containers, which must be reflected in the transunitId too. 
        //  Easiest way: each transunit of the xlf file gets a counter   
        $segmentAttributes->transunitId = $this->_fileId.'_'.$this->transUnitCnt.'_'.$transunitId;
        $sizeUnit = $this->xmlparser->getAttribute($attributes, 'size-unit');
        if($sizeUnit == 'char') {
            $segmentAttributes->minWidth = $this->xmlparser->getAttribute($attributes, 'minwidth', null);
            $segmentAttributes->maxWidth = $this->xmlparser->getAttribute($attributes, 'maxwidth', null);
        }
        
        return $segmentAttributes;
    }
    
    protected function calculateMatchRate(editor_Models_Import_FileParser_SegmentAttributes $attributes) {
        $mid = $attributes->mrkMid;
        if(strpos($mid, editor_Models_Import_FileParser_Xlf::PREFIX_MRK) === 0) {
            //remove the mrk prefix again to get numeric ids
            $mid = str_replace(editor_Models_Import_FileParser_Xlf::PREFIX_MRK, '', $mid);
        }
        if(isset($this->matchRate[$mid])) {
            $attributes->matchRate = $this->matchRate[$mid];
            $attributes->matchRateType = editor_Models_Segment_MatchRateType::TYPE_TM;
        }
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
        
        //must be set before the loop, since in the loop the currentTarget is cleared on success
        $hasTargets = !empty($this->currentTarget);
        $sourceEdit = $this->task->getEnableSourceEditing();
        
        //find mrk mids missing in source and add them marked as missing
        $this->padSourceMrkTags();
        //find mrk mids missing in target and add them marked as missing
        $this->padTargetMrkTags();
        
        $hasNoTarget = is_null($this->currentPlainTarget);
        $hasTargetSingle = !$hasNoTarget && $this->currentPlainTarget['openerMeta']['isSingle'];
        //$hasEmptyTarget includes $hasTargetSingle
        $hasEmptyTarget = !$hasNoTarget && $this->isEmptyTarget($this->currentPlainTarget['openerMeta'], $this->currentPlainTarget['closer']);
        
        if($hasNoTarget || $hasTargetSingle) {
            $preserveWhitespace = $this->currentPlainSource['openerMeta']['preserveWhitespace'];
        }
        else {
            $preserveWhitespace = $this->currentPlainTarget['openerMeta']['preserveWhitespace'];
        }
        
        foreach($this->sourceProcessOrder as $mid) {
            if($mid === '') {
                //if mid was empty string there was an error, ignore the segment, logging was already done
                unset($this->currentTarget[$mid]);
                continue;
            }
            $currentSource = $this->currentSource[$mid];
            $isSourceMrkMissing = ($currentSource == self::MISSING_MRK);
            
            if($isSourceMrkMissing) {
                $sourceChunks = [];
            }
            else {
                //parse the source chunks
                $sourceChunks = $this->xmlparser->getRange($currentSource['opener']+1, $currentSource['closer']-1);
                $sourceChunks = $this->contentConverter->convert($sourceChunks, true, $currentSource['openerMeta']['preserveWhitespace']);
                $sourceSegment = $this->xmlparser->join($sourceChunks);
                
                //if there is no source content, nothing can be done
                if(empty($sourceSegment)){
                    unset($this->currentTarget[$mid]);
                    continue;
                }
            }
            
            if($sourceEdit && $isSourceMrkMissing || $hasTargets && empty($this->currentTarget[$mid])){
                $transUnitMid = $this->xmlparser->getAttribute($transUnit, 'id', '-na-');
                $msg  = 'MRK/SUB tag of source not found in target with Mid: '.$mid."\n";
                $this->throwSegmentationException($msg, $transUnitMid);
            }
            if(empty($this->currentTarget) || empty($this->currentTarget[$mid])){
                $targetChunksOriginal = $targetChunks = [];
                $currentTarget = '';
            }
            else {
                $currentTarget = $this->currentTarget[$mid];
                if($currentTarget == self::MISSING_MRK) {
                    $targetChunksOriginal = $targetChunks = [];
                    if(!$sourceEdit) { 
                        //remove the item only if sourceEditing is disabled. 
                        // That results then in an missing MRK error if sourceEditing enabled! 
                        unset($this->currentTarget[$mid]);
                    }
                }
                else {
                    //parse the target chunks, store the real chunks from the XLF separatly 
                    $targetChunksOriginal = $this->xmlparser->getRange($currentTarget['opener']+1, $currentTarget['closer']-1);
                    //in targetChunks the content is converted (tags, whitespace etc)
                    $targetChunks = $this->contentConverter->convert($targetChunksOriginal, false, $currentTarget['openerMeta']['preserveWhitespace']);
                    unset($this->currentTarget[$mid]);
                }
            }
            
            //reset start/end shift count. 
            // the counts are set by hasSameStartAndEndTags to > 0, 
            // then the start/end offset where the placeHolder is placed is shifted
            // to exclude tags leading and trailing tags in the segment
            $this->startShiftCount = 0;
            $this->endShiftCount = 0;
            //if preserveWhitespace is enabled, hasSameStartAndEndTags should not hide tags, 
            // since potential whitespace tags does matter then in the content
            //since we are calling the leading/trailing tag stuff on the already fetched source segments, 
            // we have no ability here to conserve content outside the mrk tags - which also should not be on preserveWhitespace 
            if($preserveWhitespace || !$this->hasSameStartAndEndTags($sourceChunks, $targetChunks)) {
                //if there is just leading/trailing whitespace but no tags we reset the counter 
                // since then we dont want to cut off something
                //if there is whitespace between or before the leading / after the trailing tags,  
                // this whitespace is ignored depending the preserveWhitespace setting. 
                // above $sourceChunks $targetChunks does not contain any irrelevant whitespace (only empty chunks)
                $this->startShiftCount = 0;
                $this->endShiftCount = 0;
            }
            
            //we cut off and store the leading target tags for later insertion 
            $leadingTags = $this->xmlparser->join(array_splice($targetChunksOriginal, 0, $this->startShiftCount));
            
            //we cut off and store the trailing target tags for later insertion 
            if($this->endShiftCount > 0) {
                $trailingTags = $this->xmlparser->join(array_splice($targetChunksOriginal, -$this->endShiftCount));
            }
            else {
                $trailingTags = '';
            }
            
            //for source column we dont have a place holder, so we just cut off the leading/trailing tags and import the rest as source 
            $sourceChunks = array_slice($sourceChunks, $this->startShiftCount, count($sourceChunks) - $this->startShiftCount - $this->endShiftCount);
            //for target we have to do the same on the converted chunks to be used, 
            // since the above array_sPlice calls are working on the original array
            $targetChunks = array_slice($targetChunks, $this->startShiftCount, count($targetChunks) - $this->startShiftCount - $this->endShiftCount);
            
            $this->segmentData = array();
            $this->segmentData[$sourceName] = array(
                'original' => $this->xmlparser->join($sourceChunks)
            );
        
            $this->segmentData[$targetName] = array(
                'original' => $this->xmlparser->join($targetChunks)
            );
            
            //parse attributes for each found segment not only for the whole trans-unit
            $attributes = $this->parseSegmentAttributes($transUnit, $mid);
            if($currentTarget == self::MISSING_MRK) {
                $attributes->matchRateType = editor_Models_Segment_MatchRateType::TYPE_MISSING_TARGET_MRK;
            } elseif($currentSource == self::MISSING_MRK) {
                $attributes->matchRateType = editor_Models_Segment_MatchRateType::TYPE_MISSING_SOURCE_MRK;
            }
            
            //first we save the previous other content length to the previous segment
            $this->saveTargetOtherContentLength($attributes, $hasNoTarget || $hasEmptyTarget, $preserveWhitespace);
            
            //The internal $mid has to be added to the DB mid of <sub> element, needed for exporting the content again
            if(strpos($mid, self::PREFIX_SUB) === 0) {
                $this->setMid($this->_mid.'-'.$mid);
            }
            
            //if target was given and source contains tags only or is empty, then it will be ignored
            if(!$isSourceMrkMissing && !empty($this->segmentData[$targetName]['original']) && !$this->hasText($this->segmentData[$sourceName]['original'])) {
                $placeHolders[$mid] = $this->xmlparser->join($targetChunksOriginal);
                continue;
            }
            $segmentId = $this->setAndSaveSegmentValues();
            //only with a segmentId (in case of ProofProcessor) we can save comments
            if($segmentId !== false && is_numeric($segmentId)) {
                $this->importComments((integer) $segmentId);
            }
            if($currentTarget !== self::MISSING_MRK) {
                //we add a placeholder if it is a real segment, not just a placeholder for a missing mrk
                $placeHolders[$mid] = $leadingTags.$this->getFieldPlaceholder($segmentId, $targetName).$trailingTags;
            }
        }
        
        if(!empty($this->currentTarget)){
            $transUnitMid = $this->xmlparser->getAttribute($transUnit, 'id', '-na-');
            $msg  = 'MRK/SUB tag of target not found in source with Mid(s): '.join(', ', array_keys($this->currentTarget))."\n";
            $this->throwSegmentationException($msg, $transUnitMid);
        }
        
        //if we dont find any usable segment, we dont have to place the placeholder
        if(empty($placeHolders)){
            return;
        }
        
        foreach($placeHolders as $mid => $placeHolder) {
            if(strpos($mid, self::PREFIX_MRK) === 0) {
                //remove the mrk prefix again to get numeric ids
                $usedMid = str_replace(self::PREFIX_MRK, '', $mid); 
                $placeHolders[$mid] = '<mrk mtype="seg" mid="'.$usedMid.'">'.$placeHolder.'</mrk>';
            }
            if(strpos($mid, self::PREFIX_SUB) === 0) {
                unset($placeHolders[$mid]); //remove sub element place holders, for sub elements are some new placeholders inside the tags
            }
        }
        
        $otherContent = $this->checkAndPrepareOtherContent($hasNoTarget || $hasEmptyTarget, $preserveWhitespace);
        
        //the combination of array_merge and array_map combines the otherContent values
        // and the placeholders in a zipper (Reißverschlussverfahren) way 
        $placeHolder = join(array_merge(...array_map(null, $otherContent, array_values($placeHolders))));
        
        //this solves TRANSLATE-879: sdlxliff and XLF import does not work with missing target
        //if there is no target at all:
        if($hasNoTarget){
            //currentPlainSource point always to the last used source or seg-source 
            // the target tag should be added after the the latter of both
            $replacement = '</'.$this->currentPlainSource['tag'].">\n        <target>".$placeHolder.'</target>';
            $this->xmlparser->replaceChunk($this->currentPlainSource['closer'], $replacement);
        }
        //if the XLF contains an empty (single tag) target:
        elseif($hasTargetSingle) {
            $this->xmlparser->replaceChunk($this->currentPlainTarget['closer'], function($index, $oldChunk) use ($placeHolder) {
                return '<target>'.$placeHolder.'</target>';
            });
        }
        //existing content in the target:
        else {
            //clean up target content to empty, we store only our placeholder in the skeleton file
            $start = $this->currentPlainTarget['opener'] + 1;
            $length = $this->currentPlainTarget['closer'] - $start;
            //empty content between target tags:
            $this->xmlparser->replaceChunk($start, '', $length);
            //add placeholder and ending target tag:
            $this->xmlparser->replaceChunk($this->currentPlainTarget['closer'], function($index, $oldChunk) use ($placeHolder) {
                return $placeHolder.$oldChunk;
            });
        }
    }
    
    /**
     * Prepares and checks the internally stored other content (content outside MRK mtype seg tags)
     * check: there may not be other content as tags and whitespace
     * prepare: - converts the multidimensional otherContent arrays to onedimensional ones and returns the one to be used.
     *          - does whitespace handling: preserve completly if configured or defined in trans-unit, 
     *          or default behaviour: remove all whitespace, keep a single whitepace between MRKs
     * @return array the other content to be used for skeleton placeholder generation 
     */
    protected function checkAndPrepareOtherContent($useSourceContent, $preserveWhitespace) {
        if(!$this->checkContentOutsideMrk) {
            //if we don't check the mrk outside content, we assume that there is no outside content
            return [];
        }
        //if we need otherContent below for further checks, we have to remove the assoc keys for proper working of the array_merge commands
        $otherContentSource = array_values($this->otherContentSource);
        $otherContentTarget = array_values($this->otherContentTarget);
        
        //if there is any other text content as whitespace between the mrk type seg tags, this is invalid xliff and therefore not allowed
        // example: <mrk mtype="seg">allowed</mrk> not allowed <mrk...
        // we allow tags between the mrk tags, they are preserved too, so we remove them for the check before
        $otherContent = join(array_merge($otherContentSource, $otherContentTarget));
        if(!empty($otherContent) && preg_match('/[^\s]+/', $this->contentConverter->removeXlfTags($otherContent),$matches)) {
            $data = array_merge($otherContentSource, $otherContentTarget);
            $this->throwSegmentationException('There is other content as whitespace outside of the mrk tags. Found content: '.print_r($data,1));
        }
        
        $otherContent = $useSourceContent ? $otherContentSource : $otherContentTarget;
        
        
        // default behaviour for whitespace hanling in translate5 is:
        if($preserveWhitespace) {
            return $otherContent;
        }
        
        $firstIdx = 0;
        $lastIdx = count($otherContent) - 1;
        foreach($otherContent as $idx => $content) {
            //since the below regex deletes only whitespace before and after possible tags,
            // whitespace inside tags (<ph> for example) are preserved here.
            // But this should be ok, since the content inside the tag coming from "otherContent" is not editable.
            if($idx == $firstIdx || $idx == $lastIdx) {
                //before and after the first / last mrk the whitespace is stripped completly
                $otherContent[$idx] = preg_replace('/^[\s]+|[\s]+$/', '', $content);
                continue;
            }
            //between MRKs we keep a single whitespace: 
            $otherContent[$idx] = $this->prepareMrkInbetweenContent($content);
        }
        
        return $otherContent;
    }
    
    /**
     * prepares whitespace on content inbetween mrk tags, only to be used with preserveWhitespace = false 
     * @param string $content
     * @return string
     */
    protected function prepareMrkInbetweenContent($content) {
        return preg_replace('/^[\s]+|[\s]+$/', ' ', $content);
    }
    
    /**
     * It must be sure, that this code runs after all other attribute calculations!
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser::setCalculatedSegmentAttributes()
     */
    protected function setCalculatedSegmentAttributes() {
        $attributes = parent::setCalculatedSegmentAttributes();
        if($attributes->editable) {
            if(strpos($attributes->matchRateType, editor_Models_Segment_MatchRateType::TYPE_MISSING_TARGET_MRK) !== false){
                $attributes->editable = false; //if its a missing target the segment is not editable
            }
        }
        return $attributes;
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
     * Imports the comments of last processed segment
     * @param integer $segmentId
     */
    protected function importComments($segmentId) {
        $comments = $this->namespaces->getComments();
        if(empty($comments)) {
            return;
        }
        foreach($comments as $comment) {
            /* @var $comment editor_Models_Comment */
            $comment->setTaskGuid($this->task->getTaskGuid());
            $comment->setSegmentId($segmentId);
            $comment->save();
        }
        //if there was at least one processed comment, we have to sync the comment contents to the segment
        if(!empty($comment)){
            $comment->updateSegment($segmentId, $this->task->getTaskGuid());
        }
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
    
    /**
     * compares source and target mrk mids, adds missing mrks mids into the sourceProcessOrder
     * find all mrk-MIDs from $this->currentTarget which are missing in $this->sourceProcessOrder
     * adds them by natural sort order to $this->sourceProcessOrder and to the currentSource container as special segment (MISSING_MRK)
     */
    protected function padSourceMrkTags() {
        $isMrkMid = function($item) {
            return strpos($item, self::PREFIX_MRK) === 0;
        };
        $targetMrkKeys = array_filter(array_keys($this->currentTarget), $isMrkMid);
        $mrkMissingInSource = array_diff($targetMrkKeys, $this->sourceProcessOrder);
        if(empty($mrkMissingInSource)) {
            return;
        }
        
        foreach($mrkMissingInSource as $target) {
            $this->currentSource[$target] = self::MISSING_MRK;
        }
        
        natsort($mrkMissingInSource);
        $result = [];
        //get the first target to compare
        $target = array_shift($mrkMissingInSource);
        foreach($this->sourceProcessOrder as $sourceMid) {
            //if there is no target anymore, or the source is no mrk source, we just add the source
            if(! $isMrkMid($sourceMid) || empty($target)) {
                $result[] = $sourceMid;
                continue;
            }
            
            //if the current sourceMid would be greater or equal to the current target, 
            // then we add first the target, then the sourceMid
            if(strnatcmp($sourceMid, $target) >= 0) {
                $result[] = $target;
                //get the next target to compare
                $target = array_shift($mrkMissingInSource);
            }
            $result[] = $sourceMid;
        }
        //if the target could not added (all source mids were smaller) add it after the loop
        if(!empty($target)) {
            $result[] = $target;
        }
        //same for all other remaining targets
        if(!empty($mrkMissingInSource)) {
            $result = array_merge($result, $mrkMissingInSource);
        }
        //store the result back
        $this->sourceProcessOrder = $result;
    }
    
    /**
     * loop over $this->sourceProcessOrder and compare with $this->currentTarget, 
     *   add missing entries to $this->currentTarget also as special segment (MISSING_MRK)
     */
    protected function padTargetMrkTags() {
        //if currentTarget is completely empty, there are no single mrks missing, but all. 
        // This special case is handled otherwise.
        if(empty($this->currentTarget)) {
            return;
        }
        $isMrkMid = function($item) {
            return strpos($item, self::PREFIX_MRK) === 0;
        };
        $targetMrkKeys = array_filter(array_keys($this->currentTarget), $isMrkMid);
        $sourceMrkKeys = array_filter($this->sourceProcessOrder, $isMrkMid);
        $mrkMissingInTarget = array_diff($sourceMrkKeys, $targetMrkKeys);
        if(empty($mrkMissingInTarget)) {
            return;
        }
        $mrkMissingInTarget = array_fill_keys($mrkMissingInTarget, self::MISSING_MRK);
        $this->currentTarget = array_merge($this->currentTarget, $mrkMissingInTarget);
    }
    
    /**
     * Checks recursivly if target and source starts/ends with the same chunks, 
     *   if there are some tags in the start/end chunks it checks if they are paired tags. 
     *   if source and target start and ends just with that paired tags (no other content expect whitespace) then the tags are ignored in import 
     * @param array $source
     * @param array $target
     * @param boolean $foundTag used for recursive call
     * @return boolean returns false if there are no matching leading/trailing tags at all 
     */
    protected function hasSameStartAndEndTags(array $source, array $target, $foundTag = false) {
        //source and target must have at least a start tag, text content, and an end tag, that means at least 3 chunks:
        if(count($source) < 4 || count($target) < 4){
            return $foundTag;
        }
        
        //init variables:
        $sourceStart = array_shift($source);
        $sourceEnd = array_pop($source);
        $targetStart = array_shift($target);
        $targetEnd = array_pop($target);
        $startShiftCount = 0;
        $endShiftCount = 0;
        
        if($sourceStart != $targetStart || $sourceEnd != $targetEnd){
            //source or target chunks are different, so no tag match possible
            return $foundTag; 
        }
        
        //if sourceStart is an empty string or whitespace, get the next pairs
        while(!is_null($sourceStart) && empty($sourceStart) || preg_match('#^\s+$#', $sourceStart)) {
            $sourceStart = array_shift($source);
            $targetStart = array_shift($target);
            if($sourceStart != $targetStart) {
                return $foundTag;
            }
            //inc internal start shift count
            $startShiftCount++;
        }
        
        //check next non empty/whitespace chunk
        if(!preg_match(editor_Models_Segment_InternalTag::REGEX_STARTTAG, $sourceStart, $startMatches)) {
            //if no tag then exit
            return $foundTag;
        }
        
        while(!is_null($sourceEnd) && empty($sourceEnd) || preg_match('#^\s+$#', $sourceEnd)) {
            $sourceEnd = array_pop($source);
            $targetEnd = array_pop($target);
            if($sourceEnd != $targetEnd) {
                //if end chunks differ exit
                return $foundTag;
            }
            //inc internal end shift count
            $endShiftCount++;
        }
        
        //check next non empty/whitespace chunk from behind
        if(!preg_match(editor_Models_Segment_InternalTag::REGEX_ENDTAG, $sourceEnd, $endMatches)) {
            //if no tag then exit
            return $foundTag;
        }
        
        //if tag pairs from start to end does not match, then exit
        if($startMatches[1] !== $endMatches[1]) {
            return $foundTag;
        }
        $this->startShiftCount = $this->startShiftCount + ++$startShiftCount;
        $this->endShiftCount = $this->endShiftCount + ++$endShiftCount;
        
        //start recursivly for more than one tag pair, 
        // we have found at least one tag pair so set $foundTag to true for next iteration
        return $this->hasSameStartAndEndTags($source, $target, true);
    }
    
    /**
     * Handles other content depending if we are outside of mrk tags and wether we are in source or target
     * @param  $other
     */
    protected function otherContentHandler($other) {
        $inMrk = $this->xmlparser->getParent('mrk');
        if(!empty($inMrk) || $this->trackTagOutsideMrk !== false) {
            //we are in a mrk, so we do nothing
            // or if we are in a tag we also don't have to track the data, this is done by the whole tag then
            return;
        }
//error_log("OTHER ".$other);
        $inTarget = $this->xmlparser->getParent('target');
        if(empty($inTarget)) {
            $this->addOtherContent($this->otherContentSource, $other);
        }
        else {
            $this->addOtherContent($this->otherContentTarget, $other);
        }
    }
    
    /**
     * Add other content to the requested container
     * @param array $container
     * @param string $other
     */
    protected function addOtherContent(&$container, $other) {
        if(count($container) === 0) {
            //if there is no content, this is the first content before the first mrk at all
            $container[] = '';
        }
        $keys = array_keys($container);
        //always add content to the current last element of the array. new elements per MRKs are added elsewhere
        $container[end($keys)] .= $other;
    }
    
    /**
     * returns true if target is a single tag (<target/>) or is empty <target></target>, where whitespace between the both targets matters for emptiness depending on preserveWhitespace
     * @param array $openerMeta
     * @param integer $closerKey
     * @return boolean
     */
    protected function isEmptyTarget(array $openerMeta, $closerKey) {
        if($openerMeta['isSingle']) {
            return true;
        }
        $preserveWhitespace = $openerMeta['preserveWhitespace'];
        $content = $this->xmlparser->getRange($openerMeta['openerKey']+1, $closerKey - 1, true);
        return $preserveWhitespace ? empty($content) : (strlen(trim($content)) === 0);
    }
}
