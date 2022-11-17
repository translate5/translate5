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

use editor_Models_Export_DiffTagger_TrackChanges;
use editor_Models_File;
use editor_Models_Import_FileParser;
use editor_Models_Import_SegmentProcessor;
use editor_Models_Segment;
use editor_Models_Segment_InternalTag;
use editor_Models_Segment_Updater;
use editor_Models_SegmentFieldManager;
use editor_Models_Task;
use JsonException;
use MittagQI\Translate5\Task\Reimport\SegmentProcessor\SegmentContent\ContentDefault;
use MittagQI\Translate5\Task\Reimport\SegmentProcessor\SegmentContent\Xliff;
use Throwable;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Logger;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_User;

/***
 *
 */
class Reimport extends editor_Models_Import_SegmentProcessor
{
    /***
     * @var ZfExtended_Logger $logger
     */
    protected ZfExtended_Logger $logger;

    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $segmentTagger;

    /***
     * @var editor_Models_Export_DiffTagger_TrackChanges
     */
    protected $diffTagger;


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
    public function __construct(editor_Models_Task $task,private editor_Models_SegmentFieldManager $sfm, private ZfExtended_Models_User $user)
    {
        parent::__construct($task);
        $this->logger = Zend_Registry::get('logger')->cloneMe('editor.task.reimport');
        $this->segmentTagger = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        $this->diffTagger = ZfExtended_Factory::get('editor_Models_Export_DiffTagger_TrackChanges', [$task, $this->user]);
    }

    /**
     * Verarbeitet ein einzelnes Segment und gibt die ermittelte SegmentId zurück
     * @return int|false MUST return the segmentId or false
     */
    public function process(editor_Models_Import_FileParser $parser): bool|int
    {
        /** @var editor_Models_Segment $segment */
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        $segment->init(['taskGuid' => $this->taskGuid]);

        $mid = $parser->getMid();
        try {
            $segment->loadByFileidMid($this->fileId, $mid);
        } catch(ZfExtended_Models_Entity_NotFoundException $e) {
            /** @var ReimportSegmentErrors $reimportError */
            $reimportError = ZfExtended_Factory::get(ReimportSegmentErrors::class);
            $reimportError->setCode('E1434');
            $reimportError->setMessage('Reimport Segment processor: No matching segment was found for the given mid.');
            $reimportError->setData([
                'mid' => $mid
            ]);
            $this->segmentErrors[$reimportError->getCode()][] = $reimportError;
            return false;
        }
        try {
            $content = $this->getContentClass($parser);
            $content->saveSegment($segment,$this->saveTimestamp);

            if( $content->isUpdateSegment()){
                $this->updatedSegments[] = $segment->getSegmentNrInTask();
            }

            return $segment->getId();
        } catch (Throwable $e) {
            // collect the errors in case the segment can not be saved
            /** @var ReimportSegmentErrors $reimportError */
            $reimportError = ZfExtended_Factory::get(ReimportSegmentErrors::class);
            $reimportError->setCode('E1435');
            $reimportError->setMessage('Reimport Segment processor: Unable to save the segment');
            $reimportError->setData([
                'segmentNumber' => $segment->getSegmentNrInTask(),
                'errorMessage' => $e->getMessage()
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
        $path_parts = pathinfo($this->fileName);
        $ext = $path_parts['extension'];
        $className = 'MittagQI\\Translate5\\Task\\Reimport\\SegmentProcessor\\SegmentContent\\'.ucfirst($ext);
        $args = [
            $this->task,
            $parser->getFieldContents(),
            $this->user
        ];

        if(!class_exists($className)){
            // fallback
            $className = ContentDefault::class;
        }
        return ZfExtended_Factory::get($className,$args);
    }

    /**
     * Überschriebener Post Parse Handler, erstellt in diesem Fall das Skeleton File
     * @override
     * @param editor_Models_Import_FileParser $parser
     */
    public function postParseHandler(editor_Models_Import_FileParser $parser)
    {
        $file = ZfExtended_Factory::get('editor_Models_File');
        /* @var editor_Models_File $file */
        $file->load($this->fileId);
        $file->saveSkeletonToDisk($parser->getSkeletonFile(), $this->task);

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
     * Log reimport process information and errors
     *
     * @return void
     * @throws JsonException
     */
    public function log(){
        $this->logErrors();
        $this->logUpdated();
    }

    /***
     * Log all collected errors as warning
     */
    private function logErrors(): void
    {
        foreach ($this->segmentErrors as $code => $codeErrors){
            $extra = [];
            foreach ($codeErrors as $error) {
                /* @var ReimportSegmentErrors $error */
                $extra[] = $error->getData();
            }
            $this->logger->warn($code,$codeErrors[0]->getMessage(),[
                'task' => $this->task,
                'fileId' => $this->fileId,
                'extra' => json_encode($extra, JSON_THROW_ON_ERROR)
            ]);
        }
    }

    /**
     * Log all updated segments in the task
     * @return void
     */
    private function logUpdated(): void
    {
        $this->logger->info('E1440','File reimport finished.',[
            'task' => $this->task,
            'fileId' => $this->fileId,
            'segments' => implode(',',$this->updatedSegments)
        ]);
    }

    /**
     * @param string $saveTimestamp
     */
    public function setSaveTimestamp(string $saveTimestamp): void
    {
        $this->saveTimestamp = $saveTimestamp;
    }

}