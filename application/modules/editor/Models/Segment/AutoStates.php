<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**
 * Segment Auto States Helper Class
 * This class contains all autoState definitions and all autoState transitions, available by api
 *
 * Warning: On changing/adding autostates, change also frontend hardcoded list Editor.data.segments.autoStates
 *          filled in Editor.controller.Editor::init()
 */
class editor_Models_Segment_AutoStates {
    
    /**
     * "translated" / 'Übersetzt' ehemals 'Nicht lektoriert'
     * @var integer
     */
    const TRANSLATED = 0;
    
    /**
     * "reviewed" / 'Geprüft' ehemals 'Lektoriert'
     * @var integer
     */
    const REVIEWED = 1;
    
    /**
     * "autoreviewed" / 'Autogeprüft' ehemals 'Auto-Lektoriert'
     * @var integer
     */
    const REVIEWED_AUTO = 2;
    
    /**
     * "locked" / 'Gesperrt' => locking of 100% matches
     * @var integer
     */
    const BLOCKED = 3;
    
    /**
     * "untranslated" / "Nicht übersetzt" → kommt wie 0 initial aus dem Import
     * @var integer
     */
    const NOT_TRANSLATED = 4;
    
    /**
     * "reviewed untouched, auto-set" / "Geprüft" → Beim Abschließen eines Tasks werden alle "unberührten" Segmente auf diesen Status gesetzt.
     * @var integer
     */
    const REVIEWED_UNTOUCHED = 5;
    
    /**
     * "reviewed, untouched" / "Geprüft, unverändert" → Wenn das Segment geöffnet und dann gespeichert wurde ohne was zu verändern
     * @var integer
     */
    const REVIEWED_UNCHANGED = 6;
    
    /**
     * "autoreviewed, untouched" / "Autogeprüft, unverändert" → analog zu 6 bei den Wiederholungen
     * @var integer
     */
    const REVIEWED_UNCHANGED_AUTO = 7;
    
    /**
     * "translator reviewed" / "Übersetzer geprüft" → wenn ein Übersetzer das Segment verändert hat
     * @var integer
     */
    const REVIEWED_TRANSLATOR = 8;
    
    /**
     * "translator autoreviewed" / "Übersetzer autogeprüft" → wenn ein Übersetzer das Segment verändert hat
     * @var integer
     */
    const REVIEWED_TRANSLATOR_AUTO = 9;
    
    /**
     * reviewed by a pm not associated in the workflow of a task
     * @var integer
     */
    const REVIEWED_PM = 10;
    
    /**
     * reviewed through the repetition editor by a pm not associated in the workflow of a task
     * @var integer
     */
    const REVIEWED_PM_AUTO = 11;
    
    /**
     * reviewed but unchanged by a pm not associated in the workflow of a task
     * @var integer
     */
    const REVIEWED_PM_UNCHANGED = 12;
    
    /**
     * reviewed but unchanged through the repetition editor by a pm not associated in the workflow of a task
     * @var integer
     */
    const REVIEWED_PM_UNCHANGED_AUTO = 13;
    
    /**
     * if a translator uses the repetition editor, the segments are getting that state:
     * @var integer
     */
    const TRANSLATED_AUTO = 14;
    
    /**
     * segments pretranslated by translate5 (or external tool and imported with status pretranslated)
     * This status is a kind of initial status, so it does not belong to role/workflow step translation!
     * @var integer
     */
    const PRETRANSLATED = 15;
    
    /**
     * Internal state used to show segment is pending
     * @var integer
     */
    const EDITING_BY_USER = 998;
    
    /**
     * Internal state used to show segment is pending
     * @var integer
     */
    const PENDING = 999;
    
