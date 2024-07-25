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
 * Contains the Export Worker (the scheduling parts)
 * The export process itself is encapsulated in editor_Models_Export
 * The worker nows three parameters:
 *  "diff" boolean en- or disables the export differ
 *  "method" string is either "exportToZip" [default] or "exportToFolder"
 *  "exportToFolder" string valid writable path to the export folder, only needed for method "exportToFolder"
 */
class editor_Models_Export_Worker extends ZfExtended_Worker_Abstract
{
    public const PARAM_EXPORT_FOLDER = 'exportToFolder';

    /**
     * Disable the update progress trigger on export
     */
    protected function onProgressUpdated(float $progress): void
    {
        //do nothing on export
        //DANGER: currently we inherit from ZfExtended_Worker_Abstract, all fine then.
        //But if inheritence is changed so that we might inherit from editor_Models_Task_AbstractWorker in the future,
        // then updateProgress trigger event must not be called on export, therefore this empty stub is created!
    }

    protected function validateParameters(array $parameters): bool
    {
        if (! isset($parameters['diff']) || ! is_bool($parameters['diff'])) {
            return false;
        }

        return true;
    }

    /**
     * inits a export to the default directory
     * @return string the folder which receives the exported data
     */
    public function initExport(editor_Models_Task $task, bool $diff)
    {
        //if no explicit exportToFolder is given, we use the default (taskGuid)
        $default = $task->getAbsoluteTaskDataPath() . DIRECTORY_SEPARATOR . $task->getTaskGuid();
        is_dir($default) || @mkdir($default); //we create it if it does not exist

        return $this->initFolderExport($task, $diff, $default);
    }

    /**
     * inits a export to a given directory
     * @return string the folder which receives the exported data
     */
    public function initFolderExport(editor_Models_Task $task, bool $diff, string $exportFolder)
    {
        $parameter = [
            'diff' => $diff,
            self::PARAM_EXPORT_FOLDER => $exportFolder,
        ];
        $this->init($task->getTaskGuid(), $parameter);

        return $exportFolder;
    }

    public function work(): bool
    {
        //also containing an instance of the initial dataprovider.
        // The Dataprovider can itself hook on to several import events
        $parameters = $this->workerModel->getParameters();

        if (! $this->validateParameters($parameters)) {
            //no separate logging here, missing diff is not possible,
            // directory problems are loggeed above
            return false;
        }

        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($this->taskGuid);

        if (! is_dir($parameters[self::PARAM_EXPORT_FOLDER]) || ! is_writable($parameters[self::PARAM_EXPORT_FOLDER])) {
            //The task export folder does not exist or is not writeable, no export ZIP file can be created.
            throw new editor_Models_Export_Exception('E1147', [
                'task' => $task,
                'exportFolder' => $parameters[self::PARAM_EXPORT_FOLDER],
            ]);
        }

        $exportClass = 'editor_Models_Export';
        $export = ZfExtended_Factory::get($exportClass);
        /* @var $export editor_Models_Export */
        $export->setTaskToExport($task, $parameters['diff']);
        $export->export($parameters[self::PARAM_EXPORT_FOLDER], (int) $this->workerModel->getId());

        //we should use __CLASS__ here, if not we loose bound handlers to base class in using subclasses
        $eventManager = ZfExtended_Factory::get('ZfExtended_EventManager', [$exportClass]);
        $eventManager->trigger('afterExport', $this, [
            'task' => $task,
            'parentWorkerId' => (int) $this->workerModel->getId(),
        ]);

        return true;
    }
}
