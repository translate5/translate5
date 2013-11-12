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
    
    public function __construct(string $path, string $fileName, integer $fileId, boolean $edit100PercentMatches, editor_Models_Languages $sourceLang, editor_Models_Languages $targetLang, string $taskGuid) {
        ini_set('auto_detect_line_endings', true);//to tell php to respect mac-lineendings
        parent::__construct($path, $fileName, $fileId, $edit100PercentMatches, $sourceLang, $targetLang, $taskGuid);
        $config = Zend_Registry::get('config');
        $this->_delimiter = $config->runtimeOptions->import->csv->delimiter;
        $this->_enclosure = $config->runtimeOptions->import->csv->enclosure;
    }
    /**
     * @param string $line 
     * @return string $line
     */
    protected function prepareLine(string $line){
        $line = str_getcsv(trim($line),  $this->_delimiter,$this->_enclosure);
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
        $tmpPath = $this->_path.'.tmp';
        file_put_contents($tmpPath, $this->_origFile);
        $file = file($tmpPath);
        unlink($tmpPath);
        if(preg_match('"\r\n$"', $file[0]))$this->break = "\r\n";
        elseif(preg_match('"\n$"', $file[0]))$this->break = "\n";
        elseif(preg_match('"\r$"', $file[0]))$this->break = "\r";
        else{
            trigger_error('no linebreak found in csv.',E_USER_ERROR);
        }
        //check header and column order
        $config = Zend_Registry::get('config');
        $csvSettings = array_flip($config->runtimeOptions->import->csv->toArray());
        $header = $this->prepareLine($file[0]);
        for ($i=0; $i<3; $i++) {
            if(!isset($csvSettings[$header[$i]])){
                trigger_error('column-header '.$header[$i].' not found in application.ini.',
                        E_USER_ERROR);
            }
            $this->colOrder[$csvSettings[$header[$i]]] = $i;
        }
        $lineCount = count($file);
        for ($i=1; $i<$lineCount; $i++) {
            $file[$i] = $this->extractSegment($file[$i]);
        }
        $this->_skeletonFile = implode('', $file);//liinebreaks are added by extractSegment
    }
    /**
     * extracts the segment and saves them to db
     *
     * @param mixed $transUnit
     * @return mixed $transUnit
     */
    protected function extractSegment($line){
        $lineArr = $this->prepareLine($line);
        $this->_sourceOrig = $lineArr[$this->colOrder['source']];
        $this->_targetOrig = $lineArr[$this->colOrder['target']];
        $this->_mid = $lineArr[$this->colOrder['mid']];
        $this->_source = $this->parseSegment($this->_sourceOrig, true);
        $this->_target = $this->parseSegment($this->_targetOrig, false);
        
        $this->setSegmentAttribs($line);
        $segmentId = $this->setAndSaveSegmentValues();
        
        $lineArr[$this->colOrder['target']] = '<lekTargetSeg id="' .
                $segmentId .'"/>';
        
        return $this->str_putcsv($lineArr, $this->_delimiter, $this->_enclosure, $this->break);
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
        return $segment;
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
