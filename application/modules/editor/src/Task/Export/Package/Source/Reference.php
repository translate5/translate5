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

use editor_Models_Import_DirectoryParser_ReferenceFiles;
use MittagQI\Translate5\Task\Export\Package\ExportSource;
use ZfExtended_Factory;
use ZfExtended_Models_Worker;
use ZfExtended_Utils;

class Reference extends Base
{

    protected string $fileName = 'reference';


    /**
     * @return void
     */
    public function export(?ZfExtended_Models_Worker $workerModel): void
    {
        $referencesDirectory = $this->task->getAbsoluteTaskDataPath().DIRECTORY_SEPARATOR.editor_Models_Import_DirectoryParser_ReferenceFiles::getDirectory();
        if( !is_dir($referencesDirectory)){
            // in case there is no references' directory, ignore the copy
            return;
        }
        ZfExtended_Utils::recursiveCopy($referencesDirectory,$this->getFolderPath());
    }

    public function validate(): void
    {
    }
}