    protected $states = array(
        self::TRANSLATED => 'Übersetzt',
        self::TRANSLATED_AUTO => 'Übersetzt, auto',
        self::NOT_TRANSLATED => 'Nicht übersetzt',
        self::REVIEWED => 'Lektoriert',
        self::REVIEWED_AUTO => 'Lektoriert, auto',
        self::BLOCKED => 'Gesperrt',
        self::REVIEWED_UNTOUCHED => 'Lektoriert, unberührt, auto-gesetzt beim Aufgabenabschluss',
        self::REVIEWED_UNCHANGED => 'Lektoriert, unverändert',
        self::REVIEWED_UNCHANGED_AUTO => 'Lektoriert, unverändert, auto',
        self::REVIEWED_TRANSLATOR => '2. Lektorat',
        self::REVIEWED_TRANSLATOR_AUTO => '2. Lektorat, auto',
        self::REVIEWED_PM => 'PM lektoriert',
        self::REVIEWED_PM_AUTO => 'PM lektoriert, auto',
        self::REVIEWED_PM_UNCHANGED => 'PM lektoriert, unverändert',
        self::REVIEWED_PM_UNCHANGED_AUTO => 'PM lektoriert, unverändert, auto',
        self::PRETRANSLATED => 'Vorübersetzt'
    );
    
    /**
     * returns a map with state as index and translated text as value
     * @return array
     */
    public function getLabelMap() {
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $translate ZfExtended_Zendoverwrites_Translate */;
        //no json_encode because later on passed to php2js, which does json-encoding
        $states = $this->states;

        //only needed in frontend:
        $states[self::PENDING] = 'wird ermittelt...';
        $states[self::EDITING_BY_USER] = 'In Bearbeitung';
        
        return array_map(function($value) use ($translate) {
            return $translate->_($value);
        }, $states);
    }
    
    /**
     * returns all valid state integers as list
     * @return array
     */
    public function getStates() {
        return array_keys($this->states);
    }
    
    /**
     * returns a mapping between user workflow roles, and segment auto states to be used for this role
     * @return multitype:string
     */
    public function getRoleToStateMap() {
        return array(
          ACL_ROLE_PM => array(
            self::REVIEWED_PM,
            self::REVIEWED_PM_AUTO,
            self::REVIEWED_PM_UNCHANGED,
            self::REVIEWED_PM_UNCHANGED_AUTO,
          ),
          editor_Workflow_Abstract::ROLE_TRANSLATOR => [
              // may not contain the state PRETRANSLATED, since PRETRANSLATED does not belong (in statistics, workflow etc) to the translators!
              self::TRANSLATED,
              self::TRANSLATED_AUTO,
          ],
          editor_Workflow_Abstract::ROLE_REVIEWER => array(
            self::REVIEWED,
            self::REVIEWED_AUTO,
            self::REVIEWED_UNTOUCHED,
            self::REVIEWED_UNCHANGED,
            self::REVIEWED_UNCHANGED_AUTO,
          ),
          editor_Workflow_Abstract::ROLE_TRANSLATORCHECK => array(
            self::REVIEWED_TRANSLATOR,
            self::REVIEWED_TRANSLATOR_AUTO,
          )
        );
    }
    
    /**
     * returns a list of states, which means that the segment was just "confirmed" but was not edited by the user.
     */
    public function getNotEditedStates() {
        return [
            self::REVIEWED_UNTOUCHED,
            self::REVIEWED_UNCHANGED,
            self::REVIEWED_UNCHANGED_AUTO,
            self::REVIEWED_PM_UNCHANGED,
            self::REVIEWED_PM_UNCHANGED_AUTO,
        ];
    }
    
    /**
     * returns the state to use for Alikesegments
     *
     * @param editor_Models_Segment $segment
     * @param editor_Models_TaskUserAssoc $tua
     * @return integer
     */
    public function calculateAlikeState(editor_Models_Segment $segment, editor_Models_TaskUserAssoc $tua) {
        $calculatedState = $this->calculateSegmentState($segment, $tua);
        switch ($calculatedState) {
            case self::REVIEWED:
                return self::REVIEWED_AUTO;
            case self::TRANSLATED:
                return self::TRANSLATED_AUTO;
            case self::REVIEWED_UNCHANGED:
                return self::REVIEWED_UNCHANGED_AUTO;
            case self::REVIEWED_PM:
                return self::REVIEWED_PM_AUTO;
            case self::REVIEWED_PM_UNCHANGED:
                return self::REVIEWED_PM_UNCHANGED_AUTO;
            case self::REVIEWED_TRANSLATOR:
                return self::REVIEWED_TRANSLATOR_AUTO;
            default:
                return $calculatedState;
        }
    }
    
