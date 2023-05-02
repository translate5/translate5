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

use editor_Models_Languages as Languages;
use Zend_Db_Statement_Exception;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Factory as Factory;
use ZfExtended_Models_Entity_Abstract;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_Entity_NotFoundException;

/**
 * Class representing actual price-list in a certain currency for a pair of certain source and target languages
 *
 * @method integer getId()
 * @method void setId(int $id)
 * @method integer getPresetId()
 * @method void setPresetId(int $presetId)
 * @method int getSourceLanguageId()
 * @method void setSourceLanguageId(int $sourceLanguageId)
 * @method int getTargetLanguageId()
 * @method void setTargetLanguageId(int $targetLanguageId)
 * @method string getCurrency()
 * @method void setCurrency(string $currency)
 * @method float getNoMatch()
 * @method void setNoMatch(float $noMatch)
 * @method string getPricesByRangeIds()
 * @method void setPricesByRangeIds(string $pricesByRangeIds)
 *
 */
class PresetPrices extends ZfExtended_Models_Entity_Abstract {

    /*
     * A `match_analysis_pricing_preset_prices`-entry has the following structure:
     {
        "id": "123",
        "presetId": "1",
        "sourceLanguageId": "5",
        "targetLanguageId": "4",
        "currency": "USD",
        "pricesByRangeIds": ["rangeId1": 0.05, "rangeId2": 0.02, ...]
    },
     */

    /**
     * Db instance class
     *
     * @var string
     */
    protected $dbInstanceClass = \MittagQI\Translate5\Plugins\MatchAnalysis\Models\Db\Pricing\PresetPrices::class;

    /**
     * Get prices-entries by given $presetId
     *
     * @param int $presetId
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getByPresetId(int $presetId) : array {

        // Get prices-records
        $pricesA = $this->db->getAdapter()->query('
            SELECT * 
            FROM `match_analysis_pricing_preset_prices` 
            WHERE `presetId` = ?
        ', $presetId)->fetchAll();

        // Extract actual prices from pricesByRangeIds-prop
        foreach ($pricesA as &$prices) {
            $prices += $this->_extract($prices['pricesByRangeIds']);
        }

        // Return grid-ready data
        return $pricesA;
    }

    /**
     * Get pricing info by given $presetId, $sourceLanguageId and $targetLanguageId
     * Info looks like below: [
     *     'prices': [
     *        'range1From' => price1,
     *        'range2From' => price2,
     *        ...
     *      ],
     *     'currency' => '$',
     *     'priceAdjustment' => 123
     * ]
     *
     * @param int $presetId
     * @param int $sourceLanguageId
     * @param int $targetLanguageId
     * @return array
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function getPricesFor(int $presetId, int $sourceLanguageId, int $targetLanguageId) : array {

        // Get prices-records
        if ($prices = $this->db->getAdapter()->query('
            SELECT `currency`, `pricesByRangeIds`, `noMatch` 
            FROM `match_analysis_pricing_preset_prices` 
            WHERE `presetId` = ? 
              AND `sourceLanguageId` = ?
              AND `targetLanguageId` = ?
        ', [$presetId, $sourceLanguageId, $targetLanguageId])->fetch()) {

            // Decode json
            $byRangeId = json_decode($prices['pricesByRangeIds'], true);
        }

        // Get ranges
        $ranges = Factory::get(PresetRange::class)->getByPresetId($presetId);

        // Use `from` as range keys instead of `id`
        $byRangeFrom = [];
        foreach ($ranges as $rangeId => $rangeData) {
            $byRangeFrom[$rangeData['from']] = $byRangeId[$rangeId] ?? 0;
        }

        // Load preset
        $preset = Factory::get(Preset::class);
        $preset->load($presetId);

        // Return data
        return [
            'prices' => $byRangeFrom,
            'noMatch' => $prices['noMatch'] ?? 0,
            'noPricing' => !$prices,
            'currency' => $prices['currency'] ?? '',
            'unitType' => $preset->getUnitType(),
            'priceAdjustment' => $preset->getPriceAdjustment()
        ];
    }

    /**
     * Clone current record for other source and target language ids combinations
     *
     * @param array $combinations ['langId1-langId2', 'langId1-langId3', 'langId2-langId1', ...]
     * @return array
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function cloneFor(array $combinations) : array {

        // Prepare ctor
        $ctor = $this->toArray(); unset($ctor['id'], $ctor['sourceLanguageId'], $ctor['targetLanguageId']);

        // Cloned records will be here
        $cloned = [];

        // For each combination
        foreach ($combinations as $combination) {

            // Extract source and target language ids from combination
            list ($sourceLanguageId, $targetLanguageId) = explode('-', $combination);

            // Create clone for $sourceLanguageId and $targetLanguageId
            $clone = new self();
            $clone->init([
                'sourceLanguageId' => $sourceLanguageId,
                'targetLanguageId' => $targetLanguageId
            ] + $ctor);
            $clone->save();

            // Append to clones array
            $cloned []= $clone->toGridData();
        }

        // Return array of cloned records
        return $cloned;
    }

    /**
     * Save as toArray(), but prices from pricesByRangeIds-prop are extracted and appended as props with 'range'-prefix
     *
     * @return array
     */
    public function toGridData() {

        // Get initial array data
        $array = $this->toArray();

        // Append extracted prices
        $array += $this->_extract($array['pricesByRangeIds']);

        // Return data for grid
        return $array;
    }

