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
use MittagQI\Translate5\Task\CustomField;

/**
 * @property CustomField $entity
 */
class editor_TaskcustomfieldController extends ZfExtended_RestController {

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
    protected $entityClass = CustomField::class;

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
     * Get validation rules, shared for POST and PUT
     *
     * @return array
     */
    private function getSharedRules() {
        return [
            'label' => [
                'req' => true,
                'rex' => 'json',
            ],
            'tooltip' => [
                'rex' => 'json',
            ],
            'type' => [
                'req' => true,
                'fis' => 'text,textarea,boolean,picklist',
            ],
            'picklistData' => [
                'req' => $this->getParam('type') === 'picklist',
                'rex' => 'json',
            ],
            'regex' => [
                'rex' => 'varchar255',
            ],
            'mode' => [
                'req' => true,
                'fis' => 'regular,required,readonly',
            ],
            'placesToShow' => [
                'req' => true,
                'set' => 'projectWizard,projectGrid,taskGrid',
            ],
            'position' => [
                'req' => true,
                'rex' => 'int11',
            ],
        ];
    }

    /**
     * Prepare data to feed grid
     *
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction() {
        $this->view->rows = $this->entity->getGridRows();
        $this->view->total = count($this->view->rows);
    }

    /**
     * Update custom field props
     *
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function putAction() {

        try {

            // Get validation rules, shared for POST and PUT
            $ruleA = $this->getSharedRules();

            // Check params
            $this->jcheck([
                'customFieldId' => [
                    'req' => true,
                    'rex' => 'int11',
                    'key' => $this->entity,
                ],
            ] + $ruleA);

        // Catch mismatch-exception
        } catch (ZfExtended_Mismatch $e) {

            // Flush msg
            $this->jflush(false, $e->getMessage());
        }

        // Assign props
        foreach (array_intersect_key($this->getAllParams(),
            array_flip(['label', 'tooltip', 'type', 'picklistData', 'regex', 'mode', 'placesToShow', 'position'])
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
     */
    public function postAction() {

        try {

            // Get validation rules, shared for POST and PUT
            $ruleA = $this->getSharedRules();

            // Check params
            $this->jcheck($ruleA);

        // Catch mismatch-exception
        } catch (ZfExtended_Mismatch $e) {

            // Flush msg
            $this->jflush(false, $e->getMessage());
        }

        // Assign props
        foreach (array_intersect_key($this->getAllParams(),
            array_flip(['label', 'tooltip', 'type', 'picklistData', 'regex', 'mode', 'placesToShow', 'position'])
        ) as $prop => $value) {
            $this->entity->{'set' . ucfirst($prop)}($value);
        }

        // Save assigned
        $this->entity->save();

        // Flush success
        $this->jflush(true, ['created' => $this->entity->toArray()]);
    }
}