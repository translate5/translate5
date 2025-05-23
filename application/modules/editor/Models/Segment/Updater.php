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

use MittagQI\Translate5\ContentProtection\ContentProtector;
use MittagQI\Translate5\ContentProtection\NumberProtector;
use MittagQI\Translate5\Integration\FileBasedInterface;
use MittagQI\Translate5\LanguageResource\Operation\UpdateSegmentOperation;
use MittagQI\Translate5\Segment\EntityHandlingMode;
use MittagQI\Translate5\Segment\UpdateSegmentStatistics;
use MittagQI\Translate5\Task\TaskEventTrigger;

/**
 * Saving an existing Segment contains a lot of different steps in the business logic, not only just saving the content to the DB
 * Therefore this updater class exists, which provides some functions to update a segment
 *  in the correct way from the business logic view point
 */
class editor_Models_Segment_Updater
{
    /**
     * @var ZfExtended_EventManager
     */
    protected $events = false;

    /**
     * @var editor_Models_Segment
     */
    protected $segment;

    /**
     * @var editor_Models_Task
     */
    protected $task;

    /**
     * @var editor_Models_Segment_UtilityBroker
     */
    protected $utilities;

    /***
     * Timestamp set for the segment before the segment is saved. If no value was set, NOW_ISO constant will be used
     * @var string
     */
    private string $saveTimestamp;

    protected ContentProtector $contentProtector;

    private UpdateSegmentStatistics $updateStatistics;

    public function __construct(
        editor_Models_Task $task,
        private string $userGuid
    ) {
        $this->task = $task;
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', [get_class($this)]);
        $this->utilities = ZfExtended_Factory::get('editor_Models_Segment_UtilityBroker');
        $this->contentProtector = ContentProtector::create($this->utilities->whitespace);
        $this->updateStatistics = UpdateSegmentStatistics::create();
    }

    /**
     * Updates the segment with all dependencies
     * @throws ZfExtended_ValidateException
     * @throws editor_Models_ConfigException
     * @throws editor_Models_Segment_Exception
     */
    public function update(editor_Models_Segment $segment, editor_Models_SegmentHistory $history): void
    {
        $this->segment = $segment;
        $this->segment->setConfig($this->task->getConfig());

        $oldHash = $this->segment->getTargetMd5();

        $this->beforeSegmentUpdate($history);

        $this->segment->save();

        $this->afterSegmentUpdate($history, $oldHash);
    }

    /***
     * Segment update function used when file is reimported into a task. In compare to the original update function,
     * this excludes the language resources tm update.
     *
     * @param editor_Models_Segment $segment
     * @param editor_Models_SegmentHistory $history
     * @return void
     * @throws ZfExtended_ValidateException
     * @throws editor_Models_ConfigException
     * @throws editor_Models_Segment_Exception
     */
    public function updateForReimport(editor_Models_Segment $segment, editor_Models_SegmentHistory $history): void
    {
        $this->segment = $segment;
        $this->segment->setConfig($this->task->getConfig());

        $oldHash = $this->segment->getTargetMd5();

        $this->beforeSegmentUpdate($history);

        $this->segment->save();

        $this->segment->updateIsTargetRepeated($this->segment->getTargetMd5(), $oldHash);

        //update the segment finish count for the current workflow step
        ZfExtended_Factory::get(editor_Models_TaskProgress::class)->changeSegmentEditableAndFinishCount(
            $this->task,
            (int) $this->segment->getAutoStateId(),
            $history->getAutoStateId(),
            (int) $this->segment->getId()
        );

        $this->qaAfterSegmentSaveOnSegmentUpdate();
    }

    /**
     * Required method calls after the segment is saved on segment update
     */
    protected function afterSegmentUpdate(editor_Models_SegmentHistory $history, string $oldSegmentHash): void
    {
        $this->segment->updateIsTargetRepeated($this->segment->getTargetMd5(), $oldSegmentHash);

        if (Zend_Registry::get('config')->runtimeOptions->LanguageResources?->tmQueuedUpdate) {
            //call after segment put handler
            (new TaskEventTrigger())->triggerAfterSegmentUpdate($this->task, $this->segment);
        }
        // TODO TRANSLATE-3579 Delete this branch of logic and config above
        else {
            if (editor_Models_Segment_MatchRateType::isUpdatable($this->segment->getMatchRateType())) {
                UpdateSegmentOperation::create()->updateSegment($this->segment);
            }
        }

        //update the segment finish count for the current workflow step
        ZfExtended_Factory::get(editor_Models_TaskProgress::class)->changeSegmentEditableAndFinishCount(
            $this->task,
            (int) $this->segment->getAutoStateId(),
            $history->getAutoStateId(),
            (int) $this->segment->getId()
        );

        $this->qaAfterSegmentSaveOnSegmentUpdate();
    }

