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
 * Fileparsing for Csv-files
 *
 * - parent class should ensure, that file already is utf-8-encoded
 *
 */
class editor_Models_Import_FileParser_Csv extends editor_Models_Import_FileParser {
    use editor_Models_Import_FileParser_TagTrait {
        getTagParams as protected traitGetTagParams;
    }
    
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
     * @var boolean
     */
    protected $tagProtection = false;
    
    /**
     * @var array
     */
    protected $replaceRegularExpressionsBeforeTagParsing = array();
    
    /**
     * @var array
     */
    protected $replaceRegularExpressionsAfterTagParsing = array();
    
    /**
     *
     * @var string special placeholder needed in the loop that protects different kind of strings and tags in csv for the editing process
     */
    protected $placeholderCSV = '𓇽𓇽𓇽𓇽𓇽𓇽';
    /**
     *
     * @var string regex describing the structure of translate5 internal tags
     */
    protected $regexInternalTags = null;
    /**
     *
     * @var array syntax: array('𓇽𓇽𓇽𓇽𓇽𓇽1' => '<div class="single 73706163652074733d2263326130222f"><span title="<space/>" class="short" id="ext-gen1796">&lt;1/&gt;</span><span id="space-2-b31345d64a8594d0e7b79852d022c7f2" class="full">&lt;space/&gt;</span></div>');
     *      explanation: key: the string that is the placeholder for the actual to be protected string
     *                   value: the to be protected string already converted to a translate5 internal tag
     */
    protected $protectedStrings = array();
    
    protected $html5Tags = array();

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::getFileExtensions()
     */
    public static function getFileExtensions() {
        return ['csv'];
    }
    
    /**
     * returns the mimetypes valid for that fileparser
     * @return string[]
     */
    public static function getValidMimeTypes() {
        return array('text/plain','application/xml','text/html'); //this is due to the fact, that csv-files may contain html or xml fragments. In these cases the php-mime-type extension may recognize them as such.
    }
    
    public function __construct(string $path, string $fileName, integer $fileId, editor_Models_Task $task) {
        ini_set('auto_detect_line_endings', true);//to tell php to respect mac-lineendings
        parent::__construct($path, $fileName, $fileId, $task);
        $this->initImageTags();
        
        $this->_delimiter = $this->config->runtimeOptions->import->csv->delimiter;
        $this->_enclosure = $this->config->runtimeOptions->import->csv->enclosure;
        $this->regexInternalTags = editor_Models_Segment_InternalTag::REGEX_INTERNAL_TAGS;
        
        // check taskTemplate for options (check if tag-protection or regExes-protection is set)
        $taskConfig = Zend_Registry::get('taskTemplate');
        $className = __CLASS__;
        
        if (!isset($taskConfig->import->fileparser->$className)) {
            return;
        }
        $options = $taskConfig->import->fileparser->$className->options;
        
        if (isset($options->protectTags)) {
            $this->tagProtection = $options->protectTags;
            $ds = DIRECTORY_SEPARATOR;
            $html5TagFile = APPLICATION_PATH.$ds.'modules'.$ds.'editor'.
                    $ds.'Models'.$ds.'Import'.$ds.'FileParser'.$ds.
                    'html5-tags.txt';
            $this->html5Tags = file($html5TagFile, FILE_IGNORE_NEW_LINES);
        }
        if (isset($options->regexes->beforeTagParsing->regex)) {
            $this->addReplaceRegularExpression($options->regexes->beforeTagParsing->regex,'replaceRegularExpressionsBeforeTagParsing');
        }
        if (isset($options->regexes->afterTagParsing->regex)) {
            $this->addReplaceRegularExpression($options->regexes->afterTagParsing->regex,'replaceRegularExpressionsAfterTagParsing');
        }
    }
    /**
     * 
     * @param mixed $regExes object or string
     * @param string $regexArrayName
     * @return void
     */
    protected function addReplaceRegularExpression ($regExes, $regexArrayName) {
        if (empty($regExes)) {
            return;
        }
        if(is_string($regExes)){
            $regExes = array($regExes);
        }
        else {
           $regExes = $regExes->toArray();
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
            if(preg_match($regEx, $this->placeholderCSV)===1){
                throw new ZfExtended_NotAcceptableException('The regex '.$regEx.' matches the placeholderCSV string '.$this->placeholderCSV.' that is used in the editor_Models_Import_FileParser_Csv class to manage the protection loop. This is not allowed. Please find another solution to protect what you need to protect in your CSV via Regular Expression.');
            }
            $regexArray =& $this->$regexArrayName;
            $regexArray[] = $regEx;
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
        $csvSettings = $this->config->runtimeOptions->import->csv->fields->toArray();
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
                 'original' => $this->parseSegment($original, $isSource)
            );
        }

