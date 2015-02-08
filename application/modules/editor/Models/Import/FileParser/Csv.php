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

/** #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */

/**
 * Fileparsing for Csv-files
 *
 * - parent class should ensure, that file already is utf-8-encoded
 *
 */
class editor_Models_Import_FileParser_Csv extends editor_Models_Import_FileParser {
    /**
     * string "source" as defined in application.ini column definition
     * @var string
     */
    const CONFIG_COLUMN_SOURCE = 'source';
    /**
     * string "mid" as defined in application.ini column definition
     * @var string
     */
    const CONFIG_COLUMN_MID = 'mid';
    
    /**
     *
     * @var type array order of the columns - which column is mid, source and target
     */
    protected $colOrder = array();
    /**
     *
     * @var type string line break chars of the csv file
     */
    protected $break;
    /**
     * @var string 
     */
    protected $_delimiter;
    /**
     * @var string 
     */
    protected $_enclosure;
    
    
    
    public function __construct(string $path, string $fileName, integer $fileId, boolean $edit100PercentMatches, editor_Models_Languages $sourceLang, editor_Models_Languages $targetLang, editor_Models_Task $task) {
        ini_set('auto_detect_line_endings', true);//to tell php to respect mac-lineendings
        parent::__construct($path, $fileName, $fileId, $edit100PercentMatches, $sourceLang, $targetLang, $task);
        $config = Zend_Registry::get('config');
        $this->_delimiter = $config->runtimeOptions->import->csv->delimiter;
        $this->_enclosure = $config->runtimeOptions->import->csv->enclosure;
    }
    
