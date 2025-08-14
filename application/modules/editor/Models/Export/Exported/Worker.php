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
 * This class provides a general Worker which can be configured with a callback method which.
 * This class is designed for simple workers which dont need a own full blown worker class.
 *
 * The following parameters are needed:
 * class → the class which should be instanced on work. Classes with Constructor Parameters are currently not supported!
 * method → the class method which is called on work. The method receives the taskguid and the whole parameters array.
 *
 * Be careful: This class can not be used in worker_dependencies !
 */
class editor_Models_Export_Exported_Worker extends ZfExtended_Worker_Abstract
{
    public static function triggerExportCompleted(
        editor_Models_Export_Exported_Worker $worker,
        editor_Models_Task $task,
        array $parameters,
    ): void {
        // the event will always be triggered in the scope of the base-exported-worker
        $eventManager = ZfExtended_Factory::get(
            ZfExtended_EventManager::class,
            [
                editor_Models_Export_Exported_Worker::class,
            ]
        );
        $eventManager->trigger('exportCompleted', $worker, [
            'task' => $task,
            'parameters' => $parameters,
        ]);
    }

    protected function validateParameters(array $parameters): bool
    {
        return true;
    }

    /**
     * Get context-specific exported worker class instance if exists, or instance of self-class instance
     *
     * @return mixed
     * @throws ReflectionException
     */
    public static function factory(string $context)
    {
        // Get context-specific class name
        $className = preg_replace('~_(Worker)$~', '_' . ucfirst($context) . '$1', __CLASS__);

        // Check is class with such name exists, and if no use self
        $className = $context && class_exists($className) ? $className : preg_replace('~_(Worker)$~', '_ZipDefault$1', __CLASS__);

        // Create and return instance
        return ZfExtended_Factory::get($className);
    }

    public function work(): bool
    {
        $parameters = $this->workerModel->getParameters();

        $task = new editor_Models_Task();
        $task->loadByTaskGuid($this->taskGuid);

        $this->doWork($task);

        self::triggerExportCompleted($this, $task, $parameters);

        return true;
    }

    /**
     * Here we can't use init() as method name because it will lead to endless loop as init() method will be
     * indirectly called by WorkerController->putAction()
     *
     * @see ZfExtended_Worker_Abstract::init()
     */
    public function setup($taskGuid, $parameters = [])
    {
        return parent::init($taskGuid, $parameters);
    }

    /**
     * Override to implement a workload
     */
    protected function doWork(editor_Models_Task $task): void
    {
    }
}
