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
 * @author axel
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
    const TOO_SHORT = 'too_short';
    /**
     * @var string
     */
    const TOO_SHORT = 'too_many_lines';
    /**
     * @var editor_Segment_FieldTags
     */
    private $fieldTags;
    /**
     * @var array
     */
    private $metaCache;
    /**
     * @var boolean
     */
    private $valid = true;
    /**
     * @var boolean
     */
    private $states = [];
    /**
     * Segment Length Validator needs a instanced editor_Models_SegmentFieldManager
     * @param editor_Models_SegmentFieldManager $sfm
     */
    public function __construct(editor_Segment_FieldTags $tags, editor_Models_Segment $segment){
        $this->fieldTags = $tags;
        $data = $segment->getDataObject();
        $this->metaCache = (property_exists($data, 'metaCache') && !empty($data->metaCache)) ? json_decode($data->metaCache, true) : NULL;
        $this->validate();
    }
    /**
     * 
     */
    protected function validate(){
        if($this->metaCache == NULL){
            $this->valid = true;
        } else if(is_null($this->metaCache['minWidth']) && is_null($this->metaCache['maxWidth']) && is_null($this->metaCache['maxNumberOfLines'])){
            $this->valid = true;
        } else {
            if(array_key_exists('maxNumberOfLines',$this->metaCache) && !is_null($this->metaCache['maxNumberOfLines'])) {
                $this->validateLengthForLines();
            } else {
                $this->validateLengthForSegmentAndSiblings();
            }
        }
    }

  
  /**
   * validates the given value of the given field against the max number and length of lines of the transunit
   * @param string $value
   * @param string $field
   * @return boolean
   */
  protected function validateLengthForLines($value, $field){
      
      $isValid = true;
            
      $tagHelper = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
      /* @var $tagHelper editor_Models_Segment_InternalTag */
      $allLines = $tagHelper->getLinesAccordingToNewlineTags($value);
      if(count($allLines) > $meta['maxNumberOfLines']) {
          $this->addMessage($field, 'segmentTooManyLines', 'There are '.count($allLines).' lines in the segment, but only '.$meta['maxNumberOfLines'] . ' lines are allowed.');
          $isValid = false;
      }
      
      $checkMinWidth = (array_key_exists('minWidth', $meta) && !is_null($meta['minWidth']));
      $checkMaxWidth = (array_key_exists('maxWidth', $meta) && !is_null($meta['maxWidth']));
      if ($checkMinWidth || $checkMaxWidth) {
          $errorsMaxWidth = [];
          $errorsMinWidth = [];
          foreach ($allLines as $key => $line) {
              $length = (int)$this->segment->textLengthByMeta($line, $this->segment->meta(), $this->segment->getFileId());
              if ($checkMaxWidth && $length > $meta['maxWidth']) {
                  $errorsMaxWidth[] = ($key+1) . ': ' . $length;
              }
              if ($checkMinWidth && $length < $meta['minWidth']) {
                  $errorsMinWidth[] = ($key+1) . ': ' . $length;
              }
          }
          if (!empty($errorsMinWidth)) {
              $this->addMessage($field, 'segmentLinesTooShort', 'Not all lines in the segment match the given minimal length: ' . implode('; ', $errorsMinWidth));
              $isValid = false;
          }
          if (!empty($errorsMaxWidth)) {
              $this->addMessage($field, 'segmentLinesTooLong', 'Not all lines in the segment match the given maximal length: ' . implode('; ', $errorsMaxWidth));
              $isValid = false;
          }
      }
      
      return $isValid;
  }
  
  /**
   * validates the given value of the given field with the sibling length agains the min and max values of the transunit
   * @param string $value
   * @param string $field
   * @return boolean
   */
  public function validateLengthForSegmentAndSiblings($value, $field){

      $data = $this->segment->getDataObject();
      if(!property_exists($data, 'metaCache') || empty($data->metaCache)) {
          return true;
      }
      $meta = json_decode($data->metaCache, true);
      if(empty($meta['siblingData'])) {
          return true;
      }
      
      $sizeUnit = empty($meta['sizeUnit']) ? editor_Models_Segment_PixelLength::SIZE_UNIT_XLF_DEFAULT : $meta['sizeUnit'];
      $isPixelBased = ($sizeUnit == editor_Models_Segment_PixelLength::SIZE_UNIT_FOR_PIXELMAPPING);
      
      $length = 0;
      foreach($meta['siblingData'] as $id => $data) {
          //if we don't have any information about the givens field length, we assume all OK
          if(!array_key_exists($field, $data['length'])){
              return true;
          }
          if($id == $this->segment->getId()) {
              //if the found sibling is the segment itself, use the length of the value to be stored
              $length += (int)$this->segment->textLengthByMeta($value, $this->segment->meta(), $this->segment->getFileId());
              //normally, the length of one segment contains also the additionalMrkLength,
              //for the current segment this is added below, the siblings in the next line contain their additionalMrk data already
          }
          else {
              //add the text length of desired field
              $length += (int)$data['length'][$field];
          }
      }
      
      settype($meta['additionalUnitLength'], 'integer');
      $length += $meta['additionalUnitLength'];
      settype($meta['additionalMrkLength'], 'integer');
      $length += $meta['additionalMrkLength'];
      
      $checkMinWidth = (array_key_exists('minWidth', $meta) && !is_null($meta['minWidth']));
      $checkMaxWidth = (array_key_exists('maxWidth', $meta) && !is_null($meta['maxWidth']));
      
      $messageSizeUnit = ($isPixelBased) ? 'px' : '';
      if($checkMinWidth && $length < $meta['minWidth']) {
          $this->addMessage($field, 'segmentToShort', 'Transunit length is '.$length.$messageSizeUnit.' minWidth is '.$meta['minWidth'].$messageSizeUnit);
          return false;
      }
      if($checkMaxWidth && $length > $meta['maxWidth']) {
          $this->addMessage($field, 'segmentToLong', 'Transunit length is '.$length.$messageSizeUnit.' maxWidth is '.$meta['maxWidth'].$messageSizeUnit);
          return false;
      }
      return true;
    }
}
