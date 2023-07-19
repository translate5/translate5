<?php
/*
 START LICENSE AND COPYRIGHT

  This file is part of translate5

  Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Task\Reimport\SegmentProcessor;

use editor_Models_Import_FileParser;
use editor_Models_Import_SegmentProcessor;
use editor_Models_Segment_InternalTag;
use editor_Models_Task;
use MittagQI\Translate5\Task\Import\Alignment\AlignmentAbstract;
use MittagQI\Translate5\Task\Import\Alignment\Error;
use MittagQI\Translate5\Task\Import\Alignment\Mid;
use MittagQI\Translate5\Task\Import\Alignment\Source;
use MittagQI\Translate5\Task\Reimport\DataProvider\FileDto;
use MittagQI\Translate5\Task\Reimport\Exception;
use MittagQI\Translate5\Task\Reimport\FileparserRegistry;
use MittagQI\Translate5\Task\Reimport\SegmentProcessor\SegmentContent\ContentDefault;
use Throwable;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Models_User;
use ZfExtended_Utils;

/***
 *
 */
class Reimport extends editor_Models_Import_SegmentProcessor
{
    /***
     * Translate5 version for alignment switch. Everything before ALIGNMENT_SWITCH_VERSION
     * will use Source as alignment.
     */
    public const ALIGNMENT_SWITCH_VERSION = 621;

    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $segmentTagger;


    /***
     * Collection of segments which are updated with the reimport (target, source or both)
     * @var array
     */
    private array $updatedSegments = [];

    /***
     * Segment timestamp
     * @var string
     */
    private string $saveTimestamp;

    /***
     * @var AlignmentAbstract
     */
    protected AlignmentAbstract $alignment;

    protected FileDto $fileDto;


    /**
     * @param editor_Models_Task $task
     * @param ZfExtended_Models_User $user
     */
    public function __construct(
        editor_Models_Task             $task,
        private ZfExtended_Models_User $user
    )
    {
        parent::__construct($task);
        $this->segmentTagger = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');

        $this->alignment = $this->getAlignment($task);
    }

    /**
     * Verarbeitet ein einzelnes Segment und gibt die ermittelte SegmentId zurück
     * @return int|false MUST return the segmentId or false
     * @throws Exception
     */
    public function process(editor_Models_Import_FileParser $parser): bool|int
    {

        $content = $this->getContentClass($parser);

        $segment = $this->alignment->findSegment($parser);

        if (is_null($segment)) {
            return false;
        }

        try {
            $content->saveSegment($segment, $this->saveTimestamp);

            if ($content->isUpdateSegment()) {
                $this->updatedSegments[] = $segment->getSegmentNrInTask();
            }

            return $segment->getId();
        } catch (Throwable $e) {
            // collect the errors in case the segment can not be saved

            /* @var ReimportSegmentErrors $reimportError */
            $this->alignment->addError(new Error(
                'E1435',
                'Reimport Segment processor: Unable to save the segment:' . $segment->getSegmentNrInTask(),
                [
                    $e->getMessage()
                ]
            ));
            return false;
        }

    }

    /***
     * @param editor_Models_Import_FileParser $parser
     * @return ContentDefault
     * @throws Exception
     */
    protected function getContentClass(editor_Models_Import_FileParser $parser): ContentDefault
    {
        $reimporter = FileparserRegistry::getInstance()->getReimporterInstance($parser, [
            $this->task,
            $parser->getFieldContents(),
            $this->user
        ]);

        if (is_null($reimporter)) {
            throw new Exception('E1441', [
                'file' => basename($this->fileName)
            ]);
        }

        return $reimporter;
    }

    /**
     * Überschriebener Post Parse Handler, erstellt in diesem Fall das Skeleton File
     * @override
     * @param editor_Models_Import_FileParser $parser
     * @throws Zend_Exception
     */
    public function postParseHandler(editor_Models_Import_FileParser $parser)
    {
        $this->saveFieldWidth($parser);
        $this->logFileInfo($parser->getFileId());
    }

    /**
     * @param int $fileId
     * @return void
     * @throws Zend_Exception
     */
    private function logFileInfo(int $fileId): void
    {
        $logger = Zend_Registry::get('logger')->cloneMe('editor.task.reimport');

        $errors = $this->alignment->getErrors();

        $logger->info(
            'E1440',
            'Reimport for the file "{fileName}" is finished. Total updated segments: {updateCount}.',
            [
                'task' => $this->task,
                'fileId' => $fileId,
                'updateCount' => count($this->updatedSegments),
                'segments' => implode(',', $this->updatedSegments),
                'fileName' => $this->fileDto->filteredFilePath
            ]
        );

        foreach ($errors as $error) {
            /* @var Error $error */

            $logger->warn($error->getCode(), $error->getMessage(), [
                'task' => $this->task,
                'fileId' => $fileId,
                'fileName' => $this->fileDto->filteredFilePath,
                'extra' => implode(', ', $error->getExtra())
            ]);
        }
    }

    /***
     * (non-PHPdoc)
     * @see editor_Models_Import_SegmentProcessor::postProcessHandler()
     */
    public function postProcessHandler(editor_Models_Import_FileParser $parser, $segmentId)
    {
        $this->calculateFieldWidth($parser);
    }

    /**
     * @param string $saveTimestamp
     */
    public function setSaveTimestamp(string $saveTimestamp): void
    {
        $this->saveTimestamp = $saveTimestamp;
    }

    /**
     * @param FileDto $fileDto
     */
    public function setFileDto(FileDto $fileDto): void
    {
        $this->fileDto = $fileDto;
    }

    /***
     * Get the segment alignment to be used for the current reimport processor based on the task version.
     * @param editor_Models_Task $task
     * @return AlignmentAbstract
     */
    private function getAlignment(editor_Models_Task $task): AlignmentAbstract
    {
        $version = $task->getImportAppVersion();
        if ($version === ZfExtended_Utils::VERSION_DEVELOPMENT) {
            return ZfExtended_Factory::get(Mid::class);
        }
        $version = (int)str_replace('.', '', $version);
        // For versions > 6.2.1 use always the new MID alignment
        return ZfExtended_Factory::get(($version > self::ALIGNMENT_SWITCH_VERSION) ? Mid::class : Source::class);
    }

}

