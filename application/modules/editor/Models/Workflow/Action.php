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
 * Workflow_Action Entity Objekt
 * 
 * @method integer getId() getId()
 * @method void setId() setId(int $id)
 * @method string getWorkflow() getWorkflow()
 * @method void setWorkflow() setWorkflow(string $workflowId)
 * @method string getTrigger() getTrigger()
 * @method void setTrigger() setTrigger(string $trigger)
 * @method string getByRole() getByRole()
 * @method void setByRole() setByRole(string $role)
 * @method string getUserState() getUserState()
 * @method void setUserState() setUserState(string $state)
 * @method string getActionClass() getActionClass()
 * @method void setActionClass() setActionClass(string $class)
 * @method string getAction() getAction()
 * @method void setAction() setAction(string $action)
 * @method string getDescription() getDescription()
 * @method void setDescription() setDescription(string $description)
 */
class editor_Models_Workflow_Action extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass          = 'editor_Models_Db_Workflow_Action';
    
    /**
     * loads all available actions for the given trigger combination
     * @param array $workflowIds list of workflow ids to be considered
     * @param string $trigger
     * @param string $step
     * @param string $role
     * @param string $state
     * @return array
     */
    public function loadByTrigger(array $workflowIds, $trigger, $step, $role, $state) {
        $s = $this->db->select();
        $s->where('`workflow` in (?)', $workflowIds);
        $s->where('`trigger` = ?', $trigger);
        if(!empty($step)) {
            $s->where('`instep` = ? or `instep` is null', $step);
        }
        if(!empty($role)) {
            $s->where('`byrole` = ? or `byrole` is null', $role);
        }
        if(!empty($state)) {
            $s->where('`userState` = ? or `userState` is null', $state);
        }
        $s->order('position');
        $s->order('id');
        return $this->db->fetchAll($s)->toArray();   
    }
}