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

use editor_Models_Segment;
use editor_Models_Segment_AutoStates;

class ContentDefault extends ContentBase
{
    /***
     * Internal flag which is calculated and set if the segment should be saved/updated
     * @var bool
     */
    private bool $updateSegment = false;

    /**
     * Save the collected content to the given segment. The segment will be updated/saved only if the new content
     * is different from the current segment content.
     */
    public function saveSegment(editor_Models_Segment $segment, string $segmentSaveTimestamp): void
    {
        $this->segment = $segment;
        $this->updateSegment = false;

        // ignore the update in case source and target are empty
        if (empty($this->getDataSource()) && empty($this->getDataTarget())) {
            return;
        }

        //the history entry must be created before the original entity is modified
        $history = $this->segment->getNewHistoryEntity();

        //basically a source must exist, if not (in some specific XLF dialects) its null and must be ignored
        $newSource = $this->getDataSource();
        if (! is_null($newSource) &&
            ! $this->isContentEqual($this->segment->getFieldOriginal($this->sfm->getFirstSourceName()), $newSource)) {
            $this->updateSource($this->getDataSource());
            $this->updateSegment = true;
        }

        if (! $this->isContentEqual(
            $this->segment->getFieldEdited(
                $this->sfm->getFirstTargetName()
            ),
            $this->getDataTarget()
        )) {
            $this->updateTarget($this->getDataTarget());
            $this->updateSegment = true;
        }

        if ($this->updateSegment === false) {
            // no update needed, skip the save
            return;
        }

        $this->segment->setUserGuid($this->user->getUserGuid());
        $this->segment->setUserName($this->user->getUserName());

        $segmentAutoState = editor_Models_Segment_AutoStates::REVIEWED_PM;
        if (empty($this->getDataTarget())) {
            $segmentAutoState = editor_Models_Segment_AutoStates::NOT_TRANSLATED;
        }
        $this->segment->setAutoStateId($segmentAutoState);

        $this->segmentUpdater->setSaveTimestamp($segmentSaveTimestamp);
        $this->segmentUpdater->updateForReimport($this->segment, $history);
    }

    /**
     * Updates the segment target with the given content. In case of incorrect tag count, this will stil update
     * the segment(the autoqa tag check should find this problem)
     */
    protected function updateTarget(string $target): void
    {
        $this->segmentTagger->updateSegmentContent($this->segment->getSource(), $target, function ($original, $target) {
            if ($this->isTrackChangesActive()) {
                $fieldOriginal = $this->segment->getFieldOriginal($this->sfm->getFirstTargetName());
                $target = $this->diffTagger->diffSegment(
                    $fieldOriginal,
                    $target,
                    date(NOW_ISO),
                    $this->user->getUserName()
                );
            }
            $this->update($target, $this->sfm->getFirstTargetName(), $this->sfm->getFirstTargetNameEdit());
        }, true, true);
    }

    public function isUpdateSegment(): bool
    {
        return $this->updateSegment;
    }
}
