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
 * abstract base class for all workflow handler classes
 */
abstract class editor_Workflow_Default_AbstractHandler
{
    protected editor_Workflow_Actions_Config $config;

    /**
     * executes desired handler/trigger action
     * @return string|null returns the trigger name
     */
    abstract public function execute(editor_Workflow_Actions_Config $actionConfig): ?string;

    /**
     * calls the actions configured to the trigger with given role and state
     * @param string $step task step filterm can be empty
     * @param string $role job step filter can be empty
     * @param string $state job state filter can be empty
     *
     * @uses editor_Workflow_Notification::notifyAllFinishOfARole()
     * @uses editor_Workflow_Actions::removeCompetitiveUsers()
     * @uses editor_Workflow_Actions::cleanOldPackageExports()
     * @uses editor_Workflow_Actions::triggerCallbackAction()
     * @uses editor_Workflow_Actions::confirmCooperativeUsers()
     * @uses editor_Workflow_Actions::deleteOldEndedTasks()
     * @uses editor_Workflow_Actions::endTask()
     * @uses editor_Workflow_Actions::finishOverduedTaskUserAssoc()
     * @uses editor_Workflow_Actions::removeOldConnectorUsageLog()
     * @uses editor_Workflow_Actions::segmentsSetInitialState()
     * @uses editor_Workflow_Actions::segmentsSetUntouchedState()
     * @uses editor_Workflow_Actions::setDefaultDeadlineDate()
     */
    protected function callActions(editor_Workflow_Actions_Config $config, $step = null, $role = null, $state = null)
    {
        $actions = ZfExtended_Factory::get('editor_Models_Workflow_Action');
        /* @var $actions editor_Models_Workflow_Action */
        $debugData = [
            'trigger' => $config->trigger,
            'step' => $step,
            'role' => $role,
            'state' => $state,
        ];
        $actions = $actions->loadByTrigger([$config->workflow->getName()], $config->trigger, $step, $role, $state);
        $this->actionDebugMessage([$config->workflow->getName()], $debugData);
        $instances = [];
        foreach ($actions as $action) {
            $class = $action['actionClass'];
            $method = $action['action'];
            $config->parameters = $this->decodeParameters($config, $action);
            if (empty($instances[$class])) {
                $instance = ZfExtended_Factory::get($class);
                /* @var $instance editor_Workflow_Actions_Abstract */
                $instance->init($config);
                $instances[$class] = $instance;
            } else {
                $instance = $instances[$class];
            }

            $this->actionDebugMessage($action, $debugData);
            if (is_null($config->parameters)) {
                call_user_func([$instance, $method]);
            } else {
                call_user_func([$instance, $method], $config->parameters);
            }
        }
    }

    protected function decodeParameters(editor_Workflow_Actions_Config $config, array $action): ?stdClass
    {
        if (empty($action['parameters'])) {
            return null;
        }

        try {
            return json_decode($action['parameters'], flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $config->workflow->getLogger($config->task)->error('E1171', 'Workflow Action: JSON Parameters for workflow action call could not be parsed with message: {msg}', [
                'msg' => $e->getMessage(),
                'action' => $action,
            ]);
        }

        return null;
    }

    /**
     * generates a debug message for called actions
     * @return string
     */
    protected function actionDebugMessage(array $action, array $data)
    {
        if (! empty($action) && empty($action['actionClass'])) {
            //called in context before action load
            $msg = ' Try to load actions for workflow(s) "' . join(', ', $action) . '" through trigger {trigger}';
        } else {
            //called in context after action loaded
            $msg = ' Workflow called action ' . $action['actionClass'] . '::' . $action['action'] . '() through trigger {trigger}';
        }
        if (! empty($action['parameters'])) {
            $data['parameters'] = $action['parameters'];
        }
        $this->doDebug($msg, $data);
    }

    abstract protected function doDebug($msg, array $data = [], $levelInfo = false);
}
