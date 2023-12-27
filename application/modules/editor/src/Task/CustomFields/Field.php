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
use Zend_Db_Table_Row_Exception;
use ZfExtended_Models_Entity_Abstract;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;

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
    protected $dbInstanceClass = \MittagQI\Translate5\Task\CustomFields\Db::class;

    protected $validatorInstanceClass = \MittagQI\Translate5\Task\CustomFields\Validator::class;

    /**
     * Add db column to the tasks table structure
     */
    public function onAfterInsert() {
        $this->alterTasksTable('insert', $this->getId());
    }

    /**
     * Drop db column from the tasks table structure
     */
    public function delete() {

        // Get id of a newly created custom field
        $id = $this->getId();

        // Call parent
        parent::delete();

        // Drop column from tasks table
        $this->alterTasksTable('delete', $id);
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
            $db->query("ALTER TABLE $table ADD COLUMN `customField$customFieldId` VARCHAR(1024) DEFAULT '' NOT NULL");

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
    public function loadAllSorted() {
        $s = $this->db->select();
        $s->order('position');
        return $this->loadFilterdCustom($s);
    }
}