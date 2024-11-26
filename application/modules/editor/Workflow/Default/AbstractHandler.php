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

    protected ?ZfExtended_EventManager $events;

    /**
     * executes desired handler/trigger action
     * @return string|null returns the trigger name
     */
    abstract public function execute(editor_Workflow_Actions_Config $actionConfig): ?string;

    /**
     * we want to use events inside any WorkflowActionHandler
     * @throws ReflectionException
     */
    public function __construct()
    {
        $this->events = ZfExtended_Factory::get(ZfExtended_EventManager::class, [get_class($this)]);
    }

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
    protected function callActions(editor_Workflow_Actions_Config $config, $step = null, $role = null, $state = null): void
    {
        $actions = ZfExtended_Factory::get(editor_Models_Workflow_Action::class);
        if (is_null($actions)) {
            // @TODO: may some notification or error should be thrown
            return;
        }

        $debugData = [
            'trigger' => $config->trigger,
            'step' => $step,
            'role' => $role,
            'state' => $state,
        ];
        $actions = $actions->loadByTrigger([$config->workflow->getName()], $config->trigger, $step, $role, $state);
        $this->actionDebugMessage([$config->workflow->getName()], $debugData);

        foreach ($actions as $action) {
            $class = $action['actionClass'];
            $method = $action['action'];
            $config->parameters = $this->decodeParameters($config, $action);

            if (empty($instances[$class])) {
                $instance = $this->instantiateActions($class, $config);
                $instances[$class] = $instance;
            } else {
                $instance = $instances[$class];
            }

            $this->actionDebugMessage($action, $debugData);

            if (is_null($config->parameters)) {
                $instance->{$method}();
            } else {
                $instance->{$method}($config->parameters);
            }
        }

        // Trigger afterWorkflowCallAction-event
        $this->events?->trigger('afterWorkflowCallAction', $this, [
            'entity' => $this,
            'task' => $this->config->task,
        ]);
    }

    /**
     * @param class-string<editor_Workflow_Actions_Abstract> $className
     */
    private function instantiateActions(
        string $className,
        editor_Workflow_Actions_Config $config
    ): editor_Workflow_Actions_Abstract {
        if (method_exists($className, 'create')) {
            $instance = $className::create();
        } else {
            $instance =ZfExtended_Factory::get($className);
        }

        $instance->init($config);

        return $instance;
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
