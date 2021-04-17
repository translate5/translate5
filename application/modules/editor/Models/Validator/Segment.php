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

class editor_Models_Validator_Segment extends ZfExtended_Models_Validator_Abstract {
    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $segmentFieldManager;
    
    /**
     * @var editor_Models_Segment
     */
    protected $entity;
    
    /**
     * Segment Validator needs a instanced editor_Models_SegmentFieldManager
     * @param editor_Models_SegmentFieldManager $sfm
     */
    public function __construct(editor_Models_SegmentFieldManager $sfm, editor_Models_Segment $segment) {
        $this->segmentFieldManager = $sfm;
        parent::__construct($segment);
    }
    
    public function isValid($data) {
        if(parent::isValid($data)) {
            return true;
        }
        $messages = $this->getMessages();
        $errorCode = 'E1065';
        foreach($messages as $errors){
            //if the segment length is invalid we have to provide a separate error code for separate error level
            if(array_key_exists('segmentToShort', $errors) || array_key_exists('segmentToLong', $errors)){
                $errorCode = 'E1066';
                break;
            }
            if(array_key_exists('segmentTooManyLines', $errors) || array_key_exists('segmentLinesTooLong', $errors) || array_key_exists('segmentLinesTooShort', $errors)){
                $errorCode = 'E1259';
                break;
            }
        }
        
//FIXME this is an incomplete non perfect example how validations and exceptions should work
// create something like a ::createValidationResponse? The main problem is again the translation, since validations are in english and therefore not translatable
        throw new editor_Models_Segment_UnprocessableException($errorCode, ['errors' => $this->getMessages()]);
    }
    
    /**
     * Validators for Segment Entity
     * Validation will be done on calling entity->validate
     */
    protected function defineValidators() {
        $toValidate = $this->segmentFieldManager->getSortColMap();
        $config = Zend_Registry::get('config');
        
        foreach($toValidate as $edit => $toSort) {
            $this->addValidatorCustom($edit, function($value) use ($edit){
                return $this->validateLength($value, $edit);
            },true);
            //by default the edited and toSort fields don't have a length restriction, so we add it to the addDontValidateField
            // (ok, the MySQL length restriction for longtext fields,
            // but that should not bother the user in the frontend, since a segment will never get so long)
            $this->addDontValidateField($toSort);
        }
    
        $this->addValidator('userGuid', 'guid');
        $this->addValidator('userName', 'stringLength', array('min' => 0, 'max' => 255)); //es wird kein assoc Array benötigt, aber so ist besser lesbar
        $this->addValidator('taskGuid', 'guid');
        $this->addValidator('matchRate', 'between', array('min' => 0, 'max' => 104));
        $this->addValidator('matchRateType', 'stringLength', array('min' => 0, 'max' => 1084));
        $this->addValidator('workflowStepNr', 'int');
        
        /* simplest way to get the correct workflow here: */
        $session = new Zend_Session_Namespace();
        $workflow = ZfExtended_Factory::get('editor_Workflow_Manager')->getActive($session->taskGuid);
        /* @var $workflow editor_Workflow_Abstract */
        $this->addValidator('workflowStep', 'inArray', array($workflow->getSteps()));
        
        $allowedValues = array_keys($config->runtimeOptions->segments->stateFlags->toArray());
        $allowedValues[] = 0; //adding "not set" state
        $this->addValidator('stateId', 'inArray', array($allowedValues));
        
        $states = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
        /* @var $states editor_Models_Segment_AutoStates */
        $this->addValidator('autoStateId', 'inArray', array($states->getStates()));
    }
  
  /**
   * validates the given value of the given field with the values of the transunit,
   * - either by checking the segment and its siblings,
   * - or by checking the width and number of the lines in the segment
   * @param string $value
   * @param string $field
   * @return boolean
   */
  protected function validateLength($value, $field){
      $data = $this->entity->getDataObject();
      if(!property_exists($data, 'metaCache') || empty($data->metaCache)) {
          return true;
      }
      $meta = json_decode($data->metaCache, true);
      
      if (is_null($meta['minWidth']) && is_null($meta['maxWidth']) && is_null($meta['maxNumberOfLines'])) {
          return true;
      }
      
      
      if (array_key_exists('maxNumberOfLines',$meta) && !is_null($meta['maxNumberOfLines'])) {
          return $this->validateLengthForLines($value, $field);
      } else {
          return $this->validateLengthForSegmentAndSiblings($value, $field);
      }
  }
  
  /**
   * validates the given value of the given field against the max number and length of lines of the transunit
   * @param string $value
   * @param string $field
   * @return boolean
   */
  protected function validateLengthForLines($value, $field){
      $data = $this->entity->getDataObject();
      if(!property_exists($data, 'metaCache') || empty($data->metaCache)) {
          return true;
      }
      $meta = json_decode($data->metaCache, true);
      
      if (is_null($meta['maxNumberOfLines'])) {
          return true;
      }
      
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
              $length = (int)$this->entity->textLengthByMeta($line, $this->entity->meta(), $this->entity->getFileId());
              if ($checkMaxWidth && $length > $meta['maxWidth']) {
                  $errorsMaxWidth[] = ($key+1) . ': ' . $length;
              }
              if ($checkMinWidth && $length < $meta['minWidth']) {
                  $errorsMinWidth[] = ($key+1) . ': ' . $length;
              }
          }
          if (count($errorsMinWidth) > 0) {
              $this->addMessage($field, 'segmentLinesTooShort', 'Not all lines in the segment match the given minimal length: ' . implode('; ', $errorsMinWidth));
              $isValid = false;
          }
          if (count($errorsMaxWidth) > 0) {
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
  protected function validateLengthForSegmentAndSiblings($value, $field){
      $data = $this->entity->getDataObject();
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
          if($id == $this->entity->getId()) {
              //if the found sibling is the segment itself, use the length of the value to be stored
              $length += (int)$this->entity->textLengthByMeta($value, $this->entity->meta(), $this->entity->getFileId());
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