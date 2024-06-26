<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Workflow_Userpref Entity Objekt
 *
 * @method string getId()
 * @method void setId(int $id)
 * @method string getTaskGuid()
 * @method void setTaskGuid(string $taskGuid)
 * @method string getWorkflowStep()
 * @method void setWorkflowStep(string $step)
 * @method string getNotEditContent()
 * @method void setNotEditContent(bool $cannot)
 * @method string getAnonymousCols()
 * @method void setAnonymousCols(bool $anon)
 * @method string getVisibility()
 * @method void setVisibility(string $vis)
 * @method string getUserGuid()
 * @method void setUserGuid(string $userGuid)
 * @method string getFields()
 * @method void setFields(string $userGuid)
 * @method string getTaskUserAssocId()
 * @method void setTaskUserAssocId(int $taskUserAssocId)
 */
class editor_Models_Workflow_Userpref extends ZfExtended_Models_Entity_Abstract
{
    public const VIS_SHOW = 'show';

    public const VIS_HIDE = 'hide';

    public const VIS_DISABLE = 'disable';

    protected $dbInstanceClass = 'editor_Models_Db_Workflow_Userpref';

    protected $validatorInstanceClass = 'editor_Models_Validator_Workflow_Userpref';

    /**
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::save()
     */
    public function save()
    {
        //ensure that both values are null in the DB
        if (empty($this->row->userGuid)) {
            $this->row->userGuid = null;
        }
        if (empty($this->row->workflowStep)) {
            $this->row->workflowStep = null;
        }
        parent::save();
    }

    protected function validatorLazyInstatiation()
    {
        parent::validatorLazyInstatiation();
        $this->validator->setTaskGuid($this->getTaskGuid());
    }

    /**
     * returns true if this is the default entry
     * @return boolean
     */
    public function isDefault()
    {
        $step = $this->getWorkflowStep();
        $userGuid = $this->getUserGuid();

        return empty($step) && empty($userGuid);
    }

    /**
     * loads the userprefs defined for the given parameters (at least the default entry)
     * @param string $taskGuid
     * @param string $workflow
     * @param string $userGuid
     * @param string $workflowStep
     * @return Zend_Db_Table_Row_Abstract|null
     */
    public function loadByTaskUserAndStep($taskGuid, $workflow, $userGuid, $workflowStep)
    {
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid)
            ->where('workflow = ?', $workflow)
            ->where('(userGuid = ? or userGuid is null)', $userGuid)
            ->where('(workflowStep = ? or workflowStep is null)', $workflowStep)
            ->order(['workflowStep DESC', 'userGuid DESC'])
            ->limit(1);

        return $this->loadRowBySelect($s);
    }
}
