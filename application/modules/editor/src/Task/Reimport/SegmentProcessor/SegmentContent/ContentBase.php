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

namespace MittagQI\Translate5\Task\Reimport\SegmentProcessor\SegmentContent;

use editor_Models_Export_DiffTagger_TrackChanges;
use editor_Models_Segment;
use editor_Models_Segment_InternalTag;
use editor_Models_Segment_Updater;
use editor_Models_SegmentFieldManager;
use editor_Models_Task;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Models_User;
use ZfExtended_Plugin_Exception;
use ZfExtended_Plugin_Manager;

/**
 *
 */
abstract class ContentBase
{

    /***
     * @var editor_Models_SegmentFieldManager
     */
    protected editor_Models_SegmentFieldManager $sfm;

    /***
     * @var editor_Models_Segment_Updater
     */
    protected editor_Models_Segment_Updater $segmentUpdater;

    /***
     * @var editor_Models_Segment
     */
    protected editor_Models_Segment $segment;

    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected editor_Models_Segment_InternalTag $segmentTagger;

    /***
     * @var editor_Models_Export_DiffTagger_TrackChanges
     */
    protected $diffTagger;


    /**
     * @param editor_Models_Task $task
     * @param ZfExtended_Models_User $user
     */
    public function __construct(protected editor_Models_Task $task, protected array $segmentData, protected ZfExtended_Models_User $user)
    {
        $this->sfm = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        $this->sfm->initFields($this->task->getTaskGuid());

        $this->segmentUpdater = ZfExtended_Factory::get('editor_Models_Segment_Updater', [$this->task,$this->user->getUserGuid()]);
        $this->segmentTagger = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        $this->diffTagger = ZfExtended_Factory::get('editor_Models_Export_DiffTagger_TrackChanges', [$this->task, $this->user]);

    }

    /**
     * @param editor_Models_Segment $segment
     * @param string $segmentSaveTimestamp
     * @return void
     */
    abstract public function saveSegment(editor_Models_Segment $segment, string $segmentSaveTimestamp): void;

    /**
     * @param string $value
     * @param string $fieldName
     * @param string $fieldNameEdit
     * @return void
     */
    protected function update(string $value, string $fieldName, string $fieldNameEdit): void
    {
        $this->segment->set($fieldName,$value);
        $this->segment->set($fieldNameEdit,$value);
        $this->segment->updateToSort($fieldName);
        $this->segment->updateToSort($fieldNameEdit);
    }

    /**
     * @param string $source
     * @return void
     */
    protected function updateSource(string $source): void
    {
        $this->update($source,$this->sfm->getFirstSourceName(),$this->sfm->getFirstSourceNameEdit());
    }

    /**
     * @param string $target
     * @return void
     */
    protected function updateTarget(string $target): void
    {
        $this->update($target,$this->sfm->getFirstTargetName(),$this->sfm->getFirstTargetNameEdit());
    }

    /**
     * @return string|null
     */
    protected function getDataSource(): ?string
    {
        return $this->segmentData[$this->sfm->getFirstSourceName()]['original'] ?? null;
    }

    /**
     * @return mixed
     */
    protected function getDataTarget(): mixed
    {
        return $this->segmentData[$this->sfm->getFirstTargetName()]['original'];
    }

    /**
     * Replace tags with placeholders for easy content compare
     * @param string $content
     * @param array $tagMap
     * @return string
     */
    protected function normalizeContent(string $content, array &$tagMap = []): string
    {
        $normalized = $this->segmentTagger->protect($content);
        $tagMap = $this->segmentTagger->getOriginalTags();
        return $normalized;
    }

    /***
     * Check if the given two contents are equal. The content to be checked will be normalized
     * before the check
     * @param string $old
     * @param string $new
     * @return bool
     */
    protected function isContentEqual(string $old , string $new): bool
    {
        return $this->normalizeContent($old) === $this->normalizeContent($new);
    }

    /**
     * @return bool
     * @throws Zend_Exception
     * @throws ZfExtended_Plugin_Exception
     */
    protected function isTrackChangesActive(): bool
    {
        $pluginmanager = Zend_Registry::get('PluginManager');
        /* @var ZfExtended_Plugin_Manager $pluginmanager */
        return $pluginmanager->isActive('TrackChanges');
    }

}