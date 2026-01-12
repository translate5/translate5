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

/**
 * SECTION TO INCLUDE PROGRAMMATIC LOCALIZATION
 * ============================================
 * $translate->_('Are you sure you want to delete prices for this language combination?');
 */

use MittagQI\Translate5\Plugins\MatchAnalysis\Models\Pricing\Preset;
use MittagQI\Translate5\Plugins\MatchAnalysis\Models\Pricing\PresetPrices;
use MittagQI\ZfExtended\MismatchException;

class editor_Plugins_MatchAnalysis_PricingpresetpricesController extends ZfExtended_RestController
{
    /**
     * Use trait
     */
    use editor_Controllers_Traits_ControllerTrait;

    /***
     * Should the data post/put param be decoded to associative array
     *
     * @var bool
     */
    protected bool $decodePutAssociative = true;

    /**
     * @var string
     */
    protected $entityClass = PresetPrices::class;

    /**
     * @var PresetPrices
     */
    public $entity;

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws MismatchException
     */
    public function init()
    {
        // Call parent
        parent::init();

        // If request contains json-encoded 'data'-param, decode it and append to request params
        $this->handleData();
    }

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws MismatchException
     */
    public function indexAction()
    {
        // Check presetId-param
        $_ = $this->jcheck([
            'presetId' => [
                'req' => true,
                'rex' => 'int11',
                'key' => Preset::class,
            ],
        ]);

        // Get ranges dict and prices
        $cols = $_['presetId']->getRangeColumns();
        $rows = $this->entity->getByPresetId($_['presetId']->getId());

        // Add to response
        $this->view->metaData = $cols;
        $this->view->rows = $rows;

        // Get total
        $this->view->total = count($this->view->rows);
    }

    /**
     * Clone prices-record to other pairs of source and target languages
     *
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function cloneAction()
    {
        // Check params
        try {
            // Check `match_analysis_pricing_preset_prices`-record we're going to clone - does exist
            $this->jcheck([
                'priceId' => [
                    'req' => true,
                    'rex' => 'int11',
                    'key' => $this->entity,
                ],
            ]);

            // Get combinations
            $validPairs = $this->_validPairs($this->entity->getPresetId());

            // Catch mismatch-exception
        } catch (MismatchException $e) {
            // Flush msg
            $this->jflush(false, $e->getMessage());
        }

        // Get array of clone-records
        $append = $this->entity->cloneFor($validPairs);

        // Do clone
        $this->jflush(true, [
            'append' => $append,
        ]);
    }

    /**
     * Get pairs of [$source - $target] languages that are valid to proceed
     * creating match_analysis_pricing_preset_prices-record for each of those pairs, so it
     * means there are no records already existing for any of valid pairs and
     * pairs does not same $source and $target within one pair
     *
     * @return array
     * @throws Zend_Db_Statement_Exception
     * @throws MismatchException
     */
    private function _validPairs($presetId)
    {
        // Get array of all `match_analysis_pricing_preset_prices`-records for a given presetId
        $_ = $this->jcheck([
            'presetId' => [
                'req' => true,
                'rex' => 'int11',
                'key' => 'match_analysis_pricing_preset_prices.presetId*',
            ],
        ], [
            'presetId' => $presetId,
        ]);

        // Get all source and target language ids pairs from there
        $done = [];
        foreach ($_['presetId'] as $prices) {
            $done[] = "{$prices['sourceLanguageId']}-{$prices['targetLanguageId']}";
        }

        // Validate sourceLanguageIds and targetLanguageIds params
        $_ = $this->jcheck([
            'sourceLanguageIds,targetLanguageIds' => [
                'req' => "true:Please specify at least one language",
                'rex' => 'int11list',
                'key' => 'LEK_languages+',
            ],
        ]);

        // Get list of source and target language ids combinations for which clones are planned to be created
        $sourceLanguageIds = array_column($_['sourceLanguageIds'], 'id');
        $targetLanguageIds = array_column($_['targetLanguageIds'], 'id');
        foreach ($sourceLanguageIds as $sourceLanguageId) {
            foreach ($targetLanguageIds as $targetLanguageId) {
                if ($sourceLanguageId != $targetLanguageId) {
                    $plan[] = "$sourceLanguageId-$targetLanguageId";
                }
            }
        }

        // Deduct combinations for which prices-records already exist
        if (! $todo = array_diff($plan, $done)) {
            $this->jflush(false, 'Pricing-records already exist for all combinations of languages you specified');
        }

        // Return valid combinations to proceed with
        return $todo;
    }

