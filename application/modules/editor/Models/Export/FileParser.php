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
 * Enthält Methoden zum Fileparsing für den Export
 */
abstract class editor_Models_Export_FileParser {
    use editor_Models_Export_FileParser_MQMTrait;
    
    /**
     * @var string
     */
    protected $_exportFile = NULL;
    /**
     * @var string
     */
    protected $_skeletonFile = NULL;
    /**
     * @var integer
     */
    protected $_fileId = NULL;
    /**
     * @var editor_Models_Segment aktuell bearbeitetes Segment
     */
    protected $_segmentEntity = NULL;
    /**
     * contains a limited amount of loaded segments
     * @var array
     */
    protected $segmentCache = array();
    /**
     * @var string Klassenname des Difftaggers
     */
    protected $_classNameDifftagger = NULL;
    /**
     * @var object 
     */
    protected $_difftagger = NULL;
    /**
     * @var boolean wether or not to include a diff about the changes in the exported segments
     *
     */
    protected $_diff= false;
    /**
     * @var editor_Models_Task current task
     */
    protected $_task;
    /**
     * @var string
     */
    protected $_taskGuid;
    /**
     * @var Zend_Config
     */
    protected $config;
    /**
     *
     * @var string path including filename, on which the exported file will be saved
     */
    protected $path;
    /**
     * @var ZfExtended_Zendoverwrites_Translate
     */
    protected $translate;
    
    /**
     * Disables the MQM Export if needed
     * @var boolean
     */
    protected $disableMqmExport = false;
    
    /**
     * Container for content tag protection
     * @var array
     */
    protected $originalTags;
    
    /**
     * each array element contains the comments for one segment
     * the array-index is set to an ID for the comments
     * @var array
     */
    protected $comments;
    
    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $tagHelper;
    
    /**
     * @var editor_Models_Segment_TermTag
     */
    protected $termTagHelper;
    
    /**
     * @var editor_Models_Segment_Whitespace
     */
    protected $whitespaceHelper;
    
    /**
     * @var ZfExtended_EventManager
     */
    protected $events;
    
    /**
     * contains the length of the last content returned by getSegmentContent
     * @var integer
     */
    protected $lastSegmentLength = 0;
    
    /**
     * collected segmentNrs with tag missing or to much tags compared to the source
     * @var array
     */
    protected $segmentsWithTagErrors = [];
    
    /**
     * @var ZfExtended_Logger
     */
    protected $log;
    
    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $segmentFieldManager;
    
