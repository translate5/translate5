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

use MittagQI\Translate5\Acl\Rights;
use MittagQI\ZfExtended\Tools\Markup;

/**
 * Import the whole task from an earlier exported Excel-file
 */
class editor_Models_Import_Excel extends editor_Models_Excel_AbstractExImport
{
    /**
     * @var editor_Models_Excel_ExImport
     */
    protected $excel;

    /**
     * @var editor_Models_Task
     */
    protected $task;

    /**
     * @var ZfExtended_Models_User
     */
    protected $user;

    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $segmentTagger;

    /**
     * @var editor_Models_Export_DiffTagger_TrackChanges
     */
    protected $diffTagger;

    /**
     * @var editor_Models_Excel_TagStructureChecker
     */
    protected $tagStructureChecker;

    /**
     * A list of segment-numbers and notices about the segment (e.g. invalid tag-structure in segment).
     * This list is shown after the reimport with the hint that the user has to check the here notet segments.
     *
     * @var array
     */
    protected $segmentErrors = [];

    /**
     * reimport $filename xls into $task.
     * the fiel $filename is located inside the /data/importedTasks/<taskGuid>/excelReimport/ folder
     * returns TRUE if everything is OK, FALSE on (fatal) error
     * @param string $filename
     * @return bool
     */
    public function __construct(editor_Models_Task $task, $filename, $currentUserGuid)
    {
        parent::__construct();
        $this->task = $task;

        // task data must be actualized
        $task->createMaterializedView();

        // load the excel
        $this->excel = editor_Models_Excel_ExImport::loadFromExcel($task->getAbsoluteTaskDataPath() . '/excelReimport/' . $filename);

        // do formal checkings of the loaded excel data aginst the task
        // on error an editor_Models_Excel_ExImportException is thrown
        $this->formalCheck();

        $this->user = ZfExtended_Factory::get('ZfExtended_Models_User');
        $this->user->loadByGuid($currentUserGuid);

        // - load segment tagger to extract pure text from t5Segment
        $this->segmentTagger = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');

        // - load diffTagger for markup changes with TrackChanges Markup
        $this->diffTagger = ZfExtended_Factory::get('editor_Models_Export_DiffTagger_TrackChanges', [$task, $this->user]);

        // - load tag structure checker
        $this->tagStructureChecker = ZfExtended_Factory::get('editor_Models_Excel_TagStructureChecker');
    }

    public function reimport(ZfExtended_Zendoverwrites_Translate $translate, ZfExtended_Models_Messages $restMessages, ZfExtended_Models_User $user)
    {
        $this->segmentErrors = [];
        // contains the TUA which is used to alter the segments
        $tua = $this->prepareTaskUserAssociation();

        try {
            // now handle each segment from the excel
            $this->loopOverExcelSegments();
        } finally {
            //if it was a PM override, delete it again
            if ((bool) $tua->getIsPmOverride()) {
                $tua->delete();
            }
        }

        // unlock task and set state to 'open'
        $this->taskUnlock($this->task);

        if (! empty($this->segmentErrors)) {
            $logger = Zend_Registry::get('logger')->cloneMe('editor.task.exceleximport');
            /* @var $logger ZfExtended_Logger */

            $msg = 'Error on excel reimport in the following segments. Please check the following segment(s):';
            // log warning 'E1141' => 'Excel Reimport: at least one segment needs to be controlled.',
            $logger->warn('E1142', $msg . "\n{segments}", [
                'task' => $this->task,
                'segments' => join("\n", array_map(function (excelExImportSegmentContainer $item) {
                    return '#' . $item->nr . ': ' . $item->comment;
                }, $this->segmentErrors)),
            ]);
            $msg = $translate->_('Die Excel-Datei konnte reimportiert werden, die nachfolgenden Segmente beinhalten aber Fehler und müssen korrigiert werden:');
            $restMessages->addWarning($msg, $logger->getDomain(), null, array_map(function (excelExImportSegmentContainer $item) {
                return [
                    'type' => $item->nr,
                    'error' => $item->comment,
                ];
            }, $this->segmentErrors));

            // send mails
            $mailer = ZfExtended_Factory::get(ZfExtended_TemplateBasedMail::class);
            /* @var $mailer ZfExtended_TemplateBasedMail */
            $mailer->setParameters([
                'segmentErrors' => $this->segmentErrors,
                'task' => $this->task,
            ]);
            $pm = ZfExtended_Factory::get(ZfExtended_Models_User::class);
            /* @var $pm ZfExtended_Models_User */
            $pm->loadByGuid($this->task->getPmGuid());
            $mailer->setReplyTo($pm->getEmail(), $pm->getUserName());
            $mailer->setTemplate('workflow/pm/notifyExcelReimportErrors.phtml');
            $mailer->sendToUser($user);
        }
    }

