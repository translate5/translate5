<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */
/**
 * Converts a List with Segments to XML
 */
class editor_Models_Converter_XmlSegmentList {
    protected static $issueCache = array();
    protected static $severityCache = array();
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * @var array
     */
    protected $stateFlags;
    
    /**
     * @var editor_Models_Export_FileParser_Sdlxliff
     */
    protected $exportParser;
    
    /**
     * @var array
     */
    protected $qualityFlags;
    
    /**
     * @var editor_Models_Comment
     */
    protected $comment;
    
    /**
     * @var boolean
     */
    protected $saveXmlToFile = true;
    
    public function __construct(){
        $session = new Zend_Session_Namespace();
        $this->stateFlags = $session->runtimeOptions->segments->stateFlags->toArray();
        $this->qualityFlags = $session->runtimeOptions->segments->qualityFlags->toArray();
        $this->saveXmlToFile = (boolean) $session->runtimeOptions->saveXmlToFile;
        
        $this->comment = ZfExtended_Factory::get('editor_Models_Comment');
    }
    
    /**
     * converts a list with segment data to xml
     * @param editor_Models_Task $task
     * @param array $segments
     */
    public function convert(editor_Models_Task $task, array $segments) {
        $this->task = $task;
        
        $this->exportParser = ZfExtended_Factory::get('editor_Models_Export_FileParser_Sdlxliff', array(0, false, $task));
        /* @var $lang editor_Models_Export_FileParser_Sdlxliff */
        
        $autoStates = ZfExtended_Factory::get('editor_Models_SegmentAutoStates');
        $refl = new ReflectionClass($autoStates);
        $consts = array_map('strtolower', array_flip($refl->getConstants()));
        
        $lang = ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $lang editor_Models_Languages */
        $lang->load($task->getSourceLang());
        $sourceLang = $lang->getRfc5646();
        $lang->load($task->getTargetLang());
        $targetLang = $lang->getRfc5646();
        
        $relaisLang = $task->getRelaisLang();
        if(empty($relaisLang)){
            $relaisLang = false;
        }
        else {
            $lang->load($task->getRelaisLang());
            $relaisLang = $lang->getRfc5646();
        }
        
        $sfm = editor_Models_SegmentFieldManager::getForTaskGuid($task->getTaskGuid());
        
        //For Xliff see https://code.google.com/p/interoperability-now/downloads/detail?name=XLIFFdoc%20Representation%20Guide%20v1.0.1.pdf&can=2&q=
        // and http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html
        $result = array('<?xml version="1.0" encoding="UTF-8"?>');
        $result[] = '<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:dx="http://www.interoperability-now.org/schema" xsi:schemaLocation="urn:oasis:names:tc:xliff:document:1.2 xliff-doc-1_0_extensions.xsd" dx:version="1.4" xmlns:translate5="http://www.translate5.net/" >';
        $result[] = '<!-- attention: currently the usage of g- and x-tags in this doc is not completely in line with the xliff:doc-spec. This will change, when resources for this issue will be assigned -->';
        $result[] = '<!-- attention: we know, that the structure of this document is not complete regarding xliff:doc-spec. This will change, when resources for this issue will be assigned -->';
        $result[] = '<!-- attention: regarding internal tags the source and the target-content are in the same format as the contents of the original source formats would have been. For SDLXLIFF this means: No mqm-Tags; Terms marked with <mrk type="x-term-...">-Tags; Internal Tags marked with g- and x-tags; For CSV this means: No internal tags except mqm-tags -->';
        $file = '<file original="%1$s" source-language="%2$s" target-language="%3$s" xml:space="preserve">';
        $result[] = sprintf($file, $task->getTaskName(), $sourceLang, $targetLang);
        $result[] = '<body>';
        
        //both getFirst calls throw an exception if no corresponding field is given, that should not be, so uncatched is OK.
        $firstTarget = $sfm->getFirstTargetName();
        $firstSource = $sfm->getFirstSourceName();
        
        foreach($segments as $segment) {
            $segStart = '<trans-unit id="%1$s" translate5:autostateId="%2$s" translate5:autostateText="%3$s">';
            if(isset($consts[$segment['autoStateId']])) {
                $autoStateText =  $consts[$segment['autoStateId']];
            }
            else {
                $autoStateText = 'NOT_FOUND_'.$segment['autoStateId'];
            }
            //@todo actually we are messing around on creating the xliff file. 
            //since the mid is only unique in the source file, and we are merging here 
            //several files together, we have to use the segmentNrInTask to achieve uniqueness,
            //instead using the desired MID
            $result[] = "\n".sprintf($segStart, $segment['segmentNrInTask'], $segment['autoStateId'], $autoStateText);
            
            /*
			<!-- attention: regarding internal tags the source and the target-content are in the same format as the contents of the original source formats would have been. For SDLXLIFF this means: No mqm-Tags; Terms marked with <mrk type="x-term-...">-Tags; Internal Tags marked with g- and x-tags; For CSV this means: No internal tags except mqm-tags -->
		*/
            
            
            //$result[] = '<segmentNr>'.$segment['segmentNrInTask'].'</segmentNr>';
            $result[] = '<source>'.$this->prepareText($segment[$firstSource]).'</source>';

            $fields = $sfm->getFieldList();
            foreach($fields as $field) {
                if($field->type == editor_Models_SegmentField::TYPE_SOURCE) {
                    continue; //handled above
                }
                if($field->type == editor_Models_SegmentField::TYPE_RELAIS && $relaisLang !== false) {
                    $result[] = '<alt-trans dx:origin-shorttext="'.$field->label.'"><target xml:lang="'.$relaisLang.'">'.$this->prepareText($segment[$field->name]).'</target></alt-trans>';
                    continue;
                }
                if($field->type != editor_Models_SegmentField::TYPE_TARGET) {
                    continue;
                }
                if($firstTarget == $field->name) {
                    $matchRate = number_format($segment['matchRate'], 1, '.', '');
                    $result[] = '<target dx:match-quality="'.$matchRate.'">'.$this->prepareText($segment[$sfm->getEditIndex($firstTarget)]).'</target>';
                    $result[] = '<alt-trans dx:origin-shorttext="'.$field->label.'" alttranstype="previous-version">';
                }
                else {
                    $result[] = '<alt-trans dx:origin-shorttext="'.$field->label.'">';
                }
                $result[] = '<target xml:lang="'.$targetLang.'">'.$this->prepareText($segment[$field->name]).'</target></alt-trans>';
            }
            
            if(!empty($segment['comments'])) {
                $comments = $this->comment->loadBySegmentAndTaskPlain((integer)$segment['id'], $task->getTaskGuid());
                $note = '<dx:note dx:modified-by="%1$s" dx:annotates="target" dx:modified-at="%2$s">%3$s</dx:note>';
                foreach($comments as $comment) {
                    $modified = new DateTime($comment['modified']);
                    //if the +0200 at the end makes trouble use the following
                    //gmdate('Y-m-d\TH:i:s\Z', $modified->getTimestamp());
                    $modified = $modified->format($modified::ISO8601);
                    $result[] = sprintf($note, htmlspecialchars($comment['userName']), $modified, htmlspecialchars($comment['comment']));
                }
            }
            
            $result[] = '<state stateid="'.$segment['stateId'].'">'.$this->convertStateId($segment['stateId']).'</state>';
            $qms = $this->convertQmIds($segment['qmId']);
            if(empty($qms)) {
                $result[] = '<dx:qa-hits></dx:qa-hits>';
            }
            else {
                $result[] = '<dx:qa-hits>';
                $qmXml = '<dx:qa-hit dx:qa-origin="target" dx:qa-code="%1$s" dx:qa-shorttext="%2$s" />';
                foreach ($qms as $qmid => $qm) {
                    $result[] = sprintf($qmXml, $qmid, $qm);
                }
                $result[] = '</dx:qa-hits>';
            }
            //$result[] = '<autoStateId>'.$segment['autoStateId'].'</autoStateId>';
            //$result[] = '<matchRate>'.$segment['matchRate'].'</matchRate>';
            //$result[] = '<comments>'.$segment['comments'].'</comments>';
            $result[] = '</trans-unit>';
        }
        $result[] = '</body>';
        $result[] = '</file>';
        $result[] = '</xliff>';
        
        $xml = join("\n", $result);
        if($this->saveXmlToFile) {
            $this->saveXmlToFile($xml);
        }
        return $xml;
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
    
    /**
     * break the qm img tags apart and the apply the $resultRenderer to manipulate the tag
     * $resultRenderer is a Closure and returns the converted string. 
     * It accepts the following parameters:
     *     string $tag = original img tag, 
     *     array $cls css classes, 
     *     integer $issueId the qm issue id, 
     *     string $issueName the untranslated qm issue name, 
     *     string $sev the untranslated sev textual id, 
     *     string $sevName the untranslated sev string, 
     *     string $comment the user comment
     * 
     * @param editor_Models_Task $task
     * @param string $text
     * @param Closure $resultRenderer does the final rendering of the qm tag, Parameters see above
     */
    public function convertQmSubsegments(editor_Models_Task $task, $text, Closure $resultRenderer) {
        $qmSubFlags = $task->getQmSubsegmentFlags();
        if(empty($qmSubFlags)){
            return $text;
        }
        $this->initCaches($task);
        $parts = preg_split('#(<img[^>]+>)#i', $text, null, PREG_SPLIT_DELIM_CAPTURE);
        $tg = $task->getTaskGuid();
        $severities = array_keys(get_object_vars(self::$severityCache[$tg]));
        foreach($parts as $idx => $part) {
            if(! ($idx % 2)) {
                continue;
            }
            //<img  class="critical qmflag ownttip open qmflag-1" data-seq="412" data-comment="" src="/modules/editor/images/imageTags/qmsubsegment-1-left.png" />
            preg_match('#<img[^>]+(class="([^"]*(qmflag-([0-9]+)[^"]*))"[^>]+data-comment="([^"]*)")|(data-comment="([^"]*)"[^>]+class="([^"]*(qmflag-([0-9]+)[^"]*))")[^>]*>#i', $part, $matches);
            $cnt = count($matches);
            if($cnt < 6) {
                $parts[$idx] = $part;
                continue;
            }
            if(count($matches) > 10) {
                $cls = explode(' ', $matches[8]);
                $issueId = $matches[10];
                $comment = $matches[7];
            }
            else {
                $cls = explode(' ', $matches[2]);
                $issueId = $matches[4];
                $comment = $matches[5];
            }
            
            $sev = array_intersect($severities, $cls);
            $sev = reset($sev);
            $sev = empty($sev) ? 'sevnotfound' : $sev;
            $sevName = (isset(self::$severityCache[$tg]->$sev) ? self::$severityCache[$tg]->$sev : '');
            $issueName = (isset(self::$issueCache[$tg][$issueId]) ? self::$issueCache[$tg][$issueId] : '');
            
            $parts[$idx] = $resultRenderer($part, $cls, $issueId, $issueName, $sev, $sevName, $comment);
        }
        return join('', $parts);
    }
    
    /**
     * returns the configured value to the given state id
     * @param string $stateId
     * @return string
     */
    public function convertStateId($stateId) {
        if(empty($stateId)) {
            return '';
        }
        if(isset($this->stateFlags[$stateId])){
            return $this->stateFlags[$stateId];
        }
        return 'Unknown State '.$stateId;
    }
    
    /**
     * converts the semicolon separated qmId string into an associative array
     * key => qmId
     * value => configured String in the config for this id
     * @param string $qmIds
     * @return array
     */
    public function convertQmIds($qmIds) {
        if(empty($qmIds)) {
            return array();
        }
        $qmIds = trim($qmIds, ';');
        $qmIds = explode(';', $qmIds);
        $result = array();
        foreach($qmIds as $qmId) {
            if(isset($this->qualityFlags[$qmId])){
                $result[$qmId] = $this->qualityFlags[$qmId];
                continue;
            }
            $result[$qmId] = 'Unknown Qm Id '.$qmId;
        }
        return $result;
    }
    
    /**
     * caches task issues and severities
     * @param editor_Models_Task $task
     */
    protected function initCaches(editor_Models_Task $task) {
        $tg = $task->getTaskGuid();
        if(empty(self::$issueCache[$tg])){
            self::$issueCache[$tg] = $task->getQmSubsegmentIssuesFlat();
        }
        if(empty(self::$severityCache[$tg])){
            self::$severityCache[$tg] = $task->getQmSubsegmentSeverities();
        }
    }
}