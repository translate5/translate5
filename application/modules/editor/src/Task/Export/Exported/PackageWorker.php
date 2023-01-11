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
namespace MittagQI\Translate5\Task\Export\Exported;

use editor_Models_Export_Exception;
use editor_Models_Export_Exported_ZipDefaultWorker;
use editor_Models_Task;
use MittagQI\Translate5\Task\Export\Package\FileStructure;
use SplFileInfo;
use ZfExtended_Utils;

/**
 */
class PackageWorker extends editor_Models_Export_Exported_ZipDefaultWorker {

    /**
     * Create export zip file from the generate package directory package directory
     * @param editor_Models_Task $task
     * @throws editor_Models_Export_Exception
     */
    protected function doWork(editor_Models_Task $task): void
    {
        //TODO: before doing the zipping, validate if the file structure exist and if there is content inside. Maybe
        // this is job of the previous process and this should not be reached if there is invalid content
        parent::doWork($task);
        $params = $this->workerModel->getParameters();
        ZfExtended_Utils::cleanZipPaths(new SplFileInfo($params['zipFile']), basename(FileStructure::PACKAGE_FOLDER_NAME));
    }
}