    /**
     * 
     * @param int $fileId
     * @param bool $diff
     * @param editor_Models_Task $task
     * @param string $path 
     * @throws Zend_Exception
     */
    public function __construct(int $fileId,bool $diff,editor_Models_Task $task, string $path) {
        if(is_null($this->_classNameDifftagger)){
            //this->_classNameDifftagger must be defined in the child class.
            throw new editor_Models_Export_FileParser_Exception('E1085', [
                'task' => $task,
            ]);
        }
        $this->_fileId = $fileId;
        $this->_diffTagger = ZfExtended_Factory::get($this->_classNameDifftagger);
        $this->_diff = $diff;
        $this->_task = $task;
        $this->_taskGuid = $task->getTaskGuid();
        $this->path = $path;
        $this->config = Zend_Registry::get('config');
        $this->log = Zend_Registry::get('logger')->cloneMe('editor.export.fileparser');
        $this->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $this->tagHelper = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        $this->termTagHelper = ZfExtended_Factory::get('editor_Models_Segment_TermTag');
        $this->whitespaceHelper = ZfExtended_Factory::get('editor_Models_Segment_Whitespace');
        $this->segmentFieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        $this->segmentFieldManager->initFields($this->_taskGuid);

        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(get_class($this)));
    }

    /**
     * Gibt eine zu exportierende Datei bereits korrekt für den Export geparsed zurück
     * 
     * @return string file
     */
    public function getFile() {
        $file = ZfExtended_Factory::get('editor_Models_File');
        /* @var $file editor_Models_File */
        $file->load($this->_fileId);
        
        $this->getSkeleton($file);
        $this->parse();
        $this->convertEncoding($file);
        return $this->_exportFile;
    }
    
    public function saveFile() {
        file_put_contents($this->path, $this->getFile());
    }
    
    /**
     * returns the collected segments with tag errors
     * @return array
     */
    public function getSegmentTagErrors() {
        return $this->segmentsWithTagErrors;
    }
    
    /**
     * übernimmt das eigentliche FileParsing
     *
     * - setzt an Stelle von <lekTargetSeg... wieder das überarbeitete Targetsegment ein
     * - befüllt $this->_exportFile
     */
    protected function parse() {
        $file = preg_split('#<lekTargetSeg([^>]+)/>#', $this->_skeletonFile, null, PREG_SPLIT_DELIM_CAPTURE);

        $count = count($file) - 1;
        for ($i = 1; $i < $count;) {
            $file[$i] = $this->preProcessReplacement($file[$i]);
            $matches = [];
            if (!preg_match('#^\s*id="([^"]+)"\s*(field="([^"]+)"\s*)?$#', $file[$i], $matches)) {
                //Error in Export-Fileparsing. instead of a id="INT" and a optional field="STRING" attribute the following content was extracted: "{content}"
                throw new editor_Models_Export_FileParser_Exception('E1086', [
                    'task' => $this->_task,
                    'content' => $file[$i],
                ]);
            }
          
            //check $matches[1] for integer (segmentId) if empty throw an exception
            settype($matches[1], 'int');
            if(empty($matches[1])) {
                //Error in Export-Fileparsing. instead of a id="INT" and a optional field="STRING" attribute the following content was extracted: "{content}"
                throw new editor_Models_Export_FileParser_Exception('E1087', [
                    'task' => $this->_task,
                    'content' => $file[$i],
                ]);
            }
          
            //alternate column is optional, use target as default
            if(isset($matches[3])) {
                $field = $matches[3];
            }
            else {
              $field = editor_Models_SegmentField::TYPE_TARGET;
            }
          
            $file[$i] = $this->getSegmentContent($matches[1], $field);
            
            $file = $this->writeMatchRate($file,$i);
            
            if($this->config->runtimeOptions->editor->export->exportComments) {
                $file[$i] = $this->injectComments($matches[1], $file[$i], $field);
            }

            $i = $i + 2;
        }
        $this->_exportFile = implode('', $file);
    }
    
    /**
     * for injecting comment markers into the content which was replaced from placeholder
     * for overwriting purposes
     * @param int $segmentId
     * @param string $segment
     * @param string $field
     * @return string $id of comments index in $this->comments | null if no comments exist
     */
    protected function injectComments(int $segmentId, string $segment, string $field) {
        return $segment;
    }
    
    /**
     * pre processor for the extracted lekTargetSeg attributes
     * for overwriting purposes only
     * @param string $attributes
     * @return string
     */
    protected function preProcessReplacement($attributes) {
        return $attributes;
    }
    
    /**
     * dedicated to write the match-Rate to the right position in the target format
     * for overwriting purposes only
     * @param array $file that contains file as array as splitted by parse function
     * @param int $i position of current segment in the file array
     * @return string
     */
    protected function writeMatchRate(array $file, int $i) {
        return $file;
    }
    
    /**
     * the browser adds non-breaking-spaces instead of normal spaces, if the user
     * types more than one space directly after eachother. For the GUI this
     * makes sense, because this way the whitespace can be presented in the 
     * correct visual form to the user (normal spaces would be shown as one
     * space in HTML). For the export they have to be reconverted to normal 
     * spaces
     * 
     * @param int $segmentId
     * @param string $segment
     * @return string $segment
     */
    protected function revertNonBreakingSpaces($segment){
        //replacing nbsp introduced by browser back to multiple spaces
        return preg_replace('#\x{00a0}#u',' ',$segment);
    }
    /**
     * returns the segment content for the given segmentId and field. Adds optional diff markup, and handles tags.
     * @param int $segmentId
     * @param string $field fieldname to get the content from
     * @return string
     */
    protected function getSegmentContent($segmentId, $field) {
        $this->_segmentEntity = $segment = $this->getSegment($segmentId);
        $segmentMeta = $segment->meta();
        
        $edited = (string) $segment->getFieldEdited($field);
        
        $trackChange=ZfExtended_Factory::get('editor_Models_Segment_TrackChangeTag');
        /* @var $trackChange editor_Models_Segment_TrackChangeTag */
        
        $edited= $trackChange->removeTrackChanges($edited);
        
        $edited = $this->tagHelper->protect($edited);
        $edited = $this->removeTermTags($edited);
        $edited = $this->tagHelper->unprotect($edited);
        $this->compareTags($segment, $edited, $field);
        
        //count length after removing removeTrackChanges and removeTermTags 
        // so that the same remove must not be done again inside of textLength
        //also add additionalMrkLength to the segment length for final length calculation 
        $this->lastSegmentLength = $segment->textLengthByMeta($edited,$segmentMeta,$segment->getFileId()) + $segmentMeta->getAdditionalMrkLength();
        
        $edited = $this->parseSegment($edited);
        $edited = $this->revertNonBreakingSpaces($edited);
        
        if(!$this->_diff){
            return $this->whitespaceHelper->unprotectWhitespace($edited);
        }
        
        $original = (string) $segment->getFieldOriginal($field);
        $original = $this->tagHelper->protect($original);
        $original = $this->removeTermTags($original);
        $original = $this->tagHelper->unprotect($original);
        $original = $this->parseSegment($original);
        try {
            $diffed = $this->_diffTagger->diffSegment($original, $edited, $segment->getTimestamp(), $segment->getUserName());
        }
        catch (Exception $e) {
            throw new editor_Models_Export_FileParser_Exception('E1088', [
                'task' => $this->_task,
                'fileId' => $this->_fileId,
            ], $e);
            
        }
        // unprotectWhitespace must be done after diffing!
        return $this->whitespaceHelper->unprotectWhitespace($diffed);
    }
    
    /**
     * Compares the real tags (ignores whitespace tags) of a source and target string, track the differences between the both along the segmentNrInTask
     * @param editor_Models_Segment $segment
     * @param string $target
     */
    protected function compareTags(editor_Models_Segment $segment, string $target, string $field) {
        $isTranslationTask = $this->_task->getEmptyTargets();
        $segmentNotTranslated = $segment->getAutoStateId() == editor_Models_Segment_AutoStates::NOT_TRANSLATED;
        //do the tag compare only if $field is editable (normally source is not)
        $fieldInfo = $this->segmentFieldManager->getByName($field);
        if(!$fieldInfo || !$fieldInfo->editable || $isTranslationTask && $segmentNotTranslated) {
            return;
        }
        //if it was a translation task, we have to compare agains the source tags, otherwise against the field original
        $source = $isTranslationTask ? $segment->getSource() : $segment->getFieldOriginal($field);
        $sourceTags = $this->tagHelper->getRealTags($source);
        $targetTags = $this->tagHelper->getRealTags($target);
        $notInTarget = $this->tagHelper->diffArray($sourceTags, $targetTags);
        $notInSource = $this->tagHelper->diffArray($targetTags, $sourceTags);
        if(empty($notInSource) && empty($notInTarget)) {
            return;
        }
        $this->segmentsWithTagErrors[] = [
            'id' => $segment->getId(),
            'fileId' => $segment->getFileId(),
            'field' => $field,
            'segmentNrInTask' => $segment->getSegmentNrInTask(),
            'additionalInTarget' => $notInSource,
            'missingInTarget' => $notInTarget,
        ];
    }
    
    /**
     * loads the segment to the given Id, caches a limited count of segments internally 
     * to prevent loading again while switching between fields
     * @param int $segmentId
     * @return editor_Models_Segment
     */
    protected function getSegment($segmentId){
        if(isset($this->segmentCache[$segmentId])) {
            return $this->segmentCache[$segmentId];
        }
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $segment->load($segmentId);
        $this->segmentCache[$segmentId] = $segment;
        //we keep a max of 50 segments, this should be enough
        if(count($this->segmentCache) > 50) {
            reset($this->segmentCache);
            $firstKey = key($this->segmentCache);
            unset($this->segmentCache[$firstKey]);
        }
        return $segment;
    }
    
    /**
     * creates termMarkup according to xliff-Syntax (<mrk ...) 
     * 
     * converts from:
     * <div class="term admittedTerm transNotFound" id="term_05_1_de_1_00010-0" title="">Hause</div>
     * to:
     * <mrk mtype="x-term-admittedTerm" mid="term_05_1_de_1_00010">Hause</mrk>
     * and removes the information about trans[Not]Found
     * 
     * @param string $segment
     * @param bool $removeTermTags, default = true
     * @return string $segment
     */
    protected function removeTermTags($segment) {
        return $this->termTagHelper->remove($segment);
    }
    
    /**
     * Loads the skeleton file from the disk and stores it internally
     * @param editor_Models_File $file
     */
    protected function getSkeleton(editor_Models_File $file) {
        $this->_skeletonFile = $file->loadSkeletonFromDisk($this->_task);
    }

    /**
     * Rekonstruiert in einem Segment die ursprüngliche Form der enthaltenen Tags
     *
     * @param string $segment
     * @return string $segment 
     */
    protected function parseSegment($segment) {
        return $this->tagHelper->restore($segment);
    }
    
    /**
     * converts $this->_exportFile back to the original encoding registered in the LEK_files
     * @param editor_Models_File $file
     */
    protected function convertEncoding(editor_Models_File $file){
        $enc = $file->getEncoding();
        if(is_null($enc) || $enc === '' || strtolower($enc) === 'utf-8'){
            return;
        }
        $this->_exportFile = iconv('utf-8', $enc, $this->_exportFile);
    }
    
    /**
     * Exports a single segment content, without MQM support!
     * Term Tags remains in the content and are not touched.
     * 
     * @param string $segment
     * @return string
     */
    public function exportSingleSegmentContent($segment) {
        //processing of term tags is done after using this method!
        $this->disableMqmExport = true;
        $segment = $this->parseSegment($segment);
        $segment = $this->revertNonBreakingSpaces($segment);
        return $this->whitespaceHelper->unprotectWhitespace($segment);
    }
}
