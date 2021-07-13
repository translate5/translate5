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

/**
 * Handles OtherContent stuff (recognition and length calculation) on XLIFF import 
 */
class editor_Models_Import_FileParser_Xlf_OtherContent {
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
     * Flag if unknown content should be collected or not
     * @var boolean
     */
    protected $checkContentOutsideMrk = false;
    
    /**
     * Flag if we should preserve whitespace or not
     * @var boolean
     */
    protected $preserveWhitespace = false;
    
    /**
     * Flag if we should use source or target other content 
     * @var boolean
     */
    protected $useSource = false;
    
    /**
     * @var editor_Models_Import_FileParser_Xlf_ContentConverter
     */
    protected $contentConverter = null;
    
    /**
     * @var editor_Models_Segment
     */
    protected $segmentBareInstance;
    
    /**
     * @var editor_Models_Segment_Meta
     */
    protected $segmentMetaBareInstance;
    
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * @var int
     */
    protected $fileId;
    
    /**
     * @var editor_Models_Import_FileParser_XmlParser
     */
    protected $xmlparser;
    
    /**
     * contains the additional unit length 
     * @var integer
     */
    protected $additionalUnitLength = 0;
    
    /**
     * Constructor
     * @param editor_Models_Import_FileParser_Xlf_ContentConverter $converter
     * @param editor_Models_Segment $segment
     * @param editor_Models_Task $task
     * @param int $fileId
     */
    public function __construct(editor_Models_Import_FileParser_Xlf_ContentConverter $converter, editor_Models_Segment $segment, editor_Models_Task $task, int $fileId) {
        $this->task = $task;
        $this->contentConverter = $converter;
        $this->segmentBareInstance = $segment;
        $this->segmentMetaBareInstance = ZfExtended_Factory::get('editor_Models_Segment_Meta');
        $this->fileId = $fileId;
    }
    
    /**
     * inits othercontent class for each transunit on the start, inits containser for data gathering
     * @param editor_Models_Import_FileParser_XmlParser $parser
     */
    public function initOnUnitStart(editor_Models_Import_FileParser_XmlParser $parser) {
        //we init the xmlparser for each transunit from outside
        $this->xmlparser = $parser;
        $this->initSource(); //reset otherContent for new source
        $this->initTarget(); //reset otherContent for new target
        $this->checkContentOutsideMrk = false;
        $this->additionalUnitLength = 0;
    }
    
    /**
     * inits othercontent class for each transunit on the end of the unit, before processing starts
     * @param bool $useSource
     * @param bool $preserveWhitespace
     */
    public function initOnUnitEnd(bool $useSource, bool $preserveWhitespace) {
        $this->useSource = $useSource;
        $this->preserveWhitespace = $preserveWhitespace;
    }
    
    /**
     * Add other content to the wither source or target container
     * @param string $otherContent
     * @param bool $isSource
     */
    public function add(string $otherContent, bool $isSource) {
        if($isSource) {
            $container = &$this->otherContentSource;
        }
        else {
            $container = &$this->otherContentTarget;
        }
        if(count($container) === 0) {
            //if there is no content, this is the first content before the first mrk at all
            $container[] = '';
        }
        $keys = array_keys($container);
        //always add content to the current last element of the array. new elements per MRKs are added elsewhere
        $container[end($keys)] .= $otherContent;
    }
    
    /**
     * Sets if the content outside a mrk should be checked or not
     * @param bool $enable
     */
    public function setCheckContentOutsideMrk(bool $enable) {
        $this->checkContentOutsideMrk = $enable;
    }
    
    /**
     * Inits the target other content with an empty array
     */
    public function initTarget() {
        $this->otherContentTarget = [];
    }
    
    /**
     * Inits the source other content with an empty array
     */
    public function initSource() {
        $this->otherContentSource = [];
    }
    
    /**
     * add a new other content value to a mid
     * @param string $mid
     * @param string $value
     */
    public function addTarget(string $mid, string $value) {
        $this->otherContentTarget[$mid] = $value;
    }
    
    /**
     * add a new other content value to a mid
     * @param string $mid
     * @param string $value
     */
    public function addSource(string $mid, string $value) {
        $this->otherContentSource[$mid] = $value;
    }
    
