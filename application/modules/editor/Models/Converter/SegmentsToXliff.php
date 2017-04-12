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
 */
class editor_Models_Converter_SegmentsToXliff {
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
     * @var boolean
     */
    protected $saveXmlToFile = true;
    
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
    protected $differ;
    
    /**
     * @var boolean
     */
    protected $createDiffAltTrans;
    
    /**
     * @var editor_Models_Segment_Utility
     */
    protected $segmentUtility;
    
    public function __construct(){
        $config = Zend_Registry::get('config');
        $this->saveXmlToFile = (boolean) $config->runtimeOptions->editor->notification->saveXmlToFile;
        $this->createDiffAltTrans = (boolean) $config->runtimeOptions->editor->notification->includeDiff;
        if($this->createDiffAltTrans){
            $this->differ = ZfExtended_Factory::get('editor_Models_Export_DiffTagger_Sdlxliff');
        }
        
        $this->comment = ZfExtended_Factory::get('editor_Models_Comment');
        $this->segmentUtility = ZfExtended_Factory::get('editor_Models_Segment_Utility');
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
        if($this->saveXmlToFile) {
            $this->saveXmlToFile($xml);
        }
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
        if(empty($this->data['relaisLang'])){
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
        $headParams[] = 'xmlns:dx="http://www.interoperability-now.org/schema"';
        if($this->createDiffAltTrans) {
            $headParams[] = 'xmlns:sdl="http://sdl.com/FileTypes/SdlXliff/1.0"';
        }
        $headParams[] = 'xsi:schemaLocation="urn:oasis:names:tc:xliff:document:1.2 xliff-doc-1_0_extensions.xsd"';
        $headParams[] = 'dx:version="1.4"';
        $headParams[] = 'xmlns:translate5="http://www.translate5.net/"';
        $headParams[] = 'translate5:taskname="'.htmlspecialchars($this->task->getTaskName()).'"';
        $this->result[] = '<'.join(' ', $headParams).'>';
        
        $this->result[] = '<!-- attention: this format should be refactored to xliff 2.x. It will be, as soon as some one volunteers to do or donates funding for it -->';
        $this->result[] = '<!-- attention: currently the usage of g- and x-tags in this doc is not completely in line with the xliff:doc-spec. This will change, when resources for this issue will be assigned -->';
        $this->result[] = '<!-- attention: regarding internal tags the source and the target-content are in the same format as the contents of the original source formats would have been. For SDLXLIFF this means: No mqm-Tags; Terms marked with <mrk type="x-term-...">-Tags; Internal Tags marked with g- and x-tags; For CSV this means: all content is exported as it comes from CSV, this can result in invalid XLIFF! -->';
        $this->result[] = '<!-- attention: MQM Tags are not exported at all! -->';
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
        $file = '<file original="%1$s" source-language="%2$s" target-language="%3$s" xml:space="preserve">';
        $this->result[] = sprintf($file, htmlspecialchars($filename), $this->data['sourceLang'], $this->data['targetLang']);
        $this->result[] = '<body>';
        
        foreach($segmentsOfFile as $segment) {
            $this->processSegmentsOfFile($segment);
        }
        
        $this->result[] = '</body>';
        $this->result[] = '</file>';
    }
    
    /**
     * process and convert the segments of one file to xliff
     * @param array $segment
     */
    protected function processSegmentsOfFile($segment) {
        $segStart = '<trans-unit id="%1$s" translate5:autostateId="%2$s" translate5:autostateText="%3$s">';
        if(isset($this->data['autostates'][$segment['autoStateId']])) {
            $autoStateText =  $this->data['autostates'][$segment['autoStateId']];
        }
        else {
            $autoStateText = 'NOT_FOUND_'.$segment['autoStateId'];
        }
        //@todo actually we are messing around on creating the xliff file. 
        //since the mid is only unique in the source file, and we are merging here 
        //several files together, we have to use the segmentNrInTask to achieve uniqueness,
        //instead using the desired MID
        $this->result[] = "\n".sprintf($segStart, $segment['segmentNrInTask'], $segment['autoStateId'], $autoStateText);
        
        /*
         * <!-- attention: regarding internal tags the source and the target-content are in the same format as the contents of the original source formats would have been. For SDLXLIFF this means: No mqm-Tags; Terms marked with <mrk type="x-term-...">-Tags; Internal Tags marked with g- and x-tags; For CSV this means: No internal tags except mqm-tags -->
         */
        
        //$this->result[] = '<segmentNr>'.$segment['segmentNrInTask'].'</segmentNr>';
        $this->result[] = '<source>'.$this->prepareText($segment[$this->data['firstSource']]).'</source>';

        $fields = $this->sfm->getFieldList();
        foreach($fields as $field) {
            $this->processSegmentField($field, $segment);
        }
        
        if(!empty($segment['comments'])) {
            $this->processComment($segment);
        }
        
        $this->processStateAndQm($segment);
        
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
            $matchRate = number_format($segment['matchRate'], 1, '.', '');
            $targetEdit = $this->prepareText($segment[$this->sfm->getEditIndex($this->data['firstTarget'])]);
            $this->result[] = '<target dx:match-quality="'.$matchRate.'">'.$targetEdit.'</target>';
            $targetOriginal = $this->prepareText($segment[$field->name]);
            //add previous version of target as alt trans
            $this->addAltTransToResult($targetOriginal, $lang, $field->label, 'previous-version');
        }
        else {
            //add alternatives
            $targetEdit = $this->prepareText($segment[$this->sfm->getEditIndex($field->name)]);
            $this->addAltTransToResult($targetEdit, $lang, $field->label);
            if($this->createDiffAltTrans){
                $targetOriginal = $this->prepareText($segment[$field->name]);
            }
        }
        $this->addDiffToResult($targetEdit, $targetOriginal, $field, $segment);
    }
    
    protected function addAltTransToResult($targetText, $lang, $label, $type = null) {
        $alttranstype = empty($type) ? '' : ' alttranstype="'.$type.'"';
        $this->result[] = '<alt-trans dx:origin-shorttext="'.$label.'"'.$alttranstype.'>';
        $this->result[] = '<target xml:lang="'.$lang.'">'.$targetText.'</target></alt-trans>';
    }
    
    protected function addDiffToResult($targetEdit, $targetOriginal, Zend_Db_Table_Row $field, $segment) {
        if(!$this->createDiffAltTrans){
            return;
        }
        $diffResult = $this->differ->diffSegment($targetOriginal, $targetEdit, $segment['timestamp'], $segment['userName']);
        $this->addAltTransToResult($diffResult, $this->data['targetLang'], $field->label.'-diff', 'reference');
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
    
    protected function saveXmlToFile($xml) {
        $path = $this->task->getAbsoluteTaskDataPath();
        if(!is_dir($path) || !is_writeable($path)) {
            error_log('cant write changes.xliff file to path: '.$path);
            return;
        }
        $suffix = '.xliff';
        $filename = 'changes-'.date('Y-m-d\TH:i:s');
        $i = 0;
        $outFile = $path.DIRECTORY_SEPARATOR.$filename.$suffix;
        while(file_exists($outFile)) {
            $outFile = $path.DIRECTORY_SEPARATOR.$filename.'-'.($i++).$suffix;
        }
        if(file_put_contents($outFile, $xml) == 0) {
            error_log('Error on writing XML File: '.$outFile);
        }
    }
    
    /**
     * prepares segment text parts for xml
     * @param string $text
     * @return string
     */
    protected function prepareText($text) {
        return $this->exportParser->exportSingleSegmentContent($text);
    }
}