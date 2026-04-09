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

/**
 * Cachable definition of the workflow
 */
class editor_Workflow_CachableDefinition
{
    /**
     * The workflow name
     * @var string
     */
    public $name;

    /**
     * The workflow label (untranslated)
     * @var string
     */
    public $label;

    /**
     * labels of the states, roles and steps. Can be changed / added in constructor
     * @var array
     */
    #[\MittagQI\ZfExtended\Localization\LocalizableMsgArray]
    public $labels = [
        'STATE_IMPORT' => 'import',
        'STATE_WAITING' => 'waiting',
        'STATE_UNCONFIRMED' => 'unconfirmed',
        'STATE_FINISH' => 'finished',
        'STATE_AUTO_FINISH' => 'auto-closed',
        'STATE_OPEN' => 'open',
        'STATE_EDIT' => 'in work by myself',
        'STATE_VIEW' => 'opened by myself',
        'ROLE_TRANSLATOR' => 'Translator',
        'ROLE_REVIEWER' => 'Reviewer',
        'ROLE_TRANSLATORCHECK' => 'Second Reviewer',
        'ROLE_VISITOR' => 'visitor',
        'ROLE_VISUALAPPROVER' => 'Visual Approver',
        'STEP_NO_WORKFLOW' => 'No workflow',
        'STEP_PM_CHECK' => 'PM review',
        'STEP_WORKFLOW_ENDED' => 'Workflow finished',
        'STEP_REVIEWING' => 'Review',
        'STEP_TRANSLATORCHECK' => 'Second Review',
    ];

    /**
     * workflow steps which are part of the workflow chain (in this order)
     * @var array
     */
    public $stepChain = [];

    /**
     * Mapping between workflowSteps and roles
     * @var array
     */
    public $steps2Roles = [];

    /**
     * Loaded steps from DB, key is STEP_STEPNAME value is the step value (similar to the STEP_ constants)
     * @var array
     */
    public $steps = [];

    /**
     * list of steps with flag flagInitiallyFiltered on
     * @var array
     */
    public $stepsWithFilter = [];

    /**
     * Valid state / role combination for each step
     * the first state of the states array is also the default state for that step and role
     * @var array
     */
    public $validStates = [];
}
