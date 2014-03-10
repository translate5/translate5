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

/* * #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 * 

  /**
 * Enthält Methoden zum Fileparsing für den Export
 *
 */

abstract class editor_Models_Export_FileParser {
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
     * @var editor_Models_Db_Segments aktuell bearbeitetes Segment
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
     *
     */
    protected $_task;
    
    public function __construct(integer $fileId,boolean $diff,editor_Models_Task $task) {
        if(is_null($this->_classNameDifftagger)){
            throw new Zend_Exception('$this->_classNameDifftagger muss in der Child-Klasse definiert sein.');
        }
        $this->_fileId = $fileId;
        $this->_diffTagger = ZfExtended_Factory::get($this->_classNameDifftagger);
        $this->_diff = $diff;
        $this->_task = $task;
    }

    /**
     * Gibt eine zu exportierende Datei bereits korrekt für den Export geparsed zurück
     * 
     * @return string file
     */
    public function getFile() {
        $this->getSkeleton();
        $this->parse();
        $this->convertEncoding();
        return $this->_exportFile;
    }

    /**
     * übernimmt das eigentliche FileParsing
     *
     * - setzt an Stelle von <lekTargetSeg... wieder das überarbeitete Targetsegment ein
     * - befüllt $this->_exportFile
     */
    protected function parse() {
        $file = preg_split('#<lekTargetSeg([^>]+)/>#', $this->_skeletonFile, null, PREG_SPLIT_DELIM_CAPTURE);

        //reusable exception creation
        $exception = function($val) {
            $e  = 'Error in Export-Fileparsing. instead of a id="INT" and a optional ';
            $e .= 'field="STRING" attribute the following content was extracted: ' . $val;
            return new Zend_Exception($e);
        };
        
        $count = count($file) - 1;
        for ($i = 1; $i < $count;) {
            $file[$i] = $this->preProcessReplacement($file[$i]);
            if (!preg_match('#^\s*id="([^"]+)"\s*(field="([^"]+)"\s*)?$#', $file[$i], $matches)) {
                throw $exception($file[$i]);
            }
          
            //check $matches[1] for integer (segmentId) if empty throw an exception
            settype($matches[1], 'int');
            if(empty($matches[1])) {
                throw $exception($file[$i]);
            }
          
            //alternate column is optional, use target as default
            if(isset($matches[3])) {
                $field = $matches[3];
            }
            else {
              $field = editor_Models_SegmentField::TYPE_TARGET;
            }
          
            //$file[$i] = 'replaced: '.$matches[1].' # '.$col;
            $file[$i] = $this->getSegmentContent($matches[1], $field);
            $i = $i + 2;
        }
        $this->_exportFile = implode('', $file);
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
     * returns the segment content for the given segmentId and field. Adds optional diff markup, and handles tags.
     * @param integer $segmentId
     * @param string $field fieldname to get the content from
     * @return string
     */
    protected function getSegmentContent($segmentId, $field) {
        $config = Zend_Registry::get('config');
        $removeTaggingOnExport = $config->runtimeOptions->termTagger->removeTaggingOnExport;
        $this->_segmentEntity = $segment = $this->getSegment($segmentId);
        
        $edited = (string) $segment->getFieldEdited($field);
        
        $removeTermTags = $this->_diff ? $removeTaggingOnExport->diffExport : $removeTaggingOnExport->normalExport;
        
        $edited = $this->recreateTermTags($edited,(boolean)$removeTermTags);
        $edited = $this->parseSegment($edited);
        
        if(!$this->_diff){
            return $edited;
        }
        
        $original = (string) $segment->getFieldOriginal($field);
        $original = $this->recreateTermTags($original);
        $original = $this->parseSegment($original);
        
        return $this->_diffTagger->diffSegment(
                $original, 
                $edited,
                $segment->getTimestamp(),
                $segment->getUserName());
    }
    
    /**
     * loads the segment to the given Id, caches a limited count of segments internally 
     * to prevent loading again while switching between fields
     * @param integer $segmentId
     * @return editor_Models_Segment
     */
    protected function getSegment($segmentId){
        if(isset($this->segmentCache[$segmentId])) {
            return $this->segmentCache[$segmentId];
        }
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $this->segmentCache[$segmentId] = $segment->load($segmentId);
        //we keep a max of 5 segments, this should be enough
        if($this->segmentCache > 5) {
            array_shift($this->segmentCache);
        }
        return $segment;
    }
    
    /**
     * converts the QM-Subsegment-Tags to xliff-format
     * 
     * @param string $segment
     * @return string
     */
    protected function convertQmTags2XliffFormat($segment){
        $flags = $this->_task->getQmSubsegmentFlags();
        if(empty($flags)){
            return $segment;
        }
        $split = preg_split('"(<img[^>]+class=\"[^\"]*qmflag[^\"]*\"[^>]*>)"', $segment, NULL, PREG_SPLIT_DELIM_CAPTURE);
        $count = count($split);
        if($count==1) return $segment;
        $i = 1;
        
        $check = function($type,$content,$input,$empty = true){
            if($empty && $content == ''){
                trigger_error($type.' had been emtpy when extracting from qm-subsegment-tag.',E_USER_ERROR);
            }
            if($content == $input){
                #trigger_error($type.' could not be extracted from qm-subsegment-tag.',E_USER_ERROR);
            }
        };
        
        $extract = function($type,$numeric = false,$empty = true)use (&$split,&$i,$check){
            $a = '[^\"]*';
            if($numeric)$a = '\d+';
            $content = preg_replace('".*'.$type.'=\"('.$a.')\".*"', '\\1', $split[$i]);
            $check($type,$content,$split[$i],$empty);
            return $content;
        };
        
        $issues = $this->_task->getQmSubsegmentIssuesFlat();
        $user = $this->_segmentEntity->getUserName();
        
        for (; $i < $count; $i=$i+2) {//the uneven numbers are the tags
            $id = $extract('data-seq',true);
            $class = $extract('class');
            $open = (boolean)preg_match('"^(open .*)|(.* open)|(.* open .*)$"', $class);
            
            if($open){
                $comment = $extract('data-comment',false,false);
                $severity = preg_replace('"^\s*([^ ]+) .*$"', '\\1', $class);
                $check('severity',$severity,$class);
                $issueId = preg_replace('"^.*qmflag-(\d+).*$"', '\\1', $class);
                $check('issueId',$issueId,$class);
                $issue = $issues[$issueId];
                
                $split[$i] = '<mqm:startIssue type="'.$issue.'" severity="'.
                        $severity.'" note="'.$comment.'" agent="'.$user.
                        '" id="'.$id.'"/>';
            }
            else{
                $split[$i] = '<mqm:endIssue id="'.$id.'"/>';
            }
        }
        return implode('', $split);
    }

    /**
     * befüllt $this->_skeletonFile
     */
    protected function getSkeleton() {
        $skel = ZfExtended_Factory::get('editor_Models_Skeletonfile');
        $skel->loadRow('fileId = ?', $this->_fileId);
        $this->_skeletonFile = $skel->getFile();
    }

    /**
     * Rekonstruiert in einem Segment die ursprüngliche Form der enthaltenen Tags
     *
     * @param string $segment
     * @return string $segment 
     */
    abstract protected function parseSegment($segment);
    
    /**
     * Stellt die Term Auszeichnungen wieder her
     * 
     * @param string $segment
     * @param boolean $removeTermTags, default = false
     * @return string $segment
     */
    abstract protected function recreateTermTags($segment, $removeTermTags=true);
    
    /**
     * - converts $this->_exportFile back to the original encoding registered in the LEK_files
     */
    protected function convertEncoding(){
        $file = ZfExtended_Factory::get('editor_Models_File');
        $file->load($this->_fileId);
        $enc = $file->getEncoding();
        if(is_null($enc) || $enc === '' || strtolower($enc) === 'utf-8')return;
        $this->_exportFile = iconv('utf-8', $enc, $this->_exportFile);
    }
    
    /**
     * Exports a single segment content 
     * @param string $segment
     * @return string
     */
    public function exportSingleSegmentContent($segment) {
        return $this->recreateTermTags($this->parseSegment($segment));
    }
}
