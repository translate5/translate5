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

namespace MittagQI\Translate5\Task\Export\Package\Source;

use editor_Models_Export;
use editor_Models_Export_Exception;
use editor_Models_File;
use editor_Models_Task;
use MittagQI\Translate5\Task\Export\Package\Exception;
use MittagQI\Translate5\Task\Export\Package\ExportSource;
use MittagQI\Translate5\Task\Reimport\FileparserRegistry;
use Zend_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Worker;

/**
 *
 */
class Task extends Base
{

    /***
     * Folder name where all segment files will be placed in the export package
     */
    public const TASK_FOLDER_NAME = 'workfiles';

    public function __construct(editor_Models_Task $task, ExportSource $exportSource)
    {
        $this->fileName = self::TASK_FOLDER_NAME;
        parent::__construct($task, $exportSource);
    }

    /**
     * @return void
     * @throws Exception
     * @throws editor_Models_Export_Exception
     */
    public function validate(): void
    {
        if (!is_dir($this->getFolderPath()) || !is_writable($this->getFolderPath())) {
            //The task export folder does not exist or is not writeable, no export ZIP file can be created.
            throw new editor_Models_Export_Exception('E1147', [
                'task' => $this->task,
                'exportFolder' => $this->getFolderPath()
            ]);
        }
        /** @var editor_Models_File $files */
        $files = ZfExtended_Factory::get('editor_Models_File');
        $files = $files->loadByTaskGuid($this->task->getTaskGuid());



        foreach ($files as $file) {
            if (! FileparserRegistry::getInstance()->hasFileparser($file['fileParser'])) {
                throw new Exception('E1452', [
                    'supported' => FileparserRegistry::getInstance()->getSupportedFileTypes(),
                    'actual' => $file['fileName'],
                    'task' => $this->task
                ]);
            }

        }
    }

    /**
     * @param ZfExtended_Models_Worker|null $workerModel
     * @return void
     * @throws Zend_Exception
     */
    public function export(?ZfExtended_Models_Worker $workerModel): void
    {
        $params = $workerModel->getParameters();

        $export = ZfExtended_Factory::get('editor_Models_Export');
        /* @var editor_Models_Export $export */
        $export->setTaskToExport($this->task, $params['diff']);
        $export->export($this->getFolderPath(), $workerModel->getId());
    }
}