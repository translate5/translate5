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

use editor_Models_Task;
use MittagQI\Translate5\Task\Export\Package\ExportSource;
use ZfExtended_Models_Worker;

abstract class Base
{
    protected string $fileName;

    protected string $folderPath;

    public function __construct(
        protected editor_Models_Task $task,
        protected ExportSource $exportSource
    ) {
        $this->folderPath = $exportSource->getRootFolder() . DIRECTORY_SEPARATOR . $this->fileName;
    }

    /**
     * Validate source before export. In case of invalid source, throw exception
     */
    abstract public function validate(): void;

    abstract public function export(?ZfExtended_Models_Worker $workerModel): void;

    /***
     * @return string
     */
    public function getFolderPath(): string
    {
        return $this->folderPath;
    }

    public function getExportSource(): ExportSource
    {
        return $this->exportSource;
    }
}