    /**
     * calculates the initial autoStateId of an segment in the import process
     * @param editor_Models_Import_FileParser_SegmentAttributes $segmentAttributes
     * @return integer
     */
    public function calculateImportState(editor_Models_Import_FileParser_SegmentAttributes $segmentAttributes): int
    {
        if(! $segmentAttributes->editable) {
            return self::BLOCKED;
        }
        if($segmentAttributes->isPreTranslated) {
            return self::PRETRANSLATED;
        }
        if($segmentAttributes->isTranslated) {
            return self::TRANSLATED;
        }
        return self::NOT_TRANSLATED;
    }
    
    /**
     * calculates the initial autoStateId of an segment in the import process
     * @param bool $isEditable
     * @param bool $isTranslated
     * @return integer
     */
    public function restoreImportState($isEditable, $isTranslated): int
    {
        if(! $isEditable) {
            return self::BLOCKED;
        }
        if($isTranslated) {
            return self::TRANSLATED;
        }
        return self::NOT_TRANSLATED;
    }
    
    /**
     * calculates the initial autoStateId of an segment in the import process
     * @param bool $isEditable
     * @return integer
     */
    public function calculatePretranslationState($isEditable): int
    {
        if(! $isEditable) {
            return self::BLOCKED;
        }
        return self::PRETRANSLATED;
    }
    
    /**
     * calculates and returns the autoStateID to use
     * @param editor_Models_Segment $segment
     * @param editor_Models_TaskUserAssoc $tua
     */
    public function calculateSegmentState(editor_Models_Segment $segment, editor_Models_TaskUserAssoc $tua) {
        $isModified = $segment->isDataModifiedAgainstOriginal();
        
        $workflow = ZfExtended_Factory::get('editor_Workflow_Manager')->getActive($segment->getTaskGuid());
        /* @var $workflow editor_Workflow_Abstract */
        
        if($segment->getAutoStateId() == self::BLOCKED){
            return self::BLOCKED;
        }
        
        if($tua->getRole() == $workflow::ROLE_TRANSLATOR) {
            return self::TRANSLATED;
        }
        if($tua->getRole() == $workflow::ROLE_TRANSLATORCHECK) {
            return self::REVIEWED_TRANSLATOR;
        }
        if($tua->getRole() == $workflow::ROLE_REVIEWER) {
            return $isModified ? self::REVIEWED : self::REVIEWED_UNCHANGED;
        }
        
        if($this->isEditWithoutAssoc($tua)){
            return $isModified ? self::REVIEWED_PM : self::REVIEWED_PM_UNCHANGED;
        }
        
        //if no role match, return old value
        return $segment->getAutoStateId();
    }
    
    /**
     * sets the reviewer untouched state for a given taskGuid
     *
     * @param string $taskGuid
     */
    public function setUntouchedState(string $taskGuid, ZfExtended_Models_User $user) {
        $bulkUpdater = ZfExtended_Factory::get('editor_Models_Segment_AutoStates_BulkUpdater',[
            $user
        ]);
        /* @var $bulkUpdater editor_Models_Segment_AutoStates_BulkUpdater */
        
        $history  = ZfExtended_Factory::get('editor_Models_SegmentHistory');
        /* @var $history editor_Models_SegmentHistory */
        
        //NOTE: no record in segment data history is inserted because
        //there is no related data change for this table
        
        //add record in the segment history
        $toBeUntouched = [
            self::TRANSLATED,
            self::TRANSLATED_AUTO,
            self::NOT_TRANSLATED,
            self::PRETRANSLATED
        ];
        
        $history->createHistoryByAutoState($taskGuid, $toBeUntouched);
        
        foreach($toBeUntouched as $state) {
            $bulkUpdater->updateAutoState($taskGuid, $state, self::REVIEWED_UNTOUCHED);
        }
    }
    
