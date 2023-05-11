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
/**
 * REST Endpoint Controller to serve the presets list for the pricing-Management in the Preferences
 *
 * @property Preset $entity
 */
class editor_Plugins_MatchAnalysis_PricingpresetController extends ZfExtended_RestController {

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
    protected $entityClass = Preset::class;

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Mismatch
     */
    public function init() {

        // Call parent
        parent::init();

        // If request contains json-encoded 'data'-param, decode it and append to request params
        $this->handleData();
    }

    /**
     * Prepare data to feed presets grid
     *
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction() {

        // Get rows and total
        $this->view->rows = $this->entity->getGridRows();
        $this->view->total = count($this->view->rows);

        // Auto-import of default-preset: when there are no rows we can assume the feature
        // was just installed and the DB is empty then we automatically add the system default preset
        if ($this->view->total < 1) {

            // Do import
            $this->entity->importDefaultWhenNeeded();

            // Re-get rows and total
            $this->view->rows = $this->entity->getGridRows();
            $this->view->total = count($this->view->rows);
        }
    }

    /**
     * Delete preset
     *
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Db_Table_Row_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_NoAccessException
     * @throws Zend_Exception
     */
    public function deleteAction() {

        // Check params
        try {

            // Check preset exists, and if yes - load into $this->entity
            $this->jcheck([
                'presetId' => [
                    'req' => true,
                    'rex' => 'int11',
                    'key' => $this->entity
                ]
            ]);

            // If this preset is default
            if ($this->entity->getName() == Preset::PRESET_SYSDEFAULT_NAME || $this->entity->getIsDefault()) {
                throw new editor_Plugins_MatchAnalysis_Exception('E1513');
            }

        // Catch mismatch-exception
        } catch (ZfExtended_Mismatch $e) {

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

        // Prompt client-side confirmation
        $this->confirm("Are you sure you want to delete pricing preset '{$this->entity->getName()}'?");

        // If confirmed - do delete
        $this->entity->delete();

        // Flush success
        $this->jflush(true);
    }

    /**
     * Clone preset
     *
     * @throws Zend_Db_Statement_Exception
     */
    public function cloneAction() {

        // Check params
        try {
            $this->jcheck([
                'presetId' => [
                    'req' => true,                                                      // required
                    'rex' => 'int11',                                                   // regular expression preset key or raw expression
                    'key' => $this->entity,                                             // points to existing record in a given db table
                ],
                'name' => [
                    'req' => true,                                                      // required
                    'rex' => 'varchar255',                                              // regular expression preset key or raw expression
                    'key' => '!match_analysis_pricing_preset.name:Preset having such name already exists',  // ensure there is no record in a given db table having such name
                ],
                'customerId' => [
                    'rex' => 'int11',                                                   // regular expression preset key or raw expression
                    'key' => 'LEK_customer',                                            // points to existing record in a given db table
                ]
            ]);

        // Catch mismatch-exception
        } catch (ZfExtended_Mismatch $e) {

            // Flush msg
            $this->jflush(false, $e->getMessage());
        }

        // Get params
        $name = $this->getParam('name');
        $customerId = $this->getParam('customerId');

        // Do clone
        $this->jflush(true, ['clone' => $this->entity->clone($name, $customerId)->toArray()]);
    }

    /**
     * Update certain preset own props
     *
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function putAction() {

        // Check params
        try {
            $this->jcheck([
                'presetId' => [
                    'req' => true,
                    'rex' => 'int11',
                    'key' => $this->entity,
                ],
                'name' => [
                    'req' => array_key_exists('name', $this->getAllParams()),
                    'rex' => 'varchar255',
                ],
                'unitType' => [
                    'fis' => 'word,character',
                ],
                'description' => [
                    'rex' => 'varchar255',
                ],
                'priceAdjustment' => [
                    'rex' => 'decimal112'
                ],
                'isDefault' => [
                    'rex' => 'bool',
                ],
            ]);

            // If this preset is system default
            if ($this->entity->getName() == Preset::PRESET_SYSDEFAULT_NAME) {
                throw new editor_Plugins_MatchAnalysis_Exception('E1513');
            }

            // Custom error msg for 'key'-rule
            $err = 'Preset having such name already exists';

            // If we're going to rename preset
            if ($this->getParam('name') != $this->entity->getName()) {

                // Make sure new name is NOT used by any other preset
                $this->jcheck([
                    'name' => [
                        'key' => "!match_analysis_pricing_preset.name:$err",
                    ]
                ]);
            }

        // Catch mismatch-exception
        } catch (ZfExtended_Mismatch $e) {

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

        // Assign props
        foreach (array_intersect_key(
            $this->getAllParams(),
            array_flip(['name', 'unitType', 'description', 'priceAdjustment'])
        ) as $prop => $value) {
            $this->entity->{'set' . ucfirst($prop)}($value);
        }

        // Save assigned
        $this->entity->save();

        // Flush success
        $this->jflush(true, ['updated' => $this->entity->toArray()]);
    }

    /**
     * Create new preset
     *
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function postAction() {

        // Check params
        try {

            // Check name-param is given and is not used by any other preset so far
            $this->jcheck([
                'name' => [
                    'req' => 'true:Please specify preset name',
                    'rex' => 'varchar255',
                    'key' => "!match_analysis_pricing_preset.name:Preset having such name already exists",
                ],
                'customerId' => [
                    'rex' => 'int11',                                                   // regular expression preset key or raw expression
                    'key' => 'LEK_customer',                                            // points to existing record in a given db table
                ]
            ]);

        // Catch mismatch-exception
        } catch (ZfExtended_Mismatch $e) {

            // Flush msg
            $this->jflush(false, $e->getMessage());
        }

        // Create new preset instance having default set of ranges
        $this->entity->createWithDefaultRanges(
            $this->getParam('name'),
            $this->getParam('customerId')
        );

        // Flush success
        $this->jflush(true, ['created' => $this->entity->toArray()]);
    }

    /**
     * Sets the non-customer/common default preset
     *
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function setdefaultAction(){

        // Check params
        try {
            $this->jcheck([
                'presetId' => [
                    'req' => true,
                    'rex' => 'int11',
                    'key' => $this->entity,
                ]
            ]);

        // Catch mismatch-exception and flush msg
        } catch (ZfExtended_Mismatch $e) {
            $this->jflush(false, $e->getMessage());
        }

        // Set current preset to be default, and get id of the one that was default
        $this->view->wasDefault = $this->entity->setAsDefaultPreset();
    }
}