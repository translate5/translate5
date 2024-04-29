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

namespace MittagQI\Translate5\Plugins\MatchAnalysis\Models\Pricing;

use editor_Models_Customer_Customer as Customer;
use editor_Models_Customer_Meta;
use Zend_Db_Statement_Exception;
use Zend_Db_Table_Row_Exception;
use ZfExtended_Exception;
use ZfExtended_Factory as Factory;
use ZfExtended_Models_Entity_Abstract;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_Entity_NotFoundException as NotFoundException;
use ZfExtended_NoAccessException;

/**
 * @method string getId()
 * @method void setId(int $id)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getUnitType()
 * @method void setUnitType(string $unitType)
 * @method string getIsDefault()
 * @method void setIsDefault(int $int)
 * @method string getDescription()
 * @method string getPriceAdjustment()
 * @method void setPriceAdjustment(float $priceAdjustment)
 * @method void setDescription(string $string)
 * @method string getCustomerId()
 * @method void setCustomerId(mixed $customerId)
 */
class Preset extends ZfExtended_Models_Entity_Abstract
{
    /**
     * The GUI-name of the system default pricing preset
     *
     * @var string
     */
    public const PRESET_SYSDEFAULT_NAME = 'Translate5-Standard';

    /**
     * Db instance class
     *
     * @var string
     */
    protected $dbInstanceClass = \MittagQI\Translate5\Plugins\MatchAnalysis\Models\Db\Pricing\Preset::class;

    /**
     * Customer, that current preset is related to, if related
     */
    private ?Customer $customer = null;

    /**
     * Retrieves if the preset is the system default preset
     */
    public function isSystemDefault(): bool
    {
        return ($this->getName() === self::PRESET_SYSDEFAULT_NAME);
    }

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function importDefaultWhenNeeded(): int
    {
        // Check (by name) whether we do already have system default
        $sysPresetRow = $this->db->fetchRow($this->db->select()->where('name = ?', self::PRESET_SYSDEFAULT_NAME));

        // If no, we have to create it
        if ($sysPresetRow == null) {
            // Create instance
            $sysPreset = new self();
            $sysPreset->setName(self::PRESET_SYSDEFAULT_NAME);
            $sysPreset->setDescription('The default pricing preset. Copy to customize ranges and prices. Or go to "Clients" and customize ranges and prices there.');

            // If we have no others having isDefault-flag
            if (! $this->db->fetchRow(['isDefault = 1'])) {
                // Set isDefault-flag for the instance we're going to create
                $sysPreset->setIsDefault(1);
            }

            // Do create
            $sysPreset->save();

            // Return it's id
            return $sysPreset->getId();
        }

        // Return existing default's id
        return $sysPresetRow->id;
    }

    /**
     * @param null $customerId
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws NotFoundException
     */
    public function getDefaultPresetId($customerId = null): int
    {
        // If customerId arg is given, try to load customer-specific default pricing preset
        if ($customerId) {
            // Get customer meta model
            $customerMeta = new editor_Models_Customer_Meta();

            try {
                $customerMeta->loadByCustomerId($customerId);
                if (! empty($customerMeta->getDefaultPricingPresetId())) {
                    return $customerMeta->getDefaultPricingPresetId();
                }
            } catch (NotFoundException $e) {
            }
        }

        // Try to load system default pricing preset by isDefault-flag
        try {
            $this->loadRow('isDefault = 1 AND ISNULL(`customerId`)');

            return $this->getId();
        } catch (NotFoundException $e) {
        }

        // Try to load system default pricing preset by name equal to self::PRESET_SYSDEFAULT_NAME
        try {
            $this->loadRow('name = ? ', self::PRESET_SYSDEFAULT_NAME);

            return $this->getId();
        } catch (NotFoundException $e) {
        }

        // If not found, generate it and return it's id
        return $this->importDefaultWhenNeeded();
    }

    /**
     * Retrieves the bound customers name (cached)
     *
     * @throws NotFoundException
     */
    public function getCustomerName(): ?string
    {
        if (empty($this->getCustomerId())) {
            return null;
        }
        if ($this->customer == null || $this->customer->getId() != $this->getCustomerId()) {
            $this->customer = Factory::get(Customer::class);
            $this->customer->load($this->getCustomerId());
        }

        return $this->customer->getName();
    }

