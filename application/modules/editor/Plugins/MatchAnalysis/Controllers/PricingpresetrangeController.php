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

use MittagQI\Translate5\Plugins\MatchAnalysis\Models\Pricing\Preset;
use MittagQI\Translate5\Plugins\MatchAnalysis\Models\Pricing\PresetRange;
use MittagQI\ZfExtended\MismatchException;
use ZfExtended_Factory as Factory;
/**
 *
 */
class editor_Plugins_MatchAnalysis_PricingpresetrangeController extends ZfExtended_RestController {

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
    protected $entityClass = PresetRange::class;

    /**
     * @var PresetRange
     */
    public $entity;

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws MismatchException
     */
    public function init() {

        // Call parent
        parent::init();

        // If request contains json-encoded 'data'-param, decode it and append to request params
        $this->handleData();
    }

    /**
     * Create prices-record for given presetId and languageId params
     *
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function postAction() {

        // Check params
        try {

            // Check both params are pointing each to existing record
            $_ = $this->jcheck([
                'presetId' => [
                    'req' => true,
                    'rex' => 'int11',
                    'key' => Preset::class
                ],
                'from,till' => [
                    'req' => true,
                    'rex' => 'int11'
                ],
                'from' => [
                    'max' => $this->getParam('till')
                ],
                'till' => [
                    'min' => $this->getParam('from')
                ]
            ]);

            // If this preset is system default
            if ($_['presetId']->getName() == Preset::PRESET_SYSDEFAULT_NAME) {
                throw new editor_Plugins_MatchAnalysis_Exception('E1513');
            }

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

        // Create range-record
        $this->entity->init([
            'presetId' => $_['presetId']->getId(),
            'from' => $this->getParam('from'),
            'till' => $this->getParam('till'),
        ]);

        // Check whether this range will overlap others, and if so - flush failure
        if ($this->entity->isOverlappingOthers()) {
            $this->jflush(false, "The desired range will produce overlap with others in this preset");
        }

        // Save new range-record
        $this->entity->save();

        // Flush success
        $this->jflush(true, ['created' => $this->entity->getId()]);
    }

    /**
     * Delete pricing preset range
     *
     * @throws Zend_Db_Statement_Exception
     * @throws MismatchException
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws Zend_Exception
     */
    public function deleteAction() {

        try {

            // Check preset exists, and if yes - load into $this->entity
            $_ = $this->jcheck([
                'rangeIds' => [
                    'req' => true,
                    'rex' => 'int11list',
                    'key' => "match_analysis_pricing_preset_range+"
                ]
            ]);

            // Get id of system default preset
            $defaultId = Factory
                ::get(Preset::class)
                ->importDefaultWhenNeeded();

            // If at least one range among those we're going to delete - belongs to system default preset - flush failure
            foreach ($_['rangeIds'] as $range) {
                if ($range['presetId'] == $defaultId) {
                    throw new editor_Plugins_MatchAnalysis_Exception('E1513');
                }
            }

            // Prevent deletion of ranges related to more than one preset
            if (count(array_column($_['rangeIds'], 'presetId','presetId')) > 1) {
                throw new editor_Plugins_MatchAnalysis_Exception('E1514');
            }

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

        // If confirmed - do delete
        foreach ($_['rangeIds'] as $range) {
            $this->entity->load($range['id']);
            $this->entity->delete();
        }

        // Flush success
        $this->jflush(true, ['deleted' => array_column($_['rangeIds'], 'id')]);
    }

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function putAction() {

        // Check params
        try {

            // Check range exists, and if yes - load into $this->entity
            $this->jcheck([
                'rangeId' => [
                    'req' => true,
                    'rex' => 'int11',
                    'key' => $this->entity
                ]
            ]);

            // Get id of system default preset
            $defaultId = Factory::get(Preset::class)->importDefaultWhenNeeded();

            // If this preset is system default
            if ($this->entity->getPresetId() == $defaultId) {
                throw new editor_Plugins_MatchAnalysis_Exception('E1513');
            }

            // Limit
            $limit = [
                'max' => 104,
                'min' => 0
            ];

            // Check range exists, and if yes - load into $this->entity
            $this->jcheck([
                'from,till' => [
                    'req' => true,
                    'rex' => 'int11'
                ],
                'from' => [
                    'max' => min($this->getParam('till'), $limit['max']),
                    'min' => ($this->entity->getPrevTill() ?: -1) + 1
                ],
                'till' => [
                    'min' => max($this->getParam('from'), $limit['min']),
                    'max' => ($this->entity->getNextFrom() ?: $limit['max'] + 1) - 1
                ]
            ]);

        // Catch mismatch-exception
        } catch (MismatchException $e) {

            // Flush msg
            $this->jflush(false, $e->getMessage());
        }

        // Update range
        $this->entity->setFrom($this->getParam('from'));
        $this->entity->setTill($this->getParam('till'));
        $this->entity->save();

        // Flush success
        $this->jflush(true, ['updated' => $this->entity->toArray()]);
    }
}