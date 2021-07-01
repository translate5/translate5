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

class editor_Models_Validator_Task extends ZfExtended_Models_Validator_Abstract {
  
  /**
   * Validators for Task Entity
   */
  protected function defineValidators() {
    //comment = string, without length contrain. No validator needed / possible
    $this->addValidator('id', 'int');
    $this->addValidator('taskGuid', 'guid');
    $this->addValidator('taskNr', 'stringLength', array('min' => 0, 'max' => 120));
    $this->addValidator('foreignId', 'stringLength', array('min' => 0, 'max' => 120));
    $this->addValidator('taskName', 'stringLength', array('min' => 0, 'max' => 255));
    $this->addValidator('foreignName', 'stringLength', array('min' => 0, 'max' => 255));
    $this->addValidator('sourceLang', 'int');
    $this->addValidator('targetLang', 'int');
    $this->addValidator('relaisLang', 'int');
    $this->addDontValidateField('lockedInternalSessionUniqId');
    $this->addValidator('locked', 'date', array('Y-m-d H:i:s'));
    $this->addValidator('lockingUser', 'guid');
    $this->addValidator('state', 'inArray', array(array(editor_Models_Task::STATE_OPEN, editor_Models_Task::STATE_END, editor_Models_Task::STATE_UNCONFIRMED)));
    $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');
    /* @var $wfm editor_Workflow_Manager */
    $this->addValidator('workflow', 'inArray', [$wfm->getWorkflows()]);
    $this->addValidator('workflowStep', 'int');
    $this->addValidator('pmGuid', 'guid');
    $this->addValidator('pmName', 'stringLength', array('min' => 0, 'max' => 512));
    $this->addValidator('wordCount', 'int');
    $this->addValidator('orderdate', 'date', array('Y-m-d H:i:s'),true);
    $this->addValidator('referenceFiles', 'int');
    $this->addValidator('terminologie', 'int');
    $this->addValidator('edit100PercentMatch', 'int');
    $this->addValidator('lockLocked', 'int');
    $this->addValidator('enableSourceEditing', 'int');
    $this->addValidator('importAppVersion', 'stringLength', array('min' => 0, 'max' => 64));
    $this->addValidator('customerId', 'int');
    $this->addValidator('segmentCount', 'int');
    $this->addValidator('segmentFinishCount', 'int');
    $this->addValidator('taskType', 'inArray', [editor_Models_Task::getValidTaskTypes()]);
    $this->addValidator('usageMode', 'inArray', [[editor_Models_Task::USAGE_MODE_COMPETITIVE, editor_Models_Task::USAGE_MODE_COOPERATIVE, editor_Models_Task::USAGE_MODE_SIMULTANEOUS]]);
    $this->addValidator('projectId', 'int');
    $this->addValidator('diffExportUsable', 'int');
  }
}
