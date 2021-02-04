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
 * Converts a List with Segments to XML
 *
 * TODO: MQM and Terminology markup export is missing!
 * FIXME: use DOMDocument or similar to create the XML to deal correctly with escaping of strings!
 */
abstract class editor_Models_Converter_SegmentsToXliffAbstract {
    /**
     * includeDiff                = boolean, enable or disable diff generation, defaults to false
     * @var string
     */
    const CONFIG_INCLUDE_DIFF = 'includeDiff';
    /**
     * plainInternalTags          = boolean, exports internal tags plain content,
     *                              currently only needed for BEO export, defaults to false
     * @var string
     */
    const CONFIG_PLAIN_INTERNAL_TAGS = 'plainInternalTags';
    /**
     * addRelaisLanguage          = boolean, add relais language target as alt trans (if available), defaults to true
     * @var string
     */
    const CONFIG_ADD_RELAIS_LANGUAGE = 'addRelaisLanguage';
    /**
     * addComments                = boolean, add comments, defaults to true
     * @var string
     */
    const CONFIG_ADD_COMMENTS = 'addComments';
    /**
     * addStateAndQm              = boolean, add segment state and QM, defaults to true
     * @var string
     */
    const CONFIG_ADD_STATE_QM = 'addStateAndQm';
    /**
     * addAlternatives            = boolean, add target alternatives as alt trans, defaults to false
     * @var string
     */
    const CONFIG_ADD_ALTERNATIVES = 'addAlternatives';
    /**
     * addPreviousVersion         = boolean, add target original as alt trans, defaults to true
     * @var string
     */
    const CONFIG_ADD_PREVIOUS_VERSION = 'addPreviousVersion';
    /**
     * addDisclaimer              = boolean, add disclaimer that format is not 100% xliff 1.2, defaults to true
     * @var string
     */
    const CONFIG_ADD_DISCLAIMER = 'addDisclaimer';
    
    /**
     * addTerminology             = boolean, add existing terminology as mrk tags
     * @var string
     */
    const CONFIG_ADD_TERMINOLOGY = 'addTerminology';
    
    const XML_HEADER = '<?xml version="1.0" encoding="UTF-8"?>';
    
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * @var editor_Models_Export_FileParser_Sdlxliff
     */
    protected $exportParser;
    
    /**
     * @var editor_Models_Comment
     */
    protected $comment;
    
    /**
     * resulting XML buffer
     * @var array
     */
    protected $result = array();
    
    /**
     * different data needed while converting to XML
     * @var array
     */
    protected $data = array();
    
    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $sfm;
    
    /**
     * @var editor_Models_Export_DiffTagger_Sdlxliff
     */
    protected $differ = null;
    
    /**
     * @var editor_Models_Segment_Utility
     */
    protected $segmentUtility;
    
    /**
     * @var array
     */
    protected $options;
    
    /**
     * contains a list of the included namespaces in the header
     * @var array
     */
    protected $enabledNamespaces = [];
    
    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $taghelperInternal;
    
    /**
     * @var editor_Models_Segment_TrackChangeTag
     */
    protected $taghelperTrackChanges;
    
    /**
     * @var editor_Models_Segment_TermTag
     */
    protected $taghelperTerm;
    
    /**
     * @var editor_Models_Segment_QmSubsegments
     */
    protected $taghelperMqm;
    
    /**
     * @var array current tag map of replaced internal tags with g/x/bx/ex tags
     */
    protected $tagMap;
    
    /**
     * id counter for the generated g/x/bx/ex tags
     * @var integer
     */
    protected $tagId = 1;
    
    /**
     * Constructor
     *
     * Supported parameters for $config are
     * - includeDiff                = boolean, enable or disable diff generation, defaults to false
     * - plainInternalTags          = boolean, exports internal tags plain content,
     *                                currently only needed for BEO export, defaults to false
     * - addRelaisLanguage          = boolean, add relais language target as alt trans (if available), defaults to true
     * - addComments                = boolean, add comments, defaults to true
     * - addStateAndQm              = boolean, add segment state and QM, defaults to true
     * - addAlternatives            = boolean, add target alternatives as alt trans, defaults to false
     * - addPreviousVersion         = boolean, add target original as alt trans, defaults to true
     * - addDisclaimer              = boolean, add disclaimer that format is not 100% xliff 1.2, defaults to true
     *
     * @param array $config
     */
    public function __construct(array $config = []){
        $this->setOptions($config);
        
        $this->comment = ZfExtended_Factory::get('editor_Models_Comment');
        $this->segmentUtility = ZfExtended_Factory::get('editor_Models_Segment_Utility');
        $this->differ = ZfExtended_Factory::get('editor_Models_Export_DiffTagger_Sdlxliff');
    }
    
