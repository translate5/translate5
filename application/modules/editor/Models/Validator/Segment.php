<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3
			 http://www.gnu.org/licenses/agpl.html

END LICENSE AND COPYRIGHT
*/

class editor_Models_Validator_Segment extends ZfExtended_Models_Validator_Abstract {
    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $segmentFieldManager;
    
    /**
     * Segment Validator needs a instanced editor_Models_SegmentFieldManager
     * @param editor_Models_SegmentFieldManager $sfm
     */
    public function __construct(editor_Models_SegmentFieldManager $sfm) {
        $this->segmentFieldManager = $sfm;
        parent::__construct();
    }
    
    /**
     * Validators for Segment Entity
     * Validation will be done on calling entity->validate
     */
    protected function defineValidators() {
        $editable = $this->segmentFieldManager->getEditableDataIndexList();
        $toValidate = $this->segmentFieldManager->getSortColMap();
        foreach($toValidate as $edit => $toSort) {
            //edited = string, ohne längenbegrenzung. Daher kein Validator nötig / möglich 
            $this->addDontValidateField($edit);
            $length = editor_Models_Segment::TOSORT_LENGTH;
            $this->addValidator($toSort, 'stringLength', array('min' => 0, 'max' => $length)); //es wird kein assoc Array benötigt, aber so ist besser lesbar; stringlenght auf 300 statt 100 um auch Multibyte-Strings prüfen zu können ohne iconv_set_encoding('internal_encoding', 'UTF-8'); setzen zu müssen
        }
    
        $this->addValidator('userGuid', 'guid');
        $this->addValidator('userName', 'stringLength', array('min' => 0, 'max' => 255)); //es wird kein assoc Array benötigt, aber so ist besser lesbar
        $this->addValidator('taskGuid', 'guid');
        $this->addValidator('matchRate', 'between', array('min' => 0, 'max' => 100));
        $this->addValidator('matchRateType', 'stringLength', array('min' => 0, 'max' => 60));
        $this->addValidator('workflowStepNr', 'int');
        
        $workflow = ZfExtended_Factory::get('editor_Workflow_Manager')->getActive();
        /* @var $workflow editor_Workflow_Abstract */
        $this->addValidator('workflowStep', 'inArray', array($workflow->getSteps()));
        
        $session = new Zend_Session_Namespace();
        $flagConfig = $session->runtimeOptions->segments;
    
        $this->setQualityValidator(array_keys($flagConfig->qualityFlags->toArray()));
        
        $allowedValues = array_keys($flagConfig->stateFlags->toArray());
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
}