    /**
     * Loops over each Excel segment and saves it back into translate5 if necessary
     */
    protected function loopOverExcelSegments()
    {
        $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');
        /* @var $wfm editor_Workflow_Manager */
        $workflow = $wfm->getActiveByTask($this->task);

        foreach ($this->excel->getSegments() as $segment) {
            //segment must be initialized completly new
            $t5Segment = ZfExtended_Factory::get('editor_Models_Segment');
            /* @var $t5Segment editor_Models_Segment */

            // new segment is the one from excel
            $newSegment = $segment->target;

            // - load the model that handles the t5 segments
            $t5Segment->loadBySegmentNrInTask($segment->nr, $this->task->getTaskGuid());

            // detect $orgSegmentAsExcel as content of the t5 target segment
            $orgSegmentAsExcel = $this->segmentTagger->toExcel($t5Segment->getTargetEdit());

            // do nothing if segment has not changed
            if ($newSegment == $orgSegmentAsExcel) {
                $this->addCommentOnly($t5Segment, $segment);

                continue;
            }

            // Escape taglike placeholders before tags structure is checked
            $newSegment = Markup::escapeTaglikePlaceholders($newSegment);

            // Check tags structure
            $this->checkTagStructure($newSegment, $orgSegmentAsExcel, $segment);

            // add TrackChanges informations comparing the new segment (from excel) with the t5 segment (converted to excel tagging)
            // but only if task is not in workflowStep 'translation'
            // @FIXME: ADD check Plugin.TrackChanges active, or something similar.
            if (! $workflow->isStepOfRole($this->task->getWorkflowStepName(), [$workflow::ROLE_TRANSLATOR])) {
                $newSegment = $this->diffTagger->diffSegment($orgSegmentAsExcel, $newSegment, date(NOW_ISO), $this->user->getUserName());
            }

            // restore org. tags; detect tag-map from t5 SOURCE segment. Only there all original tags are present.
            $tempMap = [];
            $this->segmentTagger->toExcel($t5Segment->getSource(), $tempMap);
            $newSegment = $this->segmentTagger->reapply2dMap($newSegment, $tempMap);

            $this->saveSegment($t5Segment, $newSegment);

            // on every changed segment, add a comment that it was edited
            $comment = $this->addComment("Changed in external Excel editing.", $t5Segment, true);
            // save (new) comment for the segment (if not empty in excel)
            if (! empty($segment->comment)) {
                $comment = $this->addComment($segment->comment, $t5Segment);
            }
            $comment->updateSegment($t5Segment, $this->task->getTaskGuid());
        }
    }

    /**
     * If there is only a comment and no content change, we add only that comment
     */
    protected function addCommentOnly(editor_Models_Segment $t5Segment, excelExImportSegmentContainer $segment)
    {
        if (! empty($segment->comment)) {
            $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');
            /* @var $wfm editor_Workflow_Manager */
            $wfm->getActive($this->task->getTaskGuid())->getSegmentHandler()->beforeCommentedSegmentSave($t5Segment, $this->task);
            $comment = $this->addComment($segment->comment, $t5Segment);
            $comment->updateSegment($t5Segment, $this->task->getTaskGuid());
        }
    }

    /**
     * prepares the segment and content and saves it then
     */
    protected function saveSegment(editor_Models_Segment $t5Segment, string $newContent)
    {
        //the history entry must be created before the original entity is modified
        $history = $t5Segment->getNewHistoryEntity();
        //update the segment
        $updater = ZfExtended_Factory::get(
            editor_Models_Segment_Updater::class,
            [$this->task, $this->user->getUserGuid()]
        );

        if ($updater->sanitizeEditedContent($newContent, true)) {
            $this->addSegmentError(
                $t5Segment->getSegmentNrInTask(),
                'Some non representable characters were removed from the segment'
                . ' (multiple white-spaces, tabs, line-breaks etc.)!'
            );
        }

        $t5Segment->setTargetEdit($newContent);
        $t5Segment->setUserGuid($this->user->getUserGuid());
        $t5Segment->setUserName($this->user->getUserName());
        $updater->update($t5Segment, $history);
    }

    /**
     * checks the structure of the tags and logs error messages
     */
    protected function checkTagStructure(
        string $newSegment,
        string $orgSegmentAsExcel,
        excelExImportSegmentContainer $segment
    ) {
        // check structure of the new segment (from excel)
        if (! $this->tagStructureChecker->check($newSegment)) {
            $this->addSegmentError(
                $segment->nr,
                'tags in segment are not well-structured. ' . $this->tagStructureChecker->getError()
            );
        }
        $countNewSegmentTags = $this->tagStructureChecker->getCount();

        // check count tags of the new segment (from excel) against the org. segement from t5
        $this->tagStructureChecker->check($orgSegmentAsExcel);
        if ($this->tagStructureChecker->getCount() != $countNewSegmentTags) {
            $this->addSegmentError($segment->nr, 'count of tags in segment changed in excel');
        }
    }

