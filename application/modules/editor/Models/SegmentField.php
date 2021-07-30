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
 * Entity Model for segment fields
 * @method integer getId() getId()
 * @method void setId() setId(int $id)
 * @method string getTaskGuid() getTaskGuid()
 * @method void setTaskGuid() setTaskGuid(string $guid)
 * @method string getName() getName()
 * @method void setName() setName(string $name)
 * @method string getType() getType()
 * @method void setType() setType(string $type)
 * @method string getLabel() getLabel()
 * @method void setLabel() setLabel(string $label)
 * @method string getWidth() getWidth()
 * @method void setWidth() setWidth(string $width)
 * @method boolean getRankable() getRankable()
 * @method void setRankable() setRankable(bool $rankable)
 * @method boolean getEditable() getEditable()
 * @method void setEditable() setEditable(bool $editable)
 */
class editor_Models_SegmentField extends ZfExtended_Models_Entity_Abstract {
    //consts also defined in GUI Model Editor.model.segment.Field 
    const TYPE_SOURCE = 'source';
    const TYPE_TARGET = 'target';
    const TYPE_RELAIS = 'relais';
    
    protected $dbInstanceClass = 'editor_Models_Db_SegmentField';

    /**
     * loads all segment fields for the given taskGuid as rowset
     * @param string $taskGuid
     * @return Zend_Db_Table_Rowset
     */
    public function loadByTaskGuidAsRowset($taskGuid) {
        $s = $this->db->select()
            ->where('taskGuid = ?', $taskGuid)
            ->order('id ASC');
        return $this->db->fetchAll($s);
    }
    
    /**
     * loads all segment fields for the given taskGuid
     * @param string $taskGuid
     * @return array
     */
    public function loadByTaskGuid($taskGuid) {
        return $this->loadByTaskGuidAsRowset($taskGuid)->toArray();
    }
    
    /**
     * loads the fields by the userprefs defined for the given taskGuid, userGuid and workflowStep
     * @param editor_Models_Workflow_Userpref $userPref
     */
    public function loadByUserPref(editor_Models_Workflow_Userpref $userPref) {
        $allFields = $this->loadByTaskGuid($userPref->getTaskGuid());
        $fields = explode(',', $userPref->getFields());
        $result = array();
        $anon = 'A';
        foreach($allFields as $field) {
            if(! in_array($field['name'], $fields)) {
                continue;
            }
            if($userPref->getAnonymousCols() && $field['type'] == editor_Models_SegmentField::TYPE_TARGET) {
                $field['label'] = 'Spalte '.($anon++);
            }
            $result[] = $field;
        }
        return $result;
        //FIXME TaskController Logic für diesen Aufruf beim Export der QM Statistics bzw. eigentlich bei jedem bisherigen loadByTaskGuid adaptieren!
    }
    
    /**
     * Segment Fields are internally processed as ZendRowObjects, so we need a way to get it.
     * @return Zend_Db_Table_Row_Abstract
     */
    public function getRowObject() {
        return $this->row;
    }
} 