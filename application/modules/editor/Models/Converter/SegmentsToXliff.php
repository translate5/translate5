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

/**
 * Converts a List with Segments to XML
 * 
 * TODO: MQM and Terminology markup export is missing! 
 */
class editor_Models_Converter_SegmentsToXliff {
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
     * @var editor_Models_Segment_TermTag
     */
    protected $taghelperTerm;
    
    /**
     * @var editor_Models_Segment_QmSubsegments
     */
    protected $taghelperMqm;
    
    /**
     * @var current tag map of replaced internal tags with g/x/bx/ex tags
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
        
        //flags defaulting to false
        $defaultsToFalse = [
                self::CONFIG_INCLUDE_DIFF, 
                self::CONFIG_PLAIN_INTERNAL_TAGS, 
                self::CONFIG_ADD_TERMINOLOGY, 
                self::CONFIG_ADD_ALTERNATIVES,
        ];
        foreach($defaultsToFalse as $key){
            settype($this->options[$key], 'bool');
        }
        
        //flags defaulting to true; if nothing given, empty is the falsy check
        $defaultsToTrue = [
                self::CONFIG_ADD_RELAIS_LANGUAGE, 
                self::CONFIG_ADD_COMMENTS, 
                self::CONFIG_ADD_STATE_QM,
                self::CONFIG_ADD_PREVIOUS_VERSION,
                self::CONFIG_ADD_DISCLAIMER,
        ];
        foreach($defaultsToTrue as $key){
            $this->options[$key] = !(array_key_exists($key, $config) && empty($config[$key]));
        }
        
        if(! $this->options[self::CONFIG_PLAIN_INTERNAL_TAGS] || $this->options[self::CONFIG_ADD_TERMINOLOGY]) {
            $this->initTagHelper();
        }
    }
    
    /**
     * converts a list with segment data to xml (xliff)
     * 
     * For Xliff see https://code.google.com/p/interoperability-now/downloads/detail?name=XLIFFdoc%20Representation%20Guide%20v1.0.1.pdf&can=2&q=
     * and http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html
     * 
     * @param editor_Models_Task $task
     * @param array $segments
     */
    public function convert(editor_Models_Task $task, array $segments) {
        $this->result = array('<?xml version="1.0" encoding="UTF-8"?>');
        $this->task = $task;
        $allSegmentsByFile = $this->reorderByFilename($segments);
        
        $this->initConvertionData();
        
        $this->createXmlHeader();
        
        foreach($allSegmentsByFile as $filename => $segmentsOfFile) {
            $this->processAllSegments($filename, $segmentsOfFile);
        }
        
        //XML Footer, no extra method
        $this->result[] = '</xliff>';
        
        $xml = join("\n", $this->result);

        return $xml;
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
        if(empty($this->data['relaisLang']) || !$this->options[self::CONFIG_ADD_RELAIS_LANGUAGE]){
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
     * Helper function to create the XML Header
     */
    protected function createXmlHeader() {
        $headParams = array('xliff', 'version="1.2"');
        
        $headParams[] = 'xmlns="urn:oasis:names:tc:xliff:document:1.2"';
        $headParams[] = 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"';
        $this->enabledNamespaces['xsi'] = 'xsi';
        
        //add DX namespace only if needed 
        $dx = $this->options[self::CONFIG_ADD_RELAIS_LANGUAGE] 
                || $this->options[self::CONFIG_INCLUDE_DIFF]
                || $this->options[self::CONFIG_ADD_COMMENTS]
                || $this->options[self::CONFIG_ADD_ALTERNATIVES]
                || $this->options[self::CONFIG_ADD_PREVIOUS_VERSION];
        if($dx) {
            $headParams[] = 'xmlns:dx="http://www.interoperability-now.org/schema"';
            $this->enabledNamespaces['dx'] = 'dx';
        }
        if($this->options[self::CONFIG_INCLUDE_DIFF]) {
            $headParams[] = 'xmlns:sdl="http://sdl.com/FileTypes/SdlXliff/1.0"';
            $this->enabledNamespaces['sdl'] = 'sdl';
        }
        $headParams[] = 'xsi:schemaLocation="urn:oasis:names:tc:xliff:document:1.2 xliff-doc-1_0_extensions.xsd"';
        if($dx) {
            $headParams[] = 'dx:version="1.4"';
        }
        $headParams[] = 'xmlns:translate5="http://www.translate5.net/"';
        $this->enabledNamespaces['translate5'] = 'translate5';
        $headParams[] = 'translate5:taskname="'.htmlspecialchars($this->task->getTaskName()).'"';
        $headParams[] = 'translate5:taskguid="'.htmlspecialchars($this->task->getTaskGuid()).'"';
        $this->result[] = '<'.join(' ', $headParams).'>';
        
        if($this->options[self::CONFIG_ADD_DISCLAIMER]) {
            $this->result[] = '<!-- attention: this format should be refactored to xliff 2.x. It will be, as soon as some one volunteers to do or donates funding for it -->';
            $this->result[] = '<!-- attention: currently the usage of g- and x-tags in this doc is not completely in line with the xliff:doc-spec. This will change, when resources for this issue will be assigned -->';
            $this->result[] = '<!-- attention: regarding internal tags the source and the target-content are in the same format as the contents of the original source formats would have been. For SDLXLIFF this means: No mqm-Tags; Terms marked with <mrk type="x-term-...">-Tags; Internal Tags marked with g- and x-tags; For CSV this means: all content is exported as it comes from CSV, this can result in invalid XLIFF! -->';
            $this->result[] = '<!-- attention: MQM Tags are not exported at all! -->';
        }
    }
    
    /**
     * process and convert all segments to xliff
     * @param string $filename
     * @param array $segmentsOfFile
     */
    protected function processAllSegments($filename, array $segmentsOfFile) {
        if(empty($segmentsOfFile)) {
            return;
        }
        $export = ZfExtended_Factory::get('editor_Models_Export');
        /* @var $export editor_Models_Export */
        $this->exportParser = $export->getFileParserForXmlList($this->task, $filename);
        $file = '<file original="%1$s" source-language="%2$s" target-language="%3$s" xml:space="preserve" datatype="x-translate5">';
        $this->result[] = sprintf($file, htmlspecialchars($filename), $this->data['sourceLang'], $this->data['targetLang']);
        $this->result[] = '<body>';
        
        $fileMapKey = $this->prepareTagsStorageInHeader();
        foreach($segmentsOfFile as $segment) {
            $this->processSegmentsOfFile($segment);
        }
        $this->storeTagsInHeader($fileMapKey);
        
        $this->result[] = '</body>';
        $this->result[] = '</file>';
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
     * @param integer $fileMapKey the index of the filemap placeholder in the result array 
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
    
    /**
     * process and convert the segments of one file to xliff
     * @param array $segment
     */
    protected function processSegmentsOfFile($segment) {
        $segStart = '<trans-unit id="%1$s" translate5:autostateId="%2$s" translate5:autostateText="%3$s"%4$s>';
        if(isset($this->data['autostates'][$segment['autoStateId']])) {
            $autoStateText =  $this->data['autostates'][$segment['autoStateId']];
        }
        else {
            $autoStateText = 'NOT_FOUND_'.$segment['autoStateId'];
        }
        
        $additionalAttributes = '';
        if(isset($segment['editable']) && !$segment['editable']){
            $additionalAttributes = ' translate="no"';
        }

        //segmentNrInTask is the segment id of the generated segment
        $this->result[] = "\n".sprintf($segStart, $segment['segmentNrInTask'], $segment['autoStateId'], $autoStateText, $additionalAttributes);
        
        /*
         * <!-- attention: regarding internal tags the source and the target-content are in the same format as the contents of the original source formats would have been. For SDLXLIFF this means: No mqm-Tags; Terms marked with <mrk type="x-term-...">-Tags; Internal Tags marked with g- and x-tags; For CSV this means: No internal tags except mqm-tags -->
         */
        
        //$this->result[] = '<segmentNr>'.$segment['segmentNrInTask'].'</segmentNr>';
        $this->result[] = '<source>'.$this->prepareText($segment[$this->data['firstSource']]).'</source>';

        $fields = $this->sfm->getFieldList();
        foreach($fields as $field) {
            $this->processSegmentField($field, $segment);
        }
        
        if(!empty($segment['comments']) && $this->options[self::CONFIG_ADD_COMMENTS]) {
            $this->processComment($segment);
        }
        
        if($this->options[self::CONFIG_ADD_STATE_QM]) {
            $this->processStateAndQm($segment);
        }
        
        //$this->result[] = '<autoStateId>'.$segment['autoStateId'].'</autoStateId>';
        //$this->result[] = '<matchRate>'.$segment['matchRate'].'</matchRate>';
        //$this->result[] = '<comments>'.$segment['comments'].'</comments>';
        $this->result[] = '</trans-unit>';
    }
    
    /**
     * process and convert the segments of one file to xliff
     * @param Zend_Db_Table_Row $field
     * @param array $segment
     */
    protected function processSegmentField(Zend_Db_Table_Row $field, array $segment) {
        if($field->type == editor_Models_SegmentField::TYPE_SOURCE) {
            return; //handled before
        }
        if($field->type == editor_Models_SegmentField::TYPE_RELAIS && $this->data['relaisLang'] !== false) {
            $this->result[] = '<alt-trans dx:origin-shorttext="'.$field->label.'"><target xml:lang="'.$this->data['relaisLang'].'">'.$this->prepareText($segment[$field->name]).'</target></alt-trans>';
            return;
        }
        if($field->type != editor_Models_SegmentField::TYPE_TARGET) {
            return;
        }
        
        
        $lang = $this->data['targetLang'];
        if($this->data['firstTarget'] == $field->name) {
            $altTransName = $field->name;
            $matchRate = number_format($segment['matchRate'], 1, '.', '');
            $targetEdit = $this->prepareText($segment[$this->sfm->getEditIndex($this->data['firstTarget'])]);
            if(empty($this->enabledNamespaces['dx'])){
                $targetPrefix = '<target state="%1$s">';
            }
            else {
                $targetPrefix = '<target state="%1$s" dx:match-quality="'.$matchRate.'">';
            }
            if(empty($targetEdit)){
                $state = 'needs-translation';
            }
            else {
                $state = 'translated';
            }
            $this->result[] = sprintf($targetPrefix, $state).$targetEdit.'</target>';
            //add previous version of target as alt trans
            if($this->options[self::CONFIG_ADD_PREVIOUS_VERSION]) {
                //add targetOriginal
                $this->addAltTransToResult($this->prepareText($segment[$field->name]), $lang, $altTransName, 'previous-version');
            }
        }
        else {
            //add alternatives
            $altTransName = $field->label;
            $targetEdit = $this->prepareText($segment[$this->sfm->getEditIndex($field->name)]);
            if($this->options[self::CONFIG_ADD_ALTERNATIVES]) {
                $this->addAltTransToResult($targetEdit, $lang, $altTransName);
            }
        }
        if($this->options[self::CONFIG_INCLUDE_DIFF]){
            //compare targetEdit and targetOriginal
            $this->addDiffToResult($targetEdit, $this->prepareText($segment[$field->name]), $altTransName, $segment);
        }
    }
    
    protected function addAltTransToResult($targetText, $lang, $label, $type = null) {
        $alttranstype = empty($type) ? '' : ' alttranstype="'.$type.'"';
        $this->result[] = '<alt-trans dx:origin-shorttext="'.$label.'"'.$alttranstype.'>';
        $this->result[] = '<target xml:lang="'.$lang.'">'.$targetText.'</target></alt-trans>';
    }
    
    protected function addDiffToResult($targetEdit, $targetOriginal, $label, $segment) {
        $diffResult = $this->differ->diffSegment($targetOriginal, $targetEdit, $segment['timestamp'], $segment['userName']);
        $this->addAltTransToResult($diffResult, $this->data['targetLang'], $label.'-diff', 'reference');
    }
    
    /**
     * process and convert the segment comments
     * @param array $segment
     */
    protected function processComment(array $segment) {
        $comments = $this->comment->loadBySegmentAndTaskPlain((integer)$segment['id'], $this->task->getTaskGuid());
        $note = '<dx:note dx:modified-by="%1$s" dx:annotates="target" dx:modified-at="%2$s">%3$s</dx:note>';
        foreach($comments as $comment) {
            $modified = new DateTime($comment['modified']);
            //if the +0200 at the end makes trouble use the following
            //gmdate('Y-m-d\TH:i:s\Z', $modified->getTimestamp());
            $modified = $modified->format($modified::ATOM);
            $this->result[] = sprintf($note, htmlspecialchars($comment['userName']), $modified, htmlspecialchars($comment['comment']));
        }
    }
    
    /**
     * process and convert the segment states and QM states
     * @param array $segment
     */
    protected function processStateAndQm(array $segment) {
        $this->result[] = '<state stateid="'.$segment['stateId'].'">'.$this->segmentUtility->convertStateId($segment['stateId']).'</state>';
        $qms = $this->segmentUtility->convertQmIds($segment['qmId']);
        if(empty($qms)) {
            $this->result[] = '<dx:qa-hits></dx:qa-hits>';
        }
        else {
            $this->result[] = '<dx:qa-hits>';
            $qmXml = '<dx:qa-hit dx:qa-origin="target" dx:qa-code="%1$s" dx:qa-shorttext="%2$s" />';
            foreach ($qms as $qmid => $qm) {
                $this->result[] = sprintf($qmXml, $qmid, $qm);
            }
            $this->result[] = '</dx:qa-hits>';
        }
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
    
    /**
     * prepares segment text parts for xml
     * @param string $text
     * @return string
     */
    protected function prepareText($text) {
        if($this->options[self::CONFIG_PLAIN_INTERNAL_TAGS]) {
            $text = $this->handleTerminology($text, true);
            return $this->exportParser->exportSingleSegmentContent($text);
        }
        
        //if plain internal tags are disabled:
        // 1. toXliff converts the internal tags to xliff g,bx,ex and x tags
        // 2. remove MQM tags
        //TODO MQM tags are just removed and not supported by our XLIFF exporter so far!
        $text = $this->taghelperInternal->toXliffPaired($text, true, $this->tagMap, $this->tagId);
        $text = $this->handleTerminology($text, false); //internaltag replacment not needed, since already converted
        $text = $this->taghelperMqm->remove($text);
        return $text;
    }
    
    /**
     */
    protected function handleTerminology($text, $protectInternalTags) {
        if(!$this->options[self::CONFIG_ADD_TERMINOLOGY]){
            return $this->taghelperTerm->remove($text);
        }
        $termStatus = editor_Models_Term::getAllStatus();
        $transStatus = [
                editor_Models_Term::TRANSSTAT_FOUND => 'found',
                editor_Models_Term::TRANSSTAT_NOT_FOUND => 'notfound',
                editor_Models_Term::TRANSSTAT_NOT_DEFINED => 'undefined',
        ];
        return $this->taghelperTerm->replace($text, function($wholeMatch, $tbxId, $classes) use ($termStatus, $transStatus) {
            $status = '';
            $translation = '';
            foreach($classes as $class) {
                if($class == editor_Models_Term::CSS_TERM_IDENTIFIER) {
                    continue;
                }
                if(in_array($class, $termStatus)) {
                    $status = $class;
                    continue;
                }
                if(!empty($transStatus[$class])) {
                    $translation = ' translate5:translated="'.$transStatus[$class].'"';
                }
            }
            return '<mrk mtype="term" translate5:status="'.$status.'"'.$translation.'>';
        }, '</mrk>', $protectInternalTags);
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
    }
}