    /**
     * Return array of extracted prices, that can be concatenated to array of main props
     *
     * @param string $json
     * @return array
     */
    private function _extract($json) {

        // Extracted prices will be here as values under rangeId (prefixed with 'range') as keys
        $extracted = [];

        // Extract prices
        foreach (json_decode($json, true) as $rangeId => $price) {
            $extracted["range$rangeId"] = $price;
        }

        // Return array of extracted prices, that can be concatenated to array of main props
        return $extracted;
    }

    /**
     * Get value of targetLanguageId-prop
     * If $instance arg is true - languages model instance will be returned
     *
     * @param string $type Either 'source' or 'target'
     * @param bool $instance
     * @return Languages|object
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function getLanguageId($type, $instance = false) {

        // Prepare getter method name
        $getter = $type == 'source' ? 'getSourceLanguageId' : 'getTargetLanguageId';

        // If $foreign arg is true
        if ($instance) {

            // Load and return foreign model instance
            $_ = Factory::get(Languages::class);
            $_->load($this->$getter());
            return $_;

        // Else
        } else {

            // Return as is
            return parent::$getter();
        }
    }

    /**
     * Create record for given $presetId and $targetLanguageId
     *
     * @param int $presetId
     * @param array $combinations
     * @return array
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function createFor(int $presetId, array $combinations) : array {

        // Shortcut
        $db = $this->db->getAdapter();

        // Array of created records
        $created = [];

        // Get config
        $rop = Zend_Registry::get('config')->runtimeOptions;

        // For each combination
        foreach ($combinations as $combination) {

            // Extract source and target language ids from combination
            list ($sourceLanguageId, $targetLanguageId) = explode('-', $combination);

            // Skip equal source and target language pair
            if ($sourceLanguageId == $targetLanguageId) {
                continue;
            }

            // Init new record
            $this->init([
                'presetId' => $presetId,
                'sourceLanguageId' => $sourceLanguageId,
                'targetLanguageId' => $targetLanguageId,
                'currency' => $rop->plugins->MatchAnalysis->pricing->defaultCurrency,
                'pricesByRangeIds' => $db->query('
                    SELECT JSON_OBJECTAGG(`id`, 0)
                    FROM `match_analysis_pricing_preset_range` 
                    WHERE `presetId` = ?
                ', $presetId)->fetchColumn() ?? '{}'
            ]);

            // Save it
            $this->save();

            // Create clone
            $clone = clone $this;

            // Prepare grid data for the created record
            $created []= $clone->toGridData();
        }

        // Return created records array in a format applicable to the grid
        return $created;
    }

    /**
     * Delete $rangeId-key from `pricesByRangeIds`-column's JSON-value all records having given $presetId
     *
     * @param int $presetId
     * @param int $rangeId
     */
    public function deleteBy(int $presetId, int $rangeId) : void {
        $this->db->getAdapter()->query('
            UPDATE `match_analysis_pricing_preset_prices` 
            SET `pricesByRangeIds` = JSON_REMOVE(`pricesByRangeIds`, \'$."' . $rangeId . '"\') 
            WHERE `presetId` = ?
        ', $presetId);
    }

    /**
     * Add $rangeId-key for `pricesByRangeIds`-column's JSON-value for all records having given $presetId
     *
     * @param $presetId
     * @param $rangeId
     */
    public function addRange(int $presetId, int $rangeId) : void {
        $this->db->getAdapter()->query('
            UPDATE `match_analysis_pricing_preset_prices` 
            SET `pricesByRangeIds` = JSON_SET(`pricesByRangeIds`, \'$."' . $rangeId . '"\', 0.0000) 
            WHERE `presetId` = ?
        ', $presetId);
    }
}
