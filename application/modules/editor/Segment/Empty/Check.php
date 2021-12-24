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
class editor_Segment_Empty_Check {
    
    /**
     * @var string
     */
    const IS_EMPTY = 'empty'; // same as editor_Segment_Empty_QualityProvider::$type

    /* *
     * @var editor_Segment_FieldTags
     * /
    private $fieldTags;
    /* *
     * @var editor_Models_Segment
     * /
    private $segment;
    /* *
     * @var editor_Models_Segment_Meta
     * /
    private $segmentMeta;
    /* *
     * @var array
     * /
    private $metaCache;
    /* *
     * @var boolean
     * /
    private $valid = true;*/

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
    public function __construct(editor_Segment_FieldTags $fieldTags, editor_Models_Segment $segment, string $chars) {//, editor_Segment_Empty_Restriction $lengthRestriction){
        /*// just to make sure
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
        }*/

        // Get source text, strip tags, replace htmlentities, strip whitespace and punctuation chars
        $source = $segment->getSourceToSort();
        $source = strip_tags($source);
        $source = str_replace(['&lt;', '&gt;'], ['<', '>'], $source);
        $source = preg_replace('~[\s' .  preg_quote($chars, '~'). ']~', '', $source);

        // Get target text, strip tags, replace htmlentities, strip whitespace and punctuation chars
        $target = $segment->getTargetEditToSort();
        $target = strip_tags($target);
        $target = str_replace(['&lt;', '&gt;'], ['<', '>'], $target);
        $target = preg_replace('~[\s' .  preg_quote($chars, '~'). ']~', '', $target);

        // If $source is still non zero-length, but $target is  - flag it's empty
        if (!strlen($target) && strlen($source)) $this->states[] = self::IS_EMPTY;
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
    public function hasStates() {
        return count($this->states) > 0;
    }

    /* *
     * Evaluates the error-states for a lines based length restriction
     * @param editor_Segment_Length_Restriction $restriction
     * /
    private function evaluateLinesLength(editor_Segment_Length_Restriction $restriction){
        $lines = $this->fieldTags->getFieldTextLines(true);
        $numLines = count($lines);
        if($numLines > $restriction->maxNumLines){
            $this->states[] = self::TOO_MANY_LINES;
        }
        if($restriction->isLengthRestricted()){
            $lengthOverall = 0;
            foreach ($lines as $line) {
                $length = (int) $this->segment->textLengthByMeta($line, $this->segmentMeta, $this->segment->getFileId());
                $lengthOverall += $length;
                if($restriction->maxLength > 0 && $length > $restriction->maxLength && !in_array(self::TOO_LONG, $this->states)){
                    $this->states[] = self::TOO_LONG;
                }
                if($restriction->minLength > 0 && $length < $restriction->minLength && !in_array(self::TOO_SHORT, $this->states)){
                    $this->states[] = self::TOO_SHORT;
                }
            }
            // the "not long enough" state relates to the overall length. This is a bit unlogical here but it's the way the customers want the feature
            if($this->isNotLongEnough($lengthOverall, $restriction)){
                $this->states[] = self::NOT_LONG_ENOUGH;
            }
        }
    }
    */
}
