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
    
    protected $inSource = false;
    protected $inTarget = false;
    
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
    
    protected $currentSource = null;
    protected $currentTarget = null;
    
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
    public function __construct(string $path, string $fileName, integer $fileId, editor_Models_Task $task)
    {
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
        $this->xmlparser->registerElement('count', function($tag, $attributes, $key){
            if($this->xmlparser->hasParent('transunit')){
                $this->addupSegmentWordCount($attributes);
            }
        });
    }
    
    /**
     * registers handlers for source, seg-source and target nodes to be stored for later processing
     */
    protected function registerContent() {
        $source = function($tag, $key, $opener){
            //if there is already a source coming from seg-source use that and ignore the now parsed source
            if($tag == 'source' && !empty($this->source) && $this->source['tag'] == 'seg-source'){
                return;
            }
            $this->currentSource = [
                    'tag' => $tag,
                    'opener' => $opener['openerKey'],
                    'closer' => $key,
                    'openerMeta' => $opener,
            ];
        };
        
        $this->xmlparser->registerElement('source', null, $source);
        $this->xmlparser->registerElement('seg-source', null, $source);
        $this->xmlparser->registerElement('target', null, function($tag, $key, $opener){
            $this->currentTarget = [
                    'tag' => $tag,
                    'opener' => $opener['openerKey'],
                    'closer' => $key,
                    'openerMeta' => $opener,
            ];
        });
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
            $this->currentSource = null;
            $this->currentTarget = null; // set to null to identify if there is no a target at all
            
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
    protected function parseSegmentAttributes($attributes)
    {
        settype($attributes['id'], 'integer');
        //build mid from id of segment plus segmentCount, because xlf-file can have more than one file in it with repeatingly the same ids.
        // and one trans-unit (where the id comes from) can contain multiple mrk type seg tags, which are all converted into single segments.
        // instead of using mid from the mrk type seg element, the segmentCount as additional ID part is fine.
        $id = $attributes['id'].'_'.$this->segmentCount++;
        
        $segmentAttributes = $this->createSegmentAttributes($id);

        //process nonxliff attributes
        $this->namespaces->transunitAttributes($attributes, $segmentAttributes);
        $this->setMid($id);
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
        
        //parse the source chunks
        $sourceChunks = $this->xmlparser->getRange($this->currentSource['opener'], $this->currentSource['closer']);
        $sourceSegments = $this->contentConverter->convert($sourceChunks);
        
        //TODO <mrk type="seg"> subsegments are not supported so far.
        // The here uses data structure is prepared for them, so $this->contentConverter->convert returns an array of segments
        // currently it contains only one segment.
        
        //if there was no target at all we prefill the target segments with the source content (mainly to get the internal tags)
        //TODO see TRANSLATE-880: this prefill must be removed when the GUI provides the ability to take over tags
        if(is_null($this->currentTarget)) {
            $targetSegments = $sourceSegments;
        }
        else {
            //parse the target chunks
            $targetChunks = $this->xmlparser->getRange($this->currentTarget['opener'], $this->currentTarget['closer']);
            $targetSegments = $this->contentConverter->convert($targetChunks);
        }

        //check if sub segmentation of the mrk type seg tags was correct:
        $sourceMrkMids = array_keys($sourceSegments);
        $targetMrkMids = array_keys($targetSegments);
        if($sourceMrkMids !== $targetMrkMids) {
            //TODO prepared for MRK subsegments. Implement just a logging here, and import below only the segments with matching mrks
        }
        
        foreach($sourceSegments as $mid => $source) {
            if(!isset($targetSegments[$mid])) {
                continue; //mrk type seg mids are not matching, logging is done above
            }
            //if target is empty, prefill with source:
            //TODO see TRANSLATE-880: this prefill must be removed when the GUI provides the ability to take over tags
            if(empty($targetSegments[$mid])) {
                $targetSegments[$mid] = $source;
            }
            $this->segmentData = array();
            $this->segmentData[$sourceName] = array(
                'original' => $source
            );
        
            $this->segmentData[$targetName] = array(
                'original' => $targetSegments[$mid]
            );
            
            //parse attributes for each found segment not only for the whole trans-unit
            $attributes = $this->parseSegmentAttributes($transUnit);
            if(!$this->processSegment) {
                //add also translate="no" segments but readonly
                $attributes->editable = false;
            }
            
            //if target was given and source contains tags only or is empty, then it will be ignored
            if(!empty($this->currentTarget) && !$this->hasText($source)) {
                continue;
            }
            $segmentId = $this->setAndSaveSegmentValues();
            $placeHolder = $this->getFieldPlaceholder($segmentId, $targetName);
        }
        
        if(empty($sourceSegments) || empty($placeHolder)){
            //this return is needed since MRK implementation is not finished and 
            // by above hasText the above loop can be ended without providing any $placeHolder
            return;
        }
        
        //this solves TRANSLATE-879: sdlxliff and XLF import does not work with missing target
        if(is_null($this->currentTarget)){
            //TODO add also empty MRK tags not only the empty target node, see also below TODO
            $this->xmlparser->replaceChunk($this->currentSource['closer'], "</source>\r\n        <target>".$placeHolder.'</target>');
        }
        else {
            //clean up target content to empty, we store only our placeholder in the skeleton file
            $start = $this->currentTarget['opener'] + 1;
            $length = $this->currentTarget['closer'] - $start;
            $this->xmlparser->replaceChunk($start, '', $length);
            $this->xmlparser->replaceChunk($this->currentTarget['closer'], function($index, $oldChunk) use ($placeHolder) {
                //TODO integrate MRK tags here too. Proposal: clean up target (is done before) store mrks in the above contentConverter and reapply them here
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