    /**
     * For the options see the constructor
     * @see self::__construct
     * @param array $config
     */
    public function setOptions(array $config) {
        $this->options = $config;
    }
    
    /**
     * converts a list with segment data to xliff
     *
     * @param editor_Models_Task $task
     * @param array $segments
     */
    public function convert(editor_Models_Task $task, array $segments) {
        $this->result = [self::XML_HEADER];
        $this->task = $task;
        $allSegmentsByFile = $this->reorderByFilename($segments);
        
        $this->initConvertionData();
        
        $this->createXmlHeader();
        
        foreach($allSegmentsByFile as $filename => $segmentsOfFile) {
            $this->processAllSegments($filename, new ArrayIterator($segmentsOfFile));
        }
        
        return $this->finishResult();
    }
    
    /**
     * Exports a task into the xliff dialect of this class
     * @param editor_Models_Task $task
     */
    public function export(editor_Models_Task $task) {
        $this->result = [self::XML_HEADER];
        $this->task = $task;
        
        $this->initConvertionData();
        $this->createXmlHeader();
        
        $foldertree = ZfExtended_Factory::get('editor_Models_Foldertree');
        /* @var $foldertree editor_Models_Foldertree */
        $foldertree->setPathPrefix('');
        $paths = $foldertree->getPaths($this->task->getTaskGuid(), 'file');
        
        foreach($paths as $fileId => $filename) {
            $segmentsOfFile = ZfExtended_Factory::get('editor_Models_Segment_Iterator', [$this->task->getTaskGuid(), $fileId]);
            /* @var $segmentsOfFile editor_Models_Segment_Iterator */
            $this->processAllSegments($filename, $segmentsOfFile);
        }
        
        return $this->finishResult();
    }
    
    /**
     * process and convert all segments to xliff
     * @param string $filename
     * @param array $segmentsOfFile
     */
    abstract protected function processAllSegments($filename, Traversable $segmentsOfFile);
    
    /**
     * Internally we use array based access on the segment data, but data gan be given as editor_Models_Segment or StdClass or array
     * @param mixed $data
     * @return array
     */
    protected function unifySegmentData($data){
        if(is_array($data)) {
            return $data;
        }
        if(is_object($data)) {
            if($data instanceof editor_Models_Segment) {
                $data = $data->getDataObject();
            }
            if($data instanceof stdClass) {
                return (array) $data;
            }
        }
        return null;
    }
    
    protected function finishResult() {
        $this->result[] = '</xliff>';
        return join("\n", $this->result);
    }
    
    /**
     * initializes internally needed data for convertion
     */
    protected function initConvertionData() {
        $task = $this->task;
        
        /**
         * define autostates
         */
        $autoStates = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
        $refl = new ReflectionClass($autoStates);
        $this->data['autostates'] = array_map('strtolower', array_flip($refl->getConstants()));
        
        /**
         * define languages
         */
        $lang = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $lang editor_Models_Languages */
        $lang->load($task->getSourceLang());
        $this->data['sourceLang'] = $lang->getRfc5646();
        $lang->load($task->getTargetLang());
        $this->data['targetLang'] = $lang->getRfc5646();
        
        $this->data['relaisLang'] = $task->getRelaisLang();
        //disable relais export by setting relais lang to false
        if(empty($this->data['relaisLang']) || !isset($this->options[self::CONFIG_ADD_RELAIS_LANGUAGE])|| !$this->options[self::CONFIG_ADD_RELAIS_LANGUAGE]){
            $this->data['relaisLang'] = false;
        }
        else {
            $lang->load($task->getRelaisLang());
            $this->data['relaisLang'] = $lang->getRfc5646();
        }
        
        /**
         * define first soruce and target fields
         */
        $this->sfm = editor_Models_SegmentFieldManager::getForTaskGuid($task->getTaskGuid());
        //both getFirst calls throw an exception if no corresponding field is given, that should not be, so uncatched is OK.
        $this->data['firstTarget'] = $this->sfm->getFirstTargetName();
        $this->data['firstSource'] = $this->sfm->getFirstSourceName();
    }
    
