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
/**
 * Workflow_Userpref Entity Objekt
 * 
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method integer getTaskGuid() getTaskGuid()
 * @method void setTaskGuid() setTaskGuid(string $taskGuid)
 * @method integer getWorkflowStep() getWorkflowStep()
 * @method void setWorkflowStep() setWorkflowStep(string $step)
 * @method integer getAnonymousCols() getAnonymousCols()
 * @method void setAnonymousCols() setAnonymousCols(boolean $anon)
 * @method integer getVisibility() getVisibility()
 * @method void setVisibility() setVisibility(string $vis)
 * @method integer getUserGuid() getUserGuid()
 * @method void setUserGuid() setUserGuid(string $userGuid)
 * @method integer getFields() getFields()
 * @method void setFields() setFields(string $userGuid)
 */
class editor_Models_Workflow_Userpref extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass          = 'editor_Models_Db_Workflow_Userpref';
    protected $validatorInstanceClass   = 'editor_Models_Validator_Workflow_Userpref';

    /**
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::save()
     */
    public function save() {
        //ensure that both values are null in the DB
        if(empty($this->row->userGuid)) {
            $this->row->userGuid = null;
        }
        if(empty($this->row->workflowStep)) {
            $this->row->workflowStep = null;
        }
        parent::save();
    }
    
    protected function validatorLazyInstatiation() {
        parent::validatorLazyInstatiation();
        $this->validator->setTaskGuid($this->getTaskGuid());
    }
    
    /**
     * returns true if this is the default entry
     * @return boolean
     */
    public function isDefault() {
        $step = $this->getWorkflowStep();
        $userGuid = $this->getUserGuid();
        return empty($step) && empty($userGuid);
    }
}