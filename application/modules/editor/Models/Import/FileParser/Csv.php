<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

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
     * @var type array order of the columns - which column is mid, source and target
     */
    protected $colOrder = array();
    
    /**
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
    
    /**
     * @var integer
     */
    protected $segmentTagCounter = 1;
    
    /**
     * @var boolean
     */
    protected $tagProtection = false;
    
    /**
     * Array with internal tags.
     * This tags will not be manipulated by tagProtection
     */
    protected $internalTags = array('hardReturn',
                    'softReturn',
                    'macReturn',
                    'space',
                    'regex'
    );
    
    /**
     * @var array
     */
    protected $replaceRegularExpressions = array();
    
    
    
    public function __construct(string $path, string $fileName, integer $fileId, boolean $edit100PercentMatches, boolean $lockLocked, editor_Models_Languages $sourceLang, editor_Models_Languages $targetLang, editor_Models_Task $task) {
        ini_set('auto_detect_line_endings', true);//to tell php to respect mac-lineendings
        parent::__construct($path, $fileName, $fileId, $edit100PercentMatches, $lockLocked, $sourceLang, $targetLang, $task);
        $config = Zend_Registry::get('config');
        $this->_delimiter = $config->runtimeOptions->import->csv->delimiter;
        $this->_enclosure = $config->runtimeOptions->import->csv->enclosure;
        
        // check taskTemplate for options (check if tag-protection or regExes-protection is set)
        $taskConfig = Zend_Registry::get('taskTemplate');
        $className = __CLASS__;
        
        if (!isset($taskConfig->import->fileparser->$className)) {
            return;
        }
        $options = $taskConfig->import->fileparser->$className->options;
        
        if (isset($options->protectTags)) {
            $this->tagProtection = $options->protectTags;
        }
        if (isset($options->regexes->regex)) {
            $this->addReplaceRegularExpression($options->regexes->regex->toArray());
        }
    }
    
    protected function addReplaceRegularExpression (array $regExes) {
        if (empty($regExes)) {
            return;
        }
        
        foreach ($regExes as $regEx) {
            try {
                preg_match($regEx,'hello world !!!');
            }
            catch (Exception $e) {
                $log = ZfExtended_Factory::get('ZfExtended_Log');
                /* @var $log ZfExtended_Log */
                $message = (__CLASS__.' -> '.__FUNCTION__.'; CSV-import: invalid regularExpression '.$regEx);
                $log->logError('invalid regular expression', $message);
                continue;
            }
            $this->replaceRegularExpressions[] = $regEx;
        }
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
     *
     * @param mixed $segment
     * @param boolean isSource
     * @return string $segment
     */
    protected function parseSegment($segment,$isSource){
        $this->segmentTagCounter = 1;
        
        $count = 0;
        $segment = $this->parseSegmentProtectWhitespace($segment, $count);
        
        // mask regExes
        $segment = $this->parseSegmentMaskRegEx($segment);
        // add tag-protection
        $segment =  $this->parseSegmentProtectTags($segment);
        // add regEx-replacement-protection
        $segment = $this->parseSegmentProtectRegEx($segment);
        //encodes the html special characters, so that our frontend can deal with them
        $segment = htmlspecialchars($segment, ENT_COMPAT);
        
        // continue with normal processing
        if($count == 0) {
            return $segment;
        }
        
        //In CSV we have to directly replace our whitespace tags with their HTML replacement
        $search = array(
            '#<hardReturn/>#',
            '#<softReturn/>#',
            '#<macReturn/>#',
            '#<space ts="[^"]*"/>#'
        );
        
        //set data needed by $this->whitespaceTagReplacer
        $this->shortTagIdent = $this->segmentTagCounter;
        $this->_segment = $segment;
        $segment = preg_replace_callback($search, array($this,'whitespaceTagReplacer'), $segment);
        
        return $segment;
    }
    
    private function parseSegmentProtectTags($segment) {
        
        if (strpos($segment, '<')=== false || !$this->tagProtection) {
            return $segment;
        }
        
        try {
            $tempXml = qp('<?xml version="1.0"?><segment>'.$segment.'</segment>', NULL, array('format_output' => false));
        }
        catch (Exception $e) {
            return $this->parseReplaceNotWellformedXML($segment);
        }
        
        // mark single- or paired-tags
        foreach ($tempXml->find('segment *') as $element) {
            $tagType = 'singleTag';
            if (!empty($element->innerXml())) {
                $tagType = 'pairedTag';
            }
            
            // do not wrap internal tags
            if ($tagType == 'singleTag' && in_array($element->tag(), $this->internalTags)) {
                continue;
            }
            
            $element->wrap('<'.$tagType.'_'.$this->segmentTagCounter++.' data-tagname="'.$element->tag().'" />');
        }
        $tempReturn = $tempXml->find('segment')->innerXml();
        
        // replace single-, left- and right-tags
        $tempReturn = $this->parseReplaceSingleTags($tempReturn);
        $tempReturn = $this->parseReplaceLeftTags($tempReturn);
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
        if (preg_match_all('/<singleTag_([0-9]+).*?data-tagname="([^"]*)"[^>]*>(<[^>]+>)<\/singleTag_[0-9]+>/is', $text, $matches, PREG_SET_ORDER)) {
            
            foreach ($matches as $match) {
                $tag = $match[3];
                $tagId = $match[1];
                $tagName = $match[2];
                $fileNameHash = md5($tag);
                
                $p = $this->getTagParams($tag, $tagId, $tagName, $fileNameHash, $this->encodeTagsForDisplay($tag));
                $replace = $this->_singleTag->getHtmlTag($p);
                $text = str_replace($match[0], $replace, $text);
                
                $this->_singleTag->createAndSaveIfNotExists($tag, $fileNameHash);
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
        if (preg_match_all('/<pairedTag_([0-9]+).*?data-tagname="([^"]*)"[^>]*>(<[^>]+>)/is', $text, $matches, PREG_SET_ORDER)) {
            
            foreach ($matches as $match) {
                $tag = $match[3];
                $tagId = $match[1];
                $tagName = $match[2];
                $fileNameHash = md5($tag);
                
                $p = $this->getTagParams($tag, $tagId, $tagName, $fileNameHash, $this->encodeTagsForDisplay($tag));
                $replace = $this->_leftTag->getHtmlTag($p);
                $text = str_replace($match[0], $replace, $text);
                
                $this->_leftTag->createAndSaveIfNotExists($tag, $fileNameHash);
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
        if (preg_match_all('/(<[^>]+>)<\/pairedTag_([0-9]+)>/is', $text, $matches, PREG_SET_ORDER)) {
            
            foreach ($matches as $match) {
                $tag = $match[1];
                $tagId = $match[2];
                $tagName = preg_replace('/<[\/]*([^ ]*).*>/i', '$1', $tag);
                $fileNameHash = md5($tag);
                
                $p = $this->getTagParams($tag, $tagId, $tagName, $fileNameHash, $this->encodeTagsForDisplay($tag));
                $replace = $this->_rightTag->getHtmlTag($p);
                $text = str_replace($match[0], $replace, $text);
                
                $this->_rightTag->createAndSaveIfNotExists($tag, $fileNameHash);
            }
        }
        return $text;
    }
    
    
    /**
     * Replace all tags in $text as single-tags (handling for non-wellformed xml)
     * 
     * @param string $text
     * @return string
     */
    private function parseReplaceNotWellformedXML($text) {
        $tagCounter = & $this->segmentTagCounter;
        $internalTags = $this->internalTags;
        $replacer = function ($matches) use (&$tagCounter, $internalTags) {
            $tagName = preg_replace('/<[\/]*([^ ]*).*>/i', '$1', $matches[0]);
            // no replacements for internal tags
            if (in_array($tagName, $this->internalTags)) {
                return $matches[0];
            }
            $tagId = $tagCounter++;
            $tag = $matches[0];
            $fileNameHash = md5($tag);
            
            $p = $this->getTagParams($tag, $tagId, $tagName, $fileNameHash, $this->encodeTagsForDisplay($tag));
            $tempReturn = $this->_singleTag->getHtmlTag($p);
            
            $this->_singleTag->createAndSaveIfNotExists($tag, $fileNameHash);
            
            return $tempReturn;
        };
        
        return preg_replace_callback('/(<[^>]+>)/is', $replacer, $text);
    }
    
    
    /**
     * Mask all regular expressions $this->replaceRegularExpressions with internal tag <regex ...>
     *
     * @param string $text
     * @return string
     */
    private function parseSegmentMaskRegEx($text) {
        if (empty($this->replaceRegularExpressions)) {
            return $text;
        }
        
        $mask = function ($matches) {
            return '<regex data="'.base64_encode($matches[0]).'"/>';
        };
        
        //replace only on real text
        $split = preg_split('#(<[^>]+>)#', $text, null, PREG_SPLIT_DELIM_CAPTURE);
        $i = 0;
        foreach($split as &$chunk) {
            if($i++ % 2 === 1 || strlen($chunk) == 0) {
                // tag or empty chunks
                continue; 
            }
            foreach ($this->replaceRegularExpressions as $regEx) {
                $chunk = preg_replace_callback($regEx, $mask, $chunk);
            }
        }
        
        return join($split);
    }
    /**
     * Replace all masked regular expressions in $text as single-(regEx-)tags
     *
     * @param string $text
     * @return string
     */
    private function parseSegmentProtectRegEx($text) {
        if (empty($this->replaceRegularExpressions)) {
            return $text;
        }
        
        $tagCounter = & $this->segmentTagCounter;
        
        $replacer = function ($matches) use (&$tagCounter) {
            $tagId = $tagCounter++;
            $tag = base64_decode($matches[1]);
            $fileNameHash = md5($tag);
            
            $p = array(
                'class' => implode('', unpack('H*', $tag)),
                'text' => $this->encodeTagsForDisplay($tag),
                'shortTag' => $tagId,
                'id' => 'regex-'.$tagId.'-' . $fileNameHash,
            );
            
            $tempReturn = $this->_singleTag->getHtmlTag($p);
            $this->_singleTag->createAndSaveIfNotExists($tag, $fileNameHash);
            
            return $tempReturn;
        };
        
        $text = preg_replace_callback('/<regex data="([^"]*)"\/>/', $replacer, $text);
        
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
        $this->_lockedInFile[$this->_mid] = false;
        $this->_pretransSegment[$this->_mid] = false;
        $this->_editSegment[$this->_mid] = true;
        $this->_autoStateId[$this->_mid] = editor_Models_SegmentAutoStates::TRANSLATED;
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