    /**
     * Create prices-record for given presetId and targetLanguageId params
     *
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function postAction()
    {
        // Check params
        try {
            // Check both params are pointing each to existing record
            $_ = $this->jcheck([
                'presetId' => [
                    'req' => true,
                    'rex' => 'int11',
                    'key' => Preset::class,
                ],
            ]);

            // If this preset is system default
            if ($_['presetId']->getName() == Preset::PRESET_SYSDEFAULT_NAME) {
                throw new editor_Plugins_MatchAnalysis_Exception('E1513');
            }

            // Get combinations
            $validPairs = $this->_validPairs($presetId = $_['presetId']->getId());

            // Catch mismatch-exception
        } catch (MismatchException $e) {
            // Flush msg
            $this->jflush(false, $e->getMessage());

            // Catch matchanalysis-exception
        } catch (editor_Plugins_MatchAnalysis_Exception $e) {
            // Log
            Zend_Registry::get('logger')
                ->cloneMe('plugin.matchanalysis')
                ->error($e->getErrorCode(), $e->getMessage());

            // Flush msg
            $this->jflush(false, $e->getMessage());
        }

        // If no records existing - create them
        $append = $this->entity->createFor($presetId, $validPairs);

        // Do clone
        $this->jflush(true, [
            'append' => $append,
        ]);
    }

    /**
     * Delete prices for certain language
     *
     * @throws Zend_Db_Statement_Exception
     * @throws MismatchException
     */
    public function deleteAction()
    {
        // Check preset exists, and if yes - load into $this->entity
        $this->jcheck([
            'pricesId' => [
                'req' => true,
                'rex' => 'int11',
                'key' => $this->entity,
            ],
        ]);

        // Prompt client-side confirmation
        $this->confirm('Are you sure you want to delete prices for this language combination?');

        // If confirmed - do delete
        $this->entity->delete();

        // Flush success
        $this->jflush(true);
    }

    /**
     * Update entry's currency and/or prices
     */
    public function putAction()
    {
        // Check params
        try {
            // Check whether `match_analysis_pricing_preset_prices`-record we're going to amend - does exist
            $_ = $this->jcheck([
                'pricesId' => [
                    'req' => true,
                    'rex' => 'int11',
                    'key' => $this->entity,
                ],
                'currency' => [
                    'req' => $this->hasParam('currency'),
                    'rex' => '~^[a-zA-Z0-9\$€£¥]{1,3}$~u',
                ],
                'noMatch' => [
                    'rex' => 'decimal154',
                ],
            ]);

            // Get all `match_analysis_pricing_preset_range`-records array for a preset
            $_ += $this->jcheck([
                'presetId' => [
                    'req' => true,
                    'rex' => 'int11',
                    'key' => 'match_analysis_pricing_preset_range.presetId*',
                ],
            ], $this->entity);

            // Get ids of languages for which prices are already defined
            $rangeIdA = array_column($_['presetId'], 'id');

            // Extract prices from json into an array
            $priceByRangeIdA = json_decode($this->entity->getPricesByRangeIds(), true);

            // Check prices
            foreach ($rangeIdA as $rangeId) {
                // If request has such param
                if ($this->hasParam($param = 'range' . $rangeId)) {
                    // Check param
                    $this->jcheck([
                        $param => [
                            'rex' => 'decimal154',
                        ],
                    ]);

                    // Overwrite value
                    $priceByRangeIdA[$rangeId] = (float) $this->getParam($param);
                }
            }

            // Update match_analysis_pricing_preset_price-record's props
            $this->entity->setPricesByRangeIds(json_encode($priceByRangeIdA));
            if ($this->hasParam('currency')) {
                $this->entity->setCurrency($this->getParam('currency'));
            }
            if ($this->hasParam('noMatch')) {
                $this->entity->setNoMatch($this->getParam('noMatch'));
            }

            // Catch mismatch-exception
        } catch (MismatchException $e) {
            // Flush msg
            $this->jflush(false, $e->getMessage());
        }

        // Save updated match_analysis_pricing_preset_price-record's props
        $this->entity->save();

        // Flush success
        $this->jflush(true, $this->entity->toGridData());
    }
}
