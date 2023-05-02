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

use editor_Models_File;
use editor_Models_Import_FileParser;
use editor_Models_Import_SegmentProcessor;
use editor_Models_Segment;
use editor_Models_Segment_InternalTag;
use editor_Models_SegmentFieldManager;
use editor_Models_Task;
use JsonException;
use MittagQI\Translate5\Task\Reimport\Exception;
use MittagQI\Translate5\Task\Reimport\FileparserRegistry;
use MittagQI\Translate5\Task\Reimport\SegmentProcessor\SegmentContent\ContentDefault;
use Throwable;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_User;

/***
 *
 */
class Reimport extends editor_Models_Import_SegmentProcessor
{
    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $segmentTagger;

    /***
     * @var array
     */
    private array $segmentErrors = [];

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

    /**
     * @param editor_Models_Task $task
     * @param editor_Models_SegmentFieldManager $sfm
     */
    public function __construct(editor_Models_Task $task, private editor_Models_SegmentFieldManager $sfm, private ZfExtended_Models_User $user)
    {
        parent::__construct($task);
        $this->segmentTagger = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
    }

    /**
     * Verarbeitet ein einzelnes Segment und gibt die ermittelte SegmentId zurück
     * @return int|false MUST return the segmentId or false
     * @throws Exception
     */
    public function process(editor_Models_Import_FileParser $parser): bool|int
    {

        $content = $this->getContentClass($parser);

        /** @var editor_Models_Segment $segment */
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        $segment->init(['taskGuid' => $this->taskGuid]);

        $mid = $parser->getMid();
        try {
            $segment->loadByFileidMid($this->fileId, $mid);
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            /** @var ReimportSegmentErrors $reimportError */
            $reimportError = ZfExtended_Factory::get(ReimportSegmentErrors::class, [
                'E1434',
                'Reimport Segment processor: No matching segment was found for the given mid.',
                [
                    'mid' => $mid
                ]
            ]);
            $this->segmentErrors[$reimportError->getCode()][] = $reimportError;
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
            /** @var ReimportSegmentErrors $reimportError */
            $reimportError = ZfExtended_Factory::get(ReimportSegmentErrors::class, [
                'E1435',
                'Reimport Segment processor: Unable to save the segment',
                [
                    'segmentNumber' => $segment->getSegmentNrInTask(),
                    'errorMessage' => $e->getMessage()
                ]
            ]);
            $this->segmentErrors[$reimportError->getCode()] = [$reimportError];
            return false;
        }

    }

    /***
     * @param editor_Models_Import_FileParser $parser
     * @return ContentDefault
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
     */
    public function postParseHandler(editor_Models_Import_FileParser $parser)
    {
        $this->saveFieldWidth($parser);
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
     * get all updated segments in the task
     * @return array
     */
    public function getUpdatedSegments(): array
    {
        return $this->updatedSegments;
    }

    /**
     * get all segment errors
     * @return array
     */
    public function getSegmentErrors(): array
    {
        return $this->segmentErrors;
    }

    /**
     * @param string $saveTimestamp
     */
    public function setSaveTimestamp(string $saveTimestamp): void
    {
        $this->saveTimestamp = $saveTimestamp;
    }

}