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
 * Fileparsing for import of XLIFF 1.1 and 1.2 files
 */
class editor_Models_Import_FileParser_Xlf extends editor_Models_Import_FileParser {
    const PREFIX_MRK = 'mrk-';
    const PREFIX_SUB = 'sub-';
    const MISSING_MRK = 'missing-mrk';
    
    /**
     * The XLF target states which are to be considered as pretranslated only, as defined in TRANSLATE-1643
     * @var array
     */
    const PRE_TRANS_STATES = ['needs-adaption', 'needs-l10n'];
    
    /**
     * defines if the content parser should reparse the chunks
     * false default, better performance
     * true used in subclasses, sometimes thats needed because of changes done in the XML structure
     * @var boolean
     */
    const XML_REPARSE_CONTENT = false;
    
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
     * Container for plain text content
     * @var editor_Models_Import_FileParser_Xlf_OtherContent
     */
    protected $otherContent;
    
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
     * Flag if current tag is collected as otherContent (outside mrk tags)
     * @var integer|boolean
     */
    protected $trackTagOutsideMrk = false;
    
    /**
     * @var editor_Models_Import_FileParser_Xlf_LengthRestriction
     */
    protected $lengthRestriction;
    
    /**
     * @var editor_Models_Import_FileParser_Xlf_SurroundingTags
     */
    protected $surroundingTags;
    
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
    public function __construct(string $path, string $fileName, int $fileId, editor_Models_Task $task) {
        parent::__construct($path, $fileName, $fileId, $task);
        $this->initNamespaces();
        $this->contentConverter = ZfExtended_Factory::get('editor_Models_Import_FileParser_Xlf_ContentConverter', [$this->namespaces, $this->task, $fileName]);
        $this->internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        $this->segmentBareInstance = ZfExtended_Factory::get('editor_Models_Segment');
        $this->log = ZfExtended_Factory::get('ZfExtended_Log');
        $this->lengthRestriction = ZfExtended_Factory::get('editor_Models_Import_FileParser_Xlf_LengthRestriction',[
            $this->task->getConfig()
        ]);
        $this->surroundingTags = ZfExtended_Factory::get('editor_Models_Import_FileParser_Xlf_SurroundingTags', [$this->config]);
        $this->otherContent = ZfExtended_Factory::get('editor_Models_Import_FileParser_Xlf_OtherContent', [
            $this->contentConverter, $this->segmentBareInstance, $this->task, $fileId
        ]);
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
        
        try {
            $this->_skeletonFile = $parser->parse($this->_origFile, $preserveWhitespaceDefault);
        }
        catch(editor_Models_Import_FileParser_InvalidXMLException $e) {
            $logger = Zend_Registry::get('logger')->cloneMe('editor.import.fileparser.xlf');
            //we log the XML error as own exception, so that the error is listed in task overview
            $e->addExtraData(['task' => $this->task]);
            /* @var $logger ZfExtended_Logger */
            $logger->exception($e);
            //'E1190' => 'The XML of the XLF file "{fileName} (id {fileId})" is invalid!',
            throw new editor_Models_Import_FileParser_Xlf_Exception('E1190', [
                'task' => $this->task,
                'fileName' => $this->_fileName,
                'fileId' => $this->_fileId,
            ], $e);
        }
        
        if ($this->segmentCount === 0) {
            //'E1191' => 'The XLF file "{fileName} (id {fileId})" does not contain any translation relevant segments.',
            throw new editor_Models_Import_FileParser_Xlf_Exception('E1191', [
                'task' => $this->task,
                'fileName' => $this->_fileName,
                'fileId' => $this->_fileId,
            ]);
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
                $this->otherContent->addSource($mid, ''); //add a new container for the content after the current mrk
            }
        }, $sourceEndHandler);
        
        //register to seg-source directly to enable / disable the collection of other content
        $this->xmlparser->registerElement('xliff trans-unit > seg-source', function() use ($otherContentHandler) {
            //if we have a seg-source we probably have also mrks where no other content is allowed to be outside the mrks
            $this->otherContent->setCheckContentOutsideMrk(true);
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
            $this->otherContent->initTarget(); //if we use the plainTarget (no mrks), the otherContent is the plainTarget and no further checks are needed
            $this->currentTarget[$this->calculateMid($opener, false)] = $this->currentPlainTarget;
        });
        
        //handling sub segment mrks and sub tags
        $this->xmlparser->registerElement('trans-unit > target > mrk[mtype=seg], trans-unit > target sub', function($tag, $attributes) {
            if($tag == 'mrk') {
                //if we have a mrk we enable the content outside mrk check
                $this->otherContent->setCheckContentOutsideMrk(true);
                $this->otherContent->addTarget($this->calculateMid(['tag' => $tag, 'attributes' => $attributes], false), ''); //add a new container for the content after the current mrk
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
     * @param int $key
     * @param array $opener
     */
    protected function handleSourceTag($tag, $key, $opener) {
        $source = [
            'tag' => $tag,
            'opener' => $opener['openerKey'],
            'closer' => $key,
            'openerMeta' => $opener,
            'unsegmentedSource' => null,
        ];

        if($tag == 'source'){
            //set <source> only if no seg-source was set already, seg-source can always be used, seg-source is more important as source tag
            if(empty($this->currentPlainSource)) {
                //point to the plain/real source tag, needed for <target> injection
                $this->currentPlainSource = $source;
            }
            else {
                //seg-source was set before, we just store the unsegmented source
                $this->currentPlainSource['unsegmentedSource'] = $source;
            }
        }

        //set <source> only if no seg-source was set already, seg-source can always be used, seg-source is more important as source tag
        if($tag == 'seg-source'){
            //source was set before, store it as unsegmentedSource in the plain source
            if(!empty($this->currentPlainSource)) {
                $source['unsegmentedSource'] = $this->currentPlainSource;
            }
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
     * @param bool $source defines for which column the content is calculated: true if source, false if target
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
            $this->throwSegmentationException('E1070', ['field' => ($source ? 'source' : 'target')]);
            return '';
        }
        if($opener['tag'] == 'mrk') {
            $prefix = self::PREFIX_MRK;
            if($this->xmlparser->getAttribute($opener['attributes'], 'mid') === false) {
                $this->throwSegmentationException('E1071', ['field' => ($source ? 'source' : 'target')]);
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
     * Throws Xlf Exception
     * @param string $errorCode
     * @param string $data
     * @throws ZfExtended_Exception
     */
    protected function throwSegmentationException($errorCode, array $data) {
        if(!array_key_exists('transUnitId', $data)) {
            $data['transUnitId'] = $this->xmlparser->getParent('trans-unit')['attributes']['id'];
        }
        $data['task'] = $this->task;
        throw new editor_Models_Import_FileParser_Xlf_Exception($errorCode, $data);
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
            $this->otherContent->initOnUnitStart($this->xmlparser);
            
//From Globalese:
//<trans-unit id="segmentNrInTask">
//<source>Installation and Configuration</source>
//<target state="needs-review-translation" state-qualifier="leveraged-mt" translate5:origin="Globalese">Installation und Konfiguration</target>
//</trans-unit>
        }, function($tag, $key, $opener) {
            try {
                $createdSegmentIds = $this->extractSegment($opener['attributes']);
                //we collect all created segmentIds fur further usage on export (if needed by namespace)
                $this->xmlparser->replaceChunk($key, '<t5:unitSegIds ids="'.join(',', $createdSegmentIds).'" />'.$this->xmlparser->getChunk($key));
            }
            catch(ZfExtended_ErrorCodeException $e){
                $e->addExtraData(['trans-unit' => $opener['attributes']]);
                throw $e;
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
     * @param int $key
     * @throws ZfExtended_Exception
     */
    protected function checkXliffVersion($attributes, $key) {
        $validVersions = ['1.1', '1.2'];
        $version = $this->xmlparser->getAttribute($attributes, 'version');
        if(! in_array($version, $validVersions)) {
            // XLF Parser supports only XLIFF Version 1.1 and 1.2, but the imported xliff tag does not match that criteria: {tag}
            throw new editor_Models_Import_FileParser_Xlf_Exception('E1232', [
                'task' => $this->task,
                'tag' => $this->xmlparser->getChunk($key),
            ]);
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
     * @param int $mid MRK tag mid or 0 if no mrk mtype seg used
     * @return editor_Models_Import_FileParser_SegmentAttributes
     */
    protected function parseSegmentAttributes($attributes, $mid): editor_Models_Import_FileParser_SegmentAttributes {
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
            $segmentAttributes->isPreTranslated = in_array($state, self::PRE_TRANS_STATES);
        }
        
        if(!$this->processSegment) {
            //add also translate="no" segments but readonly and locked!
            $segmentAttributes->editable = false; //this is to mark the segment non editable in the application
            $segmentAttributes->locked = true; //this is to mark it explicitly locked (so that editable can not be changed)
        }
        
        // since a transunitId can exist in each file of a translate5 task the fileId must be added for uniqness of the transunitId in the DB
        // also in each XLIFF there can be multiple file containers, which must be reflected in the transunitId too.
        //  Easiest way: each transunit of the xlf file gets a counter
        $segmentAttributes->transunitId = $this->_fileId.'_'.$this->transUnitCnt.'_'.$transunitId;
        
        try {
            $this->lengthRestriction->addAttributes($this->xmlparser, $attributes, $segmentAttributes);
        }
        catch(editor_Models_Import_MetaData_Exception $e) {
            $e->addExtraData([
                'task' => $this->task,
                'rawTransUnitId' => $transunitId,
                'transUnitId' => $segmentAttributes->transunitId,
            ]);
            throw $e;
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
     * is called in the end of the transunit
     * extract source- and target-segment from a trans-unit element
     * and saves this segments into database
     *
     * @param array $transUnit In this class this are the trans-unit attributes only
     * @return array array of segmentIds created from that trans unit
     */
    protected function extractSegment($transUnit): array {
        //define the fieldnames where the data should be stored
        $sourceName = $this->segmentFieldManager->getFirstSourceName();
        $targetName = $this->segmentFieldManager->getFirstTargetName();
        
        $placeHolders = [];
        
        //must be set before the loop, since in the loop the currentTarget is cleared on success
        $hasTargets = !(empty($this->currentTarget));
        $sourceEdit = $this->task->getEnableSourceEditing();

        $hasNoTarget = is_null($this->currentPlainTarget);
        $hasTargetSingle = !$hasNoTarget && $this->currentPlainTarget['openerMeta']['isSingle'];
        //$hasEmptyTarget includes $hasTargetSingle
        $hasEmptyTarget = !$hasNoTarget && $this->isEmptyTarget($this->currentPlainTarget['openerMeta'], $this->currentPlainTarget['closer']);

        //for processSegment == false (translate="no") (which evaluates later to locked == true && editable == false) it may happen that
        // seg-source is segmented, but since it is translate="no" the target contains the content unsegmented.
        // In that case we have to ignore the seg-source content and just import source and target, for read-only access
        // and add no placeholders for export for such segments so that the original content is kept.
        if(!$this->processSegment && !$hasNoTarget && !$hasEmptyTarget && $this->isSourceSegmentedButTargetNot()) {
            $mid = $this->calculateMid(['tag' => 'source', 'attributes' => $transUnit], true);
            $this->sourceProcessOrder = [$mid];
            $this->currentSource = [$mid => $this->currentPlainSource['unsegmentedSource'] ?? $this->currentPlainSource];
            $this->currentTarget = [$mid => $this->currentPlainTarget];
        }

        //find mrk mids missing in source and add them marked as missing
        $this->padSourceMrkTags();
        //find mrk mids missing in target and add them marked as missing
        $this->padTargetMrkTags();

        if($hasNoTarget || $hasTargetSingle) {
            $preserveWhitespace = $this->currentPlainSource['openerMeta']['preserveWhitespace'];
        }
        else {
            $preserveWhitespace = $this->currentPlainTarget['openerMeta']['preserveWhitespace'];
        }
        $this->otherContent->initOnUnitEnd($hasNoTarget || $hasEmptyTarget, $preserveWhitespace);

        $createdSegmentIds = [];
        foreach($this->sourceProcessOrder as $mid) {
            if($mid === '') {
                //if mid was empty string there was an error, ignore the segment, logging was already done
                unset($this->currentTarget[$mid]);
                continue;
            }
            $currentSource = $this->currentSource[$mid];
            $isSourceMrkMissing = ($currentSource == self::MISSING_MRK);
            
            if($isSourceMrkMissing) {
                $sourceChunksOriginal = $sourceChunks = [];
            }
            else {
                //parse the source chunks
                $sourceChunksOriginal = $sourceChunks = $this->xmlparser->getRange($currentSource['opener']+1, $currentSource['closer']-1, static::XML_REPARSE_CONTENT);

                if(! $this->sourceValidation($mid, $currentSource, $sourceChunks, $placeHolders)) {
                    continue;
                }

                //due XML_REPARSE_CONTENT it can happen that $sourceChunksOriginal will be a string, so we just put it into an array for further processing
                if(!is_array($sourceChunksOriginal)){
                    $sourceChunksOriginal = [$sourceChunksOriginal];
                }
                $sourceChunks = $this->contentConverter->convert($sourceChunks, true, $currentSource['openerMeta']['preserveWhitespace']);
                $sourceSegment = $this->xmlparser->join($sourceChunks);
                
                //if there is no source content, nothing can be done
                if(empty($sourceSegment) && $sourceSegment !== "0"){
                    unset($this->currentTarget[$mid]);
                    continue;
                }
            }
            
            if($sourceEdit && $isSourceMrkMissing || $hasTargets && (empty($this->currentTarget[$mid]) && $this->currentTarget[$mid] !== "0")){
                $this->throwSegmentationException('E1067', [
                    'transUnitId' => $this->xmlparser->getAttribute($transUnit, 'id', '-na-'),
                    'mid' => $mid,
                ]);
            }
            if(empty($this->currentTarget) || empty($this->currentTarget[$mid]) && $this->currentTarget[$mid] !== "0"){
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
                    $targetChunksOriginal = $targetChunks = $this->xmlparser->getRange($currentTarget['opener']+1, $currentTarget['closer']-1);
                    
                    //if reparse content is enabled, we convert the chunks to a string, so reparsing is triggerd
                    if(static::XML_REPARSE_CONTENT) {
                        $targetChunks = $this->xmlparser->join($targetChunks);
                    }
                    //in targetChunks the content is converted (tags, whitespace etc)
                    $targetChunks = $this->contentConverter->convert($targetChunks, false, $currentTarget['openerMeta']['preserveWhitespace']);
                    unset($this->currentTarget[$mid]);
                }
            }
            
            $this->surroundingTags->calculate($preserveWhitespace, $sourceChunks, $targetChunks, $this->xmlparser);
            
            $this->segmentData = [];
            $this->segmentData[$sourceName] = [
                //for source column we dont have a place holder, so we just cut off the leading/trailing tags and import the rest as source
                'original' => $this->xmlparser->join($this->surroundingTags->sliceTags($sourceChunks))
            ];
        
            //for target we have to do the same tag cut off on the converted chunks to be used,
            $targetChunksTagCut = $this->surroundingTags->sliceTags($targetChunks);
            $this->segmentData[$targetName] = [
                'original' => $this->xmlparser->join($targetChunksTagCut)
            ];
            
            //parse attributes for each found segment not only for the whole trans-unit
            $attributes = $this->parseSegmentAttributes($transUnit, $mid);
            if($currentTarget == self::MISSING_MRK) {
                $attributes->matchRateType = editor_Models_Segment_MatchRateType::TYPE_MISSING_TARGET_MRK;
            } elseif($currentSource == self::MISSING_MRK) {
                $attributes->matchRateType = editor_Models_Segment_MatchRateType::TYPE_MISSING_SOURCE_MRK;
            }
            
            //first we save the previous other content length to the previous segment (only if preserveWhitespace true)
            $this->otherContent->saveTargetOtherContentLength($attributes);
            
            //The internal $mid has to be added to the DB mid of <sub> element, needed for exporting the content again
            if(strpos($mid, self::PREFIX_SUB) === 0) {
                $this->setMid($this->_mid.'-'.$mid);
            }

            $emptyInitialTarget = empty($targetChunksOriginal);
            $hasCutTargetContent = empty($this->segmentData[$targetName]['original']) || $this->segmentData[$targetName]['original'] === "0";
            $targetHasTagsOnly = !$this->hasText($this->segmentData[$targetName]['original']);
            //if source contains tags only or is empty (and is no missing source) then we are able to ignore non textual segments if target fulfills the given 3 criterias
            if(!$isSourceMrkMissing && !$this->hasText($this->segmentData[$sourceName]['original']) && ($emptyInitialTarget || $hasCutTargetContent || $targetHasTagsOnly)) {
                //if empty target, we fill the target with the source content, and ignore the segment then in translation
                //  on reviewing and if target content was given, then it will be ignored too
                //  on reviewing needs $hasOriginalTarget to be true, which is the case by above if
                $placeHolders[$mid] = $this->xmlparser->join($emptyInitialTarget ? $sourceChunksOriginal : $targetChunksOriginal);
                //we add the length of the ignored segment to the additionalUnitLength
                $this->otherContent->addIgnoredSegmentLength($emptyInitialTarget ? $sourceChunks : $targetChunks, $attributes);
                continue;
            }
            $createdSegmentIds[] = $segmentId = $this->setAndSaveSegmentValues();
            //only with a segmentId (in case of ProofProcessor) we can save comments
            if($segmentId !== false && is_numeric($segmentId)) {
                $this->importComments((int) $segmentId);
            }
            if($currentTarget !== self::MISSING_MRK) {
                //we add a placeholder if it is a real segment, not just a placeholder for a missing mrk
                $placeHolders[$mid] = $this->surroundingTags->getLeading().$this->getFieldPlaceholder($segmentId, $targetName).$this->surroundingTags->getTrailing();
            }
        }
        
        //normally we get at least one attributes object above, if we have none, so segment is saved, so we don't have to process the lengths
        if(!empty($attributes)) {
            $this->otherContent->updateAdditionalUnitLength($attributes);
        }
        
        if(!empty($this->currentTarget)){
            $this->throwSegmentationException('E1068', [
                'transUnitId' => $this->xmlparser->getAttribute($transUnit, 'id', '-na-'),
                'mids' => join(', ', array_keys($this->currentTarget)),
            ]);
        }
        
        //if we dont find any usable segment or the segment is locked, we dont have to place the placeholder
        if(empty($placeHolders) || !$this->processSegment){
            return $createdSegmentIds;
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
        
        $otherContent = $this->otherContent->checkAndPrepareOtherContent();
        
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

        return $createdSegmentIds;
    }

    /**
     * Method Stub: Possibility for additional source validations, return true to process as usual, return false to skip segment import
     * @param string $mid
     * @param array $currentSource
     * @param array $sourceChunks
     * @param array $placeHolders
     * @return bool
     */
    protected function sourceValidation(string $mid, array $currentSource, array $sourceChunks, array & $placeHolders): bool {
        return true;
    }

    /**
     * returns true if the source is segmented with MRKs, but target not, may happen with translate=no segments from acr*
     * DOES NOT CHECK IF TARGET IS EMPTY! MUST BE DONE BEFORE!
     * @return bool
     */
    private function isSourceSegmentedButTargetNot(): bool {
        $isMrk = function($id) {
            return strpos($id, 'mrk-') === 0;
        };
        $sourceSegmented = !empty(array_filter($this->currentSource, $isMrk, ARRAY_FILTER_USE_KEY));
        $targetSegmented = !empty(array_filter($this->currentTarget, $isMrk, ARRAY_FILTER_USE_KEY));
        return $sourceSegmented && !$targetSegmented;
    }

    /**
     * It must be sure, that this code runs after all other attribute calculations!
     * {@inheritDoc}
     * @see editor_Models_Import_FileParser::setCalculatedSegmentAttributes()
     */
    protected function setCalculatedSegmentAttributes() {
        $attributes = parent::setCalculatedSegmentAttributes();
        if($attributes->editable && strpos($attributes->matchRateType, editor_Models_Segment_MatchRateType::TYPE_MISSING_TARGET_MRK) !== false){
            $attributes->editable = false; //if its a missing target the segment is not editable
        }
        return $attributes;
    }
    
    /**
     * returns false if segment content contains only tags
     * @param string $segmentContent
     * @return boolean
     */
    protected function hasText($segmentContent) {
        return $this->internalTag->hasText($segmentContent);
    }
    
    /**
     * Imports the comments of last processed segment
     * @param int $segmentId
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
            $segment = ZfExtended_Factory::get('editor_Models_Segment');
            /* @var $segment editor_Models_Segment */
            $segment->load($segmentId);
            $comment->updateSegment($segment, $this->task->getTaskGuid());
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
            $this->wordCount += (int) trim($this->xmlparser->getNextChunk());
        }
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
        $isSource = empty($this->xmlparser->getParent('target'));
        $this->otherContent->add($other, $isSource);
    }
    
    /**
     * returns true if target is a single tag (<target/>) or is empty <target></target>, where whitespace between the both targets matters for emptiness depending on preserveWhitespace
     * @param array $openerMeta
     * @param int $closerKey
     * @return boolean
     */
    protected function isEmptyTarget(array $openerMeta, $closerKey) {
        if($openerMeta['isSingle']) {
            return true;
        }
        $preserveWhitespace = $openerMeta['preserveWhitespace'];
        $content = $this->xmlparser->getRange($openerMeta['openerKey']+1, $closerKey - 1, true);
        return $preserveWhitespace ? (empty($content) && $content !== "0") : (strlen(trim($content)) === 0);
    }
}