    /**
     * prepares the isPmOveride taskUserAssoc if needed!
     * @throws ReflectionException
     * @throws Zend_Acl_Exception
     */
    protected function prepareTaskUserAssociation(): editor_Models_TaskUserAssoc
    {
        $userTaskAssoc = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');

        /* @var $userTaskAssoc editor_Models_TaskUserAssoc */
        try {
            $acl = ZfExtended_Acl::getInstance();
            $isUserPm = $this->task->getPmGuid() == $this->user->getUserGuid();
            $isEditAllAllowed = $acl->isInAllowedRoles(
                $this->user->getRoles(),
                Rights::ID,
                Rights::EDIT_ALL_TASKS
            );
            $isEditAllTasks = $isEditAllAllowed || $isUserPm;
            //if the user is allowe to load all, use the default loader
            if ($isEditAllTasks) {
                $userTaskAssoc = editor_Models_Loaders_Taskuserassoc::loadByTaskForceWorkflowRole(
                    $this->user->getUserGuid(),
                    $this->task
                );
            } else {
                $userTaskAssoc = editor_Models_Loaders_Taskuserassoc::loadByTask(
                    $this->user->getUserGuid(),
                    $this->task
                );
            }
            $isPmOverride = (bool) $userTaskAssoc->getIsPmOverride();
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $userTaskAssoc->setUserGuid($this->user->getUserGuid());
            $userTaskAssoc->setTaskGuid($this->task->getTaskGuid());
            $userTaskAssoc->setRole('');
            $userTaskAssoc->setState('');
            $userTaskAssoc->setWorkflow($this->task->getWorkflow());
            $userTaskAssoc->setWorkflowStepName('');
            $isPmOverride = true;
            $userTaskAssoc->setIsPmOverride($isPmOverride);
        }
        $userTaskAssoc->save();

        return $userTaskAssoc;
    }

    /**
     * Add a comment to a segment in t5.
     * @param bool $noIntro
     */
    protected function addComment(string $commentText, editor_Models_Segment $segment, $noIntro = false): editor_Models_Comment
    {
        $comment = ZfExtended_Factory::get('editor_Models_Comment');
        /* @var $comment editor_Models_Comment */
        $comment->init();

        $comment->setModified(NOW_ISO);
        $comment->setCreated(NOW_ISO);

        $comment->setTaskGuid($this->task->getTaskGuid());
        $comment->setSegmentId($segment->getId());

        $comment->setUserGuid($this->user->getUserGuid());
        $comment->setUserName($this->user->getUserName());

        $tempComment = ($noIntro) ? $commentText : 'Comment from external editing in Excel:' . "\n" . $commentText;
        $comment->setComment($tempComment);

        $comment->validate();
        $comment->save();

        return $comment;
    }

    /**
     * Do some formal checks, by comparing the informations in the excel with the informations of the task<br/>
     * - compare the task-guid<br/>
     * - compare the number of segments<br/>
     * - compare all segments if an empty segment in excel was not-empty in task<br/>
     *
     * @throws editor_Models_Excel_ExImportException
     */
    protected function formalCheck()
    {
        // compare task-guid
        if ($this->task->getTaskGuid() != $this->excel->getTaskGuid()) {
            // throw exception 'E1138' => 'Excel Reimport: Formal check failed: task-guid differs in task compared to the excel.'
            throw new editor_Models_Excel_ExImportException('E1138', [
                'task' => $this->task,
            ]);
        }

        // compare number of segments.
        $t5Segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $tempCountTaskSegments = $t5Segment->count($this->task->getTaskGuid());

        $tempExcelSegments = $this->excel->getSegments();
        if ($tempCountTaskSegments != count($tempExcelSegments)) {
            // throw exception 'E1139' => 'Excel Reimport: Formal check failed: number of segments differ in task compared to the excel.'
            throw new editor_Models_Excel_ExImportException('E1139', [
                'task' => $this->task,
            ]);
        }

        // compare all segments if an empty segment in excel is not-empty in task
        $emptySegments = [];
        foreach ($tempExcelSegments as $excelSegment) {
            if (empty($excelSegment->target)) {
                $t5Segment->loadBySegmentNrInTask($excelSegment->nr, $this->task->getTaskGuid());
                if (! empty($t5Segment->getTargetEdit())) {
                    $emptySegments[] = $excelSegment->nr;
                }
            }
        }
        if (! empty($emptySegments)) {
            // throw exception 'E1140' => 'Excel Reimport: Formal check failed: segment #{segmentNr} is empty in excel while there was content in the the original task.'
            throw new editor_Models_Excel_ExImportException('E1140', [
                'task' => $this->task,
                'segmentNr' => join(',', $emptySegments),
            ]);
        }
    }

    /**
     * add an segment error to the internal segment-error-list.
     */
    protected function addSegmentError(int $segmentNr, string $hint): void
    {
        //we abuse the segment container for transporting the error messages
        $error = new excelExImportSegmentContainer();
        $error->nr = $segmentNr;
        $error->comment = $hint;
        $this->segmentErrors[] = $error;
    }
}
