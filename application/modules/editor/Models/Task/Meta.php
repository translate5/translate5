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
use MittagQI\Translate5\Plugins\MatchAnalysis\Models\Pricing\Preset;
use MittagQI\Translate5\Task\Meta\TaskMetaDTO;

/**
 * Entity Model for task meta data
 * @method string getId()
 * @method void setId(int $id)
 * @method string getTaskGuid()
 * @method void setTaskGuid(string $guid)
 * @method string getTbxHash()
 * @method void setTbxHash(string $tbxHash)
 * @method null|string getMappingType()
 * @method void setMappingType(string $mappingType)
 * @method void setPricingPresetId(int $pricingPresetId)
 * @method string getVisualPdfWorkfile()
 * @method void setVisualPdfWorkfile(string $visualPdfWorkfile)
 */
class editor_Models_Task_Meta extends ZfExtended_Models_Entity_MetaAbstract
{
    protected $dbInstanceClass = 'editor_Models_Db_TaskMeta';

    protected $validatorInstanceClass = 'editor_Models_Validator_TaskMeta';

    public function getPerTaskExport(): bool
    {
        return $this->hasField('perTaskExport') && $this->get('perTaskExport');
    }

    public function setPerTaskExport(bool $perTaskExport): void
    {
        if ($this->hasField('perTaskExport')) {
            $this->set('perTaskExport', $perTaskExport);
        }
    }

    /**
     * @return Zend_Db_Table_Row_Abstract
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function loadByTaskGuid($taskGuid)
    {
        return $this->loadRow('taskGuid = ?', $taskGuid);
    }

    /**
     * Adds an empty meta data rowset to the DB.
     */
    public function initEmptyRowset()
    {
        $db = new $this->dbInstanceClass();

        /* @var $db Zend_Db_Table_Abstract */
        try {
            $db->insert([
                'taskGuid' => $this->getTaskGuid(),
            ]);
        } catch (Zend_Db_Statement_Exception $e) {
            try {
                $this->handleIntegrityConstraintException($e);
            } catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
                //"duplicate entry" errors are ignored.
                return;
            }
        }
    }

    /***
     * reste the tbxHash for given task
     * @param array $taskGuids
     * @return number
     */
    public function resetTbxHash(array $taskGuids)
    {
        if (empty($taskGuids)) {
            return 0;
        }

        return $this->db->update([
            'tbxHash' => '',
        ], [
            'taskGuid IN(?)' => $taskGuids,
        ]);
    }

    /**
     * FIXME this self mutation code must be eliminated!!!
     * Get id of a pricing preset to be used for pricing calculation for current task
     */
    public function getPricingPresetId()
    {
        // If no pricingPresetId defined (this may occur for tasks created before pricing-feature released)
        if (! $this->row->pricingPresetId) { // @phpstan-ignore-line
            // Load task and get it's customerId
            $task = ZfExtended_Factory::get(editor_Models_Task::class);
            $task->loadByTaskGuid($this->getTaskGuid());
            $customerId = $task->getCustomerId();

            // Get pricing preset id: either customer-specific, or system default detected by either isDefault-prop or name-prop
            $pricingPresetId = ZfExtended_Factory::get(Preset::class)->getDefaultPresetId($customerId);

            // Apply that for task meta
            $this->setPricingPresetId($pricingPresetId);
            $this->save();
        }

        // Return pricingPresetId
        return $this->row->pricingPresetId; // @phpstan-ignore-line
    }

    /**
     * Retrieves if a visual PDF-workfile is set
     */
    public function hasVisualPdfWorkfile(): bool
    {
        return ! empty($this->getVisualPdfWorkfile());
    }

    /**
     * @throws ZfExtended_Exception
     */
    public function toDTO(): TaskMetaDTO
    {
        if (empty($this->getTaskGuid())) {
            throw new ZfExtended_Exception('One can only get a DTO from a properly initialized Task-Meta');
        }

        return new TaskMetaDTO(
            $this->getTaskGuid(),
            $this->row->mappingType, // @phpstan-ignore-line
            editor_Utils::parseNullableInt($this->row->pricingPresetId), // @phpstan-ignore-line
            $this->getPerTaskExport()
        );
    }

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function setFromDTO(TaskMetaDTO $dto): void
    {
        if (empty($this->getTaskGuid())) {
            throw new ZfExtended_Exception('One can only set DTO-data for a properly initialized Task-Meta');
        }
        if ($this->getTaskGuid() !== $dto->taskGuid) {
            throw new ZfExtended_Exception('taskGuid mismatch when saving DTO to task-meta');
        }
        // we only update the value, when it differs from the Zend-default
        // otherwise we loose the database-default ...
        if ($this->row->mappingType !== $dto->mappingType) { // @phpstan-ignore-line
            $this->setMappingType($dto->mappingType);
        }
        // the other fields have null as default ...
        $this->setPricingPresetId($dto->pricingPresetId);
        $this->setPerTaskExport($dto->perTaskExport);
    }

    public function debug(): string
    {
        return 'TaskMeta: ' . print_r($this->row->toArray(), true);
    }
}