    /**
     * sets the untouched state for a given taskGuid back to the initial states
     *
     * @param string $taskGuid
     */
    public function setInitialStates(string $taskGuid) {
        $bulkUpdater = ZfExtended_Factory::get('editor_Models_Segment_AutoStates_BulkUpdater');
        /* @var $bulkUpdater editor_Models_Segment_AutoStates_BulkUpdater */
        
        $history  = ZfExtended_Factory::get('editor_Models_SegmentHistory');
        /* @var $history editor_Models_SegmentHistory */
        //NOTE: no record in segment data history is inserted because
        //there is no related data change for this table
        $history->createHistoryByAutoState($taskGuid,[self::REVIEWED_UNTOUCHED]);
        
        $bulkUpdater->resetUntouchedFromHistory($taskGuid, self::REVIEWED_UNTOUCHED);
        
    }
    
    /**
     * changes the state after add / edit only a comment of this segment (no segment change)
     * @param editor_Models_Segment $segment
     */
    public function updateAfterCommented(editor_Models_Segment $segment, editor_Models_TaskUserAssoc $tua) {
        $workflow = ZfExtended_Factory::get('editor_Workflow_Manager')->getActive($segment->getTaskGuid());
        if($tua->getRole() == $workflow::ROLE_TRANSLATORCHECK) {
            $segment->setAutoStateId(self::REVIEWED_TRANSLATOR); //TODO if we have TRANSLATE-1704 then this must be changed too
            return;
        }
        if($tua->getRole() == $workflow::ROLE_REVIEWER) {
            $segment->setAutoStateId(self::REVIEWED_UNCHANGED);
            return;
        }
        if($this->isEditWithoutAssoc($tua)){
            $segment->setAutoStateId(self::REVIEWED_PM_UNCHANGED);
            return;
        }
    }
    
    /**
     * Returns a map with counts of each state in a task
     * @param string $taskGuid
     * @return array
     */
    public function getStatistics(string $taskGuid): array {
        $seg = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $seg editor_Models_Segment */
        $stats = $seg->getAutoStateCount($taskGuid);
        $result = array_fill_keys($this->getStates(), 0);
        foreach($stats as $stat) {
            $result[$stat['autoStateId']] = $stat['cnt'];
        }
        return $result;
    }
    
    /**
     * returns true if user has right to edit all Tasks, checks optionally the workflow role of the user
     * @param editor_Models_TaskUserAssoc $tua optional, if not given only acl is considered
     */
    protected function isEditWithoutAssoc(editor_Models_TaskUserAssoc $tua) {
        $userSession = new Zend_Session_Namespace('user');
        $role = $tua && $tua->getRole();
        $acl = ZfExtended_Acl::getInstance();
        
        //load the task so the check if the loagged user is also the pm to the task
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($tua->getTaskGuid());
        $sameUserGuid = $task->getPmGuid() === $userSession->data->userGuid;
        $systemUser = $userSession->data->userGuid == ZfExtended_Models_User::SYSTEM_GUID;
        $editAllTasks = $acl->isInAllowedRoles($userSession->data->roles, 'backend', 'editAllTasks');
        return empty($role) && ($editAllTasks || $sameUserGuid || $systemUser);
    }
    
    /***
     * Get the required auto states for segment workflow step loading.
     * This specific state is used for changedSegments loading in the workflow mails.
     * @param bool $withPm: set to true to include the required pm autostates
     * @return array
     */
    public function getForWorkflowStepLoading(bool $withPm = false)  {
        //required autostates
        //1 "Reviewed", 2 "Reviewed, auto-set", 8 "2. Review", 9 "2. Review, auto"
        $autoStates = [
            editor_Models_Segment_AutoStates::REVIEWED,
            editor_Models_Segment_AutoStates::REVIEWED_AUTO,
            editor_Models_Segment_AutoStates::REVIEWED_TRANSLATOR,
            editor_Models_Segment_AutoStates::REVIEWED_TRANSLATOR_AUTO
        ];
        //if pmChanges is active, add additional required autostates
        //10 PM reviewed,11 PM reviewed, auto-set
        if($withPm){
            $autoStates = array_merge($autoStates,[
                editor_Models_Segment_AutoStates::REVIEWED_PM,
                editor_Models_Segment_AutoStates::REVIEWED_PM_AUTO
            ]);
        }
        return $autoStates;
    }
    
    /**
     * returns true if the given state is state produced by a translator
     * @param int $autoState
     * @return bool
     */
    public function isTranslationState(int $autoState): bool
    {
        return in_array($autoState, [self::TRANSLATED, self::TRANSLATED_AUTO]);
    }
}