    /**
     * returns the export fileparser of the affected file
     * @param int $fileId
     * @param string $filename
     * @return mixed|object
     */
    protected function getExportFileparser($fileId, $filename) {
        $file = ZfExtended_Factory::get('editor_Models_File');
        /* @var $file editor_Models_File */
        $file->load($fileId);
        $exportParser = str_replace('_Import_', '_Export_', $file->getFileParser());
        return ZfExtended_Factory::get($exportParser, [$this->task, 0, $filename]);
    }
    
    /**
     * prepares tag storage in the xliff header - omitted with CONFIG_PLAIN_INTERNAL_TAGS
     * @return integer the index of the filemap placeholder in the result array
     */
    protected function prepareTagsStorageInHeader() {
        if($this->options[self::CONFIG_PLAIN_INTERNAL_TAGS]) {
            return null;
        }
        $fileMapKey = count($this->result);
        $this->result[] = 'FILE_MAP_PLACEHOLDER';

        $this->tagId = 1; //we start for each file with tag id = 1
        $this->tagMap = [];
        return $fileMapKey;
    }
        
    /**
     * stores the content tags in the xliff header - omitted with CONFIG_PLAIN_INTERNAL_TAGS
     * @param int $fileMapKey the index of the filemap placeholder in the result array
     */
    protected function storeTagsInHeader($fileMapKey) {
        if($this->options[self::CONFIG_PLAIN_INTERNAL_TAGS]) {
            return;
        }
        if(empty($this->tagMap)){
            unset($this->result[$fileMapKey]);
        }
        else {
            $this->result[$fileMapKey] = '<header><translate5:tagmap>'.base64_encode(serialize($this->tagMap)).'</translate5:tagmap></header>';
        }
    }
    
    protected function addAltTransToResult($targetText, $lang, $label, $type = null) {
        $alttranstype = empty($type) ? '' : ' alttranstype="'.$this->escape($type).'"';
        $this->result[] = '<alt-trans dx:origin-shorttext="'.$this->escape($label).'"'.$alttranstype.'>';
        $this->result[] = '<target xml:lang="'.$this->escape($lang).'">'.$targetText.'</target></alt-trans>';
    }
    
    protected function addDiffToResult($targetEdit, $targetOriginal, $label, $segment) {
        $diffResult = $this->differ->diffSegment($targetOriginal, $targetEdit, $segment['timestamp'], $segment['userName']);
        $this->addAltTransToResult($diffResult, $this->data['targetLang'], $label.'-diff', 'reference');
    }
    
    /**
     * converts a 1D array in a 2D array, where the original filenames containing the segments are the keys of the first dimension.
     * returns: array('FILENAME_1' => array(seg1, seg2), 'FILENAME_2' => array(seg3, seg4)
     *
     * @param array $segments
     * @return array
     */
    protected function reorderByFilename(array $segments) {
        $foldertree = ZfExtended_Factory::get('editor_Models_Foldertree');
        /* @var $foldertree editor_Models_Foldertree */
        $foldertree->setPathPrefix('');
        $paths = $foldertree->getPaths($this->task->getTaskGuid(), 'file');
        $result = array_fill_keys($paths, array());
        foreach($segments as $segment) {
            $file = $paths[$segment['fileId']];
            $result[$file][] = $segment;
        }
        return $result;
    }
    
    protected function initTagHelper() {
        if(empty($this->taghelperInternal)) {
            $this->taghelperInternal = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        }
        if(empty($this->taghelperTerm)) {
            $this->taghelperTerm = ZfExtended_Factory::get('editor_Models_Segment_TermTag',[$this->taghelperInternal]);
        }
        if(empty($this->taghelperMqm)) {
            $this->taghelperMqm = ZfExtended_Factory::get('editor_Models_Segment_QmSubsegments');
        }
        if(empty($this->taghelperTrackChanges)) {
            $this->taghelperTrackChanges = ZfExtended_Factory::get('editor_Models_Segment_TrackChangeTag');
        }
    }
    
    /**
     * escapes a string to be used in XML
     * @param string $text
     * @param bool $isAttribute set to false, if value is not used as attribute (no double quote escaping)
     * @return string
     */
    protected function escape($text, $isAttribute = true) {
        if($isAttribute) {
            return htmlspecialchars($text, ENT_XML1 | ENT_COMPAT, 'UTF-8');
        }
        return htmlspecialchars($text, ENT_XML1, 'UTF-8');
    }
}