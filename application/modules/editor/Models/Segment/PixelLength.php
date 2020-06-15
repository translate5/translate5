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
 * Calculating the length of a text in a segment based on pixelMapping.
*/
class editor_Models_Segment_PixelLength {
    
    /**
     * In xlf, the default size-unit of a trans-unit is 'pixel' if size-unit is not set.
     * - http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html#size-unit
     * - http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html#maxwidth
     * @var string
     */
    const SIZE_UNIT_XLF_DEFAULT = 'pixel';
    
    /**
     * Size-unit used for pixel-mapping.
     * @var string
     */
    const SIZE_UNIT_FOR_PIXELMAPPING = 'pixel';
    
    /**
     * Default pixel-widths for font_sizes from ZfConfig
     * @var array
     */
    protected $defaultPixelWidths;
    
    /**
     * @var editor_Models_Segment
     */
    protected $segment;
    
    /**
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * This class must be linked to a task, otherwise we might use wrong data!
     * @var string
     */
    protected $taskGuid;
    
    /**
     * @var editor_Models_PixelMapping
     */
    protected $pixelMapping;
    
    /**
     * PixelMapping for current task
     * @var array
     */
    protected $pixelMappingForTask;
    
    protected $logMissingData = [];
    
    public function __construct(string $taskGuid)
    {
        $this->segment = ZfExtended_Factory::get('editor_Models_Segment');
        
        $this->taskGuid = $taskGuid;
        $this->task = ZfExtended_Factory::get('editor_Models_Task');
        $this->task->loadByTaskGuid($taskGuid);
        $this->pixelMapping = ZfExtended_Factory::get('editor_Models_PixelMapping');
        /* @var $pixelMapping editor_Models_PixelMapping */
        $this->pixelMappingForTask = $this->pixelMapping->getPixelMappingForTask($this->task->getTaskGuid(), $this->task->getAllFontsInTask());
    }
    
    public function __destruct() {
        if(empty($this->logMissingData)) {
            return;
        }
        $logMsg = 'Segment length calculation: ';
        $logMsg .= 'No pixel-width set for several characters.'."\n";
        $logMsg .= 'Default width is used. See affected characters in extra data.';
        
        $logger = Zend_Registry::get('logger');
        /* @var $logger ZfExtended_Logger */
        $logger = $logger->cloneMe('editor.segment.pixellength');
        
        $logger->warn('E1278', $logMsg, [
            'affectedCharacters' => $this->logMissingData,
            'task' => $this->task,
        ]);
    }
    
    /**
     * Return the taskGuid the pixelLength is currently attached to.
     * @return string
     */
    public function getTaskGuid() {
        return $this->taskGuid;
    }
    
    /**
     * What's the length of a segment's content according to the pixelMapping?
     * @param string $segmentContent
     * @param string $fontFamily
     * @param int $fontSize
     * @param int $fileId
     * @return integer
     */
    public function textLengthByPixel ($segmentContent, $fontFamily, $fontSize, $fileId) {
        $pixelLength = 0;
        $fontFamily = strtolower($fontFamily);
        $pixelMappingForFontAndSize = $this->pixelMapping->getPixelMappingForFontAndSize($this->taskGuid, $this->pixelMappingForTask, $fontFamily, $fontSize);
        $charsNotSet = [];
        
        // prepare string for counting
        $segmentContent = $this->segment->prepareForPixelBasedLengthCount($segmentContent);
        
        // get length for string by adding each character's length
        $allCharsInSegment = $this->segmentContentAsCharacters($segmentContent);
        foreach ($allCharsInSegment as $char) {
            $charWidth = $this->pixelMapping->getCharWidth($char, $pixelMappingForFontAndSize, $fileId, $charsNotSet);
            
            if (is_null($charWidth)) {
                //textlength by pixel failed; most probably data about the pixelWidth is missing
                throw new editor_Models_Segment_Exception('E1081', [
                    'char' => $char,
                    'charCode' => $this->pixelMapping->getNumericValueOfUnicodeChar($char),
                    'fontFamily' => $fontFamily,
                    'fontSize' => $fontSize,
                ]);
            }
            
            $pixelLength += $charWidth;
            //error_log('[' . $key . '] ' . $char . ' ('. $this->pixelMapping->getNumericValueOfUnicodeChar($char) . ': '.$charWidth. ') => length now: ' . $pixelLength);
        }
        
        if (!empty($charsNotSet)) {
            $logKey = $fontFamily.'#'.$fontSize;
            if(array_key_exists($logKey, $this->logMissingData)) {
                $logData = $this->logMissingData[$logKey];
                $logData->affectedCharacters = array_merge($logData->affectedCharacters, $charsNotSet);
            }else {
                $logData = new stdClass();
                $logData->fontFamily = $fontFamily;
                $logData->fontSize = $fontSize;
                $logData->default = $pixelMappingForFontAndSize['default'];
                $logData->affectedCharacters = $charsNotSet;
                $this->logMissingData[$logKey] = $logData;
            }
            $logData->affectedCharacters = array_unique($logData->affectedCharacters);
            sort($logData->affectedCharacters);
        }
        
        return $pixelLength;
    }
    
    // ---------------------------------------------------------------------------------------
    // Unicode-Helpers
    // ---------------------------------------------------------------------------------------
    
    /**
     * Returns an array with the single (unicode-)characters of the given string.
     * @param string $string
     * @return string
     */
    protected function segmentContentAsCharacters (string $string) {
        $array = [];
        // Break-up a multibyte string into its individual characters.
        // http://php.net/manual/en/function.mb-split.php#80046
        $strlen = mb_strlen($string);
        while ($strlen) {
            $array[] = mb_substr($string,0,1,"UTF-8");
            $string = mb_substr($string,1,$strlen,"UTF-8");
            $strlen = mb_strlen($string);
        }
        return $array;
    }
}