        $this->parseSegmentAttributes($lineArr); //<< hier kann crap übergeben werden
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
        //check, if $this->placeholderCSV is present in segment - this must lead to error
        if(strpos($segment, $this->placeholderCSV)!==false){
            throw new ZfExtended_Exception('The string $this->placeholderCSV ('.$this->placeholderCSV.') had been present in the segment before parsing it. This is not allowed.');
        }
        $this->shortTagIdent = 1;
        
        //at first protect MQM - they will be converted to MQM-tags later by MqmParser
        //for performance reasons only do this, if escaping MQM is necessary for later protections
        if(strpos($segment, '<mqm:')!==false){
            $segment = $this->parseSegmentInsertPlaceholders($segment,'#(<mqm:startIssue[^>]+/>)#');
            $segment = $this->parseSegmentInsertPlaceholders($segment,'#(<mqm:endIssue[^>]+/>)#');
        }
        
        // protect regExes
        $segment = $this->parseSegmentRegEx($segment,  $this->replaceRegularExpressionsBeforeTagParsing);
        // add tag-protection
        $segment =  $this->parseSegmentProtectTags($segment);
        // protect regExes
        $segment = $this->parseSegmentRegEx($segment,  $this->replaceRegularExpressionsAfterTagParsing);
        
        $segment = $this->parseSegmentProtectWhitespace($segment);
        
