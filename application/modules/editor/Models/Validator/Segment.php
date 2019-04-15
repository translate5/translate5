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
            if(array_key_exists('segmentToShort', $errors) || array_key_exists('segmentToLong', $errors)){
                //if the segment length is invalid we have to provide a separate error code for separate error level
                $errorCode = 'E1066';
                break;
            }
        }
        throw editor_Models_Segment_UnprocessableException::createResponse($errorCode, $this->getMessages());
    }
    
    /**
     * Validators for Segment Entity
     * Validation will be done on calling entity->validate
     */
    protected function defineValidators() {
        $editable = $this->segmentFieldManager->getEditableDataIndexList();
        $toValidate = $this->segmentFieldManager->getSortColMap();
        $config = Zend_Registry::get('config');
        $contentLengthCheck = (boolean)$config->runtimeOptions->segments->enableCountSegmentLength;
        
        foreach($toValidate as $edit => $toSort) {
            //if $contentLengthCheck is enabled, we have to check the segment length against the configured min max values
            if($contentLengthCheck) {
                $this->addValidatorCustom($edit, function($value) use ($edit){
                    return $this->validateLength($value, $edit);
                },true);
            }
            else {
                $this->addDontValidateField($edit);
            }
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
        
        $this->setQualityValidator(array_keys($config->runtimeOptions->segments->qualityFlags->toArray()));
        
        $allowedValues = array_keys($config->runtimeOptions->segments->stateFlags->toArray());
        $allowedValues[] = 0; //adding "not set" state
        $this->addValidator('stateId', 'inArray', array($allowedValues));
        
        $states = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
        /* @var $states editor_Models_Segment_AutoStates */
        $this->addValidator('autoStateId', 'inArray', array($states->getStates()));
    }
  
  protected function setQualityValidator(array $allowedValues) {
    $inArray = $this->validatorFactory('inArray', array($allowedValues));
    $me = $this;
    $qmIdValidator = function($value) use($inArray, $me) {
      $value = explode(';', trim($value, ';'));
      foreach($value as $oneValue){
        if((strlen($oneValue) > 0) && !$inArray->isValid($oneValue)) {
          $me->addMessage('qmId', 'invalidQmId', 'invalidQmId');
          return false;
        }
      }
      return true;
    };
    
    $this->addValidatorCustom('qmId', $qmIdValidator);
  }
  
  /**
   * validates the given value of the given field with the sibling length agains the min and max values of the transunit
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
              $length += (int)$this->entity->textLengthByMeta($value, $this->entity->meta());
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
      
      $messageSizeUnit = ($isPixelBased) ? 'px' : '';
      if(array_key_exists('minWidth', $meta) && $length < $meta['minWidth']) {
          $this->addMessage($field, 'segmentToShort', 'Transunit length is '.$length.$messageSizeUnit.' minWidth is '.$meta['minWidth'].$messageSizeUnit);
          return false;
      }
      if(array_key_exists('maxWidth', $meta)&& !empty($meta['maxWidth']) && $length > $meta['maxWidth']) {
          $this->addMessage($field, 'segmentToLong', 'Transunit length is '.$length.$messageSizeUnit.' maxWidth is '.$meta['maxWidth'].$messageSizeUnit);
          return false;
      }
      return true;
  }
}