    /**
     * returns the csv content of one line, or false if line was empty / or eof reached
     * @param handle $handle
     * @return array $line or boolean false if nothing found in line
     */
    protected function prepareLine(SplTempFileObject $csv){
        $line = $csv->fgetcsv($this->_delimiter, $this->_enclosure);
        //empty lines or eof trigger false
        if(count($line) === 1 && empty($line[0]) || is_null($line)) {
            return false;
        }
        
        if($line === false){
            trigger_error('Error on parsing a line of CSV. Current line is: '.$csv->current()
                            .'. Error could also be in previous line!', E_USER_ERROR);
        }
        if(!isset($line[2])){
            trigger_error('In the line "'.
                implode($this->_enclosure.$this->_delimiter.$this->_enclosure,$line).
                '" there is no third column.',
                E_USER_ERROR);
        }
        return $line;
    }
    /**
     * does the FileParsing
     *
     * - split file into lines, regardless of linebreak-char
     * - skip first line (because of column headers)
     * - integrate extractSegment
     * - join file again for saving of skeleton file"
     *
     */
    protected function parse(){
        //@todo is the following link a improvement? http://stackoverflow.com/questions/11066857/detect-eol-type-using-php
        if(preg_match('"\r\n"', $this->_origFile)){
            $this->break = "\r\n";
        }
        elseif(preg_match('"\n"', $this->_origFile)){
            $this->break = "\n";
        }
        elseif(preg_match('"\r"', $this->_origFile)){
            $this->break = "\r";
        }
        else{
            trigger_error('no linebreak found in CSV: '.$this->_fileName,E_USER_ERROR);
        }
        
        //for this ini set see php docu: http://de2.php.net/manual/en/filesystem.configuration.php#ini.auto-detect-line-endings
        ini_set("auto_detect_line_endings", true);
        $csv = new SplTempFileObject();
        //we skip empty lines in the CSV files
        $csv->fwrite($this->_origFile);
        $csv->rewind();
        unset ($this->_origFile); //save memory, is not needed anymore.
        
        //check header and column order
        $config = Zend_Registry::get('config');
        $csvSettings = $config->runtimeOptions->import->csv->fields->toArray();
        //$csvSettings quelle => source, mid => mid
        $header = $this->prepareLine($csv);
        if($header === false) {
            trigger_error('no header column found in CSV: '.$this->_fileName,E_USER_ERROR);
        }
        $skel = array($this->str_putcsv($header, $this->_delimiter, $this->_enclosure, $this->break));
        
        $missing = array_diff($csvSettings, $header);
        if(!empty($missing)) {
            trigger_error('in application.ini configured column-header(s) '.
                            join(';', $missing).' not found in CSV: '.$this->_fileName,E_USER_ERROR);
        }
        if(count($header) < 3) {
            trigger_error('source and mid given but no more data columns found in CSV: '.$this->_fileName,E_USER_ERROR);
        }
        $i=0;
        $csvSettings = array_flip($csvSettings);
        $foundHeader = array();
        foreach($header as $colHead) {
            $type = false;

            //we ignore empty colHeads on import, so we have to track their col position
            if(empty($colHead)) {
                $i++; //increase the col index, but do nothing else!
                continue;
            }

            //get type and editable state of the field
            if(empty($csvSettings[$colHead])){
                //if no column is configured, its a target
                $type = editor_Models_SegmentField::TYPE_TARGET;
                $editable = true;
            } elseif($csvSettings[$colHead] == self::CONFIG_COLUMN_SOURCE) {
                $type = editor_Models_SegmentField::TYPE_SOURCE;
                $editable = (boolean)$this->task->getEnableSourceEditing();
            } elseif($csvSettings[$colHead] == self::CONFIG_COLUMN_MID) {
                $this->colOrder[self::CONFIG_COLUMN_MID] = $i++;
                continue;
            }
            
            //we ensure that columns with the same name in one csv file are made unique
            // this is needed by addfield to map fields between different files 
            // if mid exists multiple times in the header, only the last one is used. 
            if(empty($foundHeader[$colHead])) {
                $foundHeader[$colHead] = 1;
            }
            else {
                $colHead .= '_'.($foundHeader[$colHead]++);
            }
            $name = $this->segmentFieldManager->addField($colHead, $type, $editable);
            
            $this->colOrder[$name] = $i++;
        }
        while(!$csv->eof()){
            $line = $this->prepareLine($csv);
            //ignore empty lines:
            if($line === false) {
                $skel[] = $this->break;
                continue;
            }
            $extracted = $this->extractSegment($line);
            $skel[] = $this->str_putcsv($extracted, $this->_delimiter, $this->_enclosure, $this->break);
        }
        $this->_skeletonFile = join('', $skel);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::initDefaultSegmentFields()
     */
    protected function initDefaultSegmentFields() {
        //CSV adds all fields automatically, so omit default fields here.
    }
    
    /**
     * extracts the segment and saves them to db
     *
     * @param mixed $transUnit
     * @return array $transUnit
     */
    protected function extractSegment($lineArr){
        $this->segmentData = array();
        foreach($this->colOrder as $name => $idx) {
            if($name == self::CONFIG_COLUMN_MID) {
                $this->setMid($lineArr[$idx]);
                continue;
            }
            $field = $this->segmentFieldManager->getByName($name);
            $isSource = $field->type == editor_Models_SegmentField::TYPE_SOURCE;
            if(empty($lineArr[$idx])) {
                $original = '';
            }
            else {
                $original = $lineArr[$idx];
            }
            $this->segmentData[$name] = array(
                 'original' => $this->parseSegment($original, $isSource),
                 'originalMd5' => md5($original)
            );
        }

        $this->setSegmentAttribs($lineArr); //<< hier kann crap übergeben werden
        $segmentId = $this->setAndSaveSegmentValues();
        foreach($this->colOrder as $name => $idx) {
            $field = $this->segmentFieldManager->getByName($name);
            if($field && $field->editable) {
                $lineArr[$idx] = $this->getFieldPlaceholder($segmentId, $name);
            }
        }
        return $lineArr;
    }
    /**
     * extracts tags and converts terms of the segment 
     * 
     * - for csv currently does nothing, since tags and terms are not supported
     *
     * @param mixed $segment
     * @param boolean isSource
     * @return string $segment
     */
    protected function parseSegment($segment,$isSource){
        // TRANSLATE-411: special segment parsing is injected here, replaces normal csv parsing..
        $segment =  $this->parseSegmentTagged($segment,$isSource);
        // END TRANSLATE-411: special...
        
        $count = 0;
        $segment = $this->parseSegmentProtectWhitespace($segment, $count);
        
        if($count == 0) {
            return $segment;
        }
        
        //In CSV we have to directly replace our whitespace tags with their HTML replacement
        $search = array(
          '#<hardReturn />#',
          '#<softReturn />#',
          '#<macReturn />#',
          '#<space ts="[^"]*"/>#',
        );
        
        //set data needed by $this->whitespaceTagReplacer
        $this->shortTagIdent = 1;
        $this->_segment = $segment;
        $segment = preg_replace_callback($search, array($this,'whitespaceTagReplacer'), $segment);
        
        return $segment;
    }
    
    private function parseSegmentTagged($segment,$isSource) {
        
        if (strpos($segment, '<')=== false) {
            return $segment;
        }
        
        try {
            $tempXml = qp('<?xml version="1.0"?><segment>'.$segment.'</segment>', NULL, array('format_output' => false));
        }
        catch (Exception $e) {
            error_log(__CLASS__.' -> '.__FUNCTION__.'; TODO: replace all tags as single tags !!!'.$e->getMessage());
            return strip_tags($segment);
        }
        
        // mark single- or paired-tags and fill _tagMapping array
        $tagCounter = 1;
        foreach ($tempXml->find('segment *') as $element) {
            $this->_tagMapping[$tagCounter] = array();
            
            $tagType = 'singleTag';
            $tagText = '<'.$element->tag().'>';
            $this->_tagMapping[$tagCounter]['name'] = $element->tag();
            $this->_tagMapping[$tagCounter]['text'] = htmlentities($tagText, ENT_QUOTES, 'utf-8');
            $this->_tagMapping[$tagCounter]['imgText'] = $tagText;
            
            if (!empty($element->innerXml())) {
                $tagType = 'pairedTag';
                $eptText = '</'.$element->tag().'>';
                $this->_tagMapping[$tagCounter]['eptName'] = $element->tag();
                $this->_tagMapping[$tagCounter]['eptText'] = htmlentities($eptText, ENT_QUOTES, 'utf-8');
                $this->_tagMapping[$tagCounter]['imgEptText'] = $eptText;
            }
            $element->wrap('<'.$tagType.'_'.$tagCounter++.' data-tagname="'.$element->tag().'" />');
        }
        $tempReturn = $tempXml->find('segment')->innerXml();
        
        // replace single-tags
        $tempReturn = $this->parseReplaceSingleTags($tempReturn);
        // replace left-(opening-)tags
        $tempReturn = $this->parseReplaceLeftTags($tempReturn);
        // replace right-(closing-)-tags
        $tempReturn = $this->parseReplaceRightTags($tempReturn);
        
        return $tempReturn;
    }
    
    /**
     * Replace all special marked single-tags in $text.
     * 
     * @param string $text
     * @return string
     */
    private function parseReplaceSingleTags($text) {
        if (preg_match_all('/<singleTag_([0-9]+).*?data-tagname="([^"]*)"[^>]*>(<[^>]+>)<\/singleTag_[0-9]+>/ims', $text, $matches, PREG_SET_ORDER)) {
            
            foreach ($matches as $match) {
                $tagId = $match[1];
                $tagName = $match[2];
                $tag = $match[3];
                
                $this->_tagMapping[$tagId]['text'] = htmlentities($tag, ENT_QUOTES, 'utf-8');
                $this->_tagMapping[$tagId]['imgText'] = $tag;
                $fileNameHash = md5($this->_tagMapping[$tagId]['imgText']);
                
                $p = $this->getTagParams($tag, $tagId, $tagId, $fileNameHash, $this->encodeTagsForDisplay($tag));
                $replace = $this->_singleTag->getHtmlTag($p);
                $text = str_replace($match[0], $replace, $text);
                
                $this->_singleTag->createAndSaveIfNotExists($this->_tagMapping[$tagId]['imgText'], $fileNameHash);
            }
        }
        return $text;
    }
    
    /**
     * Replace all special marked left-tags in $text.
     * 
     * @param string $text
     * @return string
     */
    private function parseReplaceLeftTags($text) {
        if (preg_match_all('/<pairedTag_([0-9]+).*?data-tagname="([^"]*)"[^>]*>(<[^>]+>)/ims', $text, $matches, PREG_SET_ORDER)) {
            
            foreach ($matches as $match) {
                $tagId = $match[1];
                $tagName = $match[2];
                $tag = $match[3];
                
                $this->_tagMapping[$tagId]['text'] = htmlentities($tag, ENT_QUOTES, 'utf-8');
                $this->_tagMapping[$tagId]['imgText'] = $tag;
                $fileNameHash = md5($this->_tagMapping[$tagId]['imgText']);
                
                $p = $this->getTagParams($tag, $tagId, $tagId, $fileNameHash, $this->encodeTagsForDisplay($tag));
                $replace = $this->_leftTag->getHtmlTag($p);
                $text = str_replace($match[0], $replace, $text);
                
                $this->_leftTag->createAndSaveIfNotExists($this->_tagMapping[$tagId]['imgText'], $fileNameHash);
            }
        }
        return $text;
    }
    
    /**
     * Replace all special marked right-tags in $text.
     * 
     * @param string $text
     * @return string
     */
    private function parseReplaceRightTags($text) {
        if (preg_match_all('/(<[^>]+>)<\/pairedTag_([0-9]+)>/ims', $text, $matches, PREG_SET_ORDER)) {
            
            foreach ($matches as $match) {
                $tagId = $match[2];
                $tagName = $this->_tagMapping[$tagId]['eptName'];
                $tag = $match[1];
                $fileNameHash = md5($this->_tagMapping[$tagId]['imgEptText']);
                
                $p = $this->getTagParams($tag, $tagId, $tagId, $fileNameHash, $this->encodeTagsForDisplay($tag));
                $replace = $this->_rightTag->getHtmlTag($p);
                $text = str_replace($match[0], $replace, $text);
                
                $this->_rightTag->createAndSaveIfNotExists($this->_tagMapping[$tagId]['imgEptText'], $fileNameHash);
            }
        }
        return $text;
    }
    
    /**
     * Sets $this->_editSegment, $this->_matchRateSegment and $this->_autopropagated
     * and $this->_pretransSegment and $this->_autoStateId for the segment currently worked on
     * 
     * $this->_target and $this->_source must be defined already!
     * 
     * - as not defined for csv so far, $this->_matchRateSegment is always set to 
     *   0 and $this->_autopropagated to false
     * @param mixed transunit
     */
    protected function setSegmentAttribs($segment){
        $this->_matchRateSegment[$this->_mid] = 0;
        $this->_autopropagated[$this->_mid] = false;
        $this->_pretransSegment[$this->_mid] = false;
        $this->_editSegment[$this->_mid] = true;
        $this->_autoStateId[$this->_mid] = 0;
    }
    /**
     * counterpart of str_getcsv, because there is no php-func like that 
     * (function taken from php.net-comments)
     * @param array $array
     * @param string $delimiter
     * @param string $enclosure
     * @param string $terminator
     * @return string
     */
    protected function str_putcsv($array, $delimiter = ',', $enclosure = '"', $terminator = "\n") { 
        # First convert associative array to numeric indexed array 
        foreach ($array as $key => $value) $workArray[] = $value; 

        $returnString = '';                 # Initialize return string 
        $arraySize = count($workArray);     # Get size of array 
        
        for ($i=0; $i<$arraySize; $i++) { 
            # Nested array, process nest item 
            if (is_array($workArray[$i])) { 
                $returnString .= str_putcsv($workArray[$i], $delimiter, $enclosure, $terminator); 
            } else { 
                switch (gettype($workArray[$i])) { 
                    # Manually set some strings 
                    case "NULL":     $_spFormat = ''; break; 
                    case "boolean":  $_spFormat = ($workArray[$i] == true) ? 'true': 'false'; break; 
                    # Make sure sprintf has a good datatype to work with 
                    case "integer":  $_spFormat = '%i'; break; 
                    case "double":   $_spFormat = '%0.2f'; break; 
                    case "string":   $_spFormat = '%s'; break; 
                    # Unknown or invalid items for a csv - note: the datatype of array is already handled above, assuming the data is nested 
                    case "object": 
                    case "resource": 
                    default:         $_spFormat = ''; break; 
                } 
                $workArray[$i] = str_replace($enclosure, $enclosure.$enclosure, $workArray[$i]);
                $returnString .= sprintf('%2$s'.$_spFormat.'%2$s', $workArray[$i], $enclosure); 
                $returnString .= ($i < ($arraySize-1)) ? $delimiter : $terminator; 
            } 
        } 
        # Done the workload, return the output information 
        return $returnString; 
    } 
}