        return $this->parseSegmentReplacePlaceholders($segment);
    }
    /**
     * protects whitespace inside a segment with a tag
     *
     * @param string $segment
     * @return string $segment
     */
    protected function parseSegmentProtectWhitespace($segment) {
        //since CSV has escaped all tags before, we can call directly protectWhitespace instead of parseSegmentProtectWhitespace which splits the content up
        $segment = $this->protectWhitespace($segment, false);
        //In CSV we have to directly replace our whitespace tags with their HTML replacement
        $segment = $this->whitespaceTagReplacer($segment);
        $segment = $this->parseSegmentInsertPlaceholders($segment,$this->regexInternalTags);
        return $segment;
    }
    
    /**
     * 
     * @param string $segment
     * @return string 
     */
    protected function parseSegmentReplacePlaceholders($segment){
        $placeholders = array_keys($this->protectedStrings);
        $tags = array_values($this->protectedStrings);
        $this->protectedStrings = array();
        return str_replace($placeholders, $tags, $segment);
    }
    
    /**
     * be careful: if segment does not contain a "<", this method will simply return the segment (for performance reasons)
     * @param string $segment
     * @param string $tagToReplaceRegex - should contain a regex, that stands for a tag, that should be hidden for parsing reasons by a placeholder.
     * @return string 
     */
    protected function parseSegmentInsertPlaceholders($segment,$tagToReplaceRegex){
        if(strpos($segment, '<')===false){
            return $segment;
        }
        
        $str_replace_first = function($search, $replace, $subject) {
            $pos = strpos($subject, $search);
            if ($pos !== false) {
                $subject = substr_replace($subject, $replace, $pos, strlen($search));
            }
            return $subject;
        };
        
        
        preg_match_all($tagToReplaceRegex, $segment, $matches, PREG_PATTERN_ORDER);
        //"<div\s*class=\"([a-z]*)\s+([gxA-Fa-f0-9]*)\"\s*.*?(?!</div>)<span[^>]*id=\"([^-]*)-.*?(?!</div>).</div>"s
        $protectedStringCount = count($this->protectedStrings);
        foreach ($matches[0] as $match) {
            $placeholder = $this->placeholderCSV.$protectedStringCount;
            $this->protectedStrings[$placeholder] = $match;
            $segment = $str_replace_first($match,$placeholder,$segment);
            $protectedStringCount++;
        }
        return $segment;
    }

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::getTagParams()
     * @see TRANSLATE-659
     */
    protected function getTagParams($tag, $shortTag, $tagId, $fileNameHash, $text = false) {
        return $this->traitGetTagParams($tag, $shortTag, $tagId, $fileNameHash, $text);
    }
    
    private function parseSegmentProtectTags($segment) {
        
        if (strpos($segment, '<')=== false || !$this->tagProtection) {
            return $segment;
        }
        
        try {
            $tempXml = qp('<?xml version="1.0"?><segment>'.$segment.'</segment>', NULL, array('format_output' => false));
        }
        catch (Exception $e) {
            return $this->parseSegmentProtectInvalidHtml5($segment);
        }
        
        // mark single- or paired-tags
        foreach ($tempXml->find('segment *') as $element) {
            $tagType = 'singleTag';
            if (!empty($element->innerXml())) {
                $tagType = 'pairedTag';
            }
            
            $element->wrap('<'.$tagType.'_'.$this->shortTagIdent++.' data-tagname="'.$element->tag().'" />');
        }
        $r= $tempXml->find('segment')->innerXml();
        
        // replace single-, left- and right-tags
        $r = $this->parseReplaceSingleTags($r);
        $r = $this->parseReplaceLeftTags($r);
        $r = $this->parseReplaceRightTags($r);
        
        return $this->parseSegmentInsertPlaceholders($r,$this->regexInternalTags);
    }

    private function parseSegmentProtectInvalidHtml5($segment) {
        $replacer = function ($matches){
            $tagName = preg_replace('/<[\/]*([^ ]*).*>/i', '$1', $matches[0]);
            // only replace HTML5 tags
            if (!in_array($tagName, $this->html5Tags)) {
                return $matches[0];
            }
            $tagId = $this->shortTagIdent++;
            $tag = $matches[0];
            $fileNameHash = md5($tag);
            
            $p = $this->getTagParams($tag, $tagId, $tagName, $fileNameHash, $this->encodeTagsForDisplay($tag));
            $r = $this->_singleTag->getHtmlTag($p);
            
            $this->_singleTag->createAndSaveIfNotExists($tag, $fileNameHash);
            
            return $r;
        };
        
        $segment = preg_replace_callback('/(<[^><]+>)/is', $replacer, $segment);
        
        return $this->parseSegmentInsertPlaceholders($segment,$this->regexInternalTags);
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
     * Mask all regular expressions $this->replaceRegularExpressions with internal tag <regex ...>
     *
     * @param string $text
     * @param array $regexToUse $replaceRegularExpressionsBeforeTagParsing | $replaceRegularExpressionsAfterTagParsing
     * @return string
     */
    private function parseSegmentRegEx($text, &$regexToUse) {
        if (empty($regexToUse)) {
            return $text;
        }
        $mask = function ($matches){
            $tag = $matches[0];
            //if there already is a protected string inside this match, don't protect it
            if(strpos($tag, $this->placeholderCSV)!==false){
                return $tag;
            }
            $tagId = $this->shortTagIdent++;
            $fileNameHash = md5($tag);
            $p = array(
                'class' => implode('', unpack('H*', $tag)),
                'text' => $this->encodeTagsForDisplay($tag),
                'shortTag' => $tagId,
                'id' => 'regex',
                'filenameHash' => $fileNameHash,
            );
            
            $r = $this->_singleTag->getHtmlTag($p);
            $this->_singleTag->createAndSaveIfNotExists($tag, $fileNameHash);
            
            return $r;
        };
        foreach ($regexToUse as $regEx) {
            $text = preg_replace_callback($regEx, $mask, $text);
        }
        return $this->parseSegmentInsertPlaceholders($text,$this->regexInternalTags);
    }
    
    /**
     * Sets the segment attributes for the segment currently worked on, uses the system defaults
     * $this->_target and $this->_source must be defined already!
     * most of the attributes are not defined for CSV and are accordingly presetted
     * 
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::parseSegmentAttributes()
     */
    protected function parseSegmentAttributes($segment){
        //just create a segment attributes object with default values
        $this->createSegmentAttributes($this->_mid);
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
