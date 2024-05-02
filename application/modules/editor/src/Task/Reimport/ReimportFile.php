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
declare(strict_types=1);

namespace MittagQI\Translate5\Task\Reimport;

use editor_Models_File;
use editor_Models_File_FilterManager;
use editor_Models_Import_FileParser;
use editor_Models_SegmentFieldManager;
use editor_Models_Task;
use MittagQI\Translate5\Task\Import\FileParser\Factory;
use MittagQI\Translate5\Task\Reimport\DataProvider\FileDto;
use MittagQI\Translate5\Task\Reimport\SegmentProcessor\Reimport;
use SplFileInfo;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_User;

/***
 * Reimport the given file inside the task.
 */
class ReimportFile
{
    private FileDto $fileDto;

    public function __construct(
        private editor_Models_Task $task,
        private ZfExtended_Models_User $user
    ) {
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws Exception
     */
    public function import(int $fileId, string $filePath, string $segmentTimestamp): void
    {
        $segmentFieldManager = ZfExtended_Factory::get(editor_Models_SegmentFieldManager::class);
        $segmentFieldManager->initFields($this->task->getTaskGuid());

        $parserHelper = ZfExtended_Factory::get(Factory::class, [
            $this->task,
            $segmentFieldManager,
        ]);

        $file = ZfExtended_Factory::get(editor_Models_File::class);
        $file->load($fileId);
        $parserCls = $file->getFileParser();

        $fileFilter = ZfExtended_Factory::get(editor_Models_File_FilterManager::class);
        $fileFilter->initReImport($this->task, Worker::FILEFILTER_CONTEXT_NEW);

        // get the parser dynamically even of only xliff is supported
        $parser = $parserHelper->getFileParserInstance($parserCls, $fileId, new SplFileInfo($filePath));
        /* @var editor_Models_Import_FileParser $parser */

        $parser->setIsReimport();

        $segmentProcessor = ZfExtended_Factory::get(Reimport::class, [
            $this->task,
            $this->user,
        ]);

        $segmentProcessor->setSegmentFile($fileId, $parser->getFileName());
        $segmentProcessor->setSaveTimestamp($segmentTimestamp);
        $segmentProcessor->setFileDto($this->fileDto);

        $parser->addSegmentProcessor($segmentProcessor);
        $parser->parseFile();
    }

    public function setFileDto(FileDto $fileDto): void
    {
        $this->fileDto = $fileDto;
    }
}
