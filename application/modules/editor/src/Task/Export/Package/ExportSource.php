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
use MittagQI\Translate5\Task\Export\Package\Source\Base;
use MittagQI\Translate5\Task\Export\Package\Source\Collection;
use MittagQI\Translate5\Task\Export\Package\Source\Memory;
use MittagQI\Translate5\Task\Export\Package\Source\Reference;
use MittagQI\Translate5\Task\Export\Package\Source\Task;
use ZfExtended_Factory;
use ZfExtended_Models_Worker;
use ZfExtended_Utils;

class ExportSource
{
    public const PACKAGE_FOLDER_NAME = '_exportPackage';

    private array $exportSources = [
        Task::class,
        Collection::class,
        Memory::class,
        Reference::class
    ];

    /**
     * @param editor_Models_Task $task
     */
    public function __construct(private editor_Models_Task $task)
    {

    }


    /**
     * @return string
     */
    public function initFileStructure(): string
    {
        $root = $this->getRootFolder();
        if( is_dir($root)){
            ZfExtended_Utils::recursiveDelete($root);
        }

        $this->mkdir($root);

        // create folder path for each exportable source
        foreach ($this->exportSources as $resource){
            /** @var Base $r */
            $r = ZfExtended_Factory::get($resource,[
                $this->task,
                $this
            ]);
            $this->mkdir($r->getFolderPath());
        }

        return $root;
    }

    /***
     * Validate all available sources
     * @return void
     */
    public function validate(): void
    {
        // validate export sources
        foreach ($this->getExportSources() as $resource){
            /** @var Base $r */
            $r = ZfExtended_Factory::get($resource,[
                $this->task,
                $this
            ]);
            $r->validate();
        }
    }

    public function export(ZfExtended_Models_Worker $workerModel){

        // validate export sources
        foreach ($this->getExportSources() as $resource){
            /** @var Base $r */
            $r = ZfExtended_Factory::get($resource,[
                $this->task,
                $this
            ]);
            $r->export($workerModel);
        }
    }

    /***
     * @param $path
     * @return void
     */
    private function mkdir($path): void
    {
        if( is_dir($path)){
            return;
        }
        if (!mkdir($path) && !is_dir($path)) {
            throw new Exception('E1454',[
                'path' => $path
            ]);
        }
    }

    /**
     * @return string
     */
    public function getRootFolder(): string
    {
        return $this->task->getAbsoluteTaskDataPath().DIRECTORY_SEPARATOR.self::PACKAGE_FOLDER_NAME;
    }

    /**
     * @return array
     */
    public function getExportSources(): array
    {
        return $this->exportSources;
    }
}