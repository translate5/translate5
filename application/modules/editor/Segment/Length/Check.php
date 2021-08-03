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
 * NOTE: Currently we only evaluate the pixel length and the fullfillment of the maxLength (either if a segment is longer or not long enough relative to the max-length
 *
 */
class editor_Segment_Length_Check {
    
    /**
     * @var string
     */
    const TOO_LONG = 'too_long';
    /**
     * @var string
     */
    const NOT_LONG_ENOUGH = 'not_long_enough';
    /**
     * @var string
     */
    const TOO_SHORT = 'too_short';
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
        $this->fieldTags = $fieldTags;
        $this->segment = $segment;
        $data = $this->segment->getDataObject();
        $this->metaCache = (property_exists($data, 'metaCache') && !empty($data->metaCache)) ? json_decode($data->metaCache, true) : NULL;
        // dismiss segments with no length-restriction defined
        if ($this->metaCache == NULL || (is_null($this->metaCache['minWidth']) && is_null($this->metaCache['maxWidth']) && is_null($this->metaCache['maxNumberOfLines']))) {
            return;
        }
        $this->segmentMeta = $this->segment->meta();
        // set the current limits
        $lengthRestriction->sizeUnit = $this->segmentMeta->getSizeUnit();
        $lengthRestriction->minLength = (array_key_exists('minWidth', $this->metaCache) && !is_null($this->metaCache['minWidth'])) ? intval($this->metaCache['minWidth']) : 0;
        $lengthRestriction->maxLength = (array_key_exists('maxWidth', $this->metaCache) && !is_null($this->metaCache['maxWidth'])) ? intval($this->metaCache['maxWidth']) : 0;
        $lengthRestriction->maxNumLines = (array_key_exists('maxNumberOfLines', $this->metaCache) && !is_null($this->metaCache['maxNumberOfLines'])) ? intval($this->metaCache['maxNumberOfLines']) : 0;
        
        // DEBUG
        // error_log("PROCESS SEGMENT ".$this->segment->getSegmentNrInTask()." ".print_r($lengthRestriction, true));
        
        if($lengthRestriction->isRestricted()){
            // if number of lines given, validate this way
            if ($lengthRestriction->maxNumLines > 0){
                $this->evaluateLinesLength($lengthRestriction);
            } else {
                $this->evaluateTransUnitLength($lengthRestriction);
            }
        }
    }
    /**
     * Retrieves the evaluated states
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
     * @param editor_Segment_Length_Restriction $lengthRestriction
     */
    private function evaluateTransUnitLength(editor_Segment_Length_Restriction $restriction){
        
        if($restriction->isLengthRestricted() && !empty($this->metaCache['siblingData'])) {
            // calculate trans-unit length
            $length = 0;
            foreach($this->metaCache['siblingData'] as $id => $data) {
                // TODO FIXME
                // The fieldtags Do not hold the index of the field where the field text originated from
                // this is majot problem as it reduces the use-cases of the field-text API
                // we have to dirtily re-create this index her (is valid only for targets !!)
                $editIndex = $this->fieldTags->getField().editor_Models_SegmentFieldManager::_EDIT_PREFIX;
                //if we don't have any information about the givens field length, we assume all OK
                if(!array_key_exists($editIndex, $data['length'])){
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
                    $length += (int) $data['length'][$editIndex];
                }
            }
            $length += intval($this->metaCache['additionalUnitLength']);
            $length += intval($this->metaCache['additionalMrkLength']);
            if($restriction->maxLength > 0 && $length > $restriction->maxLength){
                $this->states[] = self::TOO_LONG;
            } else if($this->isNotLongEnough($length, $restriction)){
                $this->states[] = self::NOT_LONG_ENOUGH;
            }
            if($restriction->minLength > 0 && $length < $restriction->minLength){
                $this->states[] = self::TOO_SHORT;
            }
        }
    }
    /**
     * Evaluates the error-states for a lines based length restriction
     * CURRENTLY this is always pixel-based
     * @param editor_Segment_Length_Restriction $restriction
     */
    private function evaluateLinesLength(editor_Segment_Length_Restriction $restriction){
        $lines = $this->fieldTags->getFieldTextLines(true);
        $numLines = count($lines);
        if($numLines > $restriction->maxNumLines){
            $this->states[] = self::TOO_MANY_LINES;
        }
        if($restriction->isLengthRestricted()){
            foreach ($lines as $line) {
                $length = (int) $this->segment->textLengthByMeta($line, $this->segmentMeta, $this->segment->getFileId());
                if($restriction->maxLength > 0 && $length > $restriction->maxLength && !in_array(self::TOO_LONG, $this->states)){
                    $this->states[] = self::TOO_LONG;
                }
                if($this->isNotLongEnough($length, $restriction) && !in_array(self::NOT_LONG_ENOUGH, $this->states)){
                    $this->states[] = self::NOT_LONG_ENOUGH;
                }
                if($restriction->minLength > 0 && $length < $restriction->minLength && !in_array(self::TOO_SHORT, $this->states)){
                    $this->states[] = self::TOO_SHORT;
                }
            }
        }
    }
    /**
     * Checks if "Not long enough"
     * @param int $length
     * @param editor_Segment_Length_Restriction $restriction
     * @return bool
     */
    private function isNotLongEnough(int $length, editor_Segment_Length_Restriction $restriction) : bool {
        if($restriction->maxLength > 0){
            if($restriction->getMinLengthOffset() > 0 && $length <= ($restriction->maxLength - $restriction->getMinLengthOffset())){
                return true;
            }
            if($restriction->maxLengthMinPercent > 0 && ($length < round($restriction->maxLength * (100 - $restriction->maxLengthMinPercent) / 100))){
                return true;
            }
        }
        return false;
    }
}
