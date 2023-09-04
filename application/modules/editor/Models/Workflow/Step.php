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
 * Workflow Step Entity Objekt
 *
 * @method integer getId()
 * @method void setId(int $id)
 * @method string getWorkflowName()
 * @method void setWorkflowName(string $workflowName)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getLabel()
 * @method void setLabel(string $label)
 * @method string getRole()
 * @method void setRole(string $role)
 * @method integer|null getPosition()
 * @method void setPosition(?integer $position)
 * @method bool getFlagInitiallyFiltered()
 * @method void setFlagInitiallyFiltered(bool $filter)
 */
class editor_Models_Workflow_Step extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass          = 'editor_Models_Db_Workflow_Step';
    
    /**
     * returns all steps of a workflow in the order to be processed (steps with position null are first!)
     * @param editor_Models_Workflow $workflow
     * @return array
     */
    public function loadByWorkflow(editor_Models_Workflow $workflow): array {
        $s = $this->db->select()
        ->where('workflowName = ?', $workflow->getName())
        ->order('position ASC')
        ->order('workflowName ASC');
        return $this->db->fetchAll($s)->toArray();
    }
    
}