    /**
     * Required method calls before the segment is saved on segment update
     * @throws ZfExtended_ValidateException
     */
    protected function beforeSegmentUpdate(editor_Models_SegmentHistory $history): void
    {
        $this->updateToSort();

        $this->handleWorkflowOnUpdate();

        $this->updateTargetHashAndOriginal();

        $this->updateMatchRateType();

        $this->qaBeforeSegmentSaveOnSegmentUpdate();

        //saving history directly before normal saving,
        // so no exception between can lead to history entries without changing the master segment
        $history->save();

        $this->updateStatistics->updateFor(
            $this->segment,
            $this->task->getWorkflow(),
            (int) $this->task->getWorkflowStep(),
            true
        );

        $this->setTimestampOnSegmentUpdate();
    }

    /**
     * Update toSort fields on segment update
     */
    private function updateToSort(): void
    {
        $allowedAlternatesToChange = $this->segment->getEditableDataIndexList();
        $updateSearchAndSort = array_intersect(array_keys($this->segment->getModifiedValues()), $allowedAlternatesToChange);

        //HERE sanitizeEditedContent check (ob aufgerufen!)
        // Sinnvoll, ja nein? selbes Problem mit dem ENT_XML1 stuff, bei replace all und excel nötig. Wie ists mit der Pretranslation?
        // Wie ist das mit den TMs und en ENT_XML1??

        foreach ($updateSearchAndSort as $field) {
            $this->segment->updateToSort($field);
        }

        //if no content changed, restore the original content (which contains terms, so segment may not be re-tagged)
        $this->segment->restoreNotModfied();
    }

    /**
     * Updates the target original and targetMd5 hash for repetition calculation
     * Can be done only in Workflow Step 1 and if all targets were empty on import
     * This is more a hack as a right solution. See TRANSLATE-885 comments for more information!
     * See also in AlikesegmenController!
     */
    protected function updateTargetHashAndOriginal()
    {
        //TODO: also a check is missing, if task has alternate targets or not.
        // With alternates no recalc is needed at all, since no repetition editor can be used

        $hasher = ZfExtended_Factory::get('editor_Models_Segment_RepetitionHash', [$this->task]);
        /* @var $hasher editor_Models_Segment_RepetitionHash */
        $this->segment->setTargetMd5($hasher->rehashTarget($this->segment));
    }

    /**
     * Before a segment is saved, the matchrate type has to be fixed to valid value
     */
    protected function updateMatchRateType()
    {
        $segment = $this->segment;
        /* @var $segment editor_Models_Segment */
        $givenType = $segment->getMatchRateType();

        /* @var $matchrateType editor_Models_Segment_MatchRateType */
        $matchrateType = ZfExtended_Factory::get(editor_Models_Segment_MatchRateType::class);

        // If segment having a match (coming from pretranslation or UI) was opened and then saved with no changes
        // or with some changes but except when same or another match is (possibly again) taken over in UI
        if (str_starts_with($givenType, editor_Models_LanguageResources_LanguageResource::MATCH_RATE_TYPE_EDITED) === false
            && $segment->getPretrans()) {
            // Add interactive flag
            $matchrateType->init($givenType);
            $matchrateType->add(editor_Models_Segment_MatchRateType::TYPE_INTERACTIVE);
            $segment->setMatchRateType($givenType = (string) $matchrateType);
        }

        //if it was a normal segment edit, without overtaking the match we have to do nothing here
        if (! $segment->isModified('matchRateType') || strpos($givenType, editor_Models_LanguageResources_LanguageResource::MATCH_RATE_TYPE_EDITED) !== 0) {
            return;
        }

        $unknown = function () use ($matchrateType, $givenType, $segment) {
            $matchrateType->initEdited($matchrateType::TYPE_UNKNOWN, $givenType);
            $segment->setMatchRateType((string) $matchrateType);
        };

        $matches = [];
        //if it was an invalid type set it to unknown
        if (! preg_match('/' . editor_Models_LanguageResources_LanguageResource::MATCH_RATE_TYPE_EDITED . ';languageResourceid=([0-9]+)/', $givenType, $matches)) {
            $unknown();

            return;
        }

        //load the used languageResource to get more information about it (TM or MT)
        $languageResourceid = $matches[1];
        $languageresource = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');

        /* @var $languageresource editor_Models_LanguageResources_LanguageResource */
        try {
            $languageresource->load($languageResourceid);
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $unknown();

            return;
        }

        //just to display the TM name too, we add it here to the type
        $type = $languageresource->getServiceName() . ' - ' . $languageresource->getName();

        //set the type
        $matchrateType->initEdited($languageresource->getResource()->getType(), $type);

        // If givan match rate type has interactive-flag - append to resulting object
        if (preg_match('~' . editor_Models_Segment_MatchRateType::TYPE_INTERACTIVE . '~', $givenType)) {
            $matchrateType->add($matchrateType::TYPE_INTERACTIVE);
        }

        //save the type
        $segment->setMatchRateType((string) $matchrateType);

        //if it is tm or term collection and the matchrate is >=100, log the usage
        if (
            ($languageresource->isTm() || $languageresource->isTc())
            && $segment->getMatchRate() >= FileBasedInterface::EXACT_MATCH_VALUE
        ) {
            $this->logAdapterUsageOnSegmentEdit($languageresource);
        }
    }

