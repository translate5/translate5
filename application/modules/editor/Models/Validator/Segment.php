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

class editor_Models_Validator_Segment extends ZfExtended_Models_Validator_Abstract {
    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $segmentFieldManager;
    
    /**
     * @var array
     */
    protected $dbMetaData = null;
    
    /**
     * Segment Validator needs a instanced editor_Models_SegmentFieldManager
     * @param editor_Models_SegmentFieldManager $sfm
     */
    public function __construct(editor_Models_SegmentFieldManager $sfm) {
        $this->segmentFieldManager = $sfm;
        parent::__construct();
    }
    
    /**
     * sets the DbMetaData of the entity for internal reusage
     */
    public function setDbMetaData(array $md) {
      $this->dbMetaData = $md;
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
            //FIXME den toSort length anstatt als config als const festlegen, da in der DB ja auch fix definiert.
            $length = $this->dbMetaData[$toSort]['LENGTH'];
            $this->addValidator($toSort, 'stringLength', array('min' => 0, 'max' => $length)); //es wird kein assoc Array benötigt, aber so ist besser lesbar; stringlenght auf 300 statt 100 um auch Multibyte-Strings prüfen zu können ohne iconv_set_encoding('internal_encoding', 'UTF-8'); setzen zu müssen
        }
    
        $this->addValidator('userGuid', 'guid');
        $this->addValidator('userName', 'stringLength', array('min' => 0, 'max' => 255)); //es wird kein assoc Array benötigt, aber so ist besser lesbar
        $this->addValidator('taskGuid', 'guid');
        $this->addValidator('matchRate', 'between', array('min' => 0, 'max' => 100));
        $this->addValidator('workflowStepNr', 'int');
        
        $workflow = ZfExtended_Factory::get('editor_Workflow_Default');
        /* @var $workflow editor_Workflow_Default */
        $this->addValidator('workflowStep', 'inArray', array($workflow->getSteps()));
        
        $session = new Zend_Session_Namespace();
        $flagConfig = $session->runtimeOptions->segments;
    
        $this->setQualityValidator(array_keys($flagConfig->qualityFlags->toArray()));
        
        $allowedValues = array_keys($flagConfig->stateFlags->toArray());
        $this->addValidator('stateId', 'inArray', array($allowedValues));
        
        $states = ZfExtended_Factory::get('editor_Models_SegmentAutoStates');
        /* @var $states editor_Models_SegmentAutoStates */
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