    /**
     * Adds
     * @param string $content
     * @param editor_Models_Import_FileParser_SegmentAttributes $attributes
     */
    public function addIgnoredSegmentLength(array $content, editor_Models_Import_FileParser_SegmentAttributes $attributes) {
        //we have to convert the ignored content to translate5 content, otherwise the length would not be correct (for example content in <ph> tags would be counted then too)
        $contentLength = $this->segmentBareInstance->textLengthByImportattributes($this->xmlparser->join($content), $attributes, $this->task->getTaskGuid(), $this->fileId);
        $this->additionalUnitLength += $contentLength;
        
        //we add the additional mrk length of the ignored segment to the additionalUnitLength
        $this->additionalUnitLength += $attributes->additionalMrkLength;
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
     * @param bool $useSourceOtherContent
     */
    public function saveTargetOtherContentLength(editor_Models_Import_FileParser_SegmentAttributes $attributes) {
        $otherContent = $this->useSource ? $this->otherContentSource : $this->otherContentTarget;
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
        
        //the other lengths are stored per affected segment, so if there is none, do nothing
        if(empty($otherContent[$attributes->mrkMid])) {
            return;
        }
        
        if($this->preserveWhitespace) {
            //with preserve whitespace we use the original content
            $content = $this->convertText($otherContent[$attributes->mrkMid]);
        } else {
            //with ignoring whitespace we prepare the otherContent like in checkAndPrepareOtherContent, but only if we are not
            //in the last MRK: here we may not save any additionalLength, since we consider only the content inbetween MRKs
            //<target>additionalUnitLength ignored<mrk>content</mrk> this length is needed<mrk>content</mrk>this length is ignored again</target>
            $mrkMidKeys = array_keys($otherContent);
            if($attributes->mrkMid != end($mrkMidKeys)) {
                //Attention: if there is a tag between two MRKs in a formatted XML this tag has leading and trailing multiple whitespace and newline characters.
                // If $preserveWhitespace is true, this whitespace remains as it is, and is counted completely (10 lines above from here)
                // If $preserveWhitespace is false, the whitespace before and after the tag is condensed to one single whitespace character each,
                //  so that in sum this part of the segments has a length of at least 2 characters
                $content = $this->convertText($this->prepareMrkInbetweenContent($otherContent[$attributes->mrkMid]));
            }
            else {
                $content = '';
            }
        }
        //the other lengths are stored per affected segment (and is already added to the length stored in metaCache per segment)
        $attributes->additionalMrkLength = $this->segmentBareInstance->textLengthByImportattributes($content, $attributes, $this->task->getTaskGuid(), $this->fileId);
    }
    
    /**
     * for other content length calculation we have to convert the othercontent to translate5 content (mainly because of the tags)
     *  this must be done with preserve whitespace true (otherwise the length of tags would be ignored)
     * @param string $text
     * @return string
     */
    protected function convertText(string $text): string {
        $preserveWhitespace = true;
        return $this->xmlparser->join($this->contentConverter->convert($text, true, $preserveWhitespace));
    }
    
    /**
     * calculates the additionalUnitLength for the whole transunit, needs at least a SegmentAttributes for several parameters
     * @param editor_Models_Import_FileParser_SegmentAttributes $attributes
     */
    public function updateAdditionalUnitLength(editor_Models_Import_FileParser_SegmentAttributes $attributes) {
        $otherContent = $this->useSource ? $this->otherContentSource : $this->otherContentTarget;
        //by definition the first otherContent belongs to the whole transunit - this value is stored in each segment
        // only of preserveWhitespace is true
        if($this->preserveWhitespace && !empty($otherContent[0])) {
            $this->additionalUnitLength += $this->segmentBareInstance->textLengthByImportattributes($this->convertText($otherContent[0]), $attributes, $this->task->getTaskGuid(), $this->fileId);
        }
        
        if($this->additionalUnitLength > 0) {
            $this->segmentMetaBareInstance->updateAdditionalUnitLength($this->task->getTaskGuid(), $attributes->transunitId, $this->additionalUnitLength);
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
    public function checkAndPrepareOtherContent() {
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
        if(!empty($otherContent) && preg_match('/[^\s]+/', $this->contentConverter->removeXlfTagsAndProtectedWhitespace($otherContent))) {
            $data = array_merge($otherContentSource, $otherContentTarget);
            foreach ($data as &$d) {
                //print the code point for non printable characters
                if(!ctype_print($d) && mb_strlen($d)>0){
                    $tmp = [];
                    $tmp['unicode'] = json_encode((string)$d);
                    $tmp['codepoint'] = mb_ord($d);
                    $d=[];
                    $d = $tmp;
                }
            }
            $this->throwSegmentationException('E1069', [
                'content' => print_r($data,1),
                'filename' => $this->contentConverter->getFileName()
            ]);
        }
        
        $otherContent = $this->useSource ? $otherContentSource : $otherContentTarget;
        
        
        // default behaviour for whitespace hanling in translate5 is:
        if($this->preserveWhitespace) {
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
}