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
 * 
 * Just a structural wrapper for the Configuration regarding the AutoQAs length check
 */
class editor_Segment_Length_Restriction {
    
    /**
     * 
     * @var boolean
     */
    public $active = false;
    /**
     *
     * @var int
     */
    public $minLength = 0;
    /**
     *
     * @var int
     */
    public $maxLength = 0;
    /**
     *
     * @var int
     */
    public $maxLengthMinPercent = 0;
    /**
     *
     * @var int
     */
    public $maxLengthMinPixel = 0;
    /**
     *
     * @var int
     */
    public $maxLengthMinChars = 0;
    /**
     *
     * @var int
     */
    public $maxNumLines = 0;
    
    public function __construct(Zend_Config $qualityConfig, Zend_Config $taskConfig){
        $this->minLength = is_int($taskConfig->runtimeOptions->lengthRestriction->minWidth) ? $taskConfig->runtimeOptions->lengthRestriction->minWidth : 0;
        $this->maxLength = is_int($taskConfig->runtimeOptions->lengthRestriction->maxWidth) ? $taskConfig->runtimeOptions->lengthRestriction->maxWidth : 0;
        $this->maxNumLines = is_int($taskConfig->runtimeOptions->lengthRestriction->maxNumberOfLines) ? $taskConfig->runtimeOptions->lengthRestriction->maxNumberOfLines : 0;
        if($qualityConfig->enableSegmentLengthCheck == 1){
            $this->active = true;
            $this->maxLengthMinPercent = is_int($qualityConfig->segmentPixelLengthTooShortPercent) ? $qualityConfig->segmentPixelLengthTooShortPercent : 0;
            $this->maxLengthMinPixel = is_int($qualityConfig->segmentPixelLengthTooShortPixel) ? $qualityConfig->segmentPixelLengthTooShortPixel : 0;
            $this->maxLengthMinChars = is_int($qualityConfig->segmentPixelLengthTooShortChars) ? $qualityConfig->segmentPixelLengthTooShortChars : 0;
         }
        // DEBUG
        // error_log('editor_Segment_Length_Restriction: '.print_r($this, true));
    }
    /**
     * 
     * @param string $sizeUnit
     * @return int
     */
    public function getMinLengthOffset(string $sizeUnit) : int {
        if($sizeUnit == editor_Models_Segment_PixelLength::SIZE_UNIT_FOR_PIXELMAPPING){
            return $this->maxLengthMinPixel;
        }
        return $this->maxLengthMinChars;
    }
}
