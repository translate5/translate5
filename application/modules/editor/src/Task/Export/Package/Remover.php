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

namespace MittagQI\Translate5\Task\Export\Package;

use editor_Models_Task;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Factory;

/**
 * Check and remove all package exports older than 1 days for all active tasks
 *
 */
class Remover
{

    /**
     * @return void
     * @throws Zend_Exception
     */
    public function remove(): void
    {

        $data = $this->getData();
        if(empty($data)){
            return;
        }

        $model = ZfExtended_Factory::get(editor_Models_Task::class);

        foreach ($data as $task) {
            $model->init($task);
            $this->checkAndDelete($model->getAbsoluteTaskDataPath().DIRECTORY_SEPARATOR);
        }
    }

    /***
     * Check and remove the export package in the given directory in case the export is older than 1 days
     * @param string $dir
     * @return void
     * @throws \Zend_Exception
     */
    private function checkAndDelete(string $dir): void
    {

        // Check if the directory exists and is readable
        if (!is_dir($dir) || !is_readable($dir)) {
            return;
        }

        // Get the list of files in the directory
        $files = scandir($dir);

        $time = time();
        $removed = [];
        // Loop through each file in the directory
        foreach ($files as $file) {

            // Check if the file is a zip file and has the correct prefix
            if (str_starts_with($file, Downloader::PACKAGE_EXPORT) && pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                // Get the file's creation time
                $file_time = filectime($dir . $file);

                // Check if the file is older than 1 day (86400 seconds)
                if ($time - $file_time > 86400) {
                    // Attempt to delete the file
                    if (unlink($dir . $file)) {
                        $removed[] = $dir.$file;
                    }
                }
            }
        }
        if( !empty($removed)){
            $logger = Zend_Registry::get('logger');
            $logger->info('E0000', 'Clean up of package exports older than 1 days',[
                'removed' => implode(',',$removed)
            ]);
        }
    }

    /***
     * Get all reimportable tasks for which the package clean up will be called
     * @return array
     */
    private function getData(): array
    {
        $task = ZfExtended_Factory::get(editor_Models_Task::class);
        return $task->getAllReimportable();
    }
}