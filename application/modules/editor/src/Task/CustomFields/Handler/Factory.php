<?php

namespace MittagQI\Translate5\Task\CustomFields\Handler;

class Factory
{
    /**
     * @param string $handler
     * @return ProjectWizardCustomFieldHandler|AbstractCustomFieldHandler|TaskGridCustomFieldHandler|ProjectGridCustomFieldHandler
     */
    public static function getHandler($handler): ProjectWizardCustomFieldHandler|AbstractCustomFieldHandler|TaskGridCustomFieldHandler|ProjectGridCustomFieldHandler
    {
        switch ($handler) {
            case 'projectWizard':
                return new ProjectWizardCustomFieldHandler();
            case 'projectGrid':
                return new ProjectGridCustomFieldHandler();
            case 'taskGrid':
                return new TaskGridCustomFieldHandler();
            default:
                throw new \InvalidArgumentException('Invalid handler: ' . $handler);
        }
    }
}