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

use PDO;
use Zend_Db_Statement_Exception;
use ZfExtended_Factory as Factory;
use ZfExtended_Models_Entity_Abstract;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;

/**
 * Class representing matchrate range for which a price can be defined, inside a certain pricing preset
 *
 * @method integer getId()
 * @method void setId(int $id)
 * @method integer getPresetId()
 * @method void setPresetId(int $presetId)
 * @method integer getFrom()
 * @method void setFrom(int $from)
 * @method integer getTill()
 * @method void setTill(int $till)
 */
class PresetRange extends ZfExtended_Models_Entity_Abstract {

    /*
     * A `match_analysis_pricing_preset_range`-entry has the following structure:
     {
        "id": "123",
        "presetId": "1",
        "from": "50",
        "till": "59",
    },
     */

    /**
     * Db instance class
     *
     * @var string
     */
    protected $dbInstanceClass = \MittagQI\Translate5\Plugins\MatchAnalysis\Models\Db\Pricing\PresetRange::class;

    /**
     * Get range-entries by given $presetId
     *
     * @param int $presetId
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getByPresetId(int $presetId, bool $byId = true) : array {
        return $this->db->getAdapter()->query('
            SELECT `id`, `from`, `till` 
            FROM `match_analysis_pricing_preset_range` 
            WHERE `presetId` = ?
            ORDER BY `till` DESC
        ', $presetId)->fetchAll($byId ? PDO::FETCH_UNIQUE : null);
    }

    /**
     * Get range-entries as [from => till] pairs by given $presetId
     *
     * @param int $presetId
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getPairsByPresetId(int $presetId) : array {
        return $this->db->getAdapter()->query('
            SELECT `from`, `till` 
            FROM `match_analysis_pricing_preset_range` 
            WHERE `presetId` = ?
            ORDER BY `till` ASC
        ', $presetId)->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * In addition to normal deletion, keys from JSON in `match_analysis_pricing_preset_prices`.`pricesByRangeIds` are deleted as well
     */
    public function delete() {

        // Shortcuts
        $presetId = $this->getPresetId();
        $rangeId  = $this->getId();

        // Call parent
        parent::delete();

        // Delete keys from JSON in `match_analysis_pricing_preset_prices`.`pricesByRangeIds`
        Factory::get(PresetPrices::class)->deleteBy($presetId, $rangeId);
    }

    /**
     * Add {.., $rangeId: 0.00, ..} into `pricesByRangeIds`-prop's JSON-value
     * for all `match_analysis_pricing_preset_prices`-records having same $presetId
     */
    public function onAfterInsert() {

        // Shortcuts
        $presetId = $this->getPresetId();
        $rangeId  = $this->getId();

        // Add rangeId-keys
        Factory::get(PresetPrices::class)->addRange($presetId, $rangeId);
    }

    /**
     * @param int $sourcePresetId
     * @param int $targetPresetId
     * @return array
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function cloneByPresetId(int $sourcePresetId, int $targetPresetId) : array {

        // Get ranges set up for $sourcePresetId
        $sourceRangeA = $this->getByPresetId($sourcePresetId);

        // [sourceRangeId => clonedRangeId] pairs
        $rangeClone = [];

        // Foreach source range
        foreach ($sourceRangeA as $sourceRangeId => $sourceRangeI) {

            // Init range's clone
            $this->init([
                'presetId' => $targetPresetId,
                'from' => $sourceRangeI['from'],
                'till' => $sourceRangeI['till'],
            ]);

            // Save it
            $this->save();

            // Remember id of a clone
            $rangeClone[$sourceRangeId] = $this->getId();
        }

        // Return [sourceRangeId => clonedRangeId] pairs
        return $rangeClone;
    }

    /**
     * Get value of `till`-prop of the range that is previous for the current one
     * so that we'll be able to make sure current one is not overlapping the previous one
     *
     * @throws Zend_Db_Statement_Exception
     */
    public function getPrevTill() {
        return $this->db->getAdapter()->query('
            SELECT `till`  
            FROM `match_analysis_pricing_preset_range` 
            WHERE `presetId` = ? AND `till` < ?
            ORDER BY `till` DESC
        ', [
            $this->getPresetId(),
            $this->getFrom()
        ])->fetchColumn();
    }

    /**
     * Get value of `from`-prop of the range that is next for the current one
     * so that we'll be able to make sure current one is not overlapping the next one
     *
     * @throws Zend_Db_Statement_Exception
     */
    public function getNextFrom() {
        return $this->db->getAdapter()->query('
            SELECT `from`  
            FROM `match_analysis_pricing_preset_range` 
            WHERE `presetId` = ? AND `from` > ?
            ORDER BY `from`
        ', [
            $this->getPresetId(),
            $this->getTill()
        ])->fetchColumn();
    }

    /**
     * Check whether current range is overlapping others having such presetId
     *
     * @throws Zend_Db_Statement_Exception
     */
    public function isOverlappingOthers() {
        return $this->db->getAdapter()->query('
            SELECT `id` 
            FROM `match_analysis_pricing_preset_range` 
            WHERE `presetId` = ? AND (
                      ? BETWEEN `from` AND `till` 
              OR      ? BETWEEN `from` AND `till`
              OR `from` BETWEEN ?      AND ? 
              OR `till` BETWEEN ?      AND ?
            )
        ', [
            $this->getPresetId(),
            $this->getFrom(), $this->getTill(),
            $this->getFrom(), $this->getTill(),
            $this->getFrom(), $this->getTill(),
        ])->fetchColumn();
    }
}
