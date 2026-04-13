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
use MittagQI\Translate5\Segment\Tag\SegmentTagSequence;

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
        if ($this->isDataSourceEmpty() && $this->isDataTargetEmpty()) {
            return;
        }

        //the history entry must be created before the original entity is modified
        $history = $this->segment->getNewHistoryEntity();

        $originalSource = $this->getExistingSourceEdit();
        $originalTarget = $this->getExistingTargetEdit();

        //basically a source must exist, if not (in some specific XLF dialects) its null and must be ignored
        $newSource = $this->getDataSource();
        $newTarget = $this->getDataTarget();

        // we update the source only when source editing is active
        if (! is_null($newSource) &&
            $this->sfm->isEditable('source') &&
            ! $this->isContentEqual($originalSource, $newSource)
        ) {
            $this->updateSourceEdit($newSource, $originalSource);
            $this->updateSegment = true;
        }

        if (! $this->isContentEqual(
            $originalTarget,
            $newTarget
        )) {
            $this->updateTargetEdit($newTarget, $originalTarget);
            $this->updateSegment = true;
        }

        if ($this->updateSegment === false) {
            // no update needed, skip the save
            return;
        }

        $this->segment->setUserGuid($this->user->getUserGuid());
        $this->segment->setUserName($this->user->getUserName());

        $segmentAutoState = editor_Models_Segment_AutoStates::REVIEWED_PM;
        if ($this->isDataTargetEmpty()) {
            $segmentAutoState = editor_Models_Segment_AutoStates::NOT_TRANSLATED;
        }
        $this->segment->setAutoStateId($segmentAutoState);

        $this->segmentUpdater->setSaveTimestamp($segmentSaveTimestamp);
        $this->segmentUpdater->updateForReimport($this->segment, $history);
    }

    /**
     * Updates the segments editable source field and keeps the internal tags in sync
     * When the tags have faults this may create problems with editing in the frontend
     * ... but is the responsibility of the import
     */
    protected function updateSourceEdit(string $newSource, string $originalSource): void
    {
        $this->segmentTagger->synchronizeInternalTags($originalSource, $newSource, function ($original, $source) {
            // the $source will now hold the synchronized internal tags and will be saved to the source-edit field
            $this->update($source, $this->sfm->getFirstSourceNameEdit());
        }, true, true);
    }

    /**
     * Updates the segments editable target with the given content.
     * In case of incorrect tag count or missing/superflous tags, this will still update the segment
     * (the autoqa tag-check should detect & report such problems)
     */
    protected function updateTargetEdit(string $newTarget, string $originalTarget): void
    {
        // we need to transfer the tags from the existing target to the new target.
        // In case, the target is empty, we will use the source to be used as base to get the internal tags from
        if ($originalTarget === '') {
            $originalTarget = $this->segment->getSource();
        }
        $this->segmentTagger->synchronizeInternalTags($originalTarget, $newTarget, function ($original, $target) {
            // the $target will now hold the synchronized internal tags and will be saved to the target-edit field
            if ($this->isTrackChangesActive()) {
                // if trackChanges is active, we need to diff against the edited target (if existing)
                // otherwise the original target (if segment is unedited)
                $target = $this->diffTargetWithTrackChanges($this->getExistingTargetEdit(), $target);
            }
            $this->update($target, $this->sfm->getFirstTargetNameEdit());
        }, true, true);
    }

    /**
     * Create the diffed content when track-changes shall be applied
     * TrackChanges and MQM will usually be removed in this process as otherwise tag-errors are inevitable
     * @see https://jira.translate5.net/browse/TRANSLATE-5325
     */
    public function diffTargetWithTrackChanges(
        string $segmentTarget,
        string $reimportTarget,
        bool $removeTerminologyAndMqm = true,
    ): string {
        // the differ may creates invalid markup since it does not respect/check the order nor nesting of (paired) tags
        // thus we can use only internal-tags (which, in case of "meta-pairs" (opener/closer) can reppresent tag-faults
        if ($removeTerminologyAndMqm) {
            $toRemove = [\editor_Plugins_TermTagger_Tag::TYPE, \editor_Segment_Tag::TYPE_MQM];
            $segmentSequence = new SegmentTagSequence($segmentTarget);
            $reimportSequence = new SegmentTagSequence($reimportTarget);
            // we use hashed placeholders to be able to re-identify the internal tags
            $segmentTarget = $segmentSequence->toPlaceholders($toRemove, SegmentTagSequence::PLACEHOLDER_HASH);
            $reimportTarget = $reimportSequence->toPlaceholders($toRemove, SegmentTagSequence::PLACEHOLDER_HASH);
        }

        $target = $this->diffTagger->diffSegment(
            $segmentTarget,
            $reimportTarget,
            date(NOW_ISO),
            $this->user->getUserName()
        );

        if ($removeTerminologyAndMqm) {
            $target = $segmentSequence->revertPlaceholders($target);
            // can that happen: tags in the reimported target not being in the existing target ??
            $target = $reimportSequence->revertPlaceholders($target);
        }

        return $target;
    }

    public function isUpdateSegment(): bool
    {
        return $this->updateSegment;
    }
}
