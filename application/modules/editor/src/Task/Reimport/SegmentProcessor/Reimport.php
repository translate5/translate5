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
use editor_Models_Segment_Updater;
use editor_Models_SegmentFieldManager;
use editor_Models_Task;
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

    /***
     * @var ZfExtended_Models_User
     */
    protected ZfExtended_Models_User $user;

    /**
     * @param editor_Models_Task $task
     * @param editor_Models_SegmentFieldManager $sfm
     */
    public function __construct(editor_Models_Task $task,private editor_Models_SegmentFieldManager $sfm)
    {
        parent::__construct($task);
        $this->logger = Zend_Registry::get('logger')->cloneMe('editor.task.reimport');
    }

    public function process(editor_Models_Import_FileParser $parser)
    {
        /** @var editor_Models_Segment $segment */
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        $segment->init(['taskGuid' => $this->taskGuid]);

        $mid = $parser->getMid();
        try {
            $segment->loadByFileidMid($this->fileId, $mid);
        } catch(ZfExtended_Models_Entity_NotFoundException $e) {
            // TODO: log the not found segment
            return false;
        }
        $this->saveSegment($segment,$parser);
        return $segment->getId();
    }

    /**
     */
    protected function saveSegment(editor_Models_Segment $t5Segment,editor_Models_Import_FileParser $parser) {

        $data = $parser->getFieldContents();
        $source = $this->sfm->getFirstSourceName();
        $target = $this->sfm->getFirstTargetName();
        $newSource = $data[$source]["original"];
        $newTarget = $data[$target]["original"];

        // ignore the update in case source and target are empty
        if( empty($newSource) && empty($newTarget)){
            return;
        }

        $updater = ZfExtended_Factory::get('editor_Models_Segment_Updater', [$this->task]);
        /* @var editor_Models_Segment_Updater $updater */

        //the history entry must be created before the original entity is modified
        $history = $t5Segment->getNewHistoryEntity();

        $saveSegment = false;
        if( !$this->isContentEqual($t5Segment->getFieldOriginal($source),$newSource)){

            $t5Segment->set($this->sfm->getFirstSourceName(),$newSource);
            $t5Segment->set($this->sfm->getFirstSourceNameEdit(),$newSource);

            $t5Segment->updateToSort($this->sfm->getFirstSourceName());
            $t5Segment->updateToSort($this->sfm->getFirstSourceNameEdit());
            $saveSegment = true;
        }

        if( !$this->isContentEqual($t5Segment->getFieldOriginal($target),$newTarget)){

            $t5Segment->set($this->sfm->getFirstTargetName(),$newTarget);
            $t5Segment->set($this->sfm->getFirstTargetNameEdit(),$newTarget);

            $t5Segment->updateToSort($this->sfm->getFirstTargetName());
            $t5Segment->updateToSort($this->sfm->getFirstTargetNameEdit());

            $saveSegment = true;
        }

        if($saveSegment){
            $t5Segment->setUserGuid($this->user->getUserGuid());
            $t5Segment->setUserName($this->user->getUserName());
            $updater->update($t5Segment, $history);
        }

    }

    /***
     * @param string $old
     * @param string $new
     * @return bool
     */
    public function isContentEqual(string $old , string $new): bool
    {
        // TODO: protect the tags when compare
        // TODO: check the tags count. It needs to be retested on each check

        return $old === $new;
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
     * @param ZfExtended_Models_User $user
     */
    public function setUser(ZfExtended_Models_User $user): void
    {
        $this->user = $user;
    }
}