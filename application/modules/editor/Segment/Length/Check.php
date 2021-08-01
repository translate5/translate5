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
 * evaluates the quality state of a segment regarding length-restrictions
 *
 */
class editor_Segment_Length_Check {
    
    /**
     * @var string
     */
    const TOO_LONG_PIXEL = 'too_long_pixel';
    /**
     * @var string
     */
    const TOO_SHORT_PIXEL = 'too_short_pixel';
    /**
     * currently unused
     * @var string
     */
    const TOO_MANY_LINES = 'too_many_lines';
    /**
     * @var editor_Segment_FieldTags
     */
    private $fieldTags;
    /**
     * @var editor_Models_Segment
     */
    private $segment;
    /**
     * @var editor_Models_Segment_Meta
     */
    private $segmentMeta;
    /**
     * @var array
     */
    private $metaCache;
    /**
     * @var string
     */
    private $sizeUnit;
    /**
     * @var boolean
     */
    private $valid = true;
    /**
     * @var string[]
     */
    private $states = [];
    /**
     * 
     * @param editor_Segment_FieldTags $fieldTags
     * @param editor_Models_Segment $segment
     * @param stdClass $lengthRestriction
     */
    public function __construct(editor_Segment_FieldTags $fieldTags, editor_Models_Segment $segment, editor_Segment_Length_Restriction $lengthRestriction){
        // just to make sure
        if(!$lengthRestriction->active){
            return;
        }
        $this->sizeUnit = editor_Models_Segment_PixelLength::SIZE_UNIT_XLF_DEFAULT;
        $this->fieldTags = $fieldTags;
        $this->segment = $segment;
        $data = $this->segment->getDataObject();
        $this->metaCache = (property_exists($data, 'metaCache') && !empty($data->metaCache)) ? json_decode($data->metaCache, true) : NULL;
        // dismiss segments with no length-restriction defined
        if ($this->metaCache == NULL || (is_null($this->metaCache['minWidth']) && is_null($this->metaCache['maxWidth']) && is_null($this->metaCache['maxNumberOfLines']))) {
            return;
        }

        $this->segmentMeta = $this->segment->meta();
        $this->sizeUnit = $this->segmentMeta->getSizeUnit();
        if(array_key_exists('minWidth', $this->metaCache) && !is_null($this->metaCache['minWidth'])){
            $lengthRestriction->minLength = intval($this->metaCache['minWidth']);
        }
        if(array_key_exists('maxWidth', $this->metaCache) && !is_null($this->metaCache['maxWidth'])){
            $lengthRestriction->maxLength = intval($this->metaCache['maxWidth']);
        }
        if($this->sizeUnit == $lengthRestriction->sizeUnit){
            // if number of lines given, validate this way
            if (array_key_exists('maxNumberOfLines', $this->metaCache) && !is_null($this->metaCache['maxNumberOfLines'])) {
                $this->evaluateLinesLength($lengthRestriction);
            } else {                
                $this->evaluateTransUnitLength($lengthRestriction);
            }
        }
    }
    /**
     * Retrieves the evaluated states (currntly this can be only 1 or 0 states)
     * @return string[]
     */
    public function getStates(){
        return $this->states;
    }
    /**
     * 
     * @return boolean
     */
    public function hasStates(){
        return count($this->states) > 0;
    }
    /**
     * Evaluates the error-states for a trans-unit based length restriction
     * CURRENTLY this is always pixel-based
     * @param editor_Segment_Length_Restriction $lengthRestriction
     */
    protected function evaluateTransUnitLength(editor_Segment_Length_Restriction $restriction){
        if(($restriction->minLength > 0 || $restriction->maxLength > 0) && $restriction->sizeUnit == $this->sizeUnit && !empty($this->metaCache['siblingData'])) {
            // calculate trans-unit length
            $length = 0;
            foreach($this->metaCache['siblingData'] as $id => $data) {
                //if we don't have any information about the givens field length, we assume all OK
                if(!array_key_exists($this->fieldTags->getField(), $data['length'])){
                    return;
                }
                if($id == $this->segment->getId()) {
                    //if the found sibling is the segment itself, use the length of the value to be stored
                    $length += (int) $this->segment->textLengthByMeta(
                        $this->fieldTags->getFieldText(true, true),
                        $this->segmentMeta,
                        $this->segment->getFileId());
                    //normally, the length of one segment contains also the additionalMrkLength,
                    //for the current segment this is added below, the siblings in the next line contain their additionalMrk data already
                } else {
                    //add the text length of desired field
                    $length += (int) $data['length'][$this->fieldTags->getField()];
                }
            }
            $length += intval($this->metaCache['additionalUnitLength']);
            $length += intval($this->metaCache['additionalMrkLength']);
            if($restriction->maxLength > 0 && $length > $restriction->maxLength){
                $this->states[] = self::TOO_LONG_PIXEL;
            }
            if($restriction->minLength > 0 && 
                (($restriction->minLengthThresh > 0 && $length <= ($restriction->minLength - $restriction->minLengthThresh))
                || ($restriction->minLengthPercent > 0 && ($length < ($restriction->minLength * (100 - $restriction->minLengthPercent) / 100))))){
                    $this->states[] = self::TOO_SHORT_PIXEL;
            }
        }
    }
    /**
     * Evaluates the error-states for a lines based length restriction
     * CURRENTLY this is always pixel-based
     * @param editor_Segment_Length_Restriction $restriction
     */
    protected function evaluateLinesLength(editor_Segment_Length_Restriction $restriction){
        $lines = $this->fieldTags->getFieldTextLines(true);
        $numLines = count($lines);
        if($numLines > $this->metaCache['maxNumberOfLines']){
            // Currently, segments with too many lines can not be saved. Whenever this changes, activate this line
            // $this->states[] = self::TOO_MANY_LINES;
        }
        if($restriction->minLength > 0 || $restriction->maxLength > 0){
            foreach ($lines as $line) {
                $length = (int) $this->segment->textLengthByMeta($line, $this->segmentMeta, $this->segment->getFileId());
                if($restriction->maxLength > 0 && $length > $restriction->maxLength && !in_array(self::TOO_LONG_PIXEL, $this->states)){
                    $this->states[] = self::TOO_LONG_PIXEL;
                }
                if($restriction->minLength > 0 && !in_array(self::TOO_SHORT_PIXEL, $this->states) &&
                    (($restriction->minLengthThresh > 0 && $length <= ($restriction->minLength - $restriction->minLengthThresh))
                        || ($restriction->minLengthPercent > 0 && ($length < ($restriction->minLength * (100 - $restriction->minLengthPercent) / 100))))){
                            $this->states[] = self::TOO_SHORT_PIXEL;
                }
            }
        }
    }
}
