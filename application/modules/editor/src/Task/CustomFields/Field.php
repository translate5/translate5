<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Task\CustomFields;

use Zend_Db_Statement_Exception;
use MittagQI\Translate5\Acl\TaskCustomField;
use ZfExtended_Models_Entity_Abstract;

/**
 *
 * @method integer getId()
 * @method void setId(int $id)
 * @method string getLabel()
 * @method void setLabel(string $label)
 * @method string getTooltip()
 * @method void setTooltip(string $tooltip)
 * @method string getType()
 * @method void setType(string $type)
 * @method string getComboboxData()
 * @method void setComboboxData(string $comboboxData)
 * @method string getRegex()
 * @method void setRegex(string $regex)
 * @method string getMode()
 * @method void setMode(string $mode)
 * @method string getPlacesToShow()
 * @method void setPlacesToShow(string $placesToShow)
 * @method string getPosition()
 * @method void setPosition(string $position)
 */
class Field extends ZfExtended_Models_Entity_Abstract {

    /**
     * Db instance class
     *
     * @var string
     */
    protected $dbInstanceClass = Db::class;

    protected $validatorInstanceClass = Validator::class;

    /**
     * Add db column to the tasks table structure
     */
    public function onAfterInsert() {
        $this->alterTasksTable('insert', $this->getId());
    }

    public function isReadOnly(): bool
    {
        return $this->getMode() === 'readonly';
    }

    /**
     * Drop db column from the tasks table structure
     */
    public function delete() : void {

        // Get id of a newly created custom field
        $id = $this->getId();

        // Call parent
        parent::delete();

        // Drop column from tasks table
        $this->alterTasksTable('delete', $id);

        // Get db adapter
        $this->db->getAdapter()->query(
            'DELETE FROM `Zf_acl_rules` WHERE `right` = ?', "customField$id"
        );
    }

    /**
     * Add/drop column into/from the tasks table structure
     *
     * @param string $customFieldEvent
     * @param int $customFieldId
     * @throws \ReflectionException
     * @throws \Zend_Db_Table_Exception
     */
    public function alterTasksTable(string $customFieldEvent, int $customFieldId) : void {

        // Get db adapter
        $db = $this->db->getAdapter();

        // Get tasks table name
        $table = \ZfExtended_Factory
            ::get(\editor_Models_Db_Task::class)
            ->info(\Zend_Db_Table_Abstract::NAME);

        // Add column
        if ($customFieldEvent === 'insert') {

            // Prepare DEFAULT value based on custom field type
            if ($this->getType() === 'combobox') {
                $default = array_keys(json_decode($this->getComboboxData(), true) ?? [])[0];
            } else if ($this->getType() === 'checkbox') {
                $default = 0;
            } else {
                $default = '';
            }

            // Do add column
            $db->query("ALTER TABLE $table ADD COLUMN `customField$customFieldId` VARCHAR(1024) DEFAULT '$default' NOT NULL");

        // Drop column
        } else if ($customFieldEvent === 'delete') {
            $db->query("ALTER TABLE $table DROP COLUMN `customField$customFieldId`");
        }

        // Clear cache
        \Zend_Registry::get('cache')->clean();
    }

    /**
     * Get all custom fields sorted by `position`
     *
     * @return array
     */
    public function loadAllSorted() : array {
        $s = $this->db->select();
        $s->order('position');
        return $this->loadFilterdCustom($s);
    }

    /**
     * Get roles for which custom field (current or having $id if given) is enabled for
     *
     * @param int|null $id
     * @return string[]
     * @throws Zend_Db_Statement_Exception
     */
    public function getRoles(?int $id = null) : array {

        // Use own id if not given by 1st arg
        if (!$id) {
            $id = $this->getId();
        }

        // Get roles from acl table
        return $this->db->getAdapter()
            ->query('SELECT `role` FROM `Zf_acl_rules` WHERE `right` = ?',"customField$id")
            ->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * @param ?string $roles
     * @throws Zend_Db_Statement_Exception
     */
    public function setRoles(?string $roles) : void {

        // Get db adapter
        $db = $this->db->getAdapter();

        // Get acl instance
        //$acl = \ZfExtended_Acl::getInstance();

        // Shortcuts
        $right = "customField" . $this->getId();
        $was = $this->getRoles();
        $now = strlen($roles) ? explode(',', $roles) : [];

        // Get diffs
        $ins = array_diff($now, $was);
        $del = array_diff($was, $now);

        // Get roles that were kept
        //$kept = array_diff($was, $del);

        // Get full list of roles, including roles auto auto_set_role-roles
        //$full = $acl->mergeAutoSetRoles($ins, $kept);

        // If some roles were removed - DELETE corresponding acl-records
        if ($del) $db->query(
            'DELETE FROM `Zf_acl_rules` WHERE FIND_IN_SET(`role`, ?) AND `right` = ?',
            [join(',', $del), $right]
        );

        //class_exists('editor_Utils');
        //i($acl->mergeAutoSetRoles($ins, array_diff($was, $del)), 'a');
        //i($acl->mergeAutoSetRoles(['clientpm'], ['editor']), 'a');

        // If some roles were added - INSERT corresponding acl-records
        foreach ($ins as $role) {
            $db->query('
                INSERT INTO `Zf_acl_rules` SET 
                  `module` = "editor", 
                  `role` = ?,
                  `resource` = ?, 
                  `right` = ? 
                ', [$role, TaskCustomField::ID, $right]
            );
        }
    }

    /**
     * Clear usages of options from LEK_task-table in case if those options were
     * deleted from custom field's combobox definition
     *
     * @param array $deletedOptions
     */
    public function clearComboboxOptionUsages(array $deletedOptions) : void {

        // Get db adapter
        $db = $this->db->getAdapter();

        // Get col name
        $column = 'customField' . $this->getId();

        // Prepare WHERE clause
        $where = $db->quoteInto("`$column` IN (?)", $deletedOptions);

        // Do clear
        $db->query("UPDATE `LEK_task` SET `$column` = '' WHERE $where");
    }
}