    /**
     * Make current preset to be the system default non-customer preset.
     * Will reset any other non-customer default preset
     * Returns the ID of the former default (if any)
     *
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function setAsDefaultPreset(): int
    {
        // If it's a customer-level preset - throw an exception
        if ($this->getCustomerId() !== null) {
            throw new ZfExtended_Exception('Only presets not bound to a customer can be set as default preset');
        }

        // Value to be returned if no any other preset is a system default preset
        $wasDefaultId = 0;

        // Get current system default, if any
        $wasDefaultRow = $this->db->fetchRow($this->db->select()->where('customerId IS NULL AND isDefault = 1'));

        // If found
        if ($wasDefaultRow != null) {
            // Spoof value to be returned with found's id
            $wasDefaultId = $wasDefaultRow->id;

            // Clear isDefault-flag and save
            $wasDefaultRow->isDefault = 0;
            $wasDefaultRow->save();
        }

        // Set isDefault-flag and save
        $this->setIsDefault(1);
        $this->save();

        // Return prev default
        return $wasDefaultId;
    }

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Db_Table_Row_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_NoAccessException
     */
    public function delete()
    {
        // Prevent system default pricing preset from being deleted
        if ($this->isSystemDefault()) {
            throw new ZfExtended_NoAccessException('You can not delete the system default pricing preset.');
        }

        // Do delete
        try {
            $this->row->delete();
        } catch (Zend_Db_Statement_Exception $e) {
            $this->handleIntegrityConstraintException($e);
        }
    }

    /**
     * Retrieves the list to feed the presets grid view
     */
    public function getGridRows(): array
    {
        /* @var Preset $preset */
        foreach ($this->loadAllEntities() as $preset) {
            $data[] = $preset->toArray();
        }

        // Return
        return $data ?? [];
    }

    /**
     * Clone current preset under a new name and (optionally) customerId
     */
    public function clone($name, $customerId = null)
    {
        // Create and save clone
        $clone = new self();
        $clone->setName($name);
        if ($customerId) {
            $clone->setCustomerId($customerId);
        }
        $clone->setDescription($this->getDescription());
        $clone->setPriceAdjustment($this->getPriceAdjustment());
        $clone->setUnitType($this->getUnitType());
        $clone->save();

        // Clone ranges from current preset to cloned one
        $rangeClone = Factory
            ::get(PresetRange::class)
                ->cloneByPresetId($this->getId(), $clone->getId());

        /** @var PresetPrices $range */
        $prices = Factory::get(PresetPrices::class);

        // Get prices set up for this preset
        $pricesA = $prices->getByPresetId($this->getId());

        // Foreach
        foreach ($pricesA as $pricesI) {
            // Decode pricesByRangeIds-prop from json
            $pricesI['pricesByRangeIds'] = json_decode($pricesI['pricesByRangeIds'], true);

            // Clone pricesByRangeIds-prop, with spoofing orig ids with cloned ids
            $pricesByRangeIds = [];
            foreach ($rangeClone as $origId => $cloneId) {
                $pricesByRangeIds[$cloneId] = $pricesI['pricesByRangeIds'][$origId] ?? 0;
            }

            // Init prices's clone
            $prices->init([
                'presetId' => $clone->getId(),
                'sourceLanguageId' => $pricesI['sourceLanguageId'],
                'targetLanguageId' => $pricesI['targetLanguageId'],
                'currency' => $pricesI['currency'],
                'pricesByRangeIds' => json_encode($pricesByRangeIds),
                'noMatch' => $pricesI['noMatch'],
            ]);

            // Save it
            $prices->save();
        }

        // Return
        return $clone;
    }

    /**
     * Get ranges set up for this preset
     *
     * @throws Zend_Db_Statement_Exception
     */
    public function getRangeColumns(): array
    {
        return Factory::get(PresetRange::class)->getByPresetId($this->getId(), false);
    }

    /**
     * Create new preset which may belong to $customerId, if given,
     * and which have same set of ranges as system default preset has
     *
     * @param null $customerId
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function createWithDefaultRanges($name, $customerId = null): void
    {
        // Set basic props
        $this->setName($name);
        if ($customerId) {
            $this->setCustomerId($customerId);
        }

        // Save
        $this->save();

        // Get id of a system default preset
        $defaultId = $this->importDefaultWhenNeeded();

        // Clone ranges from system default preset to newly created (e.g. current) one
        Factory::get(PresetRange::class)->cloneByPresetId($defaultId, $this->getId());
    }
}
