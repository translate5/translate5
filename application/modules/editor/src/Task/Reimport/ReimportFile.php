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

namespace MittagQI\Translate5\Task\Reimport;

use editor_Models_Import_FileParser;
use editor_Models_SegmentFieldManager;
use editor_Models_Task;
use MittagQI\Translate5\Task\Import\FileParser\Factory;
use MittagQI\Translate5\Task\Reimport\SegmentProcessor\Reimport;
use ZfExtended_Factory;
use ZfExtended_Models_User;

/***
 * Reimport the given file inside the task.
 */
class ReimportFile
{

    private Reimport $segmentProcessor;

    public function __construct(private editor_Models_Task $task, private ZfExtended_Models_User $user)
    {

    }

    public function import(int $fileId, string $filePath, string $segmentTimestamp)
    {
        $segmentFieldManager = ZfExtended_Factory::get(editor_Models_SegmentFieldManager::class);
        $segmentFieldManager->initFields($this->task->getTaskGuid());

        $parserHelper = ZfExtended_Factory::get(Factory::class, [
            $this->task,
            $segmentFieldManager
        ]);

        // get the parser dynamically even of only xliff is supported
        $parser = $parserHelper->getFileParser($fileId, $filePath);
        /* @var editor_Models_Import_FileParser $parser */

        if (is_null($parser)) {
            throw new \MittagQI\Translate5\Task\Reimport\Exception('E1433', [
                'file' => $fileId,
                'task' => $this->task
            ]);
        }

        $parser->setIsReimport();

        $this->segmentProcessor = ZfExtended_Factory::get(Reimport::class, [$this->task, $segmentFieldManager, $this->user]);
        $this->segmentProcessor->setSegmentFile($fileId, $parser->getFileName());
        $this->segmentProcessor->setSaveTimestamp($segmentTimestamp);

        $parser->addSegmentProcessor($this->segmentProcessor);
        $parser->parseFile();
    }

    /**
     * @return Reimport
     */
    public function getSegmentProcessor(): Reimport
    {
        return $this->segmentProcessor;
    }
}