    /**
     * Applies the import whitespace replacing to the edited user by the content
     * @param string $content the content to be sanitized, the value is modified directly via reference!
     */
    public function sanitizeEditedContent(string &$content, bool $isEditingTargetInFront = false): bool
    {
        $nbsp = json_decode('"\u00a0"');

        //some browsers create nbsp instead of normal whitespaces, since nbsp are removed by the protectWhitespace code below
        // we convert it to usual whitespaces. If there are multiple ones, they are reduced to one then.
        // This is so far the desired behavior. No characters escaped as tag by the import should be addable through the editor.
        $content = str_replace($nbsp, ' ', $content);

        //if there are tags to be ignored, we remove them here
        $oldContent = $content = $this->utilities->internalTag->removeIgnoredTags($content);

        //since our internal tags are a div span construct with plain content in between, we have to replace them first
        $content = $this->utilities->internalTag->protect($content);

        //the following call splits the content at tag boundaries, and sanitizes the textNodes only
        // In the textnode additional / new protected characters (whitespace) is converted to internal tags and then removed
        // This is because the user is not allowed to add new internal tags by adding plain special characters directly (only via adding it as tag in the frontend)
        $content = editor_Models_Segment_Utility::foreachSegmentTextNode(
            $content,
            function ($text) use ($isEditingTargetInFront) {
                return strip_tags(
                    $this->contentProtector->protect(
                        $text,
                        ! $isEditingTargetInFront,
                        $this->task->getSourceLang(),
                        $this->task->getTargetLang(),
                        EntityHandlingMode::Restore,
                        $isEditingTargetInFront ? NumberProtector::alias() : '',
                    )
                );
            }
        );

        //revoke the internaltag replacement
        $content = $this->utilities->internalTag->unprotect($content);

        //return true if some whitespace content was changed
        return editor_Models_Segment_Utility::entityCleanup($content) !== editor_Models_Segment_Utility::entityCleanup($oldContent);
    }

    /***
     * This will write a log entry of how many characters are send to the adapter for translation.
     *
     * @param editor_Models_LanguageResources_LanguageResource $adapter
     */
    protected function logAdapterUsageOnSegmentEdit(editor_Models_LanguageResources_LanguageResource $adapter)
    {
        $manager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $manager editor_Services_Manager */
        $connector = $manager->getConnector(
            $adapter,
            $this->task->getSourceLang(),
            $this->task->getTargetLang(),
            $this->task->getConfig()
        );
        /* @var $connector editor_Services_Connector */
        $connector->logAdapterUsage($this->segment);
    }

    public function setSaveTimestamp(string $saveTimestamp): void
    {
        $this->saveTimestamp = $saveTimestamp;
    }

    /**
     * @throws ZfExtended_ValidateException
     */
    private function handleWorkflowOnUpdate(): void
    {
        //@todo do this with events
        $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');
        /* @var $wfm editor_Workflow_Manager */
        $workflow = $wfm->getActive($this->segment->getTaskGuid());

        $segmentHandler = $workflow->getSegmentHandler();
        $segmentHandler->updateUserGuid($this->userGuid);
        $segmentHandler->beforeSegmentSave($this->segment, $this->task);

        $this->segment->validate();
    }

    /**
     * Necessary quality function calls on segment update before the segment is saved
     */
    private function qaBeforeSegmentSaveOnSegmentUpdate(): void
    {
        // Do preparations for cases when we need full list of task's segments to be analysed for quality detection
        // Currently it is used only for consistency-check to detect consistency qualities BEFORE segment is saved,
        // so that it would be possible to do the same AFTER segment is saved, calculate the difference and insert/delete
        // qualities on segments where needed
        editor_Segment_Quality_Manager::instance()->preProcessTask($this->task, editor_Segment_Processing::EDIT);

        // Update the Quality Tags
        editor_Segment_Quality_Manager::instance()->processSegment($this->segment, $this->task, editor_Segment_Processing::EDIT);
    }

    /**
     * Necessary quality function calls on segment update after the segment is saved
     */
    private function qaAfterSegmentSaveOnSegmentUpdate(): void
    {
        // Update qualities for cases when we need full list of task's segments to be analysed for quality detection
        editor_Segment_Quality_Manager::instance()->postProcessTask($this->task, editor_Segment_Processing::EDIT);
    }

    /***
     * Set the segment timestamp from property or default when the property is not set
     * @return void
     */
    private function setTimestampOnSegmentUpdate(): void
    {
        if (! isset($this->saveTimestamp)) {
            $this->saveTimestamp = NOW_ISO;
        }
        $this->segment->setTimestamp($this->saveTimestamp); //see TRANSLATE-922
    }
}
